<?php
/**
 * Coordina schema management.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Database;

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
	 * Install or upgrade schema.
	 */
	public function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$tables          = $this->get_table_definitions( $wpdb->prefix, $charset_collate );

		foreach ( $tables as $sql ) {
			dbDelta( $sql );
		}

		$this->migrate_task_checklists( $wpdb->prefix );
		$this->migrate_grouped_checklists( $wpdb->prefix );
		$this->migrate_project_sponsor_field( $wpdb->prefix );

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

	/**
	 * Get dbDelta-compatible CREATE TABLE statements.
	 *
	 * @param string $prefix          WordPress table prefix.
	 * @param string $charset_collate Charset and collation string.
	 * @return array<int, string>
	 */
	private function get_table_definitions( string $prefix, string $charset_collate ): array {
		return array(
			"CREATE TABLE {$prefix}coordina_projects (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				code varchar(50) NOT NULL DEFAULT '',
				title varchar(191) NOT NULL,
				description longtext NULL,
				status varchar(50) NOT NULL DEFAULT 'draft',
				health varchar(50) NOT NULL DEFAULT 'neutral',
				priority varchar(50) NOT NULL DEFAULT 'normal',
				manager_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				sponsor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				visibility varchar(50) NOT NULL DEFAULT 'team',
				notification_policy varchar(50) NOT NULL DEFAULT 'default',
				task_group_label varchar(50) NOT NULL DEFAULT '',
				closeout_notes longtext NULL,
				workspace_id bigint(20) unsigned NOT NULL DEFAULT 0,
				start_date datetime NULL,
				target_end_date datetime NULL,
				actual_end_date datetime NULL,
				archived_at datetime NULL,
				created_by bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY status (status),
				KEY manager_user_id (manager_user_id),
				KEY sponsor_user_id (sponsor_user_id),
				KEY visibility (visibility),
				KEY workspace_id (workspace_id),
				KEY target_end_date (target_end_date),
				KEY archived_at (archived_at)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}coordina_project_members (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				project_id bigint(20) unsigned NOT NULL DEFAULT 0,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				member_role varchar(50) NOT NULL DEFAULT 'member',
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY project_user (project_id, user_id),
				KEY project_id (project_id),
				KEY user_id (user_id)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}coordina_tasks (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				project_id bigint(20) unsigned NOT NULL DEFAULT 0,
				parent_task_id bigint(20) unsigned NOT NULL DEFAULT 0,
				task_group_id bigint(20) unsigned NOT NULL DEFAULT 0,
				title varchar(191) NOT NULL,
				description longtext NULL,
				status varchar(50) NOT NULL DEFAULT 'new',
				priority varchar(50) NOT NULL DEFAULT 'normal',
				assignee_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				reporter_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				blocked tinyint(1) NOT NULL DEFAULT 0,
				blocked_reason text NULL,
				approval_required tinyint(1) NOT NULL DEFAULT 0,
				start_date datetime NULL,
				due_date datetime NULL,
				completion_percent tinyint(3) unsigned NOT NULL DEFAULT 0,
				actual_finish_date datetime NULL,
				completed_at datetime NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY project_id (project_id),
				KEY parent_task_id (parent_task_id),
				KEY task_group_id (task_group_id),
				KEY status (status),
				KEY assignee_user_id (assignee_user_id),
				KEY due_date (due_date)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}coordina_task_groups (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				project_id bigint(20) unsigned NOT NULL DEFAULT 0,
				title varchar(191) NOT NULL,
				sort_order int(11) NOT NULL DEFAULT 0,
				created_by bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY project_id (project_id),
				KEY sort_order (sort_order)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}coordina_task_checklist_items (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				task_id bigint(20) unsigned NOT NULL DEFAULT 0,
				item_text varchar(255) NOT NULL DEFAULT '',
				is_done tinyint(1) NOT NULL DEFAULT 0,
				sort_order int(11) NOT NULL DEFAULT 0,
				created_by bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY task_id (task_id),
				KEY is_done (is_done),
				KEY sort_order (sort_order)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}coordina_checklists (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				project_id bigint(20) unsigned NOT NULL DEFAULT 0,
				object_type varchar(50) NOT NULL,
				object_id bigint(20) unsigned NOT NULL DEFAULT 0,
				title varchar(191) NOT NULL DEFAULT '',
				sort_order int(11) NOT NULL DEFAULT 0,
				created_by bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY context_lookup (object_type, object_id),
				KEY project_id (project_id),
				KEY sort_order (sort_order)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}coordina_checklist_items (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				checklist_id bigint(20) unsigned NOT NULL DEFAULT 0,
				project_id bigint(20) unsigned NOT NULL DEFAULT 0,
				object_type varchar(50) NOT NULL,
				object_id bigint(20) unsigned NOT NULL DEFAULT 0,
				item_text varchar(255) NOT NULL DEFAULT '',
				is_done tinyint(1) NOT NULL DEFAULT 0,
				sort_order int(11) NOT NULL DEFAULT 0,
				created_by bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY checklist_id (checklist_id),
				KEY context_lookup (object_type, object_id),
				KEY project_id (project_id),
				KEY is_done (is_done),
				KEY sort_order (sort_order)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}coordina_requests (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				title varchar(191) NOT NULL,
				request_type varchar(100) NOT NULL DEFAULT '',
				requester_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				triage_owner_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				status varchar(50) NOT NULL DEFAULT 'submitted',
				priority varchar(50) NOT NULL DEFAULT 'normal',
				desired_due_date datetime NULL,
				business_reason longtext NULL,
				approval_status varchar(50) NOT NULL DEFAULT 'pending',
				converted_object_type varchar(50) NOT NULL DEFAULT '',
				converted_object_id bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY status (status),
				KEY triage_owner_user_id (triage_owner_user_id),
				KEY requester_user_id (requester_user_id)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}coordina_approvals (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				object_type varchar(50) NOT NULL,
				object_id bigint(20) unsigned NOT NULL DEFAULT 0,
				submitted_by_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				approver_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				status varchar(50) NOT NULL DEFAULT 'pending',
				rejection_reason text NULL,
				submitted_at datetime NOT NULL,
				decision_at datetime NULL,
				PRIMARY KEY  (id),
				KEY object_lookup (object_type, object_id),
				KEY approver_user_id (approver_user_id),
				KEY status (status)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}coordina_risks_issues (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				project_id bigint(20) unsigned NOT NULL DEFAULT 0,
				object_type varchar(20) NOT NULL DEFAULT 'risk',
				title varchar(191) NOT NULL,
				description longtext NULL,
				status varchar(50) NOT NULL DEFAULT 'identified',
				severity varchar(50) NOT NULL DEFAULT 'medium',
				impact varchar(50) NOT NULL DEFAULT 'medium',
				likelihood varchar(50) NOT NULL DEFAULT 'medium',
				owner_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				mitigation_plan longtext NULL,
				target_resolution_date datetime NULL,
				resolved_at datetime NULL,
				created_by bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY project_id (project_id),
				KEY object_type (object_type),
				KEY status (status),
				KEY severity (severity),
				KEY owner_user_id (owner_user_id),
				KEY target_resolution_date (target_resolution_date)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}coordina_milestones (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				project_id bigint(20) unsigned NOT NULL DEFAULT 0,
				title varchar(191) NOT NULL,
				status varchar(50) NOT NULL DEFAULT 'planned',
				owner_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				due_date datetime NULL,
				completion_percent tinyint(3) unsigned NOT NULL DEFAULT 0,
				dependency_flag tinyint(1) NOT NULL DEFAULT 0,
				notes longtext NULL,
				created_by bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY project_id (project_id),
				KEY status (status),
				KEY owner_user_id (owner_user_id),
				KEY due_date (due_date),
				KEY dependency_flag (dependency_flag)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}coordina_activity_log (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				object_type varchar(50) NOT NULL,
				object_id bigint(20) unsigned NOT NULL DEFAULT 0,
				event_type varchar(100) NOT NULL DEFAULT '',
				actor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				message longtext NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY object_lookup (object_type, object_id),
				KEY event_type (event_type),
				KEY actor_user_id (actor_user_id)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}coordina_saved_views (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				module varchar(50) NOT NULL,
				view_name varchar(191) NOT NULL,
				view_config longtext NULL,
				is_default tinyint(1) NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY user_id (user_id),
				KEY module (module)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}coordina_notifications (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				type varchar(100) NOT NULL DEFAULT '',
				title varchar(191) NOT NULL,
				body longtext NULL,
				action_url varchar(255) NOT NULL DEFAULT '',
				is_read tinyint(1) NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				read_at datetime NULL,
				PRIMARY KEY  (id),
				KEY user_id (user_id),
				KEY type (type),
				KEY is_read (is_read)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}coordina_files (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				project_id bigint(20) unsigned NOT NULL DEFAULT 0,
				object_type varchar(50) NOT NULL,
				object_id bigint(20) unsigned NOT NULL DEFAULT 0,
				attachment_id bigint(20) unsigned NOT NULL DEFAULT 0,
				file_name varchar(255) NOT NULL DEFAULT '',
				mime_type varchar(100) NOT NULL DEFAULT '',
				file_size bigint(20) unsigned NOT NULL DEFAULT 0,
				note text NULL,
				created_by bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY context_lookup (object_type, object_id),
				KEY project_id (project_id),
				KEY attachment_id (attachment_id),
				KEY created_at (created_at)
			) {$charset_collate};",
			"CREATE TABLE {$prefix}coordina_discussions (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				project_id bigint(20) unsigned NOT NULL DEFAULT 0,
				object_type varchar(50) NOT NULL,
				object_id bigint(20) unsigned NOT NULL DEFAULT 0,
				body longtext NULL,
				created_by bigint(20) unsigned NOT NULL DEFAULT 0,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY context_lookup (object_type, object_id),
				KEY project_id (project_id),
				KEY created_by (created_by),
				KEY created_at (created_at)
			) {$charset_collate};"
		);
	}

	/**
	 * Migrate legacy task checklist rows into the generic checklist table.
	 *
	 * @param string $prefix Table prefix.
	 * @return void
	 */
	private function migrate_task_checklists( string $prefix ): void {
		global $wpdb;

		$migration_key = 'coordina_checklist_items_migrated_0_2_5';

		if ( get_option( $migration_key, false ) ) {
			return;
		}

		$legacy_table = $prefix . 'coordina_task_checklist_items';
		$new_table    = $prefix . 'coordina_checklist_items';
		$tasks_table  = $prefix . 'coordina_tasks';

		$legacy_exists = (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $legacy_table ) );
		$new_exists    = (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $new_table ) );

		if ( $legacy_table !== $legacy_exists || $new_table !== $new_exists ) {
			update_option( $migration_key, 'missing-table', false );
			return;
		}

		$already_migrated = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$new_table} WHERE object_type = %s", 'task' ) );

		if ( $already_migrated <= 0 ) {
			$wpdb->query(
				"INSERT INTO {$new_table} (project_id, object_type, object_id, item_text, is_done, sort_order, created_by, created_at, updated_at)
				SELECT task.project_id, 'task', legacy.task_id, legacy.item_text, legacy.is_done, legacy.sort_order, legacy.created_by, legacy.created_at, legacy.updated_at
				FROM {$legacy_table} legacy
				LEFT JOIN {$tasks_table} task ON task.id = legacy.task_id"
			);
		}

		update_option( $migration_key, self::VERSION, false );
	}

	/**
	 * Create grouped checklist headers for existing flat checklist rows.
	 *
	 * @param string $prefix Table prefix.
	 * @return void
	 */
	private function migrate_grouped_checklists( string $prefix ): void {
		global $wpdb;

		$migration_key  = 'coordina_checklists_grouped_migrated_0_2_6';
		$headers_table  = $prefix . 'coordina_checklists';
		$items_table    = $prefix . 'coordina_checklist_items';

		if ( get_option( $migration_key, false ) ) {
			return;
		}

		$headers_exists = (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $headers_table ) );
		$items_exists   = (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $items_table ) );

		if ( $headers_table !== $headers_exists || $items_table !== $items_exists ) {
			update_option( $migration_key, 'missing-table', false );
			return;
		}

		$contexts = $wpdb->get_results(
			"SELECT object_type, object_id, MAX(project_id) AS project_id
			FROM {$items_table}
			WHERE COALESCE(checklist_id, 0) = 0
			GROUP BY object_type, object_id"
		);

		foreach ( $contexts ?: array() as $context ) {
			$object_type = sanitize_key( (string) $context->object_type );
			$object_id   = (int) $context->object_id;
			$project_id  = (int) $context->project_id;

			if ( '' === $object_type || $object_id <= 0 ) {
				continue;
			}

			$header_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$headers_table} WHERE object_type = %s AND object_id = %d AND title = %s ORDER BY sort_order ASC, id ASC LIMIT 1",
					$object_type,
					$object_id,
					'Checklist'
				)
			);

			if ( $header_id <= 0 ) {
				$wpdb->insert(
					$headers_table,
					array(
						'project_id'  => $project_id,
						'object_type' => $object_type,
						'object_id'   => $object_id,
						'title'       => 'Checklist',
						'sort_order'  => 10,
						'created_by'  => 0,
						'created_at'  => current_time( 'mysql', true ),
						'updated_at'  => current_time( 'mysql', true ),
					)
				);
				$header_id = (int) $wpdb->insert_id;
			}

			if ( $header_id > 0 ) {
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$items_table} SET checklist_id = %d WHERE object_type = %s AND object_id = %d AND COALESCE(checklist_id, 0) = 0",
						$header_id,
						$object_type,
						$object_id
					)
				);
			}
		}

		update_option( $migration_key, self::VERSION, false );
	}

	/**
	 * Add the project sponsor field for existing installs.
	 *
	 * @param string $prefix Table prefix.
	 * @return void
	 */
	private function migrate_project_sponsor_field( string $prefix ): void {
		global $wpdb;

		$migration_key = 'coordina_project_sponsor_migrated_0_2_8';
		$table         = $prefix . 'coordina_projects';

		if ( get_option( $migration_key, false ) ) {
			return;
		}

		$table_exists = (string) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		if ( $table !== $table_exists ) {
			update_option( $migration_key, 'missing-table', false );
			return;
		}

		$column_exists = (string) $wpdb->get_var( $wpdb->prepare( 'SHOW COLUMNS FROM ' . $table . ' LIKE %s', 'sponsor_user_id' ) );

		if ( '' === $column_exists ) {
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN sponsor_user_id bigint(20) unsigned NOT NULL DEFAULT 0 AFTER manager_user_id" );
		}

		update_option( $migration_key, self::VERSION, false );
	}
}
