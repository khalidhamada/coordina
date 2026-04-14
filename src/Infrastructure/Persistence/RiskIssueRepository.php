<?php
/**
 * Risk and issue repository.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Persistence;

use RuntimeException;

final class RiskIssueRepository extends AbstractRepository {
	/**
	 * Fetch paginated risks and issues.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array<string, mixed>
	 */
	public function get_items( array $args ): array {
		$table      = $this->table( 'risks_issues' );
		$page       = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page   = max( 1, min( 50, (int) ( $args['per_page'] ?? 10 ) ) );
		$search     = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';
		$status     = isset( $args['status'] ) ? sanitize_text_field( (string) $args['status'] ) : '';
		$project_id = isset( $args['project_id'] ) ? max( 0, (int) $args['project_id'] ) : 0;
		$type       = isset( $args['object_type'] ) ? sanitize_key( (string) $args['object_type'] ) : '';
		$owner_id   = isset( $args['owner_user_id'] ) ? max( 0, (int) $args['owner_user_id'] ) : 0;
		$severity   = isset( $args['severity'] ) ? sanitize_key( (string) $args['severity'] ) : '';
		$order_by   = $this->sanitize_order_by( (string) ( $args['orderby'] ?? 'updated_at' ) );
		$order      = 'asc' === strtolower( (string) ( $args['order'] ?? 'desc' ) ) ? 'ASC' : 'DESC';
		$offset     = ( $page - 1 ) * $per_page;
		$where      = array( '1=1' );
		$params     = array();
		list( $access_sql, $access_params ) = $this->access->risk_issue_access_where( 'id' );
		$where[] = $access_sql;
		$params  = array_merge( $params, $access_params );

		if ( '' !== $search ) {
			$where[]  = '(title LIKE %s OR description LIKE %s OR mitigation_plan LIKE %s)';
			$like     = '%' . $this->wpdb->esc_like( $search ) . '%';
			$params[] = $like;
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

		if ( '' !== $type ) {
			$where[]  = 'object_type = %s';
			$params[] = $type;
		}

		if ( $owner_id > 0 ) {
			$where[]  = 'owner_user_id = %d';
			$params[] = $owner_id;
		}

		if ( '' !== $severity ) {
			$where[]  = 'severity = %s';
			$params[] = $severity;
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
	 * Get risks and issues for a project.
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
	 * Get summary counts for a project.
	 *
	 * @param int $project_id Project id.
	 * @return array<string, mixed>
	 */
	public function get_project_summary( int $project_id ): array {
		$table       = $this->table( 'risks_issues' );
		list( $access_sql, $access_params ) = $this->access->risk_issue_access_where( 'id' );
		$summary_row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT
					COUNT(*) AS total_count,
					SUM(CASE WHEN object_type = 'risk' THEN 1 ELSE 0 END) AS risk_count,
					SUM(CASE WHEN object_type = 'issue' THEN 1 ELSE 0 END) AS issue_count,
					SUM(CASE WHEN severity IN ('high', 'critical') THEN 1 ELSE 0 END) AS high_severity_count,
					SUM(CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END) AS resolved_count,
					SUM(CASE WHEN status = 'escalated' THEN 1 ELSE 0 END) AS escalated_count
				FROM {$table}
				WHERE project_id = %d AND {$access_sql}",
				array_merge( array( $project_id ), $access_params )
			)
		);
		$status_rows  = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT status, COUNT(*) AS total FROM {$table} WHERE project_id = %d AND {$access_sql} GROUP BY status",
				array_merge( array( $project_id ), $access_params )
			)
		);

		$summary      = $this->row_to_array( $summary_row );
		$status_counts = array();
		foreach ( $status_rows ?: array() as $status_row ) {
			$status_counts[ (string) $status_row->status ] = (int) $status_row->total;
		}

		return array(
			'total'        => (int) ( $summary['total_count'] ?? 0 ),
			'risks'        => (int) ( $summary['risk_count'] ?? 0 ),
			'issues'       => (int) ( $summary['issue_count'] ?? 0 ),
			'highSeverity' => (int) ( $summary['high_severity_count'] ?? 0 ),
			'resolved'     => (int) ( $summary['resolved_count'] ?? 0 ),
			'escalated'    => (int) ( $summary['escalated_count'] ?? 0 ),
			'byStatus'     => $status_counts,
		);
	}

	/**
	 * Get one record.
	 *
	 * @param int $id Record id.
	 * @return array<string, mixed>
	 */
	public function find( int $id ): array {
		if ( ! $this->access->can_view_risk_issue( $id ) ) {
			return array();
		}

		$table = $this->table( 'risks_issues' );
		$row   = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );

		return $this->map_item( $row );
	}

	/**
	 * Create a record.
	 *
	 * @param array<string, mixed> $data Record data.
	 * @return array<string, mixed>
	 */
	public function create( array $data ): array {
		$table       = $this->table( 'risks_issues' );
		$now         = $this->now();
		$clean       = $this->clean_data( $data, true );

		if ( (int) ( $clean['project_id'] ?? 0 ) > 0 && ! $this->access->can_edit_project( (int) $clean['project_id'] ) ) {
			throw new RuntimeException( __( 'You are not allowed to create a risk or issue for this project.', 'coordina' ) );
		}

		$clean['created_by'] = get_current_user_id();
		$clean['created_at'] = $now;
		$clean['updated_at'] = $now;

		$result = $this->wpdb->insert( $table, $clean );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Risk or issue could not be created.', 'coordina' ) );
		}

		$record = $this->find( (int) $this->wpdb->insert_id );
		$this->log_create_activity( $record );

		return $record;
	}

	/**
	 * Update a record.
	 *
	 * @param int                  $id Record id.
	 * @param array<string, mixed> $data Record data.
	 * @return array<string, mixed>
	 */
	public function update( int $id, array $data ): array {
		if ( ! $this->access->can_edit_risk_issue( $id ) ) {
			throw new RuntimeException( __( 'You are not allowed to update this risk or issue.', 'coordina' ) );
		}

		$current = $this->find( $id );
		$target_project_id = max( 0, (int) ( $data['project_id'] ?? ( $current['project_id'] ?? 0 ) ) );

		if ( $target_project_id > 0 && ! $this->access->can_edit_project( $target_project_id ) ) {
			throw new RuntimeException( __( 'You are not allowed to move this risk or issue into that project.', 'coordina' ) );
		}

		$table = $this->table( 'risks_issues' );
		$clean = $this->clean_data( $data, false );
		$clean['updated_at'] = $this->now();

		$result = $this->wpdb->update( $table, $clean, array( 'id' => $id ) );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Risk or issue could not be updated.', 'coordina' ) );
		}

		$record = $this->find( $id );
		$this->log_update_activity( $current, $record );

		return $record;
	}

	/**
	 * Bulk status update.
	 *
	 * @param int[]  $ids Record ids.
	 * @param string $status New status.
	 * @return int
	 */
	public function bulk_update_status( array $ids, string $status ): int {
		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );

		if ( empty( $ids ) ) {
			return 0;
		}

		if ( ! $this->access->has_full_project_access() ) {
			$ids = array_values(
				array_filter(
					$ids,
					function ( int $id ): bool {
						return $this->access->can_edit_risk_issue( $id );
					}
				)
			);
		}

		if ( empty( $ids ) ) {
			return 0;
		}

		$table        = $this->table( 'risks_issues' );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$resolved_at  = in_array( $status, array( 'resolved', 'closed' ), true ) ? $this->now() : null;
		$params       = array_merge( array( sanitize_key( $status ), $resolved_at, $this->now() ), $ids );
		$sql          = "UPDATE {$table} SET status = %s, resolved_at = %s, updated_at = %s WHERE id IN ({$placeholders})";

		$result = $this->wpdb->query( $this->wpdb->prepare( $sql, $params ) );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Risk and issue statuses could not be updated.', 'coordina' ) );
		}

		return (int) $result;
	}

	/**
	 * Delete a risk or issue and its related records.
	 *
	 * @param int $id Record id.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		if ( ! $this->access->can_delete_risk_issue( $id ) ) {
			throw new RuntimeException( __( 'You are not allowed to delete this risk or issue.', 'coordina' ) );
		}

		$record = $this->find( $id );

		if ( empty( $record ) ) {
			throw new RuntimeException( __( 'Risk or issue could not be found.', 'coordina' ) );
		}

		$this->delete_context_relations( (string) ( $record['object_type'] ?? 'risk' ), $id );
		$result = $this->wpdb->delete( $this->table( 'risks_issues' ), array( 'id' => $id ) );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Risk or issue could not be deleted.', 'coordina' ) );
		}

		if ( $result > 0 && (int) ( $record['project_id'] ?? 0 ) > 0 ) {
			$this->log_activity( 'project', (int) $record['project_id'], 'risk_issue_deleted', sprintf( __( 'Deleted %s "%s".', 'coordina' ), (string) ( $record['object_type'] ?? __( 'record', 'coordina' ) ), (string) ( $record['title'] ?? __( 'Risk or issue', 'coordina' ) ) ) );
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
		$allowed = array( 'title', 'object_type', 'status', 'severity', 'target_resolution_date', 'updated_at', 'created_at' );
		return in_array( $value, $allowed, true ) ? $value : 'updated_at';
	}

	/**
	 * Normalize and sanitize data.
	 *
	 * @param array<string, mixed> $data Raw data.
	 * @param bool                 $is_create Whether this is a create operation.
	 * @return array<string, mixed>
	 */
	private function clean_data( array $data, bool $is_create ): array {
		$status = sanitize_key( (string) ( $data['status'] ?? 'identified' ) );

		$clean = array(
			'project_id'              => max( 0, (int) ( $data['project_id'] ?? 0 ) ),
			'object_type'             => in_array( sanitize_key( (string) ( $data['object_type'] ?? 'risk' ) ), array( 'risk', 'issue' ), true ) ? sanitize_key( (string) ( $data['object_type'] ?? 'risk' ) ) : 'risk',
			'title'                   => sanitize_text_field( (string) ( $data['title'] ?? '' ) ),
			'description'             => isset( $data['description'] ) ? wp_kses_post( (string) $data['description'] ) : '',
			'status'                  => $status,
			'severity'                => sanitize_key( (string) ( $data['severity'] ?? 'medium' ) ),
			'impact'                  => sanitize_key( (string) ( $data['impact'] ?? 'medium' ) ),
			'likelihood'              => sanitize_key( (string) ( $data['likelihood'] ?? 'medium' ) ),
			'owner_user_id'           => (int) ( $data['owner_user_id'] ?? 0 ),
			'mitigation_plan'         => isset( $data['mitigation_plan'] ) ? wp_kses_post( (string) $data['mitigation_plan'] ) : '',
			'target_resolution_date'  => $this->normalize_datetime( isset( $data['target_resolution_date'] ) ? (string) $data['target_resolution_date'] : null ),
			'resolved_at'             => in_array( $status, array( 'resolved', 'closed' ), true ) ? $this->now() : null,
		);

		if ( ! $is_create && array_key_exists( 'resolved_at', $data ) && ! in_array( $status, array( 'resolved', 'closed' ), true ) ) {
			$clean['resolved_at'] = $this->normalize_datetime( isset( $data['resolved_at'] ) ? (string) $data['resolved_at'] : null );
		}

		return $clean;
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

		$item['owner_label']   = $item['owner_user_id'] ? ( get_userdata( (int) $item['owner_user_id'] )->display_name ?? '' ) : '';
		$item['project_label'] = $this->get_project_label( (int) ( $item['project_id'] ?? 0 ) );
		$item['created_by_label'] = $item['created_by'] ? ( get_userdata( (int) $item['created_by'] )->display_name ?? '' ) : '';
		$item['can_edit']         = $this->access->can_edit_risk_issue( (int) ( $item['id'] ?? 0 ) );
		$item['can_delete']       = $this->access->can_delete_risk_issue( (int) ( $item['id'] ?? 0 ) );
		$item['can_collaborate']  = $this->access->can_post_update_on_context( (string) ( $item['object_type'] ?? 'risk' ), (int) ( $item['id'] ?? 0 ) );
		$item['can_post_update']  = $item['can_collaborate'];
		$item['can_attach_files'] = $this->access->can_attach_files_to_context( (string) ( $item['object_type'] ?? 'risk' ), (int) ( $item['id'] ?? 0 ) );
		return $item;
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

		return $title ? (string) $title : __( 'Project exception', 'coordina' );
	}

	/**
	 * Log create activity for a risk or issue.
	 *
	 * @param array<string, mixed> $record Record payload.
	 */
	private function log_create_activity( array $record ): void {
		$record_id = (int) ( $record['id'] ?? 0 );
		if ( $record_id <= 0 ) {
			return;
		}

		$this->log_activity(
			(string) ( $record['object_type'] ?? 'risk' ),
			$record_id,
			'created',
			sprintf(
				/* translators: %s: risk or issue title */
				__( 'Created %s.', 'coordina' ),
				(string) ( $record['title'] ?? __( 'risk or issue', 'coordina' ) )
			)
		);
	}

	/**
	 * Log update activity for a risk or issue.
	 *
	 * @param array<string, mixed> $before Previous record.
	 * @param array<string, mixed> $after Updated record.
	 */
	private function log_update_activity( array $before, array $after ): void {
		$record_id = (int) ( $after['id'] ?? 0 );
		if ( $record_id <= 0 ) {
			return;
		}

		$changes = array();
		foreach ( array( 'status', 'severity', 'owner_user_id', 'target_resolution_date' ) as $field ) {
			if ( (string) ( $before[ $field ] ?? '' ) !== (string) ( $after[ $field ] ?? '' ) ) {
				$changes[] = str_replace( '_', ' ', $field );
			}
		}

		$message = ! empty( $changes )
			? sprintf(
				/* translators: %s: comma-separated changed fields */
				__( 'Updated %s.', 'coordina' ),
				implode( ', ', $changes )
			)
			: __( 'Updated risk or issue details.', 'coordina' );

		$this->log_activity( (string) ( $after['object_type'] ?? 'risk' ), $record_id, 'updated', $message );
	}
}
