<?php
/**
 * REST users endpoint protection.
 *
 * @package WeRock\WPSafetyLite
 */

declare(strict_types=1);

namespace WeRock\WPSafetyLite;

defined( 'ABSPATH' ) || exit;

/**
 * Hides core REST user listing endpoints from anonymous visitors.
 */
final class RestUsers {
	/**
	 * Normalizes early REST authentication errors for anonymous core users routes.
	 *
	 * @param mixed $response Existing REST authentication response.
	 * @return mixed
	 */
	public static function restrict_anonymous_users_authentication_error( $response ) {
		if ( self::is_user_logged_in() || ! self::is_current_http_core_users_route() ) {
			return $response;
		}

		return self::not_found_error( $response );
	}

	/**
	 * Returns a 404 for anonymous access to exact core users routes.
	 *
	 * @param mixed $response Existing REST response.
	 * @param mixed $handler  REST handler.
	 * @param mixed $request  REST request.
	 * @return mixed
	 */
	public static function restrict_anonymous_users_endpoint( $response, $handler, $request ) {
		unset( $handler );

		if ( self::is_user_logged_in() ) {
			return $response;
		}

		if ( ! self::is_core_users_route( $request ) ) {
			return $response;
		}

		return self::not_found_error( $response );
	}

	/**
	 * Checks the namespace and route without affecting unrelated REST endpoints.
	 *
	 * @param mixed $request REST request.
	 * @return bool
	 */
	private static function is_core_users_route( $request ): bool {
		if ( ! is_object( $request ) || ! method_exists( $request, 'get_route' ) ) {
			return false;
		}

		$route     = (string) $request->get_route();
		$namespace = self::get_namespace( $request, $route );

		if ( 'wp/v2' !== $namespace ) {
			return false;
		}

		return preg_match( '#^/wp/v2/users(?:/[0-9]+)?/?$#', $route ) === 1;
	}

	/**
	 * Checks the current HTTP request path for exact core users REST routes.
	 *
	 * @return bool
	 */
	private static function is_current_http_core_users_route(): bool {
		$route = '';

		if ( isset( $_GET['rest_route'] ) && is_scalar( $_GET['rest_route'] ) ) {
			$route = (string) self::unslash( $_GET['rest_route'] );
		}

		if ( '' === $route && isset( $_SERVER['REQUEST_URI'] ) && is_scalar( $_SERVER['REQUEST_URI'] ) ) {
			$request_uri = (string) self::unslash( $_SERVER['REQUEST_URI'] );
			$path        = parse_url( $request_uri, PHP_URL_PATH );
			$path        = is_string( $path ) ? $path : '';
			$prefix      = self::rest_url_prefix();
			$needle      = '/' . trim( $prefix, '/' ) . '/';

			if ( 0 === strpos( $path, $needle ) ) {
				$route = '/' . ltrim( substr( $path, strlen( $needle ) ), '/' );
			}
		}

		return preg_match( '#^/wp/v2/users(?:/[0-9]+)?/?$#', $route ) === 1;
	}

	/**
	 * Reads or derives the REST namespace from the request route.
	 *
	 * @param object $request REST request.
	 * @param string $route   REST route.
	 * @return string
	 */
	private static function get_namespace( object $request, string $route ): string {
		if ( method_exists( $request, 'get_namespace' ) ) {
			return (string) $request->get_namespace();
		}

		if ( preg_match( '#^/([^/]+/[^/]+)(?:/|$)#', $route, $matches ) === 1 ) {
			return (string) $matches[1];
		}

		return '';
	}

	/**
	 * Builds the REST not-found error when WordPress error handling is available.
	 *
	 * @param mixed $fallback Fallback response.
	 * @return mixed
	 */
	private static function not_found_error( $fallback ) {
		if ( ! class_exists( '\WP_Error' ) ) {
			return $fallback;
		}

		return new \WP_Error(
			'we_rock_rest_users_not_found',
			self::esc_html__( 'Nicht gefunden.' ),
			array( 'status' => 404 )
		);
	}

	/**
	 * Reads the configured REST URL prefix.
	 *
	 * @return string
	 */
	private static function rest_url_prefix(): string {
		if ( function_exists( 'rest_get_url_prefix' ) ) {
			return (string) rest_get_url_prefix();
		}

		return 'wp-json';
	}

	/**
	 * Checks authentication state defensively.
	 *
	 * @return bool
	 */
	private static function is_user_logged_in(): bool {
		return function_exists( 'is_user_logged_in' ) && is_user_logged_in();
	}

	/**
	 * Removes slashes with a WordPress-aware fallback.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	private static function unslash( $value ) {
		if ( function_exists( 'wp_unslash' ) ) {
			return wp_unslash( $value );
		}

		if ( is_array( $value ) ) {
			return array_map( array( self::class, 'unslash' ), $value );
		}

		return is_string( $value ) ? stripslashes( $value ) : $value;
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
