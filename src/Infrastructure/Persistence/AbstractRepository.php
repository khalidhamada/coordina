<?php
/**
 * Base repository helpers.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Persistence;

use Coordina\Infrastructure\Access\AccessPolicy;
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
	 * @var AccessPolicy
	 */
	protected $access;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->access = new AccessPolicy();
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

		$title = $this->wpdb->get_var( $this->wpdb->prepare( 'SELECT title FROM ' . $this->table( 'projects' ) . ' WHERE id = %d', $project_id ) );

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
		$table_map = array(
			'project'  => 'projects',
			'task'     => 'tasks',
			'request'  => 'requests',
			'risk'     => 'risks_issues',
			'issue'    => 'risks_issues',
			'milestone'=> 'milestones',
			'approval' => 'approvals',
		);

		if ( ! isset( $table_map[ $object_type ] ) || $object_id <= 0 ) {
			return '';
		}

		if ( 'approval' === $object_type ) {
			return sprintf( __( 'Approval #%d', 'coordina' ), $object_id );
		}

		$table = $this->table( $table_map[ $object_type ] );
		$title = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT title FROM {$table} WHERE id = %d", $object_id ) );

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
		if ( $object_id <= 0 ) {
			return 0;
		}

		if ( 'project' === $object_type ) {
			return $object_id;
		}

		if ( 'task' === $object_type ) {
			return (int) $this->wpdb->get_var( $this->wpdb->prepare( 'SELECT project_id FROM ' . $this->table( 'tasks' ) . ' WHERE id = %d', $object_id ) );
		}

		if ( in_array( $object_type, array( 'risk', 'issue' ), true ) ) {
			return (int) $this->wpdb->get_var( $this->wpdb->prepare( 'SELECT project_id FROM ' . $this->table( 'risks_issues' ) . ' WHERE id = %d', $object_id ) );
		}

		if ( 'milestone' === $object_type ) {
			return (int) $this->wpdb->get_var( $this->wpdb->prepare( 'SELECT project_id FROM ' . $this->table( 'milestones' ) . ' WHERE id = %d', $object_id ) );
		}

		if ( 'approval' === $object_type ) {
			$row = $this->wpdb->get_row( $this->wpdb->prepare( 'SELECT object_type, object_id FROM ' . $this->table( 'approvals' ) . ' WHERE id = %d', $object_id ) );

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
		$table_map = array(
			'project'  => 'projects',
			'task'     => 'tasks',
			'request'  => 'requests',
			'risk'     => 'risks_issues',
			'issue'    => 'risks_issues',
			'milestone'=> 'milestones',
			'approval' => 'approvals',
		);

		if ( ! isset( $table_map[ $object_type ] ) || $object_id <= 0 ) {
			return false;
		}

		$table = $this->table( $table_map[ $object_type ] );
		$count = (int) $this->wpdb->get_var( $this->wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE id = %d", $object_id ) );

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
}
