<?php
/**
 * Coordina schema management.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Database;

use Coordina\Platform\Bootstrap\CoreRegistries;
use Coordina\Platform\Registry\MigrationRegistry;

final class SchemaManager {
	/**
	 * Schema version option.
	 */
	private const OPTION_KEY = 'coordina_db_version';

	/**
	 * Current schema version.
	 */
	private const VERSION = '0.2.8';

	/**
	 * Migration registry.
	 *
	 * @var MigrationRegistry
	 */
	private $registry;

	/**
	 * Constructor.
	 *
	 * @param MigrationRegistry|null $registry Migration registry.
	 */
	public function __construct( ?MigrationRegistry $registry = null ) {
		$this->registry = $registry ?: CoreRegistries::migrations();
	}

	/**
	 * Install or upgrade schema.
	 */
	public function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( $this->registry->tables() as $sql ) {
			dbDelta( $sql );
		}

		foreach ( $this->registry->migrations() as $migration ) {
			$migration( $wpdb->prefix );
		}

		update_option( self::OPTION_KEY, self::VERSION, false );
	}

	/**
	 * Determine whether the installed schema is current.
	 *
	 * @param string $installed_version Installed schema version.
	 * @return bool
	 */
	public function is_current( string $installed_version ): bool {
		return version_compare( $installed_version, self::VERSION, '>=' );
	}

}
