<?php
/**
 * Platform service provider contract.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Contracts;

use Coordina\Core\Container;

interface ServiceProvider {
	/**
	 * Register services into the container.
	 *
	 * @param Container $container Service container.
	 */
	public function register( Container $container ): void;

	/**
	 * Boot services after all providers have registered.
	 *
	 * @param Container $container Service container.
	 */
	public function boot( Container $container ): void;
}
