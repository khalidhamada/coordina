<?php
/**
 * Saved view repository.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Persistence;

final class SavedViewRepository extends AbstractRepository {
	/**
	 * Fetch saved views for a user and module.
	 *
	 * @param int    $user_id User id.
	 * @param string $module Module key.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_for_user( int $user_id, string $module ): array {
		$table = $this->table( 'saved_views' );
		$sql   = "SELECT * FROM {$table} WHERE user_id = %d AND module = %s ORDER BY is_default DESC, updated_at DESC";
		$rows  = $this->prepared_results( $sql, array( $user_id, $module ) );
		return array_map( array( $this, 'map_view' ), $rows ?: array() );
	}

	/**
	 * Create a saved view.
	 *
	 * @param int                  $user_id User id.
	 * @param array<string, mixed> $data View data.
	 * @return array<string, mixed>
	 */
	public function create( int $user_id, array $data ): array {
		$table      = $this->table( 'saved_views' );
		$is_default = ! empty( $data['is_default'] );
		$now        = $this->now();

		if ( $is_default ) {
			$this->wpdb->update( $table, array( 'is_default' => 0 ), array( 'user_id' => $user_id, 'module' => sanitize_key( (string) $data['module'] ) ) );
		}

		$this->wpdb->insert(
			$table,
			array(
				'user_id'     => $user_id,
				'module'      => sanitize_key( (string) $data['module'] ),
				'view_name'   => sanitize_text_field( (string) ( $data['view_name'] ?? '' ) ),
				'view_config' => wp_json_encode( $data['view_config'] ?? array() ),
				'is_default'  => $is_default ? 1 : 0,
				'created_at'  => $now,
				'updated_at'  => $now,
			)
		);

		return $this->map_view( $this->prepared_row( 'SELECT * FROM ' . $table . ' WHERE id = %d', array( (int) $this->wpdb->insert_id ) ) );
	}

	/**
	 * Map view row.
	 *
	 * @param object|null $row Row object.
	 * @return array<string, mixed>
	 */
	private function map_view( $row ): array {
		$item = $this->row_to_array( $row );

		if ( empty( $item ) ) {
			return array();
		}

		$item['view_config'] = ! empty( $item['view_config'] ) ? json_decode( (string) $item['view_config'], true ) : array();
		return $item;
	}
}