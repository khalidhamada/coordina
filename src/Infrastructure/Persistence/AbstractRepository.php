<?php
/**
 * Base repository helpers.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Persistence;

use Coordina\Infrastructure\Access\AccessPolicy;
use Coordina\Platform\Bootstrap\CoreRegistries;
use Coordina\Platform\Contracts\AccessPolicyInterface;
use Coordina\Platform\Contracts\ContextResolverInterface;
use wpdb;

abstract class AbstractRepository {
	/**
	 * WordPress database object.
	 *
	 * @var wpdb
	 */
	protected $wpdb;

	/**
	 * Shared access policy.
	 *
	 * @var AccessPolicyInterface
	 */
	protected $access;

	/**
	 * Shared context registry.
	 *
	 * @var ContextResolverInterface
	 */
	protected $context_types;

	/**
	 * Constructor.
	 *
	 * @param AccessPolicyInterface|null  $access Shared access policy.
	 * @param ContextResolverInterface|null $context_types Shared context registry.
	 */
	public function __construct( ?AccessPolicyInterface $access = null, ?ContextResolverInterface $context_types = null ) {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->access = new AccessPolicy();
		$this->context_types = $context_types ?: CoreRegistries::context_types();

		if ( $access instanceof AccessPolicyInterface ) {
			$this->access = $access;
		}
	}

	/**
	 * Get full table name.
	 *
	 * @param string $suffix Table suffix.
	 * @return string
	 */
	protected function table( string $suffix ): string {
		return $this->wpdb->prefix . 'coordina_' . $suffix;
	}

	/**
	 * Normalize datetime string for storage.
	 *
	 * @param string|null $value Raw value.
	 * @return string|null
	 */
	protected function normalize_datetime( ?string $value ): ?string {
		if ( empty( $value ) ) {
			return null;
		}

		$timestamp = strtotime( $value );

		if ( false === $timestamp ) {
			return null;
		}

		return gmdate( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * Current GMT datetime.
	 *
	 * @return string
	 */
	protected function now(): string {
		return current_time( 'mysql', true );
	}

	/**
	 * Cast table row to array.
	 *
	 * @param object|null $row Database row.
	 * @return array<string, mixed>
	 */
	protected function row_to_array( $row ): array {
		return $row ? get_object_vars( $row ) : array();
	}

	/**
	 * Resolve a user display label.
	 *
	 * @param int $user_id User id.
	 * @return string
	 */
	protected function get_user_label( int $user_id ): string {
		if ( $user_id <= 0 ) {
			return '';
		}

		return get_userdata( $user_id )->display_name ?? '';
	}

	/**
	 * Resolve a project label.
	 *
	 * @param int    $project_id Project id.
	 * @param string $fallback Fallback label.
	 * @return string
	 */
	protected function get_project_label( int $project_id, string $fallback = '' ): string {
		if ( $project_id <= 0 ) {
			return __( 'Standalone', 'coordina' );
		}

		$title = $this->prepared_var( 'SELECT title FROM ' . $this->table( 'projects' ) . ' WHERE id = %d', array( $project_id ) );

		if ( $title ) {
			return (string) $title;
		}

		return '' !== $fallback ? $fallback : __( 'Project', 'coordina' );
	}

	/**
	 * Resolve a context label from its parent object.
	 *
	 * @param string $object_type Parent object type.
	 * @param int    $object_id Parent object id.
	 * @return string
	 */
	protected function resolve_context_label( string $object_type, int $object_id ): string {
		$definition = $this->context_types->definition( $object_type );

		if ( $object_id <= 0 || empty( $definition ) ) {
			return '';
		}

		if ( isset( $definition['label_callback'] ) && is_callable( $definition['label_callback'] ) ) {
			return (string) $definition['label_callback']( $object_id, $this );
		}

		$table_suffix = (string) ( $definition['table'] ?? '' );
		$title_column = (string) ( $definition['title_column'] ?? 'title' );
		$type_column  = (string) ( $definition['type_column'] ?? '' );
		$type_value   = sanitize_key( (string) ( $definition['type_value'] ?? '' ) );

		if ( '' === $table_suffix || '' === $title_column ) {
			return '';
		}

		$table = $this->table( $table_suffix );
		$sql   = "SELECT {$title_column} FROM {$table} WHERE id = %d";
		$args  = array( $object_id );

		if ( '' !== $type_column && '' !== $type_value ) {
			$sql   .= " AND {$type_column} = %s";
			$args[] = $type_value;
		}

		$title = $this->prepared_var( $sql, $args );

		return $title ? (string) $title : '';
	}

	/**
	 * Resolve project id for a parent object.
	 *
	 * @param string $object_type Parent object type.
	 * @param int    $object_id Parent object id.
	 * @return int
	 */
	protected function resolve_project_id_for_context( string $object_type, int $object_id ): int {
		$definition = $this->context_types->definition( $object_type );

		if ( $object_id <= 0 ) {
			return 0;
		}

		if ( 'self' === ( $definition['project_lookup'] ?? '' ) ) {
			return $object_id;
		}

		if ( isset( $definition['project_lookup_callback'] ) && is_callable( $definition['project_lookup_callback'] ) ) {
			return (int) $definition['project_lookup_callback']( $object_id, $this );
		}

		$table_suffix       = (string) ( $definition['table'] ?? '' );
		$project_id_column  = (string) ( $definition['project_id_column'] ?? '' );
		$type_column        = (string) ( $definition['type_column'] ?? '' );
		$type_value         = sanitize_key( (string) ( $definition['type_value'] ?? '' ) );

		if ( '' !== $table_suffix && '' !== $project_id_column ) {
			$sql  = 'SELECT ' . $project_id_column . ' FROM ' . $this->table( $table_suffix ) . ' WHERE id = %d';
			$args = array( $object_id );

			if ( '' !== $type_column && '' !== $type_value ) {
				$sql   .= ' AND ' . $type_column . ' = %s';
				$args[] = $type_value;
			}

			return (int) $this->prepared_var( $sql, $args );
		}

		if ( 'approval' === $object_type ) {
			$row = $this->prepared_row( 'SELECT object_type, object_id FROM ' . $this->table( 'approvals' ) . ' WHERE id = %d', array( $object_id ) );

			if ( $row ) {
				return $this->resolve_project_id_for_context( sanitize_key( (string) $row->object_type ), (int) $row->object_id );
			}
		}

		return 0;
	}

	/**
	 * Validate that a parent context exists.
	 *
	 * @param string $object_type Parent object type.
	 * @param int    $object_id Parent object id.
	 * @return bool
	 */
	protected function context_exists( string $object_type, int $object_id ): bool {
		$definition   = $this->context_types->definition( $object_type );
		$table_suffix = (string) ( $definition['table'] ?? '' );
		$type_column  = (string) ( $definition['type_column'] ?? '' );
		$type_value   = sanitize_key( (string) ( $definition['type_value'] ?? '' ) );

		if ( '' === $table_suffix || $object_id <= 0 ) {
			return false;
		}

		$table = $this->table( $table_suffix );
		$sql   = "SELECT COUNT(*) FROM {$table} WHERE id = %d";
		$args  = array( $object_id );

		if ( '' !== $type_column && '' !== $type_value ) {
			$sql   .= " AND {$type_column} = %s";
			$args[] = $type_value;
		}

		$count = (int) $this->prepared_var( $sql, $args );

		return $count > 0;
	}

	/**
	 * Write a lightweight activity record.
	 *
	 * @param string $object_type Object type.
	 * @param int    $object_id Object id.
	 * @param string $event_type Event type.
	 * @param string $message Activity message.
	 * @return void
	 */
	protected function log_activity( string $object_type, int $object_id, string $event_type, string $message ): void {
		$this->wpdb->insert(
			$this->table( 'activity_log' ),
			array(
				'object_type'   => sanitize_key( $object_type ),
				'object_id'     => max( 0, $object_id ),
				'event_type'    => sanitize_key( $event_type ),
				'actor_user_id' => get_current_user_id(),
				'message'       => sanitize_text_field( $message ),
				'created_at'    => $this->now(),
			)
		);
	}

	/**
	 * Delete files, discussions, approvals, checklist items, and activity entries for a context.
	 *
	 * @param string $object_type Context object type.
	 * @param int    $object_id Context object id.
	 * @return void
	 */
	protected function delete_context_relations( string $object_type, int $object_id ): void {
		$object_type = sanitize_key( $object_type );
		$object_id   = max( 0, $object_id );

		if ( '' === $object_type || $object_id <= 0 ) {
			return;
		}

		$match = array(
			'object_type' => $object_type,
			'object_id'   => $object_id,
		);

		$this->wpdb->delete( $this->table( 'files' ), $match );
		$this->wpdb->delete( $this->table( 'discussions' ), $match );
		$this->wpdb->delete( $this->table( 'approvals' ), $match );
		$this->wpdb->delete( $this->table( 'checklist_items' ), $match );
		$this->wpdb->delete( $this->table( 'checklists' ), $match );
		$this->wpdb->delete( $this->table( 'activity_log' ), $match );
	}

	/**
	 * Determine whether current user has full project-level access.
	 *
	 * @return bool
	 */
	protected function has_full_project_access(): bool {
		return $this->access->has_full_project_access();
	}

	/**
	 * Prepare SQL with trusted internal fragments and placeholder values.
	 *
	 * @param string $sql SQL statement.
	 * @param array<int, mixed> $params Placeholder values.
	 * @return string
	 */
	protected function prepare_statement( string $sql, array $params = array() ): string {
		if ( empty( $params ) ) {
			return $sql;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Internal query fragments are assembled from trusted repository helpers and sanitized values before placeholder substitution.
		return $this->wpdb->prepare( $sql, $params );
	}

	/**
	 * Execute a prepared scalar query.
	 *
	 * @param string $sql SQL statement.
	 * @param array<int, mixed> $params Placeholder values.
	 * @return mixed
	 */
	protected function prepared_var( string $sql, array $params = array() ) {
		$prepared_sql = $this->prepare_statement( $sql, $params );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom-table query prepared through repository helper.
		return $this->wpdb->get_var( $prepared_sql );
	}

	/**
	 * Execute a prepared row query.
	 *
	 * @param string $sql SQL statement.
	 * @param array<int, mixed> $params Placeholder values.
	 * @param string|int $output Output format.
	 * @return mixed
	 */
	protected function prepared_row( string $sql, array $params = array(), $output = \OBJECT ) {
		$prepared_sql = $this->prepare_statement( $sql, $params );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom-table query prepared through repository helper.
		return $this->wpdb->get_row( $prepared_sql, $output );
	}

	/**
	 * Execute a prepared result-set query.
	 *
	 * @param string $sql SQL statement.
	 * @param array<int, mixed> $params Placeholder values.
	 * @param string|int $output Output format.
	 * @return array<int, mixed>
	 */
	protected function prepared_results( string $sql, array $params = array(), $output = \OBJECT ): array {
		$prepared_sql = $this->prepare_statement( $sql, $params );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom-table query prepared through repository helper.
		$results = $this->wpdb->get_results( $prepared_sql, $output );

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Execute a prepared column query.
	 *
	 * @param string $sql SQL statement.
	 * @param array<int, mixed> $params Placeholder values.
	 * @return array<int, mixed>
	 */
	protected function prepared_col( string $sql, array $params = array() ): array {
		$prepared_sql = $this->prepare_statement( $sql, $params );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom-table query prepared through repository helper.
		$results = $this->wpdb->get_col( $prepared_sql );

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Execute a prepared write query.
	 *
	 * @param string $sql SQL statement.
	 * @param array<int, mixed> $params Placeholder values.
	 * @return int|false
	 */
	protected function prepared_query( string $sql, array $params = array() ) {
		$prepared_sql = $this->prepare_statement( $sql, $params );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom-table query prepared through repository helper.
		return $this->wpdb->query( $prepared_sql );
	}
}
