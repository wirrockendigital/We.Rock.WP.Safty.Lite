# We.Rock.WP.Safety.Lite

We.Rock.WP.Safety.Lite ist ein kleines WordPress-MU-Plugin fuer stabile, risikoarme Haertungen in WordPress 6.5 oder neuer. Es laedt ohne Composer-Abhaengigkeiten zur Laufzeit, nutzt nur WordPress-Core-Hooks und fuehrt keine externen Netzwerkanfragen aus.

Der vollstaendige empfohlene Einsatz umfasst dieses MU-Plugin, die Hardening-Bloecke in der Root- und Upload-`.htaccess` sowie eine passend konfigurierte NinjaFirewall. Der bevorzugte Installationsweg ist in der Repository-Datei `INSTALLATION.md` beschrieben. Anwender sind fuer die Pruefung der Regeln, Backups und die Funktion ihrer Website selbst verantwortlich.

## Funktionen

- Generische Login-Fehlermeldungen fuer bekannte WordPress-Core-Credential-Fehler.
- 404 fuer anonyme numerische Author-Enumeration wie `?author=1`.
- 404 fuer anonyme WordPress-Core-REST-Routen `/wp-json/wp/v2/users` und `/wp-json/wp/v2/users/<id>`.
- Entfernung der XML-RPC-Pingback-Methoden `pingback.ping` und `pingback.extensions.getPingbacks`.
- Entfernung des `X-Pingback`-Response-Headers.
- Optionaler Schalter fuer XML-RPC-Authentifizierung ueber `xmlrpc_enabled`.
- Optionaler Schalter fuer WordPress Application Passwords.
- Lesende Site-Health-Pruefungen fuer HTTPS, Debug-Konstanten, Umgebungstyp, Security-Plugin-Praesenz, Integrations-Policies und manuelle `.htaccess`-Marker.
- Sparsame Admin-Hinweise nur fuer kritische Produktionszustaende wie aktives `WP_DEBUG`.

## Installation als MU-Plugin

Die ZIP-Datei ist fuer eine Installation relativ zum WordPress-Root vorbereitet.

1. ZIP im WordPress-Root entpacken.
2. Pruefen, dass `wp-content/mu-plugins/we-rock-wp-safety-lite.php` direkt im MU-Plugin-Verzeichnis liegt.
3. Pruefen, dass `wp-content/mu-plugins/we-rock-wp-safety-lite/` die eigentlichen Plugin-Dateien enthaelt.
4. WordPress-Adminbereich oeffnen und unter Werkzeuge > Website-Zustand die We.Rock-Pruefungen kontrollieren.
5. Die Smoke-Test-Checkliste unter `deploy/SMOKE-TEST-CHECKLIST.md` ausfuehren.

## Konfiguration

Alle Konstanten haben sichere Standardwerte. Bereits definierte Werte werden nie ueberschrieben.

```php
define( 'WE_ROCK_SAFETY_DISABLED', false );
define( 'WE_ROCK_GENERIC_LOGIN_ERRORS', true );
define( 'WE_ROCK_BLOCK_AUTHOR_ENUMERATION', true );
define( 'WE_ROCK_RESTRICT_REST_USERS', true );
define( 'WE_ROCK_DISABLE_PINGBACKS', true );
define( 'WE_ROCK_DISABLE_XMLRPC_AUTH', false );
define( 'WE_ROCK_DISABLE_APPLICATION_PASSWORDS', false );
define( 'WE_ROCK_ENABLE_SITE_HEALTH_TESTS', true );
```

`WE_ROCK_SAFETY_DISABLED=true` ist der globale Kill-Switch. Wenn er aktiv ist, registriert das Plugin keine Hooks.

`WE_ROCK_DISABLE_XMLRPC_AUTH=true` deaktiviert authentifizierte XML-RPC-Methoden ueber den offiziellen WordPress-Filter. Der `xmlrpc.php`-Endpunkt selbst bleibt erreichbar. Eine vollstaendige Endpunkt-Sperre ist eine separate Serverregel und wird in `deploy/README.md` beschrieben.

`WE_ROCK_DISABLE_APPLICATION_PASSWORDS=false` bleibt der Standard, damit REST-, Mobile- und Automationsintegrationen nicht unbeabsichtigt unterbrochen werden.

## Site Health

Die Site-Health-Tests lesen nur vorhandene Einstellungen und Dateien. Sie reparieren nichts automatisch und schreiben keine Dateien.

Geprueft wird:

- Site URL und Home URL verwenden HTTPS.
- `WP_DEBUG` ist `false`.
- `WP_DEBUG_DISPLAY` ist `false`.
- `DISALLOW_FILE_EDIT` ist `true`.
- `WP_ENVIRONMENT_TYPE` ist `production`.
- `wp-content/uploads/.htaccess` ist vorhanden.
- Root-`.htaccess` enthaelt `BEGIN WE.ROCK.WP.HARDENING` und `END WE.ROCK.WP.HARDENING`.
- Upload-`.htaccess` enthaelt einen kompatiblen We.Rock-Upload-Hardening-Marker.
- NinjaFirewall und WP 2FA sind aktiv.
- `DISALLOW_FILE_MODS` blockiert Updates nicht unbeabsichtigt.
- Application Passwords passen zur konfigurierten Integrations-Policy.
- XML-RPC-Policy ist erkennbar dokumentiert, zum Beispiel per Serverregel oder Plugin-Konstante.
- Authentifiziertes REST `users/me` bleibt fuer Administratoren nutzbar.
- Uploads enthalten eine erkennbare Sperre fuer ausfuehrbare Dateien.

Benutzer ohne `manage_options` erhalten keine sicherheitsrelevanten Detailhinweise.

## Aufgabenverteilung

NinjaFirewall bleibt fuer WAF-Regeln, Request-Pruefungen und gepflegte Firewall-Logik zustaendig. WP 2FA bleibt fuer Zwei-Faktor-Anmeldung und Backup-Codes zustaendig. `.htaccess`-Regeln bleiben fuer serverseitige Sperren wie vollstaendige XML-RPC-Blocks, HSTS und Upload-Ausfuehrungsschutz zustaendig. Dieses MU-Plugin zeigt diese Schichten nur im Website-Zustand an und setzt selbst keine WAF-, Login-Rate-Limit-, Scanner- oder Logging-Funktionen um.

## Datenschutz und DSGVO

Das Plugin speichert keine IP-Adressen, protokolliert keine Requests, sammelt keine personenbezogenen Daten, setzt keine Cookies, legt keine Datenbankoptionen an und kontaktiert keine externen Server. Es erstellt keine eigenen Datenbanktabellen, keine Aktivitaetsprotokolle und keine Cronjobs.

## Kompatibilitaet

Getestet und bei der Umsetzung beruecksichtigt:

- Standard-WordPress-Login.
- WP 2FA inklusive Backup-Codes.
- NinjaFirewall.
- WooCommerce Warenkorb und Checkout.
- Gutenberg.
- `wp-admin/admin-ajax.php`.
- WordPress REST API.
- WP-Cron.
- WordPress Multisite, soweit die einzelnen Funktionen im Netzwerkkontext sinnvoll anwendbar sind.

## Entwicklung und Qualitaetssicherung

Runtime-Abhaengigkeiten werden nicht benoetigt. Fuer lokale Pruefungen koennen Dev-Tools installiert werden:

```bash
cd wp-content/mu-plugins/we-rock-wp-safety-lite
composer install
composer lint
composer phpstan
composer test
```

Die PHPUnit-Tests nutzen WordPress-Test-Doubles und pruefen die Plugin-Callbacks ohne externe HTTP-Anfragen.
