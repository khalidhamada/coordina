<?php
/**
 * Main plugin bootstrap.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Core;

use Coordina\Admin\AdminApp;
use Coordina\Frontend\Portal;
use Coordina\Infrastructure\Access\AccessPolicy;
use Coordina\Infrastructure\Capabilities\CapabilityManager;
use Coordina\Infrastructure\Database\SchemaManager;
use Coordina\Support\DataSeedCommand;
use Coordina\Infrastructure\Persistence\ActivityRepository;
use Coordina\Infrastructure\Persistence\ApprovalRepository;
use Coordina\Infrastructure\Persistence\CalendarRepository;
use Coordina\Infrastructure\Persistence\ChecklistRepository;
use Coordina\Infrastructure\Persistence\DashboardRepository;
use Coordina\Infrastructure\Persistence\DiscussionRepository;
use Coordina\Infrastructure\Persistence\FileRepository;
use Coordina\Infrastructure\Persistence\MilestoneRepository;
use Coordina\Infrastructure\Persistence\NotificationRepository;
use Coordina\Infrastructure\Persistence\ProjectRepository;
use Coordina\Infrastructure\Persistence\RequestRepository;
use Coordina\Infrastructure\Persistence\RiskIssueRepository;
use Coordina\Infrastructure\Persistence\SavedViewRepository;
use Coordina\Infrastructure\Persistence\SettingsRepository;
use Coordina\Infrastructure\Persistence\TaskRepository;
use Coordina\Infrastructure\Persistence\WorkloadRepository;
use Coordina\Rest\RestRegistrar;
use Coordina\Support\Formatting;

final class Plugin {
	/**
	 * Service container.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->container = new Container();
		$this->register_services();
	}

	/**
	 * Boot the plugin.
	 */
	public function boot(): void {
		$this->load_textdomain();
		$this->maybe_upgrade();

		$this->container->get( 'capabilities' )->register();
		$this->container->get( 'admin' )->register();
		$this->container->get( 'frontend' )->register();
		$this->container->get( 'rest' )->register();

		// Register WP-CLI commands.
		DataSeedCommand::register();
	}

	/**
	 * Register service factories.
	 */
	private function register_services(): void {
		$this->container->set(
			'formatting',
			static function (): Formatting {
				return new Formatting();
			}
		);

		$this->container->set(
			'schema',
			static function (): SchemaManager {
				return new SchemaManager();
			}
		);

		$this->container->set(
			'capabilities',
			static function (): CapabilityManager {
				return new CapabilityManager();
			}
		);

		$this->container->set(
			'access',
			static function (): AccessPolicy {
				return new AccessPolicy();
			}
		);

		$this->container->set(
			'projects',
			static function (): ProjectRepository {
				return new ProjectRepository();
			}
		);

		$this->container->set(
			'activity',
			static function (): ActivityRepository {
				return new ActivityRepository();
			}
		);

		$this->container->set(
			'tasks',
			static function (): TaskRepository {
				return new TaskRepository();
			}
		);

		$this->container->set(
			'requests',
			static function (): RequestRepository {
				return new RequestRepository();
			}
		);

		$this->container->set(
			'approvals',
			static function (): ApprovalRepository {
				return new ApprovalRepository();
			}
		);

		$this->container->set(
			'calendar',
			static function (): CalendarRepository {
				return new CalendarRepository();
			}
		);

		$this->container->set(
			'checklists',
			static function (): ChecklistRepository {
				return new ChecklistRepository();
			}
		);

		$this->container->set(
			'dashboard',
			static function (): DashboardRepository {
				return new DashboardRepository();
			}
		);

		$this->container->set(
			'risks_issues',
			static function (): RiskIssueRepository {
				return new RiskIssueRepository();
			}
		);

		$this->container->set(
			'workload',
			static function (): WorkloadRepository {
				return new WorkloadRepository();
			}
		);

		$this->container->set(
			'notifications',
			static function (): NotificationRepository {
				return new NotificationRepository();
			}
		);

		$this->container->set(
			'saved_views',
			static function (): SavedViewRepository {
				return new SavedViewRepository();
			}
		);

		$this->container->set(
			'settings',
			static function (): SettingsRepository {
				return new SettingsRepository();
			}
		);

		$this->container->set(
			'files',
			static function (): FileRepository {
				return new FileRepository();
			}
		);

		$this->container->set(
			'milestones',
			static function (): MilestoneRepository {
				return new MilestoneRepository();
			}
		);

		$this->container->set(
			'discussions',
			static function (): DiscussionRepository {
				return new DiscussionRepository();
			}
		);

		$this->container->set(
			'admin',
			static function ( Container $container ): AdminApp {
				return new AdminApp( $container->get( 'formatting' ), $container->get( 'settings' ), $container->get( 'access' ) );
			}
		);

		$this->container->set(
			'frontend',
			static function ( Container $container ): Portal {
				return new Portal( $container->get( 'formatting' ) );
			}
		);

		$this->container->set(
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
					$container->get( 'access' )
				);
			}
		);
	}

	/**
	 * Load textdomain.
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
