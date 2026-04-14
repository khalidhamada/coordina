<?php
/**
 * Contextual discussion repository.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Persistence;

use RuntimeException;

final class DiscussionRepository extends AbstractRepository {
	/**
	 * Get paginated discussion items.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array<string, mixed>
	 */
	public function get_items( array $args ): array {
		$table       = $this->table( 'discussions' );
		$page        = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page    = max( 1, min( 50, (int) ( $args['per_page'] ?? 10 ) ) );
		$search      = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';
		$object_type = isset( $args['object_type'] ) ? sanitize_key( (string) ( $args['object_type'] ?? '' ) ) : '';
		$object_id   = isset( $args['object_id'] ) ? max( 0, (int) ( $args['object_id'] ?? 0 ) ) : 0;
		$project_id  = isset( $args['project_id'] ) ? max( 0, (int) ( $args['project_id'] ?? 0 ) ) : 0;
		$recency     = isset( $args['recency'] ) ? sanitize_key( (string) $args['recency'] ) : '';
		$order       = 'asc' === strtolower( (string) ( $args['order'] ?? 'desc' ) ) ? 'ASC' : 'DESC';
		$offset      = ( $page - 1 ) * $per_page;
		$where       = array( '1=1' );
		$params      = array();
		list( $access_sql, $access_params ) = $this->access->context_access_where( 'object_type', 'object_id', 'created_by' );
		$where[] = $access_sql;
		$params  = array_merge( $params, $access_params );

		if ( '' !== $search ) {
			$where[]  = 'body LIKE %s';
			$params[] = '%' . $this->wpdb->esc_like( $search ) . '%';
		}

		if ( '' !== $object_type ) {
			$where[]  = 'object_type = %s';
			$params[] = $object_type;
		}

		if ( $object_id > 0 ) {
			$where[]  = 'object_id = %d';
			$params[] = $object_id;
		}

		if ( $project_id > 0 ) {
			$where[]  = 'project_id = %d';
			$params[] = $project_id;
		}

		if ( '' !== $recency ) {
			$after = $this->created_after_for_recency( $recency );
			if ( '' !== $after ) {
				$where[]  = 'created_at >= %s';
				$params[] = $after;
			}
		}

		$where_sql = implode( ' AND ', $where );
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		$list_sql  = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at {$order}, id {$order} LIMIT %d OFFSET %d";
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
	 * Find one update.
	 *
	 * @param int $id Record id.
	 * @return array<string, mixed>
	 */
	public function find( int $id ): array {
		$item = $this->wpdb->get_row( $this->wpdb->prepare( 'SELECT object_type, object_id, created_by FROM ' . $this->table( 'discussions' ) . ' WHERE id = %d', $id ) );

		if ( ! $item || ( (int) $item->created_by !== get_current_user_id() && ! $this->access->can_view_context( (string) $item->object_type, (int) $item->object_id ) ) ) {
			return array();
		}

		$row = $this->wpdb->get_row( $this->wpdb->prepare( 'SELECT * FROM ' . $this->table( 'discussions' ) . ' WHERE id = %d', $id ) );
		return $this->map_item( $row );
	}

	/**
	 * Create a discussion update.
	 *
	 * @param array<string, mixed> $data Discussion data.
	 * @return array<string, mixed>
	 */
	public function create( array $data ): array {
		$object_type = sanitize_key( (string) ( $data['object_type'] ?? '' ) );
		$object_id   = max( 0, (int) ( $data['object_id'] ?? 0 ) );
		$body        = trim( wp_kses_post( (string) ( $data['body'] ?? '' ) ) );

		if ( ! $this->context_exists( $object_type, $object_id ) ) {
			throw new RuntimeException( __( 'A valid parent context is required for updates.', 'coordina' ) );
		}

		if ( ! $this->access->can_collaborate_on_context( $object_type, $object_id ) ) {
			throw new RuntimeException( __( 'You are not allowed to post updates to this context.', 'coordina' ) );
		}

		if ( '' === wp_strip_all_tags( $body ) ) {
			throw new RuntimeException( __( 'Write an update before posting it.', 'coordina' ) );
		}

		$now    = $this->now();
		$result = $this->wpdb->insert(
			$this->table( 'discussions' ),
			array(
				'project_id'  => $this->resolve_project_id_for_context( $object_type, $object_id ),
				'object_type' => $object_type,
				'object_id'   => $object_id,
				'body'        => $body,
				'created_by'  => get_current_user_id(),
				'created_at'  => $now,
				'updated_at'  => $now,
			)
		);

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'The update could not be saved.', 'coordina' ) );
		}

		$context_label = $this->resolve_context_label( $object_type, $object_id );
		$this->log_activity(
			$object_type,
			$object_id,
			'discussion_added',
			sprintf(
				/* translators: %s: context label */
				__( 'Posted an update on %s.', 'coordina' ),
				$context_label ?: __( 'linked work', 'coordina' )
			)
		);

		return $this->find( (int) $this->wpdb->insert_id );
	}

	/**
	 * Get project-specific updates.
	 *
	 * @param int                  $project_id Project id.
	 * @param array<string, mixed> $args Extra args.
	 * @return array<string, mixed>
	 */
	public function get_for_project( int $project_id, array $args = array() ): array {
		$args['project_id'] = $project_id;
		return $this->get_items( $args );
	}

	/**
	 * Get project discussion summary.
	 *
	 * @param int $project_id Project id.
	 * @return array<string, mixed>
	 */
	public function get_project_summary( int $project_id ): array {
		$table = $this->table( 'discussions' );
		list( $access_sql, $access_params ) = $this->access->context_access_where( 'object_type', 'object_id', 'created_by' );
		$row   = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT COUNT(*) AS total_count, MAX(created_at) AS latest_created_at FROM {$table} WHERE project_id = %d AND {$access_sql}",
				array_merge( array( $project_id ), $access_params )
			)
		);
		$item  = $this->row_to_array( $row );

		return array(
			'total'    => (int) ( $item['total_count'] ?? 0 ),
			'latestAt' => (string) ( $item['latest_created_at'] ?? '' ),
		);
	}

	/**
	 * Delete a discussion update.
	 *
	 * @param int $id Record id.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		$discussion = $this->find( $id );

		if ( empty( $discussion ) ) {
			throw new RuntimeException( __( 'Update could not be found.', 'coordina' ) );
		}

		if ( ! $this->can_delete_record( $discussion ) ) {
			throw new RuntimeException( __( 'You are not allowed to delete this update.', 'coordina' ) );
		}

		$result = $this->wpdb->delete( $this->table( 'discussions' ), array( 'id' => $id ) );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Update could not be deleted.', 'coordina' ) );
		}

		if ( $result > 0 ) {
			$this->log_activity(
				(string) ( $discussion['object_type'] ?? 'project' ),
				(int) ( $discussion['object_id'] ?? 0 ),
				'discussion_deleted',
				__( 'Removed an update.', 'coordina' )
			);
		}

		return $result > 0;
	}

	/**
	 * Map row to response.
	 *
	 * @param object|null $row DB row.
	 * @return array<string, mixed>
	 */
	private function map_item( $row ): array {
		$item = $this->row_to_array( $row );

		if ( empty( $item ) ) {
			return array();
		}

		$item['project_id']       = (int) ( $item['project_id'] ?? 0 );
		$item['object_label']     = $this->resolve_context_label( (string) $item['object_type'], (int) ( $item['object_id'] ?? 0 ) );
		$item['project_label']    = $this->get_project_label( (int) $item['project_id'] );
		$item['created_by_label'] = $this->get_user_label( (int) ( $item['created_by'] ?? 0 ) );
		$item['excerpt']          = wp_trim_words( wp_strip_all_tags( (string) ( $item['body'] ?? '' ) ), 18, '...' );
		$item['can_delete']       = $this->can_delete_record( $item );

		return $item;
	}

	/**
	 * Determine whether an update record can be deleted by the current user.
	 *
	 * @param array<string, mixed> $item Discussion record.
	 * @return bool
	 */
	private function can_delete_record( array $item ): bool {
		return $this->has_full_project_access() || (int) ( $item['created_by'] ?? 0 ) === get_current_user_id();
	}

	/**
	 * Resolve a created-at lower bound for discovery recency filters.
	 *
	 * @param string $recency Recency key.
	 * @return string
	 */
	private function created_after_for_recency( string $recency ): string {
		$timestamp = current_time( 'timestamp', true );

		if ( 'today' === $recency ) {
			return gmdate( 'Y-m-d 00:00:00', $timestamp );
		}

		$days = absint( $recency );
		if ( ! in_array( $days, array( 7, 30 ), true ) ) {
			return '';
		}

		return gmdate( 'Y-m-d H:i:s', $timestamp - ( DAY_IN_SECONDS * $days ) );
	}
}
