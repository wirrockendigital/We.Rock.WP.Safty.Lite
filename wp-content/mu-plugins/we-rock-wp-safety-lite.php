<?php
/**
 * Plugin Name: We.Rock.WP.Safety.Lite
 * Description: Small MU plugin with stable, low-risk WordPress hardening defaults.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: We.Rock
 * License: MIT
 * License URI: https://opensource.org/license/mit/
 * Text Domain: we-rock-wp-safety-lite
 *
 * @package WeRock\WPSafetyLite
 */

defined( 'ABSPATH' ) || exit;

$we_rock_safety_lite_dir = __DIR__ . '/we-rock-wp-safety-lite';
$we_rock_safety_lite_files = array(
	$we_rock_safety_lite_dir . '/src/Config.php',
	$we_rock_safety_lite_dir . '/src/LoginHardening.php',
	$we_rock_safety_lite_dir . '/src/AuthorEnumeration.php',
	$we_rock_safety_lite_dir . '/src/RestUsers.php',
	$we_rock_safety_lite_dir . '/src/Pingbacks.php',
	$we_rock_safety_lite_dir . '/src/XmlRpcAuth.php',
	$we_rock_safety_lite_dir . '/src/ApplicationPasswords.php',
	$we_rock_safety_lite_dir . '/src/SiteHealth.php',
	$we_rock_safety_lite_dir . '/src/AdminNotices.php',
	$we_rock_safety_lite_dir . '/src/Plugin.php',
);

foreach ( $we_rock_safety_lite_files as $we_rock_safety_lite_file ) {
	// Missing source files must not break the site.
	if ( is_readable( $we_rock_safety_lite_file ) ) {
		require_once $we_rock_safety_lite_file;
	}
}

if ( class_exists( '\WeRock\WPSafetyLite\Plugin' ) ) {
	\WeRock\WPSafetyLite\Plugin::bootstrap();
}
