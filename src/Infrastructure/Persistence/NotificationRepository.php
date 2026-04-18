<?php
/**
 * Notification repository.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Persistence;

use Coordina\Platform\Contracts\NotificationRepositoryInterface;

final class NotificationRepository extends AbstractRepository implements NotificationRepositoryInterface {
	/**
	 * Fetch notifications for a user.
	 *
	 * @param int $user_id User id.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_for_user( int $user_id ): array {
		$table = $this->table( 'notifications' );
		$sql   = "SELECT * FROM {$table} WHERE user_id = %d ORDER BY is_read ASC, created_at DESC LIMIT 30";
		$rows  = $this->prepared_results( $sql, array( $user_id ) );
		return array_map( array( $this, 'map_item' ), $rows ?: array() );
	}

	/**
	 * Create a notification.
	 *
	 * @param int    $user_id Recipient id.
	 * @param string $type Notification type.
	 * @param string $title Notification title.
	 * @param string $body Notification body.
	 * @param string $action_url Target URL.
	 * @return array<string, mixed>
	 */
	public function create( int $user_id, string $type, string $title, string $body = '', string $action_url = '' ): array {
		$user_id = max( 0, $user_id );

		if ( $user_id <= 0 || '' === trim( $title ) ) {
			return array();
		}

		$clean = array(
			'user_id'    => $user_id,
			'type'       => sanitize_key( $type ),
			'title'      => sanitize_text_field( $title ),
			'body'       => sanitize_textarea_field( $body ),
			'action_url' => esc_url_raw( $action_url ),
			'is_read'    => 0,
			'created_at' => $this->now(),
		);

		$result = $this->wpdb->insert( $this->table( 'notifications' ), $clean );

		if ( false === $result ) {
			return array();
		}

		$row = $this->prepared_row( 'SELECT * FROM ' . $this->table( 'notifications' ) . ' WHERE id = %d', array( (int) $this->wpdb->insert_id ) );

		return $this->map_item( $row );
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

	/**
	 * Mark all notifications as read for a user.
	 *
	 * @param int $user_id User id.
	 * @return int
	 */
	public function mark_all_read( int $user_id ): int {
		$result = $this->wpdb->update(
			$this->table( 'notifications' ),
			array(
				'is_read' => 1,
				'read_at' => $this->now(),
			),
			array(
				'user_id' => max( 0, $user_id ),
				'is_read' => 0,
			)
		);

		return false === $result ? 0 : (int) $result;
	}

	/**
	 * Normalize notification payloads for REST consumers.
	 *
	 * @param object|array<string, mixed>|null $row Raw database row.
	 * @return array<string, mixed>
	 */
	private function map_item( $row ): array {
		$item = $this->row_to_array( $row );

		if ( empty( $item ) ) {
			return array();
		}

		$item['id']      = (int) ( $item['id'] ?? 0 );
		$item['user_id'] = (int) ( $item['user_id'] ?? 0 );
		$item['is_read'] = ! empty( $item['is_read'] ) && '0' !== (string) $item['is_read'];

		return $item;
	}
}
