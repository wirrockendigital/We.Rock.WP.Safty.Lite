<?php
/**
 * Pingback hardening callbacks.
 *
 * @package WeRock\WPSafetyLite
 */

declare(strict_types=1);

namespace WeRock\WPSafetyLite;

defined( 'ABSPATH' ) || exit;

/**
 * Removes pingback XML-RPC methods and response headers only.
 */
final class Pingbacks {
	/**
	 * Removes only the two WordPress pingback XML-RPC methods.
	 *
	 * @param mixed $methods XML-RPC methods.
	 * @return array
	 */
	public static function filter_xmlrpc_methods( $methods ): array {
		if ( ! is_array( $methods ) ) {
			return array();
		}

		unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );

		return $methods;
	}

	/**
	 * Removes the X-Pingback header from WordPress-managed response headers.
	 *
	 * @param mixed $headers Response headers.
	 * @return array
	 */
	public static function remove_pingback_header( $headers ): array {
		if ( ! is_array( $headers ) ) {
			return array();
		}

		foreach ( array_keys( $headers ) as $header ) {
			// Header names are case-insensitive.
			if ( 'x-pingback' === strtolower( (string) $header ) ) {
				unset( $headers[ $header ] );
			}
		}

		return $headers;
	}

	/**
	 * Removes a native X-Pingback header if another component already sent it.
	 *
	 * @return void
	 */
	public static function remove_native_pingback_header(): void {
		if ( function_exists( 'header_remove' ) && ! headers_sent() ) {
			header_remove( 'X-Pingback' );
		}
	}
}
