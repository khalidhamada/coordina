<?php
/**
 * Discovers external service providers.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform;

final class ExtensionManager {
	/**
	 * Discover external provider class names.
	 *
	 * @return array<int, string>
	 */
	public function discover_provider_classes(): array {
		/**
		 * Filters extension service providers registered by add-ons.
		 *
		 * Providers must be class names implementing the platform
		 * service provider contract.
		 *
		 * @param array<int, string> $providers Provider class names.
		 */
		$providers = apply_filters( 'coordina/platform/providers', array() );

		if ( ! is_array( $providers ) ) {
			return array();
		}

		return array_values(
			array_filter(
				$providers,
				static function ( $provider ): bool {
					return is_string( $provider ) && '' !== $provider;
				}
			)
		);
	}
}
