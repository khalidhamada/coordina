<?php
/**
 * Public contract for registered context metadata and routes.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Contracts;

interface ContextResolverInterface {
	/**
	 * @return array<string, mixed>
	 */
	public function definition( string $slug ): array;

	/**
	 * @return array<int, string>
	 */
	public function slugs(): array;

	/**
	 * @return array<int, string>
	 */
	public function slugs_for_flag( string $flag ): array;

	public function label( string $slug, string $fallback = '' ): string;

	/**
	 * @return array<string, mixed>
	 */
	public function route( string $slug, int $object_id, int $project_id = 0 ): array;
}
