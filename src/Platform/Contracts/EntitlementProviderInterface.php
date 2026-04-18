<?php
/**
 * Public contract for remote entitlement providers.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Contracts;

interface EntitlementProviderInterface {
	/**
	 * Resolve current license and feature state additions.
	 *
	 * @param array<string,mixed> $local_state Local stored state.
	 * @return array<string,mixed>
	 */
	public function resolve( array $local_state ): array;
}
