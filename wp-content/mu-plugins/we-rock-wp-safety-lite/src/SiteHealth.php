<?php
/**
 * Read-only Site Health checks.
 *
 * @package WeRock\WPSafetyLite
 */

declare(strict_types=1);

namespace WeRock\WPSafetyLite;

defined( 'ABSPATH' ) || exit;

/**
 * Adds administrator-facing, read-only hardening checks.
 */
final class SiteHealth {
	/**
	 * Registers direct Site Health tests.
	 *
	 * @param mixed $tests Existing tests.
	 * @return array
	 */
	public static function register_tests( $tests ): array {
		if ( ! is_array( $tests ) ) {
			$tests = array();
		}

		if ( ! isset( $tests['direct'] ) || ! is_array( $tests['direct'] ) ) {
			$tests['direct'] = array();
		}

		$tests['direct']['we_rock_https_urls'] = array(
			'label' => self::esc_html__( 'We.Rock: HTTPS fuer Website-Adressen' ),
			'test'  => array( self::class, 'test_https_urls' ),
		);

		$tests['direct']['we_rock_wp_debug'] = array(
			'label' => self::esc_html__( 'We.Rock: WP_DEBUG ist deaktiviert' ),
			'test'  => array( self::class, 'test_wp_debug_disabled' ),
		);

		$tests['direct']['we_rock_wp_debug_display'] = array(
			'label' => self::esc_html__( 'We.Rock: WP_DEBUG_DISPLAY ist deaktiviert' ),
			'test'  => array( self::class, 'test_wp_debug_display_disabled' ),
		);

		$tests['direct']['we_rock_disallow_file_edit'] = array(
			'label' => self::esc_html__( 'We.Rock: Dateieditor ist deaktiviert' ),
			'test'  => array( self::class, 'test_disallow_file_edit' ),
		);

		$tests['direct']['we_rock_environment_type'] = array(
			'label' => self::esc_html__( 'We.Rock: Produktionsumgebung' ),
			'test'  => array( self::class, 'test_environment_type' ),
		);

		$tests['direct']['we_rock_uploads_htaccess_exists'] = array(
			'label' => self::esc_html__( 'We.Rock: Upload-.htaccess ist vorhanden' ),
			'test'  => array( self::class, 'test_uploads_htaccess_exists' ),
		);

		$tests['direct']['we_rock_root_htaccess_markers'] = array(
			'label' => self::esc_html__( 'We.Rock: Root-.htaccess-Marker' ),
			'test'  => array( self::class, 'test_root_htaccess_markers' ),
		);

		$tests['direct']['we_rock_uploads_htaccess_markers'] = array(
			'label' => self::esc_html__( 'We.Rock: Upload-.htaccess-Marker' ),
			'test'  => array( self::class, 'test_uploads_htaccess_markers' ),
		);

		$tests['direct']['we_rock_security_plugins_present'] = array(
			'label' => self::esc_html__( 'We.Rock: Sicherheitsplugins sind aktiv' ),
			'test'  => array( self::class, 'test_security_plugins_present' ),
		);

		$tests['direct']['we_rock_file_mods_policy'] = array(
			'label' => self::esc_html__( 'We.Rock: Updates sind nicht global blockiert' ),
			'test'  => array( self::class, 'test_file_mods_not_blocked' ),
		);

		$tests['direct']['we_rock_application_password_policy'] = array(
			'label' => self::esc_html__( 'We.Rock: Application-Password-Policy' ),
			'test'  => array( self::class, 'test_application_password_policy' ),
		);

		$tests['direct']['we_rock_xmlrpc_policy'] = array(
			'label' => self::esc_html__( 'We.Rock: XML-RPC-Policy' ),
			'test'  => array( self::class, 'test_xmlrpc_policy' ),
		);

		$tests['direct']['we_rock_rest_users_me'] = array(
			'label' => self::esc_html__( 'We.Rock: REST users/me bleibt nutzbar' ),
			'test'  => array( self::class, 'test_rest_users_me_compatibility' ),
		);

		$tests['direct']['we_rock_uploads_executable_hardening'] = array(
			'label' => self::esc_html__( 'We.Rock: Uploads blockieren ausfuehrbare Dateien' ),
			'test'  => array( self::class, 'test_uploads_executable_hardening' ),
		);

		return $tests;
	}

	/**
	 * Checks whether Site URL and Home URL use HTTPS.
	 *
	 * @return array
	 */
	public static function test_https_urls(): array {
		if ( ! self::can_show_admin_details() ) {
			return self::private_result();
		}

		$site_url = self::get_option( 'siteurl' );
		$home_url = self::get_option( 'home' );
		$is_good  = self::url_uses_https( $site_url ) && self::url_uses_https( $home_url );

		return self::result(
			$is_good ? 'good' : 'critical',
			self::esc_html__( 'Website- und Home-URL sollten HTTPS verwenden.' ),
			$is_good ? self::esc_html__( 'Beide WordPress-Adressen verwenden HTTPS.' ) : self::esc_html__( 'Pruefe die WordPress-Adressen unter Einstellungen > Allgemein und stelle beide auf HTTPS um.' )
		);
	}

	/**
	 * Checks whether WP_DEBUG is disabled.
	 *
	 * @return array
	 */
	public static function test_wp_debug_disabled(): array {
		if ( ! self::can_show_admin_details() ) {
			return self::private_result();
		}

		$is_good = defined( 'WP_DEBUG' ) && false === WP_DEBUG;

		return self::result(
			$is_good ? 'good' : 'critical',
			self::esc_html__( 'WP_DEBUG sollte in Produktion deaktiviert sein.' ),
			$is_good ? self::esc_html__( 'WP_DEBUG ist deaktiviert.' ) : self::esc_html__( 'Setze WP_DEBUG in wp-config.php auf false.' )
		);
	}

	/**
	 * Checks whether WP_DEBUG_DISPLAY is disabled.
	 *
	 * @return array
	 */
	public static function test_wp_debug_display_disabled(): array {
		if ( ! self::can_show_admin_details() ) {
			return self::private_result();
		}

		$is_good = defined( 'WP_DEBUG_DISPLAY' ) && false === WP_DEBUG_DISPLAY;

		return self::result(
			$is_good ? 'good' : 'recommended',
			self::esc_html__( 'WP_DEBUG_DISPLAY sollte keine Fehler im Frontend anzeigen.' ),
			$is_good ? self::esc_html__( 'WP_DEBUG_DISPLAY ist deaktiviert.' ) : self::esc_html__( 'Setze WP_DEBUG_DISPLAY in wp-config.php auf false.' )
		);
	}

	/**
	 * Checks whether the built-in file editor is disabled.
	 *
	 * @return array
	 */
	public static function test_disallow_file_edit(): array {
		if ( ! self::can_show_admin_details() ) {
			return self::private_result();
		}

		$is_good = defined( 'DISALLOW_FILE_EDIT' ) && true === DISALLOW_FILE_EDIT;

		return self::result(
			$is_good ? 'good' : 'recommended',
			self::esc_html__( 'Der WordPress-Dateieditor sollte deaktiviert sein.' ),
			$is_good ? self::esc_html__( 'DISALLOW_FILE_EDIT ist aktiv.' ) : self::esc_html__( 'Setze DISALLOW_FILE_EDIT in wp-config.php auf true.' )
		);
	}

	/**
	 * Checks whether WordPress runs in production mode.
	 *
	 * @return array
	 */
	public static function test_environment_type(): array {
		if ( ! self::can_show_admin_details() ) {
			return self::private_result();
		}

		$environment = self::get_environment_type();
		$is_good     = 'production' === $environment;

		return self::result(
			$is_good ? 'good' : 'recommended',
			self::esc_html__( 'WP_ENVIRONMENT_TYPE sollte production sein.' ),
			$is_good ? self::esc_html__( 'Die Website meldet production als Umgebungstyp.' ) : self::esc_html__( 'Setze WP_ENVIRONMENT_TYPE in wp-config.php auf production.' )
		);
	}

	/**
	 * Checks whether the uploads .htaccess file exists.
	 *
	 * @return array
	 */
	public static function test_uploads_htaccess_exists(): array {
		if ( ! self::can_show_admin_details() ) {
			return self::private_result();
		}

		$path    = self::uploads_htaccess_path();
		$is_good = '' !== $path && is_file( $path );

		return self::result(
			$is_good ? 'good' : 'recommended',
			self::esc_html__( 'Die Upload-.htaccess sollte vorhanden sein.' ),
			$is_good ? self::esc_html__( 'Die Upload-.htaccess wurde gefunden.' ) : self::esc_html__( 'Lege die Datei aus deploy/uploads.htaccess im Upload-Verzeichnis ab.' )
		);
	}

	/**
	 * Checks whether the root .htaccess contains the hardening marker block.
	 *
	 * @return array
	 */
	public static function test_root_htaccess_markers(): array {
		if ( ! self::can_show_admin_details() ) {
			return self::private_result();
		}

		$is_good = self::file_contains_markers( self::root_htaccess_path(), 'BEGIN WE.ROCK.WP.HARDENING', 'END WE.ROCK.WP.HARDENING' );

		return self::result(
			$is_good ? 'good' : 'recommended',
			self::esc_html__( 'Die Root-.htaccess sollte den We.Rock-Hardening-Block enthalten.' ),
			$is_good ? self::esc_html__( 'Der Root-.htaccess-Markerblock wurde gefunden.' ) : self::esc_html__( 'Fuege den Inhalt aus deploy/root.htaccess.snippet manuell in die Root-.htaccess ein.' )
		);
	}

	/**
	 * Checks whether the uploads .htaccess contains the no-PHP marker block.
	 *
	 * @return array
	 */
	public static function test_uploads_htaccess_markers(): array {
		if ( ! self::can_show_admin_details() ) {
			return self::private_result();
		}

		$is_good = self::file_contains_any_marker_pair(
			self::uploads_htaccess_path(),
			array(
				array( 'BEGIN WE.ROCK.NO.PHP.UPLOADS', 'END WE.ROCK.NO.PHP.UPLOADS' ),
				array( 'BEGIN WE.ROCK.NO.EXECUTABLE.UPLOADS', 'END WE.ROCK.NO.EXECUTABLE.UPLOADS' ),
			)
		);

		return self::result(
			$is_good ? 'good' : 'recommended',
			self::esc_html__( 'Die Upload-.htaccess sollte einen We.Rock-Upload-Hardening-Markerblock enthalten.' ),
			$is_good ? self::esc_html__( 'Ein kompatibler Upload-.htaccess-Markerblock wurde gefunden.' ) : self::esc_html__( 'Installiere deploy/uploads.htaccess manuell im Upload-Verzeichnis oder dokumentiere den bestehenden Upload-Hardening-Block.' )
		);
	}

	/**
	 * Checks whether the expected external security plugins are active.
	 *
	 * @return array
	 */
	public static function test_security_plugins_present(): array {
		if ( ! self::can_show_admin_details() ) {
			return self::private_result();
		}

		$ninjafirewall = self::plugin_file_is_active( 'ninjafirewall/ninjafirewall.php' ) || self::mu_plugin_file_exists( '0-ninjafirewall.php' );
		$wp_2fa        = self::plugin_file_is_active( 'wp-2fa/wp-2fa.php' );
		$is_good       = $ninjafirewall && $wp_2fa;

		if ( $is_good ) {
			$description = self::esc_html__( 'NinjaFirewall und WP 2FA sind aktiv. Dieses MU-Plugin uebernimmt deshalb nur ergaenzende Basis-Haertungen.' );
		} else {
			$description = self::esc_html__( 'Pruefe, ob NinjaFirewall und WP 2FA aktiv sein sollen. Dieses MU-Plugin ersetzt weder WAF noch Zwei-Faktor-Anmeldung.' );
		}

		return self::result(
			$is_good ? 'good' : 'recommended',
			self::esc_html__( 'NinjaFirewall und WP 2FA sollten aktiv sein.' ),
			$description
		);
	}

	/**
	 * Checks whether core, plugin, and theme updates are globally blocked.
	 *
	 * @return array
	 */
	public static function test_file_mods_not_blocked(): array {
		if ( ! self::can_show_admin_details() ) {
			return self::private_result();
		}

		$is_blocked = defined( 'DISALLOW_FILE_MODS' ) && true === DISALLOW_FILE_MODS;

		return self::result(
			$is_blocked ? 'recommended' : 'good',
			self::esc_html__( 'DISALLOW_FILE_MODS sollte Updates nicht unbeabsichtigt blockieren.' ),
			$is_blocked ? self::esc_html__( 'DISALLOW_FILE_MODS ist aktiv. Das kann Core-, Plugin- und Theme-Updates blockieren und sollte bewusst dokumentiert sein.' ) : self::esc_html__( 'Updates sind nicht durch DISALLOW_FILE_MODS global blockiert.' )
		);
	}

	/**
	 * Reports whether Application Passwords match the configured policy.
	 *
	 * @return array
	 */
	public static function test_application_password_policy(): array {
		if ( ! self::can_show_admin_details() ) {
			return self::private_result();
		}

		$config_disables = defined( 'WE_ROCK_DISABLE_APPLICATION_PASSWORDS' ) && true === WE_ROCK_DISABLE_APPLICATION_PASSWORDS;
		$available       = self::application_passwords_available();

		if ( null === $available ) {
			return self::result(
				'recommended',
				self::esc_html__( 'Application Passwords konnten nicht geprueft werden.' ),
				self::esc_html__( 'Die WordPress-Funktion zur Application-Password-Pruefung ist nicht verfuegbar.' )
			);
		}

		if ( $config_disables ) {
			$is_good     = ! $available;
			$description = $is_good ? self::esc_html__( 'Application Passwords sind durch die We.Rock-Konfiguration deaktiviert.' ) : self::esc_html__( 'Application Passwords sollten deaktiviert sein, sind aber weiterhin verfuegbar.' );
		} else {
			$is_good     = $available;
			$description = $is_good ? self::esc_html__( 'Application Passwords bleiben verfuegbar, damit REST-, Mobile- und Automationsintegrationen funktionieren.' ) : self::esc_html__( 'Application Passwords sind durch eine andere Komponente deaktiviert. Pruefe, ob REST- oder Automationsintegrationen sie benoetigen.' );
		}

		return self::result(
			$is_good ? 'good' : 'recommended',
			self::esc_html__( 'Application Passwords sollten zur bewussten Integrations-Policy passen.' ),
			$description
		);
	}

	/**
	 * Reports whether XML-RPC authentication or endpoint blocking is documented by configuration.
	 *
	 * @return array
	 */
	public static function test_xmlrpc_policy(): array {
		if ( ! self::can_show_admin_details() ) {
			return self::private_result();
		}

		$auth_disabled_by_plugin = defined( 'WE_ROCK_DISABLE_XMLRPC_AUTH' ) && true === WE_ROCK_DISABLE_XMLRPC_AUTH;
		$endpoint_marker        = self::file_contains_text( self::root_htaccess_path(), 'WE.ROCK.DISABLE.XMLRPC' );
		$auth_enabled           = self::xmlrpc_auth_enabled();

		if ( $endpoint_marker ) {
			$description = self::esc_html__( 'Die Root-.htaccess enthaelt einen XML-RPC-Sperrmarker. Das ist eine Server-Policy, nicht Teil der Plugin-Automatik.' );
			$status      = 'good';
		} elseif ( $auth_disabled_by_plugin && false === $auth_enabled ) {
			$description = self::esc_html__( 'Authentifizierte XML-RPC-Methoden sind per We.Rock-Konfiguration deaktiviert. Der Endpunkt selbst wird dadurch nicht vollstaendig gesperrt.' );
			$status      = 'good';
		} else {
			$description = self::esc_html__( 'XML-RPC-Authentifizierung bleibt verfuegbar. Wenn der Endpunkt vollstaendig gesperrt werden soll, nutze eine separate Serverregel.' );
			$status      = 'recommended';
		}

		return self::result(
			$status,
			self::esc_html__( 'Die XML-RPC-Policy sollte klar dokumentiert sein.' ),
			$description
		);
	}

	/**
	 * Checks that the authenticated REST users/me route remains usable for administrators.
	 *
	 * @return array
	 */
	public static function test_rest_users_me_compatibility(): array {
		if ( ! self::can_show_admin_details() ) {
			return self::private_result();
		}

		if ( ! function_exists( 'rest_do_request' ) || ! class_exists( '\WP_REST_Request' ) ) {
			return self::result(
				'recommended',
				self::esc_html__( 'REST users/me konnte nicht direkt geprueft werden.' ),
				self::esc_html__( 'Die REST-Testfunktionen sind in diesem Kontext nicht verfuegbar.' )
			);
		}

		$response = rest_do_request( new \WP_REST_Request( 'GET', '/wp/v2/users/me' ) );
		$status   = is_object( $response ) && method_exists( $response, 'get_status' ) ? (int) $response->get_status() : 0;
		$is_good  = $status >= 200 && $status < 400;

		return self::result(
			$is_good ? 'good' : 'recommended',
			self::esc_html__( 'REST users/me sollte fuer angemeldete Administratoren nutzbar bleiben.' ),
			$is_good ? self::esc_html__( 'Die authentifizierte REST-Route users/me ist erreichbar.' ) : self::esc_html__( 'Die authentifizierte REST-Route users/me war nicht erfolgreich. Pruefe REST-Sicherheitsregeln und Integrationen.' )
		);
	}

	/**
	 * Checks whether uploads contain a server-side executable file block.
	 *
	 * @return array
	 */
	public static function test_uploads_executable_hardening(): array {
		if ( ! self::can_show_admin_details() ) {
			return self::private_result();
		}

		$path    = self::uploads_htaccess_path();
		$is_good = self::file_contains_any_text(
			$path,
			array(
				'WE.ROCK.NO.PHP.UPLOADS',
				'WE.ROCK.NO.EXECUTABLE.UPLOADS',
				'RemoveHandler .php',
				'php_flag engine off',
			)
		) && self::file_contains_text( $path, 'FilesMatch' );

		return self::result(
			$is_good ? 'good' : 'recommended',
			self::esc_html__( 'Uploads sollten PHP und andere ausfuehrbare Dateitypen nicht ausfuehren.' ),
			$is_good ? self::esc_html__( 'Die Upload-.htaccess enthaelt eine erkennbare Sperre fuer ausfuehrbare Uploads.' ) : self::esc_html__( 'Pruefe die Upload-.htaccess oder installiere deploy/uploads.htaccess manuell.' )
		);
	}

	/**
	 * Builds a Site Health result array.
	 *
	 * @param string $status      Site Health status.
	 * @param string $label       Result label.
	 * @param string $description Result description.
	 * @return array
	 */
	private static function result( string $status, string $label, string $description ): array {
		return array(
			'status'      => $status,
			'label'       => $label,
			'description' => $description,
			'badge'       => array(
				'label' => self::esc_html__( 'We.Rock Safety Lite' ),
				'color' => 'blue',
			),
			'actions'     => '',
			'test'        => 'we_rock_wp_safety_lite',
		);
	}

	/**
	 * Builds a generic result for users without administrative permissions.
	 *
	 * @return array
	 */
	private static function private_result(): array {
		return self::result(
			'good',
			self::esc_html__( 'Pruefung nur fuer Administratoren verfuegbar.' ),
			self::esc_html__( 'Diese Pruefung gibt keine Sicherheitsdetails fuer Benutzer ohne manage_options aus.' )
		);
	}

	/**
	 * Checks whether the current user may see hardening details.
	 *
	 * @return bool
	 */
	private static function can_show_admin_details(): bool {
		return function_exists( 'current_user_can' ) && current_user_can( 'manage_options' );
	}

	/**
	 * Reads an option with a safe fallback.
	 *
	 * @param string $name Option name.
	 * @return string
	 */
	private static function get_option( string $name ): string {
		if ( function_exists( 'get_option' ) ) {
			return (string) get_option( $name, '' );
		}

		return '';
	}

	/**
	 * Reads a raw option value with a safe fallback.
	 *
	 * @param string $name    Option name.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	private static function get_raw_option( string $name, $default = array() ) {
		if ( function_exists( 'get_option' ) ) {
			return get_option( $name, $default );
		}

		return $default;
	}

	/**
	 * Checks whether a URL has an HTTPS scheme.
	 *
	 * @param string $url URL to check.
	 * @return bool
	 */
	private static function url_uses_https( string $url ): bool {
		if ( '' === $url ) {
			return false;
		}

		if ( function_exists( 'wp_parse_url' ) ) {
			$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		} else {
			$scheme = parse_url( $url, PHP_URL_SCHEME );
		}

		return 'https' === strtolower( (string) $scheme );
	}

	/**
	 * Reads the WordPress environment type.
	 *
	 * @return string
	 */
	private static function get_environment_type(): string {
		if ( function_exists( 'wp_get_environment_type' ) ) {
			return (string) wp_get_environment_type();
		}

		if ( defined( 'WP_ENVIRONMENT_TYPE' ) ) {
			return (string) WP_ENVIRONMENT_TYPE;
		}

		return 'production';
	}

	/**
	 * Builds the root .htaccess path without exposing it in output.
	 *
	 * @return string
	 */
	private static function root_htaccess_path(): string {
		if ( ! defined( 'ABSPATH' ) ) {
			return '';
		}

		return rtrim( (string) ABSPATH, '/\\' ) . DIRECTORY_SEPARATOR . '.htaccess';
	}

	/**
	 * Builds the uploads .htaccess path without exposing it in output.
	 *
	 * @return string
	 */
	private static function uploads_htaccess_path(): string {
		if ( ! defined( 'WP_CONTENT_DIR' ) ) {
			return '';
		}

		return rtrim( (string) WP_CONTENT_DIR, '/\\' ) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . '.htaccess';
	}

	/**
	 * Checks marker presence in a readable file.
	 *
	 * @param string $path         File path.
	 * @param string $begin_marker Begin marker.
	 * @param string $end_marker   End marker.
	 * @return bool
	 */
	private static function file_contains_markers( string $path, string $begin_marker, string $end_marker ): bool {
		if ( '' === $path || ! is_readable( $path ) ) {
			return false;
		}

		$contents = file_get_contents( $path );

		if ( false === $contents ) {
			return false;
		}

		return false !== strpos( $contents, $begin_marker ) && false !== strpos( $contents, $end_marker );
	}

	/**
	 * Checks whether a readable file contains any marker pair.
	 *
	 * @param string                  $path         File path.
	 * @param array<int, array<int,string>> $marker_pairs Marker pairs.
	 * @return bool
	 */
	private static function file_contains_any_marker_pair( string $path, array $marker_pairs ): bool {
		foreach ( $marker_pairs as $marker_pair ) {
			if ( 2 === count( $marker_pair ) && self::file_contains_markers( $path, $marker_pair[0], $marker_pair[1] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks whether a readable file contains a text fragment.
	 *
	 * @param string $path File path.
	 * @param string $text Text fragment.
	 * @return bool
	 */
	private static function file_contains_text( string $path, string $text ): bool {
		return self::file_contains_any_text( $path, array( $text ) );
	}

	/**
	 * Checks whether a readable file contains any text fragment.
	 *
	 * @param string            $path  File path.
	 * @param array<int,string> $texts Text fragments.
	 * @return bool
	 */
	private static function file_contains_any_text( string $path, array $texts ): bool {
		if ( '' === $path || ! is_readable( $path ) ) {
			return false;
		}

		$contents = file_get_contents( $path );

		if ( false === $contents ) {
			return false;
		}

		foreach ( $texts as $text ) {
			if ( '' !== $text && false !== stripos( $contents, $text ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks whether a normal plugin file is active.
	 *
	 * @param string $plugin_file Plugin file path relative to plugins directory.
	 * @return bool
	 */
	private static function plugin_file_is_active( string $plugin_file ): bool {
		$active_plugins = self::get_raw_option( 'active_plugins', array() );

		if ( is_array( $active_plugins ) && in_array( $plugin_file, $active_plugins, true ) ) {
			return true;
		}

		if ( function_exists( 'get_site_option' ) ) {
			$sitewide_plugins = get_site_option( 'active_sitewide_plugins', array() );

			return is_array( $sitewide_plugins ) && array_key_exists( $plugin_file, $sitewide_plugins );
		}

		return false;
	}

	/**
	 * Checks whether an MU plugin file is present.
	 *
	 * @param string $plugin_file MU plugin basename.
	 * @return bool
	 */
	private static function mu_plugin_file_exists( string $plugin_file ): bool {
		if ( ! function_exists( 'get_mu_plugins' ) ) {
			return false;
		}

		$mu_plugins = get_mu_plugins();

		return is_array( $mu_plugins ) && array_key_exists( $plugin_file, $mu_plugins );
	}

	/**
	 * Reads Application Password availability without changing policy.
	 *
	 * @return bool|null
	 */
	private static function application_passwords_available(): ?bool {
		if ( function_exists( 'wp_is_application_passwords_available' ) ) {
			return (bool) wp_is_application_passwords_available();
		}

		return null;
	}

	/**
	 * Reads XML-RPC authentication availability through the official filter.
	 *
	 * @return bool
	 */
	private static function xmlrpc_auth_enabled(): bool {
		if ( function_exists( 'apply_filters' ) ) {
			return (bool) apply_filters( 'xmlrpc_enabled', true );
		}

		return true;
	}

	/**
	 * Escapes translated text with a safe fallback for test contexts.
	 *
	 * @param string $text Text to translate.
	 * @return string
	 */
	private static function esc_html__( string $text ): string {
		if ( function_exists( 'esc_html__' ) ) {
			return esc_html__( $text, 'we-rock-wp-safety-lite' );
		}

		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}
