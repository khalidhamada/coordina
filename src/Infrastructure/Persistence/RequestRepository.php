<?php
/**
 * Request repository.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Persistence;

use Coordina\Platform\Contracts\AccessPolicyInterface;
use Coordina\Platform\Contracts\ApprovalRepositoryInterface;
use Coordina\Platform\Contracts\ProjectRepositoryInterface;
use Coordina\Platform\Contracts\TaskRepositoryInterface;
use RuntimeException;

final class RequestRepository extends AbstractRepository {
	/**
	 * Shared approvals repository.
	 *
	 * @var ApprovalRepositoryInterface
	 */
	private $approvals;

	/**
	 * Constructor.
	 *
	 * @param AccessPolicyInterface|null      $access Shared access policy.
	 * @param ApprovalRepositoryInterface|null $approvals Shared approvals repository.
	 */
	public function __construct( ?AccessPolicyInterface $access = null, ?ApprovalRepositoryInterface $approvals = null ) {
		parent::__construct( $access );
		$this->approvals = $approvals ?: new ApprovalRepository();
	}
	/**
	 * Fetch paginated requests.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array<string, mixed>
	 */
	public function get_items( array $args ): array {
		$table       = $this->table( 'requests' );
		$user_id     = get_current_user_id();
		$full_access = $this->has_full_access();
		$page        = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page    = max( 1, min( 50, (int) ( $args['per_page'] ?? 10 ) ) );
		$search      = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';
		$status      = isset( $args['status'] ) ? sanitize_text_field( (string) $args['status'] ) : '';
		$order_by    = $this->sanitize_order_by( (string) ( $args['orderby'] ?? 'updated_at' ) );
		$order       = 'asc' === strtolower( (string) ( $args['order'] ?? 'desc' ) ) ? 'ASC' : 'DESC';
		$offset      = ( $page - 1 ) * $per_page;
		$where       = array( '1=1' );
		$params      = array();

		if ( '' !== $search ) {
			$where[]  = 'title LIKE %s';
			$params[] = '%' . $this->wpdb->esc_like( $search ) . '%';
		}

		if ( '' !== $status ) {
			$where[]  = 'status = %s';
			$params[] = $status;
		}

		if ( ! $full_access ) {
			$where[]  = '(requester_user_id = %d OR triage_owner_user_id = %d)';
			$params[] = $user_id;
			$params[] = $user_id;
		}

		$where_sql   = implode( ' AND ', $where );
		$count_sql   = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		$list_sql    = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$order_by} {$order} LIMIT %d OFFSET %d";
		$total       = (int) $this->prepared_var( $count_sql, $params );
		$list_params = $params;
		$list_params[] = $per_page;
		$list_params[] = $offset;
		$rows = $this->prepared_results( $list_sql, $list_params );

		return array(
			'items'      => array_map( array( $this, 'map_item' ), $rows ?: array() ),
			'total'      => $total,
			'page'       => $page,
			'per_page'   => $per_page,
			'totalPages' => (int) ceil( $total / $per_page ),
		);
	}

	/**
	 * Find request.
	 *
	 * @param int $id Request id.
	 * @return array<string, mixed>
	 */
	public function find( int $id ): array {
		if ( ! $this->can_access_request( $id ) ) {
			return array();
		}

		$table = $this->table( 'requests' );
		$row   = $this->prepared_row( 'SELECT * FROM ' . $table . ' WHERE id = %d', array( $id ) );
		return $this->map_item( $row );
	}

	/**
	 * Create request.
	 *
	 * @param array<string, mixed> $data Request data.
	 * @return array<string, mixed>
	 */
	public function create( array $data ): array {
		$table           = $this->table( 'requests' );
		$now             = $this->now();
		$triage_owner_id = (int) ( $data['triage_owner_user_id'] ?? 0 );
		$approval_status = sanitize_key( (string) ( $data['approval_status'] ?? 'pending' ) );
		$status          = $this->normalize_status_for_approval( sanitize_key( (string) ( $data['status'] ?? 'submitted' ) ), $approval_status, $triage_owner_id );
		$clean           = array(
			'title'                 => sanitize_text_field( (string) ( $data['title'] ?? '' ) ),
			'request_type'          => sanitize_text_field( (string) ( $data['request_type'] ?? '' ) ),
			'requester_user_id'     => get_current_user_id(),
			'triage_owner_user_id'  => $triage_owner_id,
			'status'                => $status,
			'priority'              => sanitize_key( (string) ( $data['priority'] ?? 'normal' ) ),
			'desired_due_date'      => $this->normalize_datetime( isset( $data['desired_due_date'] ) ? (string) $data['desired_due_date'] : null ),
			'business_reason'       => isset( $data['business_reason'] ) ? wp_kses_post( (string) $data['business_reason'] ) : '',
			'approval_status'       => $approval_status,
			'converted_object_type' => '',
			'converted_object_id'   => 0,
			'created_at'            => $now,
			'updated_at'            => $now,
		);

		$result = $this->wpdb->insert( $table, $clean );

		if ( false === $result ) {
			throw new RuntimeException( esc_html__( 'Request could not be created.', 'coordina' ) );
		}

		$request = $this->find( (int) $this->wpdb->insert_id );
		$this->approvals->sync_for_request( $request );

		return $request;
	}

	/**
	 * Update request.
	 *
	 * @param int                  $id Request id.
	 * @param array<string, mixed> $data Request data.
	 * @return array<string, mixed>
	 */
	public function update( int $id, array $data ): array {
		if ( ! $this->can_access_request( $id ) ) {
			throw new RuntimeException( esc_html__( 'You are not allowed to update this request.', 'coordina' ) );
		}

		$table           = $this->table( 'requests' );
		$current         = $this->find( $id );
		$triage_owner_id = (int) ( $data['triage_owner_user_id'] ?? 0 );
		$approval_status = sanitize_key( (string) ( $data['approval_status'] ?? ( $current['approval_status'] ?? 'pending' ) ) );
		$status          = $this->normalize_status_for_approval( sanitize_key( (string) ( $data['status'] ?? 'submitted' ) ), $approval_status, $triage_owner_id, (string) ( $current['status'] ?? '' ) );
		$clean           = array(
			'title'                => sanitize_text_field( (string) ( $data['title'] ?? '' ) ),
			'request_type'         => sanitize_text_field( (string) ( $data['request_type'] ?? '' ) ),
			'triage_owner_user_id' => $triage_owner_id,
			'status'               => $status,
			'priority'             => sanitize_key( (string) ( $data['priority'] ?? 'normal' ) ),
			'desired_due_date'     => $this->normalize_datetime( isset( $data['desired_due_date'] ) ? (string) $data['desired_due_date'] : null ),
			'business_reason'      => isset( $data['business_reason'] ) ? wp_kses_post( (string) $data['business_reason'] ) : '',
			'approval_status'      => $approval_status,
			'updated_at'           => $this->now(),
		);

		$result = $this->wpdb->update( $table, $clean, array( 'id' => $id ) );

		if ( false === $result ) {
			throw new RuntimeException( esc_html__( 'Request could not be updated.', 'coordina' ) );
		}

		$request = $this->find( $id );
		$this->approvals->sync_for_request( $request );

		return $request;
	}

	/**
	 * Convert request into a project or task.
	 *
	 * @param int               $id Request id.
	 * @param string            $target_type Target object type.
	 * @param ProjectRepository $projects Project repository.
	 * @param TaskRepository    $tasks Task repository.
	 * @return array<string, mixed>
	 */
	public function convert( int $id, string $target_type, ProjectRepositoryInterface $projects, TaskRepositoryInterface $tasks ): array {
		$request = $this->find( $id );

		if ( empty( $request ) ) {
			throw new RuntimeException( esc_html__( 'Request could not be found.', 'coordina' ) );
		}

		if ( ! $this->has_full_access() && (int) $request['triage_owner_user_id'] !== get_current_user_id() ) {
			throw new RuntimeException( esc_html__( 'You are not allowed to convert this request.', 'coordina' ) );
		}

		if ( 'approved' !== ( $request['approval_status'] ?? '' ) ) {
			throw new RuntimeException( esc_html__( 'Only approved requests can be converted into delivery work.', 'coordina' ) );
		}

		$created = array();

		if ( 'project' === $target_type ) {
			$created = $projects->create(
				array(
					'title'           => $request['title'],
					'description'     => $request['business_reason'],
					'status'          => 'planned',
					'health'          => 'neutral',
					'priority'        => $request['priority'],
					'manager_user_id' => $request['triage_owner_user_id'],
					'target_end_date' => $request['desired_due_date'],
				)
			);
		}

		if ( 'task' === $target_type ) {
			$created = $tasks->create(
				array(
					'title'            => $request['title'],
					'description'      => $request['business_reason'],
					'status'           => 'to-do',
					'priority'         => $request['priority'],
					'assignee_user_id' => $request['triage_owner_user_id'],
					'due_date'         => $request['desired_due_date'],
				)
			);
		}

		if ( empty( $created ) ) {
			throw new RuntimeException( esc_html__( 'Request conversion target is not supported.', 'coordina' ) );
		}

		$result = $this->wpdb->update(
			$this->table( 'requests' ),
			array(
				'status'                => 'converted',
				'approval_status'       => 'approved',
				'converted_object_type' => $target_type,
				'converted_object_id'   => (int) $created['id'],
				'updated_at'            => $this->now(),
			),
			array( 'id' => $id )
		);

		if ( false === $result ) {
			throw new RuntimeException( esc_html__( 'Request could not be converted.', 'coordina' ) );
		}

		$request = $this->find( $id );
		$this->approvals->sync_for_request( $request );

		return $request;
	}

	/**
	 * Bulk status update.
	 *
	 * @param int[]  $ids Request ids.
	 * @param string $status New status.
	 * @return int
	 */
	public function bulk_update_status( array $ids, string $status ): int {
		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );

		if ( empty( $ids ) ) {
			return 0;
		}

		$table        = $this->table( 'requests' );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$params       = array_merge( array( sanitize_key( $status ), $this->now() ), $ids );
		$sql          = "UPDATE {$table} SET status = %s, updated_at = %s WHERE id IN ({$placeholders})";

		if ( ! $this->has_full_access() ) {
			$sql     .= ' AND (requester_user_id = %d OR triage_owner_user_id = %d)';
			$params[] = get_current_user_id();
			$params[] = get_current_user_id();
		}

		$result = $this->prepared_query( $sql, $params );

		if ( false === $result ) {
			throw new RuntimeException( esc_html__( 'Request statuses could not be updated.', 'coordina' ) );
		}

		return (int) $result;
	}

	/**
	 * Delete a request and its request-scoped records.
	 *
	 * @param int $id Request id.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		if ( ! $this->access->can_delete_request( $id ) ) {
			throw new RuntimeException( esc_html__( 'You are not allowed to delete this request.', 'coordina' ) );
		}

		$request = $this->find( $id );

		if ( empty( $request ) ) {
			throw new RuntimeException( esc_html__( 'Request could not be found.', 'coordina' ) );
		}

		$this->delete_context_relations( 'request', $id );
		$result = $this->wpdb->delete( $this->table( 'requests' ), array( 'id' => $id ) );

		if ( false === $result ) {
			throw new RuntimeException( esc_html__( 'Request could not be deleted.', 'coordina' ) );
		}

		return $result > 0;
	}

	/**
	 * Map request row.
	 *
	 * @param object|null $row DB row.
	 * @return array<string, mixed>
	 */
	private function map_item( $row ): array {
		$item = $this->row_to_array( $row );

		if ( empty( $item ) ) {
			return array();
		}

		$item['requester_label']    = $item['requester_user_id'] ? ( get_userdata( (int) $item['requester_user_id'] )->display_name ?? '' ) : '';
		$item['triage_owner_label'] = $item['triage_owner_user_id'] ? ( get_userdata( (int) $item['triage_owner_user_id'] )->display_name ?? '' ) : '';
		$item['approval_label']     = ucwords( str_replace( '-', ' ', (string) ( $item['approval_status'] ?? 'pending' ) ) );
		$item['can_edit']          = $this->access->can_edit_request( (int) ( $item['id'] ?? 0 ) );
		$item['can_delete']        = $this->access->can_delete_request( (int) ( $item['id'] ?? 0 ) );
		$item['can_post_update']   = $this->access->can_post_update_on_context( 'request', (int) ( $item['id'] ?? 0 ) );
		$item['can_attach_files']  = $this->access->can_attach_files_to_context( 'request', (int) ( $item['id'] ?? 0 ) );
		$item['can_convert']       = $this->can_convert_request( $item );
		return $item;
	}

	/**
	 * Sanitize order by field.
	 *
	 * @param string $value Requested field.
	 * @return string
	 */
	private function sanitize_order_by( string $value ): string {
		$allowed = array( 'title', 'status', 'priority', 'desired_due_date', 'updated_at', 'created_at' );
		return in_array( $value, $allowed, true ) ? $value : 'updated_at';
	}

	/**
	 * Normalize visible request status around approval lifecycle.
	 *
	 * @param string $status Requested status.
	 * @param string $approval_status Approval state.
	 * @param int    $triage_owner_id Triage owner id.
	 * @param string $current_status Existing status.
	 * @return string
	 */
	private function normalize_status_for_approval( string $status, string $approval_status, int $triage_owner_id, string $current_status = '' ): string {
		if ( in_array( $status, array( 'converted', 'closed', 'awaiting-info' ), true ) ) {
			return $status;
		}

		if ( 'approved' === $approval_status ) {
			return 'approved';
		}

		if ( in_array( $approval_status, array( 'rejected', 'cancelled' ), true ) ) {
			return 'rejected';
		}

		if ( $triage_owner_id > 0 ) {
			return 'under-review';
		}

		if ( '' !== $current_status && in_array( $current_status, array( 'approved', 'rejected' ), true ) ) {
			return $current_status;
		}

		return 'submitted';
	}

	/**
	 * Determine whether current user has broad request access.
	 *
	 * @return bool
	 */
	private function has_full_access(): bool {
		return current_user_can( 'coordina_manage_projects' ) || current_user_can( 'coordina_manage_settings' );
	}

	/**
	 * Check whether current user can access a request.
	 *
	 * @param int $id Request id.
	 * @return bool
	 */
	private function can_access_request( int $id ): bool {
		return $this->access->can_view_request( $id );
	}

	/**
	 * Determine whether the current user can convert a request.
	 *
	 * @param array<string, mixed> $request Request record.
	 */
	private function can_convert_request( array $request ): bool {
		if ( empty( $request ) ) {
			return false;
		}

		if ( 'approved' !== (string) ( $request['approval_status'] ?? '' ) ) {
			return false;
		}

		if ( in_array( (string) ( $request['status'] ?? '' ), array( 'converted', 'closed' ), true ) ) {
			return false;
		}

		if ( $this->has_full_access() ) {
			return true;
		}

		return (int) ( $request['triage_owner_user_id'] ?? 0 ) === get_current_user_id();
	}
}
