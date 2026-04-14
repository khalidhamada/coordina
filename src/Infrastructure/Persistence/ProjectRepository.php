<?php
/**
 * Project repository.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Persistence;

use RuntimeException;

final class ProjectRepository extends AbstractRepository {
	/**
	 * Fetch paginated projects.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array<string, mixed>
	 */
	public function get_items( array $args ): array {
		$table      = $this->table( 'projects' );
		$page       = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page   = max( 1, min( 50, (int) ( $args['per_page'] ?? 10 ) ) );
		$search     = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';
		$status     = isset( $args['status'] ) ? sanitize_text_field( (string) $args['status'] ) : '';
		$order_by   = $this->sanitize_order_by( (string) ( $args['orderby'] ?? 'updated_at' ) );
		$order      = 'asc' === strtolower( (string) ( $args['order'] ?? 'desc' ) ) ? 'ASC' : 'DESC';
		$offset     = ( $page - 1 ) * $per_page;
		$where      = array( '1=1' );
		$params     = array();
		list( $access_sql, $access_params ) = $this->access->project_access_where( 'id' );
		$where[] = $access_sql;
		$params  = array_merge( $params, $access_params );

		if ( '' !== $search ) {
			$where[]  = '(title LIKE %s OR code LIKE %s)';
			$like     = '%' . $this->wpdb->esc_like( $search ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		if ( '' !== $status ) {
			$where[]  = 'status = %s';
			$params[] = $status;
		}

		$where_sql = implode( ' AND ', $where );
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		$list_sql  = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$order_by} {$order} LIMIT %d OFFSET %d";
		$total     = (int) $this->wpdb->get_var( $this->wpdb->prepare( $count_sql, $params ) );

		$list_params   = $params;
		$list_params[] = $per_page;
		$list_params[] = $offset;
		$rows          = $this->wpdb->get_results( $this->wpdb->prepare( $list_sql, $list_params ) );

		return array(
			'items'      => array_map( array( $this, 'map_item' ), $rows ?: array() ),
			'total'      => $total,
			'page'       => $page,
			'per_page'   => $per_page,
			'totalPages' => (int) ceil( $total / $per_page ),
		);
	}

	/**
	 * Get a single project.
	 *
	 * @param int $id Project id.
	 * @return array<string, mixed>
	 */
	public function find( int $id ): array {
		if ( ! $this->access->can_view_project( $id ) ) {
			return array();
		}

		$table = $this->table( 'projects' );
		$row   = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );

		return $this->map_item( $row );
	}

	/**
	 * Get workspace summary for a project.
	 *
	 * @param int $id Project id.
	 * @return array<string, mixed>
	 */
	public function get_workspace_summary( int $id ): array {
		$project = $this->find( $id );

		if ( empty( $project ) ) {
			return array();
		}

		$tasks_table = $this->table( 'tasks' );
		list( $task_access_sql, $task_access_params ) = $this->access->task_access_where( 'id' );
		$summary_row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT
					COUNT(*) AS total_count,
					SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) AS completed_count,
					SUM(CASE WHEN status = 'blocked' OR blocked = 1 THEN 1 ELSE 0 END) AS blocked_count,
					SUM(CASE WHEN due_date IS NOT NULL AND due_date < %s AND status NOT IN ('done', 'cancelled') THEN 1 ELSE 0 END) AS overdue_count,
					SUM(CASE WHEN assignee_user_id > 0 THEN 1 ELSE 0 END) AS assigned_count
				FROM {$tasks_table}
				WHERE project_id = %d AND {$task_access_sql}",
				array_merge( array( current_time( 'mysql', true ), $id ), $task_access_params )
			)
		);

		$summary            = $this->row_to_array( $summary_row );
		$total_tasks        = (int) ( $summary['total_count'] ?? 0 );
		$completed_tasks    = (int) ( $summary['completed_count'] ?? 0 );
		$blocked_tasks      = (int) ( $summary['blocked_count'] ?? 0 );
		$overdue_tasks      = (int) ( $summary['overdue_count'] ?? 0 );
		$assigned_tasks     = (int) ( $summary['assigned_count'] ?? 0 );
		$completion_percent = $total_tasks > 0 ? (int) round( ( $completed_tasks / $total_tasks ) * 100 ) : 0;

		return array(
			'project'  => $project,
			'metrics'  => array(
				'totalTasks'         => $total_tasks,
				'completedTasks'     => $completed_tasks,
				'openTasks'          => max( 0, $total_tasks - $completed_tasks ),
				'blockedTasks'       => $blocked_tasks,
				'overdueTasks'       => $overdue_tasks,
				'assignedTasks'      => $assigned_tasks,
				'completionPercent'  => $completion_percent,
			),
			'healthSummary' => $this->build_health_summary( $project, $completion_percent, $blocked_tasks, $overdue_tasks ),
		);
	}

	/**
	 * Create a project.
	 *
	 * @param array<string, mixed> $data Project data.
	 * @return array<string, mixed>
	 */
	public function create( array $data ): array {
		if ( ! $this->access->can_edit_projects() ) {
			throw new RuntimeException( __( 'You are not allowed to create projects.', 'coordina' ) );
		}

		$table           = $this->table( 'projects' );
		$now             = $this->now();
		$status          = sanitize_key( (string) ( $data['status'] ?? 'draft' ) );
		$actual_end_date = $this->normalize_actual_end_date( $status, isset( $data['actual_end_date'] ) ? (string) $data['actual_end_date'] : null );
		$clean           = array(
			'code'            => sanitize_text_field( (string) ( $data['code'] ?? '' ) ),
			'title'           => sanitize_text_field( (string) ( $data['title'] ?? '' ) ),
			'description'     => isset( $data['description'] ) ? wp_kses_post( (string) $data['description'] ) : '',
			'status'          => $status,
			'health'          => sanitize_key( (string) ( $data['health'] ?? 'neutral' ) ),
			'priority'        => sanitize_key( (string) ( $data['priority'] ?? 'normal' ) ),
			'manager_user_id' => (int) ( $data['manager_user_id'] ?? 0 ),
			'visibility'      => $this->sanitize_choice( (string) ( $data['visibility'] ?? 'team' ), array( 'team', 'private', 'public' ), 'team' ),
			'notification_policy' => $this->sanitize_choice( (string) ( $data['notification_policy'] ?? 'default' ), array( 'default', 'important-only', 'all-updates', 'muted' ), 'default' ),
			'task_group_label' => $this->sanitize_choice( (string) ( $data['task_group_label'] ?? '' ), array( '', 'stage', 'phase', 'bucket' ), '' ),
			'closeout_notes'  => isset( $data['closeout_notes'] ) ? wp_kses_post( (string) $data['closeout_notes'] ) : '',
			'workspace_id'    => 0,
			'start_date'      => $this->normalize_datetime( isset( $data['start_date'] ) ? (string) $data['start_date'] : null ),
			'target_end_date' => $this->normalize_datetime( isset( $data['target_end_date'] ) ? (string) $data['target_end_date'] : null ),
			'actual_end_date' => $actual_end_date,
			'archived_at'     => 'archived' === $status ? $now : null,
			'created_by'      => get_current_user_id(),
			'created_at'      => $now,
			'updated_at'      => $now,
		);

		$result = $this->wpdb->insert( $table, $clean );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Project could not be created.', 'coordina' ) );
		}

		return $this->find( (int) $this->wpdb->insert_id );
	}

	/**
	 * Update a project.
	 *
	 * @param int                  $id Project id.
	 * @param array<string, mixed> $data Project data.
	 * @return array<string, mixed>
	 */
	public function update( int $id, array $data ): array {
		if ( ! $this->access->can_edit_project( $id ) ) {
			throw new RuntimeException( __( 'You are not allowed to update this project.', 'coordina' ) );
		}

		$table           = $this->table( 'projects' );
		$current         = $this->find( $id );
		$status          = sanitize_key( (string) ( $data['status'] ?? 'draft' ) );
		$actual_end_date = $this->normalize_actual_end_date( $status, isset( $data['actual_end_date'] ) ? (string) $data['actual_end_date'] : (string) ( $current['actual_end_date'] ?? '' ) );
		$archived_at     = 'archived' === $status ? ( (string) ( $current['archived_at'] ?? '' ) ?: $this->now() ) : null;
		$clean           = array(
			'title'           => sanitize_text_field( (string) ( $data['title'] ?? '' ) ),
			'description'     => isset( $data['description'] ) ? wp_kses_post( (string) $data['description'] ) : '',
			'status'          => $status,
			'health'          => sanitize_key( (string) ( $data['health'] ?? 'neutral' ) ),
			'priority'        => sanitize_key( (string) ( $data['priority'] ?? 'normal' ) ),
			'manager_user_id' => (int) ( $data['manager_user_id'] ?? 0 ),
			'start_date'      => $this->normalize_datetime( isset( $data['start_date'] ) ? (string) $data['start_date'] : null ),
			'target_end_date' => $this->normalize_datetime( isset( $data['target_end_date'] ) ? (string) $data['target_end_date'] : null ),
			'actual_end_date' => $actual_end_date,
			'archived_at'     => $archived_at,
			'updated_at'      => $this->now(),
		);

		$result = $this->wpdb->update( $table, $clean, array( 'id' => $id ) );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Project could not be updated.', 'coordina' ) );
		}

		return $this->find( $id );
	}

	/**
	 * Get project-scoped governance settings.
	 *
	 * @param int $id Project id.
	 * @return array<string, mixed>
	 */
	public function get_settings( int $id ): array {
		$project = $this->find( $id );

		if ( empty( $project ) ) {
			return array();
		}

		return array(
			'project' => array(
				'id'                  => (int) ( $project['id'] ?? 0 ),
				'title'               => (string) ( $project['title'] ?? '' ),
				'status'              => (string) ( $project['status'] ?? 'draft' ),
				'health'              => (string) ( $project['health'] ?? 'neutral' ),
				'priority'            => (string) ( $project['priority'] ?? 'normal' ),
				'manager_user_id'     => (int) ( $project['manager_user_id'] ?? 0 ),
				'manager_label'       => (string) ( $project['manager_label'] ?? '' ),
				'visibility'          => (string) ( $project['visibility'] ?? 'team' ),
				'notification_policy' => (string) ( $project['notification_policy'] ?? 'default' ),
				'task_group_label'    => (string) ( $project['task_group_label'] ?? '' ),
				'closeout_notes'      => (string) ( $project['closeout_notes'] ?? '' ),
				'actual_end_date'     => (string) ( $project['actual_end_date'] ?? '' ),
				'archived_at'         => (string) ( $project['archived_at'] ?? '' ),
			),
			'members' => $this->get_project_members( $id ),
		);
	}

	/**
	 * Update project-scoped governance settings.
	 *
	 * @param int                  $id Project id.
	 * @param array<string, mixed> $data Settings payload.
	 * @return array<string, mixed>
	 */
	public function update_settings( int $id, array $data ): array {
		if ( ! $this->access->can_edit_project( $id ) ) {
			throw new RuntimeException( __( 'You are not allowed to update this project settings.', 'coordina' ) );
		}

		$current = $this->find( $id );

		if ( empty( $current ) ) {
			throw new RuntimeException( __( 'Project could not be found.', 'coordina' ) );
		}

		$table           = $this->table( 'projects' );
		$status          = $this->sanitize_choice( (string) ( $data['status'] ?? $current['status'] ?? 'draft' ), array( 'draft', 'planned', 'active', 'on-hold', 'at-risk', 'blocked', 'completed', 'cancelled', 'archived' ), 'draft' );
		$actual_end_date = $this->normalize_actual_end_date( $status, isset( $data['actual_end_date'] ) ? (string) $data['actual_end_date'] : (string) ( $current['actual_end_date'] ?? '' ) );
		$archived_at     = 'archived' === $status ? ( (string) ( $current['archived_at'] ?? '' ) ?: $this->now() ) : null;
		$clean           = array(
			'status'              => $status,
			'health'              => $this->sanitize_choice( (string) ( $data['health'] ?? $current['health'] ?? 'neutral' ), array( 'neutral', 'good', 'at-risk', 'blocked' ), 'neutral' ),
			'priority'            => $this->sanitize_choice( (string) ( $data['priority'] ?? $current['priority'] ?? 'normal' ), array( 'low', 'normal', 'high', 'urgent' ), 'normal' ),
			'manager_user_id'     => (int) ( $data['manager_user_id'] ?? $current['manager_user_id'] ?? 0 ),
			'visibility'          => $this->sanitize_choice( (string) ( $data['visibility'] ?? $current['visibility'] ?? 'team' ), array( 'team', 'private', 'public' ), 'team' ),
			'notification_policy' => $this->sanitize_choice( (string) ( $data['notification_policy'] ?? $current['notification_policy'] ?? 'default' ), array( 'default', 'important-only', 'all-updates', 'muted' ), 'default' ),
			'task_group_label'    => $this->sanitize_choice( (string) ( $data['task_group_label'] ?? $current['task_group_label'] ?? '' ), array( '', 'stage', 'phase', 'bucket' ), '' ),
			'closeout_notes'      => isset( $data['closeout_notes'] ) ? wp_kses_post( (string) $data['closeout_notes'] ) : (string) ( $current['closeout_notes'] ?? '' ),
			'actual_end_date'     => $actual_end_date,
			'archived_at'         => $archived_at,
			'updated_at'          => $this->now(),
		);

		$result = $this->wpdb->update( $table, $clean, array( 'id' => $id ) );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Project settings could not be updated.', 'coordina' ) );
		}

		$this->set_project_members( $id, $this->parse_member_ids( $data['team_member_ids'] ?? array() ) );
		$this->log_activity( 'project', $id, 'settings-updated', __( 'Project settings were updated.', 'coordina' ) );

		return $this->get_settings( $id );
	}

	/**
	 * Bulk status update.
	 *
	 * @param int[]  $ids Item ids.
	 * @param string $status New status.
	 * @return int
	 */
	public function bulk_update_status( array $ids, string $status ): int {
		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );

		if ( empty( $ids ) ) {
			return 0;
		}

		$ids = array_values(
			array_filter(
				$ids,
				function ( int $id ): bool {
					return $this->access->can_edit_project( $id );
				}
			)
		);

		if ( empty( $ids ) ) {
			throw new RuntimeException( __( 'You are not allowed to update project statuses.', 'coordina' ) );
		}

		$table           = $this->table( 'projects' );
		$placeholders    = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$normalized_date = $this->normalize_actual_end_date( sanitize_key( $status ), null );
		$params          = array_merge( array( sanitize_key( $status ), $normalized_date, $this->now() ), $ids );
		$sql             = "UPDATE {$table} SET status = %s, actual_end_date = %s, updated_at = %s WHERE id IN ({$placeholders})";

		$result = $this->wpdb->query( $this->wpdb->prepare( $sql, $params ) );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Project statuses could not be updated.', 'coordina' ) );
		}

		return (int) $result;
	}

	/**
	 * Delete a project and its related project-scoped records.
	 *
	 * @param int $id Project id.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		if ( ! $this->access->can_delete_project( $id ) ) {
			throw new RuntimeException( __( 'You are not allowed to delete this project.', 'coordina' ) );
		}

		$project = $this->find( $id );

		if ( empty( $project ) ) {
			throw new RuntimeException( __( 'Project could not be found.', 'coordina' ) );
		}

		$tasks      = $this->wpdb->get_col( $this->wpdb->prepare( 'SELECT id FROM ' . $this->table( 'tasks' ) . ' WHERE project_id = %d', $id ) );
		$risks      = $this->wpdb->get_col( $this->wpdb->prepare( 'SELECT id FROM ' . $this->table( 'risks_issues' ) . ' WHERE project_id = %d', $id ) );
		$milestones = $this->wpdb->get_col( $this->wpdb->prepare( 'SELECT id FROM ' . $this->table( 'milestones' ) . ' WHERE project_id = %d', $id ) );
		$task_repo  = new TaskRepository();
		$risk_repo  = new RiskIssueRepository();
		$milestones_repo = new MilestoneRepository();

		foreach ( array_map( 'intval', $tasks ?: array() ) as $task_id ) {
			$task_repo->delete( $task_id );
		}

		foreach ( array_map( 'intval', $risks ?: array() ) as $risk_id ) {
			$risk_repo->delete( $risk_id );
		}

		foreach ( array_map( 'intval', $milestones ?: array() ) as $milestone_id ) {
			$milestones_repo->delete( $milestone_id );
		}

		$this->delete_context_relations( 'project', $id );
		$this->wpdb->delete( $this->table( 'task_groups' ), array( 'project_id' => $id ) );
		$this->wpdb->delete( $this->table( 'project_members' ), array( 'project_id' => $id ) );

		$result = $this->wpdb->delete( $this->table( 'projects' ), array( 'id' => $id ) );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Project could not be deleted.', 'coordina' ) );
		}

		return $result > 0;
	}

	/**
	 * Map raw row.
	 *
	 * @param object|null $row DB row.
	 * @return array<string, mixed>
	 */
	private function map_item( $row ): array {
		$item = $this->row_to_array( $row );

		if ( empty( $item ) ) {
			return array();
		}

		$item['manager_label'] = $item['manager_user_id'] ? ( get_userdata( (int) $item['manager_user_id'] )->display_name ?? '' ) : '';
		$item['visibility'] = $item['visibility'] ?? 'team';
		$item['notification_policy'] = $item['notification_policy'] ?? 'default';
		$item['task_group_label'] = $item['task_group_label'] ?? '';
		$item['closeout_notes'] = $item['closeout_notes'] ?? '';
		$item['archived_at'] = $item['archived_at'] ?? '';
		$item['can_edit'] = $this->access->can_edit_project( (int) ( $item['id'] ?? 0 ) );
		$item['can_delete'] = $this->access->can_delete_project( (int) ( $item['id'] ?? 0 ) );
		$item['can_open'] = $this->access->can_view_project( (int) ( $item['id'] ?? 0 ) );
		return $item;
	}

	/**
	 * Get project members.
	 *
	 * @param int $project_id Project id.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_project_members( int $project_id ): array {
		$table = $this->table( 'project_members' );
		$rows  = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM {$table} WHERE project_id = %d ORDER BY id ASC", $project_id ) );

		return array_map(
			function ( $row ): array {
				$item = $this->row_to_array( $row );
				return array(
					'id'          => (int) ( $item['id'] ?? 0 ),
					'project_id'  => (int) ( $item['project_id'] ?? 0 ),
					'user_id'     => (int) ( $item['user_id'] ?? 0 ),
					'member_role' => (string) ( $item['member_role'] ?? 'member' ),
					'user_label'  => $this->get_user_label( (int) ( $item['user_id'] ?? 0 ) ),
				);
			},
			$rows ?: array()
		);
	}

	/**
	 * Replace project members.
	 *
	 * @param int   $project_id Project id.
	 * @param int[] $member_ids Member user ids.
	 */
	private function set_project_members( int $project_id, array $member_ids ): void {
		$table = $this->table( 'project_members' );
		$this->wpdb->delete( $table, array( 'project_id' => $project_id ) );

		foreach ( array_values( array_unique( array_filter( array_map( 'intval', $member_ids ) ) ) ) as $user_id ) {
			if ( $user_id <= 0 || ! get_userdata( $user_id ) ) {
				continue;
			}

			$this->wpdb->insert(
				$table,
				array(
					'project_id'  => $project_id,
					'user_id'     => $user_id,
					'member_role' => 'member',
					'created_at'  => $this->now(),
				)
			);
		}
	}

	/**
	 * Parse member ids from a REST payload.
	 *
	 * @param mixed $value Raw payload value.
	 * @return int[]
	 */
	private function parse_member_ids( $value ): array {
		if ( is_string( $value ) ) {
			$value = array_filter( array_map( 'trim', explode( ',', $value ) ) );
		}

		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_values( array_unique( array_filter( array_map( 'intval', $value ) ) ) );
	}

	/**
	 * Sanitize an enum-like setting.
	 *
	 * @param string   $value Requested value.
	 * @param string[] $allowed Allowed values.
	 * @param string   $fallback Fallback value.
	 */
	private function sanitize_choice( string $value, array $allowed, string $fallback ): string {
		$value = sanitize_key( $value );
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	/**
	 * Sanitize order by field.
	 *
	 * @param string $value Requested field.
	 * @return string
	 */
	private function sanitize_order_by( string $value ): string {
		$allowed = array( 'title', 'status', 'priority', 'target_end_date', 'updated_at', 'created_at', 'health' );
		return in_array( $value, $allowed, true ) ? $value : 'updated_at';
	}

	/**
	 * Normalize actual end date for project close-out.
	 *
	 * @param string      $status Project status.
	 * @param string|null $actual_end_date Requested end date.
	 * @return string|null
	 */
	private function normalize_actual_end_date( string $status, ?string $actual_end_date ): ?string {
		$normalized = $this->normalize_datetime( $actual_end_date );

		if ( 'completed' === $status ) {
			return $normalized ?: $this->now();
		}

		if ( in_array( $status, array( 'draft', 'planned', 'active', 'on-hold', 'at-risk', 'blocked', 'cancelled', 'archived' ), true ) ) {
			return null;
		}

		return $normalized;
	}

	/**
	 * Build a short health narrative.
	 *
	 * @param array<string, mixed> $project Project record.
	 * @param int                  $completion_percent Completion percentage.
	 * @param int                  $blocked_tasks Blocked tasks.
	 * @param int                  $overdue_tasks Overdue tasks.
	 * @return string
	 */
	private function build_health_summary( array $project, int $completion_percent, int $blocked_tasks, int $overdue_tasks ): string {
		if ( $blocked_tasks > 0 ) {
			return __( 'Blocked items need attention before this project can move smoothly again.', 'coordina' );
		}

		if ( $overdue_tasks > 0 ) {
			return __( 'Some linked work is overdue, so this project needs a schedule check-in.', 'coordina' );
		}

		if ( 'completed' === ( $project['status'] ?? '' ) || $completion_percent >= 100 ) {
			return __( 'This project is effectively wrapped up and ready for close-out steps.', 'coordina' );
		}

		if ( $completion_percent >= 60 ) {
			return __( 'Execution is moving well, with most linked work already progressing toward completion.', 'coordina' );
		}

		return __( 'This workspace is ready for planning, coordination, and clearer task breakdown.', 'coordina' );
	}
}
