<?php
/**
 * Public contract for Coordina entitlement lookups.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Contracts;

interface EntitlementManagerInterface {
	public function is_enabled( string $feature ): bool;

	/**
	 * @return array<string,mixed>
	 */
	public function feature_state( string $feature ): array;

	/**
	 * @return array<string, array<string,mixed>>
	 */
	public function all_feature_states(): array;

	/**
	 * @return array<string,mixed>
	 */
	public function license_state(): array;
}
