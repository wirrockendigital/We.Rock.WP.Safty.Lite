<?php
/**
 * PHPUnit bootstrap with minimal WordPress test doubles.
 *
 * @package WeRock\WPSafetyLite\Tests
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() . '/we-rock-wp-safety-lite-test-root/' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', sys_get_temp_dir() . '/we-rock-wp-safety-lite-test-content' );
}

if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
	define( 'WPMU_PLUGIN_DIR', sys_get_temp_dir() . '/we-rock-wp-safety-lite-test-mu-' . getmypid() );
}

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
$GLOBALS['we_rock_test_download_source'] = null;
$GLOBALS['we_rock_test_mu_plugins']     = array();
$GLOBALS['we_rock_test_rest_status']    = 200;
$GLOBALS['wp_query']                   = new We_Rock_Test_Query();

/**
 * Captures filter registration.
 *
 * @param string $hook          Hook name.
 * @param mixed  $callback      Callback.
 * @param int    $priority      Hook priority.
 * @param int    $accepted_args Accepted argument count.
 * @return true
 */
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['we_rock_test_hooks']['filter'][ $hook ][] = array(
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);

	return true;
}

/**
 * Captures action registration.
 *
 * @param string $hook          Hook name.
 * @param mixed  $callback      Callback.
 * @param int    $priority      Hook priority.
 * @param int    $accepted_args Accepted argument count.
 * @return true
 */
function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['we_rock_test_hooks']['action'][ $hook ][] = array(
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);

	return true;
}

/**
 * Applies simple test filters.
 *
 * @param string $hook  Hook name.
 * @param mixed  $value Filter value.
 * @return mixed
 */
function apply_filters( $hook, $value ) {
	if ( isset( $GLOBALS['we_rock_test_hooks']['filter'][ $hook ] ) ) {
		foreach ( $GLOBALS['we_rock_test_hooks']['filter'][ $hook ] as $hook_data ) {
			$value = call_user_func( $hook_data['callback'], $value );
		}
	}

	return $value;
}

/**
 * Minimal WordPress error detector.
 *
 * @param mixed $value Value to inspect.
 * @phpstan-assert-if-true WP_Error $value
 * @return bool
 */
function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}

/**
 * Minimal WordPress tag stripper.
 *
 * @param string $text Text to strip.
 * @return string
 */
function wp_strip_all_tags( $text ) {
	return strip_tags( (string) $text );
}

/**
 * Minimal WordPress unslash helper.
 *
 * @param mixed $value Value to unslash.
 * @return mixed
 */
function wp_unslash( $value ) {
	if ( is_array( $value ) ) {
		return array_map( 'wp_unslash', $value );
	}

	return is_string( $value ) ? stripslashes( $value ) : $value;
}

/**
 * Minimal translation and escaping helper.
 *
 * @param string $text   Text to translate.
 * @param string $domain Text domain.
 * @return string
 */
function esc_html__( $text, $domain = 'default' ) {
	unset( $domain );

	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

/**
 * Minimal escaping helper.
 *
 * @param string $text Text to escape.
 * @return string
 */
function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

/**
 * Escapes a test URL.
 *
 * @param string $url URL to escape.
 * @return string
 */
function esc_url( $url ) {
	return htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
}

/**
 * Escapes a test attribute.
 *
 * @param string $value Attribute value.
 * @return string
 */
function esc_attr( $value ) {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}

/**
 * Normalizes a test key.
 *
 * @param string $key Raw key.
 * @return string
 */
function sanitize_key( $key ) {
	return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', $key ) );
}

/**
 * Sanitizes a test text field.
 *
 * @param string $text Raw text.
 * @return string
 */
function sanitize_text_field( $text ) {
	return trim( strip_tags( $text ) );
}

/**
 * Registers a test Tools page.
 *
 * @return void
 */
function add_management_page( ...$args ) {
	unset( $args );
}

/**
 * Registers a test network page.
 *
 * @return void
 */
function add_submenu_page( ...$args ) {
	unset( $args );
}

/**
 * Stops a test request.
 *
 * @param string $message Error message.
 * @return never
 */
function wp_die( $message ) {
	throw new RuntimeException( $message );
}

/**
 * Accepts a test administrator nonce.
 *
 * @return bool
 */
function check_admin_referer( $action = '' ) {
	unset( $action );
	return true;
}

/**
 * Returns the test administrator ID.
 *
 * @return int
 */
function get_current_user_id() {
	return 1;
}

/**
 * Returns a test admin URL.
 *
 * @param string $path Admin path.
 * @return string
 */
function admin_url( $path = '' ) {
	return 'https://example.test/wp-admin/' . $path;
}

/**
 * Returns a test network admin URL.
 *
 * @param string $path Admin path.
 * @return string
 */
function network_admin_url( $path = '' ) {
	return 'https://example.test/wp-admin/network/' . $path;
}

/**
 * Reports a normal site-admin context.
 *
 * @return bool
 */
function is_network_admin() {
	return false;
}

/**
 * Ignores a test nonce field.
 *
 * @return void
 */
function wp_nonce_field( $action = '' ) {
	unset( $action );
}

/**
 * Ignores a test submit button.
 *
 * @return void
 */
function submit_button( $text = '' ) {
	unset( $text );
}

/**
 * Adds a test query argument.
 *
 * @param string $key   Query key.
 * @param string $value Query value.
 * @param string $url   Base URL.
 * @return string
 */
function add_query_arg( $key, $value, $url ) {
	return $url . '&' . $key . '=' . $value;
}

/**
 * Returns a nonce-protected test URL.
 *
 * @param string $url URL.
 * @return string
 */
function wp_nonce_url( $url, $action = '' ) {
	unset( $action );
	return $url;
}

/**
 * Accepts a test safe redirect.
 *
 * @return bool
 */
function wp_safe_redirect( $url = '' ) {
	unset( $url );
	return true;
}

/**
 * Adds a test site option only once.
 *
 * @param string $key   Option key.
 * @param mixed  $value Option value.
 * @return bool
 */
function add_site_option( $key, $value ) {
	if ( array_key_exists( $key, $GLOBALS['we_rock_test_options'] ) ) {
		return false;
	}
	$GLOBALS['we_rock_test_options'][ $key ] = $value;
	return true;
}

/**
 * Deletes a test site option.
 *
 * @param string $key Option key.
 * @return bool
 */
function delete_site_option( $key ) {
	unset( $GLOBALS['we_rock_test_options'][ $key ] );
	return true;
}

/**
 * Captures status header usage.
 *
 * @param int $code HTTP status code.
 * @return void
 */
function status_header( $code ) {
	$GLOBALS['we_rock_test_status_header'] = (int) $code;
}

/**
 * Captures nocache header usage.
 *
 * @return void
 */
function nocache_headers() {
	$GLOBALS['we_rock_test_nocache'] = true;
}

/**
 * Provides the configured login state.
 *
 * @return bool
 */
function is_user_logged_in() {
	return (bool) $GLOBALS['we_rock_test_logged_in'];
}

/**
 * Provides the configured capability state.
 *
 * @param string $capability Capability name.
 * @return bool
 */
function current_user_can( $capability ) {
	return ! empty( $GLOBALS['we_rock_test_caps'][ $capability ] );
}

/**
 * Reports an admin request in tests.
 *
 * @return bool
 */
function is_admin() {
	return true;
}

/**
 * Reports non-Ajax requests in tests.
 *
 * @return bool
 */
function wp_doing_ajax() {
	return false;
}

/**
 * Reports non-cron requests in tests.
 *
 * @return bool
 */
function wp_doing_cron() {
	return false;
}

/**
 * Returns configured option values.
 *
 * @param string $name    Option name.
 * @param mixed  $default Default value.
 * @return mixed
 */
function get_option( $name, $default = false ) {
	return array_key_exists( $name, $GLOBALS['we_rock_test_options'] ) ? $GLOBALS['we_rock_test_options'][ $name ] : $default;
}

/**
 * Returns configured site option values.
 *
 * @param string $name    Site option name.
 * @param mixed  $default Default value.
 * @return mixed
 */
function get_site_option( $name, $default = false ) {
	return array_key_exists( $name, $GLOBALS['we_rock_test_options'] ) ? $GLOBALS['we_rock_test_options'][ $name ] : $default;
}

/**
 * Returns configured MU plugin metadata.
 *
 * @return array
 */
function get_mu_plugins() {
	return $GLOBALS['we_rock_test_mu_plugins'];
}

/**
 * Returns configured Application Password availability.
 *
 * @return bool
 */
function wp_is_application_passwords_available() {
	return empty( $GLOBALS['we_rock_test_application_passwords_disabled'] );
}

/**
 * Parses URLs through PHP's native parser.
 *
 * @param string $url       URL.
 * @param int    $component Component constant.
 * @return mixed
 */
function wp_parse_url( $url, $component = -1 ) {
	if ( -1 === $component ) {
		return parse_url( $url );
	}

	return parse_url( $url, $component );
}

/**
 * Returns the configured environment type.
 *
 * @return string
 */
function wp_get_environment_type() {
	return (string) $GLOBALS['we_rock_test_environment'];
}

/**
 * Captures outbound GET attempts.
 *
 * @param string $url  Request URL.
 * @param array  $args Request options.
 * @return array|WP_Error
 */
function wp_remote_get( $url = '', $args = array() ) {
	unset( $url, $args );
	++$GLOBALS['we_rock_test_http_calls'];

	return $GLOBALS['we_rock_test_http_response'];
}

/**
 * Returns the fake HTTP response status.
 *
 * @param array $response HTTP response.
 * @return int
 */
function wp_remote_retrieve_response_code( $response ) {
	return (int) ( $response['response']['code'] ?? 0 );
}

/**
 * Returns the fake HTTP response body.
 *
 * @param array $response HTTP response.
 * @return string
 */
function wp_remote_retrieve_body( $response ) {
	return (string) ( $response['body'] ?? '' );
}

/**
 * Retrieves a test site transient.
 *
 * @param string $key Transient key.
 * @return mixed
 */
function get_site_transient( $key ) {
	return $GLOBALS['we_rock_test_transients'][ $key ] ?? false;
}

/**
 * Stores a test site transient without expiry handling.
 *
 * @param string $key        Transient key.
 * @param mixed  $value      Transient value.
 * @param int    $expiration Expiration seconds.
 * @return bool
 */
function set_site_transient( $key, $value, $expiration = 0 ) {
	unset( $expiration );
	$GLOBALS['we_rock_test_transients'][ $key ] = $value;
	return true;
}

/**
 * Deletes a test site transient.
 *
 * @param string $key Transient key.
 * @return bool
 */
function delete_site_transient( $key ) {
	unset( $GLOBALS['we_rock_test_transients'][ $key ] );
	return true;
}

/**
 * Initializes the local filesystem test double.
 *
 * @return bool
 */
function WP_Filesystem() {
	global $wp_filesystem;
	return $wp_filesystem instanceof We_Rock_Test_Filesystem;
}

/**
 * Copies a prepared test package into a temporary download.
 *
 * @param string $url     Package URL.
 * @param int    $timeout Download timeout.
 * @return string|WP_Error
 */
function download_url( $url, $timeout = 300 ) {
	unset( $url, $timeout );
	$source = $GLOBALS['we_rock_test_download_source'];
	if ( ! is_string( $source ) || ! is_file( $source ) ) {
		return new WP_Error( 'missing_test_package', 'No test package was prepared.' );
	}

	$target = tempnam( sys_get_temp_dir(), 'wrsl-' );
	if ( false === $target || ! copy( $source, $target ) ) {
		return new WP_Error( 'test_download_failed', 'Could not copy the test package.' );
	}

	return $target;
}

/**
 * Deletes a temporary test download.
 *
 * @param string $file Temporary file path.
 * @return void
 */
function wp_delete_file( $file ) {
	unlink( $file );
}

/**
 * Extracts a ZIP through the local test filesystem.
 *
 * @param string $file        ZIP path.
 * @param string $destination Extraction directory.
 * @return true|WP_Error
 */
function unzip_file( $file, $destination ) {
	$zip = new ZipArchive();
	if ( true !== $zip->open( $file ) ) {
		return new WP_Error( 'invalid_test_zip', 'Could not open the test ZIP.' );
	}

	$result = $zip->extractTo( $destination );
	$zip->close();
	return $result ? true : new WP_Error( 'test_unzip_failed', 'Could not extract the test ZIP.' );
}

/**
 * Supplies a random staging suffix.
 *
 * @return string
 */
function wp_generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {
	unset( $length, $special_chars, $extra_special_chars );
	return bin2hex( random_bytes( 8 ) );
}

/**
 * Removes a trailing directory separator.
 *
 * @param string $path Directory path.
 * @return string
 */
function untrailingslashit( $path ) {
	return rtrim( $path, '/\\' );
}

/**
 * Captures outbound POST attempts.
 *
 * @return array
 */
function wp_remote_post() {
	++$GLOBALS['we_rock_test_http_calls'];

	return array();
}

/**
 * Captures generic outbound HTTP attempts.
 *
 * @return array
 */
function wp_remote_request() {
	++$GLOBALS['we_rock_test_http_calls'];

	return array();
}

/**
 * Executes a minimal REST request.
 *
 * @param WP_REST_Request $request REST request.
 * @return We_Rock_Test_REST_Response
 */
function rest_do_request( $request ) {
	unset( $request );

	return new We_Rock_Test_REST_Response( (int) $GLOBALS['we_rock_test_rest_status'] );
}

/**
 * Captures unexpected scheduled events.
 *
 * @return false
 */
function wp_schedule_event() {
	$GLOBALS['we_rock_test_hooks']['cron'][] = func_get_args();

	return false;
}

/**
 * Minimal WP_Error test double.
 */
class WP_Error {
	/**
	 * Error code.
	 *
	 * @var string
	 */
	public $code;

	/**
	 * Error message.
	 *
	 * @var string
	 */
	public $message;

	/**
	 * Error data.
	 *
	 * @var mixed
	 */
	public $data;

	/**
	 * Stores error data.
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @param mixed  $data    Error data.
	 */
	public function __construct( $code = '', $message = '', $data = null ) {
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
	}

	/**
	 * Returns stored error data.
	 *
	 * @return mixed
	 */
	public function get_error_data() {
		return $this->data;
	}

	/**
	 * Returns the error code.
	 *
	 * @return string
	 */
	public function get_error_code() {
		return $this->code;
	}

	/**
	 * Returns the error message.
	 *
	 * @return string
	 */
	public function get_error_message() {
		return $this->message;
	}
}

/**
 * Implements the filesystem operations used by the MU updater.
 */
class We_Rock_Test_Filesystem {
	/**
	 * Fail one staged payload move for rollback testing.
	 *
	 * @var bool
	 */
	public $fail_staged_move = false;

	/**
	 * Reports writable local directories.
	 *
	 * @param string $path Directory path.
	 * @return bool
	 */
	public function is_writable( $path ) {
		return is_writable( $path );
	}

	/**
	 * Creates a directory.
	 *
	 * @param string $path Directory path.
	 * @return bool
	 */
	public function mkdir( $path ) {
		return mkdir( $path, 0700, true );
	}

	/**
	 * Checks a directory.
	 *
	 * @param string $path Directory path.
	 * @return bool
	 */
	public function is_dir( $path ) {
		return is_dir( $path );
	}

	/**
	 * Checks a file.
	 *
	 * @param string $path File path.
	 * @return bool
	 */
	public function is_file( $path ) {
		return is_file( $path );
	}

	/**
	 * Reads a file.
	 *
	 * @param string $path File path.
	 * @return string|false
	 */
	public function get_contents( $path ) {
		return file_get_contents( $path );
	}

	/**
	 * Lists immediate directory entries in WordPress filesystem format.
	 *
	 * @param string $path Directory path.
	 * @return array|false
	 */
	public function dirlist( $path ) {
		if ( ! is_dir( $path ) ) {
			return false;
		}
		$entries = array();
		foreach ( new DirectoryIterator( $path ) as $item ) {
			if ( $item->isDot() ) {
				continue;
			}
			$entries[ $item->getFilename() ] = array(
				'name' => $item->getFilename(),
				'type' => $item->isDir() ? 'd' : 'f',
			);
		}
		return $entries;
	}

	/**
	 * Checks a path.
	 *
	 * @param string $path Path to check.
	 * @return bool
	 */
	public function exists( $path ) {
		return file_exists( $path );
	}

	/**
	 * Deletes a file or directory.
	 *
	 * @param string $path      Path to delete.
	 * @param bool   $recursive Delete children.
	 * @return bool
	 */
	public function delete( $path, $recursive = false ) {
		if ( ! file_exists( $path ) ) {
			return true;
		}
		if ( is_file( $path ) || is_link( $path ) ) {
			return unlink( $path );
		}
		if ( ! $recursive ) {
			return rmdir( $path );
		}
		foreach ( new DirectoryIterator( $path ) as $item ) {
			if ( $item->isDot() ) {
				continue;
			}
			if ( ! $this->delete( $item->getPathname(), true ) ) {
				return false;
			}
		}
		return rmdir( $path );
	}

	/**
	 * Moves a directory unless a test requests a staged-move failure.
	 *
	 * @param string $source      Source path.
	 * @param string $destination Destination path.
	 * @param bool   $overwrite   Whether to replace a destination.
	 * @return bool
	 */
	public function move( $source, $destination, $overwrite = false ) {
		if ( $this->fail_staged_move && false !== strpos( $source, '.we-rock-wp-safety-stage-' ) ) {
			$this->fail_staged_move = false;
			return false;
		}
		if ( ! $overwrite && file_exists( $destination ) ) {
			return false;
		}
		return rename( $source, $destination );
	}
}

/**
 * Minimal REST request test double.
 */
class We_Rock_Test_REST_Request {
	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	private $namespace;

	/**
	 * REST route.
	 *
	 * @var string
	 */
	private $route;

	/**
	 * Stores route metadata.
	 *
	 * @param string $namespace REST namespace.
	 * @param string $route     REST route.
	 */
	public function __construct( string $namespace, string $route ) {
		$this->namespace = $namespace;
		$this->route     = $route;
	}

	/**
	 * Returns the namespace.
	 *
	 * @return string
	 */
	public function get_namespace(): string {
		return $this->namespace;
	}

	/**
	 * Returns the route.
	 *
	 * @return string
	 */
	public function get_route(): string {
		return $this->route;
	}
}

/**
 * Minimal WP_REST_Request test double.
 */
class WP_REST_Request extends We_Rock_Test_REST_Request {
	/**
	 * Stores route metadata.
	 *
	 * @param string $method REST method.
	 * @param string $route  REST route.
	 */
	public function __construct( string $method, string $route ) {
		unset( $method );

		parent::__construct( 'wp/v2', $route );
	}
}

/**
 * Minimal REST response test double.
 */
class We_Rock_Test_REST_Response {
	/**
	 * HTTP status.
	 *
	 * @var int
	 */
	private $status;

	/**
	 * Stores the response status.
	 *
	 * @param int $status HTTP status.
	 */
	public function __construct( int $status ) {
		$this->status = $status;
	}

	/**
	 * Returns the response status.
	 *
	 * @return int
	 */
	public function get_status(): int {
		return $this->status;
	}
}

/**
 * Minimal main query test double.
 */
class We_Rock_Test_Query {
	/**
	 * Whether the request was marked as 404.
	 *
	 * @var bool
	 */
	public $is_404 = false;

	/**
	 * Marks the request as 404.
	 *
	 * @return void
	 */
	public function set_404(): void {
		$this->is_404 = true;
	}
}

require_once dirname( __DIR__ ) . '/src/Config.php';
require_once dirname( __DIR__ ) . '/src/LoginHardening.php';
require_once dirname( __DIR__ ) . '/src/AuthorEnumeration.php';
require_once dirname( __DIR__ ) . '/src/RestUsers.php';
require_once dirname( __DIR__ ) . '/src/Pingbacks.php';
require_once dirname( __DIR__ ) . '/src/XmlRpcAuth.php';
require_once dirname( __DIR__ ) . '/src/ApplicationPasswords.php';
require_once dirname( __DIR__ ) . '/src/SiteHealth.php';
require_once dirname( __DIR__ ) . '/src/AdminNotices.php';
require_once dirname( __DIR__ ) . '/src/Updater.php';
require_once dirname( __DIR__ ) . '/src/Plugin.php';
