# Coordina v1.0.0 — First Release

**Release Date:** April 10, 2026

## Overview

Coordina is a WordPress-native work management plugin built for operational teams. It brings structured project execution, daily work coordination, and manager oversight directly into the WordPress admin—staying simpler than heavyweight PM suites while remaining more structured than lightweight task boards.

Coordina combines your team's daily execution (`My Work`), project visibility (`Dashboard`), planning and governance (`Project Workspaces`), and settings-backed access controls into one cohesive WordPress-powered workspace.

## Key Features

### Daily Work Hub: My Work
- Central hub for your daily execution rhythms
- Urgency shaping: see which work needs attention today
- Quick actions on tasks, requests, and approvals
- Blocked and overdue visibility
- Keyboard-navigable for power users

### Oversight: Dashboard
- Summary-first surfaces for visibility into projects and team rhythm
- Project status cards
- Recent activity feed, grouped by date
- Upcoming milestones and approvals
- Quick drill-down into projects or specific work items

### Project Workspaces
Dedicated workspace for each project with a consistent rhythm:
- **Overview**: snapshot of project status, task groups, recent milestones, and key dates
- **Work**: list and board views of project tasks, organized by task groups
- **Milestones**: project planning dates and dependencies
- **Risks & Issues**: operational exceptions and mitigation tracking
- **Approvals**: approval workflows and decision history
- **Updates**: project activity and team communications
- **Files**: shared project assets with contextual attachments on work items
- **Activity**: read-only event feed grouped by date
- **Settings**: project governance, membership, visibility controls, and close-out notes

### Project Execution Structure
- **Tasks**: Project-linked or standalone work with checklists, priorities, and status
- **Task Groups**: Lightweight project-specific organization (e.g., Phases, Buckets, Stages) with progress tracking
- **Task Checklists**: Simple task-level checklists rendered as interactive checkboxes in both view and edit modes

### Approval & Request Workflows
- **Requests**: intake workflows for project proposals and changes
- **Approvals**: explicit approval steps with decision history and status tracking
- Request and approval status integrated into My Work urgency

### Planning & Oversight
- **Calendar**: Planning-first view of key dates including task due dates and project target end dates
- **Workload**: Manager-first view of team capacity with pressure scoring (ready for enhancement to full capacity planning)
- **Milestones**: Project timeline objects for planning distinct from task due dates

### Collaboration & Transparency
- **Updates**: project-associated activity feed for team communications
- **Files & Discussions**: contextual file sharing on projects, tasks, and work items; discover unattached or recent files through a discovery-first global surface
- **Activity Feed**: read-only timeline of project events with access-aware filtering

### Settings & Governance
- **Project Settings**: project-scoped governance, member visibility, and notification preferences
- **Global Settings**: WordPress-options-backed defaults, workflow values, portal behavior, and retention rules
  - `Defaults`: role defaults and automation placeholders
  - `Intake & Access`: project visibility, workspace access, and task visibility policy
  - `Governance`: workflow status values and structured field defaults
  - `Dropdowns`: configurable dropdown values across the system
  - `Advanced`: automation triggers and system behavior

### Access & Permissions
- Settings-backed access policy with separate evaluation of:
  - Project list visibility
  - Project workspace visibility
  - Task visibility within workspaces
  - Task edit rights
  - Project-manager task override capability
- Project visibility and task edit rights are independent—teams can view broadly without automatic edit access
- All access rules apply consistently across project, workspace, and collaboration surfaces

## System Requirements

- **WordPress**: 6.6 or later
- **PHP**: 7.4 or later
- **Database**: MySQL 5.7+ or MariaDB 10.2+ (required for custom table support)
- **Browser**: Modern browser with full ES6 JavaScript support

## Installation

### Via WordPress Admin
1. Go to **Plugins** → **Add New** in your WordPress admin
2. Search for "Coordina"
3. Click **Install Now**, then **Activate**
4. Click **Coordina** in the WordPress sidebar to get started

### Manual Installation
1. Download the Coordina plugin from [your distribution source]
2. Extract the ZIP file
3. Upload the `coordina` folder to `/wp-content/plugins/`
4. Go to **Plugins** in WordPress and click **Activate** next to Coordina

### First-Time Setup
1. Go to **Coordina** → **Settings** in WordPress admin
2. Configure your workspace defaults under `Defaults` tab
3. Set project visibility and access policy under `Intake & Access`
4. Review workflow status values under `Governance`
5. (Optional) Customize dropdown values under `Dropdowns`
6. Create your first project from **Coordina** → **Projects** → **New Project**

## What's Included in v1.0.0

### Core Entities
- **Projects** with custom governance and member-based access
- **Tasks** with project linking, standalone support, checklists, and task groups
- **Requests** for intake workflows
- **Approvals** for decision workflows with history
- **Milestones** for project planning timelines
- **Risks & Issues** for operational exception tracking
- **Files** with contextual attachment and project-scoped file discovery
- **Updates** for project-associated activity and team communications
- **Activity** as a read-only, access-aware timeline of events

### Admin Interfaces
- **Custom table storage** for all core work objects (not `wp_posts`)
- **REST API** endpoints for all primary surfaces with capabilities-gated access
- **Modular JavaScript and CSS** under `assets/admin/js/` and `assets/admin/css/` for maintainability
- **WordPress-native** capabilities, nonces, sanitization, escaping, and i18n support

### Role & Capability Model
- Integration with WordPress roles: **Administrator**, **Editor**, **Contributor**, **Subscriber**
- Coordina-specific capabilities for:
  - Viewing, creating, and managing projects
  - Accessing and editing workspace content
  - Viewing and managing settings
  - Accessing oversight surfaces (Dashboard, Calendar, Workload)
- Refined capabilities for task managers and project leaders

### Frontend Portal
- Lightweight portal (shortcode-based) for external stakeholder visibility
- Portal role and notification preferences configurable in Settings

## Known Limitations & Future Enhancements

### Current Scope Intentionally Kept Minimal
- **Calendar Date Semantics**: Currently centers on task due dates and project target end dates. Project timeline dates, milestone dates, and approval dates will be added with explicit semantic separation in a future release.
- **Workload Capacity Planning**: Currently uses simple pressure scoring for easy oversight. Full capacity planning modeling is planned for a future release.
- **Activity Feed**: Implemented as lightweight recent event tracking. Full audit trail and deep filtering are not included in v1.0.0.
- **Role Mutation**: Global settings do not include free-form role or capability editing. Role defaults remain in code with settings-backed configuration of business values only.
- **Import/Export**: Not included in v1.0.0; planning for future release.
- **Integrations**: No third-party tool integrations (e.g., Slack, Teams, Zapier) in v1.0.0.

### Browser & Accessibility
- Full accessibility and RTL (right-to-left) review is planned as a dedicated quality pass in the next release
- Keyboard navigation is supported but not comprehensively tested
- High-contrast and screen-reader support verified for core surfaces but not exhaustively tested

### Performance
- Current implementation tested with teams of up to 50 users and 200 active projects; larger deployments may require custom scaling strategies
- Caching and performance optimization are deferred to a future release based on real-world usage data

## Initial Deployment Notes
- Database tables are created automatically on plugin activation
- Demo data seeding is available via CLI for development/testing
- No migration from other PM tools is provided in v1.0.0; data import is planned for a future release
