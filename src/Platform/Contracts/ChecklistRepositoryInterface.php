<?php
/**
 * Contract for contextual checklists.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Contracts;

interface ChecklistRepositoryInterface {
	/**
	 * @param array<string, mixed> $args Query args.
	 * @return array<string, mixed>
	 */
	public function get_items( array $args ): array;

	/**
	 * @return array<string, mixed>
	 */
	public function get_summary_for_context( string $object_type, int $object_id ): array;

	/**
	 * @return array<string, mixed>
	 */
	public function find( int $id ): array;

	/**
	 * @return array<string, mixed>
	 */
	public function find_item( int $id ): array;

	/**
	 * @param array<string, mixed> $data Checklist payload.
	 * @return array<string, mixed>
	 */
	public function create( array $data ): array;

	/**
	 * @param array<string, mixed> $data Checklist payload.
	 * @return array<string, mixed>
	 */
	public function update( int $id, array $data ): array;

	/**
	 * @return array<string, mixed>
	 */
	public function move( int $id, string $direction ): array;

	public function delete( int $id ): bool;

	/**
	 * @param array<string, mixed> $data Checklist-item payload.
	 * @return array<string, mixed>
	 */
	public function create_item( array $data ): array;

	/**
	 * @param array<string, mixed> $data Checklist-item payload.
	 * @return array<string, mixed>
	 */
	public function update_item( int $id, array $data ): array;

	/**
	 * @return array<string, mixed>
	 */
	public function toggle_item( int $id, bool $is_done ): array;

	/**
	 * @return array<string, mixed>
	 */
	public function move_item( int $id, string $direction ): array;

	public function delete_item( int $id ): bool;

	/**
	 * @param mixed $value Checklist textarea payload.
	 */
	public function replace_from_text( string $object_type, int $object_id, $value ): void;
}
