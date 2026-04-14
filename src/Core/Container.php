<?php
/**
 * Minimal service container.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Core;

use InvalidArgumentException;

final class Container {
	/**
	 * Service factories.
	 *
	 * @var array<string, callable>
	 */
	private $factories = array();

	/**
	 * Resolved services.
	 *
	 * @var array<string, mixed>
	 */
	private $instances = array();

	/**
	 * Register a service factory.
	 *
	 * @param string   $id      Service id.
	 * @param callable $factory Factory callback.
	 */
	public function set( string $id, callable $factory ): void {
		$this->factories[ $id ] = $factory;
	}

	/**
	 * Resolve a service.
	 *
	 * @param string $id Service id.
	 * @return mixed
	 */
	public function get( string $id ) {
		if ( isset( $this->instances[ $id ] ) ) {
			return $this->instances[ $id ];
		}

		if ( ! isset( $this->factories[ $id ] ) ) {
			throw new InvalidArgumentException( sprintf( 'Coordina service "%s" is not registered.', $id ) );
		}

		$this->instances[ $id ] = ( $this->factories[ $id ] )( $this );

		return $this->instances[ $id ];
	}
}