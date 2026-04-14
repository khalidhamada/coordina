<?php
/**
 * REST bootstrap.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Rest;

use Coordina\Infrastructure\Access\AccessPolicy;
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
use Coordina\Support\DataSeeder;
use Coordina\Support\Formatting;
use Throwable;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class RestRegistrar {
	private const NAMESPACE = 'coordina/v1';

	private $formatting;
	private $activity;
	private $projects;
	private $tasks;
	private $requests;
	private $approvals;
	private $calendar;
	private $dashboard;
	private $checklists;
	private $risks_issues;
	private $workload;
	private $notifications;
	private $saved_views;
	private $settings;
	private $files;
	private $milestones;
	private $discussions;
	private $access;

	public function __construct(
		Formatting $formatting,
		ActivityRepository $activity,
		ProjectRepository $projects,
		TaskRepository $tasks,
		RequestRepository $requests,
		ApprovalRepository $approvals,
		CalendarRepository $calendar,
		DashboardRepository $dashboard,
		ChecklistRepository $checklists,
		RiskIssueRepository $risks_issues,
		WorkloadRepository $workload,
		NotificationRepository $notifications,
		SavedViewRepository $saved_views,
		SettingsRepository $settings,
		FileRepository $files,
		MilestoneRepository $milestones,
		DiscussionRepository $discussions,
		AccessPolicy $access
	) {
		$this->formatting    = $formatting;
		$this->activity      = $activity;
		$this->projects      = $projects;
		$this->tasks         = $tasks;
		$this->requests      = $requests;
		$this->approvals     = $approvals;
		$this->calendar      = $calendar;
		$this->dashboard     = $dashboard;
		$this->checklists    = $checklists;
		$this->risks_issues  = $risks_issues;
		$this->workload      = $workload;
		$this->notifications = $notifications;
		$this->saved_views   = $saved_views;
		$this->settings      = $settings;
		$this->files         = $files;
		$this->milestones    = $milestones;
		$this->discussions   = $discussions;
		$this->access        = $access;
	}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_status' ),
				'permission_callback' => array( $this, 'can_access_any_shell' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/admin-shell',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_admin_shell' ),
				'permission_callback' => array( $this, 'can_access_admin' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/calendar',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_calendar' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'coordina_access' );
				},
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/dashboard',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_dashboard' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'coordina_access' );
				},
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/activity',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_activity' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'coordina_access' );
				},
			)
		);

		$this->register_collection_routes( 'projects', 'coordina_manage_projects', array( $this->projects, 'get_items' ), array( $this->projects, 'create' ), array( $this->projects, 'update' ), array( $this->projects, 'find' ), array( $this->projects, 'bulk_update_status' ), array( $this->projects, 'delete' ), 'coordina_access' );
		$this->register_collection_routes( 'tasks', 'coordina_manage_tasks', array( $this->tasks, 'get_items' ), array( $this->tasks, 'create' ), array( $this->tasks, 'update' ), array( $this->tasks, 'find' ), array( $this->tasks, 'bulk_update_status' ), array( $this->tasks, 'delete' ), 'coordina_access', 'coordina_access', 'coordina_access' );
		$this->register_collection_routes( 'requests', 'coordina_access', array( $this->requests, 'get_items' ), array( $this->requests, 'create' ), array( $this->requests, 'update' ), array( $this->requests, 'find' ), array( $this->requests, 'bulk_update_status' ), array( $this->requests, 'delete' ), 'coordina_access', 'coordina_access', 'coordina_access' );
		$this->register_collection_routes( 'approvals', 'coordina_access', array( $this->approvals, 'get_items' ), array( $this->approvals, 'create' ), array( $this->approvals, 'update' ), array( $this->approvals, 'find' ), array( $this->approvals, 'bulk_update_status' ), null, 'coordina_access', 'coordina_access', 'coordina_access' );
		$this->register_collection_routes( 'risks-issues', 'coordina_manage_projects', array( $this->risks_issues, 'get_items' ), array( $this->risks_issues, 'create' ), array( $this->risks_issues, 'update' ), array( $this->risks_issues, 'find' ), array( $this->risks_issues, 'bulk_update_status' ), array( $this->risks_issues, 'delete' ) );
		$this->register_collection_routes( 'milestones', 'coordina_manage_projects', array( $this->milestones, 'get_items' ), array( $this->milestones, 'create' ), array( $this->milestones, 'update' ), array( $this->milestones, 'find' ), array( $this->milestones, 'bulk_update_status' ), array( $this->milestones, 'delete' ), 'coordina_access' );

		register_rest_route(
			self::NAMESPACE,
			'/checklists',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_checklists' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_checklist' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/checklists/(?P<id>\\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_checklist' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_checklist' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_checklist' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/checklists/(?P<id>\\d+)/move',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'move_checklist' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'coordina_access' );
				},
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/checklist-items',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_checklist_item' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/checklist-items/(?P<id>\\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_checklist_item' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_checklist_item' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_checklist_item' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/checklist-items/(?P<id>\\d+)/toggle',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'toggle_checklist_item' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'coordina_access' );
				},
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/checklist-items/(?P<id>\\d+)/move',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'move_checklist_item' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'coordina_access' );
				},
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/files',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_files' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_file' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/files/(?P<id>\\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_file' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_file' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/discussions',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_discussions' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_discussion' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/discussions/(?P<id>\\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_discussion' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_discussion' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/projects/(?P<id>\\d+)/workspace',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_project_workspace' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'coordina_access' );
				},
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/projects/(?P<id>\\d+)/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_project_settings' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_project_settings' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_manage_projects' ) || current_user_can( 'coordina_manage_settings' );
					},
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/projects/(?P<id>\\d+)/task-groups',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_project_task_groups' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_project_task_group' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_manage_projects' ) || current_user_can( 'coordina_manage_settings' );
					},
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/requests/(?P<id>\\d+)/convert',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'convert_request' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'coordina_access' );
				},
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/my-work',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_my_work' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'coordina_access' );
				},
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/workload',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_workload' ),
				'permission_callback' => static function (): bool {
					return current_user_can( 'coordina_manage_projects' );
				},
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/notifications',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_notifications' ),
				'permission_callback' => array( $this, 'can_access_any_shell' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/notifications/(?P<id>\\d+)',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_notification' ),
				'permission_callback' => array( $this, 'can_access_any_shell' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/notification-preferences',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_notification_preferences' ),
				'permission_callback' => array( $this, 'can_access_any_shell' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_manage_settings' );
					},
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_manage_settings' );
					},
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/saved-views',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_saved_views' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_saved_view' ),
					'permission_callback' => static function (): bool {
						return current_user_can( 'coordina_access' );
					},
				),
			)
		);
	}

	private function register_collection_routes( string $base, string $capability, callable $list_callback, callable $create_callback, callable $update_callback, callable $find_callback, callable $bulk_callback, ?callable $delete_callback = null, string $read_capability = '', string $update_capability = '', string $bulk_capability = '' ): void {
		$read_capability = '' !== $read_capability ? $read_capability : $capability;
		$update_capability = '' !== $update_capability ? $update_capability : $capability;
		$bulk_capability = '' !== $bulk_capability ? $bulk_capability : $update_capability;

		register_rest_route(
			self::NAMESPACE,
			'/' . $base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => function ( WP_REST_Request $request ) use ( $list_callback ): WP_REST_Response {
						return $this->respond( call_user_func( $list_callback, $request->get_params() ) );
					},
					'permission_callback' => static function () use ( $read_capability ): bool {
						return current_user_can( $read_capability );
					},
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => function ( WP_REST_Request $request ) use ( $create_callback ): WP_REST_Response {
						$payload = $this->sanitize_payload( $request );
						if ( ! in_array( $request->get_route(), array( '/' . self::NAMESPACE . '/approvals' ), true ) ) {
							$error = $this->validate_required_title( $payload );
							if ( $error instanceof WP_REST_Response ) {
								return $error;
							}
						}
						try {
							return $this->respond( call_user_func( $create_callback, $payload ) );
						} catch ( Throwable $exception ) {
							return $this->error_response( $exception->getMessage() );
						}
					},
					'permission_callback' => static function () use ( $update_capability ): bool {
						return current_user_can( $update_capability );
					},
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/' . $base . '/(?P<id>\\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => function ( WP_REST_Request $request ) use ( $find_callback ): WP_REST_Response {
						return $this->respond( call_user_func( $find_callback, (int) $request['id'] ) );
					},
					'permission_callback' => static function () use ( $read_capability ): bool {
						return current_user_can( $read_capability );
					},
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => function ( WP_REST_Request $request ) use ( $update_callback, $base ): WP_REST_Response {
						$payload = $this->sanitize_payload( $request );
						$requires_title = 'approvals' !== $base && ( 'tasks' !== $base || array_key_exists( 'title', $payload ) );
						if ( $requires_title ) {
							$error = $this->validate_required_title( $payload );
							if ( $error instanceof WP_REST_Response ) {
								return $error;
							}
						}
						try {
							return $this->respond( call_user_func( $update_callback, (int) $request['id'], $payload ) );
						} catch ( Throwable $exception ) {
							return $this->error_response( $exception->getMessage() );
						}
					},
					'permission_callback' => static function () use ( $update_capability ): bool {
						return current_user_can( $update_capability );
					},
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => function ( WP_REST_Request $request ) use ( $delete_callback ): WP_REST_Response {
						if ( null === $delete_callback ) {
							return $this->error_response( __( 'Delete is not supported for this record.', 'coordina' ), 405 );
						}

						try {
							return $this->respond( array( 'deleted' => (bool) call_user_func( $delete_callback, (int) $request['id'] ) ) );
						} catch ( Throwable $exception ) {
							return $this->error_response( $exception->getMessage() );
						}
					},
					'permission_callback' => static function () use ( $update_capability ): bool {
						return current_user_can( $update_capability );
					},
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/' . $base . '/bulk',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => function ( WP_REST_Request $request ) use ( $bulk_callback ): WP_REST_Response {
					$ids    = array_map( 'intval', (array) $request->get_param( 'ids' ) );
					$status = sanitize_key( (string) $request->get_param( 'status' ) );
					try {
						return $this->respond( array( 'updated' => call_user_func( $bulk_callback, $ids, $status ) ) );
					} catch ( Throwable $exception ) {
						return $this->error_response( $exception->getMessage() );
					}
				},
				'permission_callback' => static function () use ( $bulk_capability ): bool {
					return current_user_can( $bulk_capability );
				},
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/demo-data/seed',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'seed_demo_data' ),
				'permission_callback' => array( $this, 'can_manage_settings' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/demo-data/clear',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'clear_demo_data' ),
				'permission_callback' => array( $this, 'can_manage_settings' ),
			)
		);
	}

	public function get_status( WP_REST_Request $request ): WP_REST_Response {
		unset( $request );

		return $this->respond(
			array(
				'plugin'      => 'coordina',
				'version'     => COORDINA_VERSION,
				'rtl'         => is_rtl(),
				'locale'      => determine_locale(),
				'currentDate' => $this->formatting->date( current_time( 'mysql' ) ),
			)
		);
	}

	public function get_admin_shell( WP_REST_Request $request ): WP_REST_Response {
		unset( $request );

		$current_user = wp_get_current_user();
		$users        = $this->get_assignable_users();
		$projects     = $this->projects->get_items(
			array(
				'page'     => 1,
				'per_page' => 100,
				'orderby'  => 'title',
				'order'    => 'asc',
			)
		);
		$settings     = $this->settings->get();
		$dropdowns    = $settings['dropdowns'];

		return $this->respond(
			array(
				'user'         => array(
					'id'           => $current_user->ID,
					'display_name' => $current_user->display_name,
				),
				'capabilities' => array(
					'canManageProjects' => current_user_can( 'coordina_manage_projects' ),
					'canManageTasks'    => current_user_can( 'coordina_manage_tasks' ),
					'canManageRequests' => current_user_can( 'coordina_manage_requests' ),
					'canManageSettings' => current_user_can( 'coordina_manage_settings' ),
				),
				'statuses'     => $dropdowns['statuses'],
				'priorities'   => $dropdowns['priorities'],
				'health'       => $dropdowns['health'],
				'visibilityLevels' => $dropdowns['visibilityLevels'],
				'projectNotificationPolicies' => $dropdowns['projectNotificationPolicies'],
				'requestTypes' => $dropdowns['requestTypes'],
				'projectTypes' => $dropdowns['projectTypes'],
				'fileCategories' => $dropdowns['fileCategories'],
				'updateTypes' => $dropdowns['updateTypes'],
				'taskGroupLabel' => $settings['general']['task_group_label'] ?? 'stage',
				'activityPageSize' => (int) ( $settings['general']['activity_page_size'] ?? 10 ),
				'objectTypes'  => array( 'risk', 'issue' ),
				'approvalObjectTypes' => array( 'project', 'task', 'request', 'risk', 'issue', 'milestone' ),
				'contextObjectTypes'  => array( 'project', 'task', 'request', 'risk', 'issue', 'milestone', 'approval' ),
				'severities'   => $dropdowns['severities'],
				'impacts'      => $dropdowns['impacts'],
				'likelihoods'  => $dropdowns['likelihoods'],
				'users'        => array_map(
					static function ( $user ): array {
						return array(
							'id'    => (int) $user->ID,
							'label' => $user->display_name,
						);
					},
					$users
				),
				'projects'     => array_map(
					static function ( array $project ): array {
						return array(
							'id'    => (int) $project['id'],
							'label' => (string) $project['title'],
						);
					},
					$projects['items'] ?? array()
				),
				'editableProjects' => array_map(
					static function ( array $project ): array {
						return array(
							'id'    => (int) $project['id'],
							'label' => (string) $project['title'],
						);
					},
					array_values(
						array_filter(
							$projects['items'] ?? array(),
							function ( array $project ): bool {
								return $this->access->can_edit_project( (int) ( $project['id'] ?? 0 ) );
							}
						)
					)
				),
			)
		);
	}

	public function get_calendar( WP_REST_Request $request ): WP_REST_Response {
		return $this->respond( $this->calendar->get_for_current_user( $request->get_params() ) );
	}

	public function get_dashboard( WP_REST_Request $request ): WP_REST_Response {
		return $this->respond( $this->dashboard->get_for_current_user( $request->get_params() ) );
	}

	public function get_activity( WP_REST_Request $request ): WP_REST_Response {
		return $this->respond( $this->activity->get_items( $request->get_params() ) );
	}

	public function get_files( WP_REST_Request $request ): WP_REST_Response {
		return $this->respond( $this->files->get_items( $request->get_params() ) );
	}

	public function get_file( WP_REST_Request $request ): WP_REST_Response {
		return $this->respond( $this->files->find( (int) $request['id'] ) );
	}

	public function get_checklists( WP_REST_Request $request ): WP_REST_Response {
		return $this->respond( $this->checklists->get_items( $request->get_params() ) );
	}

	public function get_checklist( WP_REST_Request $request ): WP_REST_Response {
		return $this->respond( $this->checklists->find( (int) $request['id'] ) );
	}

	public function create_checklist( WP_REST_Request $request ): WP_REST_Response {
		try {
			return $this->respond( $this->checklists->create( $this->sanitize_payload( $request ) ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage(), 400 );
		}
	}

	public function update_checklist( WP_REST_Request $request ): WP_REST_Response {
		try {
			return $this->respond( $this->checklists->update( (int) $request['id'], $this->sanitize_payload( $request ) ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage(), 400 );
		}
	}

	public function move_checklist( WP_REST_Request $request ): WP_REST_Response {
		$direction = sanitize_key( (string) $request->get_param( 'direction' ) );

		if ( ! in_array( $direction, array( 'up', 'down' ), true ) ) {
			return $this->error_response( __( 'A valid checklist move direction is required.', 'coordina' ), 400 );
		}

		try {
			return $this->respond( $this->checklists->move( (int) $request['id'], $direction ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage(), 400 );
		}
	}

	public function delete_checklist( WP_REST_Request $request ): WP_REST_Response {
		try {
			return $this->respond( array( 'deleted' => $this->checklists->delete( (int) $request['id'] ) ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage(), 400 );
		}
	}

	public function get_checklist_item( WP_REST_Request $request ): WP_REST_Response {
		return $this->respond( $this->checklists->find_item( (int) $request['id'] ) );
	}

	public function create_checklist_item( WP_REST_Request $request ): WP_REST_Response {
		try {
			return $this->respond( $this->checklists->create_item( $this->sanitize_payload( $request ) ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage(), 400 );
		}
	}

	public function update_checklist_item( WP_REST_Request $request ): WP_REST_Response {
		try {
			return $this->respond( $this->checklists->update_item( (int) $request['id'], $this->sanitize_payload( $request ) ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage(), 400 );
		}
	}

	public function toggle_checklist_item( WP_REST_Request $request ): WP_REST_Response {
		try {
			return $this->respond( $this->checklists->toggle_item( (int) $request['id'], rest_sanitize_boolean( $request->get_param( 'is_done' ) ) ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage(), 400 );
		}
	}

	public function move_checklist_item( WP_REST_Request $request ): WP_REST_Response {
		$direction = sanitize_key( (string) $request->get_param( 'direction' ) );

		if ( ! in_array( $direction, array( 'up', 'down' ), true ) ) {
			return $this->error_response( __( 'A valid checklist move direction is required.', 'coordina' ), 400 );
		}

		try {
			return $this->respond( $this->checklists->move_item( (int) $request['id'], $direction ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage(), 400 );
		}
	}

	public function delete_checklist_item( WP_REST_Request $request ): WP_REST_Response {
		try {
			return $this->respond( array( 'deleted' => $this->checklists->delete_item( (int) $request['id'] ) ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage(), 400 );
		}
	}

	public function delete_file( WP_REST_Request $request ): WP_REST_Response {
		try {
			return $this->respond( array( 'deleted' => $this->files->delete( (int) $request['id'] ) ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage() );
		}
	}

	public function create_file( WP_REST_Request $request ): WP_REST_Response {
		try {
			return $this->respond( $this->files->create( $this->sanitize_payload( $request ) ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage() );
		}
	}

	public function get_discussions( WP_REST_Request $request ): WP_REST_Response {
		return $this->respond( $this->discussions->get_items( $request->get_params() ) );
	}

	public function get_discussion( WP_REST_Request $request ): WP_REST_Response {
		return $this->respond( $this->discussions->find( (int) $request['id'] ) );
	}

	public function delete_discussion( WP_REST_Request $request ): WP_REST_Response {
		try {
			return $this->respond( array( 'deleted' => $this->discussions->delete( (int) $request['id'] ) ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage() );
		}
	}

	public function create_discussion( WP_REST_Request $request ): WP_REST_Response {
		try {
			return $this->respond( $this->discussions->create( $this->sanitize_payload( $request ) ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage() );
		}
	}

	public function get_project_workspace( WP_REST_Request $request ): WP_REST_Response {
		$project_id = (int) $request['id'];
		$tab        = $this->normalize_workspace_tab( sanitize_key( (string) $request->get_param( 'tab' ) ) );
		$settings   = $this->settings->get();
		$activity_per_page = max( 5, min( 50, (int) ( $settings['general']['activity_page_size'] ?? 10 ) ) );
		$activity_page = max( 1, (int) $request->get_param( 'activity_page' ) );

		if ( ! $this->access->can_view_project( $project_id ) ) {
			return $this->error_response( __( 'You are not allowed to access this project workspace.', 'coordina' ), 403 );
		}

		$workspace  = $this->projects->get_workspace_summary( $project_id );

		if ( empty( $workspace ) ) {
			return $this->error_response( __( 'Project could not be found.', 'coordina' ), 404 );
		}

		$task_collection      = $this->tasks->get_for_project(
			$project_id,
			array(
				'page'     => 1,
				'per_page' => 'work' === $tab ? 25 : 8,
				'orderby'  => 'due_date',
				'order'    => 'asc',
			)
		);
		$task_summary         = $this->tasks->get_project_summary( $project_id );
		$task_groups          = $this->tasks->get_groups_for_project( $project_id );
		$approval_collection  = $this->approvals->get_for_project(
			$project_id,
			array(
				'page'     => 1,
				'per_page' => 'approvals' === $tab ? 25 : 8,
			)
		);
		$approval_summary     = $this->approvals->get_project_summary( $project_id );
		$risk_collection      = $this->risks_issues->get_for_project(
			$project_id,
			array(
				'page'     => 1,
				'per_page' => 'risks-issues' === $tab ? 25 : 8,
				'orderby'  => 'target_resolution_date',
				'order'    => 'asc',
			)
		);
		$risk_summary         = $this->risks_issues->get_project_summary( $project_id );
		$milestone_collection = $this->milestones->get_for_project(
			$project_id,
			array(
				'page'     => 1,
				'per_page' => 'milestones' === $tab ? 25 : 8,
				'orderby'  => 'due_date',
				'order'    => 'asc',
			)
		);
		$milestone_summary    = $this->milestones->get_project_summary( $project_id );
		$activity_collection  = $this->activity->get_for_project(
			$project_id,
			array(
				'page'     => 'activity' === $tab ? $activity_page : 1,
				'per_page' => 'activity' === $tab ? $activity_per_page : min( 8, $activity_per_page ),
			)
		);
		$activity_summary     = $this->activity->get_project_summary( $project_id );
		$file_collection      = $this->files->get_for_project(
			$project_id,
			array(
				'page'     => 1,
				'per_page' => 'files' === $tab ? 25 : 8,
			)
		);
		$file_summary         = $this->files->get_project_summary( $project_id );
		$discussion_collection = $this->discussions->get_for_project(
			$project_id,
			array(
				'page'     => 1,
				'per_page' => 'discussion' === $tab ? 25 : 8,
			)
		);
		$discussion_summary   = $this->discussions->get_project_summary( $project_id );
		$project_checklist    = $this->checklists->get_items(
			array(
				'object_type' => 'project',
				'object_id'   => $project_id,
			)
		);
		$gantt_data           = array();

		if ( 'gantt' === $tab ) {
			$gantt_data = $this->build_workspace_gantt_data(
				$workspace['project'],
				$this->collect_project_items(
					array( $this->tasks, 'get_for_project' ),
					$project_id,
					array(
						'orderby' => 'due_date',
						'order'   => 'asc',
					)
				),
				$task_groups,
				$this->collect_project_items(
					array( $this->milestones, 'get_for_project' ),
					$project_id,
					array(
						'orderby' => 'due_date',
						'order'   => 'asc',
					)
				)
			);
		}

		$project_overview     = 'overview' === $tab ? array(
			'tasks'      => $this->collect_project_items(
				array( $this->tasks, 'get_for_project' ),
				$project_id,
				array(
					'orderby' => 'due_date',
					'order'   => 'asc',
				)
			),
			'milestones' => $this->collect_project_items(
				array( $this->milestones, 'get_for_project' ),
				$project_id,
				array(
					'orderby' => 'due_date',
					'order'   => 'asc',
				)
			),
			'risksIssues' => $this->collect_project_items(
				array( $this->risks_issues, 'get_for_project' ),
				$project_id,
				array(
					'orderby' => 'target_resolution_date',
					'order'   => 'asc',
				)
			),
			'approvals' => $this->collect_project_items(
				array( $this->approvals, 'get_for_project' ),
				$project_id
			),
			'files'      => $this->collect_project_items(
				array( $this->files, 'get_for_project' ),
				$project_id
			),
			'updates'    => $this->collect_project_items(
				array( $this->discussions, 'get_for_project' ),
				$project_id
			),
		) : array();
		if ( 'settings' === $tab && ! $this->access->can_edit_project( $project_id ) ) {
			$tab = 'overview';
		}
		$project_settings     = $this->access->can_edit_project( $project_id ) ? $this->projects->get_settings( $project_id ) : array();
		$task_group_label     = ! empty( $workspace['project']['task_group_label'] ) ? (string) $workspace['project']['task_group_label'] : (string) ( $settings['general']['task_group_label'] ?? 'stage' );
		$tabs                 = array(
			array( 'key' => 'overview', 'label' => __( 'Overview', 'coordina' ) ),
			array( 'key' => 'work', 'label' => __( 'Work', 'coordina' ), 'count' => (int) ( $task_summary['total'] ?? 0 ) ),
			array( 'key' => 'gantt', 'label' => __( 'Gantt', 'coordina' ) ),
			array( 'key' => 'milestones', 'label' => __( 'Milestones', 'coordina' ), 'count' => (int) ( $milestone_summary['open'] ?? 0 ) ),
			array( 'key' => 'risks-issues', 'label' => __( 'Risks & Issues', 'coordina' ), 'count' => (int) ( $risk_summary['total'] ?? 0 ) ),
			array( 'key' => 'approvals', 'label' => __( 'Approvals', 'coordina' ), 'count' => (int) ( $approval_summary['pending'] ?? 0 ) ),
			array( 'key' => 'discussion', 'label' => __( 'Updates', 'coordina' ), 'count' => (int) ( $discussion_summary['total'] ?? 0 ) ),
			array( 'key' => 'files', 'label' => __( 'Files', 'coordina' ), 'count' => (int) ( $file_summary['total'] ?? 0 ) ),
			array( 'key' => 'activity', 'label' => __( 'Activity', 'coordina' ), 'count' => (int) ( $activity_summary['total'] ?? 0 ) ),
		);

		if ( $this->access->can_edit_project( $project_id ) ) {
			$tabs[] = array( 'key' => 'settings', 'label' => __( 'Settings', 'coordina' ) );
		}

		return $this->respond(
			array(
				'project'              => $workspace['project'],
				'overview'             => array(
					'metrics'       => $workspace['metrics'],
					'healthSummary' => $workspace['healthSummary'],
					'timeline'      => array(
						'start'  => $workspace['project']['start_date'] ?? '',
						'target' => $workspace['project']['target_end_date'] ?? '',
						'end'    => $workspace['project']['actual_end_date'] ?? '',
					),
				),
				'taskSummary'          => $task_summary,
				'taskCollection'       => $task_collection,
				'taskGroups'           => $task_groups,
				'taskGroupLabel'       => $task_group_label,
				'approvalSummary'      => $approval_summary,
				'approvalCollection'   => $approval_collection,
				'riskIssueSummary'     => $risk_summary,
				'riskIssueCollection'  => $risk_collection,
				'milestoneSummary'     => $milestone_summary,
				'milestoneCollection'  => $milestone_collection,
				'ganttData'            => $gantt_data,
				'fileSummary'          => $file_summary,
				'fileCollection'       => $file_collection,
				'discussionSummary'    => $discussion_summary,
				'discussionCollection' => $discussion_collection,
				'projectChecklist'     => $project_checklist,
				'activitySummary'      => $activity_summary,
				'activityCollection'   => $activity_collection,
				'projectOverview'      => $project_overview,
				'projectSettings'      => $project_settings,
				'tabs'                 => $tabs,
				'activeTab'            => $tab,
				'actions'              => $this->get_project_workspace_actions( $project_id ),
			)
		);
	}

	private function get_project_workspace_actions( int $project_id ): array {
		$can_edit_project = $this->access->can_edit_project( $project_id );
		$can_post_update  = $this->access->can_post_update_on_context( 'project', $project_id );
		$can_attach_file  = $this->access->can_attach_files_to_context( 'project', $project_id );

		return array(
			'canEditProject'      => $can_edit_project,
			'canCreateTask'       => current_user_can( 'coordina_manage_tasks' ) && $can_edit_project,
			'canCreateTaskGroup'  => current_user_can( 'coordina_manage_projects' ) && $this->access->can_edit_project( $project_id ),
			'canCreateRiskIssue'  => current_user_can( 'coordina_manage_projects' ) && $can_edit_project,
			'canCreateMilestone'  => current_user_can( 'coordina_manage_projects' ) && $this->access->can_edit_project( $project_id ),
			'canPostUpdate'       => $can_post_update,
			'canAttachFile'       => $can_attach_file,
			'canViewSettings'     => $can_edit_project,
		);
	}

	private function collect_project_items( callable $callback, int $project_id, array $args = array() ): array {
		$page        = 1;
		$total_pages = 1;
		$items       = array();

		do {
			$collection = call_user_func(
				$callback,
				$project_id,
				array_merge(
					$args,
					array(
						'page'     => $page,
						'per_page' => 50,
					)
				)
			);

			$items       = array_merge( $items, $collection['items'] ?? array() );
			$total_pages = max( 1, (int) ( $collection['totalPages'] ?? 1 ) );
			++$page;
		} while ( $page <= $total_pages );

		return $items;
	}

	/**
	 * Build Gantt data for a workspace tab.
	 *
	 * @param array<string, mixed>             $project Project record.
	 * @param array<int, array<string, mixed>> $tasks Project tasks.
	 * @param array<int, array<string, mixed>> $task_groups Project task groups.
	 * @param array<int, array<string, mixed>> $milestones Project milestones.
	 * @return array<string, mixed>
	 */
	private function build_workspace_gantt_data( array $project, array $tasks, array $task_groups, array $milestones ): array {
		$today_key         = current_datetime()->format( 'Y-m-d' );
		$scheduled_rows    = array();
		$unscheduled_tasks = array();
		$milestone_rows    = array();
		$task_group_rows   = array();
		$ungrouped_rows    = array();
		$range_dates       = array_filter(
			array(
				$this->normalize_workspace_date_key( (string) ( $project['start_date'] ?? '' ) ),
				$this->normalize_workspace_date_key( (string) ( $project['target_end_date'] ?? '' ) ),
				$this->normalize_workspace_date_key( (string) ( $project['actual_end_date'] ?? '' ) ),
			)
		);

		foreach ( $task_groups as $group ) {
			$task_group_rows[ (int) ( $group['id'] ?? 0 ) ] = array(
				'id'    => 'group-' . (int) ( $group['id'] ?? 0 ),
				'title' => (string) ( $group['title'] ?? __( 'Task group', 'coordina' ) ),
				'rows'  => array(),
			);
		}

		foreach ( $tasks as $task ) {
			$start_key  = $this->normalize_workspace_date_key( (string) ( $task['start_date'] ?? '' ) );
			$due_key    = $this->normalize_workspace_date_key( (string) ( $task['due_date'] ?? '' ) );
			$is_done    = in_array( (string) ( $task['status'] ?? '' ), array( 'done', 'cancelled' ), true );
			$is_overdue = ! $is_done && '' !== $due_key && $due_key < $today_key;
			$row        = array(
				'id'             => 'task-' . (int) ( $task['id'] ?? 0 ),
				'recordId'       => (int) ( $task['id'] ?? 0 ),
				'type'           => 'task',
				'title'          => (string) ( $task['title'] ?? __( 'Task', 'coordina' ) ),
				'status'         => (string) ( $task['status'] ?? 'new' ),
				'startDate'      => $start_key,
				'endDate'        => '' !== $due_key ? $due_key : $start_key,
				'dueDate'        => $due_key,
				'ownerLabel'     => (string) ( $task['assignee_label'] ?? '' ),
				'groupId'        => (int) ( $task['task_group_id'] ?? 0 ),
				'groupLabel'     => (string) ( $task['task_group_label'] ?? '' ),
				'completion'     => 'done' === ( $task['status'] ?? '' ) ? 100 : 0,
				'blocked'        => ! empty( $task['blocked'] ),
				'isDone'         => $is_done,
				'isOverdue'      => $is_overdue,
				'checklistTotal' => (int) ( $task['checklist_summary']['total'] ?? 0 ),
				'checklistDone'  => (int) ( $task['checklist_summary']['done'] ?? 0 ),
			);

			if ( '' === $row['startDate'] && '' === $row['endDate'] ) {
				$unscheduled_tasks[] = $row;
				continue;
			}

			if ( '' === $row['startDate'] ) {
				$row['startDate'] = $row['endDate'];
			}

			if ( '' === $row['endDate'] ) {
				$row['endDate'] = $row['startDate'];
			}

			if ( $row['endDate'] < $row['startDate'] ) {
				$row['endDate'] = $row['startDate'];
			}

			$scheduled_rows[] = $row;
			$range_dates[]    = $row['startDate'];
			$range_dates[]    = $row['endDate'];

			$group_id = (int) $row['groupId'];
			if ( $group_id > 0 && isset( $task_group_rows[ $group_id ] ) ) {
				$task_group_rows[ $group_id ]['rows'][] = $row;
			} else {
				$ungrouped_rows[] = $row;
			}
		}

		foreach ( $milestones as $milestone ) {
			$due_key = $this->normalize_workspace_date_key( (string) ( $milestone['due_date'] ?? '' ) );
			if ( '' === $due_key ) {
				continue;
			}

			$is_done = in_array( (string) ( $milestone['status'] ?? '' ), array( 'completed', 'skipped' ), true );
			$row     = array(
				'id'             => 'milestone-' . (int) ( $milestone['id'] ?? 0 ),
				'recordId'       => (int) ( $milestone['id'] ?? 0 ),
				'type'           => 'milestone',
				'title'          => (string) ( $milestone['title'] ?? __( 'Milestone', 'coordina' ) ),
				'status'         => (string) ( $milestone['status'] ?? 'planned' ),
				'startDate'      => $due_key,
				'endDate'        => $due_key,
				'dueDate'        => $due_key,
				'ownerLabel'     => (string) ( $milestone['owner_label'] ?? '' ),
				'groupId'        => -1,
				'groupLabel'     => __( 'Milestones', 'coordina' ),
				'completion'     => (int) ( $milestone['completion_percent'] ?? 0 ),
				'blocked'        => false,
				'isDone'         => $is_done,
				'isOverdue'      => ! $is_done && $due_key < $today_key,
				'dependencyFlag' => ! empty( $milestone['dependency_flag'] ),
			);

			$milestone_rows[] = $row;
			$scheduled_rows[] = $row;
			$range_dates[]    = $due_key;
		}

		sort( $range_dates );

		$range_start = ! empty( $range_dates ) ? $this->week_start_key( $this->shift_date_key( (string) reset( $range_dates ), -7 ) ) : $this->week_start_key( $today_key );
		$range_end   = ! empty( $range_dates ) ? $this->week_end_key( $this->shift_date_key( (string) end( $range_dates ), 7 ) ) : $this->week_end_key( $today_key );
		$weeks       = array();
		$cursor      = strtotime( $range_start . ' 00:00:00 UTC' );
		$end_ts      = strtotime( $range_end . ' 00:00:00 UTC' );

		while ( false !== $cursor && false !== $end_ts && $cursor <= $end_ts ) {
			$weeks[] = array(
				'start'      => gmdate( 'Y-m-d', $cursor ),
				'end'        => gmdate( 'Y-m-d', strtotime( '+6 days', $cursor ) ),
				'label'      => wp_date( 'M j', $cursor ),
				'monthLabel' => wp_date( 'M Y', $cursor ),
			);
			$cursor = strtotime( '+7 days', $cursor );
		}

		$groups = array();

		if ( ! empty( $milestone_rows ) ) {
			$groups[] = array(
				'id'    => 'milestones',
				'title' => __( 'Milestones', 'coordina' ),
				'rows'  => $milestone_rows,
			);
		}

		foreach ( $task_group_rows as $group ) {
			if ( ! empty( $group['rows'] ) ) {
				$groups[] = $group;
			}
		}

		if ( ! empty( $ungrouped_rows ) ) {
			$groups[] = array(
				'id'    => 'ungrouped',
				'title' => __( 'Ungrouped work', 'coordina' ),
				'rows'  => $ungrouped_rows,
			);
		}

		return array(
			'range'            => array(
				'start' => $range_start,
				'end'   => $range_end,
				'today' => $today_key,
			),
			'weeks'            => $weeks,
			'projectFrame'     => array(
				'start'  => $this->normalize_workspace_date_key( (string) ( $project['start_date'] ?? '' ) ),
				'target' => $this->normalize_workspace_date_key( (string) ( $project['target_end_date'] ?? '' ) ),
				'end'    => $this->normalize_workspace_date_key( (string) ( $project['actual_end_date'] ?? '' ) ),
			),
			'summary'          => array(
				'scheduledTasks'   => count(
					array_filter(
						$scheduled_rows,
						static function ( array $row ): bool {
							return 'task' === ( $row['type'] ?? '' );
						}
					)
				),
				'unscheduledTasks' => count( $unscheduled_tasks ),
				'milestones'       => count( $milestone_rows ),
				'blockedTasks'     => count(
					array_filter(
						$scheduled_rows,
						static function ( array $row ): bool {
							return 'task' === ( $row['type'] ?? '' ) && ! empty( $row['blocked'] );
						}
					)
				),
				'overdueItems'     => count(
					array_filter(
						$scheduled_rows,
						static function ( array $row ): bool {
							return ! empty( $row['isOverdue'] );
						}
					)
				),
				'dependencyItems'  => count(
					array_filter(
						$milestone_rows,
						static function ( array $row ): bool {
							return ! empty( $row['dependencyFlag'] );
						}
					)
				),
			),
			'groups'           => $groups,
			'unscheduledTasks' => $unscheduled_tasks,
		);
	}

	/**
	 * Normalize a workspace date value to Y-m-d.
	 *
	 * @param string $value Raw date value.
	 * @return string
	 */
	private function normalize_workspace_date_key( string $value ): string {
		if ( '' === $value ) {
			return '';
		}

		return substr( $value, 0, 10 );
	}

	/**
	 * Shift a Y-m-d key by a number of days.
	 *
	 * @param string $date_key Base date key.
	 * @param int    $days Number of days.
	 * @return string
	 */
	private function shift_date_key( string $date_key, int $days ): string {
		$timestamp = strtotime( $date_key . ' 00:00:00 UTC' );

		if ( false === $timestamp ) {
			return current_datetime()->format( 'Y-m-d' );
		}

		return gmdate( 'Y-m-d', strtotime( ( $days >= 0 ? '+' : '' ) . $days . ' days', $timestamp ) );
	}

	/**
	 * Get the week start key (Monday) for a date key.
	 *
	 * @param string $date_key Date key.
	 * @return string
	 */
	private function week_start_key( string $date_key ): string {
		$timestamp = strtotime( $date_key . ' 00:00:00 UTC' );

		if ( false === $timestamp ) {
			return current_datetime()->format( 'Y-m-d' );
		}

		$day_of_week = (int) gmdate( 'N', $timestamp );
		return gmdate( 'Y-m-d', strtotime( '-' . ( $day_of_week - 1 ) . ' days', $timestamp ) );
	}

	/**
	 * Get the week end key (Sunday) for a date key.
	 *
	 * @param string $date_key Date key.
	 * @return string
	 */
	private function week_end_key( string $date_key ): string {
		$timestamp = strtotime( $date_key . ' 00:00:00 UTC' );

		if ( false === $timestamp ) {
			return current_datetime()->format( 'Y-m-d' );
		}

		$day_of_week = (int) gmdate( 'N', $timestamp );
		return gmdate( 'Y-m-d', strtotime( '+' . ( 7 - $day_of_week ) . ' days', $timestamp ) );
	}

	private function normalize_workspace_tab( string $tab ): string {
		if ( in_array( $tab, array( 'board', 'tasks' ), true ) ) {
			return 'work';
		}

		if ( in_array( $tab, array( 'overview', 'work', 'gantt', 'milestones', 'risks-issues', 'approvals', 'files', 'discussion', 'activity', 'settings' ), true ) ) {
			return $tab;
		}

		return 'overview';
	}

	public function convert_request( WP_REST_Request $request ): WP_REST_Response {
		$target_type = sanitize_key( (string) $request->get_param( 'targetType' ) );

		if ( ! in_array( $target_type, array( 'project', 'task' ), true ) ) {
			return $this->error_response( __( 'A valid conversion target is required.', 'coordina' ), 400 );
		}

		try {
			return $this->respond( $this->requests->convert( (int) $request['id'], $target_type, $this->projects, $this->tasks ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage() );
		}
	}

	public function get_my_work( WP_REST_Request $request ): WP_REST_Response {
		unset( $request );
		$user_id = get_current_user_id();
		$my_work = $this->tasks->get_my_work( $user_id );
		$pending_approvals = $this->approvals->get_pending_for_user( $user_id );

		return $this->respond(
			array(
				'sections'         => $my_work['sections'] ?? array(),
				'summary'          => array_merge(
					$my_work['summary'] ?? array(),
					array(
						'pendingApprovals' => count( $pending_approvals ),
					)
				),
				'pendingApprovals' => $pending_approvals,
				'notifications'    => $this->notifications->get_for_user( $user_id ),
			)
		);
	}

	public function get_project_settings( WP_REST_Request $request ): WP_REST_Response {
		$project_id = (int) $request['id'];

		if ( ! $this->access->can_edit_project( $project_id ) ) {
			return $this->error_response( __( 'You are not allowed to access this project settings.', 'coordina' ), 403 );
		}

		$settings = $this->projects->get_settings( $project_id );

		if ( empty( $settings ) ) {
			return $this->error_response( __( 'Project could not be found.', 'coordina' ), 404 );
		}

		return $this->respond( $settings );
	}

	public function update_project_settings( WP_REST_Request $request ): WP_REST_Response {
		try {
			return $this->respond( $this->projects->update_settings( (int) $request['id'], $this->sanitize_payload( $request ) ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage() );
		}
	}

	public function get_project_task_groups( WP_REST_Request $request ): WP_REST_Response {
		$project_id = (int) $request['id'];

		if ( ! $this->access->can_view_project( $project_id ) ) {
			return $this->error_response( __( 'You are not allowed to access this project task groups.', 'coordina' ), 403 );
		}

		$project  = $this->projects->find( $project_id );
		$settings = $this->settings->get();
		$label    = ! empty( $project['task_group_label'] ) ? (string) $project['task_group_label'] : (string) ( $settings['general']['task_group_label'] ?? 'stage' );

		return $this->respond(
			array(
				'items'          => $this->tasks->get_groups_for_project( $project_id ),
				'taskGroupLabel' => $label,
			)
		);
	}

	public function create_project_task_group( WP_REST_Request $request ): WP_REST_Response {
		try {
			return $this->respond( $this->tasks->create_group( (int) $request['id'], $this->sanitize_payload( $request ) ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage(), 400 );
		}
	}

	public function get_workload( WP_REST_Request $request ): WP_REST_Response {
		return $this->respond( $this->workload->get_for_current_user( $request->get_params() ) );
	}

	public function get_notifications( WP_REST_Request $request ): WP_REST_Response {
		unset( $request );
		$user_id = get_current_user_id();

		return $this->respond(
			array(
				'items'       => $this->notifications->get_for_user( $user_id ),
				'preferences' => $this->get_notification_preferences( $user_id ),
			)
		);
	}

	public function update_notification( WP_REST_Request $request ): WP_REST_Response {
		$is_read = rest_sanitize_boolean( $request->get_param( 'isRead' ) );
		$user_id = get_current_user_id();

		try {
			$result = $this->notifications->set_read_state( (int) $request['id'], $user_id, $is_read );
			return $this->respond( array( 'updated' => $result ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage() );
		}
	}

	public function update_notification_preferences( WP_REST_Request $request ): WP_REST_Response {
		$user_id = get_current_user_id();
		update_user_meta( $user_id, 'coordina_notification_digest', rest_sanitize_boolean( $request->get_param( 'digest' ) ) ? '1' : '0' );
		update_user_meta( $user_id, 'coordina_notify_project_updates', rest_sanitize_boolean( $request->get_param( 'projectUpdates' ) ) ? '1' : '0' );
		update_user_meta( $user_id, 'coordina_notify_approval_alerts', rest_sanitize_boolean( $request->get_param( 'approvalAlerts' ) ) ? '1' : '0' );

		return $this->respond( $this->get_notification_preferences( $user_id ) );
	}

	public function get_settings( WP_REST_Request $request ): WP_REST_Response {
		unset( $request );
		return $this->respond( $this->settings->get() );
	}

	public function update_settings( WP_REST_Request $request ): WP_REST_Response {
		try {
			return $this->respond( $this->settings->update( $this->sanitize_payload( $request ) ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage() );
		}
	}

	public function get_saved_views( WP_REST_Request $request ): WP_REST_Response {
		$module = sanitize_key( (string) $request->get_param( 'module' ) );
		return $this->respond( array( 'items' => $this->saved_views->get_for_user( get_current_user_id(), $module ) ) );
	}

	public function create_saved_view( WP_REST_Request $request ): WP_REST_Response {
		$payload = $request->get_json_params();
		$data    = is_array( $payload ) ? $payload : $request->get_params();

		if ( empty( $data['module'] ) || empty( $data['view_name'] ) ) {
			return $this->error_response( __( 'A module and view name are required.', 'coordina' ), 400 );
		}

		try {
			return $this->respond( $this->saved_views->create( get_current_user_id(), $data ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage() );
		}
	}

	public function can_manage_settings(): bool {
		return current_user_can( 'coordina_manage_settings' );
	}

	public function seed_demo_data( WP_REST_Request $request ): WP_REST_Response {
		$type       = sanitize_key( (string) $request->get_param( 'type' ) );
		$manager_id = absint( $request->get_param( 'manager_id' ) ) ?: get_current_user_id();

		if ( ! in_array( $type, array( 'all', 'website', 'mobile', 'support' ), true ) ) {
			return $this->error_response( __( 'Invalid project type.', 'coordina' ), 400 );
		}

		if ( ! get_userdata( $manager_id ) ) {
			return $this->error_response( __( 'Invalid manager user ID.', 'coordina' ), 400 );
		}

		try {
			$seeder = new DataSeeder(
				$this->projects,
				$this->tasks,
				$this->milestones,
				$this->risks_issues,
				$this->approvals,
				$this->discussions,
				$this->activity,
				$this->files,
				$this->access
			);

			$projects = array();

			switch ( $type ) {
				case 'website':
					$projects[] = $seeder->seed_website_redesign( $manager_id );
					break;

				case 'mobile':
					$projects[] = $seeder->seed_mobile_app( $manager_id );
					break;

				case 'support':
					$projects[] = $seeder->seed_support_process( $manager_id );
					break;

				case 'all':
					$projects[] = $seeder->seed_website_redesign( $manager_id );
					$projects[] = $seeder->seed_mobile_app( $manager_id );
					$projects[] = $seeder->seed_support_process( $manager_id );
					break;
			}

			return $this->respond( array( 'projects' => $projects ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage() );
		}
	}

	public function clear_demo_data( WP_REST_Request $request ): WP_REST_Response {
		unset( $request );

		global $wpdb;

		try {
			$projects = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}coordina_projects" );

			foreach ( (array) $projects as $project_id ) {
				$project_id = (int) $project_id;

				$wpdb->delete(
					$wpdb->prefix . 'coordina_activities',
					array( 'project_id' => $project_id ),
					array( '%d' )
				);

				$wpdb->delete(
					$wpdb->prefix . 'coordina_approvals',
					array( 'project_id' => $project_id ),
					array( '%d' )
				);

				$wpdb->delete(
					$wpdb->prefix . 'coordina_risks_issues',
					array( 'project_id' => $project_id ),
					array( '%d' )
				);

				$wpdb->delete(
					$wpdb->prefix . 'coordina_milestones',
					array( 'project_id' => $project_id ),
					array( '%d' )
				);

				$task_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT id FROM {$wpdb->prefix}coordina_tasks WHERE project_id = %d",
						$project_id
					)
				);

				foreach ( (array) $task_ids as $task_id ) {
					$wpdb->delete(
						$wpdb->prefix . 'coordina_task_checklist_items',
						array( 'task_id' => $task_id ),
						array( '%d' )
					);
					$wpdb->delete(
						$wpdb->prefix . 'coordina_checklist_items',
						array(
							'object_type' => 'task',
							'object_id'   => (int) $task_id,
						),
						array( '%s', '%d' )
					);
					$wpdb->delete(
						$wpdb->prefix . 'coordina_checklists',
						array(
							'object_type' => 'task',
							'object_id'   => (int) $task_id,
						),
						array( '%s', '%d' )
					);
				}

				$wpdb->delete(
					$wpdb->prefix . 'coordina_checklist_items',
					array( 'project_id' => $project_id ),
					array( '%d' )
				);
				$wpdb->delete(
					$wpdb->prefix . 'coordina_checklists',
					array( 'project_id' => $project_id ),
					array( '%d' )
				);

				$wpdb->delete(
					$wpdb->prefix . 'coordina_tasks',
					array( 'project_id' => $project_id ),
					array( '%d' )
				);

				$wpdb->delete(
					$wpdb->prefix . 'coordina_task_groups',
					array( 'project_id' => $project_id ),
					array( '%d' )
				);

				$wpdb->delete(
					$wpdb->prefix . 'coordina_project_members',
					array( 'project_id' => $project_id ),
					array( '%d' )
				);

				$wpdb->delete(
					$wpdb->prefix . 'coordina_checklist_items',
					array( 'project_id' => $project_id ),
					array( '%d' )
				);

				$wpdb->delete(
					$wpdb->prefix . 'coordina_projects',
					array( 'id' => $project_id ),
					array( '%d' )
				);
			}

			return $this->respond( array( 'cleared' => count( $projects ) ) );
		} catch ( Throwable $exception ) {
			return $this->error_response( $exception->getMessage() );
		}
	}

	public function can_access_any_shell(): bool {
		return current_user_can( 'coordina_access' ) || current_user_can( 'coordina_access_portal' );
	}

	public function can_access_admin(): bool {
		return current_user_can( 'coordina_access' );
	}

	private function sanitize_payload( WP_REST_Request $request ): array {
		$payload = $request->get_json_params();
		$data    = is_array( $payload ) ? $payload : $request->get_params();

		foreach ( $data as $key => $value ) {
			if ( is_string( $value ) ) {
				$data[ $key ] = wp_unslash( $value );
			}
		}

		return $data;
	}

	private function validate_required_title( array $payload ): ?WP_REST_Response {
		if ( empty( $payload['title'] ) ) {
			return $this->error_response( __( 'A title is required.', 'coordina' ), 400 );
		}

		return null;
	}

	private function get_notification_preferences( int $user_id ): array {
		return array(
			'digest'          => (bool) get_user_meta( $user_id, 'coordina_notification_digest', true ),
			'project_updates' => '0' !== (string) get_user_meta( $user_id, 'coordina_notify_project_updates', true ),
			'approval_alerts' => '0' !== (string) get_user_meta( $user_id, 'coordina_notify_approval_alerts', true ),
		);
	}

	private function respond( $data ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			)
		);
	}

	private function error_response( string $message, int $status = 500 ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'success' => false,
				'error'   => $message,
			),
			$status
		);
	}

	/**
	 * Get assignable internal users for admin selectors.
	 *
	 * @return array<int,\WP_User>
	 */
	private function get_assignable_users(): array {
		$users = get_users(
			array(
				'number' => 200,
			)
		);

		$filtered = array_values(
			array_filter(
				$users,
				static function ( $user ): bool {
					return $user instanceof \WP_User && (
						user_can( $user, 'coordina_access' )
						|| user_can( $user, 'coordina_access_portal' )
						|| user_can( $user, 'coordina_manage_projects' )
						|| user_can( $user, 'coordina_manage_tasks' )
						|| user_can( $user, 'coordina_manage_requests' )
						|| user_can( $user, 'coordina_manage_settings' )
					);
				}
			)
		);

		usort(
			$filtered,
			static function ( \WP_User $left, \WP_User $right ): int {
				return strcasecmp( (string) $left->display_name, (string) $right->display_name );
			}
		);

		return array_slice( $filtered, 0, 100 );
	}
}
