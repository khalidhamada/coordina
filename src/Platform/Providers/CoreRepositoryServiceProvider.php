<?php
/**
 * Registers core repositories.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Providers;

use Coordina\Core\Container;
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
use Coordina\Infrastructure\Persistence\TaskRepository;
use Coordina\Infrastructure\Persistence\WorkloadRepository;
use Coordina\Platform\Contracts\ApprovalRepositoryInterface;
use Coordina\Platform\Contracts\ChecklistRepositoryInterface;
use Coordina\Platform\Contracts\NotificationRepositoryInterface;
use Coordina\Platform\Contracts\ProjectRepositoryInterface;
use Coordina\Platform\Contracts\ServiceProvider;
use Coordina\Platform\Contracts\TaskRepositoryInterface;

final class CoreRepositoryServiceProvider implements ServiceProvider {
	/**
	 * Register repository services.
	 *
	 * @param Container $container Service container.
	 */
	public function register( Container $container ): void {
		$container->set(
			'projects',
			static function ( Container $container ): ProjectRepository {
				return new ProjectRepository( $container->get( 'access' ), $container->get( TaskRepositoryInterface::class ), $container->get( 'risks_issues' ), $container->get( 'milestones' ) );
			}
		);

		$container->set(
			'activity',
			static function ( Container $container ): ActivityRepository {
				return new ActivityRepository( $container->get( 'context_types' ), $container->get( 'settings' ), $container->get( 'access' ) );
			}
		);

		$container->set(
			'tasks',
			static function ( Container $container ): TaskRepository {
				return new TaskRepository( $container->get( 'access' ), $container->get( ChecklistRepositoryInterface::class ), $container->get( ApprovalRepositoryInterface::class ), $container->get( NotificationRepositoryInterface::class ) );
			}
		);

		$container->set(
			'requests',
			static function ( Container $container ): RequestRepository {
				return new RequestRepository( $container->get( 'access' ), $container->get( ApprovalRepositoryInterface::class ) );
			}
		);

		$container->set(
			'approvals',
			static function ( Container $container ): ApprovalRepository {
				return new ApprovalRepository( $container->get( 'access' ), $container->get( NotificationRepositoryInterface::class ) );
			}
		);

		$container->set(
			'calendar',
			static function ( Container $container ): CalendarRepository {
				return new CalendarRepository( $container->get( 'access' ) );
			}
		);

		$container->set(
			'checklists',
			static function ( Container $container ): ChecklistRepository {
				return new ChecklistRepository( $container->get( 'access' ) );
			}
		);

		$container->set(
			'dashboard',
			static function ( Container $container ): DashboardRepository {
				return new DashboardRepository( $container->get( 'activity' ), $container->get( 'settings' ), $container->get( 'access' ) );
			}
		);

		$container->set(
			'risks_issues',
			static function ( Container $container ): RiskIssueRepository {
				return new RiskIssueRepository( $container->get( 'access' ) );
			}
		);

		$container->set(
			'workload',
			static function ( Container $container ): WorkloadRepository {
				return new WorkloadRepository( $container->get( 'access' ) );
			}
		);

		$container->set(
			'notifications',
			static function ( Container $container ): NotificationRepository {
				return new NotificationRepository( $container->get( 'access' ) );
			}
		);

		$container->set(
			'saved_views',
			static function ( Container $container ): SavedViewRepository {
				return new SavedViewRepository( $container->get( 'access' ) );
			}
		);

		$container->set(
			'files',
			static function ( Container $container ): FileRepository {
				return new FileRepository( $container->get( 'access' ) );
			}
		);

		$container->set(
			'milestones',
			static function ( Container $container ): MilestoneRepository {
				return new MilestoneRepository( $container->get( 'access' ) );
			}
		);

		$container->set(
			'discussions',
			static function ( Container $container ): DiscussionRepository {
				return new DiscussionRepository( $container->get( 'access' ) );
			}
		);

		$container->set(
			ProjectRepositoryInterface::class,
			static function ( Container $container ) {
				return $container->get( 'projects' );
			}
		);

		$container->set(
			TaskRepositoryInterface::class,
			static function ( Container $container ) {
				return $container->get( 'tasks' );
			}
		);

		$container->set(
			NotificationRepositoryInterface::class,
			static function ( Container $container ) {
				return $container->get( 'notifications' );
			}
		);

		$container->set(
			ApprovalRepositoryInterface::class,
			static function ( Container $container ) {
				return $container->get( 'approvals' );
			}
		);

		$container->set(
			ChecklistRepositoryInterface::class,
			static function ( Container $container ) {
				return $container->get( 'checklists' );
			}
		);
	}

	/**
	 * Boot repository services.
	 *
	 * @param Container $container Service container.
	 */
	public function boot( Container $container ): void {
	}
}
