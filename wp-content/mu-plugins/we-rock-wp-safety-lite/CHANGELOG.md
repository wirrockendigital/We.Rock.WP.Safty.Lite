# Changelog

Alle nennenswerten Aenderungen an diesem Projekt werden in dieser Datei dokumentiert.

Das Format folgt [Keep a Changelog](https://keepachangelog.com/de/1.1.0/), und dieses Projekt nutzt semantische Versionierung.

## [Unreleased]

### Added

- Lesende Site-Health-Pruefungen fuer NinjaFirewall, WP 2FA, Application-Password-Policy, XML-RPC-Policy, REST `users/me`, `DISALLOW_FILE_MODS` und Upload-Ausfuehrungsschutz.
- Sparsame Admin-Hinweise fuer kritische Debug-Zustaende in Produktion.
- Kompatibilitaet mit bestehenden Upload-Hardening-Markern wie `WE.ROCK.NO.EXECUTABLE.UPLOADS`.
- Dokumentierte Aufgabenverteilung zwischen MU-Plugin, NinjaFirewall, WP 2FA und `.htaccess`-Haertungen.

### Fixed

- Numerische Author-Probes werden vor spaeterer 404-Redirect-Logik terminal als HTTP 404 beantwortet.
- Anonyme Core-REST-User-Routen werden auch dann auf HTTP 404 normalisiert, wenn eine fruehere REST-Komponente bereits einen Fehler gesetzt hat.
- Fruehe REST-Authentifizierungsfehler fuer anonyme Core-User-Routen werden ebenfalls auf HTTP 404 normalisiert.

## [0.1.0] - 2026-06-18

### Added

- Erste produktionsreife MU-Plugin-Version.
- Sichere Standardkonstanten mit Kill-Switch.
- Generische Login-Fehlermeldung fuer bekannte WordPress-Core-Credential-Fehler.
- Schutz gegen anonyme numerische Author-Enumeration.
- Schutz gegen anonyme WordPress-Core-REST-Benutzerauflistung.
- Entfernung von Pingback-XML-RPC-Methoden und X-Pingback-Header.
- Optionale Schalter fuer XML-RPC-Authentifizierung und Application Passwords.
- Lesende Site-Health-Pruefungen fuer HTTPS, Debug-Konstanten, Umgebungstyp und `.htaccess`-Marker.
- Deployment-Snippets fuer Root-`.htaccess`, Upload-`.htaccess` und `wp-config.php`.
- PHPUnit-Tests, PHPCS-Konfiguration und PHPStan-Konfiguration.
