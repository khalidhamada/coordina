<?php
/**
 * Dashboard repository.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Persistence;

use Coordina\Platform\Contracts\AccessPolicyInterface;
use Coordina\Platform\Contracts\SettingsStoreInterface;

final class DashboardRepository extends AbstractRepository {
	/**
	 * Shared activity repository.
	 *
	 * @var ActivityRepository
	 */
	private $activity_repository;

	/**
	 * Shared settings repository.
	 *
	 * @var SettingsStoreInterface
	 */
	private $settings_repository;

	/**
	 * Constructor.
	 *
	 * @param ActivityRepository|null     $activity_repository Shared activity repository.
	 * @param SettingsStoreInterface|null $settings_repository Shared settings repository.
	 * @param AccessPolicyInterface|null  $access Shared access policy.
	 */
	public function __construct( ?ActivityRepository $activity_repository = null, ?SettingsStoreInterface $settings_repository = null, ?AccessPolicyInterface $access = null ) {
		parent::__construct( $access );
		$this->activity_repository = $activity_repository ?: new ActivityRepository();
		$this->settings_repository = $settings_repository ?: new SettingsRepository();
	}

	/**
	 * Get dashboard data for current user.
	 *
	 * @return array<string, mixed>
	 */
	public function get_for_current_user( array $args = array() ): array {
		$user_id   = get_current_user_id();
		$role_mode = $this->get_role_mode();
		$scope     = $this->get_scope( $role_mode );
		$activity_page = max( 1, (int) ( $args['activity_page'] ?? 1 ) );

		return array(
			'scope'    => $scope,
			'roleMode' => $role_mode,
			'kpis'     => $this->get_kpis( $scope, $user_id ),
			'widgets'  => array(
				'atRiskProjects'   => $this->get_at_risk_projects( $scope, $user_id ),
				'overdueTasks'     => $this->get_overdue_tasks( $scope, $user_id ),
				'pendingApprovals' => $this->get_pending_approvals( $scope, $user_id ),
				'recentActivity'   => $this->get_recent_activity( $scope, $user_id, $activity_page ),
				'activitySummary'  => $this->get_activity_summary( $scope, $user_id ),
				'upcomingDeadlines'=> $this->get_upcoming_deadlines( $scope, $user_id ),
			),
		);
	}

	/**
	 * Determine role mode.
	 *
	 * @return string
	 */
	private function get_role_mode(): string {
		if ( current_user_can( 'coordina_manage_settings' ) ) {
			return 'admin';
		}

		if ( current_user_can( 'coordina_view_dashboard' ) && ! current_user_can( 'coordina_manage_projects' ) ) {
			return 'executive';
		}

		if ( current_user_can( 'coordina_manage_projects' ) ) {
			return 'manager';
		}

		return 'team';
	}

	/**
	 * Determine scope label.
	 *
	 * @param string $role_mode Role mode.
	 * @return string
	 */
	private function get_scope( string $role_mode ): string {
		if ( 'admin' === $role_mode ) {
			return 'portfolio';
		}

		if ( 'manager' === $role_mode ) {
			return 'managed';
		}

		if ( 'executive' === $role_mode ) {
			return 'accessible';
		}

		return 'accessible';
	}

	/**
	 * Get KPI row.
	 *
	 * @param string $scope Scope label.
	 * @param int    $user_id User id.
	 * @return array<string, int>
	 */
	private function get_kpis( string $scope, int $user_id ): array {
		$projects_table  = $this->table( 'projects' );
		$tasks_table     = $this->table( 'tasks' );
		$approvals_table = $this->table( 'approvals' );
		list( $project_where, $project_params ) = $this->get_project_scope_sql( $scope, $user_id );
		list( $task_where, $task_params ) = $this->get_task_scope_sql( $scope, $user_id );
		list( $approval_where, $approval_params ) = $this->get_approval_scope_sql( $scope, $user_id );

		$total_projects    = (int) $this->prepared_var( "SELECT COUNT(*) FROM {$projects_table} WHERE {$project_where}", $project_params );
		$active_projects   = (int) $this->prepared_var( "SELECT COUNT(*) FROM {$projects_table} WHERE {$project_where} AND status IN ('planned', 'active', 'on-hold', 'at-risk', 'blocked')", $project_params );
		$at_risk_projects  = (int) $this->prepared_var( "SELECT COUNT(*) FROM {$projects_table} WHERE {$project_where} AND (health IN ('at-risk', 'blocked') OR status IN ('at-risk', 'blocked'))", $project_params );
		$blocked_projects  = (int) $this->prepared_var( "SELECT COUNT(*) FROM {$projects_table} WHERE {$project_where} AND (health = 'blocked' OR status = 'blocked')", $project_params );
		$overdue_tasks     = (int) $this->prepared_var( "SELECT COUNT(*) FROM {$tasks_table} WHERE {$task_where} AND due_date IS NOT NULL AND due_date < %s AND status NOT IN ('done', 'cancelled')", array_merge( $task_params, array( current_time( 'mysql', true ) ) ) );
		$pending_approvals = (int) $this->prepared_var( "SELECT COUNT(*) FROM {$approvals_table} WHERE {$approval_where} AND status = 'pending'", $approval_params );

		return array(
			'totalProjects'    => $total_projects,
			'activeProjects'   => $active_projects,
			'atRiskProjects'   => $at_risk_projects,
			'blockedProjects'  => $blocked_projects,
			'overdueTasks'     => $overdue_tasks,
			'pendingApprovals' => $pending_approvals,
		);
	}

	/**
	 * Get at-risk project rows.
	 *
	 * @param string $scope Scope label.
	 * @param int    $user_id User id.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_at_risk_projects( string $scope, int $user_id ): array {
		$table = $this->table( 'projects' );
		list( $where, $params ) = $this->get_project_scope_sql( $scope, $user_id );
		$rows  = $this->prepared_results( "SELECT * FROM {$table} WHERE {$where} AND (health IN ('at-risk', 'blocked') OR status IN ('at-risk', 'blocked')) ORDER BY updated_at DESC LIMIT 6", $params );

		return array_map( array( $this, 'map_project_row' ), $rows ?: array() );
	}

	/**
	 * Get overdue task rows.
	 *
	 * @param string $scope Scope label.
	 * @param int    $user_id User id.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_overdue_tasks( string $scope, int $user_id ): array {
		$table = $this->table( 'tasks' );
		list( $where, $params ) = $this->get_task_scope_sql( $scope, $user_id );
		$rows  = $this->prepared_results( "SELECT * FROM {$table} WHERE {$where} AND due_date IS NOT NULL AND due_date < %s AND status NOT IN ('done', 'cancelled') ORDER BY due_date ASC LIMIT 6", array_merge( $params, array( current_time( 'mysql', true ) ) ) );

		return array_map( array( $this, 'map_task_row' ), $rows ?: array() );
	}

	/**
	 * Get pending approvals rows.
	 *
	 * @param string $scope Scope label.
	 * @param int    $user_id User id.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_pending_approvals( string $scope, int $user_id ): array {
		$table = $this->table( 'approvals' );
		list( $where, $params ) = $this->get_approval_scope_sql( $scope, $user_id );
		$rows  = $this->prepared_results( "SELECT * FROM {$table} WHERE {$where} AND status = 'pending' ORDER BY submitted_at DESC LIMIT 6", $params );

		return array_map( array( $this, 'map_approval_row' ), $rows ?: array() );
	}

	/**
	 * Get recent activity rows.
	 *
	 * @param string $scope Scope label.
	 * @param int    $user_id User id.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_recent_activity( string $scope, int $user_id, int $page ): array {
		unset( $scope, $user_id );

		return $this->activity_repository->get_items(
			array(
				'page'     => $page,
				'per_page' => $this->default_activity_per_page(),
			)
		);
	}

	/**
	 * Get dashboard activity summary across the full visible period.
	 *
	 * @param string $scope Scope label.
	 * @param int    $user_id User id.
	 * @return array<string, mixed>
	 */
	private function get_activity_summary( string $scope, int $user_id ): array {
		unset( $scope, $user_id );

		return $this->activity_repository->get_summary();
	}

	/**
	 * Resolve the default dashboard activity page size.
	 */
	private function default_activity_per_page(): int {
		$settings = $this->settings_repository->get();

		return max( 5, min( 50, (int) ( $settings['general']['activity_page_size'] ?? 10 ) ) );
	}

	/**
	 * Get upcoming deadlines.
	 *
	 * @param string $scope Scope label.
	 * @param int    $user_id User id.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_upcoming_deadlines( string $scope, int $user_id ): array {
		$items = array_merge(
			$this->get_upcoming_project_deadlines( $scope, $user_id ),
			$this->get_upcoming_task_deadlines( $scope, $user_id )
		);

		usort(
			$items,
			static function ( array $left, array $right ): int {
				return strcmp( (string) $left['date'], (string) $right['date'] );
			}
		);

		return array_slice( $items, 0, 8 );
	}

	private function get_upcoming_project_deadlines( string $scope, int $user_id ): array {
		$table = $this->table( 'projects' );
		list( $where, $params ) = $this->get_project_scope_sql( $scope, $user_id );
		$rows  = $this->prepared_results( "SELECT * FROM {$table} WHERE {$where} AND target_end_date IS NOT NULL AND target_end_date >= %s ORDER BY target_end_date ASC LIMIT 4", array_merge( $params, array( current_time( 'mysql', true ) ) ) );

		return array_map(
			function ( $row ): array {
				$item = $this->row_to_array( $row );
				return array(
					'type'       => 'project',
					'id'         => (int) $item['id'],
					'title'      => (string) $item['title'],
					'label'      => __( 'Project target end', 'coordina' ),
					'date'       => (string) $item['target_end_date'],
					'status'     => (string) $item['status'],
					'route'      => array(
						'page'       => 'coordina-projects',
						'project_id' => (int) $item['id'],
						'project_tab'=> 'overview',
					),
				);
			},
			$rows ?: array()
		);
	}

	private function get_upcoming_task_deadlines( string $scope, int $user_id ): array {
		$table = $this->table( 'tasks' );
		list( $where, $params ) = $this->get_task_scope_sql( $scope, $user_id );
		$rows  = $this->prepared_results( "SELECT * FROM {$table} WHERE {$where} AND due_date IS NOT NULL AND due_date >= %s AND status NOT IN ('done', 'cancelled') ORDER BY due_date ASC LIMIT 6", array_merge( $params, array( current_time( 'mysql', true ) ) ) );

		return array_map(
			function ( $row ): array {
				$item = $this->row_to_array( $row );
				return array(
					'type'       => 'task',
					'id'         => (int) $item['id'],
					'title'      => (string) $item['title'],
					'label'      => ! empty( $item['project_id'] ) ? __( 'Project task due', 'coordina' ) : __( 'Standalone task due', 'coordina' ),
					'date'       => (string) $item['due_date'],
					'status'     => (string) $item['status'],
					'route'      => array(
						'page'    => 'coordina-task',
						'task_id' => (int) $item['id'],
					),
				);
			},
			$rows ?: array()
		);
	}

	private function get_project_scope_sql( string $scope, int $user_id ): array {
		if ( 'portfolio' === $scope ) {
			return array( '1=1', array() );
		}

		if ( 'accessible' === $scope ) {
			return $this->access->project_access_where( 'id' );
		}

		if ( 'managed' === $scope ) {
			return array( $this->prepare_statement( '(manager_user_id = %d OR created_by = %d)', array( $user_id, $user_id ) ), array() );
		}

		if ( 'personal' === $scope ) {
			return $this->access->project_access_where( 'id' );
		}

		return array( '1=0', array() );
	}

	private function get_task_scope_sql( string $scope, int $user_id ): array {
		if ( 'portfolio' === $scope ) {
			return array( '1=1', array() );
		}

		if ( 'accessible' === $scope ) {
			return $this->access->task_access_where( 'id' );
		}

		if ( 'managed' === $scope ) {
			return array( $this->prepare_statement( '(project_id IN (SELECT id FROM ' . $this->table( 'projects' ) . ' WHERE manager_user_id = %d OR created_by = %d) OR assignee_user_id = %d OR reporter_user_id = %d)', array( $user_id, $user_id, $user_id, $user_id ) ), array() );
		}

		if ( 'personal' === $scope ) {
			return array( $this->wpdb->prepare( '(assignee_user_id = %d OR reporter_user_id = %d)', $user_id, $user_id ), array() );
		}

		return array( '1=0', array() );
	}

	private function get_approval_scope_sql( string $scope, int $user_id ): array {
		if ( 'portfolio' === $scope ) {
			return array( '1=1', array() );
		}

		if ( 'accessible' === $scope ) {
			return $this->access->approval_access_where( 'id' );
		}

		return array( $this->prepare_statement( 'approver_user_id = %d', array( $user_id ) ), array() );
	}

	/**
	 * Prepare SQL when params are present.
	 *
	 * @param string           $sql SQL with optional placeholders.
	 * @param array<int,mixed> $params Prepare params.
	 */
	private function prepare_optional( string $sql, array $params ): string {
		if ( empty( $params ) ) {
			return $sql;
		}

		return $this->prepare_statement( $sql, $params );
	}

	private function map_project_row( $row ): array {
		$item = $this->row_to_array( $row );
		$task_summary = $this->get_project_task_counts( (int) $item['id'] );

		return array(
			'id'                => (int) $item['id'],
			'title'             => (string) $item['title'],
			'status'            => (string) $item['status'],
			'health'            => (string) $item['health'],
			'managerLabel'      => $this->get_user_label( (int) $item['manager_user_id'] ),
			'targetEndDate'     => (string) $item['target_end_date'],
			'completionPercent' => $task_summary['completionPercent'],
			'route'             => array(
				'page'       => 'coordina-projects',
				'project_id' => (int) $item['id'],
				'project_tab'=> 'overview',
			),
		);
	}

	private function get_project_task_counts( int $project_id ): array {
		$table = $this->table( 'tasks' );
		$row   = $this->prepared_row( "SELECT COUNT(*) AS total_count, SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) AS completed_count FROM {$table} WHERE project_id = %d", array( $project_id ) );
		$item  = $this->row_to_array( $row );
		$total = (int) ( $item['total_count'] ?? 0 );
		$done  = (int) ( $item['completed_count'] ?? 0 );

		return array(
			'completionPercent' => $total > 0 ? (int) round( ( $done / $total ) * 100 ) : 0,
		);
	}

	private function map_task_row( $row ): array {
		$item = $this->row_to_array( $row );
		return array(
			'id'           => (int) $item['id'],
			'title'        => (string) $item['title'],
			'status'       => (string) $item['status'],
			'priority'     => (string) $item['priority'],
			'assigneeLabel'=> $this->get_user_label( (int) $item['assignee_user_id'] ),
			'dueDate'      => (string) $item['due_date'],
			'projectId'    => (int) ( $item['project_id'] ?? 0 ),
			'projectLabel' => $this->get_project_label( (int) $item['project_id'] ),
			'context'      => ! empty( $item['project_id'] ) ? 'project' : 'standalone',
			'route'        => array(
				'page'       => 'coordina-task',
				'task_id'    => (int) $item['id'],
				'project_id' => ! empty( $item['project_id'] ) ? (int) $item['project_id'] : 0,
				'project_tab'=> ! empty( $item['project_id'] ) ? 'work' : '',
			),
		);
	}

	private function map_approval_row( $row ): array {
		$item = $this->row_to_array( $row );
		$project_id = $this->resolve_approval_project_id( (string) $item['object_type'], (int) $item['object_id'] );
		return array(
			'id'          => (int) $item['id'],
			'objectType'  => (string) $item['object_type'],
			'objectId'    => (int) $item['object_id'],
			'objectLabel' => $this->get_approval_object_label( (string) $item['object_type'], (int) $item['object_id'] ),
			'status'      => (string) $item['status'],
			'submittedAt' => (string) $item['submitted_at'],
			'ownerLabel'  => $this->get_user_label( (int) $item['submitted_by_user_id'] ),
			'projectId'   => $project_id,
			'projectLabel'=> $this->get_project_label( $project_id ),
			'route'       => array(
				'page'       => $project_id > 0 ? 'coordina-projects' : 'coordina-approvals',
				'project_id' => $project_id,
				'project_tab'=> $project_id > 0 ? 'approvals' : '',
			),
		);
	}

	private function get_approval_object_label( string $object_type, int $object_id ): string {
		if ( $object_id <= 0 ) {
			return ucfirst( $object_type );
		}

		if ( 'project' === $object_type ) {
			return $this->get_project_label( $object_id, __( 'Project', 'coordina' ) );
		}

		$table = '';
		if ( 'task' === $object_type ) {
			$table = $this->table( 'tasks' );
		} elseif ( 'request' === $object_type ) {
			$table = $this->table( 'requests' );
		} elseif ( in_array( $object_type, array( 'risk', 'issue' ), true ) ) {
			$table = $this->table( 'risks_issues' );
		}

		if ( '' === $table ) {
			return ucfirst( $object_type );
		}

		$title = $this->prepared_var( "SELECT title FROM {$table} WHERE id = %d", array( $object_id ) );
		return $title ? (string) $title : ucfirst( $object_type );
	}

	private function resolve_approval_project_id( string $object_type, int $object_id ): int {
		if ( $object_id <= 0 ) {
			return 0;
		}

		if ( 'project' === $object_type ) {
			return $object_id;
		}

		if ( 'task' === $object_type ) {
			return (int) $this->prepared_var( 'SELECT project_id FROM ' . $this->table( 'tasks' ) . ' WHERE id = %d', array( $object_id ) );
		}

		if ( in_array( $object_type, array( 'risk', 'issue' ), true ) ) {
			return (int) $this->prepared_var( 'SELECT project_id FROM ' . $this->table( 'risks_issues' ) . ' WHERE id = %d', array( $object_id ) );
		}

		return 0;
	}

	private function map_activity_row( $row ): array {
		$item = $this->row_to_array( $row );
		return array(
			'id'         => (int) $item['id'],
			'objectType' => (string) $item['object_type'],
			'objectId'   => (int) $item['object_id'],
			'eventType'  => (string) $item['event_type'],
			'actorLabel' => $this->get_user_label( (int) $item['actor_user_id'] ),
			'message'    => ! empty( $item['message'] ) ? (string) $item['message'] : __( 'Activity captured for this object.', 'coordina' ),
			'createdAt'  => (string) $item['created_at'],
		);
	}

	protected function get_user_label( int $user_id ): string {
		if ( $user_id <= 0 ) {
			return '';
		}

		return get_userdata( $user_id )->display_name ?? '';
	}

	protected function get_project_label( int $project_id, string $fallback = '' ): string {
		if ( $project_id <= 0 ) {
			return __( 'Standalone', 'coordina' );
		}

		$title = $this->prepared_var( 'SELECT title FROM ' . $this->table( 'projects' ) . ' WHERE id = %d', array( $project_id ) );
		return $title ? (string) $title : __( 'Project task', 'coordina' );
	}
}

