<?php
/**
 * Registry for role and capability definitions.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Registry;

final class CapabilityRegistry {
	/**
	 * Registered role labels.
	 *
	 * @var array<string, string>
	 */
	private $labels = array();

	/**
	 * Registered role capability maps.
	 *
	 * @var array<string, array<int, string>>
	 */
	private $role_capabilities = array();

	/**
	 * Register one role definition.
	 *
	 * @param string             $role Role slug.
	 * @param string             $label Role label.
	 * @param array<int, string> $capabilities Capabilities.
	 */
	public function add_role( string $role, string $label, array $capabilities ): void {
		$this->labels[ $role ]            = $label;
		$this->role_capabilities[ $role ] = array_values( $capabilities );
	}

	/**
	 * Get role labels.
	 *
	 * @return array<string, string>
	 */
	public function labels(): array {
		return $this->labels;
	}

	/**
	 * Get the role capability map.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function role_capabilities(): array {
		return $this->role_capabilities;
	}

	/**
	 * Get every managed capability across all roles.
	 *
	 * @return array<int, string>
	 */
	public function managed_capabilities(): array {
		$capabilities = array();

		foreach ( $this->role_capabilities as $role_caps ) {
			foreach ( $role_caps as $capability ) {
				if ( ! in_array( $capability, $capabilities, true ) ) {
					$capabilities[] = $capability;
				}
			}
		}

		return $capabilities;
	}
}
