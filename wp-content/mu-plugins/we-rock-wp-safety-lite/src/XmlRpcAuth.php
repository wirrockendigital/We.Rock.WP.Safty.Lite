<?php
/**
 * XML-RPC authentication hardening callbacks.
 *
 * @package WeRock\WPSafetyLite
 */

declare(strict_types=1);

namespace WeRock\WPSafetyLite;

defined( 'ABSPATH' ) || exit;

/**
 * Disables authenticated XML-RPC methods through the official WordPress filter.
 */
final class XmlRpcAuth {
	/**
	 * Disables XML-RPC methods that require authentication.
	 *
	 * This does not close the xmlrpc.php endpoint itself.
	 *
	 * @param mixed $enabled Existing availability value.
	 * @return bool
	 */
	public static function disable_xmlrpc_authentication( $enabled ): bool {
		unset( $enabled );

		return false;
	}
}
