# Coordina v1.1.0 — Dedicated Detail Pages & Enhanced Destruction

**Release Date:** April 11, 2026

## New Features

### Dedicated Detail Pages Across Core Records
**Tasks and Risks/Issues** (full-page views replacing shared drawer pattern):
- Comprehensive detail pages for tasks and risks/issues with consistent layout
- Each detail page includes:
  - **Overview**: key information, status, priority, dates, and planning summary
  - **Updates**: record-associated activity feed for team communications
  - **Files**: shared assets and contextual attachments
  - **Activity**: read-only timeline grouped by date
  - **Governance**: full sectioned editing with translatable helper descriptions
- Activity logging emits lightweight entries for task and risk/issue collaborations, edits, and status changes
- Direct routing for collaboration and activity context now resolves back to detail pages for consistent navigation

**Milestones** (detail page replacing modal):
- Milestones now open into full-page detail views instead of modals for better context and usability
- Same comprehensive structure as task and risk/issue pages with overview, updates, files, activity, and governance sections
- Unified routing across all detail page types

### Delete Functionality
- Delete actions now available across core records:
  - **Projects** (cascade-deletes related project-scoped records: tasks, milestones, risks/issues, approvals, updates, files, and discussions)
  - **Tasks** (project-linked or standalone)
  - **Requests**
  - **Milestones**
  - **Risks & Issues**
  - **Files**
  - **Updates** (discussions)
- Delete authorization is evaluated separately from edit authorization—destruction is stricter than day-to-day edits
- Project, milestone, and risk/issue deletion reserved for users with full project access
- Consistent delete UI across all delete-supporting records
- Non-admin users limited to soft-delete or restriction based on ownership/creator role

### Data Seeding Infrastructure
- Added `DataSeeder` class (668 lines) for populating demo/test environments
- `DataSeedCommand` CLI tool for reproducible data generation
- Seeding examples and documentation for development workflows
- Seeds realistic project, task, milestone, approval, and activity data

## Improvements
- Refined detail page patterns for strict consistency across tasks, risks/issues, and milestones
- Improved modular routing with access-aware server checks and client-side state loading
- Better activity logging and collaboration context preservation across destruction boundaries
- Enhanced modular form rendering with translatable helper descriptions
- Stronger access policy evaluation for detail pages and delete operations

## Technical Dependencies
- Continues to use custom tables for core work-management entities
- Maintains WordPress-native capabilities, REST permissions, and i18n across all new flows
- Modular admin assets continue to be split under `assets/admin/js/` and `assets/admin/css/`

## Upgrade Notes
- No database schema changes; upgrade is direct
- Existing drawer-style interactions will redirect users to the new detail pages automatically
- Delete authorization rules apply to existing records
