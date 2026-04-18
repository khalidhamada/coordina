<?php
/**
 * Public contract for Coordina access evaluation.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Contracts;

interface AccessPolicyInterface {
	public function has_full_project_access(): bool;

	public function can_edit_projects(): bool;

	public function can_view_project( int $project_id ): bool;

	public function can_edit_project( int $project_id ): bool;

	public function can_delete_project( int $project_id ): bool;

	public function can_view_task( int $task_id ): bool;

	public function can_fully_edit_task( int $task_id ): bool;

	public function can_update_task_progress( int $task_id ): bool;

	public function can_edit_task( int $task_id ): bool;

	public function can_delete_task( int $task_id ): bool;

	public function can_view_request( int $request_id ): bool;

	public function can_edit_request( int $request_id ): bool;

	public function can_delete_request( int $request_id ): bool;

	public function can_view_approval( int $approval_id ): bool;

	public function can_edit_approval( int $approval_id ): bool;

	public function can_view_risk_issue( int $risk_issue_id ): bool;

	public function can_edit_risk_issue( int $risk_issue_id ): bool;

	public function can_delete_risk_issue( int $risk_issue_id ): bool;

	public function can_view_milestone( int $milestone_id ): bool;

	public function can_delete_milestone( int $milestone_id ): bool;

	public function can_view_context( string $object_type, int $object_id ): bool;

	public function can_post_update_on_context( string $object_type, int $object_id ): bool;

	public function can_attach_files_to_context( string $object_type, int $object_id ): bool;

	public function can_manage_checklists_on_context( string $object_type, int $object_id ): bool;

	public function can_toggle_checklists_on_context( string $object_type, int $object_id ): bool;

	public function can_collaborate_on_context( string $object_type, int $object_id ): bool;

	/**
	 * @return array{0:string,1:array<int,mixed>}
	 */
	public function project_access_where( string $project_column ): array;

	/**
	 * @return array{0:string,1:array<int,mixed>}
	 */
	public function task_access_where( string $task_column ): array;

	/**
	 * @return array{0:string,1:array<int,mixed>}
	 */
	public function request_access_where( string $request_column ): array;

	/**
	 * @return array{0:string,1:array<int,mixed>}
	 */
	public function approval_access_where( string $approval_column ): array;

	/**
	 * @return array{0:string,1:array<int,mixed>}
	 */
	public function risk_issue_access_where( string $risk_issue_column ): array;

	/**
	 * @return array{0:string,1:array<int,mixed>}
	 */
	public function context_access_where( string $type_column, string $id_column, ?string $created_by_column = null ): array;
}
