<?php
/**
 * Contextual checklist repository.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Persistence;

use RuntimeException;

final class ChecklistRepository extends AbstractRepository {
	/**
	 * Supported checklist contexts.
	 *
	 * @var string[]
	 */
	private const SUPPORTED_CONTEXTS = array( 'project', 'task', 'milestone', 'risk', 'issue' );

	/**
	 * Default checklist header title.
	 */
	private const DEFAULT_TITLE = 'Checklist';

	/**
	 * Get grouped checklists for a context.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array<string, mixed>
	 */
	public function get_items( array $args ): array {
		$object_type = sanitize_key( (string) ( $args['object_type'] ?? '' ) );
		$object_id   = max( 0, (int) ( $args['object_id'] ?? 0 ) );

		if ( ! $this->is_supported_context( $object_type ) || $object_id <= 0 || ! $this->access->can_view_context( $object_type, $object_id ) ) {
			return $this->empty_collection( $object_type, $object_id );
		}

		$permissions = array(
			'canManage' => $this->access->can_manage_checklists_on_context( $object_type, $object_id ),
			'canToggle' => $this->access->can_toggle_checklists_on_context( $object_type, $object_id ),
		);
		$headers     = $this->wpdb->get_results(
			$this->wpdb->prepare(
				'SELECT * FROM ' . $this->table( 'checklists' ) . ' WHERE object_type = %s AND object_id = %d ORDER BY sort_order ASC, id ASC',
				$object_type,
				$object_id
			)
		);
		$items       = $this->wpdb->get_results(
			$this->wpdb->prepare(
				'SELECT * FROM ' . $this->table( 'checklist_items' ) . ' WHERE object_type = %s AND object_id = %d ORDER BY sort_order ASC, id ASC',
				$object_type,
				$object_id
			)
		);
		$grouped     = array();
		$flat_items  = array();

		foreach ( $items ?: array() as $row ) {
			$item                        = $this->map_item( $row );
			$checklist_id                = (int) ( $item['checklist_id'] ?? 0 );
			$grouped[ $checklist_id ]    = $grouped[ $checklist_id ] ?? array();
			$grouped[ $checklist_id ][]  = $item;
			$flat_items[]                = $item;
		}

		$checklists = array();
		foreach ( $headers ?: array() as $row ) {
			$header        = $this->map_checklist( $row );
			$header_items  = $grouped[ (int) $header['id'] ] ?? array();
			$header['items'] = $header_items;
			$header['summary'] = $this->get_summary_from_items( $header_items );
			$header['can_manage'] = $permissions['canManage'];
			$header['can_toggle'] = $permissions['canToggle'];
			$checklists[] = $header;
		}

		return array(
			'checklists'      => $checklists,
			'items'           => $flat_items,
			'total'           => count( $flat_items ),
			'checklist_total' => count( $checklists ),
			'summary'         => $this->get_summary_from_items( $flat_items ),
			'permissions'     => $permissions,
			'object_type'     => $object_type,
			'object_id'       => $object_id,
			'object_label'    => $this->resolve_context_label( $object_type, $object_id ),
		);
	}

	/**
	 * Get checklist summary for a context.
	 *
	 * @param string $object_type Context type.
	 * @param int    $object_id Context id.
	 * @return array<string, int>
	 */
	public function get_summary_for_context( string $object_type, int $object_id ): array {
		$collection = $this->get_items(
			array(
				'object_type' => $object_type,
				'object_id'   => $object_id,
			)
		);

		return is_array( $collection['summary'] ?? null ) ? $collection['summary'] : array(
			'total' => 0,
			'done'  => 0,
			'open'  => 0,
		);
	}

	/**
	 * Find a checklist header.
	 *
	 * @param int $id Checklist id.
	 * @return array<string, mixed>
	 */
	public function find( int $id ): array {
		$row       = $this->wpdb->get_row(
			$this->wpdb->prepare( 'SELECT * FROM ' . $this->table( 'checklists' ) . ' WHERE id = %d', $id )
		);
		$checklist = $this->map_checklist( $row );

		if ( empty( $checklist ) || ! $this->access->can_view_context( (string) ( $checklist['object_type'] ?? '' ), (int) ( $checklist['object_id'] ?? 0 ) ) ) {
			return array();
		}

		$items                = $this->get_items_for_checklist( (int) $checklist['id'] );
		$checklist['items']   = $items;
		$checklist['summary'] = $this->get_summary_from_items( $items );
		$checklist['can_manage'] = $this->access->can_manage_checklists_on_context( (string) $checklist['object_type'], (int) $checklist['object_id'] );
		$checklist['can_toggle'] = $this->access->can_toggle_checklists_on_context( (string) $checklist['object_type'], (int) $checklist['object_id'] );

		return $checklist;
	}

	/**
	 * Find a checklist item.
	 *
	 * @param int $id Item id.
	 * @return array<string, mixed>
	 */
	public function find_item( int $id ): array {
		$row  = $this->wpdb->get_row(
			$this->wpdb->prepare( 'SELECT * FROM ' . $this->table( 'checklist_items' ) . ' WHERE id = %d', $id )
		);
		$item = $this->map_item( $row );

		if ( empty( $item ) || ! $this->access->can_view_context( (string) ( $item['object_type'] ?? '' ), (int) ( $item['object_id'] ?? 0 ) ) ) {
			return array();
		}

		return $item;
	}

	/**
	 * Create a checklist header.
	 *
	 * @param array<string, mixed> $data Checklist payload.
	 * @return array<string, mixed>
	 */
	public function create( array $data ): array {
		$object_type = sanitize_key( (string) ( $data['object_type'] ?? '' ) );
		$object_id   = max( 0, (int) ( $data['object_id'] ?? 0 ) );
		$title       = sanitize_text_field( (string) ( $data['title'] ?? '' ) );

		if ( ! $this->is_supported_context( $object_type ) || ! $this->context_exists( $object_type, $object_id ) ) {
			throw new RuntimeException( __( 'A valid parent context is required for checklists.', 'coordina' ) );
		}

		if ( ! $this->access->can_manage_checklists_on_context( $object_type, $object_id ) ) {
			throw new RuntimeException( __( 'You are not allowed to manage checklists on this record.', 'coordina' ) );
		}

		if ( '' === $title ) {
			throw new RuntimeException( __( 'Checklist name is required.', 'coordina' ) );
		}

		$result = $this->wpdb->insert(
			$this->table( 'checklists' ),
			array(
				'project_id'  => $this->resolve_project_id_for_context( $object_type, $object_id ),
				'object_type' => $object_type,
				'object_id'   => $object_id,
				'title'       => $title,
				'sort_order'  => $this->next_checklist_sort_order( $object_type, $object_id ),
				'created_by'  => get_current_user_id(),
				'created_at'  => $this->now(),
				'updated_at'  => $this->now(),
			)
		);

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Checklist could not be created.', 'coordina' ) );
		}

		$this->log_activity( $object_type, $object_id, 'checklist_added', __( 'Added a checklist.', 'coordina' ) );

		return $this->find( (int) $this->wpdb->insert_id );
	}

	/**
	 * Update a checklist header.
	 *
	 * @param int                  $id Checklist id.
	 * @param array<string, mixed> $data Checklist payload.
	 * @return array<string, mixed>
	 */
	public function update( int $id, array $data ): array {
		$current = $this->find( $id );

		if ( empty( $current ) ) {
			throw new RuntimeException( __( 'Checklist could not be found.', 'coordina' ) );
		}

		if ( ! $this->access->can_manage_checklists_on_context( (string) $current['object_type'], (int) $current['object_id'] ) ) {
			throw new RuntimeException( __( 'You are not allowed to update this checklist.', 'coordina' ) );
		}

		$title = sanitize_text_field( (string) ( $data['title'] ?? $current['title'] ?? '' ) );

		if ( '' === $title ) {
			throw new RuntimeException( __( 'Checklist name is required.', 'coordina' ) );
		}

		$result = $this->wpdb->update(
			$this->table( 'checklists' ),
			array(
				'title'      => $title,
				'updated_at' => $this->now(),
			),
			array( 'id' => $id )
		);

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Checklist could not be updated.', 'coordina' ) );
		}

		if ( (string) $current['title'] !== $title ) {
			$this->log_activity( (string) $current['object_type'], (int) $current['object_id'], 'checklist_updated', __( 'Updated a checklist name.', 'coordina' ) );
		}

		return $this->find( $id );
	}

	/**
	 * Move a checklist header up or down.
	 *
	 * @param int    $id Checklist id.
	 * @param string $direction Move direction.
	 * @return array<string, mixed>
	 */
	public function move( int $id, string $direction ): array {
		$current = $this->find( $id );

		if ( empty( $current ) ) {
			throw new RuntimeException( __( 'Checklist could not be found.', 'coordina' ) );
		}

		if ( ! $this->access->can_manage_checklists_on_context( (string) $current['object_type'], (int) $current['object_id'] ) ) {
			throw new RuntimeException( __( 'You are not allowed to reorder checklists on this record.', 'coordina' ) );
		}

		$target = $this->find_neighbor(
			$this->table( 'checklists' ),
			array(
				'object_type' => (string) $current['object_type'],
				'object_id'   => (int) $current['object_id'],
			),
			(int) $current['sort_order'],
			$direction
		);

		if ( empty( $target ) ) {
			return $this->get_items(
				array(
					'object_type' => (string) $current['object_type'],
					'object_id'   => (int) $current['object_id'],
				)
			);
		}

		$this->swap_sort_orders( $this->table( 'checklists' ), (int) $current['id'], (int) $current['sort_order'], (int) $target['id'], (int) $target['sort_order'] );
		$this->log_activity( (string) $current['object_type'], (int) $current['object_id'], 'checklists_reordered', __( 'Reordered checklists.', 'coordina' ) );

		return $this->get_items(
			array(
				'object_type' => (string) $current['object_type'],
				'object_id'   => (int) $current['object_id'],
			)
		);
	}

	/**
	 * Delete a checklist header and its items.
	 *
	 * @param int $id Checklist id.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		$current = $this->find( $id );

		if ( empty( $current ) ) {
			throw new RuntimeException( __( 'Checklist could not be found.', 'coordina' ) );
		}

		if ( ! $this->access->can_manage_checklists_on_context( (string) $current['object_type'], (int) $current['object_id'] ) ) {
			throw new RuntimeException( __( 'You are not allowed to delete this checklist.', 'coordina' ) );
		}

		$this->wpdb->delete( $this->table( 'checklist_items' ), array( 'checklist_id' => $id ) );
		$result = $this->wpdb->delete( $this->table( 'checklists' ), array( 'id' => $id ) );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Checklist could not be deleted.', 'coordina' ) );
		}

		if ( $result > 0 ) {
			$this->log_activity( (string) $current['object_type'], (int) $current['object_id'], 'checklist_deleted', __( 'Removed a checklist.', 'coordina' ) );
		}

		return $result > 0;
	}

	/**
	 * Create a checklist item.
	 *
	 * @param array<string, mixed> $data Item payload.
	 * @return array<string, mixed>
	 */
	public function create_item( array $data ): array {
		$checklist_id = max( 0, (int) ( $data['checklist_id'] ?? 0 ) );
		$item_text    = sanitize_text_field( (string) ( $data['item_text'] ?? '' ) );
		$is_done      = ! empty( $data['is_done'] ) ? 1 : 0;
		$checklist    = $this->find( $checklist_id );

		if ( empty( $checklist ) ) {
			throw new RuntimeException( __( 'A valid checklist is required for checklist items.', 'coordina' ) );
		}

		if ( ! $this->access->can_manage_checklists_on_context( (string) $checklist['object_type'], (int) $checklist['object_id'] ) ) {
			throw new RuntimeException( __( 'You are not allowed to manage checklist items on this record.', 'coordina' ) );
		}

		if ( '' === $item_text ) {
			throw new RuntimeException( __( 'Checklist item text is required.', 'coordina' ) );
		}

		$result = $this->wpdb->insert(
			$this->table( 'checklist_items' ),
			array(
				'checklist_id' => $checklist_id,
				'project_id'   => (int) $checklist['project_id'],
				'object_type'  => (string) $checklist['object_type'],
				'object_id'    => (int) $checklist['object_id'],
				'item_text'    => $item_text,
				'is_done'      => $is_done,
				'sort_order'   => $this->next_item_sort_order( $checklist_id ),
				'created_by'   => get_current_user_id(),
				'created_at'   => $this->now(),
				'updated_at'   => $this->now(),
			)
		);

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Checklist item could not be created.', 'coordina' ) );
		}

		$this->log_activity( (string) $checklist['object_type'], (int) $checklist['object_id'], 'checklist_item_added', __( 'Added a checklist item.', 'coordina' ) );

		return $this->find_item( (int) $this->wpdb->insert_id );
	}

	/**
	 * Update a checklist item.
	 *
	 * @param int                  $id Item id.
	 * @param array<string, mixed> $data Item payload.
	 * @return array<string, mixed>
	 */
	public function update_item( int $id, array $data ): array {
		$current = $this->find_item( $id );

		if ( empty( $current ) ) {
			throw new RuntimeException( __( 'Checklist item could not be found.', 'coordina' ) );
		}

		if ( ! $this->access->can_manage_checklists_on_context( (string) $current['object_type'], (int) $current['object_id'] ) ) {
			throw new RuntimeException( __( 'You are not allowed to update this checklist item.', 'coordina' ) );
		}

		$item_text = sanitize_text_field( (string) ( $data['item_text'] ?? $current['item_text'] ?? '' ) );

		if ( '' === $item_text ) {
			throw new RuntimeException( __( 'Checklist item text is required.', 'coordina' ) );
		}

		$result = $this->wpdb->update(
			$this->table( 'checklist_items' ),
			array(
				'item_text'  => $item_text,
				'updated_at' => $this->now(),
			),
			array( 'id' => $id )
		);

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Checklist item could not be updated.', 'coordina' ) );
		}

		if ( (string) $current['item_text'] !== $item_text ) {
			$this->log_activity( (string) $current['object_type'], (int) $current['object_id'], 'checklist_item_updated', __( 'Updated a checklist item.', 'coordina' ) );
		}

		return $this->find_item( $id );
	}

	/**
	 * Toggle a checklist item.
	 *
	 * @param int  $id Item id.
	 * @param bool $is_done New done state.
	 * @return array<string, mixed>
	 */
	public function toggle_item( int $id, bool $is_done ): array {
		$current = $this->find_item( $id );

		if ( empty( $current ) ) {
			throw new RuntimeException( __( 'Checklist item could not be found.', 'coordina' ) );
		}

		if ( ! $this->access->can_toggle_checklists_on_context( (string) $current['object_type'], (int) $current['object_id'] ) ) {
			throw new RuntimeException( __( 'You are not allowed to change this checklist item.', 'coordina' ) );
		}

		$result = $this->wpdb->update(
			$this->table( 'checklist_items' ),
			array(
				'is_done'    => $is_done ? 1 : 0,
				'updated_at' => $this->now(),
			),
			array( 'id' => $id )
		);

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Checklist item could not be updated.', 'coordina' ) );
		}

		if ( (int) $current['is_done'] !== ( $is_done ? 1 : 0 ) ) {
			$this->log_activity(
				(string) $current['object_type'],
				(int) $current['object_id'],
				$is_done ? 'checklist_item_completed' : 'checklist_item_reopened',
				$is_done ? __( 'Completed a checklist item.', 'coordina' ) : __( 'Reopened a checklist item.', 'coordina' )
			);
		}

		return $this->find_item( $id );
	}

	/**
	 * Move a checklist item up or down.
	 *
	 * @param int    $id Item id.
	 * @param string $direction Move direction.
	 * @return array<string, mixed>
	 */
	public function move_item( int $id, string $direction ): array {
		$current = $this->find_item( $id );

		if ( empty( $current ) ) {
			throw new RuntimeException( __( 'Checklist item could not be found.', 'coordina' ) );
		}

		if ( ! $this->access->can_manage_checklists_on_context( (string) $current['object_type'], (int) $current['object_id'] ) ) {
			throw new RuntimeException( __( 'You are not allowed to reorder checklist items on this record.', 'coordina' ) );
		}

		$target = $this->find_neighbor(
			$this->table( 'checklist_items' ),
			array(
				'checklist_id' => (int) $current['checklist_id'],
			),
			(int) $current['sort_order'],
			$direction
		);

		if ( empty( $target ) ) {
			return $this->get_items(
				array(
					'object_type' => (string) $current['object_type'],
					'object_id'   => (int) $current['object_id'],
				)
			);
		}

		$this->swap_sort_orders( $this->table( 'checklist_items' ), (int) $current['id'], (int) $current['sort_order'], (int) $target['id'], (int) $target['sort_order'] );
		$this->log_activity( (string) $current['object_type'], (int) $current['object_id'], 'checklist_items_reordered', __( 'Reordered checklist items.', 'coordina' ) );

		return $this->get_items(
			array(
				'object_type' => (string) $current['object_type'],
				'object_id'   => (int) $current['object_id'],
			)
		);
	}

	/**
	 * Delete a checklist item.
	 *
	 * @param int $id Item id.
	 * @return bool
	 */
	public function delete_item( int $id ): bool {
		$current = $this->find_item( $id );

		if ( empty( $current ) ) {
			throw new RuntimeException( __( 'Checklist item could not be found.', 'coordina' ) );
		}

		if ( ! $this->access->can_manage_checklists_on_context( (string) $current['object_type'], (int) $current['object_id'] ) ) {
			throw new RuntimeException( __( 'You are not allowed to delete this checklist item.', 'coordina' ) );
		}

		$result = $this->wpdb->delete( $this->table( 'checklist_items' ), array( 'id' => $id ) );

		if ( false === $result ) {
			throw new RuntimeException( $this->wpdb->last_error ?: __( 'Checklist item could not be deleted.', 'coordina' ) );
		}

		if ( $result > 0 ) {
			$this->log_activity( (string) $current['object_type'], (int) $current['object_id'], 'checklist_item_deleted', __( 'Removed a checklist item.', 'coordina' ) );
		}

		return $result > 0;
	}

	/**
	 * Replace the default checklist for a context from textarea input.
	 *
	 * @param string $object_type Context type.
	 * @param int    $object_id Context id.
	 * @param mixed  $value Raw payload.
	 * @return void
	 */
	public function replace_from_text( string $object_type, int $object_id, $value ): void {
		$object_type = sanitize_key( $object_type );
		$object_id   = max( 0, $object_id );

		if ( ! $this->is_supported_context( $object_type ) || $object_id <= 0 || ! $this->context_exists( $object_type, $object_id ) ) {
			return;
		}

		$checklist_id = $this->get_or_create_default_checklist_id( $object_type, $object_id );
		$table        = $this->table( 'checklist_items' );
		$project_id   = $this->resolve_project_id_for_context( $object_type, $object_id );
		$lines        = is_array( $value ) ? $value : preg_split( '/\r\n|\r|\n/', (string) $value );
		$order        = 10;

		$this->wpdb->delete(
			$table,
			array(
				'checklist_id' => $checklist_id,
			)
		);

		foreach ( $lines ?: array() as $line ) {
			$text = trim( (string) $line );

			if ( '' === $text ) {
				continue;
			}

			$is_done = 0;
			if ( preg_match( '/^\[(x|X)\]\s*(.+)$/', $text, $matches ) ) {
				$is_done = 1;
				$text    = trim( $matches[2] );
			} elseif ( preg_match( '/^\[\s\]\s*(.+)$/', $text, $matches ) ) {
				$text = trim( $matches[1] );
			}

			$text = sanitize_text_field( $text );

			if ( '' === $text ) {
				continue;
			}

			$this->wpdb->insert(
				$table,
				array(
					'checklist_id' => $checklist_id,
					'project_id'   => $project_id,
					'object_type'  => $object_type,
					'object_id'    => $object_id,
					'item_text'    => $text,
					'is_done'      => $is_done,
					'sort_order'   => $order,
					'created_by'   => get_current_user_id(),
					'created_at'   => $this->now(),
					'updated_at'   => $this->now(),
				)
			);
			$order += 10;
		}
	}

	/**
	 * Build an empty checklist collection response.
	 *
	 * @param string $object_type Context type.
	 * @param int    $object_id Context id.
	 * @return array<string, mixed>
	 */
	private function empty_collection( string $object_type, int $object_id ): array {
		return array(
			'checklists'      => array(),
			'items'           => array(),
			'total'           => 0,
			'checklist_total' => 0,
			'summary'         => array( 'total' => 0, 'done' => 0, 'open' => 0 ),
			'permissions'     => array(
				'canManage' => false,
				'canToggle' => false,
			),
			'object_type'     => $object_type,
			'object_id'       => $object_id,
			'object_label'    => '',
		);
	}

	/**
	 * Get all items for a checklist header.
	 *
	 * @param int $checklist_id Checklist id.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_items_for_checklist( int $checklist_id ): array {
		if ( $checklist_id <= 0 ) {
			return array();
		}

		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare(
				'SELECT * FROM ' . $this->table( 'checklist_items' ) . ' WHERE checklist_id = %d ORDER BY sort_order ASC, id ASC',
				$checklist_id
			)
		);

		return array_map( array( $this, 'map_item' ), $rows ?: array() );
	}

	/**
	 * Map checklist header row.
	 *
	 * @param object|array<string, mixed>|null $row Raw row.
	 * @return array<string, mixed>
	 */
	private function map_checklist( $row ): array {
		$item = is_array( $row ) ? $row : $this->row_to_array( $row );

		if ( empty( $item ) ) {
			return array();
		}

		$item['id']          = (int) ( $item['id'] ?? 0 );
		$item['project_id']  = (int) ( $item['project_id'] ?? 0 );
		$item['object_id']   = (int) ( $item['object_id'] ?? 0 );
		$item['sort_order']  = (int) ( $item['sort_order'] ?? 0 );
		$item['created_by']  = (int) ( $item['created_by'] ?? 0 );
		$item['object_type'] = sanitize_key( (string) ( $item['object_type'] ?? '' ) );

		return $item;
	}

	/**
	 * Map checklist item row.
	 *
	 * @param object|array<string, mixed>|null $row Raw row.
	 * @return array<string, mixed>
	 */
	private function map_item( $row ): array {
		$item = is_array( $row ) ? $row : $this->row_to_array( $row );

		if ( empty( $item ) ) {
			return array();
		}

		$object_type           = sanitize_key( (string) ( $item['object_type'] ?? '' ) );
		$object_id             = (int) ( $item['object_id'] ?? 0 );
		$item['id']            = (int) ( $item['id'] ?? 0 );
		$item['checklist_id']  = (int) ( $item['checklist_id'] ?? 0 );
		$item['project_id']    = (int) ( $item['project_id'] ?? 0 );
		$item['object_type']   = $object_type;
		$item['object_id']     = $object_id;
		$item['is_done']       = ! empty( $item['is_done'] ) ? 1 : 0;
		$item['sort_order']    = (int) ( $item['sort_order'] ?? 0 );
		$item['can_manage']    = $this->access->can_manage_checklists_on_context( $object_type, $object_id );
		$item['can_toggle']    = $this->access->can_toggle_checklists_on_context( $object_type, $object_id );

		return $item;
	}

	/**
	 * Determine whether a checklist context is supported.
	 *
	 * @param string $object_type Context type.
	 * @return bool
	 */
	private function is_supported_context( string $object_type ): bool {
		return in_array( sanitize_key( $object_type ), self::SUPPORTED_CONTEXTS, true );
	}

	/**
	 * Get checklist summary from item rows.
	 *
	 * @param array<int, array<string, mixed>> $items Items.
	 * @return array<string, int>
	 */
	private function get_summary_from_items( array $items ): array {
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
	 * Get or create the default checklist header for legacy textarea workflows.
	 *
	 * @param string $object_type Context type.
	 * @param int    $object_id Context id.
	 * @return int
	 */
	private function get_or_create_default_checklist_id( string $object_type, int $object_id ): int {
		$existing_id = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				'SELECT id FROM ' . $this->table( 'checklists' ) . ' WHERE object_type = %s AND object_id = %d AND title = %s ORDER BY sort_order ASC, id ASC LIMIT 1',
				$object_type,
				$object_id,
				self::DEFAULT_TITLE
			)
		);

		if ( $existing_id > 0 ) {
			return $existing_id;
		}

		$this->wpdb->insert(
			$this->table( 'checklists' ),
			array(
				'project_id'  => $this->resolve_project_id_for_context( $object_type, $object_id ),
				'object_type' => $object_type,
				'object_id'   => $object_id,
				'title'       => self::DEFAULT_TITLE,
				'sort_order'  => $this->next_checklist_sort_order( $object_type, $object_id ),
				'created_by'  => get_current_user_id(),
				'created_at'  => $this->now(),
				'updated_at'  => $this->now(),
			)
		);

		return (int) $this->wpdb->insert_id;
	}

	/**
	 * Get next checklist sort order for a context.
	 *
	 * @param string $object_type Context type.
	 * @param int    $object_id Context id.
	 * @return int
	 */
	private function next_checklist_sort_order( string $object_type, int $object_id ): int {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				'SELECT COALESCE(MAX(sort_order), 0) + 10 FROM ' . $this->table( 'checklists' ) . ' WHERE object_type = %s AND object_id = %d',
				$object_type,
				$object_id
			)
		);
	}

	/**
	 * Get next item sort order for a checklist.
	 *
	 * @param int $checklist_id Checklist id.
	 * @return int
	 */
	private function next_item_sort_order( int $checklist_id ): int {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				'SELECT COALESCE(MAX(sort_order), 0) + 10 FROM ' . $this->table( 'checklist_items' ) . ' WHERE checklist_id = %d',
				$checklist_id
			)
		);
	}

	/**
	 * Find neighboring row for move operations.
	 *
	 * @param string              $table Table name.
	 * @param array<string, mixed> $where Exact match constraints.
	 * @param int                 $sort_order Current sort order.
	 * @param string              $direction Move direction.
	 * @return array<string, mixed>
	 */
	private function find_neighbor( string $table, array $where, int $sort_order, string $direction ): array {
		$direction = 'down' === $direction ? 'down' : 'up';
		$operator  = 'down' === $direction ? '>' : '<';
		$order     = 'down' === $direction ? 'ASC' : 'DESC';
		$clauses   = array();
		$params    = array();

		foreach ( $where as $column => $value ) {
			if ( is_int( $value ) || ctype_digit( (string) $value ) ) {
				$clauses[] = "{$column} = %d";
				$params[]  = (int) $value;
			} else {
				$clauses[] = "{$column} = %s";
				$params[]  = (string) $value;
			}
		}

		$clauses[] = "sort_order {$operator} %d";
		$params[]  = $sort_order;

		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $clauses ) . " ORDER BY sort_order {$order}, id {$order} LIMIT 1";
		$row = $this->wpdb->get_row( $this->wpdb->prepare( $sql, $params ), ARRAY_A );

		return is_array( $row ) ? $row : array();
	}

	/**
	 * Swap sort order values between two rows.
	 *
	 * @param string $table Table name.
	 * @param int    $first_id First row id.
	 * @param int    $first_sort First sort value.
	 * @param int    $second_id Second row id.
	 * @param int    $second_sort Second sort value.
	 * @return void
	 */
	private function swap_sort_orders( string $table, int $first_id, int $first_sort, int $second_id, int $second_sort ): void {
		$now = $this->now();

		$this->wpdb->update(
			$table,
			array(
				'sort_order' => $second_sort,
				'updated_at' => $now,
			),
			array( 'id' => $first_id )
		);
		$this->wpdb->update(
			$table,
			array(
				'sort_order' => $first_sort,
				'updated_at' => $now,
			),
			array( 'id' => $second_id )
		);
	}

	/**
	 * Determine whether a context row exists.
	 *
	 * @param string $object_type Context type.
	 * @param int    $object_id Context id.
	 * @return bool
	 */
	protected function context_exists( string $object_type, int $object_id ): bool {
		if ( $object_id <= 0 ) {
			return false;
		}

		$table = '';

		switch ( $object_type ) {
			case 'project':
				$table = $this->table( 'projects' );
				break;
			case 'task':
				$table = $this->table( 'tasks' );
				break;
			case 'milestone':
				$table = $this->table( 'milestones' );
				break;
			case 'risk':
			case 'issue':
				$table = $this->table( 'risks_issues' );
				break;
		}

		if ( '' === $table ) {
			return false;
		}

		$found = (int) $this->wpdb->get_var( $this->wpdb->prepare( "SELECT id FROM {$table} WHERE id = %d", $object_id ) );

		if ( $found <= 0 ) {
			return false;
		}

		if ( 'risk' === $object_type || 'issue' === $object_type ) {
			$stored_type = (string) $this->wpdb->get_var( $this->wpdb->prepare( 'SELECT object_type FROM ' . $table . ' WHERE id = %d', $object_id ) );
			return sanitize_key( $stored_type ) === $object_type;
		}

		return true;
	}

	/**
	 * Resolve project id for a context.
	 *
	 * @param string $object_type Context type.
	 * @param int    $object_id Context id.
	 * @return int
	 */
	protected function resolve_project_id_for_context( string $object_type, int $object_id ): int {
		if ( 'project' === $object_type ) {
			return $object_id;
		}

		switch ( $object_type ) {
			case 'task':
				return (int) $this->wpdb->get_var( $this->wpdb->prepare( 'SELECT project_id FROM ' . $this->table( 'tasks' ) . ' WHERE id = %d', $object_id ) );
			case 'milestone':
				return (int) $this->wpdb->get_var( $this->wpdb->prepare( 'SELECT project_id FROM ' . $this->table( 'milestones' ) . ' WHERE id = %d', $object_id ) );
			case 'risk':
			case 'issue':
				return (int) $this->wpdb->get_var( $this->wpdb->prepare( 'SELECT project_id FROM ' . $this->table( 'risks_issues' ) . ' WHERE id = %d', $object_id ) );
		}

		return 0;
	}

	/**
	 * Resolve context label.
	 *
	 * @param string $object_type Context type.
	 * @param int    $object_id Context id.
	 * @return string
	 */
	protected function resolve_context_label( string $object_type, int $object_id ): string {
		if ( $object_id <= 0 ) {
			return '';
		}

		switch ( $object_type ) {
			case 'project':
				return (string) $this->wpdb->get_var( $this->wpdb->prepare( 'SELECT title FROM ' . $this->table( 'projects' ) . ' WHERE id = %d', $object_id ) );
			case 'task':
				return (string) $this->wpdb->get_var( $this->wpdb->prepare( 'SELECT title FROM ' . $this->table( 'tasks' ) . ' WHERE id = %d', $object_id ) );
			case 'milestone':
				return (string) $this->wpdb->get_var( $this->wpdb->prepare( 'SELECT title FROM ' . $this->table( 'milestones' ) . ' WHERE id = %d', $object_id ) );
			case 'risk':
			case 'issue':
				return (string) $this->wpdb->get_var( $this->wpdb->prepare( 'SELECT title FROM ' . $this->table( 'risks_issues' ) . ' WHERE id = %d', $object_id ) );
		}

		return '';
	}
}
