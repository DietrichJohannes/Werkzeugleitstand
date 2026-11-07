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

- [ ] **Login einbauen.** Bisher gibt es keinen Login. Ohne `dev_fake_role` antwortet
      die API deshalb überall mit „Nicht angemeldet“. Ideal für das Firmen-Intranet:
      Anmeldung über Active Directory / LDAP oder Single Sign-On. Nach dem Login
      müssen `$_SESSION['user_id']` und `$_SESSION['role']` gesetzt werden
      (siehe `src/auth.php`).
- [ ] Die Benutzer-ID beim Anlegen von Reparaturen und Ausfällen kommt noch fest als `1` aus
      `public/components/tool_info_dialog.js`. Nach dem Login sollte sie aus der Session
      kommen.
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
