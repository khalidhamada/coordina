<?php
/**
 * Coordina roles and capabilities.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Coordina\Platform\Bootstrap\CoreRegistries;
use Coordina\Platform\Registry\CapabilityRegistry;

final class CapabilityManager {
	/**
	 * Capability registry.
	 *
	 * @var CapabilityRegistry
	 */
	private $registry;

	/**
	 * Constructor.
	 *
	 * @param CapabilityRegistry|null $registry Capability registry.
	 */
	public function __construct( ?CapabilityRegistry $registry = null ) {
		$this->registry = $registry ?: CoreRegistries::capabilities();
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'add_roles' ) );
		add_action( 'admin_init', array( $this, 'sync_role_caps' ) );
	}

	/**
	 * Run during activation.
	 */
	public function activate(): void {
		$this->add_roles();
		$this->sync_role_caps();
	}

	/**
	 * Add custom roles.
	 */
	public function add_roles(): void {
		$labels = $this->registry->labels();

		foreach ( $this->registry->role_capabilities() as $role_name => $capabilities ) {
			if ( 'administrator' === $role_name ) {
				continue;
			}

			add_role(
				$role_name,
				$labels[ $role_name ] ?? $role_name,
				$this->get_role_caps( $role_name )
			);
		}
	}

	/**
	 * Ensure managed roles keep the expected cap set.
	 */
	public function sync_role_caps(): void {
		$managed_capabilities = $this->get_managed_capabilities();

		foreach ( $this->registry->role_capabilities() as $role_name => $expected_caps ) {
			$role = get_role( $role_name );

			if ( ! $role instanceof \WP_Role ) {
				continue;
			}

			foreach ( $managed_capabilities as $capability ) {
				if ( in_array( $capability, $expected_caps, true ) ) {
					$role->add_cap( $capability );
					continue;
				}

				$role->remove_cap( $capability );
			}
		}
	}

	/**
	 * Build role capability map in WP format.
	 *
	 * @param string $role Role slug.
	 * @return array<string, bool>
	 */
	private function get_role_caps( string $role ): array {
		$caps = array();

		foreach ( $this->registry->role_capabilities()[ $role ] as $capability ) {
			$caps[ $capability ] = true;
		}

		return $caps;
	}

	/**
	 * Get the full managed capability pool.
	 *
	 * @return array<int, string>
	 */
	private function get_managed_capabilities(): array {
		return $this->registry->managed_capabilities();
	}
}
