# Coordina v1.1.1 — Access Control Tightening & Design Refinement

**Release Date:** April 12, 2026

## New Features

### Access Control Tightening
**Separate Authorization Levels**:
- **View Access**: Can the user see a record within a project?
- **Edit Access**: Can the user modify a record?
- **Delete Access**: Stricter than edit; reserved for project leads or admins
- **Collaboration Access**: Can the user post updates or attach files (context-specific)?

**New Access Behaviors**:
- Users can view project work without automatically receiving edit rights
- Record-level collaboration no longer inherits view access—posting updates and attaching files are gated separately per-context
- File attachment access is now settings-backed per-context (projects, tasks, milestones, risks/issues, requests)
- Task assignee-level collaboration is independent from project-wide access—assignees can always update their assigned tasks
- Non-admin users have restricted risk/issue edit access (owner/creator only)
- Delete actions are strictly gated and fully access-aware

**UI Adaptation**:
- Drawers and detail pages display in read-only mode when user lacks edit permission
- Edit buttons and collaboration actions are completely hidden (not disabled) when not available
- No "you don't have permission" messages in collections; inaccessible records do not appear
- Clear visual treatment for read-only context (form fields display as text, not disabled inputs)

### Modern Design System
**Refined Visual Language**:
- **Color Tokens**: Semantic palette with light/dark variants (success, warning, danger, info, etc.)
- **Shadows & Depth**: Refined hierarchy with `shadow-xs` through `shadow-lg` for visual elevation
- **Gradients**: Sophisticated 135° gradients for cards, buttons, and metric surfaces
- **Spacing**: Consistent 4px/8px/12px rhythm throughout
- **Transitions**: Smooth 200ms cubic-bezier(0.4, 0, 0.2, 1) for interactive feedback
- **Border Radius**: Modern 8–10px angles (cleaned from previous 12–14px)
- **Typography**: System UI fonts for performance and theme compatibility

**Icon System**:
- Replaced custom fonts with Unicode symbols for broad cross-browser reliability
- Icons included in status badges, notices, and priority indicators
- No external font dependencies

**WordPress Theme Integration**:
- Respects `--wp--preset--color--primary` for theme-aware colors
- Coordina colors blend naturally with light and dark WordPress themes
- Semantic colors remain accessible across different backgrounds

## Improvements
- Better visual hierarchy through refined shadow and gradient system
- Tighter badge spacing (4px gap, 5px 10px padding) for modern, compact appearance
- Improved form input and button transitions
- Enhanced hover states with more pronounced elevation (`-2px` transform)
- Better focus states for accessibility
- Settings page layout simplified with lighter, more cohesive visual treatment

## Technical Changes
- Admin CSS assets split modularly under `assets/admin/css/` for safer, localized edits
- Design tokens (colors, shadows, gradients, transitions) centralized in `shell.css`
- Component-level refinements in `components.css`, `workspace.css`, and `planning.css`
- All CSS transitions updated to modern cubic-bezier easing

## Bug Fixes & Refinements
- Fixed form input sizing and border-radius consistency
- Improved visibility of form focus states across all input types
- Better visual feedback on card hover states
- Cleaner appearance of tabs and active states
- Enhanced contrast ratios for better readability

## Upgrade Notes
- This is a minor update with no database schema changes
- No action required on upgrade; all access policies apply retroactively
- Settings for file attachment contexts should be reviewed to ensure proper access configuration
