<?php
/**
 * Plugin Name: We.Rock.WP.Safety.Lite
 * Description: Stable loader for the We.Rock.WP.Safety.Lite MU plugin.
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

// Keep this loader outside the replaceable release payload.
$we_rock_safety_lite_bootstrap = __DIR__ . '/we-rock-wp-safety-lite/bootstrap.php';

if ( is_readable( $we_rock_safety_lite_bootstrap ) ) {
	require_once $we_rock_safety_lite_bootstrap;
} elseif ( function_exists( 'add_action' ) ) {
	// A missing payload must leave WordPress usable and alert administrators.
	add_action( 'admin_notices', 'we_rock_safety_lite_missing_payload_notice' );
	add_action( 'network_admin_notices', 'we_rock_safety_lite_missing_payload_notice' );
}

/**
 * Reports a missing MU payload to administrators.
 *
 * @return void
 */
function we_rock_safety_lite_missing_payload_notice(): void {
	if ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'update_plugins' ) ) {
		return;
	}

	echo '<div class="notice notice-error"><p>';
	echo 'We.Rock.WP.Safety.Lite: MU plugin files are missing. Restore the plugin directory from a backup.';
	echo '</p></div>';
}
