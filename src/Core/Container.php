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
	 * Determine whether a service is registered or already resolved.
	 *
	 * @param string $id Service id.
	 */
	public function has( string $id ): bool {
		return isset( $this->factories[ $id ] ) || isset( $this->instances[ $id ] );
	}

	/**
	 * Decorate an existing service definition.
	 *
	 * @param string   $id       Service id.
	 * @param callable $extender Extender callback receiving the resolved service and container.
	 */
	public function extend( string $id, callable $extender ): void {
		if ( ! isset( $this->factories[ $id ] ) ) {
			throw new InvalidArgumentException( sprintf( 'Coordina service "%s" is not registered.', $id ) );
		}

		$factory = $this->factories[ $id ];

		$this->factories[ $id ] = static function ( Container $container ) use ( $factory, $extender ) {
			$service = $factory( $container );
			return $extender( $service, $container );
		};

		unset( $this->instances[ $id ] );
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
