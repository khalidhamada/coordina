# Coordina v1.3.1 — Planning & File Experience Polish

**Release Date:** April 18, 2026

## Overview

Coordina 1.3.1 is a patch release focused on making key execution surfaces behave more cleanly and predictably. It fixes approval drawer routing, tightens the admin-shell drawer offset, improves Calendar reliability, compacts and aligns long-range Gantt timelines more effectively, and upgrades file surfaces with clearer cards and a richer drawer.

This release does not change the data model. It is about polish, correctness, and reducing friction in the places teams use every day.

## What Changed

### Approval And Drawer Behavior Was Tightened
- `Open source item` from approval drawers now routes correctly back to the linked work item
- Shared drawers now clear the WordPress admin bar so titles and close actions stay visible
- Project workspace Approvals now follows the milestone-style card treatment for better scanning
- Related activity links now show the linked item name instead of a generic item type label

### My Work And Calendar Were Smoothed Out
- My Work side-rail cards now use a more consistent card rhythm and cleaner visual separation
- Calendar next and previous navigation now works correctly again in both month and week modes
- Switching between month and week updates immediately without forcing an extra filter apply step
- Calendar day cells now use higher-contrast white surfaces for clearer planning review
- Removed extra instructional copy that was occupying space without adding much value

### Gantt Became More Compact And Accurate
- Removed extra helper framing and reduced row density so each work item reads more cleanly
- Work-item date information now sits on a single compact line instead of stacking vertically
- Longer project timelines automatically group into half-month or month periods to reduce horizontal sprawl
- Timeline columns remain aligned to true project date span so task and milestone placement stays accurate
- Hover tooltips now present item details in a cleaner styled format
- Workspace tabs and key planning actions now use WordPress Dashicons for a more native admin feel

### Files Are Easier To Scan And Act On
- Project and record-level files now render as clearer cards with file-type icons and direct download affordances
- Clicking the file name or edit affordance now opens a richer file drawer instead of dumping users into a generic flow
- File drawers now show readable file types, linked-item routing, and direct view, download, and delete actions
- Attach-file flows now use clearer naming and cleaner context handling

## Why This Matters

- Approval and drawer fixes reduce broken routing and shell-level friction during review work
- Calendar and Gantt now behave more like dependable planning tools instead of surfaces that require workaround clicks
- Long project timelines stay usable without excessive horizontal scroll pressure
- File handling feels more intentional and closer to the quality of the other project workspace surfaces

## Upgrade Notes

- No database changes are required in this release
- Direct upgrade from 1.3.0 is supported
- Existing projects, tasks, approvals, and files continue to work without migration steps

## Recommended Review After Upgrade

- Open an approval drawer and confirm `Open source item` routes back to the expected project, task, milestone, or related record
- Switch Calendar between month and week views, then use next and previous navigation to verify the period updates immediately
- Open a longer-span project in the Gantt tab and confirm the denser grouping still aligns bars correctly
- Open project and task file sections to verify file cards, file-type labels, and drawer actions with real attachments