<?php
/**
 * GitHub release checks and administrator-approved MU payload updates.
 *
 * @package WeRock\WPSafetyLite
 */

declare(strict_types=1);

namespace WeRock\WPSafetyLite;

defined( 'ABSPATH' ) || exit;

/**
 * Updates only the replaceable MU payload directory.
 */
final class Updater {
	private const API_URL       = 'https://api.github.com/repos/wirrockendigital/We.Rock.WP.Safty.Lite/releases/latest';
	private const REPO_URL      = 'https://github.com/wirrockendigital/We.Rock.WP.Safty.Lite';
	private const ASSET_NAME    = 'we-rock-wp-safety-lite.zip';
	private const MAX_ZIP_SIZE  = 10485760;
	private const PAYLOAD_NAME  = 'we-rock-wp-safety-lite';
	private const CACHE_KEY     = 'we_rock_safety_lite_release';
	private const LOCK_KEY      = 'we_rock_safety_lite_update_lock';
	private const RESULT_PREFIX = 'we_rock_safety_lite_update_result_';
	private const PAGE_SLUG     = 'we-rock-safety-lite-updates';

	/**
	 * Registers administrator-only update controls.
	 *
	 * @return void
	 */
	public static function bootstrap(): void {
		if ( ! function_exists( 'add_action' ) ) {
			return;
		}

		add_action( 'admin_menu', array( self::class, 'register_admin_page' ) );
		add_action( 'network_admin_menu', array( self::class, 'register_network_page' ) );
		add_action( 'admin_notices', array( self::class, 'render_plugin_notice' ) );
		add_action( 'network_admin_notices', array( self::class, 'render_plugin_notice' ) );
		add_action( 'admin_post_we_rock_safety_lite_update', array( self::class, 'handle_update' ) );
	}

	/**
	 * Adds the update page to the regular Tools menu.
	 *
	 * @return void
	 */
	public static function register_admin_page(): void {
		add_management_page(
			'We.Rock.WP.Safety.Lite Updates',
			'We.Rock Updates',
			'update_plugins',
			self::PAGE_SLUG,
			array( self::class, 'render_page' )
		);
	}

	/**
	 * Adds the update page to multisite Network Settings.
	 *
	 * @return void
	 */
	public static function register_network_page(): void {
		add_submenu_page(
			'settings.php',
			'We.Rock.WP.Safety.Lite Updates',
			'We.Rock Updates',
			'update_plugins',
			self::PAGE_SLUG,
			array( self::class, 'render_page' )
		);
	}

	/**
	 * Parses a published release into a fixed update source.
	 *
	 * @param array<string, mixed> $data GitHub release response.
	 * @return array<string, string>|\WP_Error
	 */
	public static function parse_release( array $data ) {
		$tag = $data['tag_name'] ?? '';

		if ( ! is_string( $tag ) || ! preg_match( '/^v([0-9]+\.[0-9]+\.[0-9]+)$/', $tag, $matches ) ) {
			return new \WP_Error( 'invalid_release_tag', 'The GitHub release tag is not a stable semantic version.' );
		}

		if ( ! empty( $data['draft'] ) || ! empty( $data['prerelease'] ) || ! isset( $data['assets'] ) || ! is_array( $data['assets'] ) ) {
			return new \WP_Error( 'invalid_release', 'The GitHub release is not a published stable release.' );
		}

		$package_url = self::REPO_URL . '/releases/download/' . $tag . '/' . self::ASSET_NAME;

		foreach ( $data['assets'] as $asset ) {
			if ( ! is_array( $asset ) || ( $asset['name'] ?? '' ) !== self::ASSET_NAME ) {
				continue;
			}

			$digest = $asset['digest'] ?? '';
			$size   = $asset['size'] ?? 0;

			if (
				( $asset['state'] ?? '' ) !== 'uploaded'
				|| ( $asset['browser_download_url'] ?? '' ) !== $package_url
				|| ! is_string( $digest )
				|| ! preg_match( '/^sha256:([a-f0-9]{64})$/', $digest, $digest_matches )
				|| ! is_int( $size )
				|| $size < 1
				|| $size > self::MAX_ZIP_SIZE
			) {
				return new \WP_Error( 'invalid_release_asset', 'The release asset URL, size, or SHA-256 digest is invalid.' );
			}

			return array(
				'version' => $matches[1],
				'tag'     => $tag,
				'url'     => self::REPO_URL . '/releases/tag/' . $tag,
				'package' => $package_url,
				'sha256'  => $digest_matches[1],
			);
		}

		return new \WP_Error( 'missing_release_asset', 'The release does not contain the expected MU update package.' );
	}

	/**
	 * Determines whether a release is newer than the running payload.
	 *
	 * @param array<string, string> $release Validated release.
	 * @return bool
	 */
	public static function is_newer( array $release ): bool {
		return isset( $release['version'] ) && version_compare( $release['version'], Config::VERSION, '>' );
	}

	/**
	 * Displays an update notice on the Plugins screen.
	 *
	 * @return void
	 */
	public static function render_plugin_notice(): void {
		global $pagenow;

		if ( 'plugins.php' !== $pagenow || ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		$release = self::get_release();

		if ( is_array( $release ) && self::is_newer( $release ) ) {
			echo '<div class="notice notice-info"><p>We.Rock.WP.Safety.Lite ' . esc_html( $release['version'] ) . ' is available. ';
			echo '<a href="' . esc_url( self::page_url() ) . '">Review the MU plugin update</a>.</p></div>';
		}
	}

	/**
	 * Renders the manual update screen.
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( 'You are not allowed to update plugins.' );
		}

		$force = isset( $_GET['we_rock_refresh'] );
		if ( $force ) {
			check_admin_referer( 'we_rock_safety_lite_check' );
		}

		$release = self::get_release( $force );
		$result  = get_site_transient( self::RESULT_PREFIX . get_current_user_id() );

		echo '<div class="wrap"><h1>We.Rock.WP.Safety.Lite Updates</h1>';
		echo '<p>Installed MU payload version: <strong>' . esc_html( Config::VERSION ) . '</strong></p>';
		echo '<p>Updates are checked against published releases in the project GitHub repository.</p>';
		echo '<p>Installation requires an administrator click.</p>';

		if ( is_string( $result ) && '' !== $result ) {
			echo '<div class="notice notice-info"><p>' . esc_html( $result ) . '</p></div>';
			delete_site_transient( self::RESULT_PREFIX . get_current_user_id() );
		}

		if ( is_wp_error( $release ) ) {
			echo '<div class="notice notice-warning"><p>';
			echo 'Release check failed: ' . esc_html( $release->get_error_message() ) . '</p></div>';
		} elseif ( null === $release ) {
			echo '<p>No published release is available yet.</p>';
		} elseif ( self::is_newer( $release ) ) {
			echo '<p>Available version: <strong>' . esc_html( $release['version'] ) . '</strong> — ';
			echo '<a href="' . esc_url( $release['url'] ) . '">release details</a>.</p>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			echo '<input type="hidden" name="action" value="we_rock_safety_lite_update">';
			echo '<input type="hidden" name="version" value="' . esc_attr( $release['version'] ) . '">';
			wp_nonce_field( 'we_rock_safety_lite_update' );
			submit_button( 'Install MU plugin update' );
			echo '</form>';
		} else {
			echo '<p>The installed MU payload is up to date.</p>';
		}

		$refresh_url = wp_nonce_url( add_query_arg( 'we_rock_refresh', '1', self::page_url() ), 'we_rock_safety_lite_check' );
		echo '<p><a class="button" href="' . esc_url( $refresh_url ) . '">Check GitHub now</a></p>';
		echo '<p>The root MU loader, root and uploads .htaccess files, NinjaFirewall, and WP 2FA are outside this update.</p></div>';
	}

	/**
	 * Handles an authorized update request.
	 *
	 * @return void
	 */
	public static function handle_update(): void {
		if ( ! current_user_can( 'update_plugins' ) ) {
			wp_die( 'You are not allowed to update plugins.' );
		}

		check_admin_referer( 'we_rock_safety_lite_update' );
		$requested = isset( $_POST['version'] ) && is_string( $_POST['version'] )
			? sanitize_text_field( wp_unslash( $_POST['version'] ) )
			: '';
		$release   = self::get_release( true );

		if ( is_wp_error( $release ) ) {
			self::redirect_with_result( 'Update stopped: ' . $release->get_error_message() );
		}

		if ( null === $release || ! self::is_newer( $release ) || $requested !== $release['version'] ) {
			self::redirect_with_result(
				'Update stopped: the release changed or no newer version is available. Check again.'
			);
		}

		$locked = add_site_option( self::LOCK_KEY, time() );
		if ( ! $locked ) {
			$started = (int) get_site_option( self::LOCK_KEY, 0 );
			if ( $started > time() - 900 ) {
				self::redirect_with_result( 'Update stopped: another update is in progress.' );
			}

			// Replace only a stale lock; a second request must still win the add operation.
			delete_site_option( self::LOCK_KEY );
			$locked = add_site_option( self::LOCK_KEY, time() );
		}

		if ( ! $locked ) {
			self::redirect_with_result( 'Update stopped: the update lock could not be acquired.' );
		}

		try {
			$result = self::install_release( $release );
		} finally {
			delete_site_option( self::LOCK_KEY );
		}

		if ( is_wp_error( $result ) ) {
			self::redirect_with_result( 'Update stopped: ' . $result->get_error_message() );
		}

		delete_site_transient( self::CACHE_KEY );
		self::redirect_with_result(
			'MU payload updated to ' . $release['version'] . '. Recheck the site and the required security stack.'
		);
	}

	/**
	 * Downloads, verifies, and swaps the exact MU payload directory.
	 *
	 * @param array<string, string> $release Validated release data.
	 * @return true|\WP_Error
	 */
	public static function install_release( array $release ) {
		if ( ! self::is_newer( $release ) ) {
			return new \WP_Error( 'not_newer', 'The selected release is not newer than the installed payload.' );
		}

		if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
			return new \WP_Error( 'missing_mu_dir', 'The MU plugin directory is not defined.' );
		}

		if ( ! function_exists( 'WP_Filesystem' ) || ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! WP_Filesystem() ) {
			return new \WP_Error(
				'filesystem_unavailable',
				'WordPress could not access its filesystem. Use the documented manual update path.'
			);
		}

		global $wp_filesystem;
		$root = untrailingslashit( WPMU_PLUGIN_DIR );

		if ( ! $wp_filesystem->is_writable( $root ) ) {
			return new \WP_Error( 'mu_dir_not_writable', 'The MU plugin directory is not writable by WordPress.' );
		}

		$download = download_url( $release['package'], 300 );
		if ( is_wp_error( $download ) ) {
			return $download;
		}

		$stage_root = $root . '/.we-rock-wp-safety-stage-' . wp_generate_password( 16, false, false );
		$target     = $root . '/' . self::PAYLOAD_NAME;
		$backup     = $root . '/.we-rock-wp-safety-previous';

		try {
			$digest = hash_file( 'sha256', $download );
			if ( ! is_string( $digest ) || ! hash_equals( $release['sha256'], $digest ) ) {
				return new \WP_Error( 'digest_mismatch', 'The downloaded release ZIP failed SHA-256 verification.' );
			}

			if ( ! $wp_filesystem->mkdir( $stage_root ) ) {
				return new \WP_Error( 'staging_failed', 'WordPress could not create the MU update staging directory.' );
			}

			$unpacked = unzip_file( $download, $stage_root );
			if ( is_wp_error( $unpacked ) ) {
				return $unpacked;
			}

			$stage_items = $wp_filesystem->dirlist( $stage_root );
			if (
				! is_array( $stage_items )
				|| count( $stage_items ) !== 1
				|| ! isset( $stage_items[ self::PAYLOAD_NAME ] )
			) {
				return new \WP_Error( 'invalid_package_layout', 'The ZIP must contain only the expected MU payload directory.' );
			}

			$staged    = $stage_root . '/' . self::PAYLOAD_NAME;
			$validated = self::validate_payload( $wp_filesystem, $staged, $release['version'] );
			if ( is_wp_error( $validated ) ) {
				return $validated;
			}

			if ( ! $wp_filesystem->is_dir( $target ) ) {
				return new \WP_Error( 'payload_missing', 'The installed MU payload directory is missing.' );
			}

			// Retain one previous release for manual recovery after a successful swap.
			if ( $wp_filesystem->exists( $backup ) && ! $wp_filesystem->delete( $backup, true ) ) {
				return new \WP_Error( 'backup_cleanup_failed', 'The previous MU backup could not be removed.' );
			}

			if ( ! $wp_filesystem->move( $target, $backup, false ) ) {
				return new \WP_Error( 'backup_failed', 'The installed MU payload could not be moved to a backup.' );
			}

			if ( ! $wp_filesystem->move( $staged, $target, false ) ) {
				return self::restore_backup( $wp_filesystem, $target, $backup, 'The staged MU payload could not be installed.' );
			}

			$installed = self::validate_payload( $wp_filesystem, $target, $release['version'] );
			if ( is_wp_error( $installed ) ) {
				return self::restore_backup( $wp_filesystem, $target, $backup, 'The installed MU payload failed readback validation.' );
			}

			// PHP keeps the old classes in this request; invalidate file caches for the next one.
			if ( function_exists( 'opcache_invalidate' ) ) {
				opcache_invalidate( $target . '/bootstrap.php', true );
				$php_files = glob( $target . '/src/*.php' );
				foreach ( is_array( $php_files ) ? $php_files : array() as $file ) {
					opcache_invalidate( $file, true );
				}
			}

			return true;
		} finally {
			// A failed update never removes the last known good backup.
			if ( is_file( $download ) ) {
				wp_delete_file( $download );
			}
			if ( $wp_filesystem->exists( $stage_root ) ) {
				$wp_filesystem->delete( $stage_root, true );
			}
		}
	}

	/**
	 * Validates the package layout and release version without executing new code.
	 *
	 * @param object $filesystem WordPress filesystem instance.
	 * @param string $path       Payload directory.
	 * @param string $version    Expected release version.
	 * @return true|\WP_Error
	 */
	public static function validate_payload( $filesystem, string $path, string $version ) {
		$required = array(
			'bootstrap.php',
			'release.json',
			'LICENSE',
			'src/Config.php',
			'src/Plugin.php',
			'src/Updater.php',
		);

		if ( ! $filesystem->is_dir( $path ) ) {
			return new \WP_Error( 'invalid_package_layout', 'The ZIP does not contain the expected MU payload directory.' );
		}

		foreach ( $required as $file ) {
			if ( ! $filesystem->is_file( $path . '/' . $file ) ) {
				return new \WP_Error( 'invalid_package_layout', 'The ZIP is missing a required MU payload file.' );
			}
		}

		$top_files = $filesystem->dirlist( $path );
		$src_files = $filesystem->dirlist( $path . '/src' );
		if ( ! is_array( $top_files ) || ! is_array( $src_files ) ) {
			return new \WP_Error( 'invalid_package_layout', 'The MU payload directory could not be inspected.' );
		}

		foreach ( $top_files as $name => $entry ) {
			if (
				! in_array( $name, array( 'bootstrap.php', 'release.json', 'LICENSE', 'src' ), true )
				|| ! is_array( $entry )
				|| ( 'src' === $name ? 'd' !== ( $entry['type'] ?? '' ) : 'f' !== ( $entry['type'] ?? '' ) )
			) {
				return new \WP_Error( 'invalid_package_layout', 'The MU update ZIP contains an unexpected file or directory.' );
			}
		}

		foreach ( $src_files as $name => $entry ) {
			if (
				! preg_match( '/^[A-Za-z][A-Za-z0-9]*\.php$/', $name )
				|| ! is_array( $entry )
				|| 'f' !== ( $entry['type'] ?? '' )
			) {
				return new \WP_Error( 'invalid_package_layout', 'The MU source directory contains an unexpected entry.' );
			}
		}

		$manifest = json_decode( (string) $filesystem->get_contents( $path . '/release.json' ), true );
		$config   = (string) $filesystem->get_contents( $path . '/src/Config.php' );

		if (
			! is_array( $manifest )
			|| ( $manifest['version'] ?? '' ) !== $version
			|| ! preg_match( "/public const VERSION = '" . preg_quote( $version, '/' ) . "';/", $config )
		) {
			return new \WP_Error( 'version_mismatch', 'The package version does not match the GitHub release.' );
		}

		return true;
	}

	/**
	 * Restores the previous payload after a failed swap or readback.
	 *
	 * @param object $filesystem WordPress filesystem instance.
	 * @param string $target     Active payload directory.
	 * @param string $backup     Previous payload directory.
	 * @param string $reason     Failure reason.
	 * @return \WP_Error
	 */
	private static function restore_backup( $filesystem, string $target, string $backup, string $reason ): \WP_Error {
		if ( $filesystem->exists( $target ) && ! $filesystem->delete( $target, true ) ) {
			return new \WP_Error( 'restore_failed', $reason . ' Manual recovery from the previous payload is required.' );
		}

		if ( ! $filesystem->move( $backup, $target, false ) ) {
			return new \WP_Error( 'restore_failed', $reason . ' Manual recovery from the previous payload is required.' );
		}

		return new \WP_Error( 'install_failed', $reason . ' The previous payload was restored.' );
	}

	/**
	 * Retrieves and briefly caches the latest published GitHub release.
	 *
	 * @param bool $force Bypass the release cache.
	 * @return array<string, string>|\WP_Error|null
	 */
	private static function get_release( bool $force = false ) {
		if ( ! $force ) {
			$cached = get_site_transient( self::CACHE_KEY );
			if ( is_array( $cached ) && isset( $cached['state'] ) ) {
				if ( 'ready' === $cached['state'] ) {
					return $cached['release'];
				}
				if ( 'none' === $cached['state'] ) {
					return null;
				}
				if ( 'error' === $cached['state'] ) {
					return new \WP_Error( $cached['code'], $cached['message'] );
				}
			}
		}

		$response = wp_remote_get(
			self::API_URL,
			array(
				'timeout' => 8,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'WeRockWPSafetyLite',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return self::cache_error( $response );
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( 404 === $status ) {
			set_site_transient(
				self::CACHE_KEY,
				array(
					'state' => 'none',
				),
				43200
			);
			return null;
		}

		if ( 200 !== $status ) {
			return self::cache_error( new \WP_Error( 'github_unavailable', 'GitHub did not return a published release.' ) );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			return self::cache_error( new \WP_Error( 'invalid_release_response', 'GitHub returned invalid release data.' ) );
		}

		$release = self::parse_release( $data );
		if ( is_wp_error( $release ) ) {
			return self::cache_error( $release );
		}

		set_site_transient(
			self::CACHE_KEY,
			array(
				'state'   => 'ready',
				'release' => $release,
			),
			43200
		);
		return $release;
	}

	/**
	 * Caches release errors briefly to avoid repeated failing requests.
	 *
	 * @param \WP_Error $error Release error.
	 * @return \WP_Error
	 */
	private static function cache_error( \WP_Error $error ): \WP_Error {
		set_site_transient(
			self::CACHE_KEY,
			array(
				'state'   => 'error',
				'code'    => $error->get_error_code(),
				'message' => $error->get_error_message(),
			),
			300
		);

		return $error;
	}

	/**
	 * Returns the update page for the current administration context.
	 *
	 * @return string
	 */
	private static function page_url(): string {
		if ( function_exists( 'is_network_admin' ) && is_network_admin() ) {
			return network_admin_url( 'settings.php?page=' . self::PAGE_SLUG );
		}

		return admin_url( 'tools.php?page=' . self::PAGE_SLUG );
	}

	/**
	 * Redirects to the update page with a private, short-lived result.
	 *
	 * @param string $message Result message.
	 * @return void
	 */
	private static function redirect_with_result( string $message ): void {
		set_site_transient( self::RESULT_PREFIX . get_current_user_id(), $message, 120 );
		wp_safe_redirect( self::page_url() );
		exit;
	}
}
