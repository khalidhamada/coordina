<?php
/**
 * Registers core foundational services.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Providers;

use Coordina\Core\Container;
use Coordina\Infrastructure\Access\AccessPolicy;
use Coordina\Infrastructure\Capabilities\CapabilityManager;
use Coordina\Infrastructure\Database\SchemaManager;
use Coordina\Infrastructure\Persistence\SettingsRepository;
use Coordina\Platform\Contracts\AccessPolicyInterface;
use Coordina\Platform\Contracts\EntitlementManagerInterface;
use Coordina\Platform\Contracts\EntitlementProviderInterface;
use Coordina\Platform\Contracts\SettingsStoreInterface;
use Coordina\Platform\Contracts\ServiceProvider;
use Coordina\Platform\Entitlements\EntitlementManager;
use Coordina\Platform\Entitlements\LocalLicenseStore;
use Coordina\Support\Formatting;

final class CoreServiceProvider implements ServiceProvider {
	/**
	 * Register foundational services.
	 *
	 * @param Container $container Service container.
	 */
	public function register( Container $container ): void {
		$container->set(
			'formatting',
			static function (): Formatting {
				return new Formatting();
			}
		);

		$container->set(
			'schema',
			static function ( Container $container ): SchemaManager {
				return new SchemaManager( $container->get( 'migration_registry' ) );
			}
		);

		$container->set(
			'capabilities',
			static function ( Container $container ): CapabilityManager {
				return new CapabilityManager( $container->get( 'capability_registry' ) );
			}
		);

		$container->set(
			'access',
			static function ( Container $container ): AccessPolicy {
				return new AccessPolicy( $container->get( 'settings' ) );
			}
		);

		$container->set(
			'settings',
			static function ( Container $container ): SettingsRepository {
				return new SettingsRepository( $container->get( 'settings_registry' ) );
			}
		);

		$container->set(
			'local_license_store',
			static function (): LocalLicenseStore {
				return new LocalLicenseStore();
			}
		);

		$container->set(
			'entitlements',
			static function ( Container $container ): EntitlementManager {
				return new EntitlementManager(
					$container->get( 'local_license_store' ),
					self::discover_entitlement_providers( $container )
				);
			}
		);

		$container->set(
			SettingsStoreInterface::class,
			static function ( Container $container ) {
				return $container->get( 'settings' );
			}
		);

		$container->set(
			AccessPolicyInterface::class,
			static function ( Container $container ) {
				return $container->get( 'access' );
			}
		);

		$container->set(
			EntitlementManagerInterface::class,
			static function ( Container $container ) {
				return $container->get( 'entitlements' );
			}
		);
	}

	/**
	 * Boot foundational services.
	 *
	 * @param Container $container Service container.
	 */
	public function boot( Container $container ): void {
		$container->get( 'capabilities' )->register();
	}

	/**
	 * @return array<int, EntitlementProviderInterface>
	 */
	private static function discover_entitlement_providers( Container $container ): array {
		/**
		 * Filters external entitlement providers registered by add-ons.
		 *
		 * Providers may be instantiated objects implementing the public
		 * entitlement provider contract, or registered container ids that
		 * resolve to those provider objects.
		 *
		 * @param array<int, mixed> $providers Provider objects or container ids.
		 * @param Container         $container Service container.
		 */
		$providers = apply_filters( 'coordina/platform/entitlement-providers', array(), $container );

		if ( ! is_array( $providers ) ) {
			return array();
		}

		$resolved = array();

		foreach ( $providers as $provider ) {
			if ( is_string( $provider ) && $container->has( $provider ) ) {
				$provider = $container->get( $provider );
			}

			if ( $provider instanceof EntitlementProviderInterface ) {
				$resolved[] = $provider;
			}
		}

		return $resolved;
	}
}
