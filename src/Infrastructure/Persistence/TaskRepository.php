<?php
/**
 * Task repository.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Persistence;

use RuntimeException;

final class TaskRepository extends AbstractRepository {
	/**
	 * Fetch paginated tasks.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array<string, mixed>
	 */
	public function get_items( array $args ): array {
		$table        = $this->table( 'tasks' );
		$page         = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page     = max( 1, min( 50, (int) ( $args['per_page'] ?? 10 ) ) );
		$search       = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';
		$status       = isset( $args['status'] ) ? sanitize_text_field( (string) $args['status'] ) : '';
		$project_id   = isset( $args['project_id'] ) ? max( 0, (int) $args['project_id'] ) : 0;
		$assignee_user_id = isset( $args['assignee_user_id'] ) ? max( 0, (int) $args['assignee_user_id'] ) : 0;
		$task_group_id = isset( $args['task_group_id'] ) ? max( 0, (int) $args['task_group_id'] ) : 0;
		$project_mode = isset( $args['project_mode'] ) ? sanitize_key( (string) $args['project_mode'] ) : 'all';
		$order_by     = $this->sanitize_order_by( (string) ( $args['orderby'] ?? 'updated_at' ) );
		$order        = 'asc' === strtolower( (string) ( $args['order'] ?? 'desc' ) ) ? 'ASC' : 'DESC';
		$offset       = ( $page - 1 ) * $per_page;
		$where        = array( '1=1' );
		$params       = array();

		if ( '' !== $search ) {
			$where[]  = 'title LIKE %s';
			$params[] = '%' . $this->wpdb->esc_like( $search ) . '%';
		}

		if ( '' !== $status ) {
			$where[]  = 'status = %s';
			$params[] = $status;
		}

		if ( $project_id > 0 ) {
			$where[]  = 'project_id = %d';
			$params[] = $project_id;
		} elseif ( 'standalone' === $project_mode ) {
			$where[] = 'project_id = 0';
		} elseif ( 'project' === $project_mode ) {
			$where[] = 'project_id > 0';
		}

		if ( $task_group_id > 0 ) {
			$where[]  = 'task_group_id = %d';
			$params[] = $task_group_id;
		}

		if ( $assignee_user_id > 0 ) {
			$where[]  = 'assignee_user_id = %d';
			$params[] = $assignee_user_id;
		}

		list( $access_sql, $access_params ) = $this->access->task_access_where( 'id' );
		$where[] = $access_sql;
		$params  = array_merge( $params, $access_params );

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
	 * Fetch project-specific tasks.
	 *
	 * @param int                  $project_id Project id.
	 * @param array<string, mixed> $args Extra query args.
	 * @return array<string, mixed>
	 */
	public function get_for_project( int $project_id, array $args = array() ): array {
		$args['project_id'] = $project_id;

		if ( ! isset( $args['per_page'] ) ) {
			$args['per_page'] = 12;
		}

		return $this->get_items( $args );
	}

	/**
	 * Fetch project task groups.
	 *
	 * @param int $project_id Project id.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_groups_for_project( int $project_id ): array {
		if ( $project_id <= 0 || ! $this->access->can_view_project( $project_id ) ) {
			return array();
		}

		$table = $this->table( 'task_groups' );
		$rows  = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT * FROM {$table} WHERE project_id = %d ORDER BY sort_order ASC, title ASC", $project_id ) );

		return array_map( array( $this, 'map_group' ), $rows ?: array() );
	}

	/**
	 * Create a project task group.
	 *
	 * @param int                  $project_id Project id.
	 * @param array<string, mixed> $data Group payload.
	 * @return array<string, mixed>
	 */
	public function create_group( int $project_id, array $data ): array {
		if ( $project_id <= 0 || ! $this->access->can_edit_project( $project_id ) ) {
			throw new RuntimeException( __( 'You are not allowed to create task groups for this project.', 'coordina' ) );
		}

		$table = $this->table( 'task_groups' );
		$now   = $this->now();
		$clean = array(
			'project_id' => $project_id,
			'title'      => sanitize_text_field( (string) ( $data['title'] ?? '' ) ),
			'sort_order' => (int) ( $data['sort_order'] ?? $this->next_group_sort_order( $project_id ) ),
			'created_by' => get_current_user_id(),
			'created_at' => $now,
			'updated_at' => $now,
		);

		if ( '' === $clean['title'] ) {
			throw new RuntimeException( __( 'Task group title is required.', 'coordina' ) );
		}

		$result = $this->wpdb->insert( $table, $clean );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Task group could not be created.', 'coordina' ) );
		}

		$id = (int) $this->wpdb->insert_id;
		$this->log_activity( 'project', $project_id, 'task-group-created', sprintf( __( 'Created task group "%s".', 'coordina' ), $clean['title'] ) );

		return $this->find_group( $id );
	}

	/**
	 * Update a project task group.
	 *
	 * @param int                  $id Group id.
	 * @param array<string, mixed> $data Group payload.
	 * @return array<string, mixed>
	 */
	public function update_group( int $id, array $data ): array {
		$current = $this->find_group( $id );

		if ( empty( $current ) ) {
			throw new RuntimeException( __( 'Task group could not be found.', 'coordina' ) );
		}

		$project_id = (int) ( $current['project_id'] ?? 0 );

		if ( $project_id <= 0 || ! $this->access->can_edit_project( $project_id ) ) {
			throw new RuntimeException( __( 'You are not allowed to update this task group.', 'coordina' ) );
		}

		$title = sanitize_text_field( (string) ( $data['title'] ?? $current['title'] ?? '' ) );

		if ( '' === $title ) {
			throw new RuntimeException( __( 'Task group title is required.', 'coordina' ) );
		}

		$clean = array(
			'title'      => $title,
			'sort_order' => isset( $data['sort_order'] ) ? (int) $data['sort_order'] : (int) ( $current['sort_order'] ?? 0 ),
			'updated_at' => $this->now(),
		);

		$result = $this->wpdb->update( $this->table( 'task_groups' ), $clean, array( 'id' => $id ) );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Task group could not be updated.', 'coordina' ) );
		}

		if ( $title !== (string) ( $current['title'] ?? '' ) ) {
			$this->log_activity( 'project', $project_id, 'task-group-updated', sprintf( __( 'Updated task group "%s".', 'coordina' ), $title ) );
		}

		return $this->find_group( $id );
	}

	/**
	 * Delete a project task group.
	 *
	 * @param int $id Group id.
	 * @return bool
	 */
	public function delete_group( int $id ): bool {
		$current = $this->find_group( $id );

		if ( empty( $current ) ) {
			throw new RuntimeException( __( 'Task group could not be found.', 'coordina' ) );
		}

		$project_id = (int) ( $current['project_id'] ?? 0 );

		if ( $project_id <= 0 || ! $this->access->can_edit_project( $project_id ) ) {
			throw new RuntimeException( __( 'You are not allowed to delete this task group.', 'coordina' ) );
		}

		$this->wpdb->update(
			$this->table( 'tasks' ),
			array(
				'task_group_id' => 0,
				'updated_at'    => $this->now(),
			),
			array( 'task_group_id' => $id )
		);

		$result = $this->wpdb->delete( $this->table( 'task_groups' ), array( 'id' => $id ) );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Task group could not be deleted.', 'coordina' ) );
		}

		$this->log_activity( 'project', $project_id, 'task-group-deleted', sprintf( __( 'Deleted task group "%s".', 'coordina' ), (string) ( $current['title'] ?? '' ) ) );

		return true;
	}

	/**
	 * Get project task summary.
	 *
	 * @param int $project_id Project id.
	 * @return array<string, mixed>
	 */
	public function get_project_summary( int $project_id ): array {
		$table = $this->table( 'tasks' );
		list( $access_sql, $access_params ) = $this->access->task_access_where( 'id' );
		$row   = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT
					COUNT(*) AS total_count,
					SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) AS completed_count,
					SUM(CASE WHEN status = 'blocked' OR blocked = 1 THEN 1 ELSE 0 END) AS blocked_count,
					SUM(CASE WHEN due_date IS NOT NULL AND due_date < %s AND status NOT IN ('done', 'cancelled') THEN 1 ELSE 0 END) AS overdue_count
				FROM {$table}
				WHERE project_id = %d AND {$access_sql}",
				array_merge( array( current_time( 'mysql', true ), $project_id ), $access_params )
			)
		);
		$status_rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT status, COUNT(*) AS total FROM {$table} WHERE project_id = %d AND {$access_sql} GROUP BY status",
				array_merge( array( $project_id ), $access_params )
			)
		);

		$status_counts = array();
		foreach ( $status_rows ?: array() as $status_row ) {
			$status_counts[ (string) $status_row->status ] = (int) $status_row->total;
		}

		$totals = $this->row_to_array( $row );
		$total  = (int) ( $totals['total_count'] ?? 0 );

		return array(
			'total'      => $total,
			'completed'  => (int) ( $totals['completed_count'] ?? 0 ),
			'blocked'    => (int) ( $totals['blocked_count'] ?? 0 ),
			'overdue'    => (int) ( $totals['overdue_count'] ?? 0 ),
			'open'       => max( 0, $total - (int) ( $totals['completed_count'] ?? 0 ) ),
			'completion' => $total > 0 ? (int) round( ( (int) ( $totals['completed_count'] ?? 0 ) / $total ) * 100 ) : 0,
			'byStatus'   => $status_counts,
		);
	}

	/**
	 * Get My Work sections for a user.
	 *
	 * @param int $user_id User id.
	 * @return array<string, mixed>
	 */
	public function get_my_work( int $user_id ): array {
		$table    = $this->table( 'tasks' );
		$sql      = "SELECT * FROM {$table} WHERE assignee_user_id = %d AND status NOT IN ('done', 'cancelled') ORDER BY due_date ASC, updated_at DESC LIMIT 40";
		$rows     = $this->wpdb->get_results( $this->wpdb->prepare( $sql, $user_id ) );
		$items    = array_map( array( $this, 'map_item' ), $rows ?: array() );
		$today    = current_datetime()->format( 'Y-m-d' );
		$week_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days', current_time( 'timestamp', true ) ) );
		$focus_queue = $this->build_my_work_focus_queue( $items, $today, $week_ago );
		$focus_summary = array(
			'attention' => count( $focus_queue['attention'] ),
			'today'     => count( $focus_queue['today'] ),
			'upNext'    => count( $focus_queue['upNext'] ),
		);

		$sections = array(
			'blocked'          => array(),
			'dueToday'         => array(),
			'overdue'          => array(),
			'waiting'          => array(),
			'nextUp'           => array(),
			'assignedRecently' => array(),
		);

		foreach ( $items as $index => $item ) {
			$reason = $this->describe_my_work_item( $item, $today, $week_ago );
			$item['my_work_reason_key']   = $reason['reasonKey'];
			$item['my_work_reason_label'] = $reason['reasonLabel'];
			$item['my_work_reason_tone']  = $reason['reasonTone'];
			$item['my_work_guidance']     = $reason['guidance'];
			$items[ $index ]              = $item;

			$due_date = ! empty( $item['due_date'] ) ? substr( (string) $item['due_date'], 0, 10 ) : '';
			$is_blocked = ! empty( $item['blocked'] ) || 'blocked' === ( $item['status'] ?? '' );
			$is_waiting = 'waiting' === ( $item['status'] ?? '' );
			$is_overdue = '' !== $due_date && $due_date < $today;
			$is_due_today = $due_date === $today;

			if ( $is_blocked ) {
				$sections['blocked'][] = $item;
			}

			if ( $is_waiting ) {
				$sections['waiting'][] = $item;
			}

			if ( $is_due_today ) {
				$sections['dueToday'][] = $item;
			}

			if ( $is_overdue ) {
				$sections['overdue'][] = $item;
			}

			if ( ! $is_overdue && ! $is_due_today && ! $is_blocked && ! $is_waiting ) {
				$sections['nextUp'][] = $item;
			}

			if ( ! empty( $item['created_at'] ) && $item['created_at'] >= $week_ago ) {
				$sections['assignedRecently'][] = $item;
			}
		}

		foreach ( $sections as $key => $section_items ) {
			$sections[ $key ] = array_slice( $section_items, 0, 6 );
		}

		foreach ( $focus_queue as $key => $focus_items ) {
			$focus_queue[ $key ] = array_slice( $focus_items, 0, 6 );
		}

		return array(
			'items'      => $items,
			'focusQueue' => $focus_queue,
			'sections' => $sections,
			'summary'  => array(
				'open'             => count( $items ),
				'attention'        => $focus_summary['attention'],
				'blocked'          => count( $sections['blocked'] ),
				'overdue'          => count( $sections['overdue'] ),
				'dueToday'         => count( $sections['dueToday'] ),
				'waiting'          => count( $sections['waiting'] ),
				'nextUp'           => count( $sections['nextUp'] ),
				'assignedRecently' => count( $sections['assignedRecently'] ),
			),
		);
	}

	/**
	 * Build a prioritized My Work focus queue.
	 *
	 * @param array<int, array<string, mixed>> $items Task items.
	 * @param string                           $today Current local date.
	 * @param string                           $week_ago Recent assignment threshold.
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	private function build_my_work_focus_queue( array $items, string $today, string $week_ago ): array {
		$groups = array(
			'attention' => array(),
			'today'     => array(),
			'upNext'    => array(),
		);

		foreach ( $items as $item ) {
			$focus = $this->classify_my_work_focus_item( $item, $today, $week_ago );

			if ( empty( $focus['bucket'] ) ) {
				continue;
			}

			$item['my_work_reason_key']   = $focus['reasonKey'];
			$item['my_work_reason_label'] = $focus['reasonLabel'];
			$item['my_work_reason_tone']  = $focus['reasonTone'];
			$item['my_work_guidance']     = $focus['guidance'];
			$item['_my_work_sort_order']  = $focus['sortOrder'];

			$groups[ $focus['bucket'] ][] = $item;
		}

		foreach ( $groups as $key => $bucket_items ) {
			usort( $bucket_items, array( $this, 'compare_my_work_focus_items' ) );

			foreach ( $bucket_items as &$bucket_item ) {
				unset( $bucket_item['_my_work_sort_order'] );
			}

			unset( $bucket_item );
			$groups[ $key ] = $bucket_items;
		}

		return $groups;
	}

	/**
	 * Describe a My Work item with a consistent reason label and guidance.
	 *
	 * @param array<string, mixed> $item Task item.
	 * @param string               $today Current local date.
	 * @param string               $week_ago Recent assignment threshold.
	 * @return array<string, mixed>
	 */
	private function describe_my_work_item( array $item, string $today, string $week_ago ): array {
		$due_date     = ! empty( $item['due_date'] ) ? substr( (string) $item['due_date'], 0, 10 ) : '';
		$is_blocked   = ! empty( $item['blocked'] ) || 'blocked' === ( $item['status'] ?? '' );
		$is_waiting   = 'waiting' === ( $item['status'] ?? '' );
		$is_overdue   = '' !== $due_date && $due_date < $today;
		$is_due_today = $due_date === $today;
		$is_recent    = ! empty( $item['created_at'] ) && (string) $item['created_at'] >= $week_ago;

		if ( $is_overdue && $is_blocked ) {
			return array(
				'reasonKey'   => 'overdue-blocked',
				'reasonLabel' => __( 'Overdue and blocked', 'coordina' ),
				'reasonTone'  => 'danger',
				'guidance'    => __( 'Clear the blocker and reset the due plan before it slips further.', 'coordina' ),
				'sortOrder'   => 0,
				'bucket'      => 'attention',
			);
		}

		if ( $is_overdue ) {
			return array(
				'reasonKey'   => 'overdue',
				'reasonLabel' => __( 'Overdue', 'coordina' ),
				'reasonTone'  => 'danger',
				'guidance'    => __( 'Recover the due date or finish the work now.', 'coordina' ),
				'sortOrder'   => 1,
				'bucket'      => 'attention',
			);
		}

		if ( $is_blocked ) {
			return array(
				'reasonKey'   => 'blocked',
				'reasonLabel' => __( 'Blocked', 'coordina' ),
				'reasonTone'  => 'warning',
				'guidance'    => __( 'Resolve the blocker before moving to the rest of the queue.', 'coordina' ),
				'sortOrder'   => 2,
				'bucket'      => 'attention',
			);
		}

		if ( $is_waiting ) {
			return array(
				'reasonKey'   => 'waiting',
				'reasonLabel' => __( 'Waiting', 'coordina' ),
				'reasonTone'  => 'neutral',
				'guidance'    => __( 'Follow up with the blocker owner and move it back into active execution when ready.', 'coordina' ),
				'sortOrder'   => 6,
				'bucket'      => '',
			);
		}

		if ( $is_due_today ) {
			return array(
				'reasonKey'   => 'due-today',
				'reasonLabel' => __( 'Due today', 'coordina' ),
				'reasonTone'  => 'warning',
				'guidance'    => __( 'Finish this today or reset expectations clearly.', 'coordina' ),
				'sortOrder'   => 3,
				'bucket'      => 'today',
			);
		}

		if ( $is_recent ) {
			return array(
				'reasonKey'   => 'assigned-recently',
				'reasonLabel' => __( 'Assigned recently', 'coordina' ),
				'reasonTone'  => 'accent',
				'guidance'    => __( 'Review the scope and decide the next concrete step.', 'coordina' ),
				'sortOrder'   => 4,
				'bucket'      => 'upNext',
			);
		}

		return array(
			'reasonKey'   => 'up-next',
			'reasonLabel' => __( 'Coming next', 'coordina' ),
			'reasonTone'  => 'neutral',
			'guidance'    => __( 'Keep this ready after today\'s priority items are handled.', 'coordina' ),
			'sortOrder'   => 5,
			'bucket'      => 'upNext',
		);
	}

	/**
	 * Classify a task into the My Work focus queue.
	 *
	 * @param array<string, mixed> $item Task item.
	 * @param string               $today Current local date.
	 * @param string               $week_ago Recent assignment threshold.
	 * @return array<string, mixed>
	 */
	private function classify_my_work_focus_item( array $item, string $today, string $week_ago ): array {
		return $this->describe_my_work_item( $item, $today, $week_ago );
	}

	/**
	 * Sort My Work focus items by urgency, due date, priority, then recency.
	 *
	 * @param array<string, mixed> $left Left item.
	 * @param array<string, mixed> $right Right item.
	 * @return int
	 */
	private function compare_my_work_focus_items( array $left, array $right ): int {
		$sort_compare = (int) ( $left['_my_work_sort_order'] ?? 99 ) <=> (int) ( $right['_my_work_sort_order'] ?? 99 );

		if ( 0 !== $sort_compare ) {
			return $sort_compare;
		}

		$left_due  = ! empty( $left['due_date'] ) ? substr( (string) $left['due_date'], 0, 10 ) : '';
		$right_due = ! empty( $right['due_date'] ) ? substr( (string) $right['due_date'], 0, 10 ) : '';

		if ( $left_due && $right_due && $left_due !== $right_due ) {
			return $left_due <=> $right_due;
		}

		if ( $left_due !== $right_due ) {
			return $left_due ? -1 : 1;
		}

		$priority_compare = $this->my_work_priority_weight( (string) ( $right['priority'] ?? '' ) ) <=> $this->my_work_priority_weight( (string) ( $left['priority'] ?? '' ) );

		if ( 0 !== $priority_compare ) {
			return $priority_compare;
		}

		return strcmp( (string) ( $right['updated_at'] ?? '' ), (string) ( $left['updated_at'] ?? '' ) );
	}

	/**
	 * Convert workflow priority to a sortable weight.
	 *
	 * @param string $priority Priority key.
	 * @return int
	 */
	private function my_work_priority_weight( string $priority ): int {
		switch ( sanitize_key( $priority ) ) {
			case 'urgent':
				return 4;
			case 'high':
				return 3;
			case 'medium':
			case 'normal':
				return 2;
			case 'low':
				return 1;
			default:
				return 0;
		}
	}

	/**
	 * Get single task.
	 *
	 * @param int $id Task id.
	 * @return array<string, mixed>
	 */
	public function find( int $id ): array {
		if ( ! $this->access->can_view_task( $id ) ) {
			return array();
		}

		$table = $this->table( 'tasks' );
		$row   = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
		return $this->map_item( $row );
	}

	/**
	 * Create a task.
	 *
	 * @param array<string, mixed> $data Task data.
	 * @return array<string, mixed>
	 */
	public function create( array $data ): array {
		$table             = $this->table( 'tasks' );
		$now               = $this->now();
		$approval_required = ! empty( $data['approval_required'] );
		$status            = $this->normalize_workflow_status( sanitize_key( (string) ( $data['status'] ?? 'new' ) ), $approval_required );
		$project_id        = max( 0, (int) ( $data['project_id'] ?? 0 ) );
		$actual_finish_date = $this->resolve_task_actual_finish_date( $data, $status );
		$completion_percent = $this->resolve_task_completion_percent( $data, $status );

		if ( $project_id > 0 && ! $this->access->can_edit_project( $project_id ) ) {
			throw new RuntimeException( __( 'You are not allowed to create tasks for this project.', 'coordina' ) );
		}

		$clean = array(
			'project_id'        => $project_id,
			'parent_task_id'    => 0,
			'task_group_id'     => $this->normalize_task_group_id( $project_id, (int) ( $data['task_group_id'] ?? 0 ) ),
			'title'             => sanitize_text_field( (string) ( $data['title'] ?? '' ) ),
			'description'       => isset( $data['description'] ) ? wp_kses_post( (string) $data['description'] ) : '',
			'status'            => $status,
			'priority'          => sanitize_key( (string) ( $data['priority'] ?? 'normal' ) ),
			'assignee_user_id'  => (int) ( $data['assignee_user_id'] ?? 0 ),
			'reporter_user_id'  => get_current_user_id(),
			'blocked'           => ! empty( $data['blocked'] ) ? 1 : 0,
			'blocked_reason'    => sanitize_text_field( (string) ( $data['blocked_reason'] ?? '' ) ),
			'approval_required' => $approval_required ? 1 : 0,
			'start_date'        => $this->normalize_datetime( isset( $data['start_date'] ) ? (string) $data['start_date'] : null ),
			'due_date'          => $this->normalize_datetime( isset( $data['due_date'] ) ? (string) $data['due_date'] : null ),
			'completion_percent' => $completion_percent,
			'actual_finish_date' => $actual_finish_date,
			'completed_at'      => $this->resolve_task_completed_at( $status, $actual_finish_date, $now ),
			'created_at'        => $now,
			'updated_at'        => $now,
		);

		$result = $this->wpdb->insert( $table, $clean );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Task could not be created.', 'coordina' ) );
		}

		$task_id = (int) $this->wpdb->insert_id;
		$task    = $this->find( $task_id );
		( new ChecklistRepository() )->replace_from_text( 'task', $task_id, $data['checklist'] ?? '' );
		$task = $this->find( $task_id );
		( new ApprovalRepository() )->sync_for_task( $task );
		$task = $this->find( $task_id );
		$this->notify_task_assignment( array(), $task );
		$this->log_activity( 'task', $task_id, 'task_created', sprintf( __( 'Created task "%s".', 'coordina' ), $clean['title'] ) );

		return $task;
	}

	/**
	 * Update task.
	 *
	 * @param int                  $id Task id.
	 * @param array<string, mixed> $data Task data.
	 * @return array<string, mixed>
	 */
	public function update( int $id, array $data ): array {
		if ( ! $this->access->can_edit_task( $id ) ) {
			throw new RuntimeException( __( 'You are not allowed to update this task.', 'coordina' ) );
		}

		$current           = $this->find( $id );
		$can_full_edit     = $this->access->can_fully_edit_task( $id );
		$incoming          = $can_full_edit ? array_merge( $current, $data ) : $this->merge_progress_update_data( $current, $data );
		$target_project_id = max( 0, (int) ( $incoming['project_id'] ?? ( $current['project_id'] ?? 0 ) ) );

		if ( $can_full_edit && $target_project_id > 0 && ! $this->access->can_edit_project( $target_project_id ) ) {
			throw new RuntimeException( __( 'You are not allowed to move this task into that project.', 'coordina' ) );
		}

		$table             = $this->table( 'tasks' );
		$approval_required = $can_full_edit ? ! empty( $incoming['approval_required'] ) : ! empty( $current['approval_required'] );
		$status            = $this->normalize_workflow_status( sanitize_key( (string) ( $incoming['status'] ?? ( $current['status'] ?? 'new' ) ) ), $approval_required );
		$actual_finish_date = $this->resolve_task_actual_finish_date( $incoming, $status, (string) ( $current['actual_finish_date'] ?? $current['completed_at'] ?? '' ) );
		$completion_percent = $this->resolve_task_completion_percent( $incoming, $status, (int) ( $current['completion_percent'] ?? 0 ) );
		$clean             = array(
			'status'             => $status,
			'completion_percent' => $completion_percent,
			'actual_finish_date' => $actual_finish_date,
			'completed_at'       => $this->resolve_task_completed_at( $status, $actual_finish_date, (string) ( $current['completed_at'] ?? '' ) ),
			'updated_at'         => $this->now(),
		);

		if ( $can_full_edit ) {
			$clean = array_merge(
				$clean,
				array(
					'project_id'        => $target_project_id,
					'task_group_id'     => $this->normalize_task_group_id( $target_project_id, (int) ( $incoming['task_group_id'] ?? 0 ) ),
					'title'             => sanitize_text_field( (string) ( $incoming['title'] ?? '' ) ),
					'description'       => isset( $incoming['description'] ) ? wp_kses_post( (string) $incoming['description'] ) : '',
					'priority'          => sanitize_key( (string) ( $incoming['priority'] ?? 'normal' ) ),
					'assignee_user_id'  => (int) ( $incoming['assignee_user_id'] ?? 0 ),
					'blocked'           => ! empty( $incoming['blocked'] ) ? 1 : 0,
					'blocked_reason'    => sanitize_text_field( (string) ( $incoming['blocked_reason'] ?? '' ) ),
					'approval_required' => $approval_required ? 1 : 0,
					'start_date'        => $this->normalize_datetime( isset( $incoming['start_date'] ) ? (string) $incoming['start_date'] : null ),
					'due_date'          => $this->normalize_datetime( isset( $incoming['due_date'] ) ? (string) $incoming['due_date'] : null ),
				)
			);
		}

		$result = $this->wpdb->update( $table, $clean, array( 'id' => $id ) );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Task could not be updated.', 'coordina' ) );
		}

		$task = $this->find( $id );
		if ( array_key_exists( 'checklist', $data ) && $this->access->can_manage_checklists_on_context( 'task', $id ) ) {
			( new ChecklistRepository() )->replace_from_text( 'task', $id, $data['checklist'] );
			$task = $this->find( $id );
		}
		( new ApprovalRepository() )->sync_for_task( $task );
		$task = $this->find( $id );
		$this->notify_task_assignment( $current, $task );
		$this->log_update_activity( $id, $current, $task );

		return $task;
	}

	/**
	 * Bulk status update.
	 *
	 * @param int[]  $ids Task ids.
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
					return $this->access->can_edit_task( $id );
				}
			)
		);

		if ( empty( $ids ) ) {
			return 0;
		}

		$table        = $this->table( 'tasks' );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$params       = array_merge( array( sanitize_key( $status ), $this->now() ), $ids );
		$sql          = "UPDATE {$table} SET status = %s, updated_at = %s WHERE id IN ({$placeholders})";

		$result = $this->wpdb->query( $this->wpdb->prepare( $sql, $params ) );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Task statuses could not be updated.', 'coordina' ) );
		}

		return (int) $result;
	}

	/**
	 * Delete a task and its task-scoped records.
	 *
	 * @param int $id Task id.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		if ( ! $this->access->can_delete_task( $id ) ) {
			throw new RuntimeException( __( 'You are not allowed to delete this task.', 'coordina' ) );
		}

		$task = $this->find( $id );

		if ( empty( $task ) ) {
			throw new RuntimeException( __( 'Task could not be found.', 'coordina' ) );
		}

		$this->wpdb->delete( $this->table( 'task_checklist_items' ), array( 'task_id' => $id ) );
		$this->delete_context_relations( 'task', $id );

		$result = $this->wpdb->delete( $this->table( 'tasks' ), array( 'id' => $id ) );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Task could not be deleted.', 'coordina' ) );
		}

		if ( $result > 0 && (int) ( $task['project_id'] ?? 0 ) > 0 ) {
			$this->log_activity( 'project', (int) $task['project_id'], 'task_deleted', sprintf( __( 'Deleted task "%s".', 'coordina' ), (string) ( $task['title'] ?? __( 'Task', 'coordina' ) ) ) );
		}

		return $result > 0;
	}

	/**
	 * Map raw task row.
	 *
	 * @param object|null $row DB row.
	 * @return array<string, mixed>
	 */
	private function map_item( $row ): array {
		$item = $this->row_to_array( $row );

		if ( empty( $item ) ) {
			return array();
		}

		$item['project_id']        = (int) ( $item['project_id'] ?? 0 );
		$item['task_group_id']     = (int) ( $item['task_group_id'] ?? 0 );
		$item['assignee_user_id']  = (int) ( $item['assignee_user_id'] ?? 0 );
		$item['reporter_user_id']  = (int) ( $item['reporter_user_id'] ?? 0 );
		$item['blocked']           = ! empty( $item['blocked'] ) ? 1 : 0;
		$item['approval_required'] = ! empty( $item['approval_required'] ) ? 1 : 0;
		$item['actual_finish_date'] = (string) ( $item['actual_finish_date'] ?? $item['completed_at'] ?? '' );
		$item['completion_percent'] = array_key_exists( 'completion_percent', $item )
			? (int) ( $item['completion_percent'] ?? 0 )
			: ( ( 'done' === ( $item['status'] ?? '' ) || 'in-review' === ( $item['status'] ?? '' ) || ! empty( $item['actual_finish_date'] ) ) ? 100 : 0 );
		$item['assignee_label']    = $item['assignee_user_id'] ? ( get_userdata( (int) $item['assignee_user_id'] )->display_name ?? '' ) : '';
		$item['reporter_label']    = $item['reporter_user_id'] ? ( get_userdata( (int) $item['reporter_user_id'] )->display_name ?? '' ) : '';
		$item['project_label']     = $this->get_project_label( (int) ( $item['project_id'] ?? 0 ) );
		$item['task_group_label']  = $this->get_task_group_label( (int) ( $item['task_group_id'] ?? 0 ) );
		$item['project_mode']      = ! empty( $item['project_id'] ) ? 'project' : 'standalone';
		$item['approval_state']    = $this->get_approval_state( (int) ( $item['id'] ?? 0 ), ! empty( $item['approval_required'] ) );
		$item['approval_label']    = $this->humanize_status( (string) $item['approval_state'] );
		$item['is_waiting']        = 'waiting' === $item['status'];
		$item['can_edit']           = $this->access->can_fully_edit_task( (int) ( $item['id'] ?? 0 ) );
		$item['can_update_progress'] = $this->access->can_update_task_progress( (int) ( $item['id'] ?? 0 ) );
		$item['can_delete']         = $this->access->can_delete_task( (int) ( $item['id'] ?? 0 ) );
		$item['can_collaborate']    = $this->access->can_post_update_on_context( 'task', (int) ( $item['id'] ?? 0 ) );
		$item['can_post_update']    = $item['can_collaborate'];
		$item['can_attach_files']   = $this->access->can_attach_files_to_context( 'task', (int) ( $item['id'] ?? 0 ) );
		$item['can_manage_checklist'] = $this->access->can_manage_checklists_on_context( 'task', (int) ( $item['id'] ?? 0 ) );
		$item['can_toggle_checklist'] = $this->access->can_toggle_checklists_on_context( 'task', (int) ( $item['id'] ?? 0 ) );
		$checklist_collection         = $this->get_checklist_collection( (int) ( $item['id'] ?? 0 ) );
		$item['checklists']           = is_array( $checklist_collection['checklists'] ?? null ) ? $checklist_collection['checklists'] : array();
		$item['checklist']            = is_array( $checklist_collection['items'] ?? null ) ? $checklist_collection['items'] : array();
		$item['checklist_text']       = $this->checklist_to_text( $item['checklist'] );
		$item['checklist_summary']    = is_array( $checklist_collection['summary'] ?? null ) ? $checklist_collection['summary'] : $this->get_checklist_summary( $item['checklist'] );
		return $item;
	}

	/**
	 * Resolve task approval state.
	 *
	 * @param int  $task_id Task id.
	 * @param bool $approval_required Whether approval is required.
	 * @return string
	 */
	private function get_approval_state( int $task_id, bool $approval_required ): string {
		if ( ! $approval_required || $task_id <= 0 ) {
			return 'not-required';
		}

		$approval = ( new ApprovalRepository() )->get_latest_for_object( 'task', $task_id );

		if ( empty( $approval ) ) {
			return 'pending';
		}

		return sanitize_key( (string) ( $approval['status'] ?? 'pending' ) );
	}

	/**
	 * Humanize a status key.
	 *
	 * @param string $status Status key.
	 * @return string
	 */
	private function humanize_status( string $status ): string {
		return ucwords( str_replace( '-', ' ', $status ) );
	}

	/**
	 * Normalize workflow status for approval-gated tasks.
	 *
	 * @param string $status Requested status.
	 * @param bool   $approval_required Whether approval is required.
	 * @return string
	 */
	private function normalize_workflow_status( string $status, bool $approval_required ): string {
		if ( $approval_required && 'done' === $status ) {
			return 'in-review';
		}

		return $status;
	}

	/**
	 * Merge restricted progress-update fields into the current task payload.
	 *
	 * @param array<string, mixed> $current Current task values.
	 * @param array<string, mixed> $data Incoming payload.
	 * @return array<string, mixed>
	 */
	private function merge_progress_update_data( array $current, array $data ): array {
		$merged  = $current;
		$allowed = array( 'status', 'completion_percent', 'actual_finish_date', 'completed_at' );

		foreach ( $allowed as $field ) {
			if ( array_key_exists( $field, $data ) ) {
				$merged[ $field ] = $data[ $field ];
			}
		}

		return $merged;
	}

	/**
	 * Resolve task completion percentage.
	 *
	 * @param array<string, mixed> $data Task payload.
	 * @param string               $status Normalized status.
	 * @param int                  $current Current value.
	 * @return int
	 */
	private function resolve_task_completion_percent( array $data, string $status, int $current = 0 ): int {
		$percent = array_key_exists( 'completion_percent', $data ) ? (int) $data['completion_percent'] : $current;
		$percent = min( 100, max( 0, $percent ) );

		if ( in_array( $status, array( 'done', 'in-review' ), true ) ) {
			return 100;
		}

		return $percent;
	}

	/**
	 * Resolve task actual finish date.
	 *
	 * @param array<string, mixed> $data Task payload.
	 * @param string               $status Normalized status.
	 * @param string               $current Current value.
	 * @return string|null
	 */
	private function resolve_task_actual_finish_date( array $data, string $status, string $current = '' ): ?string {
		$has_explicit_value = array_key_exists( 'actual_finish_date', $data ) || array_key_exists( 'completed_at', $data );
		$raw_value          = array_key_exists( 'actual_finish_date', $data ) ? $data['actual_finish_date'] : ( $data['completed_at'] ?? null );

		if ( $has_explicit_value ) {
			$normalized = $this->normalize_datetime( null !== $raw_value ? (string) $raw_value : null );

			if ( null !== $normalized ) {
				return $normalized;
			}
		}

		if ( in_array( $status, array( 'done', 'in-review' ), true ) ) {
			return '' !== $current ? $current : $this->now();
		}

		return null;
	}

	/**
	 * Resolve task completion timestamp.
	 *
	 * @param string      $status Normalized status.
	 * @param string|null $actual_finish_date Actual finish date.
	 * @param string      $current Current completion timestamp.
	 * @return string|null
	 */
	private function resolve_task_completed_at( string $status, ?string $actual_finish_date, string $current = '' ): ?string {
		if ( 'done' !== $status ) {
			return null;
		}

		if ( null !== $actual_finish_date && '' !== $actual_finish_date ) {
			return $actual_finish_date;
		}

		return '' !== $current ? $current : $this->now();
	}

	/**
	 * Log meaningful task update events.
	 *
	 * @param int                  $id Task id.
	 * @param array<string, mixed> $current Current task data.
	 * @param array<string, mixed> $updated Updated task data.
	 * @return void
	 */
	private function log_update_activity( int $id, array $current, array $updated ): void {
		if ( (string) ( $current['title'] ?? '' ) !== (string) ( $updated['title'] ?? '' ) ) {
			$this->log_activity( 'task', $id, 'task_title_changed', __( 'Renamed the task.', 'coordina' ) );
		}

		if ( (string) ( $current['description'] ?? '' ) !== (string) ( $updated['description'] ?? '' ) ) {
			$this->log_activity( 'task', $id, 'task_description_changed', __( 'Updated the task description.', 'coordina' ) );
		}

		if ( (string) ( $current['status'] ?? '' ) !== (string) ( $updated['status'] ?? '' ) ) {
			$this->log_activity( 'task', $id, 'task_status_changed', sprintf( __( 'Changed task status to %s.', 'coordina' ), $this->humanize_status( (string) ( $updated['status'] ?? '' ) ) ) );
		}

		if ( (string) ( $current['priority'] ?? '' ) !== (string) ( $updated['priority'] ?? '' ) ) {
			$this->log_activity( 'task', $id, 'task_priority_changed', sprintf( __( 'Changed task priority to %s.', 'coordina' ), $this->humanize_status( (string) ( $updated['priority'] ?? '' ) ) ) );
		}

		if ( (int) ( $current['assignee_user_id'] ?? 0 ) !== (int) ( $updated['assignee_user_id'] ?? 0 ) ) {
			$this->log_activity( 'task', $id, 'task_assignee_changed', sprintf( __( 'Changed task assignee to %s.', 'coordina' ), $updated['assignee_label'] ?: __( 'Unassigned', 'coordina' ) ) );
		}

		if ( (int) ( $current['project_id'] ?? 0 ) !== (int) ( $updated['project_id'] ?? 0 ) ) {
			$this->log_activity( 'task', $id, 'task_project_changed', __( 'Changed the linked project.', 'coordina' ) );
		}

		if ( (int) ( $current['task_group_id'] ?? 0 ) !== (int) ( $updated['task_group_id'] ?? 0 ) ) {
			$this->log_activity( 'task', $id, 'task_group_changed', __( 'Changed the task group.', 'coordina' ) );
		}

		if ( (string) ( $current['start_date'] ?? '' ) !== (string) ( $updated['start_date'] ?? '' ) ) {
			$this->log_activity( 'task', $id, 'task_start_date_changed', __( 'Changed task start date.', 'coordina' ) );
		}

		if ( (string) ( $current['due_date'] ?? '' ) !== (string) ( $updated['due_date'] ?? '' ) ) {
			$this->log_activity( 'task', $id, 'task_due_date_changed', __( 'Changed task due date.', 'coordina' ) );
		}

		if ( (int) ( $current['completion_percent'] ?? 0 ) !== (int) ( $updated['completion_percent'] ?? 0 ) ) {
			$this->log_activity( 'task', $id, 'task_completion_changed', sprintf( __( 'Changed task completion to %d%%.', 'coordina' ), (int) ( $updated['completion_percent'] ?? 0 ) ) );
		}

		if ( (string) ( $current['actual_finish_date'] ?? '' ) !== (string) ( $updated['actual_finish_date'] ?? '' ) ) {
			$this->log_activity( 'task', $id, 'task_actual_finish_changed', __( 'Changed the actual finish date.', 'coordina' ) );
		}

		if ( (int) ( $current['blocked'] ?? 0 ) !== (int) ( $updated['blocked'] ?? 0 ) ) {
			$this->log_activity( 'task', $id, 'task_blocked_changed', ! empty( $updated['blocked'] ) ? __( 'Marked this task as blocked.', 'coordina' ) : __( 'Cleared the blocked state on this task.', 'coordina' ) );
		}

		if ( (string) ( $current['blocked_reason'] ?? '' ) !== (string) ( $updated['blocked_reason'] ?? '' ) ) {
			$this->log_activity( 'task', $id, 'task_blocked_reason_changed', __( 'Updated the blocked reason.', 'coordina' ) );
		}

		if ( (int) ( $current['approval_required'] ?? 0 ) !== (int) ( $updated['approval_required'] ?? 0 ) ) {
			$this->log_activity( 'task', $id, 'task_approval_requirement_changed', ! empty( $updated['approval_required'] ) ? __( 'Marked this task as requiring approval.', 'coordina' ) : __( 'Removed the approval requirement from this task.', 'coordina' ) );
		}

		if ( (string) ( $current['checklist_text'] ?? '' ) !== (string) ( $updated['checklist_text'] ?? '' ) ) {
			$this->log_activity( 'task', $id, 'task_checklist_changed', __( 'Updated the checklist.', 'coordina' ) );
		}
	}

	/**
	 * Create a notification when a task is assigned or reassigned.
	 *
	 * @param array<string, mixed> $current Previous task state.
	 * @param array<string, mixed> $task Updated task state.
	 * @return void
	 */
	private function notify_task_assignment( array $current, array $task ): void {
		$previous_assignee = (int) ( $current['assignee_user_id'] ?? 0 );
		$assignee_id       = (int) ( $task['assignee_user_id'] ?? 0 );
		$reporter_id       = (int) ( $task['reporter_user_id'] ?? 0 );

		if ( $assignee_id <= 0 || $assignee_id === $previous_assignee || $assignee_id === $reporter_id ) {
			return;
		}

		$project_label = ! empty( $task['project_label'] ) ? (string) $task['project_label'] : __( 'Standalone', 'coordina' );
		$due_label     = ! empty( $task['due_date'] ) ? substr( (string) $task['due_date'], 0, 10 ) : '';
		$title         = 0 === $previous_assignee ? __( 'You were assigned a task', 'coordina' ) : __( 'A task was reassigned to you', 'coordina' );
		$body          = sprintf( __( '%1$s in %2$s%3$s', 'coordina' ), (string) ( $task['title'] ?? __( 'Task', 'coordina' ) ), $project_label, $due_label ? sprintf( __( ' · Due %s', 'coordina' ), $due_label ) : '' );
		$url           = $this->task_admin_url( $task );

		( new NotificationRepository() )->create( $assignee_id, 'task-assigned', $title, $body, $url );
	}

	/**
	 * Build an admin URL for a task.
	 *
	 * @param array<string, mixed> $task Task data.
	 * @return string
	 */
	private function task_admin_url( array $task ): string {
		$args = array(
			'page'    => 'coordina-task',
			'task_id' => (int) ( $task['id'] ?? 0 ),
		);

		if ( (int) ( $task['project_id'] ?? 0 ) > 0 ) {
			$args['project_id']  = (int) $task['project_id'];
			$args['project_tab'] = 'work';
		}

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Sanitize order by field.
	 *
	 * @param string $value Requested field.
	 * @return string
	 */
	private function sanitize_order_by( string $value ): string {
		$allowed = array( 'title', 'status', 'priority', 'due_date', 'updated_at', 'created_at' );
		return in_array( $value, $allowed, true ) ? $value : 'updated_at';
	}

	/**
	 * Find a task group by id.
	 *
	 * @param int $id Group id.
	 * @return array<string, mixed>
	 */
	private function find_group( int $id ): array {
		$table = $this->table( 'task_groups' );
		$row   = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
		return $this->map_group( $row );
	}

	/**
	 * Map task group row.
	 *
	 * @param object|null $row DB row.
	 * @return array<string, mixed>
	 */
	private function map_group( $row ): array {
		$item = $this->row_to_array( $row );

		if ( empty( $item ) ) {
			return array();
		}

		$item['id']         = (int) ( $item['id'] ?? 0 );
		$item['project_id'] = (int) ( $item['project_id'] ?? 0 );
		$item['sort_order'] = (int) ( $item['sort_order'] ?? 0 );
		$item['created_by'] = (int) ( $item['created_by'] ?? 0 );

		return $item;
	}

	/**
	 * Determine the next group sort order for a project.
	 */
	private function next_group_sort_order( int $project_id ): int {
		$table = $this->table( 'task_groups' );
		return (int) $this->wpdb->get_var( $this->wpdb->prepare( "SELECT COALESCE(MAX(sort_order), 0) + 10 FROM {$table} WHERE project_id = %d", $project_id ) );
	}

	/**
	 * Validate task group belongs to the same project.
	 */
	private function normalize_task_group_id( int $project_id, int $task_group_id ): int {
		if ( $project_id <= 0 || $task_group_id <= 0 ) {
			return 0;
		}

		$table = $this->table( 'task_groups' );
		$found = (int) $this->wpdb->get_var( $this->wpdb->prepare( "SELECT id FROM {$table} WHERE id = %d AND project_id = %d", $task_group_id, $project_id ) );

		return $found > 0 ? $found : 0;
	}

	/**
	 * Resolve task group label.
	 */
	private function get_task_group_label( int $task_group_id ): string {
		if ( $task_group_id <= 0 ) {
			return '';
		}

		$table = $this->table( 'task_groups' );
		$title = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT title FROM {$table} WHERE id = %d", $task_group_id ) );

		return $title ? (string) $title : '';
	}

	/**
	 * Fetch checklist items for a task.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function get_checklist_collection( int $task_id ): array {
		if ( $task_id <= 0 ) {
			return array(
				'checklists' => array(),
				'items'      => array(),
				'summary'    => array(
					'total' => 0,
					'done'  => 0,
					'open'  => 0,
				),
			);
		}

		return ( new ChecklistRepository() )->get_items(
			array(
				'object_type' => 'task',
				'object_id'   => $task_id,
			)
		);
	}

	/**
	 * Convert checklist items to textarea lines.
	 *
	 * @param array<int, array<string, mixed>> $items Checklist rows.
	 */
	private function checklist_to_text( array $items ): string {
		return implode(
			"\n",
			array_map(
				static function ( array $item ): string {
					return ( ! empty( $item['is_done'] ) ? '[x] ' : '[ ] ' ) . (string) ( $item['item_text'] ?? '' );
				},
				$items
			)
		);
	}

	/**
	 * Build checklist summary.
	 *
	 * @param array<int, array<string, mixed>> $items Checklist rows.
	 * @return array<string, int>
	 */
	private function get_checklist_summary( array $items ): array {
		$total = count( $items );
		$done  = count(
			array_filter(
				$items,
				static function ( array $item ): bool {
					return ! empty( $item['is_done'] );
				}
			)
		);

		return array(
			'total' => $total,
			'done'  => $done,
			'open'  => max( 0, $total - $done ),
		);
	}

	/**
	 * Resolve project label.
	 *
	 * @param int $project_id Project id.
	 * @return string
	 */
	protected function get_project_label( int $project_id, string $fallback = '' ): string {
		if ( $project_id <= 0 ) {
			return __( 'Standalone', 'coordina' );
		}

		$projects_table = $this->table( 'projects' );
		$title          = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT title FROM {$projects_table} WHERE id = %d", $project_id ) );

		return $title ? (string) $title : __( 'Project task', 'coordina' );
	}
}
