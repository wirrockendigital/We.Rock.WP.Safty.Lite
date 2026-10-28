<?php
/**
 * Optional wp-config.php hardening constants for We.Rock.WP.Safety.Lite.
 *
 * Copy the lines between the markers into wp-config.php above the WordPress
 * bootstrap line. Do not include a second opening PHP tag.
 *
 * @package WeRock\WPSafetyLite
 */

// BEGIN WE.ROCK.WP.CONFIG.HARDENING
defined( 'DISALLOW_FILE_EDIT' ) || define( 'DISALLOW_FILE_EDIT', true );
defined( 'WP_DEBUG_DISPLAY' ) || define( 'WP_DEBUG_DISPLAY', false );
defined( 'WP_ENVIRONMENT_TYPE' ) || define( 'WP_ENVIRONMENT_TYPE', 'production' );

defined( 'WE_ROCK_SAFETY_DISABLED' ) || define( 'WE_ROCK_SAFETY_DISABLED', false );
defined( 'WE_ROCK_GENERIC_LOGIN_ERRORS' ) || define( 'WE_ROCK_GENERIC_LOGIN_ERRORS', true );
defined( 'WE_ROCK_BLOCK_AUTHOR_ENUMERATION' ) || define( 'WE_ROCK_BLOCK_AUTHOR_ENUMERATION', true );
defined( 'WE_ROCK_RESTRICT_REST_USERS' ) || define( 'WE_ROCK_RESTRICT_REST_USERS', true );
defined( 'WE_ROCK_DISABLE_PINGBACKS' ) || define( 'WE_ROCK_DISABLE_PINGBACKS', true );
defined( 'WE_ROCK_DISABLE_XMLRPC_AUTH' ) || define( 'WE_ROCK_DISABLE_XMLRPC_AUTH', false );
defined( 'WE_ROCK_DISABLE_APPLICATION_PASSWORDS' ) || define( 'WE_ROCK_DISABLE_APPLICATION_PASSWORDS', false );
defined( 'WE_ROCK_ENABLE_SITE_HEALTH_TESTS' ) || define( 'WE_ROCK_ENABLE_SITE_HEALTH_TESTS', true );
// END WE.ROCK.WP.CONFIG.HARDENING
