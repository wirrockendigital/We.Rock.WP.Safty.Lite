<?php
/**
 * Tests the GitHub release contract and guarded MU payload replacement.
 *
 * @package WeRock\WPSafetyLite\Tests
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WeRock\WPSafetyLite\Updater;

/**
 * Covers release validation and filesystem rollback.
 */
final class UpdaterTest extends TestCase {
	/**
	 * Temporary ZIP path.
	 *
	 * @var string|null
	 */
	private $package;

	/**
	 * Prepares an isolated MU plugin directory.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		global $wp_filesystem;
		$wp_filesystem = new We_Rock_Test_Filesystem();
		$wp_filesystem->delete( WPMU_PLUGIN_DIR, true );
		mkdir( WPMU_PLUGIN_DIR, 0700, true );
		$GLOBALS['we_rock_test_download_source'] = null;
		$GLOBALS['we_rock_test_http_response']   = array();
		$GLOBALS['we_rock_test_http_calls']      = 0;
		$GLOBALS['we_rock_test_transients']      = array();
		$this->package = null;
	}

	/**
	 * Removes all update test files.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		global $wp_filesystem;
		$wp_filesystem->delete( WPMU_PLUGIN_DIR, true );
		if ( is_string( $this->package ) && is_file( $this->package ) ) {
			unlink( $this->package );
		}
		$GLOBALS['we_rock_test_download_source'] = null;
		parent::tearDown();
	}

	/**
	 * Accepts only the expected stable release asset.
	 *
	 * @return void
	 */
	public function test_valid_release_is_parsed(): void {
		$release = Updater::parse_release( $this->release_data() );

		$this->assertIsArray( $release );
		$this->assertSame( '0.1.1', $release['version'] );
		$this->assertTrue( Updater::is_newer( $release ) );
		$this->assertSame( str_repeat( 'a', 64 ), $release['sha256'] );
	}

	/**
	 * Rejects prereleases and redirects to untrusted package URLs.
	 *
	 * @return void
	 */
	public function test_invalid_release_metadata_is_rejected(): void {
		$data               = $this->release_data();
		$data['prerelease']  = true;
		$rejected           = Updater::parse_release( $data );
		$this->assertInstanceOf( WP_Error::class, $rejected );

		$data                                      = $this->release_data();
		$data['assets'][0]['browser_download_url'] = 'https://example.test/untrusted.zip';
		$rejected                                  = Updater::parse_release( $data );
		$this->assertSame( 'invalid_release_asset', $rejected->get_error_code() );

		$data                      = $this->release_data();
		$data['assets'][0]['size'] = 0;
		$rejected                  = Updater::parse_release( $data );
		$this->assertSame( 'invalid_release_asset', $rejected->get_error_code() );
	}

	/**
	 * Caches the absence of releases without claiming an update exists.
	 *
	 * @return void
	 */
	public function test_missing_release_is_cached(): void {
		$GLOBALS['we_rock_test_http_response'] = array( 'response' => array( 'code' => 404 ) );
		$method = new ReflectionMethod( Updater::class, 'get_release' );
		$method->setAccessible( true );

		$this->assertNull( $method->invoke( null ) );
		$this->assertNull( $method->invoke( null ) );
		$this->assertSame( 1, $GLOBALS['we_rock_test_http_calls'] );
	}

	/**
	 * Reports a GitHub error separately from an absent release.
	 *
	 * @return void
	 */
	public function test_github_failure_is_not_reported_as_current(): void {
		$GLOBALS['we_rock_test_http_response'] = array( 'response' => array( 'code' => 500 ) );
		$method = new ReflectionMethod( Updater::class, 'get_release' );
		$method->setAccessible( true );
		$result = $method->invoke( null );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'github_unavailable', $result->get_error_code() );
	}

	/**
	 * Shows an explicit empty release state in the administrator page.
	 *
	 * @return void
	 */
	public function test_admin_page_reports_no_published_release(): void {
		$GLOBALS['we_rock_test_caps']['update_plugins'] = true;
		$GLOBALS['we_rock_test_http_response']          = array( 'response' => array( 'code' => 404 ) );

		ob_start();
		Updater::render_page();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'No published release is available yet.', $html );
		$this->assertStringNotContainsString( 'Install MU plugin update', $html );
	}

	/**
	 * Offers an administrator action when a newer stable release exists.
	 *
	 * @return void
	 */
	public function test_admin_page_offers_manual_update(): void {
		$GLOBALS['we_rock_test_caps']['update_plugins'] = true;
		$GLOBALS['we_rock_test_http_response']          = array(
			'response' => array( 'code' => 200 ),
			'body'     => json_encode( $this->release_data() ),
		);

		ob_start();
		Updater::render_page();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'Available version: <strong>0.1.1</strong>', $html );
		$this->assertStringContainsString( 'we_rock_safety_lite_update', $html );
		$this->assertSame( 1, $GLOBALS['we_rock_test_http_calls'] );
	}

	/**
	 * Swaps only the payload and retains the previous directory.
	 *
	 * @return void
	 */
	public function test_verified_payload_is_installed_with_backup(): void {
		$this->create_installed_payload();
		$release = $this->create_release_package();
		$result  = Updater::install_release( $release );

		$this->assertTrue( $result );
		$this->assertSame( '0.1.1', $this->payload_version( WPMU_PLUGIN_DIR . '/we-rock-wp-safety-lite' ) );
		$this->assertSame( '0.1.0', $this->payload_version( WPMU_PLUGIN_DIR . '/.we-rock-wp-safety-previous' ) );
	}

	/**
	 * Restores the installed payload when a staged move fails.
	 *
	 * @return void
	 */
	public function test_failed_swap_restores_previous_payload(): void {
		global $wp_filesystem;
		$this->create_installed_payload();
		$release                            = $this->create_release_package();
		$wp_filesystem->fail_staged_move = true;
		$result                             = Updater::install_release( $release );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'install_failed', $result->get_error_code() );
		$this->assertSame( '0.1.0', $this->payload_version( WPMU_PLUGIN_DIR . '/we-rock-wp-safety-lite' ) );
	}

	/**
	 * Rejects a mismatched ZIP before moving the installed payload.
	 *
	 * @return void
	 */
	public function test_digest_mismatch_preserves_installed_payload(): void {
		$this->create_installed_payload();
		$release           = $this->create_release_package();
		$release['sha256'] = str_repeat( '0', 64 );
		$result            = Updater::install_release( $release );

		$this->assertSame( 'digest_mismatch', $result->get_error_code() );
		$this->assertSame( '0.1.0', $this->payload_version( WPMU_PLUGIN_DIR . '/we-rock-wp-safety-lite' ) );
		$this->assertFalse( file_exists( WPMU_PLUGIN_DIR . '/.we-rock-wp-safety-previous' ) );
	}

	/**
	 * Rejects a package that adds an unreviewed file before the installed payload moves.
	 *
	 * @return void
	 */
	public function test_unexpected_package_file_preserves_installed_payload(): void {
		$this->create_installed_payload();
		$release = $this->create_release_package( true );
		$result  = Updater::install_release( $release );

		$this->assertSame( 'invalid_package_layout', $result->get_error_code() );
		$this->assertSame( '0.1.0', $this->payload_version( WPMU_PLUGIN_DIR . '/we-rock-wp-safety-lite' ) );
		$this->assertFalse( file_exists( WPMU_PLUGIN_DIR . '/.we-rock-wp-safety-previous' ) );
	}

	/**
	 * Creates the currently installed test payload.
	 *
	 * @return void
	 */
	private function create_installed_payload(): void {
		$path = WPMU_PLUGIN_DIR . '/we-rock-wp-safety-lite';
		mkdir( $path . '/src', 0700, true );
		file_put_contents( $path . '/bootstrap.php', "<?php\n/** Test bootstrap. */\n" );
		file_put_contents( $path . '/release.json', "{\"version\":\"0.1.0\"}\n" );
		file_put_contents( $path . '/LICENSE', "MIT\n" );
		file_put_contents( $path . '/src/Config.php', "<?php\n/** Test config. */\nfinal class TestConfig { public const VERSION = '0.1.0'; }\n" );
		file_put_contents( $path . '/src/Plugin.php', "<?php\n/** Test plugin. */\n" );
		file_put_contents( $path . '/src/Updater.php', "<?php\n/** Test updater. */\n" );
	}

	/**
	 * Builds an isolated ZIP in the expected release layout.
	 *
	 * @param bool $unexpected_file Add a disallowed file to the package.
	 * @return array<string, string>
	 */
	private function create_release_package( bool $unexpected_file = false ): array {
		$this->package = tempnam( sys_get_temp_dir(), 'wrsl-zip-' );
		$zip = new ZipArchive();
		$this->assertTrue( $zip->open( $this->package, ZipArchive::OVERWRITE ) );
		$root = 'we-rock-wp-safety-lite/';
		$zip->addFromString( $root . 'bootstrap.php', "<?php\n/** Test bootstrap. */\n" );
		$zip->addFromString( $root . 'release.json', "{\"version\":\"0.1.1\"}\n" );
		$zip->addFromString( $root . 'LICENSE', "MIT\n" );
		$zip->addFromString( $root . 'src/Config.php', "<?php\n/** Test config. */\nfinal class TestConfig { public const VERSION = '0.1.1'; }\n" );
		$zip->addFromString( $root . 'src/Plugin.php', "<?php\n/** Test plugin. */\n" );
		$zip->addFromString( $root . 'src/Updater.php', "<?php\n/** Test updater. */\n" );
		if ( $unexpected_file ) {
			$zip->addFromString( $root . '.htaccess', "Deny from all\n" );
		}
		$this->assertTrue( $zip->close() );
		$GLOBALS['we_rock_test_download_source'] = $this->package;

		return array(
			'version' => '0.1.1',
			'package' => 'https://example.test/we-rock-wp-safety-lite.zip',
			'sha256'  => hash_file( 'sha256', $this->package ),
		);
	}

	/**
	 * Reads a test payload manifest.
	 *
	 * @param string $path Payload directory.
	 * @return string
	 */
	private function payload_version( string $path ): string {
		$data = json_decode( (string) file_get_contents( $path . '/release.json' ), true );
		return (string) $data['version'];
	}

	/**
	 * Supplies a valid GitHub release response.
	 *
	 * @return array<string, mixed>
	 */
	private function release_data(): array {
		return array(
			'tag_name'  => 'v0.1.1',
			'draft'     => false,
			'prerelease' => false,
			'assets'    => array(
				array(
					'name'                 => 'we-rock-wp-safety-lite.zip',
					'state'                => 'uploaded',
					'browser_download_url' => 'https://github.com/wirrockendigital/We.Rock.WP.Safty.Lite/releases/download/v0.1.1/we-rock-wp-safety-lite.zip',
					'digest'               => 'sha256:' . str_repeat( 'a', 64 ),
					'size'                 => 2048,
				),
			),
		);
	}
}
