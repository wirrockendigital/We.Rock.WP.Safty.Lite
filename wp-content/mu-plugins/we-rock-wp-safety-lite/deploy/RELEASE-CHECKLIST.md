# Release-Checkliste

- `Config::VERSION`, `release.json` und der geplante Release-Tag `v<semver>` stimmen ueberein.
- `CHANGELOG.md` enthaelt einen Eintrag fuer die neue Version.
- `composer install` wurde fuer Dev-Tools ausgefuehrt.
- `composer lint` ist gruen.
- `composer phpstan` ist gruen.
- `composer test` ist gruen.
- `php scripts/build-release.php` erstellt `dist/we-rock-wp-safety-lite.zip` mit dem Ordner `we-rock-wp-safety-lite/`.
- ZIP-Datei enthaelt nur `bootstrap.php`, `release.json`, `src/*.php` und `LICENSE` innerhalb dieses Ordners; der stabile MU-Loader wird separat installiert.
- ZIP-Digest ist SHA-256 und erscheint nach dem Hochladen im GitHub-Release-Asset.
- ZIP-Datei enthaelt keine `vendor/`-Abhaengigkeiten.
- ZIP-Datei enthaelt keine lokalen Secrets.
- Deployment-Snippets wurden nicht automatisch angewendet.
- Smoke-Test-Checkliste wurde in Staging abgeschlossen.
- Release erst nach ausdruecklichem Versions- und Releaseauftrag als Draft mit ZIP-Asset vorbereiten und danach veroeffentlichen; keine automatischen Tags durch normale Commits oder Pushes.
