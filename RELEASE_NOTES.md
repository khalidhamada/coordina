# Coordina Release Notes

Quick links to detailed release information for each version. For getting started, setup instructions, and configuration details, see [README.md](README.md).

## Current Release

### [v1.4.0 — Modular Core Platform](releases/v1.4.0/RELEASE_NOTES.md)
**April 18, 2026**

Coordina 1.4.0 is the core platform architecture release. It packages the kernel/provider runtime, registry-backed extension points, public contracts, and centralized entitlement plumbing into the main plugin without bundling separate add-on features into this release.

**Highlights:** Kernel/provider boot • Registry-backed runtime • Public contracts • Centralized entitlement state

[View full details →](releases/v1.4.0/RELEASE_NOTES.md)

---

## Previous Releases

### [v1.3.1 — Planning & File Experience Polish](releases/v1.3.1/RELEASE_NOTES.md)
**April 18, 2026**

Coordina 1.3.1 is a focused patch release that tightens approval routing, stabilizes Calendar and Gantt interactions, and upgrades the project and record-level file experience.

**Highlights:** Approval drawer fixes • Calendar and Gantt polish • Adaptive long-range timeline grouping • File cards and richer file drawer

[View full details →](releases/v1.3.1/RELEASE_NOTES.md)

---

### [v1.3.0 — Workspace Expansion & Portfolio Refinement](releases/v1.3.0/RELEASE_NOTES.md)
**April 17, 2026**

Coordina 1.3.0 expands the project workspace, refines Dashboard and My Work, and adds sponsor support to the core project record.

**Highlights:** Project Details tab • Dashboard redesign • My Work cleanup • Project sponsor field

[View full details →](releases/v1.3.0/RELEASE_NOTES.md)

---

### [v1.2.1 — Modular Admin Runtime Stabilization](releases/v1.2.1/RELEASE_NOTES.md)
**April 15, 2026**

Coordina 1.2.1 is a patch release focused on admin maintainability and runtime stability after the admin JavaScript split.

**Highlights:** Split page modules • Split event modules • Runtime binding fix • No database changes

[View full details →](releases/v1.2.1/RELEASE_NOTES.md)

---

### [v1.2.0 — Daily Workspace & Global Inbox](releases/v1.2.0/RELEASE_NOTES.md)
**April 15, 2026**

My Work now behaves like a fuller personal workspace with Queue, Board, and Tasks views, plus a shared inbox for assignments and approvals.

**Highlights:** Queue/Board/Tasks views • Global inbox drawer • Actionable notifications • My Work settings controls

[View full details →](releases/v1.2.0/RELEASE_NOTES.md)

---

### [v1.1.1 — Access Control Tightening & Design Refinement](releases/v1.1.1/RELEASE_NOTES.md)
**April 12, 2026**

Multi-level access control (view/edit/delete), modern design system, Unicode icons, and WordPress theme integration.

**Highlights:** Separate authorization levels • Design tokens & gradients • Read-only UI adaptation • Enhanced consistency

[View full details →](releases/v1.1.1/RELEASE_NOTES.md)

---

### [v1.1.0 — Dedicated Detail Pages & Enhanced Destruction](releases/v1.1.0/RELEASE_NOTES.md)
**April 11, 2026**

Full-page detail views for tasks, risks/issues, and milestones; delete functionality with cascade support.

**Highlights:** Detail pages replace drawers • Cascading deletion • Data seeding • Activity improvements

[View full details →](releases/v1.1.0/RELEASE_NOTES.md)

---

### [v1.0.0 — First Release](releases/v1.0.0/RELEASE_NOTES.md)
**April 10, 2026**

Complete foundation: My Work, Dashboard, Project Workspaces (9 tabs), approvals, requests, planning surfaces, and settings-backed access control.

**Highlights:** Daily execution hub • Project workspace suite • Approval workflows • Role-based permissions

[View full details →](releases/v1.0.0/RELEASE_NOTES.md)

---

## Upgrade Path

All upgrades are direct and backward-compatible:
- **v1.3.0 → v1.3.1**: No database changes
- **v1.3.1 → v1.4.0**: No database changes
- **v1.2.1 → v1.3.0**: Includes a database update for the new project sponsor field
- **v1.2.0 → v1.2.1**: No database changes
- **v1.1.1 → v1.2.0**: No database changes
- **v1.0.0 → v1.1.0**: No database changes
- **v1.1.0 → v1.1.1**: No database changes

See the detailed release notes above for any version-specific guidance.

---

## Key Principles

As you use Coordina, keep these design principles in mind:

1. **Start with My Work**—It's your daily execution hub. Everything else is context.
2. **Dashboard stays summary-first**—It's for exception visibility, not a raw task list.
3. **Projects are workspaces**—Each project is a dedicated, navigable space with consistent rhythm.
4. **Collaboration stays contextual**—Files, updates, and discussions attach to parent work, not float in isolation.
5. **Access is settings-governed**—Visibility and edit rights are independent and evaluated consistently.
6. **WordPress-native**—Coordina respects WordPress roles, capabilities, sanitization, and i18n. No separate user system.

---

## License

Coordina is free software distributed under the [GNU General Public License v2 or later](./LICENSE). It integrates deeply with WordPress and respects the open-source values of the WordPress community.

## Credits & Acknowledgments

Coordina was built with the operational realities of growing teams in mind. It reflects months of real-world iteration, design review, and field testing. Special thanks to the teams who shaped this direction.

---

## Contact & Support

For questions, feedback, or support:
- [Support email or contact process]
- [Community forum or Slack]
- [GitHub issues or bug tracker]

---

**Thank you for using Coordina. Happy coordinating!**
