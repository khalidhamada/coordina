<?php
/**
 * Public contract for Coordina settings access.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Contracts;

interface SettingsStoreInterface {
	/**
	 * @return array<string, mixed>
	 */
	public function get(): array;

	/**
	 * @param array<string, mixed> $data Settings payload.
	 * @return array<string, mixed>
	 */
	public function update( array $data ): array;

	/**
	 * @return array<string, mixed>
	 */
	public function get_dropdowns(): array;

	/**
	 * @return array<string, array<int, string>>
	 */
	public function choice_lists(): array;
}
