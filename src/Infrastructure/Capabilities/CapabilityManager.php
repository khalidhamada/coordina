<?php
/**
 * Coordina roles and capabilities.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Capabilities;

final class CapabilityManager {
	/**
	 * Capability map by role.
	 *
	 * @var array<string, array<int, string>>
	 */
	private $role_capabilities = array(
		'administrator'             => array(
			'read',
			'upload_files',
			'coordina_access',
			'coordina_manage_settings',
			'coordina_view_reports',
			'coordina_manage_projects',
			'coordina_manage_tasks',
			'coordina_manage_requests',
			'coordina_manage_approvals',
			'coordina_view_dashboard',
			'coordina_access_portal',
		),
		'coordina_project_manager'  => array(
			'read',
			'upload_files',
			'coordina_access',
			'coordina_view_dashboard',
			'coordina_manage_projects',
			'coordina_manage_tasks',
			'coordina_manage_requests',
			'coordina_manage_approvals',
			'coordina_view_reports',
			'coordina_access_portal',
		),
		'coordina_team_member'      => array(
			'read',
			'upload_files',
			'coordina_access',
			'coordina_access_portal',
		),
		'coordina_executive_viewer' => array(
			'read',
			'upload_files',
			'coordina_access',
			'coordina_view_dashboard',
			'coordina_view_reports',
			'coordina_access_portal',
		),
		'coordina_portal_user'      => array(
			'read',
			'upload_files',
			'coordina_access_portal',
		),
	);

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
		add_role(
			'coordina_project_manager',
			__( 'Coordina Project Manager', 'coordina' ),
			$this->get_role_caps( 'coordina_project_manager' )
		);

		add_role(
			'coordina_team_member',
			__( 'Coordina Team Member', 'coordina' ),
			$this->get_role_caps( 'coordina_team_member' )
		);

		add_role(
			'coordina_executive_viewer',
			__( 'Coordina Executive Viewer', 'coordina' ),
			$this->get_role_caps( 'coordina_executive_viewer' )
		);

		add_role(
			'coordina_portal_user',
			__( 'Coordina Portal User', 'coordina' ),
			$this->get_role_caps( 'coordina_portal_user' )
		);
	}

	/**
	 * Ensure managed roles keep the expected cap set.
	 */
	public function sync_role_caps(): void {
		$managed_capabilities = $this->get_managed_capabilities();

		foreach ( $this->role_capabilities as $role_name => $expected_caps ) {
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

		foreach ( $this->role_capabilities[ $role ] as $capability ) {
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
