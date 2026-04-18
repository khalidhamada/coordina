<?php
/**
 * Public contract for project operations.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Contracts;

interface ProjectRepositoryInterface {
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
	 * @return array<string, mixed>
	 */
	public function get_workspace_summary( int $id ): array;

	/**
	 * @param array<string, mixed> $data Project data.
	 * @return array<string, mixed>
	 */
	public function create( array $data ): array;

	/**
	 * @param array<string, mixed> $data Project data.
	 * @return array<string, mixed>
	 */
	public function update( int $id, array $data ): array;

	/**
	 * @return array<string, mixed>
	 */
	public function get_settings( int $id ): array;

	/**
	 * @param array<string, mixed> $data Settings data.
	 * @return array<string, mixed>
	 */
	public function update_settings( int $id, array $data ): array;

	public function bulk_update_status( array $ids, string $status ): int;

	public function delete( int $id ): bool;
}
