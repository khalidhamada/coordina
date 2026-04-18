<?php
/**
 * Registry for context object metadata.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Registry;

use Coordina\Platform\Contracts\ContextResolverInterface;

final class ContextTypeRegistry implements ContextResolverInterface {
	/**
	 * Registered context types.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private $types = array();

	/**
	 * Add one context type definition.
	 *
	 * @param string               $slug Type slug.
	 * @param array<string, mixed> $definition Type definition.
	 */
	public function add( string $slug, array $definition ): void {
		$this->types[ $slug ] = $definition;
	}

	/**
	 * Get all context type definitions.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function all(): array {
		return $this->types;
	}

	/**
	 * Get one context type definition.
	 *
	 * @param string $slug Type slug.
	 * @return array<string, mixed>
	 */
	public function definition( string $slug ): array {
		return is_array( $this->types[ $slug ] ?? null ) ? $this->types[ $slug ] : array();
	}

	/**
	 * Get all registered type slugs.
	 *
	 * @return array<int, string>
	 */
	public function slugs(): array {
		return array_keys( $this->types );
	}

	/**
	 * Get registered type slugs filtered by a boolean flag.
	 *
	 * @param string $flag Flag key.
	 * @return array<int, string>
	 */
	public function slugs_for_flag( string $flag ): array {
		$slugs = array();

		foreach ( $this->types as $slug => $definition ) {
			if ( ! empty( $definition[ $flag ] ) ) {
				$slugs[] = $slug;
			}
		}

		return $slugs;
	}

	/**
	 * Resolve a user-facing label for a type.
	 *
	 * @param string $slug Type slug.
	 * @param string $fallback Fallback label.
	 * @return string
	 */
	public function label( string $slug, string $fallback = '' ): string {
		if ( ! isset( $this->types[ $slug ]['label'] ) ) {
			return $fallback;
		}

		return (string) $this->types[ $slug ]['label'];
	}

	/**
	 * Build a route payload for a context type.
	 *
	 * @param string $slug Type slug.
	 * @param int    $object_id Object id.
	 * @param int    $project_id Project id.
	 * @return array<string, mixed>
	 */
	public function route( string $slug, int $object_id, int $project_id = 0 ): array {
		if ( ! isset( $this->types[ $slug ] ) ) {
			return array( 'page' => 'coordina-dashboard' );
		}

		$definition = $this->types[ $slug ];

		if ( isset( $definition['route_callback'] ) && is_callable( $definition['route_callback'] ) ) {
			return (array) $definition['route_callback']( $object_id, $project_id );
		}

		$route = is_array( $definition['route'] ?? null ) ? $definition['route'] : array();

		if ( isset( $route['param'] ) ) {
			$route[ $route['param'] ] = $object_id;
			unset( $route['param'] );
		}

		if ( ! empty( $route['include_project_id'] ) ) {
			$route['project_id'] = $project_id;
			unset( $route['include_project_id'] );
		}

		if ( ! empty( $route['project_tab_when_project'] ) ) {
			$route['project_tab'] = $project_id > 0 ? (string) $route['project_tab_when_project'] : '';
			unset( $route['project_tab_when_project'] );
		}

		return $route;
	}
}
