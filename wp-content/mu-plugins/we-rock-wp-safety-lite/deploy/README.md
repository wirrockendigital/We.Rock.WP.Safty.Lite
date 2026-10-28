# Deployment-Dokumentation

Die vollstaendigen, manuell anzuwendenden Serverregeln stehen in der
Repository-Datei `MANUAL-HARDENING.txt`. Die Snippets in diesem Verzeichnis
sind kleinere Vorlagen und enthalten nicht alle dort dokumentierten Regeln.

Diese Dateien sind Vorlagen. Sie werden vom Plugin nie automatisch angewendet und muessen vor der Nutzung in einer Staging-Umgebung getestet werden.

## Installationsreihenfolge

1. MU-Plugin-Dateien nach `wp-content/mu-plugins/` kopieren.
2. Website, Login, Adminbereich und REST API testen.
3. Optional `deploy/wp-config.snippet.php` manuell in `wp-config.php` uebernehmen.
4. Optional `deploy/root.htaccess.snippet` manuell in die Root-`.htaccess` uebernehmen.
5. Optional `deploy/uploads.htaccess` als `.htaccess` in `wp-content/uploads/` installieren.
6. Site Health pruefen und danach die Smoke-Test-Checkliste ausfuehren.

## deploy/root.htaccess.snippet

Zielpfad: WordPress-Root-`.htaccess`, also dieselbe Datei, in der WordPress seine Rewrite-Regeln verwaltet.

Installation:

1. Backup der bestehenden Root-`.htaccess` erstellen.
2. Den Block `BEGIN WE.ROCK.WP.HARDENING` bis `END WE.ROCK.WP.HARDENING` ausserhalb des WordPress-Rewrite-Blocks einfuegen.
3. Datei speichern und sofort die Startseite, `wp-admin/`, `wp-login.php`, Medien-URLs und REST API testen.

Rollback:

1. Den kompletten Markerblock wieder entfernen.
2. Backup der vorherigen `.htaccess` zurueckspielen, falls der Server nicht wieder korrekt antwortet.

Moegliche HTTP-500-Ursachen:

- Apache erlaubt `Options` oder `Header` in diesem Kontext nicht.
- `mod_headers` oder `mod_authz_core` ist anders konfiguriert als erwartet.
- Der Block wurde innerhalb eines bestehenden WordPress-Rewrite-Blocks eingefuegt.
- Tippfehler oder kaputte Zeilenumbrueche in der `.htaccess`.

## deploy/uploads.htaccess

Zielpfad: `wp-content/uploads/.htaccess`.

Installation:

1. Falls bereits eine Upload-`.htaccess` existiert, Backup erstellen.
2. Datei als `wp-content/uploads/.htaccess` ablegen oder den Markerblock in die bestehende Datei uebernehmen.
3. Einen normalen Medien-Upload testen.
4. Eine vorhandene Bild-URL im Browser testen.
5. Einen PHP-Dateiaufruf im Upload-Verzeichnis mit einer harmlosen Testdatei in Staging pruefen.

Rollback:

1. Neue Upload-`.htaccess` entfernen oder Backup wiederherstellen.
2. Medien-Upload und bestehende Medien-URLs erneut testen.

Moegliche HTTP-500-Ursachen:

- Der Webserver erlaubt `Options`, `RemoveHandler`, `RemoveType` oder `php_flag` in Upload-Verzeichnissen nicht.
- PHP laeuft nicht als Apache-Modul und reagiert empfindlich auf `php_flag`.
- Eine bestehende Upload-`.htaccess` enthaelt widerspruechliche Regeln.

## deploy/wp-config.snippet.php

Zielpfad: `wp-config.php`, oberhalb der Zeile, die WordPress laedt.

Installation:

1. Backup der bestehenden `wp-config.php` erstellen.
2. Nur die Zeilen zwischen `BEGIN WE.ROCK.WP.CONFIG.HARDENING` und `END WE.ROCK.WP.CONFIG.HARDENING` uebernehmen.
3. Keinen zweiten `<?php`-Header einfuegen.
4. Website, Login und Adminbereich testen.

Rollback:

1. Eingefuegten Markerblock entfernen.
2. Backup der `wp-config.php` wiederherstellen, falls PHP-Fehler auftreten.

Moegliche HTTP-500-Ursachen:

- Der Block wurde ausserhalb des PHP-Kontexts eingefuegt.
- Es wurde versehentlich ein zweiter PHP-Oeffnungstag eingefuegt.
- Eine bestehende Konstante wurde an anderer Stelle mit inkompatiblem Typ definiert.

## Vollstaendige XML-RPC-Sperre

`WE_ROCK_DISABLE_XMLRPC_AUTH` nutzt den WordPress-Filter `xmlrpc_enabled`. Das deaktiviert authentifizierte XML-RPC-Methoden, sperrt aber nicht den gesamten `xmlrpc.php`-Endpunkt. Fuer eine vollstaendige Sperre kann in Staging eine separate Apache-Regel getestet werden:

```apache
<Files "xmlrpc.php">
	<IfModule mod_authz_core.c>
		Require all denied
	</IfModule>
	<IfModule !mod_authz_core.c>
		Order allow,deny
		Deny from all
	</IfModule>
</Files>
```

Nach dieser Regel muessen Mobile-Apps, Jetpack, externe Publisher, Monitoring und Integrationen geprueft werden.
