<?php
/**
 * Activation handler.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Core;

use Coordina\Infrastructure\Capabilities\CapabilityManager;
use Coordina\Infrastructure\Database\SchemaManager;

final class Activator {
	/**
	 * Run activation tasks.
	 */
	public static function activate(): void {
		( new SchemaManager() )->install();
		( new CapabilityManager() )->activate();

		update_option( 'coordina_version', COORDINA_VERSION, false );
		flush_rewrite_rules();
	}
}