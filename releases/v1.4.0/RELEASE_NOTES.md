# Coordina v1.4.0 — Modular Core Platform

**Release Date:** April 18, 2026

## Overview

Coordina 1.4.0 is the core platform architecture release. It packages the modular kernel/provider runtime, registry-backed extension points, public service contracts, and centralized entitlement plumbing into the main plugin so future extensions can integrate without expanding the old monolithic bootstrap shape.

This release is intentionally about the core plugin. It does not bundle separate add-on feature notes into the Coordina package documentation.

## What Changed

### Core Boot Now Runs Through A Modular Platform Layer
- Plugin runtime now boots through `src/Platform/Kernel.php` and provider-owned service wiring
- Container-managed services can now be registered and extended through a cleaner platform graph instead of direct bootstrap construction
- Activation and runtime use the same provider-owned services more consistently

### Registries Now Drive More Of The Runtime
- Admin pages, REST routes, capability maps, settings defaults and choice lists, migration definitions, and context metadata now resolve through registries
- Shared admin shell payloads consume registry-fed definitions rather than duplicating fixed lists in the client layer
- Context-driven metadata now powers more routing and backend behavior consistently

### Public Contracts Reduce Core Coupling
- Shared project, task, settings, access, context, notification, and entitlement services are exposed behind platform contracts
- Main flows can depend on the contract layer instead of direct concrete instantiation
- Data seeding and other support workflows now reuse the platform-owned service graph more safely

### Entitlement And Extension Plumbing Are Centralized
- Core now includes a dedicated entitlement layer and option-backed feature-state storage
- Route-only admin pages are supported cleanly through page metadata for future packaged extensions
- The shared shell can reason about feature state without hardwiring extension implementation details into the core plugin

## Why This Matters

- Future packaged extensions have a clearer, safer integration surface
- Core boot, activation, and service wiring are easier to maintain and reason about
- Registry-driven definitions reduce drift between PHP runtime behavior and admin-shell behavior
- The architecture is now materially modular instead of only planned on paper

## Upgrade Notes

- No database changes are required in this release
- Direct upgrade from 1.3.1 is supported
- Existing projects, tasks, workspaces, settings, and planning surfaces continue to work with the same data model

## Recommended Review After Upgrade

- Activate Coordina in a live WordPress environment and confirm kernel/provider boot completes normally
- Open key admin surfaces and verify registry-fed pages, routes, and settings metadata load correctly
- Confirm role-aware navigation, project workspaces, and existing REST-backed flows still behave as expected
- Smoke-test activation/deactivation and any seeded-data workflows that depend on the platform container