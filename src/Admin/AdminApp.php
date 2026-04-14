<?php
/**
 * Admin app shell.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Admin;

use Coordina\Infrastructure\Access\AccessPolicy;
use Coordina\Infrastructure\Persistence\SettingsRepository;
use Coordina\Support\Formatting;

final class AdminApp {
	/**
	 * Modules map.
	 *
	 * @var array<string, array<string, string>>
	 */
	private $pages = array();

	/**
	 * Formatting helper.
	 *
	 * @var Formatting
	 */
	private $formatting;

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepository
	 */
	private $settings;

	/**
	 * Shared access policy.
	 *
	 * @var AccessPolicy
	 */
	private $access;

	/**
	 * Constructor.
	 *
	 * @param Formatting $formatting Formatting helper.
	 */
	public function __construct( Formatting $formatting, SettingsRepository $settings, AccessPolicy $access ) {
		$this->formatting = $formatting;
		$this->settings   = $settings;
		$this->access     = $access;
		$this->pages      = $this->get_pages();
	}

	/**
	 * Register WordPress hooks.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register menu structure.
	 */
	public function register_menu(): void {
		$capability = 'coordina_access';

		add_menu_page(
			__( 'Coordina', 'coordina' ),
			__( 'Coordina', 'coordina' ),
			$capability,
			'coordina',
			array( $this, 'render_page' ),
			'dashicons-clipboard',
			26
		);

		foreach ( $this->pages as $slug => $page ) {
			add_submenu_page(
				$this->is_visible_in_menu( $slug ) ? 'coordina' : null,
				$page['title'],
				$page['menu_title'],
				$page['capability'],
				$slug,
				array( $this, 'render_page' )
			);
		}
	}

	/**
	 * Enqueue scoped admin assets.
	 *
	 * @param string $hook_suffix Current admin hook.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( false === strpos( $hook_suffix, 'coordina' ) ) {
			return;
		}

		$this->enqueue_admin_styles();
		wp_enqueue_media();
		$this->enqueue_admin_scripts();

		wp_localize_script(
			'coordina-admin-core',
			'coordinaAdmin',
			array(
				'restUrl'        => esc_url_raw( rest_url( 'coordina/v1' ) ),
				'nonce'          => wp_create_nonce( 'wp_rest' ),
				'defaultPage'    => $this->get_default_page_slug(),
				'currentPage'    => $this->get_current_page_slug(),
				'visiblePages'   => $this->get_visible_page_slugs(),
				'isRtl'          => is_rtl(),
				'projectContext' => array(
					'id'  => $this->get_current_project_id(),
					'tab' => $this->get_current_project_tab(),
				),
				'taskContext'    => array(
					'id' => $this->get_current_task_id(),
				),
				'milestoneContext' => array(
					'id' => $this->get_current_milestone_id(),
				),
				'riskIssueContext' => array(
					'id' => $this->get_current_risk_issue_id(),
				),
				'pages'          => $this->get_frontend_pages(),
				'i18n'           => array(
					'loading'    => __( 'Loading Coordina shell...', 'coordina' ),
					'chooseFile' => __( 'Choose file', 'coordina' ),
				),
			)
		);
	}

	/**
	 * Enqueue admin styles as smaller modules so future edits stay localized.
	 */
	private function enqueue_admin_styles(): void {
		$styles = array(
			'coordina-admin-shell'      => 'assets/admin/css/shell.css',
			'coordina-admin-components' => 'assets/admin/css/components.css',
			'coordina-admin-workspace'  => 'assets/admin/css/workspace.css',
			'coordina-admin-planning'   => 'assets/admin/css/planning.css',
			'coordina-admin-icons'      => 'assets/admin/css/icons.css',
		);

		foreach ( $styles as $handle => $relative_path ) {
			$path = COORDINA_PATH . $relative_path;
			$url  = COORDINA_URL . $relative_path;

			wp_enqueue_style( $handle, $url, array(), $this->asset_version( $path ) );
		}
	}

	/**
	 * Enqueue admin scripts as smaller modules so future edits stay localized.
	 */
	private function enqueue_admin_scripts(): void {
		$scripts = array(
			'coordina-admin-core' => array(
				'path' => 'assets/admin/js/core.js',
				'deps' => array( 'wp-element', 'wp-i18n' ),
			),
			'coordina-admin-collection-ui' => array(
				'path' => 'assets/admin/js/collection-ui.js',
				'deps' => array( 'coordina-admin-core', 'wp-i18n' ),
			),
			'coordina-admin-pages' => array(
				'path' => 'assets/admin/js/pages.js',
				'deps' => array( 'coordina-admin-collection-ui', 'wp-i18n' ),
			),
			'coordina-admin-collaboration' => array(
				'path' => 'assets/admin/js/collaboration.js',
				'deps' => array( 'coordina-admin-pages', 'wp-i18n' ),
			),
			'coordina-admin-ui' => array(
				'path' => 'assets/admin/js/ui.js',
				'deps' => array( 'coordina-admin-collaboration', 'wp-i18n' ),
			),
			'coordina-admin-events' => array(
				'path' => 'assets/admin/js/events.js',
				'deps' => array( 'coordina-admin-ui', 'wp-i18n' ),
			),
		);

		foreach ( $scripts as $handle => $script ) {
			$path = COORDINA_PATH . $script['path'];
			$url  = COORDINA_URL . $script['path'];

			wp_enqueue_script( $handle, $url, $script['deps'], $this->asset_version( $path ), true );
			wp_set_script_translations( $handle, 'coordina', COORDINA_PATH . 'languages' );
		}
	}

	/**
	 * Get a stable asset version from filemtime.
	 *
	 * @param string $path Asset path.
	 * @return string
	 */
	private function asset_version( string $path ): string {
		return file_exists( $path ) ? (string) filemtime( $path ) : COORDINA_VERSION;
	}

	/**
	 * Render requested page.
	 */
	public function render_page(): void {
		$page_slug = $this->get_current_page_slug();
		$page      = $this->pages[ $page_slug ] ?? $this->pages[ $this->get_default_page_slug() ];
		$page_data = $page;
		$template  = COORDINA_PATH . 'templates/admin/app.php';

		if ( ! $this->can_access_page( $page_slug ) ) {
			wp_die( esc_html__( 'You are not allowed to access this Coordina screen.', 'coordina' ) );
		}

		if ( 'coordina-projects' === $page_slug && $this->get_current_project_id() > 0 ) {
			$page_data['title']       = __( 'Project Workspace', 'coordina' );
			$page_data['description'] = __( 'Dedicated project dashboard with execution tabs, project context, and linked task management.', 'coordina' );
		}

		if ( 'coordina-task' === $page_slug && $this->get_current_task_id() > 0 ) {
			$page_data['title']       = __( 'Task Detail', 'coordina' );
			$page_data['description'] = __( 'Full task context with details, updates, files, and edit actions.', 'coordina' );
		}

		if ( 'coordina-milestone' === $page_slug && $this->get_current_milestone_id() > 0 ) {
			$page_data['title']       = __( 'Milestone Detail', 'coordina' );
			$page_data['description'] = __( 'Full milestone context with details, updates, files, and edit actions.', 'coordina' );
		}

		if ( 'coordina-risk-issue' === $page_slug && $this->get_current_risk_issue_id() > 0 ) {
			$page_data['title']       = __( 'Risk & Issue Detail', 'coordina' );
			$page_data['description'] = __( 'Full risk or issue context with details, updates, files, and edit actions.', 'coordina' );
		}

		if ( ! file_exists( $template ) ) {
			return;
		}

		$current_user = wp_get_current_user();
		$screen_data  = array(
			'page'         => $page_data,
			'page_slug'    => $page_slug,
			'pages'        => $this->pages,
			'current_user' => $current_user,
			'is_rtl'       => is_rtl(),
			'today'        => $this->formatting->date( current_time( 'mysql' ) ),
			'default_page' => $this->get_default_page_slug(),
		);

		include $template;
	}

	/**
	 * Determine current page slug.
	 *
	 * @return string
	 */
	private function get_current_page_slug(): string {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'coordina';

		if ( 'coordina' === $page ) {
			return $this->get_default_page_slug();
		}

		if ( isset( $this->pages[ $page ] ) ) {
			return $page;
		}

		return $this->get_default_page_slug();
	}

	/**
	 * Get current project id from query string.
	 *
	 * @return int
	 */
	private function get_current_project_id(): int {
		return isset( $_GET['project_id'] ) ? max( 0, absint( wp_unslash( $_GET['project_id'] ) ) ) : 0;
	}

	/**
	 * Get current task id from query string.
	 *
	 * @return int
	 */
	private function get_current_task_id(): int {
		return isset( $_GET['task_id'] ) ? max( 0, absint( wp_unslash( $_GET['task_id'] ) ) ) : 0;
	}

	/**
	 * Get current milestone id from query string.
	 *
	 * @return int
	 */
	private function get_current_milestone_id(): int {
		return isset( $_GET['milestone_id'] ) ? max( 0, absint( wp_unslash( $_GET['milestone_id'] ) ) ) : 0;
	}

	/**
	 * Get current risk or issue id from query string.
	 *
	 * @return int
	 */
	private function get_current_risk_issue_id(): int {
		return isset( $_GET['risk_issue_id'] ) ? max( 0, absint( wp_unslash( $_GET['risk_issue_id'] ) ) ) : 0;
	}

	/**
	 * Get current project tab.
	 *
	 * @return string
	 */
	private function get_current_project_tab(): string {
		$tab = isset( $_GET['project_tab'] ) ? sanitize_key( wp_unslash( $_GET['project_tab'] ) ) : 'overview';

		return '' !== $tab ? $tab : 'overview';
	}

	/**
	 * Choose a role-aware landing page.
	 *
	 * @return string
	 */
	private function get_default_page_slug(): string {
		$settings = $this->settings->get();
		$default  = (string) ( $settings['general']['default_landing_page'] ?? '' );
		$visible  = $this->get_visible_page_slugs();

		if ( isset( $this->pages[ $default ] ) && in_array( $default, $visible, true ) && current_user_can( $this->pages[ $default ]['capability'] ) ) {
			return $default;
		}

		if ( in_array( 'coordina-dashboard', $visible, true ) ) {
			return 'coordina-dashboard';
		}

		return 'coordina-my-work';
	}

	/**
	 * Determine whether current user is an admin-level Coordina user.
	 */
	private function is_admin_level_user(): bool {
		return current_user_can( 'coordina_manage_settings' );
	}

	/**
	 * Get visible top-level pages for the current user.
	 *
	 * @return array<int, string>
	 */
	private function get_visible_page_slugs(): array {
		$visible = array();
		$settings = $this->settings->get();
		$scope    = (string) ( $settings['access']['non_admin_navigation_scope'] ?? 'dashboard-my-work-projects' );
		$allowed_non_admin = array( 'coordina-dashboard', 'coordina-my-work' );

		if ( in_array( $scope, array( 'dashboard-my-work-projects', 'dashboard-my-work-projects-tasks' ), true ) ) {
			$allowed_non_admin[] = 'coordina-projects';
		}

		$allowed_non_admin[] = 'coordina-requests';

		if ( 'dashboard-my-work-projects-tasks' === $scope ) {
			$allowed_non_admin[] = 'coordina-tasks';
		}

		foreach ( $this->pages as $slug => $page ) {
			if ( ! current_user_can( $page['capability'] ) ) {
				continue;
			}

			if ( $this->is_admin_level_user() ) {
				$visible[] = $slug;
				continue;
			}

			if ( in_array( $slug, $allowed_non_admin, true ) ) {
				$visible[] = $slug;
			}
		}

		if ( empty( $visible ) && current_user_can( 'coordina_access' ) ) {
			$visible[] = 'coordina-my-work';
		}

		return array_values( array_unique( $visible ) );
	}

	/**
	 * Determine whether a page should appear in the menu.
	 */
	private function is_visible_in_menu( string $slug ): bool {
		if ( ! empty( $this->pages[ $slug ]['hidden'] ) ) {
			return false;
		}

		return in_array( $slug, $this->get_visible_page_slugs(), true );
	}

	/**
	 * Determine whether current user can access the requested page.
	 */
	private function can_access_page( string $slug ): bool {
		if ( ! isset( $this->pages[ $slug ] ) || ! current_user_can( $this->pages[ $slug ]['capability'] ) ) {
			return false;
		}

		if ( $this->is_visible_in_menu( $slug ) ) {
			return true;
		}

		if ( 'coordina-projects' === $slug && ! $this->is_admin_level_user() ) {
			$project_id = $this->get_current_project_id();

			return $project_id > 0 && $this->access->can_view_project( $project_id );
		}

		if ( 'coordina-task' === $slug ) {
			$task_id = $this->get_current_task_id();

			return $task_id > 0 && $this->access->can_view_task( $task_id );
		}

		if ( 'coordina-milestone' === $slug ) {
			$milestone_id = $this->get_current_milestone_id();

			return $milestone_id > 0 && $this->access->can_view_milestone( $milestone_id );
		}

		if ( 'coordina-risk-issue' === $slug ) {
			$risk_issue_id = $this->get_current_risk_issue_id();

			return $risk_issue_id > 0 && $this->access->can_view_risk_issue( $risk_issue_id );
		}

		if ( $this->is_admin_level_user() ) {
			return false;
		}

		if ( 'coordina-projects' !== $slug ) {
			return false;
		}

		return false;
	}

	/**
	 * Frontend page config without capability internals.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function get_frontend_pages(): array {
		$pages = array();

		foreach ( $this->pages as $slug => $page ) {
			$pages[ $slug ] = array(
				'title'              => (string) $page['title'],
				'description'        => (string) $page['description'],
				'priority'           => (string) ( $page['priority'] ?? 'secondary' ),
				'purpose'            => (string) ( $page['purpose'] ?? 'support' ),
				'empty_action_label' => (string) ( $page['empty_action_label'] ?? '' ),
			);
		}

		return $pages;
	}

	/**
	 * Build page configuration.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function get_pages(): array {
		return array(
			'coordina-dashboard' => array(
				'title'       => __( 'Dashboard', 'coordina' ),
				'menu_title'  => __( 'Dashboard', 'coordina' ),
				'capability'  => 'coordina_view_dashboard',
				'description' => __( 'See exceptions first, then route into projects, approvals, and execution work.', 'coordina' ),
				'priority'    => 'primary',
				'purpose'     => 'oversight',
			),
			'coordina-my-work' => array(
				'title'       => __( 'My Work', 'coordina' ),
				'menu_title'  => __( 'My Work', 'coordina' ),
				'capability'  => 'coordina_access',
				'description' => __( 'Work your day from one place: due, overdue, waiting, and approvals.', 'coordina' ),
				'priority'    => 'primary',
				'purpose'     => 'execution',
			),
			'coordina-projects' => array(
				'title'       => __( 'Projects', 'coordina' ),
				'menu_title'  => __( 'Projects', 'coordina' ),
				'capability'  => 'coordina_access',
				'description' => __( 'Browse the portfolio, then open each project workspace for planning and execution.', 'coordina' ),
				'priority'    => 'primary',
				'purpose'     => 'portfolio',
			),
			'coordina-requests' => array(
				'title'       => __( 'Requests', 'coordina' ),
				'menu_title'  => __( 'Requests', 'coordina' ),
				'capability'  => 'coordina_access',
				'description' => __( 'Triage incoming work, decide ownership, and convert requests into action.', 'coordina' ),
				'priority'    => 'primary',
				'purpose'     => 'execution',
			),
			'coordina-approvals' => array(
				'title'       => __( 'Approvals', 'coordina' ),
				'menu_title'  => __( 'Approvals', 'coordina' ),
				'capability'  => 'coordina_access',
				'description' => __( 'Review approval queues without losing the parent work context.', 'coordina' ),
				'priority'    => 'primary',
				'purpose'     => 'execution',
			),
			'coordina-tasks' => array(
				'title'       => __( 'Tasks', 'coordina' ),
				'menu_title'  => __( 'Tasks', 'coordina' ),
				'capability'  => 'coordina_manage_tasks',
				'description' => __( 'Scan work across projects and standalone operations when you need a cross-project task view.', 'coordina' ),
				'priority'    => 'secondary',
				'purpose'     => 'support',
			),
			'coordina-task' => array(
				'title'       => __( 'Task Detail', 'coordina' ),
				'menu_title'  => __( 'Task Detail', 'coordina' ),
				'capability'  => 'coordina_access',
				'description' => __( 'Review the full task context, updates, files, and edit options on one page.', 'coordina' ),
				'priority'    => 'secondary',
				'purpose'     => 'execution',
				'hidden'      => true,
			),
			'coordina-milestone' => array(
				'title'       => __( 'Milestone Detail', 'coordina' ),
				'menu_title'  => __( 'Milestone Detail', 'coordina' ),
				'capability'  => 'coordina_access',
				'description' => __( 'Review the full milestone context, updates, files, and edit options on one page.', 'coordina' ),
				'priority'    => 'secondary',
				'purpose'     => 'execution',
				'hidden'      => true,
			),
			'coordina-risk-issue' => array(
				'title'       => __( 'Risk & Issue Detail', 'coordina' ),
				'menu_title'  => __( 'Risk & Issue Detail', 'coordina' ),
				'capability'  => 'coordina_access',
				'description' => __( 'Review the full risk or issue context, updates, files, and edit options on one page.', 'coordina' ),
				'priority'    => 'secondary',
				'purpose'     => 'execution',
				'hidden'      => true,
			),
			'coordina-calendar' => array(
				'title'       => __( 'Calendar', 'coordina' ),
				'menu_title'  => __( 'Calendar', 'coordina' ),
				'capability'  => 'coordina_access',
				'description' => __( 'Use this when you need a dated view across work, then jump back into the task or project to act.', 'coordina' ),
				'priority'    => 'secondary',
				'purpose'     => 'support',
			),
			'coordina-workload' => array(
				'title'       => __( 'Workload', 'coordina' ),
				'menu_title'  => __( 'Workload', 'coordina' ),
				'capability'  => 'coordina_manage_projects',
				'description' => __( 'Use this when you need to rebalance people and pressure across managed work.', 'coordina' ),
				'priority'    => 'secondary',
				'purpose'     => 'support',
			),
			'coordina-risks-issues' => array(
				'title'       => __( 'Risks & Issues', 'coordina' ),
				'menu_title'  => __( 'Risks & Issues', 'coordina' ),
				'capability'  => 'coordina_manage_projects',
				'description' => __( 'Monitor exceptions across projects when you need a cross-project risk view.', 'coordina' ),
				'priority'    => 'secondary',
				'purpose'     => 'support',
			),
			'coordina-files-discussion' => array(
				'title'       => __( 'Files & Discussions', 'coordina' ),
				'menu_title'  => __( 'Files & Discussions', 'coordina' ),
				'capability'  => 'coordina_access',
				'description' => __( 'Use this to find recent files and updates, then return to the parent work item to act.', 'coordina' ),
				'priority'    => 'secondary',
				'purpose'     => 'support',
			),
			'coordina-settings' => array(
				'title'       => __( 'Settings', 'coordina' ),
				'menu_title'  => __( 'Settings', 'coordina' ),
				'capability'  => 'coordina_manage_settings',
				'description' => __( 'Manage team defaults, intake rules, governance, and advanced plugin behavior.', 'coordina' ),
				'priority'    => 'secondary',
				'purpose'     => 'admin',
			),
		);
	}
}
