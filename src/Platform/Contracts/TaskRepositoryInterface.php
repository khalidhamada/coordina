<?php
/**
 * Public contract for task operations.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Contracts;

interface TaskRepositoryInterface {
	/**
	 * @param array<string, mixed> $args Query args.
	 * @return array<string, mixed>
	 */
	public function get_items( array $args ): array;

	/**
	 * @param array<string, mixed> $args Query args.
	 * @return array<string, mixed>
	 */
	public function get_for_project( int $project_id, array $args = array() ): array;

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_groups_for_project( int $project_id ): array;

	/**
	 * @param array<string, mixed> $data Group payload.
	 * @return array<string, mixed>
	 */
	public function create_group( int $project_id, array $data ): array;

	/**
	 * @param array<string, mixed> $data Group payload.
	 * @return array<string, mixed>
	 */
	public function update_group( int $id, array $data ): array;

	public function delete_group( int $id ): bool;

	/**
	 * @return array<string, mixed>
	 */
	public function get_project_summary( int $project_id ): array;

	/**
	 * @return array<string, mixed>
	 */
	public function get_my_work( int $user_id ): array;

	/**
	 * @return array<string, mixed>
	 */
	public function find( int $id ): array;

	/**
	 * @param array<string, mixed> $data Task payload.
	 * @return array<string, mixed>
	 */
	public function create( array $data ): array;

	/**
	 * @param array<string, mixed> $data Task payload.
	 * @return array<string, mixed>
	 */
	public function update( int $id, array $data ): array;

	public function bulk_update_status( array $ids, string $status ): int;

	public function delete( int $id ): bool;
}
