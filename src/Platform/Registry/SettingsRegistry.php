<?php
/**
 * Registry for settings defaults and choice lists.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Registry;

final class SettingsRegistry {
	/**
	 * Registered default settings.
	 *
	 * @var array<string, mixed>
	 */
	private $defaults = array();

	/**
	 * Registered choice lists keyed by dot path.
	 *
	 * @var array<string, array<int, string>>
	 */
	private $choices = array();

	/**
	 * Merge a defaults payload into the registry.
	 *
	 * @param array<string, mixed> $defaults Defaults payload.
	 */
	public function add_defaults( array $defaults ): void {
		$this->defaults = $this->merge_assoc( $this->defaults, $defaults );
	}

	/**
	 * Register one choice list.
	 *
	 * @param string              $path Dot-path key.
	 * @param array<int, string>  $values Allowed values.
	 */
	public function add_choices( string $path, array $values ): void {
		$this->choices[ $path ] = array_values( $values );
	}

	/**
	 * Get the merged defaults payload.
	 *
	 * @return array<string, mixed>
	 */
	public function defaults(): array {
		return $this->defaults;
	}

	/**
	 * Get an allowed-values list for a settings path.
	 *
	 * @param string             $path Dot-path key.
	 * @param array<int, string> $fallback Fallback values.
	 * @return array<int, string>
	 */
	public function choices( string $path, array $fallback = array() ): array {
		return $this->choices[ $path ] ?? $fallback;
	}

	/**
	 * Get all registered choice lists.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function all_choices(): array {
		return $this->choices;
	}

	/**
	 * Merge associative arrays while replacing list values.
	 *
	 * @param array<string, mixed> $base Base config.
	 * @param array<string, mixed> $extra Extra config.
	 * @return array<string, mixed>
	 */
	private function merge_assoc( array $base, array $extra ): array {
		foreach ( $extra as $key => $value ) {
			if ( isset( $base[ $key ] ) && is_array( $base[ $key ] ) && is_array( $value ) && ! $this->is_list_array( $base[ $key ] ) ) {
				$base[ $key ] = $this->merge_assoc( $base[ $key ], $value );
				continue;
			}

			$base[ $key ] = $value;
		}

		return $base;
	}

	/**
	 * Determine whether an array is a list.
	 *
	 * @param array<mixed> $value Array value.
	 */
	private function is_list_array( array $value ): bool {
		if ( empty( $value ) ) {
			return true;
		}

		return array_keys( $value ) === range( 0, count( $value ) - 1 );
	}
}
