<?php
/**
 * Tests for We.Rock.WP.Safety.Lite.
 *
 * @package WeRock\WPSafetyLite\Tests
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WeRock\WPSafetyLite\ApplicationPasswords;
use WeRock\WPSafetyLite\AuthorEnumeration;
use WeRock\WPSafetyLite\LoginHardening;
use WeRock\WPSafetyLite\Pingbacks;
use WeRock\WPSafetyLite\Plugin;
use WeRock\WPSafetyLite\RestUsers;
use WeRock\WPSafetyLite\SiteHealth;

/**
 * Covers the public hardening callbacks.
 */
final class PluginTest extends TestCase {
	/**
	 * Resets mutable WordPress test doubles.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['we_rock_test_hooks']         = array();
		$GLOBALS['we_rock_test_status_header'] = null;
		$GLOBALS['we_rock_test_nocache']       = false;
		$GLOBALS['we_rock_test_logged_in']     = false;
		$GLOBALS['we_rock_test_caps']          = array();
		$GLOBALS['we_rock_test_options']       = array(
			'siteurl' => 'https://example.test',
			'home'    => 'https://example.test',
		);
		$GLOBALS['we_rock_test_environment']   = 'production';
		$GLOBALS['we_rock_test_http_calls']    = 0;
		$GLOBALS['we_rock_test_http_response'] = array();
		$GLOBALS['we_rock_test_transients']    = array();
		$GLOBALS['we_rock_test_mu_plugins']     = array();
		$GLOBALS['we_rock_test_rest_status']    = 200;
		unset( $GLOBALS['we_rock_test_application_passwords_disabled'] );
		$GLOBALS['wp_query']                   = new We_Rock_Test_Query();
		$_GET                                  = array();
	}

	/**
	 * Ensures the kill switch leaves only the recovery updater available.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @return void
	 */
	public function test_kill_switch_keeps_only_updater_hooks(): void {
		define( 'WE_ROCK_SAFETY_DISABLED', true );

		require dirname( __DIR__ ) . '/../we-rock-wp-safety-lite.php';

		$this->assertArrayNotHasKey( 'login_errors', $GLOBALS['we_rock_test_hooks']['filter'] ?? array() );
		$this->assertArrayHasKey( 'admin_post_we_rock_safety_lite_update', $GLOBALS['we_rock_test_hooks']['action'] );
		$this->assertSame( 0, $GLOBALS['we_rock_test_http_calls'] );
	}

	/**
	 * Ensures unsafe core login errors are replaced with one generic message.
	 *
	 * @return void
	 */
	public function test_generic_login_errors(): void {
		$error  = '<strong>Error:</strong> The password you entered for the username admin is incorrect.';
		$result = LoginHardening::filter_login_errors( $error );

		$this->assertSame( 'Anmeldung fehlgeschlagen. Bitte pruefe deine Zugangsdaten.', $result );
	}

	/**
	 * Ensures WP_Error values are not damaged by login error filtering.
	 *
	 * @return void
	 */
	public function test_login_filter_preserves_wp_error_objects(): void {
		$error = new WP_Error( 'safe_state', 'Safe state.' );

		$this->assertSame( $error, LoginHardening::filter_login_errors( $error ) );
	}

	/**
	 * Ensures anonymous numeric author probes are converted to 404.
	 *
	 * @return void
	 */
	public function test_author_one_as_anonymous_visitor_is_404(): void {
		$_GET['author'] = '1';
		$wp             = (object) array( 'query_vars' => array( 'author' => 1 ) );

		AuthorEnumeration::block_numeric_author_query( $wp );

		$this->assertSame( array( 'error' => '404' ), $wp->query_vars );
		$this->assertSame( 404, $GLOBALS['we_rock_test_status_header'] );
		$this->assertTrue( $GLOBALS['wp_query']->is_404 );
	}

	/**
	 * Ensures administrators can test numeric author URLs without being blocked.
	 *
	 * @return void
	 */
	public function test_author_one_as_administrator_is_allowed(): void {
		$GLOBALS['we_rock_test_logged_in']          = true;
		$GLOBALS['we_rock_test_caps']['manage_options'] = true;
		$_GET['author']                            = '1';
		$wp                                        = (object) array( 'query_vars' => array( 'author' => 1 ) );

		AuthorEnumeration::block_numeric_author_query( $wp );

		$this->assertSame( array( 'author' => 1 ), $wp->query_vars );
		$this->assertNull( $GLOBALS['we_rock_test_status_header'] );
	}

	/**
	 * Ensures anonymous core REST user list access receives a 404 WP_Error.
	 *
	 * @return void
	 */
	public function test_anonymous_rest_user_list_is_404(): void {
		$request = new We_Rock_Test_REST_Request( 'wp/v2', '/wp/v2/users' );
		$result  = RestUsers::restrict_anonymous_users_endpoint( null, null, $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( array( 'status' => 404 ), $result->get_error_data() );
	}

	/**
	 * Ensures authenticated REST users endpoints are left to WordPress capability checks.
	 *
	 * @return void
	 */
	public function test_authenticated_rest_user_list_is_allowed(): void {
		$GLOBALS['we_rock_test_logged_in'] = true;
		$request                           = new We_Rock_Test_REST_Request( 'wp/v2', '/wp/v2/users/1' );

		$this->assertNull( RestUsers::restrict_anonymous_users_endpoint( null, null, $request ) );
	}

	/**
	 * Ensures only pingback XML-RPC methods are removed.
	 *
	 * @return void
	 */
	public function test_pingback_methods_are_removed_only(): void {
		$methods = array(
			'pingback.ping'                    => 'this:pingback_ping',
			'pingback.extensions.getPingbacks' => 'this:pingback_extensions',
			'wp.getPosts'                      => 'this:wp_getPosts',
		);

		$result = Pingbacks::filter_xmlrpc_methods( $methods );

		$this->assertArrayNotHasKey( 'pingback.ping', $result );
		$this->assertArrayNotHasKey( 'pingback.extensions.getPingbacks', $result );
		$this->assertArrayHasKey( 'wp.getPosts', $result );
	}

	/**
	 * Ensures the Application Passwords switch disables availability.
	 *
	 * @return void
	 */
	public function test_application_password_switch_disables_passwords(): void {
		$this->assertFalse( ApplicationPasswords::disable_application_passwords( true ) );
		$this->assertFalse( ApplicationPasswords::disable_application_passwords_for_user( true, (object) array( 'ID' => 1 ) ) );
	}

	/**
	 * Ensures all Site Health checks are registered.
	 *
	 * @return void
	 */
	public function test_site_health_checks_are_registered(): void {
		$tests = SiteHealth::register_tests( array() );

		$this->assertArrayHasKey( 'we_rock_https_urls', $tests['direct'] );
		$this->assertArrayHasKey( 'we_rock_wp_debug', $tests['direct'] );
		$this->assertArrayHasKey( 'we_rock_uploads_htaccess_markers', $tests['direct'] );
		$this->assertArrayHasKey( 'we_rock_security_plugins_present', $tests['direct'] );
		$this->assertArrayHasKey( 'we_rock_application_password_policy', $tests['direct'] );
		$this->assertArrayHasKey( 'we_rock_rest_users_me', $tests['direct'] );
	}

	/**
	 * Ensures Site Health marker checks are read-only and report success when files contain markers.
	 *
	 * @return void
	 */
	public function test_site_health_marker_checks(): void {
		$GLOBALS['we_rock_test_caps']['manage_options'] = true;

		if ( ! is_dir( ABSPATH ) ) {
			mkdir( ABSPATH, 0777, true );
		}

		if ( ! is_dir( WP_CONTENT_DIR . '/uploads' ) ) {
			mkdir( WP_CONTENT_DIR . '/uploads', 0777, true );
		}

		file_put_contents( ABSPATH . '.htaccess', "# BEGIN WE.ROCK.WP.HARDENING\n# END WE.ROCK.WP.HARDENING\n" );
		file_put_contents( WP_CONTENT_DIR . '/uploads/.htaccess', "# BEGIN WE.ROCK.NO.EXECUTABLE.UPLOADS\n<FilesMatch \"\\.php$\">\nRequire all denied\n</FilesMatch>\n# END WE.ROCK.NO.EXECUTABLE.UPLOADS\n" );

		$this->assertSame( 'good', SiteHealth::test_root_htaccess_markers()['status'] );
		$this->assertSame( 'good', SiteHealth::test_uploads_htaccess_markers()['status'] );
		$this->assertSame( 'good', SiteHealth::test_uploads_executable_hardening()['status'] );
	}

	/**
	 * Ensures security plugin presence is detected from active plugin metadata.
	 *
	 * @return void
	 */
	public function test_security_plugin_presence_check(): void {
		$GLOBALS['we_rock_test_caps']['manage_options'] = true;
		$GLOBALS['we_rock_test_options']['active_plugins'] = array(
			'ninjafirewall/ninjafirewall.php',
			'wp-2fa/wp-2fa.php',
		);
		$GLOBALS['we_rock_test_mu_plugins'] = array(
			'0-ninjafirewall.php' => array( 'Name' => 'NinjaFirewall' ),
		);

		$this->assertSame( 'good', SiteHealth::test_security_plugins_present()['status'] );
	}

	/**
	 * Ensures policy checks stay read-only and report compatible defaults.
	 *
	 * @return void
	 */
	public function test_policy_checks_report_compatible_defaults(): void {
		$GLOBALS['we_rock_test_caps']['manage_options'] = true;

		$this->assertSame( 'good', SiteHealth::test_file_mods_not_blocked()['status'] );
		$this->assertSame( 'good', SiteHealth::test_application_password_policy()['status'] );
		$this->assertSame( 'good', SiteHealth::test_rest_users_me_compatibility()['status'] );
	}

	/**
	 * Ensures the plugin registers no cron events.
	 *
	 * @return void
	 */
	public function test_no_unexpected_cronjobs_are_registered(): void {
		Plugin::bootstrap();

		$this->assertArrayNotHasKey( 'cron', $GLOBALS['we_rock_test_hooks'] );
	}

	/**
	 * Ensures bootstrap and read-only checks make no external HTTP requests.
	 *
	 * @return void
	 */
	public function test_no_external_http_requests_are_made(): void {
		$GLOBALS['we_rock_test_caps']['manage_options'] = true;

		Plugin::bootstrap();
		SiteHealth::test_https_urls();
		SiteHealth::test_environment_type();
		SiteHealth::test_application_password_policy();
		SiteHealth::test_rest_users_me_compatibility();

		$this->assertSame( 0, $GLOBALS['we_rock_test_http_calls'] );
	}
}
