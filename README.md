# Coordina — Project & Work Management for WordPress

A WordPress-native project and work management plugin built for operational teams, agencies, and organizations that need structured execution without the complexity of heavyweight PM tools.

## Overview

Coordina brings professional work management, project oversight, and team coordination directly into WordPress admin. It's designed to be simpler than Jira, more structured than Trello, and deeply integrated with WordPress workflows, permissions, and practices.

**Perfect for:**
- Agencies managing multiple client projects
- Operations teams coordinating approval workflows and requests
- Project managers overseeing team workload and planning
- Teams using WordPress as their operational hub

## Key Features

### Daily Execution
- **My Work Hub** – Your daily command center with urgency-sighted task lists, approvals, and requests
- **Dashboard** – Project snapshots, activity feed, workload visibility, and quick drill-down into active projects

### Project Management
- **Project Workspaces** – Dedicated spaces for each project with 10 coordinated tabs:
  - Overview, Work (list & board views), Gantt timeline, Milestones, Risks & Issues, Approvals, Updates, Files, Activity, Settings
- **Task Groups** – Lightweight project organization (e.g., Phases, Departments, Stages) with progress tracking
- **Checklists** – Interactive checkboxes on tasks for breaking work into steps

### Collaboration & Planning
- **Approval Workflows** – Route work through explicit approval steps with decision history
- **Requests Portal** – Intake surface for project proposals and change requests
- **Calendar** – Planning-first view of key dates and milestones
- **Workload** – Manager view of team capacity and pressure
- **Files & Discussions** – Contextual collaboration on projects and tasks
- **Activity Feed** – Read-only timeline of project events, grouped by date

### Governance & Access
- **Role-Based Access Policy** – Settings-backed visibility and edit controls:
  - Project list visibility
  - Workspace access
  - Task visibility and edit rights
  - Project manager overrides
- **Access-Aware UI** – Users see only what they can access; edit capabilities adapt dynamically:
  - View-only mode for records without edit permission
  - Separate authorization for destructive actions (delete)
  - Task assignee-level collaboration independent of project-wide access
  - Context-aware file attachment and update permissions
- **Global Settings** – Centralized configuration for defaults, workflow values, portal behavior, and retention rules
- **WordPress Office** – Integrates with WordPress roles, capabilities, users, and permissions

## System Requirements

- **WordPress:** 6.6 or higher
- **PHP:** 7.4 or higher
- **Browser:** Modern browser with ES6 support (Chrome, Firefox, Safari, Edge)

## Installation

### From Plugin Repository
1. Go to **Plugins** → **Add New** in your WordPress admin
2. Search for **Coordina**
3. Click **Install Now**, then **Activate**
4. Open **Coordina** from the WordPress admin menu

### Manual Upload
1. Download the Coordina plugin ZIP file
2. Go to **Plugins** → **Add New** → **Upload Plugin**
3. Select the ZIP file and click **Install Now**
4. Click **Activate Plugin**

### After Activation
1. Open **Coordina** in the WordPress admin sidebar (you'll see a new menu item)
2. Go to **Settings** and configure:
   - **Defaults** – Role permissions and initial team settings
   - **Intake & Access** – Visibility controls for projects and tasks
   - **Governance** – Workflow statuses and field defaults
   - **Dropdowns** – Custom values for your workflows
   - **Advanced** – Automation and system behavior

## Getting Started

### Step 1: Create Your First Project
1. Click **Projects** in the Coordina menu
2. Click **New Project**
3. Fill in project details (name, description, dates, team members)
4. Click **Create Project**
5. Open the project to access its dedicated workspace

### Step 2: Build Your Team & Structure
1. Go to your project's **Work** tab
2. Add **Task Groups** to organize work (e.g., Design, Development, QA)
3. Add **Tasks** within each group
4. Assign tasks to team members
5. Use **Settings** to manage project members and visibility

### Step 3: Track Daily Work
1. Each team member uses **My Work** as their daily hub
2. Managers use **Dashboard** for overview
3. Use **Calendar** for planning
4. Reference **Activity** feed for updates and collaboration

## Architecture & Design

### Data Storage
Coordina uses custom database tables for core work objects (projects, tasks, approvals, risks, etc.) rather than WordPress posts. This provides:
- **Performance** – Optimized schema for project and task queries
- **Integrity** – Structured data with proper relationships
- **Simplicity** – No post-type confusion or post-meta overhead

### WordPress Integration
Coordina is deeply integrated with WordPress:
- **Users & Roles** – Uses WordPress user system and roles for access control
- **Capabilities** – Respects WordPress capabilities for permission checks
- **REST API** – All UI data flows through WordPress REST endpoints with nonce protection
- **Sanitization & Escaping** – Full WordPress security practices throughout
- **Internationalization** – Ready for translation via WordPress i18n functions

### Admin UI
The admin interface is modular and built on modern JavaScript, with assets split for maintainability:
- Shared components under `assets/admin/js/`
- Modular CSS under `assets/admin/css/`
- All code follows WordPress coding standards and escaped/sanitized properly

### Design System
Coordina includes a modern, cohesive design system:
- **Color Tokens** – Semantic colors for status, priority, and UI states with light/dark variants
- **Shadows & Depth** – Refined shadow hierarchy (XS–LG) for visual depth and elevation
- **Gradients** – Subtle 135° gradients for sophisticated depth perception
- **Spacing** – Consistent 4px/8px/12px rhythm throughout
- **Transitions** – Smooth 200ms cubic-bezier animations for interactive feedback
- **Typography** – System UI fonts for performance and WordPress theme compatibility
- **Border Radius** – Modern 8–10px angles for refined appearance
- **Icons** – Unicode-based icon system for reliable cross-browser rendering
- **Theme Integration** – Respects WordPress theme colors when available

## Frequently Asked Questions

### Q: Why custom tables instead of WordPress posts?
**A:** Custom tables provide better performance and data integrity for structured work objects. Projects, tasks, and approvals need precise relationships and schema—post-meta gets messy. You keep the simplicity of WordPress while using the right tool for project data.

### Q: Where do teams start?
**A:** **My Work** is the daily hub for everyone. Use **Dashboard** for management overview. **Projects** opens dedicate workspaces for planning and execution. **Calendar** and **Workload** are planning surfaces.

### Q: Can tasks exist without a project?
**A:** Yes. You can create standalone tasks in **My Work** for ad-hoc work, or link tasks to projects for structured planning. Standalone and project-linked tasks work together.

### Q: How is access controlled?
**A:** Through **Settings** → **Intake & Access**. You can control:
- Who sees which projects (by role)
- Who can edit tasks (by role or project assignment)
- Who can approve workflows
- Non-admin navigation scope (what operational team members see by default)
- Context-specific file attachments and update permissions

**Access is evaluated separately at each level:**
- *View access* – Can the user see this project/task?
- *Edit access* – Can the user edit this record?
- *Delete access* – Stricter; reserved for project leads or admins
- *Collaboration access* – Can the user post updates or attach files?

### Q: Can I use Coordina with multiple teams?
**A:** Yes. Use role-based access policy to scope visibility. WordPress roles automatically control what each user sees.

### Q: What about mobile access?
**A:** Coordina is optimized for desktop and tablet admin views. Mobile support for responsive views is on the roadmap.

### Q: Does Coordina have email notifications?
**A:** Notifications are managed through WordPress. Email delivery uses your site's email configuration. You can extend with custom notification hooks.

### Q: What's new in version 1.3.0?
**A:** Version 1.3.0 includes:
- **Dashboard Redesign** – Dashboard now works as a cleaner portfolio overview with smaller KPIs, grouped recent activity, and stronger review queues
- **My Work Cleanup** – My Work is flatter, clearer, and better aligned with project workspace task statuses and filters
- **Project Workspace Expansion** – Project workspaces now include `Details` and `Gantt`, plus clearer overview and record-detail patterns
- **Project Sponsor Support** – Projects now store a sponsor and use a clearer sectioned project form for create and edit flows
- **Schema Update** – This release includes a database update for the new project sponsor field

## Support & Documentation

- **Documentation:** See [RELEASE_NOTES.md](RELEASE_NOTES.md) for feature details

## Plugin Details

- **Contributors:** Khalid Hamada
- **Requires at least:** WordPress 6.6
- **Tested up to:** WordPress 6.6
- **Requires PHP:** 7.4 or higher
- **Stable tag:** 1.3.0
- **License:** GPLv2 or later
- **Text Domain:** `coordina`

## Credits

**Author:** Khalid Hamada

---

**Questions?** Check [RELEASE_NOTES.md](RELEASE_NOTES.md) for the latest features and improvements, or see [documentation/](docs/) for deeper technical guidance.
