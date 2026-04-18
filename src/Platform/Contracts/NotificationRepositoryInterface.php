<?php
/**
 * Public contract for notifications.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Contracts;

interface NotificationRepositoryInterface {
	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_for_user( int $user_id ): array;

	/**
	 * @return array<string, mixed>
	 */
	public function create( int $user_id, string $type, string $title, string $body = '', string $action_url = '' ): array;

	public function set_read_state( int $id, int $user_id, bool $is_read ): bool;

	public function mark_all_read( int $user_id ): int;
}
