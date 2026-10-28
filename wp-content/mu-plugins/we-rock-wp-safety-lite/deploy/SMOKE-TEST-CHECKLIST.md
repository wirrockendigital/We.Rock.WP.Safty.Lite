# Manuelle Smoke-Test-Checkliste

## Vor der Aktivierung

- Backup der Dateien und Datenbank ist vorhanden.
- Staging-Umgebung ist erreichbar.
- Externe Sicherheitskomponenten wie NinjaFirewall bleiben aktiv.
- Falls WP 2FA installiert ist: Das Plugin ist konfiguriert und Backup-Codes sind verfuegbar.

## Nach Aktivierung des MU-Plugins

- Oeffentliche Startseite laedt mit HTTP 200.
- `wp-login.php` laedt normal.
- Anmeldung mit Benutzername und Passwort funktioniert.
- Falls WP 2FA installiert ist: Die Abfrage funktioniert.
- Falls WP 2FA installiert ist: Ein Backup-Code funktioniert.
- `wp-admin/` laedt normal.
- `wp-admin/admin-ajax.php` bleibt erreichbar.
- Gutenberg-Editor laedt und speichert einen Testbeitrag.
- Medien-Upload funktioniert.
- Kontaktformular sendet in Staging wie erwartet.
- WooCommerce-Warenkorb funktioniert.
- WooCommerce-Checkout funktioniert in Staging.
- WP-Cron kann weiterhin ausgefuehrt werden.
- Cache-Purge funktioniert.
- WordPress-Core-Updates werden angezeigt und koennen in Staging getestet werden.
- Plugin- und Theme-Updates werden angezeigt.
- `/wp-json/` bleibt erreichbar.
- Anonymes `/wp-json/wp/v2/users` gibt 404 zurueck.
- Authentifizierte REST-Benutzer-Endpunkte folgen den WordPress-Berechtigungen.
- `/?author=1` gibt anonym 404 zurueck.
- Normale Author-Archive ohne numerischen Query-Parameter funktionieren.
- `xmlrpc.php` ist erreichbar, sofern keine separate Serverregel gesetzt wurde.
- Pingback-Methoden sind aus der XML-RPC-Methodenliste entfernt.

## Nach optionalen Deployment-Snippets

- Root-`.htaccess`-Aenderung erzeugt keinen HTTP 500.
- Upload-`.htaccess` blockiert PHP-Dateien im Upload-Verzeichnis.
- Bild-, PDF- und andere erlaubte Medien-Dateien im Upload-Verzeichnis bleiben erreichbar.
- `wp-config.php`-Konstanten werden in Site Health korrekt erkannt.
