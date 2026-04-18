<?php
/**
 * Frontend portal shell.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Frontend;

use Coordina\Infrastructure\Persistence\SettingsRepository;
use Coordina\Platform\Contracts\SettingsStoreInterface;
use Coordina\Support\Formatting;

final class Portal {
	/**
	 * Formatting helper.
	 *
	 * @var Formatting
	 */
	private $formatting;

	/**
	 * Shared settings repository.
	 *
	 * @var SettingsStoreInterface
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Formatting                   $formatting Formatting helper.
	 * @param SettingsStoreInterface|null $settings Shared settings repository.
	 */
	public function __construct( Formatting $formatting, ?SettingsStoreInterface $settings = null ) {
		$this->formatting = $formatting;
		$this->settings   = $settings ?: new SettingsRepository();
	}

	/**
	 * Register frontend hooks.
	 */
	public function register(): void {
		add_shortcode( 'coordina_portal', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render shortcode output.
	 *
	 * @param array<string, string> $attributes Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( array $attributes = array() ): string {
		unset( $attributes );

		if ( ! is_user_logged_in() ) {
			return '<div class="coordina-portal-notice">' . esc_html__( 'Please sign in to access the Coordina portal.', 'coordina' ) . '</div>';
		}

		$settings    = $this->settings->get();
		$portal_mode = (string) ( $settings['access']['portal_access_default'] ?? 'requesters' );

		if ( 'disabled' === $portal_mode ) {
			return '<div class="coordina-portal-notice">' . esc_html__( 'The Coordina portal is currently disabled.', 'coordina' ) . '</div>';
		}

		$can_access = 'logged-in-users' === $portal_mode
			|| current_user_can( 'coordina_access_portal' )
			|| current_user_can( 'coordina_access' );

		if ( ! $can_access ) {
			return '<div class="coordina-portal-notice">' . esc_html__( 'You do not have permission to access the Coordina portal.', 'coordina' ) . '</div>';
		}

		wp_enqueue_style( 'coordina-frontend', COORDINA_URL . 'assets/frontend/style.css', array(), (string) filemtime( COORDINA_PATH . 'assets/frontend/style.css' ) );
		wp_enqueue_script( 'coordina-frontend', COORDINA_URL . 'assets/frontend/index.js', array(), (string) filemtime( COORDINA_PATH . 'assets/frontend/index.js' ), true );

		$template = COORDINA_PATH . 'templates/frontend/portal.php';

		if ( ! file_exists( $template ) ) {
			return '';
		}

		$portal_data = array(
			'user'      => wp_get_current_user(),
			'is_rtl'    => is_rtl(),
			'today'     => $this->formatting->date( current_time( 'mysql' ) ),
			'sections'  => array(
				__( 'My Tasks', 'coordina' ),
				__( 'My Projects', 'coordina' ),
				__( 'Requests', 'coordina' ),
				__( 'Approvals', 'coordina' ),
				__( 'Notifications', 'coordina' ),
				__( 'Profile & Preferences', 'coordina' ),
			),
		);

		ob_start();
		include $template;
		return (string) ob_get_clean();
	}
}
