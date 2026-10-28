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
 * @return array
 */
function wp_remote_get() {
	++$GLOBALS['we_rock_test_http_calls'];

	return array();
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
require_once dirname( __DIR__ ) . '/src/Plugin.php';
