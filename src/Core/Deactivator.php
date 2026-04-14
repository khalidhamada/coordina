<?php
/**
 * Deactivation handler.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Core;

final class Deactivator {
	/**
	 * Run deactivation tasks.
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}