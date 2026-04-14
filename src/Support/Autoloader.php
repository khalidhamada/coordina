<?php
/**
 * Lightweight fallback autoloader.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Support;

final class Autoloader {
	/**
	 * Base path.
	 *
	 * @var string
	 */
	private static $base_path = '';

	/**
	 * Register autoloader.
	 *
	 * @param string $base_path Base source path.
	 */
	public static function register( string $base_path ): void {
		self::$base_path = rtrim( $base_path, '/\\' ) . DIRECTORY_SEPARATOR;
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload Coordina classes.
	 *
	 * @param string $class Class name.
	 */
	private static function autoload( string $class ): void {
		$prefix = 'Coordina\\';

		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$path     = self::$base_path . str_replace( '\\', DIRECTORY_SEPARATOR, $relative ) . '.php';

		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
}