=== Coordina ===
Contributors: coordina
Tags: project management, task management, workflow, operations, approvals, requests, teamwork
Requires at least: 6.6
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: coordina

Coordina is a WordPress-native work management plugin for operational teams.

== Description ==

Coordina provides structured daily execution and project oversight directly inside WordPress admin.

Key capabilities:

* Custom-table domain storage for core work objects (projects, tasks, requests, approvals, risks/issues, files, discussions, notifications, and saved views)
* Daily work execution through My Work
* Oversight through Dashboard
* Dedicated Project Workspaces with tabs for Overview, Work, Milestones, Risks & Issues, Approvals, Updates, Files, Activity, and Settings
* Support surfaces for Requests, Approvals, Calendar, Workload, Files & Discussions, and global Settings
* Project-scoped task grouping and task checklists
* Settings-backed access policy controls for visibility and edit behavior

Coordina is designed to stay simpler than heavyweight PM suites while remaining more structured than lightweight board-only tools.

== Installation ==

1. Upload the `coordina` plugin folder to `/wp-content/plugins/`, or install it through your deployment workflow.
2. Activate Coordina through the Plugins menu in WordPress.
3. Ensure your site runs WordPress 6.6+ and PHP 7.4+.
4. Open Coordina in wp-admin and configure defaults in Settings.

== Frequently Asked Questions ==

= Does Coordina use custom tables or `wp_posts`? =

Coordina uses custom tables for core work-management entities.

= Where should teams start day to day? =

Use My Work as the primary daily execution hub.

= Can tasks exist without a project? =

Yes. Coordina supports both project-linked tasks and standalone tasks.

= Is project access separate from task edit rights? =

Yes. Visibility and edit rights are evaluated through separate settings-backed policies.

== Changelog ==

= 1.1.1 =

* Access control tightening with separate view/edit/delete authorization
* Modern design system with refined colors, shadows, and gradients
* Unicode icon system for cross-browser reliability
* WordPress theme color integration
* Context-aware read-only UI when user lacks edit permission
* Admin CSS assets split for safer maintenance on Windows

= 1.1.0 =

* Dedicated detail pages for tasks, milestones, and risks/issues
* Full-page views replacing shared drawer pattern
* Delete functionality with access-aware authorization
* Cascading delete for projects with related records
* Data seeding infrastructure for testing
* Activity logging for collaboration events

= 1.0.0 =

* First production release with complete Phase 2 operational depth
* My Work hub and urgency-driven daily execution
* Dashboard oversight with summary-first design
* Full Project Workspace suite with 9 tabs and consistent rhythm
* Task groups and checklists for lightweight project structure
* Approvals and request workflows
* Calendar and Workload planning surfaces
* Files & Discussions contextual collaboration
* Global and project-scoped governance settings
* Settings-driven, role-aware access control
* WordPress-native capabilities, nonces, sanitization, and i18n
* Modular admin asset structure for maintainability

= 0.2.4 =

* Current operational Phase 2 implementation with:
* My Work, Dashboard, Projects, Tasks, Requests, Approvals, Risks & Issues
* Project workspaces with Overview, Work, Milestones, Risks & Issues, Approvals, Updates, Files, Activity, and Settings
* Calendar, Workload, Files & Discussions discovery surfaces
* Global settings structure with defaults, intake/access, governance, dropdowns, and advanced sections
* Task groups and task checklists

== Upgrade Notice ==

= 1.1.1 =

Access control hardening and modern design system refinements. No database changes required; direct upgrade available.

= 1.1.0 =

Dedicated detail pages for core work records and enhanced delete functionality. Direct upgrade available.

= 1.0.0 =

First production release. Complete Phase 2 operational depth with My Work, Dashboards, Workspaces, and settings-driven access control.

= 0.2.4 =

Recommended update for current Phase 2 operational depth and settings-backed access policy improvements.
