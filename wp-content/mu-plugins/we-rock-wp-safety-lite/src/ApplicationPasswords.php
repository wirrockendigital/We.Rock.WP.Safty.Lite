<?php
/**
 * Application Passwords hardening callbacks.
 *
 * @package WeRock\WPSafetyLite
 */

declare(strict_types=1);

namespace WeRock\WPSafetyLite;

defined( 'ABSPATH' ) || exit;

/**
 * Disables WordPress Application Passwords when explicitly configured.
 */
final class ApplicationPasswords {
	/**
	 * Disables Application Passwords globally.
	 *
	 * @param mixed $available Existing availability value.
	 * @return bool
	 */
	public static function disable_application_passwords( $available ): bool {
		unset( $available );

		return false;
	}

	/**
	 * Disables Application Passwords for each user.
	 *
	 * @param mixed $available Existing availability value.
	 * @param mixed $user      User object.
	 * @return bool
	 */
	public static function disable_application_passwords_for_user( $available, $user ): bool {
		unset( $available, $user );

		return false;
	}
}
