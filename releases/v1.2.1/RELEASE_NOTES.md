# Coordina v1.2.1 — Modular Admin Runtime Stabilization

**Release Date:** April 15, 2026

## Overview

Coordina 1.2.1 is a focused patch release. It does not widen product scope or introduce schema changes. Instead, it makes the admin JavaScript architecture safer to maintain and fixes the runtime integration issues that surfaced after splitting larger admin files.

This release is about stability, load order clarity, and keeping Inbox, Settings, Dashboard, and My Work reliable while the admin asset layer becomes more modular.

## What Changed

### Admin Page Rendering Split Into Smaller Modules
- Shared page helpers remain in the core pages module
- Settings rendering now lives in its own module
- Dashboard, Calendar, Workload, My Work, and notification surfaces now live in a separate surface module
- WordPress admin enqueue order was updated so shared helpers load before specialized page surfaces

### Admin Event Handling Split Into Focused Modules
- Shared event utilities now live in a dedicated helper module
- Click-heavy behavior now lives in a focused click-action module
- Change, input, and submit behavior now live in a form-action module
- The top-level events file is now a thinner binder instead of a large monolith

### Runtime Binding Fixes For Split Modules
- Split page renderers are now resolved from the shared app object at render time instead of being captured once too early
- Inbox now handles the notification-list dependency more safely instead of failing hard when the helper is temporarily unavailable
- This specifically stabilizes Settings, Dashboard, My Work, and Inbox after the page-module split

## Why This Matters

- Future admin changes can stay localized instead of growing one oversized JS file again
- The dependency chain between admin modules is clearer and easier to reason about
- Patch-level reliability improves without changing the database or user-facing workflows

## Upgrade Notes

- No database schema changes are required for 1.2.1
- Direct upgrade from 1.2.0 is supported
- Existing project, task, and notification data remain compatible

## Recommended Review After Upgrade

- Open Inbox and confirm unread notifications render correctly
- Open Settings and verify the page sections load normally
- Open Dashboard and My Work and confirm the main surfaces render fully
- If you maintain custom admin deployment packaging, include the new split JS files introduced in this release