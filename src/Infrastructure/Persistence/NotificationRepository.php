<?php
/**
 * Notification repository.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Persistence;

final class NotificationRepository extends AbstractRepository {
	/**
	 * Fetch notifications for a user.
	 *
	 * @param int $user_id User id.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_for_user( int $user_id ): array {
		$table = $this->table( 'notifications' );
		$sql   = "SELECT * FROM {$table} WHERE user_id = %d ORDER BY is_read ASC, created_at DESC LIMIT 30";
		$rows  = $this->wpdb->get_results( $this->wpdb->prepare( $sql, $user_id ) );
		return array_map( array( $this, 'row_to_array' ), $rows ?: array() );
	}

	/**
	 * Mark notification read state.
	 *
	 * @param int  $id Notification id.
	 * @param bool $is_read Read flag.
	 * @return bool
	 */
	public function set_read_state( int $id, int $user_id, bool $is_read ): bool {
		$result = $this->wpdb->update(
			$this->table( 'notifications' ),
			array(
				'is_read' => $is_read ? 1 : 0,
				'read_at' => $is_read ? $this->now() : null,
			),
			array(
				'id'      => $id,
				'user_id' => $user_id,
			)
		);

		return false !== $result;
	}
}