<?php
/**
 * Loads the replaceable MU plugin payload.
 *
 * @package WeRock\WPSafetyLite
 */

defined( 'ABSPATH' ) || exit;

// The stable root loader only requires this file, so future payloads can change their own file list.
$we_rock_safety_lite_files = array(
	__DIR__ . '/src/Config.php',
	__DIR__ . '/src/LoginHardening.php',
	__DIR__ . '/src/AuthorEnumeration.php',
	__DIR__ . '/src/RestUsers.php',
	__DIR__ . '/src/Pingbacks.php',
	__DIR__ . '/src/XmlRpcAuth.php',
	__DIR__ . '/src/ApplicationPasswords.php',
	__DIR__ . '/src/SiteHealth.php',
	__DIR__ . '/src/AdminNotices.php',
	__DIR__ . '/src/Updater.php',
	__DIR__ . '/src/Plugin.php',
);

foreach ( $we_rock_safety_lite_files as $we_rock_safety_lite_file ) {
	if ( is_readable( $we_rock_safety_lite_file ) ) {
		require_once $we_rock_safety_lite_file;
	}
}

// The updater remains available when the hardening kill switch is enabled.
if ( class_exists( '\WeRock\WPSafetyLite\Updater' ) ) {
	\WeRock\WPSafetyLite\Updater::bootstrap();
}

if ( class_exists( '\WeRock\WPSafetyLite\Plugin' ) ) {
	\WeRock\WPSafetyLite\Plugin::bootstrap();
}
