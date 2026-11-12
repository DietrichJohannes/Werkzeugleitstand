# BSH Werkzeugleitstand – Lokring

Web-Anwendung zum Verwalten und Verfolgen von Lokring-Werkzeugen: Standorte, Status
(Bereit / Aktiv / Wartung / Verschrottet), Grundkörper, Backensets, Verbindungen,
Ersatzteile, Reparaturen und Ausfälle.

Technik: PHP 8.1+ (ohne Framework), MariaDB/MySQL, JavaScript und CSS ohne Build-Schritt.

## Ordnerstruktur

```
config/               Konfiguration
  config.example.php  Vorlage (im Git)
  config.php          echte Werte mit Passwörtern (NICHT im Git)
database/             Datenbank-Schema
storage/documents/    hochgeladene BoM-PDFs (NICHT im Git, der Webserver muss hier schreiben dürfen)
src/                  PHP-Kern – vom Webserver NICHT erreichbar
  config.php          config('db.host') usw.
  database.php        db() – Datenbankverbindung
  auth.php            Rollen & Rechte (PERMISSIONS)
  api_bootstrap.php   gemeinsame Basis aller API-Endpunkte
  tool_status.php     Werkzeug-Status (IDs + Texte)
public/               ← einziger Ordner, den der Webserver ausliefert
  index.php           Startseite (Leitstand)
  api/                REST-API, ein Endpunkt pro Datei (tools.php, repairs.php, …)
  pages/              Unterseiten: overview, management, editor, line, instruction
  components/         wiederverwendbare Dialoge (Werkzeug, Ersatzteile, Reparatur/Ausfall)
  assets/css/         gemeinsame Styles
  assets/js/          gemeinsame Skripte (api.js, tool-status.js, location-status.js)
```

Jede Unterseite liegt in `public/pages/<name>/` mit `index.php`, `script.js` und
optional `styles.css`.

## Lokal starten

```bash
cp config/config.example.php config/config.php   # einmalig, dann Werte eintragen
php -S localhost:8000 -t public
```

Dann <http://localhost:8000> öffnen.

Für die Entwicklung in `config/config.php` setzen:

```php
'environment' => 'development',
'auth' => ['dev_fake_role' => 'einrichter'],   // jeder Aufruf gilt als angemeldet
```

`dev_fake_role` wirkt **nur** bei `'environment' => 'development'`.

## Auf einem Server installieren

1. Repository auf den Server kopieren, z. B. nach `/var/www/lokring`.
2. `config/config.php` aus der Vorlage anlegen und ausfüllen:
   - `environment` auf `'production'` setzen
   - `auth.dev_fake_role` auf `null` setzen
   - `session.cookie_secure` auf `true` setzen, wenn die Seite über HTTPS läuft
3. Datenbank anlegen (siehe `database/README.md`).
4. Den Webserver so einrichten, dass **nur `public/`** ausgeliefert wird.
   Die Anwendung muss im Wurzelverzeichnis der (Sub-)Domain laufen, z. B.
   `https://werkzeuge.intranet.firma.de/`, nicht in einem Unterordner.

Apache (VirtualHost):

```apache
<VirtualHost *:443>
    ServerName werkzeuge.intranet.firma.de
    DocumentRoot /var/www/lokring/public

    <Directory /var/www/lokring/public>
        AllowOverride None
        Require all granted
    </Directory>
</VirtualHost>
```

### Anmeldung (Kerberos / AD)

Die Anmeldung läuft per Single Sign-On: Apache prüft das Kerberos-Ticket des Windows-Logins,
PHP liest danach Name, E-Mail und Gruppen per LDAP aus dem AD (`src/ad_login.php`).

| AD-Gruppe (`config.php`)      | Rolle        | Darf                        |
|-------------------------------|--------------|-----------------------------|
| `auth.ldap.group_einrichter`  | `einrichter` | alles inkl. Stammdaten      |
| `auth.ldap.group_user`        | `user`       | Status, Ausfälle, Reparaturen, Reinigungen |
| keine der beiden              | `beobachter` | nur lesen                   |

Verschachtelte Gruppen zählen mit. Rollenänderungen im AD greifen nach spätestens
`auth.role_refresh_minutes` (Standard 15). Ist das AD nicht erreichbar, gibt es nur Leserechte.

**1. Im AD (einmalig)**

- Zwei Gruppen anlegen, z. B. `GG-Werkzeugleitstand-Einrichter` und `GG-Werkzeugleitstand-Benutzer`.
- Servicebenutzer für **Kerberos** (z. B. `svc-wzl-http`) mit SPN und Keytab, auf einem DC:
  ```
  setspn -S HTTP/werkzeuge.intranet.firma.de FIRMA\svc-wzl-http
  ktpass -princ HTTP/werkzeuge.intranet.firma.de@FIRMA.LOCAL -mapuser FIRMA\svc-wzl-http ^
         -pass * -crypto AES256-SHA1 -ptype KRB5_NT_PRINCIPAL -out werkzeugleitstand.keytab
  ```
  Beim Benutzer „Dieses Konto unterstützt Kerberos AES 256“ aktivieren.
- Servicebenutzer für **LDAP** (z. B. `svc-werkzeugleitstand`), normales Konto mit Leserechten,
  Passwort läuft nicht ab. Kann auch derselbe wie oben sein.

**2. Auf dem Server**

```bash
apt install libapache2-mod-auth-gssapi php-ldap
a2enmod auth_gssapi
install -o root -g www-data -m 640 werkzeugleitstand.keytab /etc/apache2/werkzeugleitstand.keytab
```

`/etc/krb5.conf` muss den AD-Realm kennen (`default_realm = FIRMA.LOCAL`, KDCs per DNS).
Im VirtualHost (zusätzlich zum Block oben):

```apache
    <Location />
        AuthType GSSAPI
        AuthName "Werkzeugleitstand"
        GssapiCredStore keytab:/etc/apache2/werkzeugleitstand.keytab
        GssapiAllowedMech krb5
        GssapiLocalName Off
        # Fallback-Passwortabfrage für Geräte ohne Domänenanmeldung, sonst Off:
        GssapiBasicAuth Off
        Require valid-user
    </Location>
```

Danach `auth.ldap` in `config/config.php` ausfüllen (Vorlage: `config/config.example.php`)
und `auth.dev_fake_role` auf `null` setzen.

**3. Im Browser**

- Die Seite immer über den Namen aus dem SPN aufrufen, nicht über die IP.
- Edge/Chrome: Seite muss in der Zone „Lokales Intranet“ sein (per GPO).
- Firefox: `network.negotiate-auth.trusted-uris` = `werkzeuge.intranet.firma.de` (per GPO/Policy).

**Test:** `https://werkzeuge.intranet.firma.de/api/me.php` zeigt die Rechte des angemeldeten
Benutzers. Probleme stehen im Apache-Errorlog (Kerberos) bzw. PHP-Errorlog (`AD-Login: …`).

nginx:

```nginx
server {
    listen 443 ssl;
    server_name werkzeuge.intranet.firma.de;
    root /var/www/lokring/public;
    index index.php;

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
}
```

## Checkliste vor dem Produktivbetrieb

- [ ] **Kerberos/AD-Login einrichten** (siehe „Anmeldung (Kerberos / AD)“): Gruppen,
      Servicebenutzer, Keytab, `auth.ldap` in `config/config.php`.
- [ ] Migrationen aus `database/migrations/` in der Produktions-DB einspielen
      (u. a. `001_users.sql`).
- [ ] Datenbank-Passwort ändern. Das alte Passwort steht noch in der Git-Historie.
- [ ] Eigenen Datenbank-Benutzer mit minimalen Rechten verwenden
      (SELECT/INSERT/UPDATE/DELETE nur auf die Datenbank `lokring`).
- [ ] HTTPS aktivieren und `session.cookie_secure` auf `true` setzen.
- [ ] `environment` = `'production'`, `dev_fake_role` = `null`.
- [ ] Regelmäßige Datenbank-Backups einrichten.

## BoM-Dokumente

BoM-PDFs werden im Editor beim Grundkörper hochgeladen und liegen in `storage/documents/`, also
außerhalb von `public/`. Abruf nur über `/documents/` (Übersicht) und `/documents/?file=<name>`.

- `storage/documents/` muss für den Webserver-Benutzer beschreibbar sein und ins Backup.
- Die App erlaubt bis zu 20 MB pro Datei. PHP muss das ebenfalls zulassen, Standard sind nur 2 MB:
  in der `php.ini` `upload_max_filesize = 20M` und `post_max_size = 21M` setzen, lokal
  `php -d upload_max_filesize=20M -d post_max_size=21M -S localhost:8000 -t public`.
  Bei nginx zusätzlich `client_max_body_size 21m;`.

## API

Jede Datei in `public/api/` ist ein Endpunkt. Die erlaubten Aufrufe stehen im Kopfkommentar
der Datei. Es gelten folgende Regeln:

- IDs werden im Query-String übergeben (`?id=5`), Daten als JSON im Body.
- `GET` liest, `POST` legt an, `PATCH` ändert nur die mitgeschickten Felder, `DELETE` löscht.
- Antwort immer `{ "success": true|false, "message": "...", ... }`.
- Rechte stehen in `src/auth.php` (`PERMISSIONS`). Endpunkte prüfen Rechte, nie Rollen.

Im Frontend werden alle Aufrufe über `apiRequest()` aus `public/assets/js/api.js` gemacht.

## Werkzeug-Status

Die IDs und Texte stehen an zwei Stellen und müssen zusammen geändert werden:
`src/tool_status.php` (PHP) und `public/assets/js/tool-status.js` (JavaScript).
