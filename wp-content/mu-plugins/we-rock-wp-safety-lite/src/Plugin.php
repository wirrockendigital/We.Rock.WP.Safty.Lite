<?php
/**
 * Hook registration for We.Rock.WP.Safety.Lite.
 *
 * @package WeRock\WPSafetyLite
 */

declare(strict_types=1);

namespace WeRock\WPSafetyLite;

defined( 'ABSPATH' ) || exit;

/**
 * Registers low-risk hardening hooks.
 */
final class Plugin {
	/**
	 * Bootstraps configuration and registers hooks when the kill switch permits it.
	 *
	 * @return void
	 */
	public static function bootstrap(): void {
		if ( ! class_exists( Config::class ) ) {
			return;
		}

		Config::define_defaults();

		if ( ! Config::is_enabled() ) {
			return;
		}

		self::register_hooks();
	}

	/**
	 * Registers feature hooks only when the relevant WordPress APIs are available.
	 *
	 * @return void
	 */
	private static function register_hooks(): void {
		if ( Config::feature_enabled( 'WE_ROCK_GENERIC_LOGIN_ERRORS' ) && function_exists( 'add_filter' ) ) {
			add_filter( 'login_errors', array( LoginHardening::class, 'filter_login_errors' ), 20, 1 );
		}

		if ( Config::feature_enabled( 'WE_ROCK_BLOCK_AUTHOR_ENUMERATION' ) ) {
			if ( function_exists( 'add_action' ) ) {
				add_action( 'parse_request', array( AuthorEnumeration::class, 'block_numeric_author_query' ), 1, 1 );
				add_action( 'template_redirect', array( AuthorEnumeration::class, 'render_numeric_author_404' ), 0, 0 );
			}

			if ( function_exists( 'add_filter' ) ) {
				add_filter( 'redirect_canonical', array( AuthorEnumeration::class, 'prevent_numeric_author_redirect' ), 10, 2 );
			}
		}

		if ( Config::feature_enabled( 'WE_ROCK_RESTRICT_REST_USERS' ) && function_exists( 'add_filter' ) ) {
			add_filter( 'rest_authentication_errors', array( RestUsers::class, 'restrict_anonymous_users_authentication_error' ), 0, 1 );
			add_filter( 'rest_pre_dispatch', array( RestUsers::class, 'restrict_anonymous_users_endpoint' ), 0, 3 );
			add_filter( 'rest_request_before_callbacks', array( RestUsers::class, 'restrict_anonymous_users_endpoint' ), 10, 3 );
		}

		if ( Config::feature_enabled( 'WE_ROCK_DISABLE_PINGBACKS' ) ) {
			if ( function_exists( 'add_filter' ) ) {
				add_filter( 'xmlrpc_methods', array( Pingbacks::class, 'filter_xmlrpc_methods' ), 10, 1 );
				add_filter( 'wp_headers', array( Pingbacks::class, 'remove_pingback_header' ), 10, 1 );
			}

			if ( function_exists( 'add_action' ) ) {
				add_action( 'send_headers', array( Pingbacks::class, 'remove_native_pingback_header' ), 0, 0 );
			}
		}

		if ( Config::feature_enabled( 'WE_ROCK_DISABLE_XMLRPC_AUTH', false ) && function_exists( 'add_filter' ) ) {
			add_filter( 'xmlrpc_enabled', array( XmlRpcAuth::class, 'disable_xmlrpc_authentication' ), 10, 1 );
		}

		if ( Config::feature_enabled( 'WE_ROCK_DISABLE_APPLICATION_PASSWORDS', false ) && function_exists( 'add_filter' ) ) {
			add_filter( 'wp_is_application_passwords_available', array( ApplicationPasswords::class, 'disable_application_passwords' ), 10, 1 );
			add_filter( 'wp_is_application_passwords_available_for_user', array( ApplicationPasswords::class, 'disable_application_passwords_for_user' ), 10, 2 );
		}

		if ( Config::feature_enabled( 'WE_ROCK_ENABLE_SITE_HEALTH_TESTS' ) && function_exists( 'add_filter' ) ) {
			add_filter( 'site_status_tests', array( SiteHealth::class, 'register_tests' ), 10, 1 );
		}

		if ( function_exists( 'add_action' ) ) {
			add_action( 'admin_notices', array( AdminNotices::class, 'render_critical_notices' ), 10, 0 );
		}
	}
}
