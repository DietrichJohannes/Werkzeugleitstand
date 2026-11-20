# BSH Werkzeugleitstand – Lokring

**Digitale Leitstand für Lokringwerkzeuge.** Die Intranet-Anwendung zeigt auf einen
Blick, welches Werkzeug wo steht, ob sie einsatzbereit sind und wie oft diese ausfallen – von der
Werkzeugausgabe an der Linie bis zur Auswertung für das Management.

Statt Listen, Zettel und Excel-Tabellen gibt es eine zentrale Stelle für den gesamten
Lebenszyklus eines Werkzeugs: **Bereit → Aktiv → Wartung → (Verschrottet)**, inklusive
lückenlosem Verlauf jeder Statusänderung, jedes Ausfalls und jeder Reparatur.

---

## Funktionen

### Leitstand (Startseite)
- Kennzahlen aller Werkzeuge: Gesamt, Bereit, Aktiv, In Wartung
- Kachel je Standort/Linie mit Bestand und **Mindestbestand** – Standorte, die darunter fallen,
  werden als kritisch hervorgehoben

### Werkzeugpool je Linie
- Alle Werkzeuge eines Standorts mit Status
- Werkzeug per Klick öffnen und direkt **Status oder Standort ändern**
- **Ausfall melden** mit Ursache, betroffenem Bauteil und Bemerkung
- **Reparatur erfassen** – optional einem offenen Ausfall zugeordnet, mit getauschten Bauteilen
- **Reinigung quittieren**, letzte Reinigung wird angezeigt
- Werkzeugdetails mit Grundkörper, Backenset, Verbindung, Lokring und komplettem **Verlauf**

### Übersicht
- Tabellarische Gesamtliste aller Werkzeuge über alle Standorte hinweg

### Management-Ansicht
- Status nach Standort
- Auswertung über 30, 90 oder 365 Tage:
  - Zeitverlauf von Ausfällen und Reparaturen
  - Ausfälle nach Ursache
  - häufig betroffene Bauteile
  - Werkzeuge mit den meisten Ausfällen
  - durchschnittliche Ausfallzeit je Standort
- Ereignisliste

### Dokumente & Anleitungen
- **BoM-PDFs** (Stücklisten) je Grundkörper – hochladen im Editor, abrufbar für alle Berechtigten
- Schritt-für-Schritt-Anleitungen als Präsentation: *Werkzeug reinigen*, *Werkzeug warten*

### Verwaltung (Stammdaten)
- Werkzeuge, Standorte, Grundkörper, Backensets, Verbindungen, Ersatzteile (HIBE- und
  Herstellernummer) und Ausfallursachen pflegen
- Werkzeuge werden nie gelöscht, sondern verschrottet – die Historie bleibt erhalten
- Stammdaten lassen sich nur löschen, solange nichts mehr auf sie verweist

## Rollen

Die Anmeldung erfolgt per **Single Sign-On** über den Windows-Login (Kerberos). Die Rolle ergibt
sich automatisch aus der Gruppenzugehörigkeit im Active Directory.

| Rolle        | Darf |
|--------------|------|
| `beobachter` | alles ansehen (Leitstand, Übersicht, Management) |
| `user`       | zusätzlich Status/Standort ändern, Ausfälle, Reparaturen und Reinigungen erfassen, Dokumente und Anleitungen öffnen |
| `einrichter` | zusätzlich Stammdaten pflegen, Werkzeuge anlegen und verschrotten, Dokumente hochladen |


## Technik

| Bereich    | Umsetzung |
|------------|-----------|
| Backend    | PHP 8.1+, ohne Framework und ohne Composer |
| Datenbank  | MariaDB / MySQL über PDO |
| Frontend   | Vanilla JavaScript und CSS – kein Build-Schritt, kein npm |
| Schnittstelle | REST-API in `public/api/`, ein Endpunkt pro Datei, JSON |
| Anmeldung  | Apache + Kerberos (`mod_auth_gssapi`), Gruppen per LDAP |
| Sicherheit | rechtebasierte Prüfung in jedem Endpunkt, CSRF-Schutz für schreibende Aufrufe, Dateien und PHP-Kern außerhalb des Web-Roots |


### Ordnerstruktur

```
config/               Konfiguration
  config.example.php  Vorlage (im Git)
  config.php          echte Werte mit Passwörtern (NICHT im Git)
database/             Schema, Grunddaten, Demodaten, Migrationen
storage/documents/    hochgeladene BoM-PDFs (NICHT im Git, der Webserver muss hier schreiben dürfen)
src/                  PHP-Kern – vom Webserver NICHT erreichbar
  config.php          config('db.host') usw.
  database.php        db() – Datenbankverbindung
  auth.php            Rollen & Rechte (PERMISSIONS)
  ad_login.php        Kerberos/LDAP-Anmeldung
  api_bootstrap.php   gemeinsame Basis aller API-Endpunkte
  page_bootstrap.php  gemeinsame Basis geschützter Seiten
  tool_status.php     Werkzeug-Status (IDs + Texte)
  tool_events.php     Werkzeugverlauf
public/               ← einziger Ordner, den der Webserver ausliefert
  index.php           Startseite (Leitstand)
  api/                REST-API, ein Endpunkt pro Datei (tools.php, repairs.php, …)
  pages/              Unterseiten: overview, management, editor, line, instruction
  documents/          Dokumentenübersicht und PDF-Abruf
  components/         wiederverwendbare Dialoge (Werkzeug, Ersatzteile, Reparatur/Ausfall, Upload)
  assets/css/         gemeinsame Styles
  assets/js/          gemeinsame Skripte (api.js, tool-status.js, location-status.js, charts.js)
```

---

## Schnellstart (lokal)

```bash
cp config/config.example.php config/config.php   # einmalig, dann Werte eintragen
php -d upload_max_filesize=20M -d post_max_size=21M -S localhost:8000 -t public
```

Datenbank anlegen wie in [`database/README.md`](database/README.md) beschrieben
(`schema.sql`, `seed.sql`, optional `demo_data.sql`), dann <http://localhost:8000> öffnen.

Ohne Active Directory in `config/config.php` setzen:

```php
'environment' => 'development',
'auth' => ['dev_fake_role' => 'einrichter'],   // jeder Aufruf gilt als angemeldet
```

`dev_fake_role` wirkt **nur** bei `'environment' => 'development'` und nur mit dem
PHP-Testserver, nie unter Apache.

---

## Installation auf einem Server

1. Repository auf den Server kopieren, z. B. nach `/var/www/lokring`.
2. `config/config.php` aus der Vorlage anlegen und ausfüllen:
   - `environment` auf `'production'` setzen
   - `auth.dev_fake_role` auf `null` setzen
   - `session.cookie_secure` auf `true` setzen, wenn die Seite über HTTPS läuft
3. Datenbank anlegen (siehe [`database/README.md`](database/README.md)).
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

nginx:

```nginx
server {
    listen 443 ssl;
    server_name werkzeuge.intranet.firma.de;
    root /var/www/lokring/public;
    index index.php;
    client_max_body_size 21m;

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
}
```

### Anmeldung (Kerberos / AD)

Die Anmeldung läuft per Single Sign-On: Apache prüft das Kerberos-Ticket des Windows-Logins,
PHP liest danach Name, E-Mail und Gruppen per LDAP aus dem AD (`src/ad_login.php`).

| AD-Gruppe (`config.php`)      | Rolle        |
|-------------------------------|--------------|
| `auth.ldap.group_einrichter`  | `einrichter` |
| `auth.ldap.group_user`        | `user`       |
| keine der beiden              | `beobachter` |

Verschachtelte Gruppen zählen mit. Rollenänderungen im AD greifen nach spätestens
`auth.role_refresh_minutes` (Standard 15).

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

### BoM-Dokumente

BoM-PDFs werden im Editor beim Grundkörper hochgeladen und liegen in `storage/documents/`, also
außerhalb von `public/`. Abruf nur über `/documents/` (Übersicht) und `/documents/?file=<name>`.

- `storage/documents/` muss für den Webserver-Benutzer beschreibbar sein und ins Backup.
- Die App erlaubt bis zu 20 MB pro Datei. PHP muss das ebenfalls zulassen, Standard sind nur 2 MB:
  in der `php.ini` `upload_max_filesize = 20M` und `post_max_size = 21M` setzen.
  Bei nginx zusätzlich `client_max_body_size 21m;`.

### Checkliste vor dem Produktivbetrieb

- [ ] **Kerberos/AD-Login einrichten** (siehe „Anmeldung (Kerberos / AD)“): Gruppen,
      Servicebenutzer, Keytab, `auth.ldap` in `config/config.php`.
- [ ] Migrationen aus `database/migrations/` in der Produktions-DB einspielen
      (u. a. `001_users.sql`).
- [ ] Datenbank-Passwort ändern. Das alte Passwort steht noch in der Git-Historie.
- [ ] Eigenen Datenbank-Benutzer mit minimalen Rechten verwenden
      (SELECT/INSERT/UPDATE/DELETE nur auf die Datenbank `lokring`).
- [ ] HTTPS aktivieren und `session.cookie_secure` auf `true` setzen.
- [ ] `environment` = `'production'`, `dev_fake_role` = `null`.
- [ ] Regelmäßige Datenbank-Backups einrichten (Datenbank **und** `storage/documents/`).

---

## Für Entwickler

### API

Jede Datei in `public/api/` ist ein Endpunkt. Die erlaubten Aufrufe stehen im Kopfkommentar
der Datei. Es gelten folgende Regeln:

- IDs werden im Query-String übergeben (`?id=5`), Daten als JSON im Body.
- `GET` liest, `POST` legt an, `PATCH` ändert nur die mitgeschickten Felder, `DELETE` löscht.
- Antwort immer `{ "success": true|false, "message": "...", ... }`.
- Rechte stehen in `src/auth.php` (`PERMISSIONS`). Endpunkte prüfen Rechte, nie Rollen.
- Schreibende Aufrufe brauchen den Header `X-Requested-With: fetch` und dürfen nicht von
  fremden Origins kommen (CSRF-Schutz).

Im Frontend werden alle Aufrufe über `apiRequest()` aus `public/assets/js/api.js` gemacht,
das den Header automatisch setzt.

### Werkzeug-Status

Die IDs (1 Bereit, 2 Aktiv, 3 Wartung, 4 Verschrottet) und Texte stehen an zwei Stellen und
müssen zusammen geändert werden: `src/tool_status.php` (PHP) und
`public/assets/js/tool-status.js` (JavaScript).

### Datenbank-Änderungen

Die Produktion ist live: Jede Schemaänderung kommt als nummerierte Migration nach
`database/migrations/` und wird gleichzeitig in `database/schema.sql` nachgezogen.
Tabellenübersicht und Benennungsregeln: [`database/README.md`](database/README.md).
