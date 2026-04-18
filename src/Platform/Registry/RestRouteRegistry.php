<?php
/**
 * Registry for REST route contributors.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Registry;

use Coordina\Rest\RestRegistrar;

final class RestRouteRegistry {
	/**
	 * Registered route contributors.
	 *
	 * @var array<int, callable>
	 */
	private $contributors = array();

	/**
	 * Add a route contributor callback.
	 *
	 * @param callable $contributor Callback receiving the registrar instance.
	 */
	public function add( callable $contributor ): void {
		$this->contributors[] = $contributor;
	}

	/**
	 * Register all contributed routes.
	 *
	 * @param RestRegistrar $registrar Registrar instance.
	 */
	public function register( RestRegistrar $registrar ): void {
		foreach ( $this->contributors as $contributor ) {
			$contributor( $registrar );
		}
	}
}
