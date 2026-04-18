<?php
/**
 * Approval repository.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Persistence;

use Coordina\Platform\Contracts\AccessPolicyInterface;
use Coordina\Platform\Contracts\ApprovalRepositoryInterface;
use Coordina\Platform\Contracts\NotificationRepositoryInterface;
use RuntimeException;

final class ApprovalRepository extends AbstractRepository implements ApprovalRepositoryInterface {
	/**
	 * Shared notifications repository.
	 *
	 * @var NotificationRepositoryInterface
	 */
	private $notifications;

	/**
	 * Constructor.
	 *
	 * @param AccessPolicyInterface|null         $access Shared access policy.
	 * @param NotificationRepositoryInterface|null $notifications Shared notifications repository.
	 */
	public function __construct( ?AccessPolicyInterface $access = null, ?NotificationRepositoryInterface $notifications = null ) {
		parent::__construct( $access );
		$this->notifications = $notifications ?: new NotificationRepository();
	}
	/**
	 * Get paginated approvals.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array<string, mixed>
	 */
	public function get_items( array $args ): array {
		$table       = $this->table( 'approvals' );
		$page        = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page    = max( 1, min( 50, (int) ( $args['per_page'] ?? 10 ) ) );
		$search      = isset( $args['search'] ) ? sanitize_text_field( (string) $args['search'] ) : '';
		$status      = isset( $args['status'] ) ? sanitize_key( (string) $args['status'] ) : '';
		$object_type = isset( $args['object_type'] ) ? sanitize_key( (string) $args['object_type'] ) : '';
		$approver_id = isset( $args['approver_user_id'] ) ? max( 0, (int) $args['approver_user_id'] ) : 0;
		$order_by    = $this->sanitize_order_by( (string) ( $args['orderby'] ?? 'submitted_at' ) );
		$order       = 'asc' === strtolower( (string) ( $args['order'] ?? 'desc' ) ) ? 'ASC' : 'DESC';
		$offset      = ( $page - 1 ) * $per_page;
		$where       = array( '1=1' );
		$params      = array();

		if ( '' !== $search ) {
			$where[]  = '(object_type LIKE %s OR CAST(object_id AS CHAR) LIKE %s OR rejection_reason LIKE %s)';
			$like     = '%' . $this->wpdb->esc_like( $search ) . '%';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ( '' !== $status ) {
			$where[]  = 'status = %s';
			$params[] = $status;
		}

		if ( '' !== $object_type ) {
			$where[]  = 'object_type = %s';
			$params[] = $object_type;
		}

		if ( $approver_id > 0 ) {
			$where[]  = 'approver_user_id = %d';
			$params[] = $approver_id;
		}

		list( $access_sql, $access_params ) = $this->access->approval_access_where( 'id' );
		$where[] = $access_sql;
		$params  = array_merge( $params, $access_params );

		$where_sql = implode( ' AND ', $where );
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		$list_sql  = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$order_by} {$order} LIMIT %d OFFSET %d";
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
	 * Find one approval.
	 *
	 * @param int $id Approval id.
	 * @return array<string, mixed>
	 */
	public function find( int $id ): array {
		if ( ! $this->can_access_approval( $id ) ) {
			return array();
		}

		$table = $this->table( 'approvals' );
		$row   = $this->prepared_row( 'SELECT * FROM ' . $table . ' WHERE id = %d', array( $id ) );
		return $this->map_item( $row );
	}

	/**
	 * Create approval.
	 *
	 * @param array<string, mixed> $data Approval data.
	 * @return array<string, mixed>
	 */
	public function create( array $data ): array {
		unset( $data );
		throw new RuntimeException( esc_html__( 'Approvals are generated automatically from linked work items and cannot be created directly.', 'coordina' ) );
	}

	/**
	 * Update approval.
	 *
	 * @param int                  $id Approval id.
	 * @param array<string, mixed> $data Approval data.
	 * @return array<string, mixed>
	 */
	public function update( int $id, array $data ): array {
		if ( ! $this->access->can_edit_approval( $id ) ) {
			throw new RuntimeException( esc_html__( 'You are not allowed to update this approval.', 'coordina' ) );
		}

		$current = $this->find( $id );
		$status  = sanitize_key( (string) ( $data['status'] ?? ( $current['status'] ?? 'pending' ) ) );
		$clean   = array(
			'status'           => $status,
			'rejection_reason' => sanitize_textarea_field( (string) ( $data['rejection_reason'] ?? ( $current['rejection_reason'] ?? '' ) ) ),
			'decision_at'      => $this->decision_timestamp_for_status( $status ),
		);

		if ( $this->has_full_access() && array_key_exists( 'approver_user_id', $data ) ) {
			$clean['approver_user_id'] = max( 0, (int) $data['approver_user_id'] );
		}

		$result = $this->wpdb->update( $this->table( 'approvals' ), $clean, array( 'id' => $id ) );

		if ( false === $result ) {
			throw new RuntimeException( esc_html__( 'Approval could not be updated.', 'coordina' ) );
		}

		$this->sync_parent_approval_state( (string) ( $current['object_type'] ?? '' ), (int) ( $current['object_id'] ?? 0 ), $status );

		return $this->find( $id );
	}

	/**
	 * Bulk status update approvals.
	 *
	 * @param int[]  $ids Approval ids.
	 * @param string $status New status.
	 * @return int
	 */
	public function bulk_update_status( array $ids, string $status ): int {
		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );

		if ( empty( $ids ) ) {
			return 0;
		}

		$table        = $this->table( 'approvals' );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$params       = array_merge( array( sanitize_key( $status ), $this->decision_timestamp_for_status( sanitize_key( $status ) ) ), $ids );
		$sql          = "UPDATE {$table} SET status = %s, decision_at = %s WHERE id IN ({$placeholders})";

		if ( ! $this->has_full_access() ) {
			$sql     .= ' AND approver_user_id = %d';
			$params[] = get_current_user_id();
		}

		$result = $this->prepared_query( $sql, $params );

		if ( false === $result ) {
			throw new RuntimeException( esc_html__( 'Approvals could not be updated.', 'coordina' ) );
		}

		foreach ( $ids as $id ) {
			$item = $this->find( $id );
			if ( ! empty( $item ) ) {
				$this->sync_parent_approval_state( (string) $item['object_type'], (int) $item['object_id'], sanitize_key( $status ) );
			}
		}

		return (int) $result;
	}

	/**
	 * Get pending approvals for a user.
	 *
	 * @param int $user_id User id.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_pending_for_user( int $user_id ): array {
		$table = $this->table( 'approvals' );
		$sql   = "SELECT * FROM {$table} WHERE approver_user_id = %d AND status = 'pending' ORDER BY submitted_at DESC LIMIT 20";
		$rows  = $this->prepared_results( $sql, array( $user_id ) );
		return array_map( array( $this, 'map_item' ), $rows ?: array() );
	}

	/**
	 * Get approvals relevant to a project.
	 *
	 * @param int                  $project_id Project id.
	 * @param array<string, mixed> $args Extra args.
	 * @return array<string, mixed>
	 */
	public function get_for_project( int $project_id, array $args = array() ): array {
		$table = $this->table( 'approvals' );
		$page  = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page = max( 1, min( 50, (int) ( $args['per_page'] ?? 10 ) ) );
		$offset = ( $page - 1 ) * $per_page;
		$status = isset( $args['status'] ) ? sanitize_key( (string) $args['status'] ) : '';
		$where = array(
			'(object_type = %s AND object_id = %d)',
			'(object_type = %s AND object_id IN (SELECT id FROM ' . $this->table( 'tasks' ) . ' WHERE project_id = %d))',
		);
		$params = array( 'project', $project_id, 'task', $project_id );
		$status_params = array();
		list( $access_sql, $access_params ) = $this->access->approval_access_where( 'id' );

		if ( '' !== $status ) {
			$where[] = 'status = %s';
			$status_params[] = $status;
		}

		$sql_where = '(' . implode( ' OR ', array_slice( $where, 0, 2 ) ) . ') AND ' . $access_sql;
		$params    = array_merge( $params, $access_params, $status_params );
		if ( count( $where ) > 2 ) {
			$sql_where .= ' AND ' . implode( ' AND ', array_slice( $where, 2 ) );
		}

		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$sql_where}";
		$list_sql  = "SELECT * FROM {$table} WHERE {$sql_where} ORDER BY submitted_at DESC LIMIT %d OFFSET %d";
		$total     = (int) $this->prepared_var( $count_sql, $params );
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
	 * Get project approval summary.
	 *
	 * @param int $project_id Project id.
	 * @return array<string, mixed>
	 */
	public function get_project_summary( int $project_id ): array {
		$collection = $this->get_for_project( $project_id, array( 'page' => 1, 'per_page' => 100 ) );
		$items      = $collection['items'] ?? array();

		return array(
			'total'    => count( $items ),
			'pending'  => count( array_filter( $items, static function ( array $item ): bool { return 'pending' === ( $item['status'] ?? '' ); } ) ),
			'approved' => count( array_filter( $items, static function ( array $item ): bool { return 'approved' === ( $item['status'] ?? '' ); } ) ),
			'rejected' => count( array_filter( $items, static function ( array $item ): bool { return 'rejected' === ( $item['status'] ?? '' ); } ) ),
		);
	}

	/**
	 * Get current approval state for an object.
	 *
	 * @param string $object_type Object type.
	 * @param int    $object_id Object id.
	 * @return array<string, mixed>
	 */
	public function get_latest_for_object( string $object_type, int $object_id ): array {
		$table = $this->table( 'approvals' );
		$row   = $this->prepared_row( 'SELECT * FROM ' . $table . ' WHERE object_type = %s AND object_id = %d ORDER BY submitted_at DESC, id DESC LIMIT 1', array( sanitize_key( $object_type ), $object_id ) );
		return $this->map_item( $row );
	}

	/**
	 * Ensure task approval record exists or is cleaned up.
	 *
	 * @param array<string, mixed> $task Task record.
	 * @return void
	 */
	public function sync_for_task( array $task ): void {
		$task_id            = (int) ( $task['id'] ?? 0 );
		$approval_required  = ! empty( $task['approval_required'] );
		$project_id         = (int) ( $task['project_id'] ?? 0 );
		$approver_user_id   = $project_id > 0 ? $this->get_project_manager_id( $project_id ) : 0;

		if ( $task_id <= 0 ) {
			return;
		}

		if ( ! $approval_required || $approver_user_id <= 0 ) {
			$this->delete_for_object( 'task', $task_id );
			return;
		}

		$this->upsert_pending_approval( 'task', $task_id, $approver_user_id, (int) ( $task['reporter_user_id'] ?? get_current_user_id() ) );
	}

	/**
	 * Ensure request approval record exists or is cleaned up.
	 *
	 * @param array<string, mixed> $request Request record.
	 * @return void
	 */
	public function sync_for_request( array $request ): void {
		$request_id   = (int) ( $request['id'] ?? 0 );
		$approver_id  = (int) ( $request['triage_owner_user_id'] ?? 0 );
		$status       = sanitize_key( (string) ( $request['approval_status'] ?? 'pending' ) );

		if ( $request_id <= 0 ) {
			return;
		}

		if ( $approver_id <= 0 || in_array( $status, array( 'approved', 'rejected' ), true ) ) {
			$this->delete_for_object( 'request', $request_id );
			return;
		}

		$this->upsert_pending_approval( 'request', $request_id, $approver_id, (int) ( $request['requester_user_id'] ?? get_current_user_id() ) );
	}

	/**
	 * Sanitize order by.
	 *
	 * @param string $value Requested field.
	 * @return string
	 */
	private function sanitize_order_by( string $value ): string {
		$allowed = array( 'status', 'object_type', 'approver_user_id', 'submitted_at', 'decision_at' );
		return in_array( $value, $allowed, true ) ? $value : 'submitted_at';
	}

	/**
	 * Determine if current user has full approval access.
	 *
	 * @return bool
	 */
	private function has_full_access(): bool {
		return current_user_can( 'coordina_manage_projects' ) || current_user_can( 'coordina_manage_settings' );
	}

	/**
	 * Check whether current user can access an approval.
	 *
	 * @param int $id Approval id.
	 * @return bool
	 */
	private function can_access_approval( int $id ): bool {
		return $this->access->can_view_approval( $id );
	}

	/**
	 * Upsert a pending approval.
	 *
	 * @param string $object_type Object type.
	 * @param int    $object_id Object id.
	 * @param int    $approver_user_id Approver id.
	 * @param int    $submitted_by_user_id Submitter id.
	 * @return void
	 */
	private function upsert_pending_approval( string $object_type, int $object_id, int $approver_user_id, int $submitted_by_user_id ): void {
		$current = $this->get_latest_for_object( $object_type, $object_id );

		if ( empty( $current ) ) {
			$this->create_linked_approval(
				array(
					'object_type'          => $object_type,
					'object_id'            => $object_id,
					'submitted_by_user_id' => $submitted_by_user_id,
					'approver_user_id'     => $approver_user_id,
					'status'               => 'pending',
				)
			);
			return;
		}

		$should_notify = (int) ( $current['approver_user_id'] ?? 0 ) !== $approver_user_id || 'pending' !== (string) ( $current['status'] ?? '' );

		$this->wpdb->update(
			$this->table( 'approvals' ),
			array(
				'approver_user_id'     => $approver_user_id,
				'submitted_by_user_id' => $submitted_by_user_id,
				'status'               => 'pending',
				'rejection_reason'     => '',
				'decision_at'          => null,
			),
			array( 'id' => (int) $current['id'] )
		);

		if ( $should_notify ) {
			$this->notify_pending_approval( $object_type, $object_id, $approver_user_id, $submitted_by_user_id );
		}
	}

	/**
	 * Create a linked approval internally.
	 *
	 * @param array<string, mixed> $data Approval data.
	 * @return array<string, mixed>
	 */
	private function create_linked_approval( array $data ): array {
		$table = $this->table( 'approvals' );
		$status = sanitize_key( (string) ( $data['status'] ?? 'pending' ) );
		$clean = array(
			'object_type'          => sanitize_key( (string) ( $data['object_type'] ?? 'task' ) ),
			'object_id'            => max( 0, (int) ( $data['object_id'] ?? 0 ) ),
			'submitted_by_user_id' => max( 0, (int) ( $data['submitted_by_user_id'] ?? get_current_user_id() ) ),
			'approver_user_id'     => max( 0, (int) ( $data['approver_user_id'] ?? 0 ) ),
			'status'               => $status,
			'rejection_reason'     => sanitize_textarea_field( (string) ( $data['rejection_reason'] ?? '' ) ),
			'submitted_at'         => $this->normalize_datetime( isset( $data['submitted_at'] ) ? (string) $data['submitted_at'] : null ) ?: $this->now(),
			'decision_at'          => $this->decision_timestamp_for_status( $status ),
		);

		$result = $this->wpdb->insert( $table, $clean );

		if ( false === $result ) {
			throw new RuntimeException( esc_html__( 'Approval could not be created.', 'coordina' ) );
		}

		$approval = $this->find( (int) $this->wpdb->insert_id );

		if ( 'pending' === $status ) {
			$this->notify_pending_approval( $clean['object_type'], (int) $clean['object_id'], (int) $clean['approver_user_id'], (int) $clean['submitted_by_user_id'] );
		}

		return $approval;
	}

	/**
	 * Delete approval records for an object.
	 *
	 * @param string $object_type Object type.
	 * @param int    $object_id Object id.
	 * @return void
	 */
	private function delete_for_object( string $object_type, int $object_id ): void {
		$this->wpdb->delete(
			$this->table( 'approvals' ),
			array(
				'object_type' => sanitize_key( $object_type ),
				'object_id'   => $object_id,
			)
		);
	}

	/**
	 * Sync parent object approval state.
	 *
	 * @param string $object_type Object type.
	 * @param int    $object_id Object id.
	 * @param string $status Approval status.
	 * @return void
	 */
	private function sync_parent_approval_state( string $object_type, int $object_id, string $status ): void {
		if ( 'request' === $object_type ) {
			$request_table = $this->table( 'requests' );
			$current       = $this->prepared_row( 'SELECT status, triage_owner_user_id FROM ' . $request_table . ' WHERE id = %d', array( $object_id ) );
			$triage_owner  = (int) ( $current->triage_owner_user_id ?? 0 );
			$next_status   = 'submitted';

			if ( 'approved' === $status ) {
				$next_status = 'approved';
			} elseif ( in_array( $status, array( 'rejected', 'cancelled' ), true ) ) {
				$next_status = 'rejected';
			} elseif ( $triage_owner > 0 ) {
				$next_status = 'under-review';
			}

			$this->wpdb->update( $request_table, array( 'status' => $next_status, 'approval_status' => $status, 'updated_at' => $this->now() ), array( 'id' => $object_id ) );
			return;
		}

		if ( 'task' === $object_type ) {
			$current = $this->prepared_row( 'SELECT actual_finish_date, completed_at FROM ' . $this->table( 'tasks' ) . ' WHERE id = %d', array( $object_id ), \ARRAY_A );
			$finish  = is_array( $current ) && ! empty( $current['actual_finish_date'] )
				? (string) $current['actual_finish_date']
				: ( ( is_array( $current ) && ! empty( $current['completed_at'] ) ) ? (string) $current['completed_at'] : $this->now() );

			if ( 'approved' === $status ) {
				$this->wpdb->update(
					$this->table( 'tasks' ),
					array(
						'status'             => 'done',
						'completion_percent' => 100,
						'actual_finish_date' => $finish,
						'completed_at'       => $finish,
						'updated_at'         => $this->now(),
					),
					array( 'id' => $object_id )
				);
				return;
			}

			if ( in_array( $status, array( 'rejected', 'cancelled' ), true ) ) {
				$this->wpdb->update(
					$this->table( 'tasks' ),
					array(
						'status'       => 'in-progress',
						'completed_at' => null,
						'updated_at'   => $this->now(),
					),
					array( 'id' => $object_id )
				);
			}
		}
	}

	/**
	 * Decision timestamp helper.
	 *
	 * @param string $status Approval status.
	 * @return string|null
	 */
	private function decision_timestamp_for_status( string $status ): ?string {
		return in_array( $status, array( 'approved', 'rejected', 'cancelled' ), true ) ? $this->now() : null;
	}

	/**
	 * Resolve project manager id.
	 *
	 * @param int $project_id Project id.
	 * @return int
	 */
	private function get_project_manager_id( int $project_id ): int {
		return (int) $this->prepared_var( 'SELECT manager_user_id FROM ' . $this->table( 'projects' ) . ' WHERE id = %d', array( $project_id ) );
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

		$item['approver_label']   = $item['approver_user_id'] ? ( get_userdata( (int) $item['approver_user_id'] )->display_name ?? '' ) : '';
		$item['submitted_by_label'] = $item['submitted_by_user_id'] ? ( get_userdata( (int) $item['submitted_by_user_id'] )->display_name ?? '' ) : '';
		$item['object_label']     = $this->resolve_object_label( (string) $item['object_type'], (int) $item['object_id'] );
		$item['project_id']       = $this->resolve_project_id( (string) $item['object_type'], (int) $item['object_id'] );
		$item['project_label']    = $this->get_project_label( (int) $item['project_id'] );
		$item['can_edit']         = $this->access->can_edit_approval( (int) ( $item['id'] ?? 0 ) );
		return $item;
	}

	/**
	 * Resolve parent object label.
	 *
	 * @param string $object_type Object type.
	 * @param int    $object_id Object id.
	 * @return string
	 */
	private function resolve_object_label( string $object_type, int $object_id ): string {
		$table_map = array(
			'project' => 'projects',
			'task'    => 'tasks',
			'request' => 'requests',
			'risk'    => 'risks_issues',
			'issue'   => 'risks_issues',
		);

		if ( ! isset( $table_map[ $object_type ] ) || $object_id <= 0 ) {
			return '';
		}

		$table = $this->table( $table_map[ $object_type ] );
		$title = $this->prepared_var( 'SELECT title FROM ' . $table . ' WHERE id = %d', array( $object_id ) );
		return $title ? (string) $title : '';
	}

	/**
	 * Notify an approver about pending approval work.
	 *
	 * @param string $object_type Object type.
	 * @param int    $object_id Object id.
	 * @param int    $approver_user_id Approver user id.
	 * @param int    $submitted_by_user_id Submitter user id.
	 * @return void
	 */
	private function notify_pending_approval( string $object_type, int $object_id, int $approver_user_id, int $submitted_by_user_id ): void {
		if ( $approver_user_id <= 0 || $approver_user_id === $submitted_by_user_id ) {
			return;
		}

		$object_label     = $this->resolve_object_label( $object_type, $object_id );
		$object_type_label = $this->approval_object_type_label( $object_type );
		$submitter_label  = $submitted_by_user_id > 0 ? ( get_userdata( $submitted_by_user_id )->display_name ?? '' ) : '';
		$title            = __( 'Approval requested', 'coordina' );
		if ( $submitter_label ) {
			/* translators: 1: submitter name, 2: approval target label. */
			$body = sprintf( __( '%1$s submitted %2$s for your approval.', 'coordina' ), $submitter_label, $object_label ?: $object_type_label );
		} else {
			/* translators: %s: approval target label. */
			$body = sprintf( __( '%1$s is waiting for your approval.', 'coordina' ), $object_label ?: $object_type_label );
		}
		$url              = $this->approval_admin_url( $object_type, $object_id );

		$this->notifications->create( $approver_user_id, 'approval-requested', $title, $body, $url );
	}

	/**
	 * Build an admin URL for a pending approval target.
	 *
	 * @param string $object_type Object type.
	 * @param int    $object_id Object id.
	 * @return string
	 */
	private function approval_admin_url( string $object_type, int $object_id ): string {
		if ( 'task' === $object_type ) {
			$project_id = $this->resolve_project_id( $object_type, $object_id );
			$args       = array(
				'page'    => 'coordina-task',
				'task_id' => $object_id,
			);

			if ( $project_id > 0 ) {
				$args['project_id']  = $project_id;
				$args['project_tab'] = 'work';
			}

			return add_query_arg( $args, admin_url( 'admin.php' ) );
		}

		if ( 'request' === $object_type ) {
			return add_query_arg(
				array(
					'page'       => 'coordina-requests',
					'request_id' => $object_id,
				),
				admin_url( 'admin.php' )
			);
		}

		return add_query_arg( array( 'page' => 'coordina-approvals' ), admin_url( 'admin.php' ) );
	}

	/**
	 * Human label for approval object types.
	 *
	 * @param string $object_type Object type.
	 * @return string
	 */
	private function approval_object_type_label( string $object_type ): string {
		$labels = array(
			'project' => __( 'project', 'coordina' ),
			'task'    => __( 'task', 'coordina' ),
			'request' => __( 'request', 'coordina' ),
			'risk'    => __( 'risk', 'coordina' ),
			'issue'   => __( 'issue', 'coordina' ),
		);

		return $labels[ $object_type ] ?? __( 'item', 'coordina' );
	}

	/**
	 * Resolve project id from object.
	 *
	 * @param string $object_type Object type.
	 * @param int    $object_id Object id.
	 * @return int
	 */
	private function resolve_project_id( string $object_type, int $object_id ): int {
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

		$title = $this->prepared_var( 'SELECT title FROM ' . $this->table( 'projects' ) . ' WHERE id = %d', array( $project_id ) );
		return $title ? (string) $title : __( 'Project', 'coordina' );
	}
}
