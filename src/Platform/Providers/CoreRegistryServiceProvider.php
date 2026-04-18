<?php
/**
 * Registers core platform registries.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Providers;

use Coordina\Core\Container;
use Coordina\Platform\Bootstrap\CoreRegistries;
use Coordina\Platform\Contracts\ContextResolverInterface;
use Coordina\Platform\Contracts\ServiceProvider;

final class CoreRegistryServiceProvider implements ServiceProvider {
	/**
	 * Register core registries.
	 *
	 * @param Container $container Service container.
	 */
	public function register( Container $container ): void {
		$container->set(
			'admin_pages',
			static function () {
				return CoreRegistries::admin_pages();
			}
		);

		$container->set(
			'rest_routes',
			static function () {
				return CoreRegistries::rest_routes();
			}
		);

		$container->set(
			'settings_registry',
			static function () {
				return CoreRegistries::settings();
			}
		);

		$container->set(
			'capability_registry',
			static function () {
				return CoreRegistries::capabilities();
			}
		);

		$container->set(
			'migration_registry',
			static function () {
				return CoreRegistries::migrations();
			}
		);

		$container->set(
			'context_types',
			static function () {
				return CoreRegistries::context_types();
			}
		);

		$container->set(
			ContextResolverInterface::class,
			static function ( Container $container ) {
				return $container->get( 'context_types' );
			}
		);
	}

	/**
	 * Boot registry services.
	 *
	 * @param Container $container Service container.
	 */
	public function boot( Container $container ): void {
	}
}
