<?php
/**
 * Registry for admin pages.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Registry;

final class AdminPageRegistry {
	/**
	 * Registered page definitions.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private $pages = array();

	/**
	 * Add one page definition.
	 *
	 * @param string               $slug Page slug.
	 * @param array<string, mixed> $page Page configuration.
	 */
	public function add( string $slug, array $page ): void {
		$this->pages[ $slug ] = $page;
	}

	/**
	 * Get every registered page definition.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function all(): array {
		return $this->pages;
	}
}
