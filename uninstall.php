<?php
/**
 * Coordina uninstall routine.
 *
 * @package Coordina
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'coordina_version' );
delete_option( 'coordina_db_version' );
