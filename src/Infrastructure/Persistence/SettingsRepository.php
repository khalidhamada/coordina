<?php
/**
 * Global plugin settings repository.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Persistence;

use Coordina\Platform\Bootstrap\CoreRegistries;
use Coordina\Platform\Contracts\SettingsStoreInterface;
use Coordina\Platform\Registry\SettingsRegistry;
use RuntimeException;

final class SettingsRepository implements SettingsStoreInterface {
	private const OPTION_KEY = 'coordina_settings';

	/**
	 * Settings registry.
	 *
	 * @var SettingsRegistry
	 */
	private $registry;

	/**
	 * Constructor.
	 *
	 * @param SettingsRegistry|null $registry Settings registry.
	 */
	public function __construct( ?SettingsRegistry $registry = null ) {
		$this->registry = $registry ?: CoreRegistries::settings();
	}

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
	 * Get all registered settings choice lists.
	 *
	 * @return array<string, array<int, string>>
	 */
	public function choice_lists(): array {
		return $this->registry->all_choices();
	}

	/**
	 * Build default settings.
	 *
	 * @return array<string, mixed>
	 */
	private function defaults(): array {
		return $this->registry->defaults();
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

		$settings['general']['default_landing_page'] = $this->choice( $settings['general']['default_landing_page'] ?? '', $this->choices( 'general.default_landing_page', array( 'coordina-my-work', 'coordina-dashboard', 'coordina-projects' ) ), $defaults['general']['default_landing_page'] );
		$settings['general']['date_display'] = $this->choice( $settings['general']['date_display'] ?? '', $this->choices( 'general.date_display', array( 'site', 'relative', 'absolute' ) ), $defaults['general']['date_display'] );
		$settings['general']['workspace_default_tab'] = $this->choice( $settings['general']['workspace_default_tab'] ?? '', $this->choices( 'general.workspace_default_tab', array( 'overview', 'work', 'milestones', 'risks-issues', 'approvals', 'files', 'discussion', 'activity' ) ), $defaults['general']['workspace_default_tab'] );
		$settings['general']['task_group_label'] = $this->choice( $settings['general']['task_group_label'] ?? '', $this->choices( 'general.task_group_label', array( 'stage', 'phase', 'bucket' ) ), $defaults['general']['task_group_label'] );
		$settings['general']['activity_page_size'] = max( 5, min( 50, (int) ( $settings['general']['activity_page_size'] ?? $defaults['general']['activity_page_size'] ) ) );
		$settings['general']['page_descriptions_enabled'] = (bool) ( $settings['general']['page_descriptions_enabled'] ?? $defaults['general']['page_descriptions_enabled'] );
		$settings['general']['section_descriptions_enabled'] = (bool) ( $settings['general']['section_descriptions_enabled'] ?? $defaults['general']['section_descriptions_enabled'] );
		$settings['general']['my_work_card_guidance_enabled'] = (bool) ( $settings['general']['my_work_card_guidance_enabled'] ?? $defaults['general']['my_work_card_guidance_enabled'] );
		$settings['general']['my_work_card_actions_enabled'] = (bool) ( $settings['general']['my_work_card_actions_enabled'] ?? $defaults['general']['my_work_card_actions_enabled'] );
		$legacy_palette = (string) ( $settings['appearance']['theme_palette'] ?? '' );
		if ( '' === (string) ( $settings['appearance']['color_source'] ?? '' ) || '' === (string) ( $settings['appearance']['primary_color'] ?? '' ) ) {
			$legacy_map = array(
				'slate'     => array(
					'color_source'  => 'custom',
					'primary_color' => 'cobalt',
					'accent_color'  => 'amber',
				),
				'forest'    => array(
					'color_source'  => 'custom',
					'primary_color' => 'spruce',
					'accent_color'  => 'mint',
				),
				'ember'     => array(
					'color_source'  => 'custom',
					'primary_color' => 'terracotta',
					'accent_color'  => 'amber',
				),
				'wordpress' => array(
					'color_source'  => 'wordpress',
					'primary_color' => 'cobalt',
					'accent_color'  => 'amber',
				),
			);
			if ( isset( $legacy_map[ $legacy_palette ] ) ) {
				$settings['appearance'] = array_merge( $legacy_map[ $legacy_palette ], is_array( $settings['appearance'] ?? null ) ? $settings['appearance'] : array() );
			}
		}
		$settings['appearance']['color_source'] = $this->choice( $settings['appearance']['color_source'] ?? '', $this->choices( 'appearance.color_source', array( 'custom', 'wordpress' ) ), $defaults['appearance']['color_source'] );
		$settings['appearance']['primary_color'] = $this->choice( $settings['appearance']['primary_color'] ?? '', $this->choices( 'appearance.primary_color', array( 'cobalt', 'spruce', 'berry', 'terracotta', 'indigo', 'custom' ) ), $defaults['appearance']['primary_color'] );
		$settings['appearance']['accent_color'] = $this->choice( $settings['appearance']['accent_color'] ?? '', $this->choices( 'appearance.accent_color', array( 'sky', 'mint', 'amber', 'rose', 'lilac', 'custom' ) ), $defaults['appearance']['accent_color'] );
		$settings['appearance']['primary_custom_color'] = $this->hex_color( $settings['appearance']['primary_custom_color'] ?? '' );
		$settings['appearance']['accent_custom_color'] = $this->hex_color( $settings['appearance']['accent_custom_color'] ?? '' );
		$settings['appearance']['theme_mode'] = $this->choice( $settings['appearance']['theme_mode'] ?? '', $this->choices( 'appearance.theme_mode', array( 'auto', 'light', 'dark' ) ), $defaults['appearance']['theme_mode'] );
		$settings['appearance']['saved_themes'] = $this->theme_list( $settings['appearance']['saved_themes'] ?? array() );

		foreach ( $defaults['dropdowns']['statuses'] as $key => $fallback ) {
			$settings['dropdowns']['statuses'][ $key ] = $this->token_list( $settings['dropdowns']['statuses'][ $key ] ?? array(), $fallback );
		}

		foreach ( array( 'priorities', 'health', 'severities', 'impacts', 'likelihoods', 'visibilityLevels', 'projectNotificationPolicies', 'requestTypes', 'projectTypes', 'fileCategories', 'updateTypes' ) as $key ) {
			$settings['dropdowns'][ $key ] = $this->token_list( $settings['dropdowns'][ $key ] ?? array(), $defaults['dropdowns'][ $key ] );
		}

		$settings['access']['project_access_default'] = $this->choice( $settings['access']['project_access_default'] ?? '', $settings['dropdowns']['visibilityLevels'], $defaults['access']['project_access_default'] );
		$settings['access']['portal_access_default'] = $this->choice( $settings['access']['portal_access_default'] ?? '', $this->choices( 'access.portal_access_default', array( 'disabled', 'requesters', 'logged-in-users' ) ), $defaults['access']['portal_access_default'] );
		$settings['access']['project_workspace_visibility'] = $this->choice( $settings['access']['project_workspace_visibility'] ?? '', $this->choices( 'access.project_workspace_visibility', array( 'members-only', 'members-and-assignees', 'all-coordina-users' ) ), $defaults['access']['project_workspace_visibility'] );
		$settings['access']['project_list_visibility'] = $this->choice( $settings['access']['project_list_visibility'] ?? '', $this->choices( 'access.project_list_visibility', array( 'assigned-projects-only', 'all-accessible-projects', 'all-projects' ) ), $defaults['access']['project_list_visibility'] );
		$settings['access']['project_task_visibility'] = $this->choice( $settings['access']['project_task_visibility'] ?? '', $this->choices( 'access.project_task_visibility', array( 'assigned-tasks-only', 'all-tasks-in-accessible-projects' ) ), $defaults['access']['project_task_visibility'] );
		$settings['access']['task_edit_policy'] = $this->choice( $settings['access']['task_edit_policy'] ?? '', $this->choices( 'access.task_edit_policy', array( 'assignee-only', 'assignee-or-reporter', 'all-project-members' ) ), $defaults['access']['task_edit_policy'] );
		$settings['access']['non_admin_navigation_scope'] = $this->choice( $settings['access']['non_admin_navigation_scope'] ?? '', $this->choices( 'access.non_admin_navigation_scope', array( 'dashboard-my-work-only', 'dashboard-my-work-projects', 'dashboard-my-work-projects-tasks' ) ), $defaults['access']['non_admin_navigation_scope'] );
		$settings['access']['file_attachment_rules'] = is_array( $settings['access']['file_attachment_rules'] ?? null ) ? $settings['access']['file_attachment_rules'] : array();
		$settings['access']['file_attachment_rules']['project'] = $this->choice( $settings['access']['file_attachment_rules']['project'] ?? '', $this->choices( 'access.file_attachment_rules.project', array( 'project-leads-only', 'project-members' ) ), $defaults['access']['file_attachment_rules']['project'] );
		$settings['access']['file_attachment_rules']['task'] = $this->choice( $settings['access']['file_attachment_rules']['task'] ?? '', $this->choices( 'access.file_attachment_rules.task', array( 'assignee-and-project-leads', 'task-participants-and-project-leads', 'project-members' ) ), $defaults['access']['file_attachment_rules']['task'] );
		$settings['access']['file_attachment_rules']['milestone'] = $this->choice( $settings['access']['file_attachment_rules']['milestone'] ?? '', $this->choices( 'access.file_attachment_rules.milestone', array( 'owner-and-project-leads', 'project-members' ) ), $defaults['access']['file_attachment_rules']['milestone'] );
		$settings['access']['file_attachment_rules']['risk_issue'] = $this->choice( $settings['access']['file_attachment_rules']['risk_issue'] ?? '', $this->choices( 'access.file_attachment_rules.risk_issue', array( 'owner-and-project-leads', 'project-members' ) ), $defaults['access']['file_attachment_rules']['risk_issue'] );
		$settings['access']['file_attachment_rules']['request'] = $this->choice( $settings['access']['file_attachment_rules']['request'] ?? '', $this->choices( 'access.file_attachment_rules.request', array( 'request-participants', 'triage-only' ) ), $defaults['access']['file_attachment_rules']['request'] );
		$settings['access']['checklist_manage_rules'] = is_array( $settings['access']['checklist_manage_rules'] ?? null ) ? $settings['access']['checklist_manage_rules'] : array();
		$settings['access']['checklist_manage_rules']['project'] = $this->choice( $settings['access']['checklist_manage_rules']['project'] ?? '', $this->choices( 'access.checklist_manage_rules.project', array( 'project-leads-only', 'project-members' ) ), $defaults['access']['checklist_manage_rules']['project'] );
		$settings['access']['checklist_manage_rules']['task'] = $this->choice( $settings['access']['checklist_manage_rules']['task'] ?? '', $this->choices( 'access.checklist_manage_rules.task', array( 'project-leads-only', 'assignee-and-project-leads', 'task-participants-and-project-leads', 'project-members' ) ), $defaults['access']['checklist_manage_rules']['task'] );
		$settings['access']['checklist_manage_rules']['milestone'] = $this->choice( $settings['access']['checklist_manage_rules']['milestone'] ?? '', $this->choices( 'access.checklist_manage_rules.milestone', array( 'project-leads-only', 'owner-and-project-leads', 'project-members' ) ), $defaults['access']['checklist_manage_rules']['milestone'] );
		$settings['access']['checklist_manage_rules']['risk_issue'] = $this->choice( $settings['access']['checklist_manage_rules']['risk_issue'] ?? '', $this->choices( 'access.checklist_manage_rules.risk_issue', array( 'project-leads-only', 'owner-and-project-leads', 'project-members' ) ), $defaults['access']['checklist_manage_rules']['risk_issue'] );
		$settings['access']['checklist_toggle_rules'] = is_array( $settings['access']['checklist_toggle_rules'] ?? null ) ? $settings['access']['checklist_toggle_rules'] : array();
		$settings['access']['checklist_toggle_rules']['project'] = $this->choice( $settings['access']['checklist_toggle_rules']['project'] ?? '', $this->choices( 'access.checklist_toggle_rules.project', array( 'project-leads-only', 'project-members' ) ), $defaults['access']['checklist_toggle_rules']['project'] );
		$settings['access']['checklist_toggle_rules']['task'] = $this->choice( $settings['access']['checklist_toggle_rules']['task'] ?? '', $this->choices( 'access.checklist_toggle_rules.task', array( 'project-leads-only', 'assignee-and-project-leads', 'task-participants-and-project-leads', 'project-members' ) ), $defaults['access']['checklist_toggle_rules']['task'] );
		$settings['access']['checklist_toggle_rules']['milestone'] = $this->choice( $settings['access']['checklist_toggle_rules']['milestone'] ?? '', $this->choices( 'access.checklist_toggle_rules.milestone', array( 'project-leads-only', 'owner-and-project-leads', 'project-members' ) ), $defaults['access']['checklist_toggle_rules']['milestone'] );
		$settings['access']['checklist_toggle_rules']['risk_issue'] = $this->choice( $settings['access']['checklist_toggle_rules']['risk_issue'] ?? '', $this->choices( 'access.checklist_toggle_rules.risk_issue', array( 'project-leads-only', 'owner-and-project-leads', 'project-members' ) ), $defaults['access']['checklist_toggle_rules']['risk_issue'] );
		$settings['workflows']['request_conversion_default'] = $this->choice( $settings['workflows']['request_conversion_default'] ?? '', $this->choices( 'workflows.request_conversion_default', array( 'task', 'project' ) ), $defaults['workflows']['request_conversion_default'] );
		$settings['portal']['requester_visibility'] = $this->choice( $settings['portal']['requester_visibility'] ?? '', $this->choices( 'portal.requester_visibility', array( 'own-requests', 'project-requests', 'none' ) ), $defaults['portal']['requester_visibility'] );
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

	/**
	 * Get registered choices for one settings path.
	 *
	 * @param string             $path Dot-path key.
	 * @param array<int, string> $fallback Fallback choices.
	 * @return array<int, string>
	 */
	private function choices( string $path, array $fallback = array() ): array {
		return $this->registry->choices( $path, $fallback );
	}

	/**
	 * Sanitize a hex color or return an empty string.
	 *
	 * @param mixed $value Raw value.
	 */
	private function hex_color( $value ): string {
		$color = trim( (string) $value );
		if ( '' === $color ) {
			return '';
		}

		if ( preg_match( '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color ) ) {
			return strtolower( $color );
		}

		return '';
	}

	/**
	 * Sanitize saved theme presets.
	 *
	 * @param mixed $value Raw value.
	 * @return array<int, array<string, string>>
	 */
	private function theme_list( $value ): array {
		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );
			$value   = is_array( $decoded ) ? $decoded : array();
		}

		if ( ! is_array( $value ) ) {
			return array();
		}

		$result = array();
		foreach ( $value as $index => $theme ) {
			if ( ! is_array( $theme ) ) {
				continue;
			}

			$label = sanitize_text_field( (string) ( $theme['label'] ?? '' ) );
			if ( '' === $label ) {
				continue;
			}

			$key = sanitize_key( (string) ( $theme['key'] ?? $label ) );
			if ( '' === $key ) {
				$key = 'theme_' . (string) $index;
			}

			$result[] = array(
				'key' => $key,
				'label' => mb_substr( $label, 0, 60 ),
				'color_source' => $this->choice( $theme['color_source'] ?? '', array( 'custom', 'wordpress' ), 'custom' ),
				'primary_color' => $this->choice( $theme['primary_color'] ?? '', array( 'cobalt', 'spruce', 'berry', 'terracotta', 'indigo', 'custom' ), 'cobalt' ),
				'accent_color' => $this->choice( $theme['accent_color'] ?? '', array( 'sky', 'mint', 'amber', 'rose', 'lilac', 'custom' ), 'amber' ),
				'primary_custom_color' => $this->hex_color( $theme['primary_custom_color'] ?? '' ),
				'accent_custom_color' => $this->hex_color( $theme['accent_custom_color'] ?? '' ),
				'theme_mode' => $this->choice( $theme['theme_mode'] ?? '', array( 'auto', 'light', 'dark' ), 'auto' ),
			);
		}

		return array_values( array_slice( $result, 0, 24 ) );
	}
}
