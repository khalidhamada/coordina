<?php
/**
 * Platform kernel boot orchestrator.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform;

use Coordina\Core\Container;
use Coordina\Platform\Contracts\ServiceProvider;
use Coordina\Platform\Providers\CoreAppServiceProvider;
use Coordina\Platform\Providers\CoreRegistryServiceProvider;
use Coordina\Platform\Providers\CoreRepositoryServiceProvider;
use Coordina\Platform\Providers\CoreServiceProvider;
use Coordina\Support\DataSeedCommand;
use Coordina\Support\DataSeeder;
use InvalidArgumentException;

final class Kernel {
	/**
	 * Service container.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Extension discovery manager.
	 *
	 * @var ExtensionManager
	 */
	private $extensions;

	/**
	 * Instantiated providers.
	 *
	 * @var array<int, ServiceProvider>
	 */
	private $providers = array();

	/**
	 * Constructor.
	 *
	 * @param Container|null        $container  Optional existing container.
	 * @param ExtensionManager|null $extensions Optional extension manager.
	 */
	public function __construct( ?Container $container = null, ?ExtensionManager $extensions = null ) {
		$this->container  = $container ?: new Container();
		$this->extensions = $extensions ?: new ExtensionManager();
	}

	/**
	 * Boot Coordina through the platform kernel.
	 */
	public function boot(): void {
		$this->load_textdomain();
		$this->register_providers();
		$this->maybe_upgrade();
		$this->boot_providers();

		DataSeedCommand::register(
			function (): DataSeeder {
				return $this->container->get( 'data_seeder' );
			}
		);
	}

	/**
	 * Get the shared container.
	 *
	 * @return Container
	 */
	public function container(): Container {
		return $this->container;
	}

	/**
	 * Register all core and extension providers.
	 */
	private function register_providers(): void {
		$this->providers = array();

		foreach ( $this->provider_class_names() as $provider_class ) {
			$provider = $this->instantiate_provider( $provider_class );
			$provider->register( $this->container );
			$this->providers[] = $provider;
		}
	}

	/**
	 * Boot providers after registration.
	 */
	private function boot_providers(): void {
		foreach ( $this->providers as $provider ) {
			$provider->boot( $this->container );
		}
	}

	/**
	 * Resolve the ordered provider class list.
	 *
	 * @return array<int, string>
	 */
	private function provider_class_names(): array {
		return array_merge(
			array(
				CoreRegistryServiceProvider::class,
				CoreServiceProvider::class,
				CoreRepositoryServiceProvider::class,
				CoreAppServiceProvider::class,
			),
			$this->extensions->discover_provider_classes()
		);
	}

	/**
	 * Instantiate one provider class safely.
	 *
	 * @param string $provider_class Provider class name.
	 * @return ServiceProvider
	 */
	private function instantiate_provider( string $provider_class ): ServiceProvider {
		$provider = new $provider_class();

		if ( ! $provider instanceof ServiceProvider ) {
			throw new InvalidArgumentException(
				sprintf( 'Coordina provider "%s" must implement the service provider contract.', $provider_class )
			);
		}

		return $provider;
	}

	/**
	 * Load plugin translations.
	 */
	private function load_textdomain(): void {
		load_plugin_textdomain( 'coordina', false, dirname( COORDINA_BASENAME ) . '/languages' );
	}

	/**
	 * Run schema upgrades when needed.
	 */
	private function maybe_upgrade(): void {
		$schema            = $this->container->get( 'schema' );
		$installed_version = (string) get_option( 'coordina_version', '' );
		$db_version        = (string) get_option( 'coordina_db_version', '' );

		if ( version_compare( $installed_version, COORDINA_VERSION, '>=' ) && $schema->is_current( $db_version ) ) {
			return;
		}

		$schema->install();
		update_option( 'coordina_version', COORDINA_VERSION, false );
	}
}
