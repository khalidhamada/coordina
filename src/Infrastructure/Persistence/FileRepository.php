<?php
/**
 * Contextual file repository.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Persistence;

use RuntimeException;

final class FileRepository extends AbstractRepository {
	/**
	 * Get paginated files.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array<string, mixed>
	 */
	public function get_items( array $args ): array {
		$table       = $this->table( 'files' );
		$page        = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page    = max( 1, min( 50, (int) ( $args['per_page'] ?? 10 ) ) );
		$search      = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';
		$object_type = isset( $args['object_type'] ) ? sanitize_key( (string) $args['object_type'] ) : '';
		$object_id   = isset( $args['object_id'] ) ? max( 0, (int) $args['object_id'] ) : 0;
		$project_id  = isset( $args['project_id'] ) ? max( 0, (int) $args['project_id'] ) : 0;
		$recency     = isset( $args['recency'] ) ? sanitize_key( (string) $args['recency'] ) : '';
		$order       = 'asc' === strtolower( (string) ( $args['order'] ?? 'desc' ) ) ? 'ASC' : 'DESC';
		$offset      = ( $page - 1 ) * $per_page;
		$where       = array( '1=1' );
		$params      = array();
		list( $access_sql, $access_params ) = $this->access->context_access_where( 'object_type', 'object_id', 'created_by' );
		$where[] = $access_sql;
		$params  = array_merge( $params, $access_params );

		if ( '' !== $search ) {
			$where[]  = '(file_name LIKE %s OR note LIKE %s)';
			$like     = '%' . $this->wpdb->esc_like( $search ) . '%';
			$params[] = $like;
			$params[] = $like;
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
		$total     = (int) $this->prepared_var( $count_sql, $params );

		$list_params   = $params;
		$list_params[] = $per_page;
		$list_params[] = $offset;
		$rows          = $this->prepared_results( $list_sql, $list_params );

		return array(
			'items'      => array_map( array( $this, 'map_item' ), $rows ?: array() ),
			'total'      => $total,
			'page'       => $page,
			'per_page'   => $per_page,
			'totalPages' => (int) ceil( $total / $per_page ),
		);
	}

	/**
	 * Find a single file record.
	 *
	 * @param int $id Record id.
	 * @return array<string, mixed>
	 */
	public function find( int $id ): array {
		$item = $this->prepared_row( 'SELECT object_type, object_id, created_by FROM ' . $this->table( 'files' ) . ' WHERE id = %d', array( $id ) );

		if ( ! $item || ( (int) $item->created_by !== get_current_user_id() && ! $this->access->can_view_context( (string) $item->object_type, (int) $item->object_id ) ) ) {
			return array();
		}

		$row = $this->prepared_row( 'SELECT * FROM ' . $this->table( 'files' ) . ' WHERE id = %d', array( $id ) );
		return $this->map_item( $row );
	}

	/**
	 * Create a contextual file record from a WP attachment.
	 *
	 * @param array<string, mixed> $data File data.
	 * @return array<string, mixed>
	 */
	public function create( array $data ): array {
		$object_type   = sanitize_key( (string) ( $data['object_type'] ?? '' ) );
		$object_id     = max( 0, (int) ( $data['object_id'] ?? 0 ) );
		$attachment_id = max( 0, (int) ( $data['attachment_id'] ?? 0 ) );
		$note          = sanitize_textarea_field( (string) ( $data['note'] ?? '' ) );

		if ( ! $this->context_exists( $object_type, $object_id ) ) {
			throw new RuntimeException( esc_html__( 'A valid parent context is required for files.', 'coordina' ) );
		}

		if ( ! $this->access->can_attach_files_to_context( $object_type, $object_id ) ) {
			throw new RuntimeException( esc_html__( 'You are not allowed to attach files to this context.', 'coordina' ) );
		}

		if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
			throw new RuntimeException( esc_html__( 'Select a media file before attaching it.', 'coordina' ) );
		}

		$file_path  = get_attached_file( $attachment_id );
		$file_name  = wp_basename( (string) $file_path );
		$mime_type  = (string) get_post_mime_type( $attachment_id );
		$file_size  = is_string( $file_path ) && '' !== $file_path && file_exists( $file_path ) ? (int) filesize( $file_path ) : 0;
		$project_id = $this->resolve_project_id_for_context( $object_type, $object_id );
		$clean      = array(
			'project_id'    => $project_id,
			'object_type'   => $object_type,
			'object_id'     => $object_id,
			'attachment_id' => $attachment_id,
			'file_name'     => sanitize_file_name( $file_name ?: (string) get_the_title( $attachment_id ) ),
			'mime_type'     => sanitize_text_field( $mime_type ),
			'file_size'     => $file_size,
			'note'          => $note,
			'created_by'    => get_current_user_id(),
			'created_at'    => $this->now(),
		);

		$result = $this->wpdb->insert( $this->table( 'files' ), $clean );

		if ( false === $result ) {
			throw new RuntimeException( esc_html__( 'The file could not be attached.', 'coordina' ) );
		}

		$context_label = $this->resolve_context_label( $object_type, $object_id );
		$this->log_activity(
			$object_type,
			$object_id,
			'file_added',
			sprintf(
				/* translators: 1: file name, 2: context label */
				__( 'Attached "%1$s" to %2$s.', 'coordina' ),
				$clean['file_name'],
				$context_label ?: __( 'linked work', 'coordina' )
			)
		);

		return $this->find( (int) $this->wpdb->insert_id );
	}

	/**
	 * Get project-specific files.
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
	 * Get file summary for a project.
	 *
	 * @param int $project_id Project id.
	 * @return array<string, mixed>
	 */
	public function get_project_summary( int $project_id ): array {
		$table = $this->table( 'files' );
		list( $access_sql, $access_params ) = $this->access->context_access_where( 'object_type', 'object_id', 'created_by' );
		$row   = $this->prepared_row(
			'SELECT COUNT(*) AS total_count, MAX(created_at) AS latest_created_at FROM ' . $table . ' WHERE project_id = %d AND ' . $access_sql,
			array_merge( array( $project_id ), $access_params )
		);
		$item  = $this->row_to_array( $row );

		return array(
			'total'    => (int) ( $item['total_count'] ?? 0 ),
			'latestAt' => (string) ( $item['latest_created_at'] ?? '' ),
		);
	}

	/**
	 * Delete a contextual file record.
	 *
	 * @param int $id Record id.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		$file = $this->find( $id );

		if ( empty( $file ) ) {
			throw new RuntimeException( esc_html__( 'File could not be found.', 'coordina' ) );
		}

		if ( ! $this->can_delete_record( $file ) ) {
			throw new RuntimeException( esc_html__( 'You are not allowed to delete this file.', 'coordina' ) );
		}

		$result = $this->wpdb->delete( $this->table( 'files' ), array( 'id' => $id ) );

		if ( false === $result ) {
			throw new RuntimeException( esc_html__( 'File could not be deleted.', 'coordina' ) );
		}

		if ( $result > 0 ) {
			$this->log_activity(
				(string) ( $file['object_type'] ?? 'project' ),
				(int) ( $file['object_id'] ?? 0 ),
				'file_deleted',
				/* translators: %s: file name. */
				sprintf( __( 'Removed file "%s".', 'coordina' ), (string) ( $file['file_name'] ?? __( 'File', 'coordina' ) ) )
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

		$attachment_id               = (int) ( $item['attachment_id'] ?? 0 );
		$item['attachment_id']       = $attachment_id;
		$item['project_id']          = (int) ( $item['project_id'] ?? 0 );
		$item['object_label']        = $this->resolve_context_label( (string) $item['object_type'], (int) ( $item['object_id'] ?? 0 ) );
		$item['project_label']       = $this->get_project_label( (int) $item['project_id'] );
		$item['created_by_label']    = $this->get_user_label( (int) ( $item['created_by'] ?? 0 ) );
		$item['attachment_title']    = $attachment_id > 0 ? (string) get_the_title( $attachment_id ) : '';
		$item['attachment_url']      = $attachment_id > 0 ? (string) wp_get_attachment_url( $attachment_id ) : '';
		$item['attachment_edit_url'] = $attachment_id > 0 ? (string) get_edit_post_link( $attachment_id, '' ) : '';
		$item['mime_group']          = false !== strpos( (string) ( $item['mime_type'] ?? '' ), '/' ) ? explode( '/', (string) $item['mime_type'] )[0] : '';
		$item['can_delete']         = $this->can_delete_record( $item );

		return $item;
	}

	/**
	 * Determine whether a file record can be deleted by the current user.
	 *
	 * @param array<string, mixed> $item File record.
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
