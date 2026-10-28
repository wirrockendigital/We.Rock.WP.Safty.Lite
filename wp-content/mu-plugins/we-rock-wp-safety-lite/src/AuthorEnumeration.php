<?php
/**
 * Author enumeration protection.
 *
 * @package WeRock\WPSafetyLite
 */

declare(strict_types=1);

namespace WeRock\WPSafetyLite;

defined( 'ABSPATH' ) || exit;

/**
 * Blocks anonymous numeric author probes without changing normal author archives.
 */
final class AuthorEnumeration {
	/**
	 * Turns anonymous numeric author requests into a 404 response.
	 *
	 * @param mixed $wp WordPress request object.
	 * @return void
	 */
	public static function block_numeric_author_query( $wp ): void {
		if ( ! self::should_block_request() ) {
			return;
		}

		if ( is_object( $wp ) && property_exists( $wp, 'query_vars' ) && is_array( $wp->query_vars ) ) {
			$wp->query_vars = array( 'error' => '404' );
		}

		self::mark_404();
	}

	/**
	 * Prevents WordPress from redirecting numeric author probes to author slugs.
	 *
	 * @param mixed $redirect_url  Proposed redirect URL.
	 * @param mixed $requested_url Requested URL.
	 * @return mixed
	 */
	public static function prevent_numeric_author_redirect( $redirect_url, $requested_url ) {
		unset( $requested_url );

		if ( self::should_block_request() ) {
			return false;
		}

		return $redirect_url;
	}

	/**
	 * Renders a terminal 404 for anonymous numeric author probes before later redirect logic.
	 *
	 * @return void
	 */
	public static function render_numeric_author_404(): void {
		if ( ! self::should_block_request() ) {
			return;
		}

		self::mark_404();

		$template = function_exists( 'get_404_template' ) ? get_404_template() : '';

		if ( is_string( $template ) && '' !== $template && is_readable( $template ) ) {
			include $template;
			exit;
		}

		if ( function_exists( 'esc_html_e' ) ) {
			esc_html_e( 'Nicht gefunden.', 'we-rock-wp-safety-lite' );
		} else {
			echo 'Nicht gefunden.';
		}

		exit;
	}

	/**
	 * Determines whether the current request is an anonymous numeric author probe.
	 *
	 * @return bool
	 */
	private static function should_block_request(): bool {
		if ( self::is_user_logged_in() ) {
			return false;
		}

		$author = self::get_author_query_value();

		return null !== $author && preg_match( '/^[1-9][0-9]*$/', $author ) === 1;
	}

	/**
	 * Reads the raw author query value from query parameters only.
	 *
	 * @return string|null
	 */
	private static function get_author_query_value(): ?string {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput -- Read-only route matching validates the final value.
		if ( ! isset( $_GET['author'] ) ) {
			return null;
		}

		$value = self::unslash( $_GET['author'] );

		if ( is_array( $value ) ) {
			return null;
		}

		return trim( (string) $value );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput

	/**
	 * Marks the current request as a 404 without exposing author details.
	 *
	 * @return void
	 */
	private static function mark_404(): void {
		global $wp_query;

		if ( is_object( $wp_query ) && method_exists( $wp_query, 'set_404' ) ) {
			$wp_query->set_404();
		}

		if ( function_exists( 'status_header' ) ) {
			status_header( 404 );
		}

		if ( function_exists( 'nocache_headers' ) ) {
			nocache_headers();
		}
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
}
