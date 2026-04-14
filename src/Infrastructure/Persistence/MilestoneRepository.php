<?php
/**
 * Project milestone repository.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Persistence;

use RuntimeException;

final class MilestoneRepository extends AbstractRepository {
	/**
	 * Fetch paginated milestones.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array<string, mixed>
	 */
	public function get_items( array $args ): array {
		$table      = $this->table( 'milestones' );
		$page       = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page   = max( 1, min( 50, (int) ( $args['per_page'] ?? 10 ) ) );
		$search     = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';
		$status     = isset( $args['status'] ) ? sanitize_key( (string) $args['status'] ) : '';
		$project_id = isset( $args['project_id'] ) ? max( 0, (int) $args['project_id'] ) : 0;
		$owner_id   = isset( $args['owner_user_id'] ) ? max( 0, (int) $args['owner_user_id'] ) : 0;
		$order_by   = $this->sanitize_order_by( (string) ( $args['orderby'] ?? 'due_date' ) );
		$order      = 'desc' === strtolower( (string) ( $args['order'] ?? 'asc' ) ) ? 'DESC' : 'ASC';
		$offset     = ( $page - 1 ) * $per_page;
		$where      = array( '1=1' );
		$params     = array();
		list( $access_sql, $access_params ) = $this->access->project_access_where( 'project_id' );
		$where[] = $access_sql;
		$params  = array_merge( $params, $access_params );

		if ( '' !== $search ) {
			$where[]  = '(title LIKE %s OR notes LIKE %s)';
			$like     = '%' . $this->wpdb->esc_like( $search ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		if ( '' !== $status ) {
			$where[]  = 'status = %s';
			$params[] = $status;
		}

		if ( $project_id > 0 ) {
			$where[]  = 'project_id = %d';
			$params[] = $project_id;
		}

		if ( $owner_id > 0 ) {
			$where[]  = 'owner_user_id = %d';
			$params[] = $owner_id;
		}

		$where_sql = implode( ' AND ', $where );
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		$list_sql  = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$order_by} {$order}, id {$order} LIMIT %d OFFSET %d";
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
	 * Get milestones for a project.
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
	 * Get milestone summary for a project.
	 *
	 * @param int $project_id Project id.
	 * @return array<string, mixed>
	 */
	public function get_project_summary( int $project_id ): array {
		$table = $this->table( 'milestones' );
		list( $access_sql, $access_params ) = $this->access->project_access_where( 'project_id' );
		$row   = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT
					COUNT(*) AS total_count,
					SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
					SUM(CASE WHEN due_date IS NOT NULL AND due_date < %s AND status NOT IN ('completed', 'skipped') THEN 1 ELSE 0 END) AS overdue_count,
					SUM(CASE WHEN dependency_flag = 1 THEN 1 ELSE 0 END) AS dependency_count,
					MIN(CASE WHEN status NOT IN ('completed', 'skipped') THEN due_date ELSE NULL END) AS next_due_date
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

		$summary = $this->row_to_array( $row );
		$total   = (int) ( $summary['total_count'] ?? 0 );

		return array(
			'total'        => $total,
			'completed'    => (int) ( $summary['completed_count'] ?? 0 ),
			'open'         => max( 0, $total - (int) ( $summary['completed_count'] ?? 0 ) ),
			'overdue'      => (int) ( $summary['overdue_count'] ?? 0 ),
			'dependencies' => (int) ( $summary['dependency_count'] ?? 0 ),
			'nextDueDate'  => (string) ( $summary['next_due_date'] ?? '' ),
			'byStatus'     => $status_counts,
		);
	}

	/**
	 * Find one milestone.
	 *
	 * @param int $id Milestone id.
	 * @return array<string, mixed>
	 */
	public function find( int $id ): array {
		$row = $this->wpdb->get_row( $this->wpdb->prepare( 'SELECT * FROM ' . $this->table( 'milestones' ) . ' WHERE id = %d', $id ) );
		$item = $this->map_item( $row );

		if ( empty( $item ) || ! $this->access->can_view_project( (int) ( $item['project_id'] ?? 0 ) ) ) {
			return array();
		}

		return $item;
	}

	/**
	 * Create a milestone.
	 *
	 * @param array<string, mixed> $data Milestone data.
	 * @return array<string, mixed>
	 */
	public function create( array $data ): array {
		$clean = $this->clean_data( $data, true );

		if ( ! $this->access->can_edit_project( (int) $clean['project_id'] ) ) {
			throw new RuntimeException( __( 'You are not allowed to create milestones for this project.', 'coordina' ) );
		}

		$now                 = $this->now();
		$clean['created_by'] = get_current_user_id();
		$clean['created_at'] = $now;
		$clean['updated_at'] = $now;
		$result              = $this->wpdb->insert( $this->table( 'milestones' ), $clean );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Milestone could not be created.', 'coordina' ) );
		}

		$milestone_id = (int) $this->wpdb->insert_id;
		$this->log_activity( 'milestone', $milestone_id, 'milestone_created', sprintf( __( 'Created milestone "%s".', 'coordina' ), $clean['title'] ) );

		return $this->find( $milestone_id );
	}

	/**
	 * Update a milestone.
	 *
	 * @param int                  $id Milestone id.
	 * @param array<string, mixed> $data Milestone data.
	 * @return array<string, mixed>
	 */
	public function update( int $id, array $data ): array {
		$current = $this->find( $id );
		if ( empty( $current ) || ! $this->access->can_edit_project( (int) ( $current['project_id'] ?? 0 ) ) ) {
			throw new RuntimeException( __( 'You are not allowed to update this milestone.', 'coordina' ) );
		}

		$clean = $this->clean_data( $data, false );

		if ( (int) ( $clean['project_id'] ?? 0 ) > 0 && ! $this->access->can_edit_project( (int) $clean['project_id'] ) ) {
			throw new RuntimeException( __( 'You are not allowed to move this milestone into that project.', 'coordina' ) );
		}

		$clean['updated_at'] = $this->now();
		$result = $this->wpdb->update( $this->table( 'milestones' ), $clean, array( 'id' => $id ) );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Milestone could not be updated.', 'coordina' ) );
		}

		$this->log_update_activity( $id, $current, $clean );

		return $this->find( $id );
	}

	/**
	 * Bulk status update.
	 *
	 * @param int[]  $ids Milestone ids.
	 * @param string $status New status.
	 * @return int
	 */
	public function bulk_update_status( array $ids, string $status ): int {
		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );

		if ( empty( $ids ) ) {
			return 0;
		}

		$allowed_ids = array_values(
			array_filter(
				$ids,
				function ( int $id ): bool {
					$item = $this->find( $id );
					return ! empty( $item ) && $this->access->can_edit_project( (int) ( $item['project_id'] ?? 0 ) );
				}
			)
		);

		if ( empty( $allowed_ids ) ) {
			return 0;
		}

		$table        = $this->table( 'milestones' );
		$placeholders = implode( ',', array_fill( 0, count( $allowed_ids ), '%d' ) );
		$params       = array_merge( array( sanitize_key( $status ), $this->now() ), $allowed_ids );
		$sql          = "UPDATE {$table} SET status = %s, updated_at = %s WHERE id IN ({$placeholders})";
		$result       = $this->wpdb->query( $this->wpdb->prepare( $sql, $params ) );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Milestone statuses could not be updated.', 'coordina' ) );
		}

		foreach ( $allowed_ids as $id ) {
			$this->log_activity( 'milestone', $id, 'milestone_status_changed', sprintf( __( 'Changed milestone status to %s.', 'coordina' ), sanitize_key( $status ) ) );
		}

		return (int) $result;
	}

	/**
	 * Delete a milestone and its milestone-scoped records.
	 *
	 * @param int $id Milestone id.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		$current = $this->find( $id );

		if ( empty( $current ) || ! $this->access->can_delete_milestone( $id ) ) {
			throw new RuntimeException( __( 'You are not allowed to delete this milestone.', 'coordina' ) );
		}

		$this->delete_context_relations( 'milestone', $id );
		$result = $this->wpdb->delete( $this->table( 'milestones' ), array( 'id' => $id ) );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Milestone could not be deleted.', 'coordina' ) );
		}

		if ( $result > 0 && (int) ( $current['project_id'] ?? 0 ) > 0 ) {
			$this->log_activity( 'project', (int) $current['project_id'], 'milestone_deleted', sprintf( __( 'Deleted milestone "%s".', 'coordina' ), (string) ( $current['title'] ?? __( 'Milestone', 'coordina' ) ) ) );
		}

		return $result > 0;
	}

	/**
	 * Sanitize order by field.
	 *
	 * @param string $value Requested field.
	 * @return string
	 */
	private function sanitize_order_by( string $value ): string {
		$allowed = array( 'title', 'status', 'owner_user_id', 'due_date', 'completion_percent', 'updated_at', 'created_at' );
		return in_array( $value, $allowed, true ) ? $value : 'due_date';
	}

	/**
	 * Normalize milestone data.
	 *
	 * @param array<string, mixed> $data Raw data.
	 * @param bool                 $is_create Whether this is a create operation.
	 * @return array<string, mixed>
	 */
	private function clean_data( array $data, bool $is_create ): array {
		unset( $is_create );

		return array(
			'project_id'          => max( 0, (int) ( $data['project_id'] ?? 0 ) ),
			'title'               => sanitize_text_field( (string) ( $data['title'] ?? '' ) ),
			'status'              => sanitize_key( (string) ( $data['status'] ?? 'planned' ) ),
			'owner_user_id'       => max( 0, (int) ( $data['owner_user_id'] ?? 0 ) ),
			'due_date'            => $this->normalize_datetime( isset( $data['due_date'] ) ? (string) $data['due_date'] : null ),
			'completion_percent'  => min( 100, max( 0, (int) ( $data['completion_percent'] ?? 0 ) ) ),
			'dependency_flag'     => ! empty( $data['dependency_flag'] ) ? 1 : 0,
			'notes'               => isset( $data['notes'] ) ? wp_kses_post( (string) $data['notes'] ) : '',
		);
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

		$item['project_id']         = (int) ( $item['project_id'] ?? 0 );
		$item['owner_user_id']      = (int) ( $item['owner_user_id'] ?? 0 );
		$item['completion_percent'] = (int) ( $item['completion_percent'] ?? 0 );
		$item['dependency_flag']    = ! empty( $item['dependency_flag'] ) ? 1 : 0;
		$item['owner_label']        = $this->get_user_label( (int) $item['owner_user_id'] );
		$item['project_label']      = $this->get_project_label( (int) $item['project_id'] );
		$item['can_edit']           = $this->access->can_edit_project( (int) $item['project_id'] );
		$item['can_delete']         = $this->access->can_delete_milestone( (int) ( $item['id'] ?? 0 ) );
		$item['can_collaborate']    = $this->access->can_post_update_on_context( 'milestone', (int) ( $item['id'] ?? 0 ) );
		$item['can_post_update']    = $item['can_collaborate'];
		$item['can_attach_files']   = $this->access->can_attach_files_to_context( 'milestone', (int) ( $item['id'] ?? 0 ) );

		return $item;
	}

	/**
	 * Log meaningful update events.
	 *
	 * @param int                  $id Milestone id.
	 * @param array<string, mixed> $current Current milestone.
	 * @param array<string, mixed> $clean New data.
	 * @return void
	 */
	private function log_update_activity( int $id, array $current, array $clean ): void {
		if ( (string) ( $current['status'] ?? '' ) !== (string) ( $clean['status'] ?? '' ) ) {
			$this->log_activity( 'milestone', $id, 'milestone_status_changed', sprintf( __( 'Changed milestone status to %s.', 'coordina' ), (string) $clean['status'] ) );
		}

		if ( (string) ( $current['due_date'] ?? '' ) !== (string) ( $clean['due_date'] ?? '' ) ) {
			$this->log_activity( 'milestone', $id, 'milestone_due_date_changed', __( 'Changed milestone due date.', 'coordina' ) );
		}

		if ( (int) ( $current['completion_percent'] ?? 0 ) !== (int) ( $clean['completion_percent'] ?? 0 ) ) {
			$this->log_activity( 'milestone', $id, 'milestone_completion_changed', sprintf( __( 'Changed milestone completion to %d%%.', 'coordina' ), (int) $clean['completion_percent'] ) );
		}
	}
}
