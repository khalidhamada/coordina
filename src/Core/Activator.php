<?php
/**
 * Activation handler.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Core;

use Coordina\Platform\Bootstrap\ActivationBootstrap;

final class Activator {
	/**
	 * Run activation tasks.
	 */
	public static function activate(): void {
		$container = ActivationBootstrap::container();

		$container->get( 'schema' )->install();
		$container->get( 'capabilities' )->activate();

		update_option( 'coordina_version', COORDINA_VERSION, false );
		flush_rewrite_rules();
	}
}
