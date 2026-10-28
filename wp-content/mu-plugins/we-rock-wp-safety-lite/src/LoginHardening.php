<?php
/**
 * Login hardening callbacks.
 *
 * @package WeRock\WPSafetyLite
 */

declare(strict_types=1);

namespace WeRock\WPSafetyLite;

defined( 'ABSPATH' ) || exit;

/**
 * Normalizes unsafe credential error messages.
 */
final class LoginHardening {
	/**
	 * Replaces username, email, and password enumeration messages with one generic message.
	 *
	 * @param mixed $error Login error value.
	 * @return mixed
	 */
	public static function filter_login_errors( $error ) {
		if ( self::is_wp_error_object( $error ) ) {
			return $error;
		}

		if ( ! is_string( $error ) ) {
			return '';
		}

		if ( '' === trim( $error ) ) {
			return $error;
		}

		if ( ! self::looks_like_core_credential_error( $error ) ) {
			return $error;
		}

		return self::esc_html__( 'Anmeldung fehlgeschlagen. Bitte pruefe deine Zugangsdaten.' );
	}

	/**
	 * Detects core credential errors that can disclose account existence.
	 *
	 * @param string $error Login error markup.
	 * @return bool
	 */
	private static function looks_like_core_credential_error( string $error ): bool {
		$normalized = strtolower( self::strip_all_tags( $error ) );
		$needles    = array(
			'incorrect password',
			'password you entered',
			'invalid username',
			'invalid email',
			'unknown email',
			'not registered',
			'ist nicht auf dieser website registriert',
			'e-mail-adresse ist auf dieser website nicht registriert',
			'das passwort, das du',
		);

		foreach ( $needles as $needle ) {
			// Matching only known credential messages keeps unrelated safe states intact.
			if ( false !== strpos( $normalized, $needle ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks WP_Error defensively when WordPress is available.
	 *
	 * @param mixed $value Value to inspect.
	 * @return bool
	 */
	private static function is_wp_error_object( $value ): bool {
		if ( function_exists( 'is_wp_error' ) ) {
			return is_wp_error( $value );
		}

		return is_object( $value ) && is_a( $value, 'WP_Error' );
	}

	/**
	 * Strips markup with a WordPress-aware fallback.
	 *
	 * @param string $text Text to strip.
	 * @return string
	 */
	private static function strip_all_tags( string $text ): string {
		if ( function_exists( 'wp_strip_all_tags' ) ) {
			return wp_strip_all_tags( $text );
		}

		return strip_tags( $text );
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
