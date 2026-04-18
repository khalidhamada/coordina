<?php
/**
 * Minimal platform bootstrap for activation-time services.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Bootstrap;

use Coordina\Core\Container;
use Coordina\Platform\Providers\CoreRegistryServiceProvider;
use Coordina\Platform\Providers\CoreServiceProvider;

final class ActivationBootstrap {
	/**
	 * Build a container with the activation-time providers.
	 *
	 * @return Container
	 */
	public static function container(): Container {
		$container = new Container();
		$providers = array(
			new CoreRegistryServiceProvider(),
			new CoreServiceProvider(),
		);

		foreach ( $providers as $provider ) {
			$provider->register( $container );
		}

		return $container;
	}
}
