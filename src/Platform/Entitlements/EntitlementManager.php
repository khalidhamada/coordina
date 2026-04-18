<?php
/**
 * Centralized entitlement and feature-state evaluation.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Entitlements;

use Coordina\Platform\Contracts\EntitlementManagerInterface;
use Coordina\Platform\Contracts\EntitlementProviderInterface;

final class EntitlementManager implements EntitlementManagerInterface {
	/**
	 * @var LocalLicenseStore
	 */
	private $store;

	/**
	 * @var array<int, EntitlementProviderInterface>
	 */
	private $providers;

	/**
	 * @var array<string,mixed>|null
	 */
	private $resolved_state;

	/**
	 * @param array<int, EntitlementProviderInterface> $providers Remote providers.
	 */
	public function __construct( LocalLicenseStore $store, array $providers = array() ) {
		$this->store     = $store;
		$this->providers = array_values(
			array_filter(
				$providers,
				static function ( $provider ): bool {
					return $provider instanceof EntitlementProviderInterface;
				}
			)
		);
	}

	public function is_enabled( string $feature ): bool {
		$state = $this->feature_state( $feature );
		return ! empty( $state['enabled'] );
	}

	/**
	 * @return array<string,mixed>
	 */
	public function feature_state( string $feature ): array {
		$states = $this->all_feature_states();
		$key    = sanitize_key( $feature );

		if ( isset( $states[ $key ] ) ) {
			return $states[ $key ];
		}

		return $this->normalize_feature_state( $key, array() );
	}

	/**
	 * @return array<string, array<string,mixed>>
	 */
	public function all_feature_states(): array {
		$state    = $this->resolved_state();
		$defaults = $this->default_feature_states();
		$local    = is_array( $state['features'] ?? null ) ? $state['features'] : array();
		$features = array();

		foreach ( array_unique( array_merge( array_keys( $defaults ), array_keys( $local ) ) ) as $feature_key ) {
			$default_state          = $defaults[ $feature_key ] ?? array();
			$local_state            = is_array( $local[ $feature_key ] ?? null ) ? $local[ $feature_key ] : array();
			$features[ $feature_key ] = $this->normalize_feature_state(
				$feature_key,
				array_merge( $default_state, $local_state )
			);
		}

		return $features;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function license_state(): array {
		$state   = $this->resolved_state();
		$license = is_array( $state['license'] ?? null ) ? $state['license'] : array();

		return array(
			'status'    => sanitize_key( (string) ( $license['status'] ?? 'unlicensed' ) ),
			'plan'      => sanitize_text_field( (string) ( $license['plan'] ?? '' ) ),
			'expiresAt' => sanitize_text_field( (string) ( $license['expires_at'] ?? '' ) ),
			'checkedAt' => sanitize_text_field( (string) ( $license['checked_at'] ?? '' ) ),
			'hasKey'    => ! empty( $license['key'] ),
			'source'    => sanitize_key( (string) ( $license['source'] ?? 'local' ) ),
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private function resolved_state(): array {
		if ( null !== $this->resolved_state ) {
			return $this->resolved_state;
		}

		$state = $this->store->get();

		foreach ( $this->providers as $provider ) {
			$resolved = $provider->resolve( $state );

			if ( ! is_array( $resolved ) ) {
				continue;
			}

			$state = $this->merge_state( $state, $resolved );
		}

		$this->resolved_state = $state;

		return $this->resolved_state;
	}

	/**
	 * @param array<string,mixed> $base Base state.
	 * @param array<string,mixed> $resolved Provider state.
	 * @return array<string,mixed>
	 */
	private function merge_state( array $base, array $resolved ): array {
		$license = is_array( $base['license'] ?? null ) ? $base['license'] : array();
		$license = array_merge( $license, is_array( $resolved['license'] ?? null ) ? $resolved['license'] : array() );

		$features = is_array( $base['features'] ?? null ) ? $base['features'] : array();
		$incoming = is_array( $resolved['features'] ?? null ) ? $resolved['features'] : array();

		foreach ( $incoming as $feature_key => $feature_state ) {
			if ( ! is_string( $feature_key ) || ! is_array( $feature_state ) ) {
				continue;
			}

			$normalized_key              = sanitize_key( $feature_key );
			$current_feature             = is_array( $features[ $normalized_key ] ?? null ) ? $features[ $normalized_key ] : array();
			$features[ $normalized_key ] = array_merge( $current_feature, $feature_state );
		}

		return array(
			'license'  => $license,
			'features' => $features,
		);
	}

	/**
	 * @return array<string, array<string,mixed>>
	 */
	private function default_feature_states(): array {
		return array(
			'project_wizard' => array(
				'label'            => __( 'Project Wizard', 'coordina' ),
				'enabled'          => false,
				'status'           => 'unavailable',
				'requires_license' => true,
				'source'           => 'core',
				'reason'           => __( 'Install and activate the Project Wizard add-on to enable this workflow.', 'coordina' ),
				'route'            => array(),
			),
		);
	}

	/**
	 * @param string              $feature_key Feature key.
	 * @param array<string,mixed> $state Feature state.
	 * @return array<string,mixed>
	 */
	private function normalize_feature_state( string $feature_key, array $state ): array {
		$route = is_array( $state['route'] ?? null ) ? $state['route'] : array();

		return array(
			'key'             => sanitize_key( $feature_key ),
			'label'           => sanitize_text_field( (string) ( $state['label'] ?? $this->fallback_feature_label( $feature_key ) ) ),
			'enabled'         => ! empty( $state['enabled'] ),
			'status'          => sanitize_key( (string) ( $state['status'] ?? 'unavailable' ) ),
			'requiresLicense' => ! empty( $state['requires_license'] ),
			'source'          => sanitize_key( (string) ( $state['source'] ?? 'local' ) ),
			'reason'          => sanitize_text_field( (string) ( $state['reason'] ?? '' ) ),
			'route'           => $this->normalize_route( $route ),
		);
	}

	/**
	 * @param string $feature_key Feature key.
	 */
	private function fallback_feature_label( string $feature_key ): string {
		return ucwords( str_replace( '_', ' ', sanitize_key( $feature_key ) ) );
	}

	/**
	 * @param array<string,mixed> $route Raw route.
	 * @return array<string,mixed>
	 */
	private function normalize_route( array $route ): array {
		$normalized = array();

		foreach ( array( 'page', 'project_tab' ) as $key ) {
			if ( isset( $route[ $key ] ) && '' !== (string) $route[ $key ] ) {
				$normalized[ $key ] = sanitize_key( (string) $route[ $key ] );
			}
		}

		foreach ( array( 'project_id', 'task_id', 'milestone_id', 'risk_issue_id' ) as $key ) {
			if ( isset( $route[ $key ] ) && absint( $route[ $key ] ) > 0 ) {
				$normalized[ $key ] = absint( $route[ $key ] );
			}
		}

		return $normalized;
	}
}
