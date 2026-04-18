<?php
/**
 * Frontend portal template.
 *
 * @var array<string, mixed> $portal_data
 *
 * @package Coordina
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="coordina-portal <?php echo $portal_data['is_rtl'] ? 'is-rtl' : 'is-ltr'; ?>">
	<header class="coordina-portal__header">
		<div>
			<p class="coordina-portal__eyebrow"><?php esc_html_e( 'Coordina portal', 'coordina' ); ?></p>
			<?php /* translators: %s: current user display name. */ ?>
			<h2><?php echo esc_html( sprintf( __( 'Welcome back, %s', 'coordina' ), $portal_data['user']->display_name ) ); ?></h2>
			<?php /* translators: %s: current date string. */ ?>
			<p><?php echo esc_html( sprintf( __( 'Today: %s', 'coordina' ), $portal_data['today'] ) ); ?></p>
		</div>
		<a class="coordina-portal__button" href="#"><?php esc_html_e( 'Submit request', 'coordina' ); ?></a>
	</header>

	<nav class="coordina-portal__nav" aria-label="<?php esc_attr_e( 'Portal sections', 'coordina' ); ?>">
		<ul>
			<?php foreach ( $portal_data['sections'] as $coordina_section ) : ?>
				<li><a href="#"><?php echo esc_html( $coordina_section ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</nav>

	<div class="coordina-portal__grid">
		<article class="coordina-portal__card">
			<h3><?php esc_html_e( 'My tasks', 'coordina' ); ?></h3>
			<p><?php esc_html_e( 'Lightweight, theme-compatible placeholders for personal execution live here.', 'coordina' ); ?></p>
		</article>
		<article class="coordina-portal__card">
			<h3><?php esc_html_e( 'Requests', 'coordina' ); ?></h3>
			<p><?php esc_html_e( 'This shell is ready for request submission, triage visibility, and approvals without sending users into wp-admin.', 'coordina' ); ?></p>
		</article>
		<article class="coordina-portal__card">
			<h3><?php esc_html_e( 'Approvals', 'coordina' ); ?></h3>
			<p><?php esc_html_e( 'Approvals remain first-class and can be expanded here with minimal JavaScript.', 'coordina' ); ?></p>
		</article>
	</div>
	<div id="coordina-portal-app" class="coordina-portal-app-root" aria-live="polite"></div>
</section>