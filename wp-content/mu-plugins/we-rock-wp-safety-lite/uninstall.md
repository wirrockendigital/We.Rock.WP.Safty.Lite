# Deinstallation

We.Rock.WP.Safety.Lite legt keine Datenbanktabellen, Optionen, Cronjobs, Cookies oder Protokolldateien an. Die Deinstallation besteht deshalb aus dem Entfernen der Dateien und optional manuell installierter Snippets.

## MU-Plugin entfernen

1. Datei `wp-content/mu-plugins/we-rock-wp-safety-lite.php` entfernen.
2. Verzeichnis `wp-content/mu-plugins/we-rock-wp-safety-lite/` entfernen.
3. WordPress-Adminbereich neu laden.
4. Website, Login, REST API und Medien-Upload testen.

## Optionale Deployment-Snippets zuruecknehmen

Falls die Deployment-Dateien manuell angewendet wurden:

- In der Root-`.htaccess` den Block `BEGIN WE.ROCK.WP.HARDENING` bis `END WE.ROCK.WP.HARDENING` entfernen.
- In `wp-content/uploads/.htaccess` den Block `BEGIN WE.ROCK.NO.PHP.UPLOADS` bis `END WE.ROCK.NO.PHP.UPLOADS` entfernen oder die Datei loeschen, falls sie nur fuer diesen Zweck existiert.
- In `wp-config.php` den Block `BEGIN WE.ROCK.WP.CONFIG.HARDENING` bis `END WE.ROCK.WP.CONFIG.HARDENING` entfernen, sofern keine anderen Betriebsregeln diese Konstanten benoetigen.

## Rollback-Test

- Oeffentliche Website laedt.
- `wp-login.php` laedt.
- `wp-admin/` laedt.
- `/wp-json/` laedt.
- Medien aus `wp-content/uploads/` bleiben erreichbar.
