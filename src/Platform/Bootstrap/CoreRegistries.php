<?php
/**
 * Builds core platform registries.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Platform\Bootstrap;

use Coordina\Platform\Registry\AdminPageRegistry;
use Coordina\Platform\Registry\CapabilityRegistry;
use Coordina\Platform\Registry\ContextTypeRegistry;
use Coordina\Platform\Registry\MigrationRegistry;
use Coordina\Platform\Registry\RestRouteRegistry;
use Coordina\Platform\Registry\SettingsRegistry;
use Coordina\Rest\RestRegistrar;

final class CoreRegistries {
	/**
	 * Build the core admin page registry.
	 */
	public static function admin_pages(): AdminPageRegistry {
		$registry = new AdminPageRegistry();

		$pages = array(
			'coordina-dashboard' => array(
				'title'       => __( 'Dashboard', 'coordina' ),
				'menu_title'  => __( 'Dashboard', 'coordina' ),
				'capability'  => 'coordina_view_dashboard',
				'description' => __( 'See key issues, deadlines, and decisions across your work.', 'coordina' ),
				'priority'    => 'primary',
				'purpose'     => 'oversight',
			),
			'coordina-my-work' => array(
				'title'       => __( 'My Work', 'coordina' ),
				'menu_title'  => __( 'My Work', 'coordina' ),
				'capability'  => 'coordina_access',
				'description' => __( 'See what needs your attention today, what is waiting, and what needs a decision.', 'coordina' ),
				'priority'    => 'primary',
				'purpose'     => 'execution',
			),
			'coordina-projects' => array(
				'title'       => __( 'Projects', 'coordina' ),
				'menu_title'  => __( 'Projects', 'coordina' ),
				'capability'  => 'coordina_access',
				'description' => __( 'Browse projects and open a workspace to plan or review the details.', 'coordina' ),
				'priority'    => 'primary',
				'purpose'     => 'portfolio',
			),
			'coordina-requests' => array(
				'title'       => __( 'Requests', 'coordina' ),
				'menu_title'  => __( 'Requests', 'coordina' ),
				'capability'  => 'coordina_access',
				'description' => __( 'Review incoming requests, assign ownership, and decide what happens next.', 'coordina' ),
				'priority'    => 'primary',
				'purpose'     => 'execution',
			),
			'coordina-approvals' => array(
				'title'       => __( 'Approvals', 'coordina' ),
				'menu_title'  => __( 'Approvals', 'coordina' ),
				'capability'  => 'coordina_access',
				'description' => __( 'Review pending approvals and record each decision.', 'coordina' ),
				'priority'    => 'primary',
				'purpose'     => 'execution',
			),
			'coordina-tasks' => array(
				'title'       => __( 'Tasks', 'coordina' ),
				'menu_title'  => __( 'Tasks', 'coordina' ),
				'capability'  => 'coordina_manage_tasks',
				'description' => __( 'Browse tasks across projects and standalone work.', 'coordina' ),
				'priority'    => 'secondary',
				'purpose'     => 'support',
			),
			'coordina-task' => array(
				'title'       => __( 'Task Detail', 'coordina' ),
				'menu_title'  => __( 'Task Detail', 'coordina' ),
				'capability'  => 'coordina_access',
				'description' => __( 'See task details, updates, files, and edits in one place.', 'coordina' ),
				'priority'    => 'secondary',
				'purpose'     => 'execution',
				'hidden'      => true,
			),
			'coordina-milestone' => array(
				'title'       => __( 'Milestone Detail', 'coordina' ),
				'menu_title'  => __( 'Milestone Detail', 'coordina' ),
				'capability'  => 'coordina_access',
				'description' => __( 'See milestone details, updates, files, and edits in one place.', 'coordina' ),
				'priority'    => 'secondary',
				'purpose'     => 'execution',
				'hidden'      => true,
			),
			'coordina-risk-issue' => array(
				'title'       => __( 'Risk & Issue Detail', 'coordina' ),
				'menu_title'  => __( 'Risk & Issue Detail', 'coordina' ),
				'capability'  => 'coordina_access',
				'description' => __( 'See risk or issue details, updates, files, and edits in one place.', 'coordina' ),
				'priority'    => 'secondary',
				'purpose'     => 'execution',
				'hidden'      => true,
			),
			'coordina-calendar' => array(
				'title'       => __( 'Calendar', 'coordina' ),
				'menu_title'  => __( 'Calendar', 'coordina' ),
				'capability'  => 'coordina_access',
				'description' => __( 'See work by date and open the related item when you need details.', 'coordina' ),
				'priority'    => 'secondary',
				'purpose'     => 'support',
			),
			'coordina-workload' => array(
				'title'       => __( 'Workload', 'coordina' ),
				'menu_title'  => __( 'Workload', 'coordina' ),
				'capability'  => 'coordina_manage_projects',
				'description' => __( 'See who is busy and where work may need to be rebalanced.', 'coordina' ),
				'priority'    => 'secondary',
				'purpose'     => 'support',
			),
			'coordina-risks-issues' => array(
				'title'       => __( 'Risks & Issues', 'coordina' ),
				'menu_title'  => __( 'Risks & Issues', 'coordina' ),
				'capability'  => 'coordina_manage_projects',
				'description' => __( 'Review risks and issues across projects.', 'coordina' ),
				'priority'    => 'secondary',
				'purpose'     => 'support',
			),
			'coordina-files-discussion' => array(
				'title'       => __( 'Files & Discussions', 'coordina' ),
				'menu_title'  => __( 'Files & Discussions', 'coordina' ),
				'capability'  => 'coordina_access',
				'description' => __( 'Browse recent files and updates, then open the related work item when needed.', 'coordina' ),
				'priority'    => 'secondary',
				'purpose'     => 'support',
			),
			'coordina-settings' => array(
				'title'       => __( 'Settings', 'coordina' ),
				'menu_title'  => __( 'Settings', 'coordina' ),
				'capability'  => 'coordina_manage_settings',
				'description' => __( 'Manage defaults, access, dropdowns, and other plugin settings.', 'coordina' ),
				'priority'    => 'secondary',
				'purpose'     => 'admin',
			),
		);

		foreach ( $pages as $slug => $page ) {
			$registry->add( $slug, $page );
		}

		return $registry;
	}

	/**
	 * Build the core REST route registry.
	 */
	public static function rest_routes(): RestRouteRegistry {
		$registry = new RestRouteRegistry();
		$route_groups = array(
			'register_status_routes',
			'register_admin_shell_routes',
			'register_overview_routes',
			'register_collection_resource_routes',
			'register_checklist_routes',
			'register_collaboration_routes',
			'register_project_workspace_routes',
			'register_request_conversion_routes',
			'register_my_work_routes',
			'register_workload_routes',
			'register_notification_routes',
			'register_settings_routes',
			'register_saved_view_routes',
		);

		foreach ( $route_groups as $method ) {
			$registry->add(
				static function ( RestRegistrar $registrar ) use ( $method ): void {
					$registrar->{$method}();
				}
			);
		}

		return $registry;
	}

	/**
	 * Build the core settings registry.
	 */
	public static function settings(): SettingsRegistry {
		$registry = new SettingsRegistry();

		$registry->add_defaults(
			array(
				'general'      => array(
					'default_landing_page' => 'coordina-my-work',
					'date_display'         => 'site',
					'workspace_default_tab' => 'overview',
					'task_group_label'      => 'stage',
					'activity_page_size'    => 10,
					'page_descriptions_enabled'    => true,
					'section_descriptions_enabled' => true,
					'my_work_card_guidance_enabled' => true,
					'my_work_card_actions_enabled'  => true,
				),
				'appearance'   => array(
					'color_source'  => 'custom',
					'primary_color' => 'cobalt',
					'accent_color'  => 'amber',
					'primary_custom_color' => '',
					'accent_custom_color'  => '',
					'theme_mode'    => 'auto',
					'saved_themes'  => array(),
				),
				'dropdowns'    => array(
					'statuses'                      => array(
						'projects'    => array( 'draft', 'planned', 'active', 'on-hold', 'at-risk', 'blocked', 'completed', 'cancelled', 'archived' ),
						'tasks'       => array( 'new', 'to-do', 'in-progress', 'waiting', 'blocked', 'in-review', 'done', 'cancelled' ),
						'requests'    => array( 'submitted', 'under-review', 'awaiting-info', 'approved', 'rejected', 'converted', 'closed' ),
						'approvals'   => array( 'pending', 'approved', 'rejected', 'cancelled' ),
						'risksIssues' => array( 'identified', 'monitoring', 'mitigation-in-progress', 'escalated', 'resolved', 'closed' ),
						'milestones'  => array( 'planned', 'in-progress', 'at-risk', 'completed', 'skipped' ),
					),
					'priorities'                    => array( 'low', 'normal', 'high', 'urgent' ),
					'health'                        => array( 'neutral', 'good', 'at-risk', 'blocked' ),
					'severities'                    => array( 'low', 'medium', 'high', 'critical' ),
					'impacts'                       => array( 'low', 'medium', 'high', 'critical' ),
					'likelihoods'                   => array( 'low', 'medium', 'high', 'critical' ),
					'visibilityLevels'              => array( 'team', 'private', 'public' ),
					'projectNotificationPolicies'   => array( 'default', 'important-only', 'all-updates', 'muted' ),
					'requestTypes'                  => array( 'general', 'project', 'task', 'support' ),
					'projectTypes'                  => array( 'operational', 'delivery', 'internal', 'client' ),
					'fileCategories'                => array( 'brief', 'design', 'contract', 'report', 'other' ),
					'updateTypes'                   => array( 'status', 'decision', 'blocker', 'note' ),
				),
				'access'       => array(
					'project_access_default'         => 'team',
					'portal_access_default'          => 'requesters',
					'project_workspace_visibility'   => 'members-and-assignees',
					'project_list_visibility'        => 'all-accessible-projects',
					'project_task_visibility'        => 'all-tasks-in-accessible-projects',
					'task_edit_policy'              => 'assignee-only',
					'file_attachment_rules'         => array(
						'project'    => 'project-leads-only',
						'task'       => 'assignee-and-project-leads',
						'milestone'  => 'owner-and-project-leads',
						'risk_issue' => 'owner-and-project-leads',
						'request'    => 'request-participants',
					),
					'checklist_manage_rules'       => array(
						'project'    => 'project-leads-only',
						'task'       => 'project-leads-only',
						'milestone'  => 'project-leads-only',
						'risk_issue' => 'project-leads-only',
					),
					'checklist_toggle_rules'       => array(
						'project'    => 'project-leads-only',
						'task'       => 'assignee-and-project-leads',
						'milestone'  => 'owner-and-project-leads',
						'risk_issue' => 'owner-and-project-leads',
					),
					'non_admin_navigation_scope'    => 'dashboard-my-work-projects',
				),
				'workflows'    => array(
					'request_conversion_default' => 'task',
					'allow_direct_closeout'      => false,
					'archive_completed_only'     => true,
					'approval_required_default'  => false,
				),
				'notifications' => array(
					'assignment'       => true,
					'mention'          => true,
					'approval'         => true,
					'due_date'         => true,
					'overdue'          => true,
					'project_update'   => true,
					'milestone_update' => true,
					'digest'           => false,
				),
				'portal'       => array(
					'allowed_request_types' => array( 'general', 'project', 'task', 'support' ),
					'uploads_enabled'       => true,
					'requester_visibility'  => 'own-requests',
				),
				'data'         => array(
					'activity_retention_days'     => 365,
					'notification_retention_days' => 180,
					'export_enabled'              => true,
				),
				'automation'   => array(
					'enabled'              => false,
					'status_sync_enabled'  => false,
					'overdue_alerts'       => true,
				),
			)
		);

		$registry->add_choices( 'general.default_landing_page', array( 'coordina-my-work', 'coordina-dashboard', 'coordina-projects' ) );
		$registry->add_choices( 'general.date_display', array( 'site', 'relative', 'absolute' ) );
		$registry->add_choices( 'general.workspace_default_tab', array( 'overview', 'work', 'milestones', 'risks-issues', 'approvals', 'files', 'discussion', 'activity' ) );
		$registry->add_choices( 'general.task_group_label', array( 'stage', 'phase', 'bucket' ) );
		$registry->add_choices( 'appearance.color_source', array( 'custom', 'wordpress' ) );
		$registry->add_choices( 'appearance.primary_color', array( 'cobalt', 'spruce', 'berry', 'terracotta', 'indigo', 'custom' ) );
		$registry->add_choices( 'appearance.accent_color', array( 'sky', 'mint', 'amber', 'rose', 'lilac', 'custom' ) );
		$registry->add_choices( 'appearance.theme_mode', array( 'auto', 'light', 'dark' ) );
		$registry->add_choices( 'access.portal_access_default', array( 'disabled', 'requesters', 'logged-in-users' ) );
		$registry->add_choices( 'access.project_workspace_visibility', array( 'members-only', 'members-and-assignees', 'all-coordina-users' ) );
		$registry->add_choices( 'access.project_list_visibility', array( 'assigned-projects-only', 'all-accessible-projects', 'all-projects' ) );
		$registry->add_choices( 'access.project_task_visibility', array( 'assigned-tasks-only', 'all-tasks-in-accessible-projects' ) );
		$registry->add_choices( 'access.task_edit_policy', array( 'assignee-only', 'assignee-or-reporter', 'all-project-members' ) );
		$registry->add_choices( 'access.non_admin_navigation_scope', array( 'dashboard-my-work-only', 'dashboard-my-work-projects', 'dashboard-my-work-projects-tasks' ) );
		$registry->add_choices( 'access.file_attachment_rules.project', array( 'project-leads-only', 'project-members' ) );
		$registry->add_choices( 'access.file_attachment_rules.task', array( 'assignee-and-project-leads', 'task-participants-and-project-leads', 'project-members' ) );
		$registry->add_choices( 'access.file_attachment_rules.milestone', array( 'owner-and-project-leads', 'project-members' ) );
		$registry->add_choices( 'access.file_attachment_rules.risk_issue', array( 'owner-and-project-leads', 'project-members' ) );
		$registry->add_choices( 'access.file_attachment_rules.request', array( 'request-participants', 'triage-only' ) );
		$registry->add_choices( 'access.checklist_manage_rules.project', array( 'project-leads-only', 'project-members' ) );
		$registry->add_choices( 'access.checklist_manage_rules.task', array( 'project-leads-only', 'assignee-and-project-leads', 'task-participants-and-project-leads', 'project-members' ) );
		$registry->add_choices( 'access.checklist_manage_rules.milestone', array( 'project-leads-only', 'owner-and-project-leads', 'project-members' ) );
		$registry->add_choices( 'access.checklist_manage_rules.risk_issue', array( 'project-leads-only', 'owner-and-project-leads', 'project-members' ) );
		$registry->add_choices( 'access.checklist_toggle_rules.project', array( 'project-leads-only', 'project-members' ) );
		$registry->add_choices( 'access.checklist_toggle_rules.task', array( 'project-leads-only', 'assignee-and-project-leads', 'task-participants-and-project-leads', 'project-members' ) );
		$registry->add_choices( 'access.checklist_toggle_rules.milestone', array( 'project-leads-only', 'owner-and-project-leads', 'project-members' ) );
		$registry->add_choices( 'access.checklist_toggle_rules.risk_issue', array( 'project-leads-only', 'owner-and-project-leads', 'project-members' ) );
		$registry->add_choices( 'workflows.request_conversion_default', array( 'task', 'project' ) );
		$registry->add_choices( 'portal.requester_visibility', array( 'own-requests', 'project-requests', 'none' ) );

		return $registry;
	}

	/**
	 * Build the core capability registry.
	 */
	public static function capabilities(): CapabilityRegistry {
		$registry = new CapabilityRegistry();

		$registry->add_role(
			'administrator',
			__( 'Administrator', 'coordina' ),
			array(
				'read',
				'upload_files',
				'coordina_access',
				'coordina_manage_settings',
				'coordina_view_reports',
				'coordina_manage_projects',
				'coordina_manage_tasks',
				'coordina_manage_requests',
				'coordina_manage_approvals',
				'coordina_view_dashboard',
				'coordina_access_portal',
			)
		);

		$registry->add_role(
			'coordina_project_manager',
			__( 'Coordina Project Manager', 'coordina' ),
			array(
				'read',
				'upload_files',
				'coordina_access',
				'coordina_view_dashboard',
				'coordina_manage_projects',
				'coordina_manage_tasks',
				'coordina_manage_requests',
				'coordina_manage_approvals',
				'coordina_view_reports',
				'coordina_access_portal',
			)
		);

		$registry->add_role(
			'coordina_team_member',
			__( 'Coordina Team Member', 'coordina' ),
			array(
				'read',
				'upload_files',
				'coordina_access',
				'coordina_access_portal',
			)
		);

		$registry->add_role(
			'coordina_executive_viewer',
			__( 'Coordina Executive Viewer', 'coordina' ),
			array(
				'read',
				'upload_files',
				'coordina_access',
				'coordina_view_dashboard',
				'coordina_view_reports',
				'coordina_access_portal',
			)
		);

		$registry->add_role(
			'coordina_portal_user',
			__( 'Coordina Portal User', 'coordina' ),
			array(
				'read',
				'upload_files',
				'coordina_access_portal',
			)
		);

		return $registry;
	}

	/**
	 * Build the core migration registry.
	 */
	public static function migrations(): MigrationRegistry {
		$registry = new MigrationRegistry();

		foreach ( self::table_definitions() as $sql ) {
			$registry->add_table( $sql );
		}

		$registry->add_migration( 'task_checklists_0_2_5', array( __CLASS__, 'migrate_task_checklists' ) );
		$registry->add_migration( 'grouped_checklists_0_2_6', array( __CLASS__, 'migrate_grouped_checklists' ) );
		$registry->add_migration( 'project_sponsor_0_2_8', array( __CLASS__, 'migrate_project_sponsor_field' ) );

		return $registry;
	}

	/**
	 * Build the core context-type registry.
	 */
	public static function context_types(): ContextTypeRegistry {
		$registry = new ContextTypeRegistry();

		$registry->add(
			'project',
			array(
				'label'           => __( 'Project', 'coordina' ),
				'approval_object' => true,
				'checklist_context' => true,
				'table'           => 'projects',
				'title_column'    => 'title',
				'query_arg'       => 'project_id',
				'admin_page'      => 'coordina-projects',
				'project_lookup'  => 'self',
				'route'           => array(
					'page'       => 'coordina-projects',
					'param'      => 'project_id',
					'project_tab'=> 'overview',
				),
			)
		);

		$registry->add(
			'task',
			array(
				'label'                   => __( 'Task', 'coordina' ),
				'approval_object'         => true,
				'checklist_context'       => true,
				'table'                   => 'tasks',
				'title_column'            => 'title',
				'query_arg'               => 'task_id',
				'admin_page'              => 'coordina-task',
				'detail_context'          => true,
				'access_method'           => 'can_view_task',
				'project_id_column'       => 'project_id',
				'project_activity_member' => true,
				'route'                   => array(
					'page'                  => 'coordina-task',
					'param'                 => 'task_id',
					'include_project_id'    => true,
					'project_tab_when_project' => 'work',
				),
			)
		);

		$registry->add(
			'request',
			array(
				'label'           => __( 'Request', 'coordina' ),
				'approval_object' => true,
				'table'           => 'requests',
				'title_column'    => 'title',
				'admin_page'      => 'coordina-requests',
				'route'           => array(
					'page' => 'coordina-requests',
				),
			)
		);

		$registry->add(
			'risk',
			array(
				'label'                   => __( 'Risk', 'coordina' ),
				'approval_object'         => true,
				'checklist_context'       => true,
				'risk_object'             => true,
				'table'                   => 'risks_issues',
				'type_column'             => 'object_type',
				'type_value'              => 'risk',
				'title_column'            => 'title',
				'query_arg'               => 'risk_issue_id',
				'admin_page'              => 'coordina-risk-issue',
				'detail_context'          => true,
				'access_method'           => 'can_view_risk_issue',
				'project_id_column'       => 'project_id',
				'project_activity_member' => true,
				'route'                   => array(
					'page'                  => 'coordina-risk-issue',
					'param'                 => 'risk_issue_id',
					'include_project_id'    => true,
					'project_tab_when_project' => 'risks-issues',
				),
			)
		);

		$registry->add(
			'issue',
			array(
				'label'                   => __( 'Issue', 'coordina' ),
				'approval_object'         => true,
				'checklist_context'       => true,
				'risk_object'             => true,
				'table'                   => 'risks_issues',
				'type_column'             => 'object_type',
				'type_value'              => 'issue',
				'title_column'            => 'title',
				'query_arg'               => 'risk_issue_id',
				'admin_page'              => 'coordina-risk-issue',
				'detail_context'          => true,
				'access_method'           => 'can_view_risk_issue',
				'project_id_column'       => 'project_id',
				'project_activity_member' => true,
				'route'                   => array(
					'page'                  => 'coordina-risk-issue',
					'param'                 => 'risk_issue_id',
					'include_project_id'    => true,
					'project_tab_when_project' => 'risks-issues',
				),
			)
		);

		$registry->add(
			'milestone',
			array(
				'label'                   => __( 'Milestone', 'coordina' ),
				'approval_object'         => true,
				'checklist_context'       => true,
				'table'                   => 'milestones',
				'title_column'            => 'title',
				'query_arg'               => 'milestone_id',
				'admin_page'              => 'coordina-milestone',
				'detail_context'          => true,
				'access_method'           => 'can_view_milestone',
				'project_id_column'       => 'project_id',
				'project_activity_member' => true,
				'route'                   => array(
					'page'                  => 'coordina-milestone',
					'param'                 => 'milestone_id',
					'include_project_id'    => true,
					'project_tab_when_project' => 'milestones',
				),
			)
		);

		$registry->add(
			'approval',
			array(
				'label'          => __( 'Approval', 'coordina' ),
				'table'          => 'approvals',
				'admin_page'     => 'coordina-approvals',
				'label_callback' => static function ( int $object_id ): string {
					return sprintf( __( 'Approval #%d', 'coordina' ), $object_id );
				},
				'route_callback' => static function ( int $object_id, int $project_id ): array {
					return array(
						'page'       => $project_id > 0 ? 'coordina-projects' : 'coordina-approvals',
						'project_id' => $project_id,
						'project_tab'=> $project_id > 0 ? 'approvals' : '',
					);
				},
			)
		);

		return $registry;
	}

	/**
	 * Get core dbDelta table definitions.
	 *
	 * @return array<int, string>
	 */
	private static function table_definitions(): array {
		global $wpdb;

		$prefix          = $wpdb->prefix;
		$charset_collate = $wpdb->get_charset_collate();

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
			) {$charset_collate};",
		);
	}

	/**
	 * Migrate legacy task checklist rows into the generic checklist table.
	 *
	 * @param string $prefix Table prefix.
	 */
	public static function migrate_task_checklists( string $prefix ): void {
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

		update_option( $migration_key, '0.2.8', false );
	}

	/**
	 * Create grouped checklist headers for existing flat checklist rows.
	 *
	 * @param string $prefix Table prefix.
	 */
	public static function migrate_grouped_checklists( string $prefix ): void {
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

		update_option( $migration_key, '0.2.8', false );
	}

	/**
	 * Add the project sponsor field for existing installs.
	 *
	 * @param string $prefix Table prefix.
	 */
	public static function migrate_project_sponsor_field( string $prefix ): void {
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

		update_option( $migration_key, '0.2.8', false );
	}
}
