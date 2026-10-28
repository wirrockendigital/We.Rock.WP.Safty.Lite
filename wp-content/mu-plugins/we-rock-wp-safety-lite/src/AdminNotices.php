<?php
/**
 * Sparse administrator notices for critical configuration states.
 *
 * @package WeRock\WPSafetyLite
 */

declare(strict_types=1);

namespace WeRock\WPSafetyLite;

defined( 'ABSPATH' ) || exit;

/**
 * Shows only critical read-only warnings to administrators.
 */
final class AdminNotices {
	/**
	 * Renders critical notices without storing dismissal state.
	 *
	 * @return void
	 */
	public static function render_critical_notices(): void {
		if ( ! self::can_show_notice() ) {
			return;
		}

		$messages = self::critical_messages();

		if ( array() === $messages ) {
			return;
		}

		foreach ( $messages as $message ) {
			printf(
				'<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>',
				esc_html( 'We.Rock Safety Lite:' ),
				esc_html( $message )
			);
		}
	}

	/**
	 * Builds administrator-facing messages for critical states only.
	 *
	 * @return array<int, string>
	 */
	private static function critical_messages(): array {
		$messages = array();

		if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
			$messages[] = self::esc_html__( 'WP_DEBUG ist in der Produktionsumgebung aktiv.' );
		}

		if ( defined( 'WP_DEBUG_DISPLAY' ) && true === WP_DEBUG_DISPLAY ) {
			$messages[] = self::esc_html__( 'WP_DEBUG_DISPLAY gibt Fehler direkt aus und sollte in Produktion deaktiviert sein.' );
		}

		return $messages;
	}

	/**
	 * Checks whether the current request may show administrative security details.
	 *
	 * @return bool
	 */
	private static function can_show_notice(): bool {
		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			return false;
		}

		if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
			return false;
		}

		if ( function_exists( 'current_user_can' ) && ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		return function_exists( 'is_admin' ) && is_admin();
	}

	/**
	 * Escapes translated text with a safe fallback for test contexts.
	 *
	 * @param string $text Text to translate.
	 * @return string
	 */
	private static function esc_html__( string $text ): string {
		if ( function_exists( 'esc_html__' ) ) {
			return esc_html__( $text, 'we-rock-wp-safety-lite' );
		}

		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}
