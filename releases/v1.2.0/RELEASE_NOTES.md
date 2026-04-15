# Coordina v1.2.0 — Daily Workspace & Global Inbox

**Release Date:** April 15, 2026

## Overview

Coordina 1.2.0 makes daily execution clearer and more actionable. My Work now behaves like a fuller personal workspace instead of a single priority list, and notifications now have a proper shared inbox surface instead of living behind scattered page-level controls.

This release focuses on execution flow, attention management, and better day-to-day usability without widening the product into heavier planning complexity.

## New Features

### My Work Becomes a Small Personal Workspace
- **Queue** remains the urgency-led default view for what needs attention first
- **Board** adds a personal status-based execution view inside My Work instead of forcing users into a separate module
- **Tasks** adds a fuller assigned-task surface with filtering, sorting, and pagination through the normal task endpoint
- **Coming up** keeps a lighter handoff into Calendar instead of embedding a full planning surface inside My Work

### Shared Global Inbox
- Added a top-right inbox trigger in the shared shell with unread count
- Notifications now open in a right-side drawer instead of a modal
- Inbox supports unread/all filtering, mark-all-read, direct open actions, and inline preference controls
- Unread remains the default state until a notification is explicitly marked read

### Actionable Notifications
- Task assignment and reassignment now create inbox notifications with direct admin URLs
- Pending approval creation and reassignment now create inbox notifications for approvers
- Inbox items are ordered with unread first, then newest

### My Work Controls In Settings
- Added global settings to control whether My Work task cards show helper guidance text
- Added global settings to control whether My Work task cards show quick action buttons

## Improvements

### Execution Flow
- Board and Tasks now avoid the generic `Coming next` badge that only makes sense in Queue
- Queue mini calendar uses stronger highlighting so dates with assigned work stand out more clearly
- Calendar is visible more broadly to normal Coordina users so planning access does not depend on admin-only navigation

### Reliability
- Notification read-state handling is normalized so unread counts and mark read/unread actions behave correctly
- Settings renderer regressions affecting the page display were fixed during the same release line

## Upgrade Notes
- No database schema changes are required for 1.2.0
- Direct upgrade from 1.1.1 is supported
- Existing notifications remain unread until users mark them read

## Recommended Review After Upgrade
- Check My Work settings to confirm whether guidance text and quick actions should stay enabled for your team
- Review notification preferences for project updates and approval alerts
- Validate non-admin navigation access if Calendar visibility matters for your team setup