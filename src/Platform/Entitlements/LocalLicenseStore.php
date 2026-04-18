<?php
/**
 * Stores local entitlement and license state in WordPress options.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Entitlements;

final class LocalLicenseStore {
	private const OPTION_KEY = 'coordina_entitlements';

	/**
	 * @return array<string,mixed>
	 */
	public function get(): array {
		$value = get_option( self::OPTION_KEY, array() );
		return $this->normalize_state( is_array( $value ) ? $value : array() );
	}

	/**
	 * @param array<string,mixed> $state Raw state.
	 * @return array<string,mixed>
	 */
	public function update( array $state ): array {
		$normalized = $this->normalize_state( $state );
		update_option( self::OPTION_KEY, $normalized, false );
		return $normalized;
	}

	/**
	 * @param array<string,mixed> $state Raw state.
	 * @return array<string,mixed>
	 */
	private function normalize_state( array $state ): array {
		$license  = is_array( $state['license'] ?? null ) ? $state['license'] : array();
		$features = is_array( $state['features'] ?? null ) ? $state['features'] : array();

		$normalized_features = array();

		foreach ( $features as $feature_key => $feature_state ) {
			if ( ! is_string( $feature_key ) || '' === $feature_key || ! is_array( $feature_state ) ) {
				continue;
			}

			$normalized_features[ sanitize_key( $feature_key ) ] = $this->normalize_feature_state( $feature_state );
		}

		return array(
			'license'  => array(
				'key'        => isset( $license['key'] ) ? sanitize_text_field( (string) $license['key'] ) : '',
				'status'     => isset( $license['status'] ) ? sanitize_key( (string) $license['status'] ) : 'unlicensed',
				'plan'       => isset( $license['plan'] ) ? sanitize_text_field( (string) $license['plan'] ) : '',
				'expires_at' => isset( $license['expires_at'] ) ? sanitize_text_field( (string) $license['expires_at'] ) : '',
				'checked_at' => isset( $license['checked_at'] ) ? sanitize_text_field( (string) $license['checked_at'] ) : '',
				'source'     => isset( $license['source'] ) ? sanitize_key( (string) $license['source'] ) : 'local',
			),
			'features' => $normalized_features,
		);
	}

	/**
	 * @param array<string,mixed> $feature_state Raw feature state.
	 * @return array<string,mixed>
	 */
	private function normalize_feature_state( array $feature_state ): array {
		$route = is_array( $feature_state['route'] ?? null ) ? $feature_state['route'] : array();

		return array(
			'enabled'          => ! empty( $feature_state['enabled'] ),
			'status'           => isset( $feature_state['status'] ) ? sanitize_key( (string) $feature_state['status'] ) : 'unavailable',
			'label'            => isset( $feature_state['label'] ) ? sanitize_text_field( (string) $feature_state['label'] ) : '',
			'reason'           => isset( $feature_state['reason'] ) ? sanitize_text_field( (string) $feature_state['reason'] ) : '',
			'requires_license' => ! empty( $feature_state['requires_license'] ),
			'source'           => isset( $feature_state['source'] ) ? sanitize_key( (string) $feature_state['source'] ) : 'local',
			'route'            => $this->normalize_route( $route ),
		);
	}

	/**
	 * @param array<string,mixed> $route Raw route payload.
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
