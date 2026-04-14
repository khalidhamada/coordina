<?php
/**
 * Locale-aware formatting helpers.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Support;

final class Formatting {
	/**
	 * Format date according to site locale.
	 *
	 * @param string|null $date_string Datetime string.
	 * @return string
	 */
	public function date( ?string $date_string ): string {
		if ( empty( $date_string ) ) {
			return __( 'Not set', 'coordina' );
		}

		$timestamp = strtotime( $date_string );

		if ( false === $timestamp ) {
			return __( 'Invalid date', 'coordina' );
		}

		return wp_date( get_option( 'date_format' ), $timestamp );
	}
}