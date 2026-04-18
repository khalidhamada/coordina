<?php
/**
 * Registry for schema definitions and upgrade callbacks.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Registry;

final class MigrationRegistry {
	/**
	 * Registered table definitions.
	 *
	 * @var array<int, string>
	 */
	private $tables = array();

	/**
	 * Registered upgrade callbacks.
	 *
	 * @var array<string, callable>
	 */
	private $migrations = array();

	/**
	 * Add one dbDelta-compatible table definition.
	 *
	 * @param string $sql CREATE TABLE SQL.
	 */
	public function add_table( string $sql ): void {
		$this->tables[] = $sql;
	}

	/**
	 * Add one runtime migration callback.
	 *
	 * @param string   $key Migration key.
	 * @param callable $migration Migration callback.
	 */
	public function add_migration( string $key, callable $migration ): void {
		$this->migrations[ $key ] = $migration;
	}

	/**
	 * Get table definitions.
	 *
	 * @return array<int, string>
	 */
	public function tables(): array {
		return $this->tables;
	}

	/**
	 * Get runtime migrations.
	 *
	 * @return array<string, callable>
	 */
	public function migrations(): array {
		return $this->migrations;
	}
}
