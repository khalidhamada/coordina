<?php
/**
 * Global plugin settings repository.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Persistence;

use RuntimeException;

final class SettingsRepository {
	private const OPTION_KEY = 'coordina_settings';

	/**
	 * Fetch settings with defaults applied.
	 *
	 * @return array<string, mixed>
	 */
	public function get(): array {
		$stored = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return $this->sanitize_settings( $this->merge_defaults( $stored ) );
	}

	/**
	 * Update global settings.
	 *
	 * @param array<string, mixed> $data Settings payload.
	 * @return array<string, mixed>
	 */
	public function update( array $data ): array {
		if ( ! current_user_can( 'coordina_manage_settings' ) ) {
			throw new RuntimeException( __( 'You are not allowed to update Coordina settings.', 'coordina' ) );
		}

		$settings = $this->merge_config( $this->get(), $data );
		$settings = $this->merge_defaults( $settings );
		$settings = $this->sanitize_settings( $settings );

		update_option( self::OPTION_KEY, $settings, false );

		return $settings;
	}

	/**
	 * Get dropdown values for the admin shell.
	 *
	 * @return array<string, mixed>
	 */
	public function get_dropdowns(): array {
		return $this->get()['dropdowns'];
	}

	/**
	 * Build default settings.
	 *
	 * @return array<string, mixed>
	 */
	private function defaults(): array {
		return array(
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
		);
	}

	/**
	 * Merge stored settings with defaults.
	 *
	 * @param array<string, mixed> $settings Stored settings.
	 * @return array<string, mixed>
	 */
	private function merge_defaults( array $settings ): array {
		return $this->merge_config( $this->defaults(), $settings );
	}

	/**
	 * Merge associative config while replacing list values.
	 *
	 * @param array<string, mixed> $defaults Default config.
	 * @param array<string, mixed> $settings Stored config.
	 * @return array<string, mixed>
	 */
	private function merge_config( array $defaults, array $settings ): array {
		foreach ( $settings as $key => $value ) {
			if ( isset( $defaults[ $key ] ) && is_array( $defaults[ $key ] ) && is_array( $value ) && ! $this->is_list_array( $defaults[ $key ] ) ) {
				$defaults[ $key ] = $this->merge_config( $defaults[ $key ], $value );
			} else {
				$defaults[ $key ] = $value;
			}
		}

		return $defaults;
	}

	/**
	 * Determine whether an array is a list under PHP 7.4.
	 *
	 * @param array<mixed> $value Array value.
	 */
	private function is_list_array( array $value ): bool {
		if ( empty( $value ) ) {
			return true;
		}

		return array_keys( $value ) === range( 0, count( $value ) - 1 );
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array<string, mixed>
	 */
	private function sanitize_settings( array $settings ): array {
		$defaults = $this->defaults();

		$settings['general']['default_landing_page'] = $this->choice( $settings['general']['default_landing_page'] ?? '', array( 'coordina-my-work', 'coordina-dashboard', 'coordina-projects' ), $defaults['general']['default_landing_page'] );
		$settings['general']['date_display'] = $this->choice( $settings['general']['date_display'] ?? '', array( 'site', 'relative', 'absolute' ), $defaults['general']['date_display'] );
		$settings['general']['workspace_default_tab'] = $this->choice( $settings['general']['workspace_default_tab'] ?? '', array( 'overview', 'work', 'milestones', 'risks-issues', 'approvals', 'files', 'discussion', 'activity' ), $defaults['general']['workspace_default_tab'] );
		$settings['general']['task_group_label'] = $this->choice( $settings['general']['task_group_label'] ?? '', array( 'stage', 'phase', 'bucket' ), $defaults['general']['task_group_label'] );
		$settings['general']['activity_page_size'] = max( 5, min( 50, (int) ( $settings['general']['activity_page_size'] ?? $defaults['general']['activity_page_size'] ) ) );
		$settings['general']['page_descriptions_enabled'] = (bool) ( $settings['general']['page_descriptions_enabled'] ?? $defaults['general']['page_descriptions_enabled'] );
		$settings['general']['section_descriptions_enabled'] = (bool) ( $settings['general']['section_descriptions_enabled'] ?? $defaults['general']['section_descriptions_enabled'] );
		$settings['general']['my_work_card_guidance_enabled'] = (bool) ( $settings['general']['my_work_card_guidance_enabled'] ?? $defaults['general']['my_work_card_guidance_enabled'] );
		$settings['general']['my_work_card_actions_enabled'] = (bool) ( $settings['general']['my_work_card_actions_enabled'] ?? $defaults['general']['my_work_card_actions_enabled'] );

		foreach ( $defaults['dropdowns']['statuses'] as $key => $fallback ) {
			$settings['dropdowns']['statuses'][ $key ] = $this->token_list( $settings['dropdowns']['statuses'][ $key ] ?? array(), $fallback );
		}

		foreach ( array( 'priorities', 'health', 'severities', 'impacts', 'likelihoods', 'visibilityLevels', 'projectNotificationPolicies', 'requestTypes', 'projectTypes', 'fileCategories', 'updateTypes' ) as $key ) {
			$settings['dropdowns'][ $key ] = $this->token_list( $settings['dropdowns'][ $key ] ?? array(), $defaults['dropdowns'][ $key ] );
		}

		$settings['access']['project_access_default'] = $this->choice( $settings['access']['project_access_default'] ?? '', $settings['dropdowns']['visibilityLevels'], $defaults['access']['project_access_default'] );
		$settings['access']['portal_access_default'] = $this->choice( $settings['access']['portal_access_default'] ?? '', array( 'disabled', 'requesters', 'logged-in-users' ), $defaults['access']['portal_access_default'] );
		$settings['access']['project_workspace_visibility'] = $this->choice( $settings['access']['project_workspace_visibility'] ?? '', array( 'members-only', 'members-and-assignees', 'all-coordina-users' ), $defaults['access']['project_workspace_visibility'] );
		$settings['access']['project_list_visibility'] = $this->choice( $settings['access']['project_list_visibility'] ?? '', array( 'assigned-projects-only', 'all-accessible-projects', 'all-projects' ), $defaults['access']['project_list_visibility'] );
		$settings['access']['project_task_visibility'] = $this->choice( $settings['access']['project_task_visibility'] ?? '', array( 'assigned-tasks-only', 'all-tasks-in-accessible-projects' ), $defaults['access']['project_task_visibility'] );
		$settings['access']['task_edit_policy'] = $this->choice( $settings['access']['task_edit_policy'] ?? '', array( 'assignee-only', 'assignee-or-reporter', 'all-project-members' ), $defaults['access']['task_edit_policy'] );
		$settings['access']['non_admin_navigation_scope'] = $this->choice( $settings['access']['non_admin_navigation_scope'] ?? '', array( 'dashboard-my-work-only', 'dashboard-my-work-projects', 'dashboard-my-work-projects-tasks' ), $defaults['access']['non_admin_navigation_scope'] );
		$settings['access']['file_attachment_rules'] = is_array( $settings['access']['file_attachment_rules'] ?? null ) ? $settings['access']['file_attachment_rules'] : array();
		$settings['access']['file_attachment_rules']['project'] = $this->choice( $settings['access']['file_attachment_rules']['project'] ?? '', array( 'project-leads-only', 'project-members' ), $defaults['access']['file_attachment_rules']['project'] );
		$settings['access']['file_attachment_rules']['task'] = $this->choice( $settings['access']['file_attachment_rules']['task'] ?? '', array( 'assignee-and-project-leads', 'task-participants-and-project-leads', 'project-members' ), $defaults['access']['file_attachment_rules']['task'] );
		$settings['access']['file_attachment_rules']['milestone'] = $this->choice( $settings['access']['file_attachment_rules']['milestone'] ?? '', array( 'owner-and-project-leads', 'project-members' ), $defaults['access']['file_attachment_rules']['milestone'] );
		$settings['access']['file_attachment_rules']['risk_issue'] = $this->choice( $settings['access']['file_attachment_rules']['risk_issue'] ?? '', array( 'owner-and-project-leads', 'project-members' ), $defaults['access']['file_attachment_rules']['risk_issue'] );
		$settings['access']['file_attachment_rules']['request'] = $this->choice( $settings['access']['file_attachment_rules']['request'] ?? '', array( 'request-participants', 'triage-only' ), $defaults['access']['file_attachment_rules']['request'] );
		$settings['access']['checklist_manage_rules'] = is_array( $settings['access']['checklist_manage_rules'] ?? null ) ? $settings['access']['checklist_manage_rules'] : array();
		$settings['access']['checklist_manage_rules']['project'] = $this->choice( $settings['access']['checklist_manage_rules']['project'] ?? '', array( 'project-leads-only', 'project-members' ), $defaults['access']['checklist_manage_rules']['project'] );
		$settings['access']['checklist_manage_rules']['task'] = $this->choice( $settings['access']['checklist_manage_rules']['task'] ?? '', array( 'project-leads-only', 'assignee-and-project-leads', 'task-participants-and-project-leads', 'project-members' ), $defaults['access']['checklist_manage_rules']['task'] );
		$settings['access']['checklist_manage_rules']['milestone'] = $this->choice( $settings['access']['checklist_manage_rules']['milestone'] ?? '', array( 'project-leads-only', 'owner-and-project-leads', 'project-members' ), $defaults['access']['checklist_manage_rules']['milestone'] );
		$settings['access']['checklist_manage_rules']['risk_issue'] = $this->choice( $settings['access']['checklist_manage_rules']['risk_issue'] ?? '', array( 'project-leads-only', 'owner-and-project-leads', 'project-members' ), $defaults['access']['checklist_manage_rules']['risk_issue'] );
		$settings['access']['checklist_toggle_rules'] = is_array( $settings['access']['checklist_toggle_rules'] ?? null ) ? $settings['access']['checklist_toggle_rules'] : array();
		$settings['access']['checklist_toggle_rules']['project'] = $this->choice( $settings['access']['checklist_toggle_rules']['project'] ?? '', array( 'project-leads-only', 'project-members' ), $defaults['access']['checklist_toggle_rules']['project'] );
		$settings['access']['checklist_toggle_rules']['task'] = $this->choice( $settings['access']['checklist_toggle_rules']['task'] ?? '', array( 'project-leads-only', 'assignee-and-project-leads', 'task-participants-and-project-leads', 'project-members' ), $defaults['access']['checklist_toggle_rules']['task'] );
		$settings['access']['checklist_toggle_rules']['milestone'] = $this->choice( $settings['access']['checklist_toggle_rules']['milestone'] ?? '', array( 'project-leads-only', 'owner-and-project-leads', 'project-members' ), $defaults['access']['checklist_toggle_rules']['milestone'] );
		$settings['access']['checklist_toggle_rules']['risk_issue'] = $this->choice( $settings['access']['checklist_toggle_rules']['risk_issue'] ?? '', array( 'project-leads-only', 'owner-and-project-leads', 'project-members' ), $defaults['access']['checklist_toggle_rules']['risk_issue'] );
		$settings['workflows']['request_conversion_default'] = $this->choice( $settings['workflows']['request_conversion_default'] ?? '', array( 'task', 'project' ), $defaults['workflows']['request_conversion_default'] );
		$settings['portal']['requester_visibility'] = $this->choice( $settings['portal']['requester_visibility'] ?? '', array( 'own-requests', 'project-requests', 'none' ), $defaults['portal']['requester_visibility'] );
		$settings['portal']['allowed_request_types'] = $this->token_list( $settings['portal']['allowed_request_types'] ?? array(), $defaults['portal']['allowed_request_types'] );

		foreach ( array( 'allow_direct_closeout', 'archive_completed_only', 'approval_required_default' ) as $key ) {
			$settings['workflows'][ $key ] = (bool) ( $settings['workflows'][ $key ] ?? false );
		}

		foreach ( array( 'assignment', 'mention', 'approval', 'due_date', 'overdue', 'project_update', 'milestone_update', 'digest' ) as $key ) {
			$settings['notifications'][ $key ] = (bool) ( $settings['notifications'][ $key ] ?? false );
		}

		$settings['portal']['uploads_enabled'] = (bool) ( $settings['portal']['uploads_enabled'] ?? false );
		$settings['data']['export_enabled'] = (bool) ( $settings['data']['export_enabled'] ?? false );
		$settings['automation']['enabled'] = (bool) ( $settings['automation']['enabled'] ?? false );
		$settings['automation']['status_sync_enabled'] = (bool) ( $settings['automation']['status_sync_enabled'] ?? false );
		$settings['automation']['overdue_alerts'] = (bool) ( $settings['automation']['overdue_alerts'] ?? false );
		$settings['data']['activity_retention_days'] = max( 30, min( 3650, (int) ( $settings['data']['activity_retention_days'] ?? 365 ) ) );
		$settings['data']['notification_retention_days'] = max( 30, min( 3650, (int) ( $settings['data']['notification_retention_days'] ?? 180 ) ) );

		return $settings;
	}

	/**
	 * Sanitize a token list.
	 *
	 * @param mixed    $value Raw list.
	 * @param string[] $fallback Fallback list.
	 * @return string[]
	 */
	private function token_list( $value, array $fallback ): array {
		if ( is_string( $value ) ) {
			$value = preg_split( '/[\r\n,]+/', $value );
		}

		if ( ! is_array( $value ) ) {
			return $fallback;
		}

		$tokens = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( $item ): string {
							return sanitize_key( (string) $item );
						},
						$value
					)
				)
			)
		);

		return empty( $tokens ) ? $fallback : $tokens;
	}

	/**
	 * Sanitize a controlled choice.
	 *
	 * @param mixed    $value Raw value.
	 * @param string[] $allowed Allowed values.
	 * @param string   $fallback Fallback value.
	 */
	private function choice( $value, array $allowed, string $fallback ): string {
		$value = sanitize_key( (string) $value );
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}
}
