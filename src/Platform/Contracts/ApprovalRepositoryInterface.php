<?php
/**
 * Contract for approval workflows used across core modules.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Contracts;

interface ApprovalRepositoryInterface {
	/**
	 * @param array<string, mixed> $args Query args.
	 * @return array<string, mixed>
	 */
	public function get_items( array $args ): array;

	/**
	 * @return array<string, mixed>
	 */
	public function find( int $id ): array;

	/**
	 * @param array<string, mixed> $data Approval payload.
	 * @return array<string, mixed>
	 */
	public function create( array $data ): array;

	/**
	 * @param array<string, mixed> $data Approval payload.
	 * @return array<string, mixed>
	 */
	public function update( int $id, array $data ): array;

	public function bulk_update_status( array $ids, string $status ): int;

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_pending_for_user( int $user_id ): array;

	/**
	 * @param array<string, mixed> $args Query args.
	 * @return array<string, mixed>
	 */
	public function get_for_project( int $project_id, array $args = array() ): array;

	/**
	 * @return array<string, mixed>
	 */
	public function get_project_summary( int $project_id ): array;

	/**
	 * @return array<string, mixed>
	 */
	public function get_latest_for_object( string $object_type, int $object_id ): array;

	/**
	 * @param array<string, mixed> $task Task payload.
	 */
	public function sync_for_task( array $task ): void;

	/**
	 * @param array<string, mixed> $request Request payload.
	 */
	public function sync_for_request( array $request ): void;
}
