=== Coordina ===
Contributors: coordina
Tags: project management, task management, workflow, operations, approvals, requests, teamwork
Requires at least: 6.6
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.3.0
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
* Dedicated Project Workspaces with tabs for Overview, Details, Work, Gantt, Milestones, Risks & Issues, Approvals, Updates, Files, Activity, and Settings
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

= 1.3.0 =

* Dashboard redesigned into a cleaner portfolio overview with smaller KPIs, grouped recent activity, full-period activity-by-user insight, and clearer side queues
* My Work flattened and cleaned up with better board/status alignment and lighter task/task-filter presentation
* Project workspaces expanded with a new Details tab, richer overview/layout refinements, and clearer workspace-native detail screens
* Projects now support a sponsor field and a dedicated sectioned project form for clearer create/edit flows
* Database update included for the new sponsor-backed project schema field

= 1.2.1 =

* Admin page rendering split into smaller specialized modules for safer maintenance
* Admin event handling split into shared, click, form, and binder modules
* Fixed split-module runtime binding so Inbox, Settings, Dashboard, and My Work render reliably
* No database changes; direct upgrade from 1.2.0 supported

= 1.2.0 =

* My Work expanded into Queue, Board, and Tasks views for daily execution
* Global inbox added to the shared shell with unread count and drawer-based notification handling
* Task assignment and approval-request notifications now create actionable inbox items
* My Work task-card controls added to Settings for guidance text and quick actions
* Queue mini calendar improved and Calendar exposed more broadly to Coordina users

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

= 1.3.0 =

Feature release with dashboard, My Work, and project workspace improvements, plus a small project schema update for sponsor support.

= 1.2.1 =

Patch release focused on admin asset modularization and runtime reliability. No database changes required; direct upgrade from 1.2.0.

= 1.2.0 =

My Work is now a fuller daily workspace with Queue, Board, Tasks, and a global inbox. Direct upgrade available with no database changes.

= 1.1.1 =

Access control hardening and modern design system refinements. No database changes required; direct upgrade available.

= 1.1.0 =

Dedicated detail pages for core work records and enhanced delete functionality. Direct upgrade available.

= 1.0.0 =

First production release. Complete Phase 2 operational depth with My Work, Dashboards, Workspaces, and settings-driven access control.

= 0.2.4 =

Recommended update for current Phase 2 operational depth and settings-backed access policy improvements.
