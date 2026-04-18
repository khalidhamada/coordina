<?php
/**
 * Shared access policy for Coordina work objects.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Access;

use Coordina\Infrastructure\Persistence\SettingsRepository;
use Coordina\Platform\Contracts\AccessPolicyInterface;
use Coordina\Platform\Contracts\SettingsStoreInterface;
use wpdb;

final class AccessPolicy implements AccessPolicyInterface {
	/**
	 * WordPress database object.
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Cached settings.
	 *
	 * @var array<string, mixed>|null
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param SettingsStoreInterface|null $settings_repository Shared settings repository.
	 */
	public function __construct( ?SettingsStoreInterface $settings_repository = null ) {
		global $wpdb;
		$this->wpdb     = $wpdb;
		$this->settings = $settings_repository ? $settings_repository->get() : null;
	}

	/**
	 * Determine whether the current user has broad project access.
	 */
	public function has_full_project_access(): bool {
		return current_user_can( 'coordina_manage_settings' );
	}

	/**
	 * Determine whether the current user can edit projects.
	 */
	public function can_edit_projects(): bool {
		return current_user_can( 'coordina_manage_projects' ) || current_user_can( 'coordina_manage_settings' );
	}

	/**
	 * Determine whether the current user can view a project.
	 */
	public function can_view_project( int $project_id ): bool {
		if ( $project_id <= 0 || ! current_user_can( 'coordina_access' ) ) {
			return false;
		}

		if ( $this->has_full_project_access() ) {
			return true;
		}

		return $this->exists_by_sql( $this->project_access_exists_sql(), $this->project_access_exists_params( $project_id ) );
	}

	/**
	 * Determine whether the current user can edit a project.
	 */
	public function can_edit_project( int $project_id ): bool {
		if ( $project_id <= 0 || ! $this->can_edit_projects() ) {
			return false;
		}

		if ( current_user_can( 'coordina_manage_settings' ) ) {
			return true;
		}

		$project = $this->get_project_row( $project_id );

		if ( empty( $project ) ) {
			return false;
		}

		$user_id = get_current_user_id();

		return (int) ( $project['manager_user_id'] ?? 0 ) === $user_id || (int) ( $project['created_by'] ?? 0 ) === $user_id;
	}

	/**
	 * Determine whether the current user can delete a project.
	 */
	public function can_delete_project( int $project_id ): bool {
		return $this->can_edit_project( $project_id );
	}

	/**
	 * Determine whether the current user can view a task.
	 */
	public function can_view_task( int $task_id ): bool {
		if ( $task_id <= 0 || ! current_user_can( 'coordina_access' ) ) {
			return false;
		}

		if ( $this->has_full_project_access() ) {
			return true;
		}

		$task = $this->get_task_row( $task_id );

		if ( empty( $task ) ) {
			return false;
		}

		if ( (int) ( $task['project_id'] ?? 0 ) > 0 && 'all-tasks-in-accessible-projects' === $this->task_visibility_mode() ) {
			return $this->can_view_project( (int) $task['project_id'] );
		}

		return $this->user_is_task_participant( $task );
	}

	/**
	 * Determine whether the current user can fully edit a task.
	 */
	public function can_fully_edit_task( int $task_id ): bool {
		if ( ! current_user_can( 'coordina_access' ) ) {
			return false;
		}

		if ( $this->has_full_project_access() ) {
			return true;
		}

		$task = $this->get_task_row( $task_id );

		if ( empty( $task ) || ! $this->can_view_task( $task_id ) ) {
			return false;
		}

		$project_id = (int) ( $task['project_id'] ?? 0 );

		if ( $project_id > 0 ) {
			return $this->can_edit_project( $project_id );
		}

		return $this->user_is_standalone_task_owner( $task );
	}

	/**
	 * Determine whether the current user can update task progress.
	 */
	public function can_update_task_progress( int $task_id ): bool {
		if ( ! current_user_can( 'coordina_access' ) ) {
			return false;
		}

		if ( $this->can_fully_edit_task( $task_id ) ) {
			return true;
		}

		$task = $this->get_task_row( $task_id );

		if ( empty( $task ) || ! $this->can_view_task( $task_id ) ) {
			return false;
		}

		$project_id = (int) ( $task['project_id'] ?? 0 );
		$policy     = $this->task_edit_policy_mode();

		if ( 'all-project-members' === $policy && $project_id > 0 ) {
			return $this->can_view_project( $project_id );
		}

		if ( 'assignee-or-reporter' === $policy ) {
			return $this->user_is_task_participant( $task );
		}

		if ( (int) ( $task['assignee_user_id'] ?? 0 ) === get_current_user_id() ) {
			return true;
		}

		return $project_id <= 0 && 0 === (int) ( $task['assignee_user_id'] ?? 0 ) && $this->user_is_standalone_task_owner( $task );
	}

	/**
	 * Determine whether the current user can edit a task.
	 */
	public function can_edit_task( int $task_id ): bool {
		return $this->can_update_task_progress( $task_id );
	}

	/**
	 * Determine whether the current user can delete a task.
	 */
	public function can_delete_task( int $task_id ): bool {
		if ( ! current_user_can( 'coordina_access' ) ) {
			return false;
		}

		if ( $this->has_full_project_access() ) {
			return true;
		}

		$task = $this->get_task_row( $task_id );

		if ( empty( $task ) ) {
			return false;
		}

		// Standalone tasks can be removed by the original reporter.
		return (int) ( $task['project_id'] ?? 0 ) <= 0 && (int) ( $task['reporter_user_id'] ?? 0 ) === get_current_user_id();
	}

	/**
	 * Determine whether the current user can view a request.
	 */
	public function can_view_request( int $request_id ): bool {
		if ( $request_id <= 0 || ! current_user_can( 'coordina_access' ) ) {
			return false;
		}

		if ( $this->has_full_project_access() ) {
			return true;
		}

		return $this->exists_by_sql(
			'SELECT COUNT(*) FROM ' . $this->table( 'requests' ) . ' WHERE id = %d AND (requester_user_id = %d OR triage_owner_user_id = %d)',
			array( $request_id, get_current_user_id(), get_current_user_id() )
		);
	}

	/**
	 * Determine whether the current user can edit a request.
	 */
	public function can_edit_request( int $request_id ): bool {
		return $this->can_view_request( $request_id );
	}

	/**
	 * Determine whether the current user can delete a request.
	 */
	public function can_delete_request( int $request_id ): bool {
		if ( ! current_user_can( 'coordina_access' ) ) {
			return false;
		}

		if ( $this->has_full_project_access() ) {
			return true;
		}

		return $this->exists_by_sql(
			'SELECT COUNT(*) FROM ' . $this->table( 'requests' ) . " WHERE id = %d AND requester_user_id = %d AND status IN ('submitted', 'under-review', 'awaiting-info')",
			array( $request_id, get_current_user_id() )
		);
	}

	/**
	 * Determine whether the current user can view an approval.
	 */
	public function can_view_approval( int $approval_id ): bool {
		if ( $approval_id <= 0 || ! current_user_can( 'coordina_access' ) ) {
			return false;
		}

		if ( $this->has_full_project_access() ) {
			return true;
		}

		return $this->exists_by_sql(
			'SELECT COUNT(*) FROM ' . $this->table( 'approvals' ) . ' WHERE id = %d AND (approver_user_id = %d OR submitted_by_user_id = %d)',
			array( $approval_id, get_current_user_id(), get_current_user_id() )
		);
	}

	/**
	 * Determine whether the current user can edit an approval decision.
	 */
	public function can_edit_approval( int $approval_id ): bool {
		if ( $this->has_full_project_access() ) {
			return true;
		}

		return $this->exists_by_sql(
			'SELECT COUNT(*) FROM ' . $this->table( 'approvals' ) . ' WHERE id = %d AND approver_user_id = %d',
			array( $approval_id, get_current_user_id() )
		);
	}

	/**
	 * Determine whether the current user can view a risk or issue.
	 */
	public function can_view_risk_issue( int $risk_issue_id ): bool {
		if ( $risk_issue_id <= 0 || ! current_user_can( 'coordina_access' ) ) {
			return false;
		}

		if ( $this->has_full_project_access() ) {
			return true;
		}

		$row = $this->prepared_row(
			'SELECT project_id, owner_user_id, created_by FROM ' . $this->table( 'risks_issues' ) . ' WHERE id = %d',
			array( $risk_issue_id ),
			\ARRAY_A
		);

		if ( ! is_array( $row ) || empty( $row ) ) {
			return false;
		}

		$user_id    = get_current_user_id();
		$project_id = (int) ( $row['project_id'] ?? 0 );

		if ( $project_id > 0 && $this->can_view_project( $project_id ) ) {
			return true;
		}

		return (int) ( $row['owner_user_id'] ?? 0 ) === $user_id || (int) ( $row['created_by'] ?? 0 ) === $user_id;
	}

	/**
	 * Determine whether the current user can edit a risk or issue.
	 */
	public function can_edit_risk_issue( int $risk_issue_id ): bool {
		if ( ! current_user_can( 'coordina_access' ) ) {
			return false;
		}

		if ( $this->has_full_project_access() ) {
			return true;
		}

		$row = $this->prepared_row(
			'SELECT project_id, owner_user_id, created_by FROM ' . $this->table( 'risks_issues' ) . ' WHERE id = %d',
			array( $risk_issue_id ),
			\ARRAY_A
		);

		if ( ! is_array( $row ) || empty( $row ) ) {
			return false;
		}

		$user_id    = get_current_user_id();
		$project_id = (int) ( $row['project_id'] ?? 0 );

		if ( $project_id > 0 && $this->can_edit_project( $project_id ) ) {
			return true;
		}

		return (int) ( $row['owner_user_id'] ?? 0 ) === $user_id || (int) ( $row['created_by'] ?? 0 ) === $user_id;
	}

	/**
	 * Determine whether the current user can delete a risk or issue.
	 */
	public function can_delete_risk_issue( int $risk_issue_id ): bool {
		if ( ! current_user_can( 'coordina_access' ) ) {
			return false;
		}

		if ( $risk_issue_id <= 0 ) {
			return false;
		}

		if ( current_user_can( 'coordina_manage_settings' ) ) {
			return true;
		}

		$project_id = (int) $this->prepared_var(
			'SELECT project_id FROM ' . $this->table( 'risks_issues' ) . ' WHERE id = %d',
			array( $risk_issue_id )
		);

		if ( $project_id > 0 ) {
			return $this->can_edit_project( $project_id );
		}

		return $this->exists_by_sql(
			'SELECT COUNT(*) FROM ' . $this->table( 'risks_issues' ) . ' WHERE id = %d AND (owner_user_id = %d OR created_by = %d)',
			array( $risk_issue_id, get_current_user_id(), get_current_user_id() )
		);
	}

	/**
	 * Determine whether the current user can view a milestone.
	 */
	public function can_view_milestone( int $milestone_id ): bool {
		if ( $milestone_id <= 0 || ! current_user_can( 'coordina_access' ) ) {
			return false;
		}

		if ( $this->has_full_project_access() ) {
			return true;
		}

		$row = $this->prepared_row(
			'SELECT project_id, owner_user_id FROM ' . $this->table( 'milestones' ) . ' WHERE id = %d',
			array( $milestone_id ),
			\ARRAY_A
		);

		if ( ! is_array( $row ) || empty( $row ) ) {
			return false;
		}

		return (int) ( $row['owner_user_id'] ?? 0 ) === get_current_user_id() || $this->can_view_project( (int) ( $row['project_id'] ?? 0 ) );
	}

	/**
	 * Determine whether the current user can delete a milestone.
	 */
	public function can_delete_milestone( int $milestone_id ): bool {
		if ( ! current_user_can( 'coordina_access' ) ) {
			return false;
		}

		if ( $milestone_id <= 0 ) {
			return false;
		}

		if ( current_user_can( 'coordina_manage_settings' ) ) {
			return true;
		}

		$project_id = (int) $this->prepared_var(
			'SELECT project_id FROM ' . $this->table( 'milestones' ) . ' WHERE id = %d',
			array( $milestone_id )
		);

		return $project_id > 0 && $this->can_edit_project( $project_id );
	}

	/**
	 * Determine whether the current user can view a generic parent context.
	 */
	public function can_view_context( string $object_type, int $object_id ): bool {
		$object_type = sanitize_key( $object_type );

		if ( 'project' === $object_type ) {
			return $this->can_view_project( $object_id );
		}

		if ( 'task' === $object_type ) {
			return $this->can_view_task( $object_id );
		}

		if ( 'request' === $object_type ) {
			return $this->can_view_request( $object_id );
		}

		if ( 'approval' === $object_type ) {
			return $this->can_view_approval( $object_id );
		}

		if ( in_array( $object_type, array( 'risk', 'issue' ), true ) ) {
			return $this->can_view_risk_issue( $object_id );
		}

		if ( 'milestone' === $object_type ) {
			return $this->can_view_milestone( $object_id );
		}

		return false;
	}

	/**
	 * Determine whether the current user can post updates to a context.
	 */
	public function can_post_update_on_context( string $object_type, int $object_id ): bool {
		return current_user_can( 'coordina_access' ) && $this->can_view_context( $object_type, $object_id );
	}

	/**
	 * Determine whether the current user can attach files to a context.
	 */
	public function can_attach_files_to_context( string $object_type, int $object_id ): bool {
		$object_type = $this->normalize_context_type( $object_type );

		if ( ! current_user_can( 'coordina_access' ) || ! current_user_can( 'upload_files' ) ) {
			return false;
		}

		if ( 'approval' === $object_type ) {
			return false;
		}

		if ( 'project' === $object_type ) {
			return $this->can_attach_files_to_project( $object_id );
		}

		if ( 'task' === $object_type ) {
			return $this->can_attach_files_to_task( $object_id );
		}

		if ( 'milestone' === $object_type ) {
			return $this->can_attach_files_to_milestone( $object_id );
		}

		if ( 'risk_issue' === $object_type ) {
			return $this->can_attach_files_to_risk_issue( $object_id );
		}

		if ( 'request' === $object_type ) {
			return $this->can_attach_files_to_request( $object_id );
		}

		return false;
	}

	/**
	 * Determine whether the current user can manage checklist structure on a context.
	 */
	public function can_manage_checklists_on_context( string $object_type, int $object_id ): bool {
		$object_type = $this->normalize_context_type( $object_type );

		if ( ! current_user_can( 'coordina_access' ) ) {
			return false;
		}

		if ( $this->has_full_project_access() ) {
			return true;
		}

		if ( 'project' === $object_type ) {
			return $this->can_manage_project_checklists( $object_id );
		}

		if ( 'task' === $object_type ) {
			return $this->can_manage_task_checklists( $object_id );
		}

		if ( 'milestone' === $object_type ) {
			return $this->can_manage_milestone_checklists( $object_id );
		}

		if ( 'risk_issue' === $object_type ) {
			return $this->can_manage_risk_issue_checklists( $object_id );
		}

		return false;
	}

	/**
	 * Determine whether the current user can toggle checklist completion on a context.
	 */
	public function can_toggle_checklists_on_context( string $object_type, int $object_id ): bool {
		$object_type = $this->normalize_context_type( $object_type );

		if ( ! current_user_can( 'coordina_access' ) ) {
			return false;
		}

		if ( $this->has_full_project_access() ) {
			return true;
		}

		if ( 'project' === $object_type ) {
			return $this->can_toggle_project_checklists( $object_id );
		}

		if ( 'task' === $object_type ) {
			return $this->can_toggle_task_checklists( $object_id );
		}

		if ( 'milestone' === $object_type ) {
			return $this->can_toggle_milestone_checklists( $object_id );
		}

		if ( 'risk_issue' === $object_type ) {
			return $this->can_toggle_risk_issue_checklists( $object_id );
		}

		return false;
	}

	/**
	 * Determine whether the current user can add collaboration to a context.
	 */
	public function can_collaborate_on_context( string $object_type, int $object_id ): bool {
		return $this->can_post_update_on_context( $object_type, $object_id );
	}

	/**
	 * Build project visibility SQL for repository queries.
	 *
	 * @param string $project_column SQL column or expression containing a project id.
	 * @return array{0:string,1:array<int,mixed>}
	 */
	public function project_access_where( string $project_column ): array {
		if ( $this->has_full_project_access() ) {
			return array( '1=1', array() );
		}

		if ( 'all-projects' === $this->project_list_visibility_mode() && current_user_can( 'coordina_manage_projects' ) ) {
			return array( '1=1', array() );
		}

		$assigned_only = 'assigned-projects-only' === $this->project_list_visibility_mode();
		list( $private_sql, $private_params ) = $this->project_visibility_sql( $project_column, 'private', true, false );
		list( $team_sql, $team_params ) = $this->project_visibility_sql( $project_column, 'team', false, $assigned_only );

		return array(
			'(' . $this->public_project_sql( $project_column ) . ' OR ' . $private_sql . ' OR ' . $team_sql . ')',
			array_merge( $private_params, $team_params ),
		);
	}

	/**
	 * Build task visibility SQL for repository queries.
	 *
	 * @param string $task_column SQL column or expression containing a task id.
	 * @return array{0:string,1:array<int,mixed>}
	 */
	public function task_access_where( string $task_column ): array {
		if ( $this->has_full_project_access() ) {
			return array( '1=1', array() );
		}

		if ( 'all-tasks-in-accessible-projects' === $this->task_visibility_mode() ) {
			return array(
				"{$task_column} IN (SELECT id FROM " . $this->table( 'tasks' ) . ' WHERE (assignee_user_id = %d OR reporter_user_id = %d) OR (project_id > 0 AND project_id IN (' . $this->workspace_access_subquery() . ')))',
				array_merge( array( get_current_user_id(), get_current_user_id() ), $this->workspace_access_params() ),
			);
		}

		return array(
			"{$task_column} IN (SELECT id FROM " . $this->table( 'tasks' ) . ' WHERE assignee_user_id = %d OR reporter_user_id = %d)',
			array( get_current_user_id(), get_current_user_id() ),
		);
	}

	/**
	 * Build request visibility SQL for repository queries.
	 *
	 * @param string $request_column SQL column or expression containing a request id.
	 * @return array{0:string,1:array<int,mixed>}
	 */
	public function request_access_where( string $request_column ): array {
		if ( $this->has_full_project_access() ) {
			return array( '1=1', array() );
		}

		return array(
			"{$request_column} IN (SELECT id FROM " . $this->table( 'requests' ) . ' WHERE requester_user_id = %d OR triage_owner_user_id = %d)',
			array( get_current_user_id(), get_current_user_id() ),
		);
	}

	/**
	 * Build approval visibility SQL for repository queries.
	 *
	 * @param string $approval_column SQL column or expression containing an approval id.
	 * @return array{0:string,1:array<int,mixed>}
	 */
	public function approval_access_where( string $approval_column ): array {
		if ( $this->has_full_project_access() ) {
			return array( '1=1', array() );
		}

		return array(
			"{$approval_column} IN (SELECT id FROM " . $this->table( 'approvals' ) . ' WHERE approver_user_id = %d OR submitted_by_user_id = %d)',
			array( get_current_user_id(), get_current_user_id() ),
		);
	}

	/**
	 * Build risk/issue visibility SQL for repository queries.
	 *
	 * @param string $risk_issue_column SQL column or expression containing a risk/issue id.
	 * @return array{0:string,1:array<int,mixed>}
	 */
	public function risk_issue_access_where( string $risk_issue_column ): array {
		if ( $this->has_full_project_access() ) {
			return array( '1=1', array() );
		}

		list( $project_sql, $project_params ) = $this->project_access_where( 'project_id' );

		return array(
			"{$risk_issue_column} IN (SELECT id FROM " . $this->table( 'risks_issues' ) . " WHERE owner_user_id = %d OR created_by = %d OR (project_id > 0 AND {$project_sql}))",
			array_merge( array( get_current_user_id(), get_current_user_id() ), $project_params ),
		);
	}

	/**
	 * Build context visibility SQL for files/discussions.
	 *
	 * @param string      $type_column       SQL column containing object type.
	 * @param string      $id_column         SQL column containing object id.
	 * @param string|null $created_by_column SQL column containing creator id.
	 * @return array{0:string,1:array<int,mixed>}
	 */
	public function context_access_where( string $type_column, string $id_column, ?string $created_by_column = null ): array {
		if ( $this->has_full_project_access() ) {
			return array( '1=1', array() );
		}

		$params = array();
		$parts  = array();

		if ( null !== $created_by_column ) {
			$parts[]  = "{$created_by_column} = %d";
			$params[] = get_current_user_id();
		}

		list( $project_sql, $project_params ) = $this->project_access_where( $id_column );
		$parts[] = "({$type_column} = 'project' AND {$project_sql})";
		$params  = array_merge( $params, $project_params );

		list( $task_sql, $task_params ) = $this->task_access_where( $id_column );
		$parts[] = "({$type_column} = 'task' AND {$task_sql})";
		$params  = array_merge( $params, $task_params );

		list( $request_sql, $request_params ) = $this->request_access_where( $id_column );
		$parts[] = "({$type_column} = 'request' AND {$request_sql})";
		$params  = array_merge( $params, $request_params );

		list( $approval_sql, $approval_params ) = $this->approval_access_where( $id_column );
		$parts[] = "({$type_column} = 'approval' AND {$approval_sql})";
		$params  = array_merge( $params, $approval_params );

		list( $risk_sql, $risk_params ) = $this->risk_issue_access_where( $id_column );
		$parts[] = "({$type_column} IN ('risk', 'issue') AND {$risk_sql})";
		$params  = array_merge( $params, $risk_params );

		list( $milestone_sql, $milestone_params ) = $this->project_access_where(
			'(SELECT project_id FROM ' . $this->table( 'milestones' ) . " WHERE id = {$id_column} LIMIT 1)"
		);
		$parts[] = "({$type_column} = 'milestone' AND {$milestone_sql})";
		$params  = array_merge( $params, $milestone_params );

		return array( '(' . implode( ' OR ', $parts ) . ')', $params );
	}

	/**
	 * Get a table name.
	 */
	private function table( string $suffix ): string {
		return $this->wpdb->prefix . 'coordina_' . $suffix;
	}

	/**
	 * Check existence with prepared SQL.
	 *
	 * @param string           $sql    SQL with placeholders.
	 * @param array<int,mixed> $params Prepare params.
	 */
	private function exists_by_sql( string $sql, array $params ): bool {
		return (int) $this->prepared_var( $sql, $params ) > 0;
	}

	/**
	 * Project access exists SQL.
	 */
	private function project_access_exists_sql(): string {
		list( $private_sql ) = $this->project_visibility_sql( 'id', 'private', true, false );
		list( $team_sql ) = $this->project_visibility_sql( 'id', 'team', false, false );

		return 'SELECT COUNT(*) FROM ' . $this->table( 'projects' ) . ' WHERE id = %d AND (' . $this->public_project_sql( 'id' ) . ' OR ' . $private_sql . ' OR ' . $team_sql . ')';
	}

	/**
	 * Build params for project access existence checks.
	 *
	 * @return array<int,mixed>
	 */
	private function project_access_exists_params( int $project_id ): array {
		list( , $private_params ) = $this->project_visibility_sql( 'id', 'private', true, false );
		list( , $team_params ) = $this->project_visibility_sql( 'id', 'team', false, false );

		return array_merge( array( $project_id ), $private_params, $team_params );
	}

	/**
	 * Workspace access subquery for the current user.
	 */
	private function workspace_access_subquery(): string {
		if ( 'members-only' === $this->workspace_visibility_mode() ) {
			return $this->member_access_subquery();
		}

		return $this->assignment_access_subquery();
	}

	/**
	 * Workspace access params for the current user.
	 *
	 * @return array<int,int>
	 */
	private function workspace_access_params(): array {
		if ( 'members-only' === $this->workspace_visibility_mode() ) {
			return $this->member_access_params();
		}

		return $this->assignment_access_params();
	}

	/**
	 * Member-based project access subquery for the current user.
	 */
	private function member_access_subquery(): string {
		$projects_table = $this->table( 'projects' );
		$members_table  = $this->table( 'project_members' );

		return "SELECT id FROM {$projects_table} WHERE manager_user_id = %d OR created_by = %d
			OR id IN (SELECT project_id FROM {$members_table} WHERE user_id = %d)";
	}

	/**
	 * Member-based project access params.
	 *
	 * @return array<int,int>
	 */
	private function member_access_params(): array {
		$user_id = get_current_user_id();
		return array( $user_id, $user_id, $user_id );
	}

	/**
	 * Assignment-aware project access subquery for the current user.
	 */
	private function assignment_access_subquery(): string {
		$projects_table = $this->table( 'projects' );
		$tasks_table    = $this->table( 'tasks' );
		$risks_table    = $this->table( 'risks_issues' );
		$milestones_table = $this->table( 'milestones' );
		$members_table  = $this->table( 'project_members' );
		$approvals_table = $this->table( 'approvals' );

		return "SELECT id FROM {$projects_table} WHERE manager_user_id = %d OR created_by = %d
			OR id IN (SELECT project_id FROM {$members_table} WHERE user_id = %d)
			OR id IN (SELECT project_id FROM {$tasks_table} WHERE project_id > 0 AND (assignee_user_id = %d OR reporter_user_id = %d))
			OR id IN (SELECT project_id FROM {$risks_table} WHERE project_id > 0 AND (owner_user_id = %d OR created_by = %d))
			OR id IN (SELECT project_id FROM {$milestones_table} WHERE project_id > 0 AND owner_user_id = %d)
			OR id IN (SELECT object_id FROM {$approvals_table} WHERE object_type = 'project' AND (approver_user_id = %d OR submitted_by_user_id = %d))
			OR id IN (SELECT task.project_id FROM {$tasks_table} task INNER JOIN {$approvals_table} approval ON approval.object_type = 'task' AND approval.object_id = task.id WHERE task.project_id > 0 AND (approval.approver_user_id = %d OR approval.submitted_by_user_id = %d))";
	}

	/**
	 * Assignment-aware project access params.
	 *
	 * @return array<int,int>
	 */
	private function assignment_access_params(): array {
		$user_id = get_current_user_id();
		return array( $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id );
	}

	/**
	 * Build public visibility SQL for a project column.
	 */
	private function public_project_sql( string $project_column ): string {
		return "EXISTS (SELECT 1 FROM " . $this->table( 'projects' ) . " project_access WHERE project_access.id = {$project_column} AND project_access.visibility = 'public')";
	}

	/**
	 * Build visibility-aware SQL for a project column.
	 *
	 * @return array{0:string,1:array<int,mixed>}
	 */
	private function project_visibility_sql( string $project_column, string $visibility, bool $strict_membership, bool $assigned_only ): array {
		$subquery = $strict_membership
			? $this->member_access_subquery()
			: ( $assigned_only ? $this->assignment_access_subquery() : $this->workspace_access_subquery() );
		$params   = $strict_membership
			? $this->member_access_params()
			: ( $assigned_only ? $this->assignment_access_params() : $this->workspace_access_params() );

		if ( 'all-coordina-users' === $this->workspace_visibility_mode() && ! $strict_membership ) {
			return array(
				"EXISTS (SELECT 1 FROM " . $this->table( 'projects' ) . " project_access WHERE project_access.id = {$project_column} AND project_access.visibility = %s)",
				array( $visibility ),
			);
		}

		return array(
			"EXISTS (SELECT 1 FROM " . $this->table( 'projects' ) . " project_access WHERE project_access.id = {$project_column} AND project_access.visibility = %s AND project_access.id IN (" . $subquery . '))',
			array_merge( array( $visibility ), $params ),
		);
	}

	/**
	 * Resolve current settings.
	 *
	 * @return array<string, mixed>
	 */
	private function settings(): array {
		if ( null === $this->settings ) {
			$this->settings = ( new SettingsRepository() )->get();
		}

		return is_array( $this->settings ) ? $this->settings : array();
	}

	/**
	 * Get workspace visibility mode.
	 */
	private function workspace_visibility_mode(): string {
		return (string) ( $this->settings()['access']['project_workspace_visibility'] ?? 'members-and-assignees' );
	}

	/**
	 * Get project list visibility mode.
	 */
	private function project_list_visibility_mode(): string {
		return (string) ( $this->settings()['access']['project_list_visibility'] ?? 'all-projects' );
	}

	/**
	 * Get task visibility mode.
	 */
	private function task_visibility_mode(): string {
		return (string) ( $this->settings()['access']['project_task_visibility'] ?? 'all-tasks-in-accessible-projects' );
	}

	/**
	 * Get task edit policy mode.
	 */
	private function task_edit_policy_mode(): string {
		return (string) ( $this->settings()['access']['task_edit_policy'] ?? 'assignee-only' );
	}

	/**
	 * Get file attachment policy mode for a context.
	 */
	private function file_attachment_policy_mode( string $object_type ): string {
		$object_type = $this->normalize_context_type( $object_type );
		$defaults    = array(
			'project'    => 'project-leads-only',
			'task'       => 'assignee-and-project-leads',
			'milestone'  => 'owner-and-project-leads',
			'risk_issue' => 'owner-and-project-leads',
			'request'    => 'request-participants',
		);
		$rules       = $this->settings()['access']['file_attachment_rules'] ?? array();

		return (string) ( $rules[ $object_type ] ?? $defaults[ $object_type ] ?? 'project-leads-only' );
	}

	/**
	 * Get checklist management policy mode for a context.
	 */
	private function checklist_manage_policy_mode( string $object_type ): string {
		$object_type = $this->normalize_context_type( $object_type );
		$defaults    = array(
			'project'    => 'project-leads-only',
			'task'       => 'project-leads-only',
			'milestone'  => 'project-leads-only',
			'risk_issue' => 'project-leads-only',
		);
		$rules       = $this->settings()['access']['checklist_manage_rules'] ?? array();

		return (string) ( $rules[ $object_type ] ?? $defaults[ $object_type ] ?? 'project-leads-only' );
	}

	/**
	 * Get checklist toggle policy mode for a context.
	 */
	private function checklist_toggle_policy_mode( string $object_type ): string {
		$object_type = $this->normalize_context_type( $object_type );
		$defaults    = array(
			'project'    => 'project-leads-only',
			'task'       => 'assignee-and-project-leads',
			'milestone'  => 'owner-and-project-leads',
			'risk_issue' => 'owner-and-project-leads',
		);
		$rules       = $this->settings()['access']['checklist_toggle_rules'] ?? array();

		return (string) ( $rules[ $object_type ] ?? $defaults[ $object_type ] ?? 'project-leads-only' );
	}

	/**
	 * Fetch a task row.
	 *
	 * @return array<string, mixed>
	 */
	private function get_task_row( int $task_id ): array {
		if ( $task_id <= 0 ) {
			return array();
		}

		$row = $this->prepared_row( 'SELECT * FROM ' . $this->table( 'tasks' ) . ' WHERE id = %d', array( $task_id ), \ARRAY_A );

		return is_array( $row ) ? $row : array();
	}

	/**
	 * Fetch a milestone row.
	 *
	 * @return array<string, mixed>
	 */
	private function get_milestone_row( int $milestone_id ): array {
		if ( $milestone_id <= 0 ) {
			return array();
		}

		$row = $this->prepared_row( 'SELECT * FROM ' . $this->table( 'milestones' ) . ' WHERE id = %d', array( $milestone_id ), \ARRAY_A );

		return is_array( $row ) ? $row : array();
	}

	/**
	 * Fetch a project row.
	 *
	 * @return array<string, mixed>
	 */
	private function get_project_row( int $project_id ): array {
		if ( $project_id <= 0 ) {
			return array();
		}

		$row = $this->prepared_row( 'SELECT * FROM ' . $this->table( 'projects' ) . ' WHERE id = %d', array( $project_id ), \ARRAY_A );

		return is_array( $row ) ? $row : array();
	}

	/**
	 * Fetch a risk or issue row.
	 *
	 * @return array<string, mixed>
	 */
	private function get_risk_issue_row( int $risk_issue_id ): array {
		if ( $risk_issue_id <= 0 ) {
			return array();
		}

		$row = $this->prepared_row( 'SELECT * FROM ' . $this->table( 'risks_issues' ) . ' WHERE id = %d', array( $risk_issue_id ), \ARRAY_A );

		return is_array( $row ) ? $row : array();
	}

	/**
	 * Fetch a request row.
	 *
	 * @return array<string, mixed>
	 */
	private function get_request_row( int $request_id ): array {
		if ( $request_id <= 0 ) {
			return array();
		}

		$row = $this->prepared_row( 'SELECT * FROM ' . $this->table( 'requests' ) . ' WHERE id = %d', array( $request_id ), \ARRAY_A );

		return is_array( $row ) ? $row : array();
	}

	/**
	 * Prepare SQL with trusted internal fragments and placeholder values.
	 *
	 * @param string            $sql SQL statement.
	 * @param array<int, mixed> $params Placeholder values.
	 * @return string
	 */
	private function prepare_statement( string $sql, array $params = array() ): string {
		if ( empty( $params ) ) {
			return $sql;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Internal access-policy fragments are assembled from trusted helper methods before placeholder substitution.
		return $this->wpdb->prepare( $sql, $params );
	}

	/**
	 * Execute a prepared scalar query.
	 *
	 * @param string            $sql SQL statement.
	 * @param array<int, mixed> $params Placeholder values.
	 * @return mixed
	 */
	private function prepared_var( string $sql, array $params = array() ) {
		$prepared_sql = $this->prepare_statement( $sql, $params );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom-table access query prepared through shared policy helper.
		return $this->wpdb->get_var( $prepared_sql );
	}

	/**
	 * Execute a prepared row query.
	 *
	 * @param string            $sql SQL statement.
	 * @param array<int, mixed> $params Placeholder values.
	 * @param string|int        $output Output format.
	 * @return mixed
	 */
	private function prepared_row( string $sql, array $params = array(), $output = \OBJECT ) {
		$prepared_sql = $this->prepare_statement( $sql, $params );

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom-table access query prepared through shared policy helper.
		return $this->wpdb->get_row( $prepared_sql, $output );

		return is_array( $row ) ? $row : array();
	}

	/**
	 * Determine whether current user is the assignee or reporter on a task row.
	 *
	 * @param array<string, mixed> $task Task row.
	 */
	private function user_is_task_participant( array $task ): bool {
		$user_id = get_current_user_id();

		return (int) ( $task['assignee_user_id'] ?? 0 ) === $user_id || (int) ( $task['reporter_user_id'] ?? 0 ) === $user_id;
	}

	/**
	 * Determine whether current user is the task attachment owner.
	 *
	 * Falls back to the reporter when the task is unassigned.
	 *
	 * @param array<string, mixed> $task Task row.
	 */
	private function user_is_task_attachment_owner( array $task ): bool {
		$user_id     = get_current_user_id();
		$assignee_id = (int) ( $task['assignee_user_id'] ?? 0 );

		if ( $assignee_id > 0 ) {
			return $assignee_id === $user_id;
		}

		return (int) ( $task['reporter_user_id'] ?? 0 ) === $user_id || (int) ( $task['created_by'] ?? 0 ) === $user_id;
	}

	/**
	 * Determine whether the current user is a standalone task owner.
	 *
	 * @param array<string, mixed> $task Task row.
	 * @return bool
	 */
	private function user_is_standalone_task_owner( array $task ): bool {
		if ( (int) ( $task['project_id'] ?? 0 ) > 0 ) {
			return false;
		}

		return $this->user_is_task_attachment_owner( $task );
	}

	/**
	 * Determine whether current user matches any listed owner fields.
	 *
	 * @param array<string, mixed> $row Row data.
	 * @param string[]             $fields Candidate user-id fields.
	 */
	private function user_matches_any_field( array $row, array $fields ): bool {
		$user_id = get_current_user_id();

		foreach ( $fields as $field ) {
			if ( (int) ( $row[ $field ] ?? 0 ) === $user_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Normalize object types used in context checks.
	 */
	private function normalize_context_type( string $object_type ): string {
		$object_type = sanitize_key( $object_type );

		if ( in_array( $object_type, array( 'risk', 'issue' ), true ) ) {
			return 'risk_issue';
		}

		return $object_type;
	}

	/**
	 * Determine project file-attachment access.
	 */
	private function can_attach_files_to_project( int $project_id ): bool {
		if ( $project_id <= 0 || ! $this->can_view_project( $project_id ) ) {
			return false;
		}

		if ( $this->can_edit_project( $project_id ) ) {
			return true;
		}

		return 'project-members' === $this->file_attachment_policy_mode( 'project' );
	}

	/**
	 * Determine task file-attachment access.
	 */
	private function can_attach_files_to_task( int $task_id ): bool {
		$task = $this->get_task_row( $task_id );

		if ( empty( $task ) || ! $this->can_view_task( $task_id ) ) {
			return false;
		}

		$project_id = (int) ( $task['project_id'] ?? 0 );

		if ( $project_id > 0 && $this->can_edit_project( $project_id ) ) {
			return true;
		}

		$policy = $this->file_attachment_policy_mode( 'task' );

		if ( 'project-members' === $policy ) {
			return $project_id > 0 ? $this->can_view_project( $project_id ) : $this->user_is_task_participant( $task );
		}

		if ( 'task-participants-and-project-leads' === $policy ) {
			return $this->user_is_task_participant( $task );
		}

		return $this->user_is_task_attachment_owner( $task );
	}

	/**
	 * Determine milestone file-attachment access.
	 */
	private function can_attach_files_to_milestone( int $milestone_id ): bool {
		$row = $this->get_milestone_row( $milestone_id );

		if ( empty( $row ) || ! $this->can_view_milestone( $milestone_id ) ) {
			return false;
		}

		$project_id = (int) ( $row['project_id'] ?? 0 );

		if ( $project_id > 0 && $this->can_edit_project( $project_id ) ) {
			return true;
		}

		if ( 'project-members' === $this->file_attachment_policy_mode( 'milestone' ) ) {
			return $project_id > 0 && $this->can_view_project( $project_id );
		}

		return $this->user_matches_any_field( $row, array( 'owner_user_id', 'created_by' ) );
	}

	/**
	 * Determine risk/issue file-attachment access.
	 */
	private function can_attach_files_to_risk_issue( int $risk_issue_id ): bool {
		$row = $this->get_risk_issue_row( $risk_issue_id );

		if ( empty( $row ) || ! $this->can_view_risk_issue( $risk_issue_id ) ) {
			return false;
		}

		$project_id = (int) ( $row['project_id'] ?? 0 );

		if ( $project_id > 0 && $this->can_edit_project( $project_id ) ) {
			return true;
		}

		if ( 'project-members' === $this->file_attachment_policy_mode( 'risk_issue' ) ) {
			return $project_id > 0 && $this->can_view_project( $project_id );
		}

		return $this->user_matches_any_field( $row, array( 'owner_user_id', 'created_by' ) );
	}

	/**
	 * Determine request file-attachment access.
	 */
	private function can_attach_files_to_request( int $request_id ): bool {
		$row = $this->get_request_row( $request_id );

		if ( empty( $row ) || ! $this->can_view_request( $request_id ) ) {
			return false;
		}

		if ( 'triage-only' === $this->file_attachment_policy_mode( 'request' ) ) {
			return $this->user_matches_any_field( $row, array( 'triage_owner_user_id' ) );
		}

		return $this->user_matches_any_field( $row, array( 'requester_user_id', 'triage_owner_user_id', 'created_by' ) );
	}

	/**
	 * Determine project checklist-management access.
	 */
	private function can_manage_project_checklists( int $project_id ): bool {
		if ( $project_id <= 0 || ! $this->can_view_project( $project_id ) ) {
			return false;
		}

		if ( $this->can_edit_project( $project_id ) ) {
			return true;
		}

		return 'project-members' === $this->checklist_manage_policy_mode( 'project' );
	}

	/**
	 * Determine task checklist-management access.
	 */
	private function can_manage_task_checklists( int $task_id ): bool {
		$task = $this->get_task_row( $task_id );

		if ( empty( $task ) || ! $this->can_view_task( $task_id ) ) {
			return false;
		}

		$project_id = (int) ( $task['project_id'] ?? 0 );

		if ( $project_id > 0 && $this->can_edit_project( $project_id ) ) {
			return true;
		}

		$policy = $this->checklist_manage_policy_mode( 'task' );

		if ( 'project-members' === $policy ) {
			return $project_id > 0 ? $this->can_view_project( $project_id ) : $this->user_is_task_participant( $task ) || $this->user_is_standalone_task_owner( $task );
		}

		if ( 'task-participants-and-project-leads' === $policy ) {
			return $this->user_is_task_participant( $task );
		}

		if ( 'assignee-and-project-leads' === $policy ) {
			return $this->user_is_task_attachment_owner( $task );
		}

		return $this->user_is_standalone_task_owner( $task );
	}

	/**
	 * Determine milestone checklist-management access.
	 */
	private function can_manage_milestone_checklists( int $milestone_id ): bool {
		$row = $this->get_milestone_row( $milestone_id );

		if ( empty( $row ) || ! $this->can_view_milestone( $milestone_id ) ) {
			return false;
		}

		$project_id = (int) ( $row['project_id'] ?? 0 );

		if ( $project_id > 0 && $this->can_edit_project( $project_id ) ) {
			return true;
		}

		$policy = $this->checklist_manage_policy_mode( 'milestone' );

		if ( 'project-members' === $policy ) {
			return $project_id > 0 ? $this->can_view_project( $project_id ) : $this->user_matches_any_field( $row, array( 'owner_user_id', 'created_by' ) );
		}

		if ( 'owner-and-project-leads' === $policy ) {
			return $this->user_matches_any_field( $row, array( 'owner_user_id', 'created_by' ) );
		}

		return $project_id <= 0 && $this->user_matches_any_field( $row, array( 'owner_user_id', 'created_by' ) );
	}

	/**
	 * Determine risk/issue checklist-management access.
	 */
	private function can_manage_risk_issue_checklists( int $risk_issue_id ): bool {
		$row = $this->get_risk_issue_row( $risk_issue_id );

		if ( empty( $row ) || ! $this->can_view_risk_issue( $risk_issue_id ) ) {
			return false;
		}

		$project_id = (int) ( $row['project_id'] ?? 0 );

		if ( $project_id > 0 && $this->can_edit_project( $project_id ) ) {
			return true;
		}

		$policy = $this->checklist_manage_policy_mode( 'risk_issue' );

		if ( 'project-members' === $policy ) {
			return $project_id > 0 ? $this->can_view_project( $project_id ) : $this->user_matches_any_field( $row, array( 'owner_user_id', 'created_by' ) );
		}

		if ( 'owner-and-project-leads' === $policy ) {
			return $this->user_matches_any_field( $row, array( 'owner_user_id', 'created_by' ) );
		}

		return $project_id <= 0 && $this->user_matches_any_field( $row, array( 'owner_user_id', 'created_by' ) );
	}

	/**
	 * Determine project checklist-toggle access.
	 */
	private function can_toggle_project_checklists( int $project_id ): bool {
		if ( $project_id <= 0 || ! $this->can_view_project( $project_id ) ) {
			return false;
		}

		if ( $this->can_edit_project( $project_id ) ) {
			return true;
		}

		return 'project-members' === $this->checklist_toggle_policy_mode( 'project' );
	}

	/**
	 * Determine task checklist-toggle access.
	 */
	private function can_toggle_task_checklists( int $task_id ): bool {
		$task = $this->get_task_row( $task_id );

		if ( empty( $task ) || ! $this->can_view_task( $task_id ) ) {
			return false;
		}

		$project_id = (int) ( $task['project_id'] ?? 0 );

		if ( $project_id > 0 && $this->can_edit_project( $project_id ) ) {
			return true;
		}

		$policy = $this->checklist_toggle_policy_mode( 'task' );

		if ( 'project-members' === $policy ) {
			return $project_id > 0 ? $this->can_view_project( $project_id ) : $this->user_is_task_participant( $task ) || $this->user_is_standalone_task_owner( $task );
		}

		if ( 'task-participants-and-project-leads' === $policy ) {
			return $this->user_is_task_participant( $task );
		}

		if ( 'assignee-and-project-leads' === $policy ) {
			return $this->user_is_task_attachment_owner( $task );
		}

		return $this->user_is_standalone_task_owner( $task );
	}

	/**
	 * Determine milestone checklist-toggle access.
	 */
	private function can_toggle_milestone_checklists( int $milestone_id ): bool {
		$row = $this->get_milestone_row( $milestone_id );

		if ( empty( $row ) || ! $this->can_view_milestone( $milestone_id ) ) {
			return false;
		}

		$project_id = (int) ( $row['project_id'] ?? 0 );

		if ( $project_id > 0 && $this->can_edit_project( $project_id ) ) {
			return true;
		}

		$policy = $this->checklist_toggle_policy_mode( 'milestone' );

		if ( 'project-members' === $policy ) {
			return $project_id > 0 ? $this->can_view_project( $project_id ) : $this->user_matches_any_field( $row, array( 'owner_user_id', 'created_by' ) );
		}

		if ( 'owner-and-project-leads' === $policy ) {
			return $this->user_matches_any_field( $row, array( 'owner_user_id', 'created_by' ) );
		}

		return $project_id <= 0 && $this->user_matches_any_field( $row, array( 'owner_user_id', 'created_by' ) );
	}

	/**
	 * Determine risk/issue checklist-toggle access.
	 */
	private function can_toggle_risk_issue_checklists( int $risk_issue_id ): bool {
		$row = $this->get_risk_issue_row( $risk_issue_id );

		if ( empty( $row ) || ! $this->can_view_risk_issue( $risk_issue_id ) ) {
			return false;
		}

		$project_id = (int) ( $row['project_id'] ?? 0 );

		if ( $project_id > 0 && $this->can_edit_project( $project_id ) ) {
			return true;
		}

		$policy = $this->checklist_toggle_policy_mode( 'risk_issue' );

		if ( 'project-members' === $policy ) {
			return $project_id > 0 ? $this->can_view_project( $project_id ) : $this->user_matches_any_field( $row, array( 'owner_user_id', 'created_by' ) );
		}

		if ( 'owner-and-project-leads' === $policy ) {
			return $this->user_matches_any_field( $row, array( 'owner_user_id', 'created_by' ) );
		}

		return $project_id <= 0 && $this->user_matches_any_field( $row, array( 'owner_user_id', 'created_by' ) );
	}
}
