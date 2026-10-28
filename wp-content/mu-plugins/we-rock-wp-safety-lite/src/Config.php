<?php
/**
 * Configuration helpers for We.Rock.WP.Safety.Lite.
 *
 * @package WeRock\WPSafetyLite
 */

declare(strict_types=1);

namespace WeRock\WPSafetyLite;

defined( 'ABSPATH' ) || exit;

/**
 * Provides safe defaults without overwriting existing constants.
 */
final class Config {
	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public const VERSION = '0.1.0';

	/**
	 * Defines all public configuration constants when they are not set already.
	 *
	 * @return void
	 */
	public static function define_defaults(): void {
		$defaults = array(
			'WE_ROCK_SAFETY_DISABLED'                  => false,
			'WE_ROCK_GENERIC_LOGIN_ERRORS'             => true,
			'WE_ROCK_BLOCK_AUTHOR_ENUMERATION'         => true,
			'WE_ROCK_RESTRICT_REST_USERS'              => true,
			'WE_ROCK_DISABLE_PINGBACKS'                => true,
			'WE_ROCK_DISABLE_XMLRPC_AUTH'              => false,
			'WE_ROCK_DISABLE_APPLICATION_PASSWORDS'    => false,
			'WE_ROCK_ENABLE_SITE_HEALTH_TESTS'         => true,
		);

		foreach ( $defaults as $constant => $value ) {
			// Existing site-specific values always win.
			if ( ! defined( $constant ) ) {
				define( $constant, $value );
			}
		}
	}

	/**
	 * Determines whether the global kill switch allows hook registration.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return ! self::constant_as_bool( 'WE_ROCK_SAFETY_DISABLED', false );
	}

	/**
	 * Reads a boolean feature constant defensively.
	 *
	 * @param string $constant Constant name.
	 * @param bool   $default  Default value.
	 * @return bool
	 */
	public static function feature_enabled( string $constant, bool $default = true ): bool {
		return self::constant_as_bool( $constant, $default );
	}

	/**
	 * Converts common constant value formats into a boolean.
	 *
	 * @param string $constant Constant name.
	 * @param bool   $default  Default value.
	 * @return bool
	 */
	private static function constant_as_bool( string $constant, bool $default ): bool {
		if ( ! defined( $constant ) ) {
			return $default;
		}

		$value = constant( $constant );

		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_int( $value ) ) {
			return 1 === $value;
		}

		if ( is_string( $value ) ) {
			$normalized = strtolower( trim( $value ) );

			if ( in_array( $normalized, array( '1', 'true', 'yes', 'on' ), true ) ) {
				return true;
			}

			if ( in_array( $normalized, array( '0', 'false', 'no', 'off', '' ), true ) ) {
				return false;
			}
		}

		return $default;
	}
}
