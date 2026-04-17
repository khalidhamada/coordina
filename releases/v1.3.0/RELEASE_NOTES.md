# Coordina v1.3.0 — Workspace Expansion & Portfolio Refinement

**Release Date:** April 17, 2026

## Overview

Coordina 1.3.0 is a feature release focused on making the day-to-day product surfaces clearer and more complete. It refines Dashboard into a stronger portfolio overview, cleans up My Work for execution, expands the project workspace with richer detail patterns, and widens the core project record with sponsor support.

This release is about making the main admin experience easier to scan and act on while keeping the product WordPress-native and structurally lightweight.

## What Changed

### Dashboard Became A Cleaner Portfolio Overview
- KPI cards were reduced and simplified so the page scans faster
- Recent activity now follows the grouped project-workspace activity pattern for consistency
- Activity-by-user uses a full-period backend summary instead of only paginated rows
- Pending approvals and overdue tasks are separated into clearer focused queues
- Heavy portfolio hero and insight framing were removed in favor of a tighter overview surface

### My Work Was Flattened And Cleaned Up
- Queue, Board, and Tasks views now use a lighter surface rhythm with less nested chrome
- Board status lanes align more closely with project workspace task states
- Task sorting and filtering presentation is clearer and less cramped
- The page stays focused on execution rather than duplicating broader planning surfaces

### Project Workspaces Expanded
- Projects now include a dedicated `Details` tab alongside the existing workspace rhythm
- Overview, Work, Milestones, Risks & Issues, Updates, and Files were refined for clearer scanning and more consistent detail-page patterns
- Project details now use the same two-column rhythm as task, milestone, and risk/issue pages, without adding unnecessary activity noise to the details surface
- Project create and edit now use a dedicated sectioned form instead of the generic shared fallback

### Core Project Record Now Supports A Sponsor
- Projects now store a `sponsor_user_id` alongside the project manager
- Project detail views and forms now expose sponsor clearly as a first-class field
- Existing installs receive a database update that adds the sponsor column safely during upgrade

## Why This Matters

- Portfolio review is clearer without forcing users through oversized dashboard chrome
- Daily execution feels lighter and more focused in My Work
- Project workspaces now feel more complete as true working surfaces rather than partial overview containers
- The core project record better matches common operational expectations by distinguishing the manager from the sponsor

## Upgrade Notes

- This release includes a database update for the new project sponsor field
- Direct upgrade from 1.2.1 is supported
- Existing projects remain compatible; the new sponsor field defaults to unassigned until populated

## Recommended Review After Upgrade

- Open Dashboard and confirm KPI cards, grouped recent activity, and side queues render correctly with real data
- Open My Work and verify Queue, Board, and Tasks still reflect the expected task states and filters
- Open at least one project workspace and confirm the new `Details` tab and inline project edit flow behave correctly
- Edit a project and verify sponsor, code, schedule dates, and close-out notes persist as expected