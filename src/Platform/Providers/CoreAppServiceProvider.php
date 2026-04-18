<?php
/**
 * Registers application entry services.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Providers;

use Coordina\Admin\AdminApp;
use Coordina\Core\Container;
use Coordina\Frontend\Portal;
use Coordina\Platform\Contracts\AccessPolicyInterface;
use Coordina\Platform\Contracts\ApprovalRepositoryInterface;
use Coordina\Platform\Contracts\EntitlementManagerInterface;
use Coordina\Platform\Contracts\ProjectRepositoryInterface;
use Coordina\Platform\Contracts\ServiceProvider;
use Coordina\Platform\Contracts\TaskRepositoryInterface;
use Coordina\Rest\RestRegistrar;
use Coordina\Support\DataSeeder;

final class CoreAppServiceProvider implements ServiceProvider {
	/**
	 * Register app entry services.
	 *
	 * @param Container $container Service container.
	 */
	public function register( Container $container ): void {
		$container->set(
			'admin',
			static function ( Container $container ): AdminApp {
				return new AdminApp( $container->get( 'formatting' ), $container->get( 'settings' ), $container->get( 'access' ), $container->get( 'admin_pages' ), $container->get( 'context_types' ) );
			}
		);

		$container->set(
			'frontend',
			static function ( Container $container ): Portal {
				return new Portal( $container->get( 'formatting' ), $container->get( 'settings' ) );
			}
		);

		$container->set(
			'data_seeder',
			static function ( Container $container ): DataSeeder {
				return new DataSeeder(
					$container->get( ProjectRepositoryInterface::class ),
					$container->get( TaskRepositoryInterface::class ),
					$container->get( 'milestones' ),
					$container->get( 'risks_issues' ),
					$container->get( ApprovalRepositoryInterface::class ),
					$container->get( 'discussions' ),
					$container->get( 'activity' ),
					$container->get( 'files' ),
					$container->get( AccessPolicyInterface::class )
				);
			}
		);

		$container->set(
			'rest',
			static function ( Container $container ): RestRegistrar {
				return new RestRegistrar(
					$container->get( 'formatting' ),
					$container->get( 'activity' ),
					$container->get( 'projects' ),
					$container->get( 'tasks' ),
					$container->get( 'requests' ),
					$container->get( 'approvals' ),
					$container->get( 'calendar' ),
					$container->get( 'dashboard' ),
					$container->get( 'checklists' ),
					$container->get( 'risks_issues' ),
					$container->get( 'workload' ),
					$container->get( 'notifications' ),
					$container->get( 'saved_views' ),
					$container->get( 'settings' ),
					$container->get( 'files' ),
					$container->get( 'milestones' ),
					$container->get( 'discussions' ),
					$container->get( 'access' ),
					$container->get( EntitlementManagerInterface::class ),
					$container->get( 'data_seeder' ),
					$container->get( 'rest_routes' ),
					$container->get( 'context_types' )
				);
			}
		);
	}

	/**
	 * Boot app entry services.
	 *
	 * @param Container $container Service container.
	 */
	public function boot( Container $container ): void {
		$container->get( 'admin' )->register();
		$container->get( 'frontend' )->register();
		$container->get( 'rest' )->register();
	}
}
