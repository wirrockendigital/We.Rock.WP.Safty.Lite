# Release-Checkliste

- Versionsnummer in Plugin-Header und `Config::VERSION` ist konsistent.
- `CHANGELOG.md` enthaelt einen Eintrag fuer die neue Version.
- `composer install` wurde fuer Dev-Tools ausgefuehrt.
- `composer lint` ist gruen.
- `composer phpstan` ist gruen.
- `composer test` ist gruen.
- ZIP-Datei enthaelt den Loader direkt unter `wp-content/mu-plugins/`.
- ZIP-Datei enthaelt keine `vendor/`-Abhaengigkeiten.
- ZIP-Datei enthaelt keine lokalen Secrets.
- Deployment-Snippets wurden nicht automatisch angewendet.
- Smoke-Test-Checkliste wurde in Staging abgeschlossen.
