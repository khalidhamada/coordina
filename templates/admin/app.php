<?php
/**
 * Admin shell template.
 *
 * @var array<string, mixed> $screen_data
 *
 * @package Coordina
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap coordina-admin <?php echo $screen_data['is_rtl'] ? 'is-rtl' : 'is-ltr'; ?>">
	<div class="coordina-shell">
		<h1 class="screen-reader-text"><?php echo esc_html( $screen_data['page']['title'] ); ?></h1>
		<noscript>
			<div class="coordina-card coordina-card--notice">
				<strong><?php esc_html_e( 'JavaScript is required for the Phase 1 interactive shell.', 'coordina' ); ?></strong>
				<p><?php esc_html_e( 'Core capability checks and routing still remain enforced by WordPress, but interactive lists, drawers, and forms need JavaScript enabled.', 'coordina' ); ?></p>
			</div>
		</noscript>

		<div id="coordina-admin-app" class="coordina-admin-app-root" aria-live="polite"></div>
	</div>
</div>
