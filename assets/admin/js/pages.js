(function () {
const app = window.CoordinaAdminApp || {};

if (!app.root || !app.state) {
	return;
}

const { state, modules, currentModule, escapeHtml, __, nice, dateLabel, todayKey, shiftDate, defaultCalendarFilters, defaultWorkloadFilters, getPageMeta, canAccessPage } = app;

function pageHeading(pageKey, actions, fallback) {
	void pageKey;
	void actions;
	void fallback;
	return '';
}

function iconLabel(type, label, className) {
	if (typeof app.iconLabel === 'function') {
		return app.iconLabel(type, label, className);
	}
	return escapeHtml(label || '');
}

function pageIconType(page) {
	const map = {
		'coordina-dashboard': 'dashboard',
		'coordina-my-work': 'my-work',
		'coordina-projects': 'project',
		'coordina-tasks': 'task',
		'coordina-requests': 'request',
		'coordina-approvals': 'approval',
		'coordina-calendar': 'calendar',
		'coordina-workload': 'workload',
		'coordina-risks-issues': 'risk-issue',
		'coordina-files-discussion': 'discussion',
		'coordina-settings': 'settings',
	};
	return map[String(page || '')] || 'dashboard';
}

function workspaceTabIconType(tabKey) {
	const map = {
		overview: 'overview',
		details: 'details',
		work: 'task',
		milestones: 'milestone',
		approvals: 'approval',
		gantt: 'gantt',
		calendar: 'calendar',
		files: 'file',
		discussion: 'discussion',
		'risks-issues': 'risk-issue',
		settings: 'settings',
	};
	return map[String(tabKey || '')] || 'dashboard';
}

function buttonLabel(type, label, className) {
	return iconLabel(type, label, className || 'coordina-button-label');
}

function openProjectButton(projectId, label, tab) {
	if (Number(projectId || 0) <= 0) {
		return `<span class="coordina-empty-inline">${escapeHtml(label || __('Standalone', 'coordina'))}</span>`;
	}
	return `<button class="coordina-link-button" data-action="open-route" data-page="coordina-projects" data-project-id="${projectId}" data-project-tab="${tab || 'overview'}">${iconLabel('project', label || __('Project workspace', 'coordina'))}</button>`;
}

function openTaskButton(taskId, label, projectId, projectTab) {
	return `<button class="coordina-link-button" data-action="open-task-page" data-id="${taskId}" data-project-id="${projectId || ''}" data-project-tab="${projectTab || ''}">${iconLabel('task', label || __('Task', 'coordina'))}</button>`;
}

function openMilestoneButton(milestoneId, label, projectId, projectTab) {
	return `<button class="coordina-link-button" data-action="open-milestone-page" data-id="${milestoneId}" data-project-id="${projectId || ''}" data-project-tab="${projectTab || ''}">${iconLabel('milestone', label || __('Milestone', 'coordina'))}</button>`;
}

function openRiskIssueButton(riskIssueId, label, projectId, projectTab) {
	return `<button class="coordina-link-button" data-action="open-risk-issue-page" data-id="${riskIssueId}" data-project-id="${projectId || ''}" data-project-tab="${projectTab || ''}">${iconLabel('risk-issue', label || __('Risk or issue', 'coordina'))}</button>`;
}

function modulePage() {
	const module = currentModule();
	const shell = state.shell || { statuses: {}, capabilities: {} };
	const items = state.collection && state.collection.items ? state.collection.items : [];
	const statuses = shell.statuses[module.statuses] || [];
	const options = statuses.map((status) => `<option value="${status}" ${state.filters.status === status ? 'selected' : ''}>${escapeHtml(nice(status))}</option>`).join('');
	const savedOptions = [`<option value="">${escapeHtml(__('Saved views', 'coordina'))}</option>`].concat(state.savedViews.map((view) => `<option value="${view.id}">${escapeHtml(view.view_name)}</option>`)).join('');
	const chips = statuses.slice(0, 4).map((status) => `<span class="coordina-summary-chip"><strong>${items.filter((item) => item.status === status).length}</strong>${escapeHtml(nice(status))}</span>`).join('');
	const caps = shell.capabilities || {};
	const canManageModule = module.key === 'projects' ? caps.canManageProjects : module.key === 'tasks' ? caps.canManageTasks : module.key === 'requests' ? caps.canManageRequests : ['risks-issues', 'milestones'].includes(module.key) ? caps.canManageProjects : true;
	const bulk = module.key !== 'projects' && state.selection.length && canManageModule && module.bulk ? `<div class="coordina-bulk-bar coordina-card"><span>${state.selection.length} ${escapeHtml(__('selected', 'coordina'))}</span><select name="bulk-status">${module.bulk.map((status) => `<option value="${status}">${escapeHtml(nice(status))}</option>`).join('')}</select><button class="button" data-action="bulk-status">${escapeHtml(__('Change status', 'coordina'))}</button></div>` : '';
	const canCreate = module.createEnabled !== false && canManageModule;
	const emptyAction = canCreate ? `<button class="button button-primary" data-action="open-create">${escapeHtml(__('Create now', 'coordina'))}</button>` : '';
	const empty = module.key === 'approvals'
		? `<section class="coordina-card coordina-empty-state"><h3>${escapeHtml(__('No approvals are waiting right now', 'coordina'))}</h3><p>${escapeHtml(__('Approvals appear here automatically when linked tasks, requests, projects, or other governed work needs a decision.', 'coordina'))}</p></section>`
		: `<section class="coordina-card coordina-empty-state"><h3>${escapeHtml(__('Nothing here yet', 'coordina'))}</h3><p>${escapeHtml(canCreate ? __('Create a record or adjust the current filters.', 'coordina') : __('Adjust the current filters or open the parent workspace to add new records.', 'coordina'))}</p>${emptyAction}</section>`;
	const pager = state.collection && state.collection.totalPages > 1 ? `<div class="coordina-pagination"><button class="button" data-action="page" data-page="${Math.max(1, state.collection.page - 1)}" ${state.collection.page <= 1 ? 'disabled' : ''}>${escapeHtml(__('Previous', 'coordina'))}</button><span>${state.collection.page} / ${state.collection.totalPages}</span><button class="button" data-action="page" data-page="${Math.min(state.collection.totalPages, state.collection.page + 1)}" ${state.collection.page >= state.collection.totalPages ? 'disabled' : ''}>${escapeHtml(__('Next', 'coordina'))}</button></div>` : '';
	const createButton = canCreate ? `<button class="button button-primary" data-action="open-create">${escapeHtml(__('New', 'coordina'))} ${escapeHtml(module.singular)}</button>` : '';
	const actions = `<button class="button" data-action="save-view">${escapeHtml(__('Save view', 'coordina'))}</button>${createButton}`;
	const listView = module.key === 'projects' && typeof app.renderProjectCards === 'function'
		? app.renderProjectCards(items, canManageModule)
		: app.renderTable(module, items, module.key);
	return `<section class="coordina-page">${pageHeading(state.page, actions, { title: module.title })}${app.renderFilterBar(module, options, savedOptions)}<div class="coordina-summary-row coordina-summary-row--subtle">${chips}</div>${bulk}${items.length ? listView : empty}${pager}</section>`;
}

function workspaceBoard() {
	const items = state.workspace && state.workspace.taskCollection ? state.workspace.taskCollection.items || [] : [];
	const shell = state.shell || {};
	const normalizeStatus = (status) => {
		const key = String(status || '').trim();
		if (!key) {
			return 'new';
		}
		if (key === 'not-started') {
			return 'new';
		}
		if (key === 'completed') {
			return 'done';
		}
		return key;
	};
	const configuredStatuses = Array.isArray(shell.statuses && shell.statuses.tasks) ? shell.statuses.tasks.map((status) => normalizeStatus(status)) : [];
	const extraStatuses = items.map((item) => normalizeStatus(item.status)).filter((status) => status && !configuredStatuses.includes(status));
	const statuses = Array.from(new Set(configuredStatuses.concat(extraStatuses).filter(Boolean)));
	const buckets = statuses.map((status) => ({ status, items: [] }));
	items.forEach((item) => {
		const bucket = buckets.find((candidate) => candidate.status === normalizeStatus(item.status));
		if (bucket) {
			bucket.items.push(item);
		}
	});
	return `<section class="coordina-board-grid">${buckets.map((bucket) => `<article class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(nice(bucket.status || 'new'))}</h3><p class="coordina-section-note">${escapeHtml(__('Tasks currently in this status are grouped here.', 'coordina'))}</p></div><span class="coordina-summary-chip"><strong>${bucket.items.length}</strong></span></div>${bucket.items.length ? `<ul class="coordina-work-list">${bucket.items.map((item) => `<li>${openTaskButton(item.id, item.title, item.project_id, item.project_id ? 'work' : '')}<div class="coordina-work-meta"><span class="coordina-status-badge status-${escapeHtml(item.status || 'new')}">${escapeHtml(nice(item.status || 'new'))}</span><span>${escapeHtml(item.assignee_label || __('Unassigned', 'coordina'))}</span><span>${escapeHtml(dateLabel(item.due_date))}</span><span>${escapeHtml(`${Number(item.completion_percent || 0)}% ${__('complete', 'coordina')}`)}</span></div></li>`).join('')}</ul>` : `<p class="coordina-empty-inline">${escapeHtml(__('No tasks in this status yet.', 'coordina'))}</p>`}</article>`).join('')}</section>`;
}

function workspaceWorkTab(taskSummary) {
	const items = state.workspace && state.workspace.taskCollection ? state.workspace.taskCollection.items || [] : [];
	const actions = state.workspace && state.workspace.actions ? state.workspace.actions : {};
	const groupLabel = nice((state.workspace && state.workspace.taskGroupLabel) || (state.shell && state.shell.taskGroupLabel) || 'stage');
	const view = state.workspaceView === 'board' ? 'board' : 'list';
	const addTaskButton = actions.canCreateTask ? `<button class="button button-primary" data-action="open-project-task-create">${escapeHtml(__('Add task', 'coordina'))}</button>` : '';
	const addGroupButton = actions.canCreateTaskGroup ? `<button class="button" data-action="open-task-group-create">${escapeHtml(__('Add', 'coordina'))} ${escapeHtml(groupLabel)}</button>` : '';
	const viewButtons = `<div class="coordina-action-bar__actions"><button class="button ${view === 'list' ? 'button-primary' : ''}" data-action="switch-work-view" data-view="list">${escapeHtml(__('List', 'coordina'))}</button><button class="button ${view === 'board' ? 'button-primary' : ''}" data-action="switch-work-view" data-view="board">${escapeHtml(__('Board', 'coordina'))}</button>${addGroupButton}${addTaskButton}</div>`;
	const summary = `<div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-summary-chip"><strong>${Number(taskSummary.total || 0)}</strong>${escapeHtml(__('Total', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(taskSummary.open || 0)}</strong>${escapeHtml(__('Open', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(taskSummary.blocked || 0)}</strong>${escapeHtml(__('Blocked', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(taskSummary.overdue || 0)}</strong>${escapeHtml(__('Overdue', 'coordina'))}</span></div>`;
	const emptyAction = actions.canCreateTask ? `<button class="button button-primary" data-action="open-project-task-create">${escapeHtml(__('Add task', 'coordina'))}</button>` : '';
	const list = items.length ? workspaceGroupedTaskList(items) : `<section class="coordina-empty-state"><h3>${escapeHtml(__('No project work yet', 'coordina'))}</h3><p>${escapeHtml(__('Linked tasks you can access will appear here.', 'coordina'))}</p>${emptyAction}</section>`;
	const body = view === 'board' ? workspaceBoard() : list;

	return `<section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Project work', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See tasks, progress, blockers, and completion across this project.', 'coordina'))}</p></div>${viewButtons}</div>${summary}${body}</section>`;
}

function workspaceGroupedTaskList(items) {
	const groups = state.workspace && state.workspace.taskGroups ? state.workspace.taskGroups : [];
	const actions = state.workspace && state.workspace.actions ? state.workspace.actions : {};
	const canManageTaskGroups = !!actions.canCreateTaskGroup;
	const groupBuckets = groups.map((group) => Object.assign({}, group, { items: [] }));
	const ungrouped = { id: 0, title: __('Ungrouped', 'coordina'), items: [] };
	items.forEach((item) => {
		const group = groupBuckets.find((candidate) => Number(candidate.id) === Number(item.task_group_id || 0));
		(group || ungrouped).items.push(item);
	});
	const buckets = groupBuckets.concat(ungrouped).filter((group) => group.items.length || Number(group.id) > 0);
	return `<div class="coordina-task-groups"><div class="coordina-task-group-head"><span>${escapeHtml(__('Task', 'coordina'))}</span><span>${escapeHtml(__('State', 'coordina'))}</span><span>${escapeHtml(__('Owner', 'coordina'))}</span><span>${escapeHtml(__('Due', 'coordina'))}</span><span>${escapeHtml(__('Checklist', 'coordina'))}</span><span>${escapeHtml(__('Completion', 'coordina'))}</span></div>${buckets.map((group) => {
		const total = group.items.length;
		const doneCount = group.items.filter((item) => ['done', 'cancelled'].includes(String(item.status || ''))).length;
		const completion = total > 0 ? Math.round((doneCount / total) * 100) : 0;
		const groupActions = canManageTaskGroups && Number(group.id || 0) > 0 ? `<div class="coordina-row-actions"><button class="button button-small" data-action="open-task-group-edit" data-id="${group.id}" data-title="${escapeHtml(group.title || '')}">${escapeHtml(__('Edit', 'coordina'))}</button><button class="button button-small button-link-delete" data-action="delete-task-group" data-id="${group.id}" data-title="${escapeHtml(group.title || '')}">${escapeHtml(__('Delete', 'coordina'))}</button></div>` : '';
		return `<section class="coordina-task-group"><div class="coordina-section-header"><div><h4>${escapeHtml(group.title)}</h4></div><div class="coordina-task-group__header-side">${groupActions}${progressBar(completion, __('Task group completion', 'coordina'))}</div></div>${group.items.length ? `<div class="coordina-task-group-rows">${group.items.map((item) => {
		const summary = item.checklist_summary || {};
		const total = Number(summary.total || 0);
		const checklist = total > 0 ? `${Number(summary.done || 0)} / ${total}` : __('None', 'coordina');
		return `<div class="coordina-task-group-row"><div class="coordina-task-group-row__title">${openTaskButton(item.id, item.title, item.project_id, item.project_id ? 'work' : '')}${item.blocked ? `<span class="coordina-status-badge status-blocked">${escapeHtml(__('Blocked', 'coordina'))}</span>` : ''}</div><div><span class="coordina-status-badge status-${escapeHtml(item.status)}">${escapeHtml(nice(item.status))}</span></div><div>${escapeHtml(item.assignee_label || __('Unassigned', 'coordina'))}</div><div>${escapeHtml(dateLabel(item.due_date))}</div><div>${escapeHtml(checklist)}</div><div>${escapeHtml(`${Number(item.completion_percent || 0)}%`)}</div></div>`;
	}).join('')}</div>` : `<p class="coordina-empty-inline">${escapeHtml(__('No tasks in this group yet.', 'coordina'))}</p>`}</section>`;
	}).join('')}</div>`;
}

function ganttDateKey(value) {
	return String(value || '').slice(0, 10);
}

function ganttDateValue(value) {
	const key = ganttDateKey(value);
	if (!key) {
		return null;
	}
	const date = new Date(`${key}T12:00:00`);
	return Number.isNaN(date.getTime()) ? null : date;
}

function ganttDayDiff(startValue, endValue) {
	const start = ganttDateValue(startValue);
	const end = ganttDateValue(endValue);
	if (!start || !end) {
		return 0;
	}
	return Math.round((end.getTime() - start.getTime()) / 86400000);
}

function ganttPosition(range, startValue, endValue) {
	const start = ganttDateValue(startValue);
	const end = ganttDateValue(endValue || startValue);
	if (!ganttDateValue(range && range.start) || !ganttDateValue(range && range.end) || !start || !end) {
		return { left: 0, width: 0, marker: 0 };
	}
	const safeEnd = end.getTime() < start.getTime() ? start : end;
	const totalDays = Math.max(1, ganttDayDiff(range.start, range.end) + 1);
	const offsetDays = Math.max(0, ganttDayDiff(range.start, startValue));
	const spanDays = Math.max(1, ganttDayDiff(startValue, endValue || startValue) + 1);
	const left = Math.max(0, Math.min(100, (offsetDays / totalDays) * 100));
	const width = Math.max(1.5, Math.min(100 - left, (spanDays / totalDays) * 100));
	const marker = Math.max(0, Math.min(100, ((offsetDays + 0.5) / totalDays) * 100));
	return { left, width, marker };
}

function ganttCompactDateLabel(value) {
	const date = ganttDateValue(value);
	if (!date) {
		return '';
	}
	try {
		return new Intl.DateTimeFormat(document.documentElement.lang || undefined, { month: 'short', day: 'numeric' }).format(date);
	} catch (error) {
		return dateLabel(value);
	}
}

function workspaceGanttPrimaryAction(row) {
	if (row.type === 'milestone') {
		return openMilestoneButton(row.recordId, row.title, state.projectContext.id, 'milestones');
	}
	return openTaskButton(row.recordId, row.title, state.projectContext.id, 'work');
}

function workspaceGanttDateSummary(row) {
	const start = ganttCompactDateLabel(row.startDate || '') || dateLabel(row.startDate || '');
	const end = ganttCompactDateLabel(row.endDate || row.startDate || '') || dateLabel(row.endDate || row.startDate || '');
	if (start && end && start !== end) {
		return `${start} - ${end}`;
	}
	return start || end || __('No dates', 'coordina');
}

function workspaceGanttDateColumn(row) {
	return `<span class="coordina-gantt__date-range">${escapeHtml(workspaceGanttDateSummary(row))}</span>`;
}

function workspaceGanttPeriodMinWidth(grouping) {
	if (grouping === 'month') {
		return 62;
	}
	if (grouping === 'half-month') {
		return 56;
	}
	return 68;
}

function workspaceGanttGridTemplate(periods) {
	return periods.length ? periods.map((period) => `${Math.max(1, Number(period.spanDays || 1))}fr`).join(' ') : 'minmax(0, 1fr)';
}

function workspaceGanttTimelineWidth(periods, grouping) {
	return Math.max(540, Math.max(periods.length, 1) * workspaceGanttPeriodMinWidth(grouping));
}

function workspaceGanttTooltip(row) {
	const details = [
		row.title || __('Work item', 'coordina'),
		`${__('Type', 'coordina')}: ${row.type === 'milestone' ? __('Milestone', 'coordina') : __('Task', 'coordina')}`,
		`${__('Status', 'coordina')}: ${nice(row.status || (row.type === 'milestone' ? 'planned' : 'new'))}`,
		`${__('Owner', 'coordina')}: ${row.ownerLabel || (row.type === 'milestone' ? __('No owner assigned', 'coordina') : __('Unassigned', 'coordina'))}`,
		`${__('Dates', 'coordina')}: ${workspaceGanttDateSummary(row)}`,
	];
	if (row.type === 'task') {
		details.push(`${__('Completion', 'coordina')}: ${Math.max(0, Math.min(100, Number(row.completion || 0)))}%`);
	}
	if (row.blocked) {
		details.push(__('Blocked', 'coordina'));
	}
	if (row.dependencyFlag) {
		details.push(__('Dependency', 'coordina'));
	}
	return details.join('\n');
}

function workspaceGanttTooltipTarget(className, style, tooltip, body) {
	return `<span class="coordina-gantt__hint-target ${className}" style="${style}" data-tooltip="${escapeHtml(tooltip)}" tabindex="0" aria-label="${escapeHtml(tooltip)}">${body}</span>`;
}

function workspaceGanttTrack(range, periods, row, timelineWidth, gridTemplate) {
	const position = ganttPosition(range, row.startDate, row.endDate);
	const today = ganttPosition(range, range && range.today, range && range.today);
	const showToday = !!(range && range.today && range.start && range.end && range.today >= range.start && range.today <= range.end);
	const tone = row.isDone ? 'done' : row.blocked ? 'blocked' : row.isOverdue ? 'overdue' : row.type === 'milestone' ? 'milestone' : 'active';
	const tooltip = workspaceGanttTooltip(row);
	const gridStyle = `style="grid-template-columns:${gridTemplate};min-width:${timelineWidth}px"`;
	const periodCells = periods.map((period, index) => `<span class="coordina-gantt__week-cell ${index === 0 || (periods[index - 1] && periods[index - 1].start.slice(0, 7) !== period.start.slice(0, 7)) ? 'is-month-start' : ''}"></span>`).join('');
	if (row.type === 'milestone') {
		return `<div class="coordina-gantt__track" style="min-width:${timelineWidth}px"><div class="coordina-gantt__track-grid" ${gridStyle}>${periodCells}</div>${showToday ? `<span class="coordina-gantt__today-line" style="left:${today.marker}%"></span>` : ''}${workspaceGanttTooltipTarget(`coordina-gantt__hint-target--marker tone-${escapeHtml(tone)}`, `left:${position.marker}%`, tooltip, `<span class="coordina-gantt__marker tone-${escapeHtml(tone)}"></span>`)}</div>`;
	}
	return `<div class="coordina-gantt__track" style="min-width:${timelineWidth}px"><div class="coordina-gantt__track-grid" ${gridStyle}>${periodCells}</div>${showToday ? `<span class="coordina-gantt__today-line" style="left:${today.marker}%"></span>` : ''}${workspaceGanttTooltipTarget(`coordina-gantt__hint-target--bar tone-${escapeHtml(tone)}`, `left:${position.left}%;width:${position.width}%`, tooltip, `<span class="coordina-gantt__bar tone-${escapeHtml(tone)}"></span>`)}</div>`;
}

function workspaceGanttTab() {
	const gantt = state.workspace && state.workspace.ganttData ? state.workspace.ganttData : { range: {}, periods: [], weeks: [], groups: [], summary: {}, unscheduledTasks: [], projectFrame: {} };
	const actions = state.workspace && state.workspace.actions ? state.workspace.actions : {};
	const summary = gantt.summary || {};
	const periods = gantt.periods || gantt.weeks || [];
	const grouping = gantt.grouping || 'week';
	const groups = gantt.groups || [];
	const unscheduled = gantt.unscheduledTasks || [];
	const projectFrame = gantt.projectFrame || {};
	const toolbar = `<div class="coordina-action-bar__actions">${actions.canCreateTask ? `<button class="button button-primary" data-action="open-project-task-create">${buttonLabel('task', __('Add task', 'coordina'))}</button>` : ''}${actions.canCreateMilestone ? `<button class="button" data-action="open-project-milestone-create">${buttonLabel('milestone', __('Add milestone', 'coordina'))}</button>` : ''}<button class="button" data-action="switch-project-tab" data-tab="work">${buttonLabel('task', __('Open work', 'coordina'))}</button><button class="button" data-action="switch-project-tab" data-tab="milestones">${buttonLabel('milestone', __('Open milestones', 'coordina'))}</button></div>`;
	const summaryRow = `<div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-summary-chip"><strong>${Number(summary.scheduledTasks || 0)}</strong>${escapeHtml(__('Scheduled tasks', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(summary.milestones || 0)}</strong>${escapeHtml(__('Milestones', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(summary.unscheduledTasks || 0)}</strong>${escapeHtml(__('Unscheduled tasks', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(summary.blockedTasks || 0)}</strong>${escapeHtml(__('Blocked', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(summary.overdueItems || 0)}</strong>${escapeHtml(__('Overdue', 'coordina'))}</span></div>`;
	const frame = `<div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-status-badge">${escapeHtml(__('Project start', 'coordina'))}: ${escapeHtml(dateLabel(projectFrame.start || ''))}</span><span class="coordina-status-badge">${escapeHtml(__('Target end', 'coordina'))}: ${escapeHtml(dateLabel(projectFrame.target || ''))}</span><span class="coordina-status-badge">${escapeHtml(__('Actual end', 'coordina'))}: ${escapeHtml(dateLabel(projectFrame.end || ''))}</span></div>`;
	if (!groups.length) {
		const emptyAction = actions.canCreateTask ? `<button class="button button-primary" data-action="open-project-task-create">${escapeHtml(__('Add task', 'coordina'))}</button>` : '';
		const unscheduledBody = unscheduled.length ? `<ul class="coordina-work-list coordina-work-list--compact">${unscheduled.map((item) => `<li>${workspaceGanttPrimaryAction(item)}<div class="coordina-work-meta"><span class="coordina-status-badge status-${escapeHtml(item.status || 'new')}">${escapeHtml(nice(item.status || 'new'))}</span><span>${escapeHtml(item.ownerLabel || __('Unassigned', 'coordina'))}</span></div></li>`).join('')}</ul>` : `<p class="coordina-empty-inline">${escapeHtml(__('Tasks without dates will appear here until they are scheduled.', 'coordina'))}</p>`;
		return `<div class="coordina-columns"><section class="coordina-card coordina-card--wide"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Project timeline', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See task timing and milestone dates across the project.', 'coordina'))}</p></div>${toolbar}</div>${summaryRow}${frame}<section class="coordina-empty-state"><h3>${escapeHtml(__('No scheduled work to plot yet', 'coordina'))}</h3><p>${escapeHtml(__('Add start dates, due dates, or milestones to show work on the timeline.', 'coordina'))}</p>${emptyAction}</section></section><section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Needs scheduling', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('These tasks do not have a start date or due date yet.', 'coordina'))}</p></div></div>${unscheduledBody}</section></div>`;
	}

	const timelineWidth = workspaceGanttTimelineWidth(periods, grouping);
	const gridTemplate = workspaceGanttGridTemplate(periods);
	const layoutStyle = `style="grid-template-columns:minmax(220px, 280px) minmax(118px, 140px) minmax(${timelineWidth}px, 1fr)"`;
	const timelineStyle = `style="grid-template-columns:${gridTemplate};min-width:${timelineWidth}px"`;
	const headerWeeks = periods.map((period, index) => {
		const isMonthStart = index === 0 || (periods[index - 1] && periods[index - 1].start.slice(0, 7) !== period.start.slice(0, 7));
		return `<div class="coordina-gantt__week-head ${isMonthStart ? 'is-month-start' : ''}"><strong>${escapeHtml(period.label || '')}</strong><span>${escapeHtml(period.secondaryLabel || '')}</span></div>`;
	}).join('');
	const groupMarkup = groups.map((group) => `<section class="coordina-gantt__group"><div class="coordina-gantt__group-head"><strong>${escapeHtml(group.title || __('Workstream', 'coordina'))}</strong><span class="coordina-summary-chip"><strong>${Number((group.rows || []).length)}</strong>${escapeHtml(__('Items', 'coordina'))}</span></div>${(group.rows || []).map((row) => `<article class="coordina-gantt__row" ${layoutStyle}><div class="coordina-gantt__label"><div class="coordina-gantt__label-line">${workspaceGanttPrimaryAction(row)}</div></div><div class="coordina-gantt__schedule-cell">${workspaceGanttDateColumn(row)}</div>${workspaceGanttTrack(gantt.range || {}, periods, row, timelineWidth, gridTemplate)}</article>`).join('')}</section>`).join('');
	const unscheduledCard = `<section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Needs scheduling', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Add dates here to place these items on the timeline.', 'coordina'))}</p></div></div>${unscheduled.length ? `<ul class="coordina-work-list coordina-work-list--compact">${unscheduled.map((item) => `<li>${workspaceGanttPrimaryAction(item)}<div class="coordina-work-meta"><span class="coordina-status-badge status-${escapeHtml(item.status || 'new')}">${escapeHtml(nice(item.status || 'new'))}</span><span>${escapeHtml(item.ownerLabel || __('Unassigned', 'coordina'))}</span></div></li>`).join('')}</ul>` : `<p class="coordina-empty-inline">${escapeHtml(__('Everything in this workspace has at least one planning date.', 'coordina'))}</p>`}</section>`;
	return `<div class="coordina-gantt-layout"><section class="coordina-card coordina-card--wide coordina-gantt-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Project timeline', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See task spans, milestones, and today\'s position across the project.', 'coordina'))}</p></div>${toolbar}</div>${summaryRow}${frame}<div class="coordina-gantt-shell"><div class="coordina-gantt__header" ${layoutStyle}><div class="coordina-gantt__sidebar-head">${escapeHtml(__('Work item', 'coordina'))}</div><div class="coordina-gantt__date-head">${escapeHtml(__('Dates', 'coordina'))}</div><div class="coordina-gantt__timeline-head" ${timelineStyle}>${headerWeeks}</div></div><div class="coordina-gantt__body">${groupMarkup}</div></div></section><div class="coordina-gantt-layout__secondary">${unscheduledCard}</div></div>`;
}

function plainText(value) {
	return String(value || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
}

function shortText(value, maxLength) {
	const text = plainText(value);
	if (text.length <= maxLength) {
		return text;
	}
	return `${text.slice(0, Math.max(0, maxLength - 3)).trim()}...`;
}

function meaningfulText(value) {
	const text = plainText(value);
	if (!text) {
		return '';
	}
	const normalized = text.toLowerCase();
	return ['0', 'false', 'null', 'undefined', 'none', 'n/a'].includes(normalized) ? '' : text;
}

function progressBar(percent, label) {
	const safePercent = Math.max(0, Math.min(100, Number(percent || 0)));
	return `<div class="coordina-progress" aria-label="${escapeHtml(label || '')}"><span class="coordina-progress__track"><span class="coordina-progress__fill" style="width:${safePercent}%"></span></span><strong class="coordina-progress__value">${safePercent}%</strong></div>`;
}

function workspaceCardDisplayPrefs() {
	const shell = state.shell || {};
	return {
		showGuidance: shell.myWorkCardGuidanceEnabled !== false,
		showActions: shell.myWorkCardActionsEnabled !== false,
	};
}

function workspaceItemDescription(text, emptyMessage, maxLength = 160) {
	return shortText(text || '', maxLength) || emptyMessage;
}

function overviewSection(title, note, body, action) {
	return `<section class="coordina-project-overview-section"><div class="coordina-section-header"><div><h4>${escapeHtml(title)}</h4><p class="coordina-section-note">${escapeHtml(note)}</p></div>${action || ''}</div>${body}</section>`;
}

function overviewBarChart(series, emptyMessage, options) {
	const config = options || {};
	if (!(series || []).length) {
		return `<p class="coordina-empty-inline">${escapeHtml(emptyMessage)}</p>`;
	}
	const percentScale = config.scale === 'percent';
	const max = percentScale ? 100 : Math.max(1, ...(series || []).map((item) => Number(item.value || 0)));
	return `<div class="coordina-overview-chart">${(series || []).map((item) => {
		const numericValue = Number(item.value || 0);
		const width = percentScale ? Math.max(0, Math.min(100, numericValue)) : Math.max(10, Math.round((numericValue / max) * 100));
		const valueLabel = typeof item.valueLabel !== 'undefined' ? item.valueLabel : (percentScale ? `${Math.round(numericValue)}%` : `${numericValue}`);
		return `<div class="coordina-overview-chart__row"><div class="coordina-overview-chart__row-head"><span>${escapeHtml(item.label || '')}</span><strong>${escapeHtml(valueLabel)}</strong></div><span class="coordina-overview-chart__row-bar"><span style="width:${width}%"></span></span></div>`;
	}).join('')}</div>`;
}

function countSeries(items, keyFn, labelFn) {
	const counts = (items || []).reduce((carry, item) => {
		const key = keyFn(item);
		if (!key) {
			return carry;
		}
		carry[key] = (carry[key] || 0) + 1;
		return carry;
	}, {});
	return Object.keys(counts).map((key) => ({
		label: labelFn(key),
		value: counts[key],
		valueLabel: `${counts[key]}`,
	})).sort((left, right) => Number(right.value || 0) - Number(left.value || 0));
}

function projectTaskGroupCompletionSeries(items, groups) {
	const groupBuckets = (groups || []).map((group) => Object.assign({}, group, { items: [] }));
	const ungrouped = { id: 0, title: __('Ungrouped', 'coordina'), items: [] };
	(items || []).forEach((item) => {
		const group = groupBuckets.find((candidate) => Number(candidate.id) === Number(item.task_group_id || 0));
		(group || ungrouped).items.push(item);
	});
	return groupBuckets.concat(ungrouped).filter((group) => group.items.length > 0).map((group) => {
		const done = group.items.filter((item) => ['done', 'cancelled'].includes(String(item.status || ''))).length;
		const completion = group.items.length ? Math.round((done / group.items.length) * 100) : 0;
		return { label: group.title || __('Untitled group', 'coordina'), value: completion, valueLabel: `${completion}%` };
	}).sort((left, right) => String(left.label || '').localeCompare(String(right.label || '')));
}

function projectChecklistCompletionSeries(collection) {
	const data = normalizeChecklistCollection(collection || {}, { summary: { total: 0, done: 0, open: 0 } });
	return (data.checklists || []).map((checklist) => {
		const summary = checklist.summary || { total: 0, done: 0 };
		const completion = Number(summary.total || 0) ? Math.round((Number(summary.done || 0) / Number(summary.total || 1)) * 100) : 0;
		return { label: checklist.title || __('Checklist', 'coordina'), value: completion, valueLabel: `${completion}%` };
	});
}

function projectMilestoneCompletionSeries(items) {
	return (items || []).map((item) => ({
		label: item.title || __('Milestone', 'coordina'),
		value: Math.max(0, Math.min(100, Number(item.completion_percent || 0))),
		valueLabel: `${Math.max(0, Math.min(100, Number(item.completion_percent || 0)))}%`,
	}));
}

function projectRiskTypeSeries(items) {
	return countSeries(items, (item) => String(item.object_type || 'risk'), (key) => nice(key));
}

function projectUpdateUserSeries(items) {
	return countSeries(items, (item) => String(item.created_by_label || __('System', 'coordina')), (key) => key);
}

function projectFileTypeSeries(items) {
	return countSeries(items, (item) => String(item.object_type || 'project'), (key) => nice(key));
}

function projectOverviewInfoSection(project, overview, taskSummary) {
	const projectSettings = state.workspace && state.workspace.projectSettings ? state.workspace.projectSettings : {};
	const members = Array.isArray(projectSettings.members) ? projectSettings.members : [];
	const metrics = overview && overview.metrics ? overview.metrics : {};
	const description = plainText(project.description || '');
	const teamMarkup = members.length
		? `<div class="coordina-summary-row coordina-summary-row--subtle">${members.map((member) => `<span class="coordina-status-badge">${escapeHtml(member.user_label || __('Team member', 'coordina'))}</span>`).join('')}</div>`
		: `<p class="coordina-empty-inline">${escapeHtml(__('No team members are assigned yet.', 'coordina'))}</p>`;
	return `<section class="coordina-card coordina-project-overview-core"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Project overview', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See the core project context before scanning progress across work areas.', 'coordina'))}</p></div></div><div class="coordina-project-overview-core__layout"><div class="coordina-project-overview-core__main"><section class="coordina-task-page__overview-section"><h4>${escapeHtml(__('Name', 'coordina'))}</h4><p class="coordina-task-page__lead">${escapeHtml(project.title || __('Project', 'coordina'))}</p></section><section class="coordina-task-page__overview-section"><h4>${escapeHtml(__('Description', 'coordina'))}</h4>${description ? `<p class="coordina-task-page__description">${escapeHtml(description)}</p>` : `<p class="coordina-empty-inline">${escapeHtml(__('No project description has been added yet.', 'coordina'))}</p>`}</section><section class="coordina-task-page__overview-section"><h4>${escapeHtml(__('Team members', 'coordina'))}</h4>${teamMarkup}</section></div><div class="coordina-project-overview-core__side"><dl class="coordina-key-value"><div><dt>${escapeHtml(__('Start date', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(overview.timeline && overview.timeline.start))}</dd></div><div><dt>${escapeHtml(__('Target end', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(overview.timeline && overview.timeline.target))}</dd></div><div><dt>${escapeHtml(__('Actual end', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(overview.timeline && overview.timeline.end))}</dd></div><div><dt>${escapeHtml(__('Project manager', 'coordina'))}</dt><dd>${escapeHtml(project.manager_label || __('Unassigned', 'coordina'))}</dd></div><div><dt>${escapeHtml(__('Overall progress', 'coordina'))}</dt><dd>${escapeHtml(`${Number(metrics.completionPercent || taskSummary.completion || 0)}%`)}</dd></div><div><dt>${escapeHtml(__('Project health', 'coordina'))}</dt><dd>${escapeHtml(nice(project.health || 'neutral'))}</dd></div><div><dt>${escapeHtml(__('Status', 'coordina'))}</dt><dd>${escapeHtml(nice(project.status || 'draft'))}</dd></div><div><dt>${escapeHtml(__('Priority', 'coordina'))}</dt><dd>${escapeHtml(nice(project.priority || 'normal'))}</dd></div></dl>${overview.healthSummary ? `<p class="coordina-project-overview-core__summary">${escapeHtml(overview.healthSummary)}</p>` : ''}</div></div></section>`;
}

function workspaceOverviewTaskGroups(items, groups) {
	const groupBuckets = (groups || []).map((group) => Object.assign({}, group, { items: [] }));
	const ungrouped = { id: 0, title: __('Ungrouped', 'coordina'), items: [] };
	(items || []).forEach((item) => {
		const group = groupBuckets.find((candidate) => Number(candidate.id) === Number(item.task_group_id || 0));
		(group || ungrouped).items.push(item);
	});
	const buckets = groupBuckets.concat(ungrouped).filter((group) => group.items.length > 0);

	if (!buckets.length) {
		return `<p class="coordina-empty-inline">${escapeHtml(__('No linked project tasks are available yet.', 'coordina'))}</p>`;
	}

	return `<div class="coordina-project-overview-groups">${buckets.map((group) => {
		const total = group.items.length;
		const done = group.items.filter((item) => String(item.status || '') === 'done').length;
		const completion = total > 0 ? Math.round((done / total) * 100) : 0;

		return `<article class="coordina-project-overview-group"><div class="coordina-section-header"><div><h5>${escapeHtml(group.title)}</h5></div>${progressBar(completion, __('Task group completion', 'coordina'))}</div><ul class="coordina-work-list coordina-work-list--compact coordina-project-overview-list">${group.items.map((item) => {
			const summary = item.checklist_summary || {};
			const totalChecklist = Number(summary.total || 0);
			const checklist = totalChecklist > 0 ? `${Number(summary.done || 0)} / ${totalChecklist} ${__('checklist', 'coordina')}` : __('No checklist', 'coordina');
			return `<li>${openTaskButton(item.id, item.title, item.project_id, item.project_id ? 'work' : '')}<div class="coordina-work-meta"><span class="coordina-status-badge status-${escapeHtml(item.status)}">${escapeHtml(nice(item.status || 'new'))}</span>${item.blocked ? `<span class="coordina-status-badge status-blocked">${escapeHtml(__('Blocked', 'coordina'))}</span>` : ''}<span>${escapeHtml(item.assignee_label || __('Unassigned', 'coordina'))}</span><span>${escapeHtml(dateLabel(item.due_date))}</span><span>${escapeHtml(checklist)}</span></div></li>`;
		}).join('')}</ul></article>`;
	}).join('')}</div>`;
}

function workspaceOverviewMilestones(items) {
	if (!(items || []).length) {
		return `<p class="coordina-empty-inline">${escapeHtml(__('No milestones are attached to this project yet.', 'coordina'))}</p>`;
	}

	return `<ul class="coordina-work-list coordina-work-list--compact coordina-project-overview-list">${items.map((item) => `<li>${openMilestoneButton(item.id, item.title, item.project_id, item.project_id ? 'milestones' : '')}<p class="coordina-work-item-note">${escapeHtml(shortText(item.notes || '', 160) || __('No milestone notes yet.', 'coordina'))}</p><div class="coordina-work-meta"><span class="coordina-status-badge status-${escapeHtml(item.status)}">${escapeHtml(nice(item.status || 'planned'))}</span><span>${escapeHtml(item.owner_label || __('No owner assigned', 'coordina'))}</span><span>${escapeHtml(dateLabel(item.due_date))}</span><span>${escapeHtml(`${Number(item.completion_percent || 0)}% ${__('complete', 'coordina')}`)}</span>${item.dependency_flag ? `<span class="coordina-status-badge">${escapeHtml(__('Dependency', 'coordina'))}</span>` : ''}</div></li>`).join('')}</ul>`;
}

function workspaceOverviewRisks(items) {
	if (!(items || []).length) {
		return `<p class="coordina-empty-inline">${escapeHtml(__('No risks or issues are linked to this project right now.', 'coordina'))}</p>`;
	}

	return `<ul class="coordina-work-list coordina-work-list--compact coordina-project-overview-list">${items.map((item) => `<li><button class="coordina-link-button" data-action="open-record" data-module="risks-issues" data-id="${item.id}">${escapeHtml(item.title)}</button><p class="coordina-work-item-note">${escapeHtml(shortText(item.mitigation_plan || item.description || '', 140) || __('No mitigation plan has been added yet.', 'coordina'))}</p><div class="coordina-work-meta"><span class="coordina-status-badge status-${escapeHtml(item.status)}">${escapeHtml(nice(item.status || 'identified'))}</span><span class="coordina-status-badge">${escapeHtml(nice(item.object_type || 'risk'))}</span><span class="coordina-status-badge">${escapeHtml(nice(item.severity || 'medium'))}</span><span>${escapeHtml(item.owner_label || __('No owner assigned', 'coordina'))}</span><span>${escapeHtml(dateLabel(item.target_resolution_date))}</span></div></li>`).join('')}</ul>`;
}

function workspaceOverviewApprovals(items) {
	if (!(items || []).length) {
		return `<p class="coordina-empty-inline">${escapeHtml(__('No approvals are linked to this project right now.', 'coordina'))}</p>`;
	}

	return `<ul class="coordina-work-list coordina-work-list--compact coordina-project-overview-list">${items.map((item) => `<li><button class="coordina-link-button" data-action="open-record" data-module="approvals" data-id="${item.id}">${escapeHtml(item.object_label || nice(item.object_type || 'approval'))}</button><div class="coordina-work-meta"><span class="coordina-status-badge status-${escapeHtml(item.status)}">${escapeHtml(nice(item.status || 'pending'))}</span><span>${escapeHtml(item.approver_label || __('No approver assigned', 'coordina'))}</span><span>${escapeHtml(item.submitted_by_label || __('Unknown submitter', 'coordina'))}</span><span>${escapeHtml(dateLabel(item.submitted_at))}</span></div></li>`).join('')}</ul>`;
}

function workspaceOverviewFiles(items) {
	if (!(items || []).length) {
		return `<p class="coordina-empty-inline">${escapeHtml(__('No files are attached to this project yet.', 'coordina'))}</p>`;
	}

	return `<ul class="coordina-work-list coordina-work-list--compact coordina-project-overview-list">${items.map((item) => `<li><button class="coordina-link-button" data-action="open-record" data-module="files" data-id="${item.id}">${escapeHtml(item.file_name || item.attachment_title || __('Attached file', 'coordina'))}</button><div class="coordina-work-meta"><span>${escapeHtml(nice(item.object_type || 'project'))}</span><span>${escapeHtml(item.object_label || __('Project', 'coordina'))}</span><span>${escapeHtml(item.created_by_label || __('Unknown uploader', 'coordina'))}</span><span>${escapeHtml(dateLabel(item.created_at))}</span></div></li>`).join('')}</ul>`;
}

function workspaceOverviewUpdates(items) {
	if (!(items || []).length) {
		return `<p class="coordina-empty-inline">${escapeHtml(__('No updates have been posted on this project yet.', 'coordina'))}</p>`;
	}

	return `<ul class="coordina-work-list coordina-work-list--compact coordina-project-overview-list">${items.map((item) => `<li><button class="coordina-link-button" data-action="open-record" data-module="discussions" data-id="${item.id}">${escapeHtml(item.object_label || __('Project update', 'coordina'))}</button><p class="coordina-work-item-note">${escapeHtml(shortText(item.excerpt || item.body || '', 160) || __('No update summary is available.', 'coordina'))}</p><div class="coordina-work-meta"><span>${escapeHtml(nice(item.object_type || 'project'))}</span><span>${escapeHtml(item.created_by_label || __('Unknown author', 'coordina'))}</span><span>${escapeHtml(dateLabel(item.created_at))}</span></div></li>`).join('')}</ul>`;
}

function workspaceFullProjectOverview(project, overview, taskSummary) {
	const projectOverview = state.workspace && state.workspace.projectOverview ? state.workspace.projectOverview : {};
	const taskGroups = state.workspace && state.workspace.taskGroups ? state.workspace.taskGroups : [];
	const milestoneSummary = state.workspace && state.workspace.milestoneSummary ? state.workspace.milestoneSummary : {};
	const riskSummary = state.workspace && state.workspace.riskIssueSummary ? state.workspace.riskIssueSummary : {};
	const approvalSummary = state.workspace && state.workspace.approvalSummary ? state.workspace.approvalSummary : {};
	const fileSummary = state.workspace && state.workspace.fileSummary ? state.workspace.fileSummary : {};
	const discussionSummary = state.workspace && state.workspace.discussionSummary ? state.workspace.discussionSummary : {};
	const projectChecklist = state.workspace && state.workspace.projectChecklist ? state.workspace.projectChecklist : { items: [], summary: { total: 0, done: 0, open: 0 }, permissions: { canManage: false, canToggle: false }, object_type: 'project', object_id: project.id, object_label: project.title || __('Project', 'coordina') };
	const metrics = overview && overview.metrics ? overview.metrics : {};
	const taskItems = projectOverview.tasks || [];
	const milestoneItems = projectOverview.milestones || [];
	const riskItems = projectOverview.risksIssues || [];
	const approvalItems = projectOverview.approvals || [];
	const fileItems = projectOverview.files || [];
	const updateItems = projectOverview.updates || [];
	const description = shortText(project.description || '', 420);

	return `<section class="coordina-card coordina-card--wide coordina-project-overview-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Project overview', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See the main project details, work, milestones, and supporting records in one place.', 'coordina'))}</p></div><button class="button button-small" data-action="switch-project-tab" data-tab="work">${escapeHtml(__('Open work tab', 'coordina'))}</button></div><div class="coordina-project-overview-hero"><div class="coordina-project-overview-hero__main"><h4>${escapeHtml(project.title || __('Project workspace', 'coordina'))}</h4>${description ? `<p>${escapeHtml(description)}</p>` : ''}<div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-status-badge status-${escapeHtml(project.status || 'draft')}">${escapeHtml(nice(project.status || 'draft'))}</span><span class="coordina-status-badge status-${escapeHtml(project.health || 'neutral')}">${escapeHtml(nice(project.health || 'neutral'))}</span><span class="coordina-status-badge">${escapeHtml(nice(project.priority || 'normal'))}</span><span class="coordina-status-badge">${escapeHtml(project.manager_label || __('No manager assigned', 'coordina'))}</span><span class="coordina-status-badge">${escapeHtml(dateLabel(overview.timeline && overview.timeline.target))}</span></div>${progressBar(taskSummary.completion || metrics.completionPercent || 0, __('Overall task completion', 'coordina'))}</div><div class="coordina-project-overview-hero__stats"><div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-summary-chip"><strong>${Number(taskSummary.total || 0)}</strong>${escapeHtml(__('Tasks', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(milestoneSummary.total || 0)}</strong>${escapeHtml(__('Milestones', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(riskSummary.total || 0)}</strong>${escapeHtml(__('Risks & issues', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number((projectChecklist.summary && projectChecklist.summary.total) || 0)}</strong>${escapeHtml(__('Checklist', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(fileSummary.total || 0)}</strong>${escapeHtml(__('Files', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(discussionSummary.total || 0)}</strong>${escapeHtml(__('Updates', 'coordina'))}</span></div><dl class="coordina-key-value coordina-key-value--compact"><div><dt>${escapeHtml(__('Start', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(overview.timeline && overview.timeline.start))}</dd></div><div><dt>${escapeHtml(__('Target end', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(overview.timeline && overview.timeline.target))}</dd></div><div><dt>${escapeHtml(__('Actual end', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(overview.timeline && overview.timeline.end))}</dd></div><div><dt>${escapeHtml(__('Open work', 'coordina'))}</dt><dd>${Number(taskSummary.open || 0)}</dd></div><div><dt>${escapeHtml(__('Blocked', 'coordina'))}</dt><dd>${Number(taskSummary.blocked || 0)}</dd></div><div><dt>${escapeHtml(__('Overdue', 'coordina'))}</dt><dd>${Number(taskSummary.overdue || 0)}</dd></div></dl></div></div><div class="coordina-project-overview-layout"><div class="coordina-project-overview-main">${overviewSection(__('Task groups and work items', 'coordina'), __('See project tasks grouped the way your team works.', 'coordina'), workspaceOverviewTaskGroups(taskItems, taskGroups), `<button class="button button-small" data-action="switch-project-tab" data-tab="work">${escapeHtml(__('Open work', 'coordina'))}</button>`)}${overviewSection(__('Milestones', 'coordina'), __('See upcoming checkpoints and target dates.', 'coordina'), workspaceOverviewMilestones(milestoneItems), `<button class="button button-small" data-action="switch-project-tab" data-tab="milestones">${escapeHtml(__('Open milestones', 'coordina'))}</button>`)}</div><div class="coordina-project-overview-side">${checklistCard(projectChecklist, { title: __('Project checklists', 'coordina'), note: __('Track project checklists such as handover, requirements, or launch steps.', 'coordina'), emptyChecklistMessage: __('No checklists are attached to this project yet.', 'coordina'), addChecklistLabel: __('Add checklist', 'coordina'), addLabel: __('Add item', 'coordina') })}${overviewSection(__('Risks and issues', 'coordina'), __('See open risks, issues, and who owns them.', 'coordina'), workspaceOverviewRisks(riskItems), `<button class="button button-small" data-action="switch-project-tab" data-tab="risks-issues">${escapeHtml(__('Open risks', 'coordina'))}</button>`)}${overviewSection(__('Approvals', 'coordina'), __('See decisions linked to this project.', 'coordina'), workspaceOverviewApprovals(approvalItems), `<button class="button button-small" data-action="switch-project-tab" data-tab="approvals">${escapeHtml(__('Open approvals', 'coordina'))}</button>`)}${overviewSection(__('Files', 'coordina'), __('See files linked to this project and its work items.', 'coordina'), workspaceOverviewFiles(fileItems), `<button class="button button-small" data-action="switch-project-tab" data-tab="files">${escapeHtml(__('Open files', 'coordina'))}</button>`)}${overviewSection(__('Updates', 'coordina'), __('See recent project and work-item updates.', 'coordina'), workspaceOverviewUpdates(updateItems), `<button class="button button-small" data-action="switch-project-tab" data-tab="discussion">${escapeHtml(__('Open updates', 'coordina'))}</button>`)}</div></div></section>`;
}

function workspaceOverviewTab(project, overview, taskSummary) {
	const projectOverview = state.workspace && state.workspace.projectOverview ? state.workspace.projectOverview : {};
	const taskGroups = state.workspace && state.workspace.taskGroups ? state.workspace.taskGroups : [];
	const projectChecklist = state.workspace && state.workspace.projectChecklist ? state.workspace.projectChecklist : { checklists: [], items: [], summary: { total: 0, done: 0, open: 0 } };
	const taskItems = projectOverview.tasks || [];
	const milestoneItems = projectOverview.milestones || [];
	const riskItems = projectOverview.risksIssues || [];
	return `${projectOverviewInfoSection(project, overview, taskSummary)}<div class="coordina-project-overview-flat-grid">${overviewSection(__('Task groups', 'coordina'), __('Completion by task group.', 'coordina'), overviewBarChart(projectTaskGroupCompletionSeries(taskItems, taskGroups), __('No task groups with linked work yet.', 'coordina'), { scale: 'percent' }))}${overviewSection(__('Checklists', 'coordina'), __('Completion by checklist.', 'coordina'), overviewBarChart(projectChecklistCompletionSeries(projectChecklist), __('No project checklists are attached yet.', 'coordina'), { scale: 'percent' }))}${overviewSection(__('Milestones', 'coordina'), __('Completion by milestone.', 'coordina'), overviewBarChart(projectMilestoneCompletionSeries(milestoneItems), __('No milestones are attached to this project yet.', 'coordina'), { scale: 'percent' }))}${overviewSection(__('Risks and issues', 'coordina'), __('Open records grouped by type.', 'coordina'), overviewBarChart(projectRiskTypeSeries(riskItems), __('No risks or issues are linked to this project right now.', 'coordina')))}</div>`;
}

function workspaceMilestonesTab() {
	const items = state.workspace && state.workspace.milestoneCollection ? state.workspace.milestoneCollection.items || [] : [];
	const summary = state.workspace && state.workspace.milestoneSummary ? state.workspace.milestoneSummary : {};
	const actions = state.workspace && state.workspace.actions ? state.workspace.actions : {};
	const prefs = workspaceCardDisplayPrefs();
	const addButton = actions.canCreateMilestone ? `<button class="button button-primary" data-action="open-project-milestone-create">${escapeHtml(__('Add milestone', 'coordina'))}</button>` : '';
	const summaryChips = `<div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-summary-chip"><strong>${Number(summary.total || 0)}</strong>${escapeHtml(__('Total', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(summary.open || 0)}</strong>${escapeHtml(__('Open', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(summary.overdue || 0)}</strong>${escapeHtml(__('Overdue', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(summary.dependencies || 0)}</strong>${escapeHtml(__('Dependencies', 'coordina'))}</span></div>`;
	const empty = `<section class="coordina-empty-state"><h3>${escapeHtml(__('No milestones yet', 'coordina'))}</h3><p>${escapeHtml(__('Project planning checkpoints you can access will appear here.', 'coordina'))}</p></section>`;
	const list = items.length ? `<div class="coordina-project-item-grid">${items.map((item) => {
		const nextStep = item.status === 'completed' ? __('Milestone is complete', 'coordina') : item.dependency_flag ? __('Clear dependency before moving forward', 'coordina') : item.due_date && new Date(item.due_date) < new Date() ? __('Recover schedule and reset expectations', 'coordina') : __('Advance the remaining work', 'coordina');
		const actionsMarkup = prefs.showActions ? `<div class="coordina-inline-actions">${item.can_post_update ? `<button class="button button-small" data-action="open-discussion-create" data-object-type="milestone" data-object-id="${item.id}" data-object-label="${escapeHtml(item.title || __('Milestone', 'coordina'))}" data-lock-context="1">${escapeHtml(__('Update', 'coordina'))}</button>` : ''}<button class="button button-small button-primary" data-action="open-milestone-page" data-id="${item.id}" data-project-id="${item.project_id || ''}" data-project-tab="${item.project_id ? 'milestones' : ''}">${escapeHtml(__('Open', 'coordina'))}</button></div>` : '';
		return `<article class="coordina-project-item-card"><div class="coordina-project-item-card__heading">${openMilestoneButton(item.id, item.title, item.project_id, item.project_id ? 'milestones' : '')}<p class="coordina-project-item-card__note">${escapeHtml(workspaceItemDescription(item.notes || '', __('No milestone notes yet.', 'coordina'), 150))}</p></div><div class="coordina-work-meta"><span class="coordina-status-badge status-${escapeHtml(item.status)}">${escapeHtml(nice(item.status))}</span>${item.dependency_flag ? `<span class="coordina-status-badge">${escapeHtml(__('Dependency', 'coordina'))}</span>` : ''}<span>${escapeHtml(item.owner_label || __('No owner assigned', 'coordina'))}</span><span>${escapeHtml(dateLabel(item.due_date))}</span></div>${progressBar(item.completion_percent || 0, __('Milestone completion', 'coordina'))}${prefs.showGuidance ? `<p class="coordina-project-item-card__hint">${escapeHtml(nextStep)}</p>` : ''}${actionsMarkup}</article>`;
	}).join('')}</div>` : empty;
	return `<section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Project milestones', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See upcoming checkpoints, owners, and schedule risk.', 'coordina'))}</p></div>${addButton}</div>${summaryChips}${list}</section>`;
}

function activityObjectTypeLabel(item) {
	return item.objectTypeLabel || nice(item.objectType || 'activity item');
}

function activityRouteControl(item, options) {
	const route = item.route || {};
	if (!options.showContextLink || !route.page) {
		return '';
	}
	const label = options.linkLabelMode === 'title'
		? (item.objectLabel || activityObjectTypeLabel(item))
		: activityObjectTypeLabel(item);
	return `<button class="coordina-link-button coordina-link-button--activity" data-action="open-route" data-page="${route.page || ''}" data-project-id="${route.project_id || ''}" data-project-tab="${route.project_tab || ''}" data-task-id="${route.task_id || ''}" data-milestone-id="${route.milestone_id || ''}" data-risk-issue-id="${route.risk_issue_id || ''}">${escapeHtml(label)}</button>`;
}

function activityTimeLabel(value) {
	if (!value) {
		return '';
	}
	try {
		const raw = String(value).trim();
		const parsed = new Date(raw.replace(' ', 'T'));
		if (Number.isNaN(parsed.getTime())) {
			return '';
		}
		return new Intl.DateTimeFormat(document.documentElement.lang || undefined, { hour: '2-digit', minute: '2-digit' }).format(parsed);
	} catch (error) {
		return '';
	}
}

function activityTimestampLabel(value, mode) {
	if (!value) {
		return '';
	}
	if (mode === 'time') {
		return activityTimeLabel(value);
	}
	return dateLabel(value);
}

function activityDateTile(key) {
	if (key === '__unknown') {
		return `<div class="coordina-activity-date-card"><strong>--</strong><span>${escapeHtml(__('Unknown', 'coordina'))}</span></div>`;
	}
	const parsed = new Date(`${key}T00:00:00`);
	if (Number.isNaN(parsed.getTime())) {
		return `<div class="coordina-activity-date-card"><strong>--</strong><span>${escapeHtml(__('Unknown', 'coordina'))}</span></div>`;
	}
	const day = `${parsed.getDate()}`.padStart(2, '0');
	const month = new Intl.DateTimeFormat(document.documentElement.lang || undefined, { month: 'long' }).format(parsed);
	return `<div class="coordina-activity-date-card"><strong>${escapeHtml(day)}</strong><span>${escapeHtml(month)}</span></div>`;
}

function activityList(items, emptyMessage, options = {}) {
	const config = Object.assign({ showContextLink: true, showProjectLabel: false, linkLabelMode: 'type', timestampMode: 'full', listClass: '' }, options || {});
	return items.length ? `<ul class="coordina-timeline coordina-timeline--activity ${escapeHtml(config.listClass || '')}">${items.map((item) => {
		const timestampLabel = activityTimestampLabel(item.createdAt, config.timestampMode);
		const meta = [
			`<span class="coordina-status-badge">${escapeHtml(nice(item.eventType || 'activity'))}</span>`,
			activityRouteControl(item, config),
			config.showProjectLabel ? `<span>${escapeHtml(item.projectLabel || __('Standalone', 'coordina'))}</span>` : '',
		].filter(Boolean).join('');
		return `<li><div class="coordina-activity-entry"><div class="coordina-activity-entry__header"><strong>${escapeHtml(item.actorLabel || __('System', 'coordina'))}</strong>${timestampLabel ? `<span class="coordina-activity-entry__time">${escapeHtml(timestampLabel)}</span>` : ''}</div><p>${escapeHtml(item.message || __('Activity captured for this object.', 'coordina'))}</p>${meta ? `<div class="coordina-work-meta">${meta}</div>` : ''}</div></li>`;
	}).join('')}</ul>` : `<p class="coordina-empty-inline">${escapeHtml(emptyMessage)}</p>`;
}

function activityDateKey(value) {
	const text = String(value || '').trim();
	if (!text) {
		return '__unknown';
	}
	const datePart = text.split('T')[0];
	if (/^\d{4}-\d{2}-\d{2}$/.test(datePart)) {
		return datePart;
	}
	const parsed = new Date(text);
	if (!Number.isNaN(parsed.getTime())) {
		return parsed.toISOString().slice(0, 10);
	}
	return '__unknown';
}

function activityDateLabel(key) {
	if (key === '__unknown') {
		return __('Unknown date', 'coordina');
	}
	return dateLabel(key);
}

function activityPager(collection, scope) {
	const page = Number(collection && collection.page ? collection.page : 1);
	const totalPages = Number(collection && collection.totalPages ? collection.totalPages : 1);
	if (totalPages <= 1) {
		return '';
	}
	return `<nav class="coordina-activity-pager" aria-label="${escapeHtml(__('Activity pages', 'coordina'))}"><button class="button button-small" type="button" data-action="change-activity-page" data-scope="${scope}" data-page="${page - 1}" ${page <= 1 ? 'disabled' : ''}>${escapeHtml(__('Previous', 'coordina'))}</button><span class="coordina-activity-pager__status">${escapeHtml(`${__('Page', 'coordina')} ${page} ${__('of', 'coordina')} ${totalPages}`)}</span><button class="button button-small" type="button" data-action="change-activity-page" data-scope="${scope}" data-page="${page + 1}" ${page >= totalPages ? 'disabled' : ''}>${escapeHtml(__('Next', 'coordina'))}</button></nav>`;
}

function activityColumnsChart(series) {
	const max = Math.max(1, ...series.map((item) => Number(item.count || 0)));
	return `<div class="coordina-activity-chart coordina-activity-chart--columns" style="grid-template-columns:repeat(${Math.max(1, series.length)}, minmax(0, 1fr))">${series.map((item) => `<div class="coordina-activity-chart__column"><span class="coordina-activity-chart__track"><span class="coordina-activity-chart__bar" style="transform:scaleY(${Math.max(0.08, Number(item.count || 0) / max)})"></span></span><strong>${Number(item.count || 0)}</strong><span>${escapeHtml(item.label || '')}</span></div>`).join('')}</div>`;
}

function activityRankingChart(series, emptyMessage) {
	if (!series.length) {
		return `<p class="coordina-empty-inline">${escapeHtml(emptyMessage)}</p>`;
	}
	const max = Math.max(1, ...series.map((item) => Number(item.count || 0)));
	return `<div class="coordina-activity-chart coordina-activity-chart--ranking">${series.map((item) => `<div class="coordina-activity-chart__row"><div class="coordina-activity-chart__row-head"><span>${escapeHtml(item.label || '')}</span><strong>${Number(item.count || 0)}</strong></div><span class="coordina-activity-chart__row-bar"><span style="width:${Math.max(10, Math.round((Number(item.count || 0) / max) * 100))}%"></span></span></div>`).join('')}</div>`;
}

function workspaceActivityInsights(summary) {
	const charts = summary && summary.charts ? summary.charts : {};
	const rhythm = charts.rhythm || {};
	const buckets = Array.isArray(rhythm.buckets) ? rhythm.buckets : [];
	const actors = Array.isArray(charts.actors) ? charts.actors : [];
	const categories = Array.isArray(charts.categories) ? charts.categories : [];
	return `<div class="coordina-activity-insights"><section class="coordina-activity-insight"><div class="coordina-section-header"><div><h4>${escapeHtml(rhythm.title || __('Daily rhythm', 'coordina'))}</h4></div></div>${buckets.length ? activityColumnsChart(buckets) : `<p class="coordina-empty-inline">${escapeHtml(__('Not enough activity yet.', 'coordina'))}</p>`}</section><section class="coordina-activity-insight"><div class="coordina-section-header"><div><h4>${escapeHtml(__('Most active people', 'coordina'))}</h4></div></div>${activityRankingChart(actors, __('No people activity yet.', 'coordina'))}</section><section class="coordina-activity-insight"><div class="coordina-section-header"><div><h4>${escapeHtml(__('Item mix', 'coordina'))}</h4></div></div>${activityRankingChart(categories, __('No item activity yet.', 'coordina'))}</section></div>`;
}

function groupedActivityTimeline(collection, emptyMessage, options = {}) {
	const items = collection && Array.isArray(collection.items) ? collection.items : [];
	if (!items.length) {
		return activityList([], emptyMessage, options);
	}
	const groups = items.reduce((carry, item) => {
		const key = activityDateKey(item.createdAt);
		if (!carry[key]) {
			carry[key] = [];
		}
		carry[key].push(item);
		return carry;
	}, {});
	const groupKeys = Object.keys(groups).sort((a, b) => {
		if (a === '__unknown') {
			return 1;
		}
		if (b === '__unknown') {
			return -1;
		}
		return b.localeCompare(a);
	});
	return `<div class="coordina-activity-groups">${groupKeys.map((key) => `<section class="coordina-activity-group coordina-activity-group--dated">${activityDateTile(key)}<div class="coordina-activity-group__content"><div class="coordina-section-header coordina-section-header--activity-group"><div><h4>${escapeHtml(activityDateLabel(key))}</h4></div></div>${activityList(groups[key], __('No activity recorded for this date.', 'coordina'), Object.assign({ timestampMode: 'time', listClass: 'coordina-timeline--activity-group' }, options || {}))}</div></section>`).join('')}</div>`;
}

function workspaceActivityTab() {
	const collection = state.workspace && state.workspace.activityCollection ? state.workspace.activityCollection : { items: [] };
	const summary = state.workspace && state.workspace.activitySummary ? state.workspace.activitySummary : {};
	const summaryChips = `<div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-summary-chip"><strong>${Number(summary.total || 0)}</strong>${escapeHtml(__('Recent events', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${escapeHtml(dateLabel(summary.latestAt))}</strong>${escapeHtml(__('Latest', 'coordina'))}</span></div>`;
	const insights = workspaceActivityInsights(summary);
	const groupedTimeline = groupedActivityTimeline(collection, __('No project activity has been logged yet.', 'coordina'), { showContextLink: true, showProjectLabel: false, linkLabelMode: 'title' });
	return `<section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Project activity', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See recent project activity, who is active, and where work is happening.', 'coordina'))}</p></div></div>${summaryChips}${insights}${groupedTimeline}${activityPager(collection, 'project')}</section>`;
}

function optionList(items, selected) {
	return (items || []).map((item) => `<option value="${item}" ${String(selected || '') === String(item) ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('');
}

function workspaceSettingsTab() {
	const shell = state.shell || {};
	const actions = state.workspace && state.workspace.actions ? state.workspace.actions : {};
	const settings = state.workspace && state.workspace.projectSettings ? state.workspace.projectSettings : {};
	const project = settings.project || (state.workspace && state.workspace.project ? state.workspace.project : {});
	const members = settings.members || [];
	const memberIds = members.map((member) => String(member.user_id || member.id || ''));
	const statusOptions = optionList((shell.statuses && shell.statuses.projects) || [], project.status || 'draft');
	const healthOptions = optionList(shell.health || [], project.health || 'neutral');
	const priorityOptions = optionList(shell.priorities || [], project.priority || 'normal');
	const visibilityOptions = optionList(shell.visibilityLevels || ['team', 'private', 'public'], project.visibility || 'team');
	const notificationOptions = optionList(shell.projectNotificationPolicies || ['default', 'important-only', 'all-updates', 'muted'], project.notification_policy || 'default');
	const taskGroupLabelOptions = `<option value="">${escapeHtml(__('Use global default', 'coordina'))}</option>${optionList(['stage', 'phase', 'bucket'], project.task_group_label || '')}`;
	const managerOptions = (shell.users || []).map((user) => `<option value="${user.id}" ${String(project.manager_user_id || '') === String(user.id) ? 'selected' : ''}>${escapeHtml(user.label)}</option>`).join('');
	const memberOptions = (shell.users || []).map((user) => `<option value="${user.id}" ${memberIds.includes(String(user.id)) ? 'selected' : ''}>${escapeHtml(user.label)}</option>`).join('');
	if (!actions.canViewSettings) {
		return `<section class="coordina-card coordina-card--notice"><h3>${escapeHtml(__('Project settings', 'coordina'))}</h3><p>${escapeHtml(__('Project settings are available to project managers and administrators only.', 'coordina'))}</p></section>`;
	}
	return `<section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Project settings', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Keep governance, people, and close-out details tidy inside the workspace.', 'coordina'))}</p></div></div><form class="coordina-form coordina-workspace-settings-form" data-action="project-settings-form" data-project-id="${project.id || state.projectContext.id}"><div class="coordina-settings-layout coordina-workspace-settings-layout coordina-workspace-settings-layout--single"><div class="coordina-settings-stack"><section class="coordina-settings-block is-notice"><p>${escapeHtml(__('Changes here apply only to this project. Global defaults stay in Settings.', 'coordina'))}</p></section><section class="coordina-settings-block"><div class="coordina-section-header"><div><h4>${escapeHtml(__('Project pulse', 'coordina'))}</h4><p class="coordina-section-note">${escapeHtml(__('Status, health, priority, and the main owner stay together here.', 'coordina'))}</p></div></div><div class="coordina-form-grid"><label><span>${escapeHtml(__('Status', 'coordina'))}</span><select name="status">${statusOptions}</select></label><label><span>${escapeHtml(__('Health', 'coordina'))}</span><select name="health">${healthOptions}</select></label><label><span>${escapeHtml(__('Priority', 'coordina'))}</span><select name="priority">${priorityOptions}</select></label><label><span>${escapeHtml(__('Manager', 'coordina'))}</span><select name="manager_user_id"><option value="">${escapeHtml(__('Unassigned', 'coordina'))}</option>${managerOptions}</select></label></div></section><section class="coordina-settings-block"><div class="coordina-section-header"><div><h4>${escapeHtml(__('Access and collaboration', 'coordina'))}</h4><p class="coordina-section-note">${escapeHtml(__('Control visibility, notices, task-group language, and who belongs to this project.', 'coordina'))}</p></div></div><div class="coordina-form-grid"><label><span>${escapeHtml(__('Visibility', 'coordina'))}</span><select name="visibility">${visibilityOptions}</select></label><label><span>${escapeHtml(__('Project notifications', 'coordina'))}</span><select name="notification_policy">${notificationOptions}</select></label><label><span>${escapeHtml(__('Task group name', 'coordina'))}</span><select name="task_group_label">${taskGroupLabelOptions}</select></label><label class="coordina-form-grid__wide"><span>${escapeHtml(__('Team members', 'coordina'))}</span><select name="team_member_ids" multiple size="6">${memberOptions}</select></label></div></section><section class="coordina-settings-block"><div class="coordina-section-header"><div><h4>${escapeHtml(__('Close-out and completion', 'coordina'))}</h4><p class="coordina-section-note">${escapeHtml(__('Capture the actual finish and any final notes once the project wraps up.', 'coordina'))}</p></div></div><div class="coordina-form-grid"><label><span>${escapeHtml(__('Actual end date', 'coordina'))}</span><input type="datetime-local" name="actual_end_date" value="${escapeHtml(app.dateTimeInputValue(project.actual_end_date || ''))}" /></label><label class="coordina-form-grid__wide"><span>${escapeHtml(__('Close-out notes', 'coordina'))}</span><textarea name="closeout_notes">${escapeHtml(project.closeout_notes || '')}</textarea></label></div></section></div></div><div class="coordina-form-actions"><button class="button button-primary" type="submit">${escapeHtml(__('Save project settings', 'coordina'))}</button></div></form></section>`;
}

function workspaceProjectDetailsTab(project, overview, taskSummary) {
	const settings = state.workspace && state.workspace.projectSettings ? state.workspace.projectSettings : {};
	const members = Array.isArray(settings.members) ? settings.members : [];
	const actions = state.workspace && state.workspace.actions ? state.workspace.actions : {};
	const metrics = overview && overview.metrics ? overview.metrics : {};
	const description = plainText(project.description || '');
	const closeoutNotes = plainText(project.closeout_notes || '');
	const editFormHtml = typeof app.formHtml === 'function' ? app.formHtml(modules['coordina-projects'], project).replace(/data-action="close-modal"/g, 'data-action="cancel-project-edit"') : '';
	const editSection = actions.canEditProject && state.projectDetailEditing && editFormHtml
		? `<section class="coordina-card coordina-task-page__edit"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Edit project details', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Update the core project record here without leaving the workspace.', 'coordina'))}</p></div><button class="button" data-action="cancel-project-edit">${escapeHtml(__('Cancel', 'coordina'))}</button></div>${editFormHtml}</section>`
		: '';
	const peopleMarkup = members.length
		? `<div class="coordina-summary-row coordina-summary-row--subtle">${members.map((member) => `<span class="coordina-status-badge">${escapeHtml(member.user_label || __('Team member', 'coordina'))}</span>`).join('')}</div>`
		: `<p class="coordina-empty-inline">${escapeHtml(actions.canViewSettings ? __('No team members are assigned yet.', 'coordina') : __('Team membership is maintained by project managers.', 'coordina'))}</p>`;
	const overviewCard = `<section class="coordina-card coordina-task-page__overview"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Overview', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See the core project description and delivery intent before reviewing the structured fields.', 'coordina'))}</p></div>${actions.canEditProject ? `<button class="button button-primary" data-action="toggle-project-edit">${escapeHtml(state.projectDetailEditing ? __('Hide edit form', 'coordina') : __('Edit details', 'coordina'))}</button>` : ''}</div><div class="coordina-task-page__overview-stack"><section class="coordina-task-page__overview-section"><h4>${escapeHtml(__('Project name', 'coordina'))}</h4><p class="coordina-task-page__lead">${escapeHtml(project.title || __('Project', 'coordina'))}</p></section><section class="coordina-task-page__overview-section"><h4>${escapeHtml(__('Description', 'coordina'))}</h4>${description ? `<p class="coordina-task-page__description">${escapeHtml(description)}</p>` : `<p class="coordina-empty-inline">${escapeHtml(__('No project description has been added yet.', 'coordina'))}</p>`}</section></div></section>`;
	const deliveryCard = `<section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Delivery notes', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See how this project is framed, sponsored, and expected to close out.', 'coordina'))}</p></div></div><div class="coordina-project-detail-stack"><section class="coordina-task-page__overview-section"><h4>${escapeHtml(__('Sponsor and ownership', 'coordina'))}</h4><div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-summary-chip"><strong>${escapeHtml(project.manager_label || __('Unassigned', 'coordina'))}</strong>${escapeHtml(__('Manager', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${escapeHtml(project.sponsor_label || __('Unassigned', 'coordina'))}</strong>${escapeHtml(__('Sponsor', 'coordina'))}</span></div></section><section class="coordina-task-page__overview-section"><h4>${escapeHtml(__('Team members', 'coordina'))}</h4>${peopleMarkup}</section><section class="coordina-task-page__overview-section"><h4>${escapeHtml(__('Close-out notes', 'coordina'))}</h4>${closeoutNotes ? `<p class="coordina-task-page__description">${escapeHtml(closeoutNotes)}</p>` : `<p class="coordina-empty-inline">${escapeHtml(__('No close-out notes have been added yet.', 'coordina'))}</p>`}</section></div></section>`;
	const frameCard = `<section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Project details', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See ownership, delivery state, schedule, and workspace defaults in one frame.', 'coordina'))}</p></div></div><dl class="coordina-key-value"><div><dt>${escapeHtml(__('Project code', 'coordina'))}</dt><dd>${escapeHtml(project.code || __('Not set', 'coordina'))}</dd></div><div><dt>${escapeHtml(__('Status', 'coordina'))}</dt><dd>${escapeHtml(nice(project.status || 'draft'))}</dd></div><div><dt>${escapeHtml(__('Health', 'coordina'))}</dt><dd>${escapeHtml(nice(project.health || 'neutral'))}</dd></div><div><dt>${escapeHtml(__('Priority', 'coordina'))}</dt><dd>${escapeHtml(nice(project.priority || 'normal'))}</dd></div><div><dt>${escapeHtml(__('Manager', 'coordina'))}</dt><dd>${escapeHtml(project.manager_label || __('Unassigned', 'coordina'))}</dd></div><div><dt>${escapeHtml(__('Sponsor', 'coordina'))}</dt><dd>${escapeHtml(project.sponsor_label || __('Unassigned', 'coordina'))}</dd></div><div><dt>${escapeHtml(__('Visibility', 'coordina'))}</dt><dd>${escapeHtml(nice(project.visibility || 'team'))}</dd></div><div><dt>${escapeHtml(__('Notifications', 'coordina'))}</dt><dd>${escapeHtml(nice(project.notification_policy || 'default'))}</dd></div><div><dt>${escapeHtml(__('Task group name', 'coordina'))}</dt><dd>${escapeHtml(project.task_group_label ? nice(project.task_group_label) : __('Global default', 'coordina'))}</dd></div><div><dt>${escapeHtml(__('Start date', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(overview.timeline && overview.timeline.start))}</dd></div><div><dt>${escapeHtml(__('Target end', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(overview.timeline && overview.timeline.target))}</dd></div><div><dt>${escapeHtml(__('Actual end', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(overview.timeline && overview.timeline.end))}</dd></div><div><dt>${escapeHtml(__('Overall progress', 'coordina'))}</dt><dd>${escapeHtml(`${Number(metrics.completionPercent || taskSummary.completion || 0)}%`)}</dd></div><div><dt>${escapeHtml(__('Open tasks', 'coordina'))}</dt><dd>${escapeHtml(`${Number(metrics.openTasks || taskSummary.open || 0)}`)}</dd></div><div><dt>${escapeHtml(__('Blocked tasks', 'coordina'))}</dt><dd>${escapeHtml(`${Number(metrics.blockedTasks || taskSummary.blocked || 0)}`)}</dd></div><div><dt>${escapeHtml(__('Overdue tasks', 'coordina'))}</dt><dd>${escapeHtml(`${Number(metrics.overdueTasks || taskSummary.overdue || 0)}`)}</dd></div><div><dt>${escapeHtml(__('Last updated', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(project.updated_at))}</dd></div></dl></section>`;
	const governanceCard = `<section class="coordina-card coordina-project-detail-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Workspace governance', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Use Settings for team membership, visibility, notifications, and other project-wide governance controls.', 'coordina'))}</p></div>${actions.canViewSettings ? `<button class="button button-small" data-action="switch-project-tab" data-tab="settings">${escapeHtml(__('Open settings', 'coordina'))}</button>` : ''}</div><div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-summary-chip"><strong>${escapeHtml(nice(project.visibility || 'team'))}</strong>${escapeHtml(__('Visibility', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${escapeHtml(nice(project.notification_policy || 'default'))}</strong>${escapeHtml(__('Notifications', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${members.length}</strong>${escapeHtml(__('Members', 'coordina'))}</span></div></section>`;
	return `<section class="coordina-page coordina-task-page coordina-project-detail-page"><div class="coordina-task-page__layout"><div class="coordina-task-page__main">${overviewCard}${editSection}${deliveryCard}</div><div class="coordina-task-page__side">${frameCard}${governanceCard}</div></div></section>`;
}

function settingsTextarea(label, path, values) {
	const text = Array.isArray(values) ? values.join('\n') : '';
	return `<label><span>${escapeHtml(label)}</span><textarea data-setting-path="${escapeHtml(path)}" rows="4">${escapeHtml(text)}</textarea></label>`;
}

function settingsCheckbox(label, path, checked) {
	return `<label class="coordina-checkbox"><input type="checkbox" data-setting-path="${escapeHtml(path)}" value="1" ${app.isCheckedValue(checked) ? 'checked' : ''} /><span>${escapeHtml(label)}</span></label>`;
}

function settingsHint(text) {
	return `<p class="coordina-section-note">${escapeHtml(text)}</p>`;
}

function workspaceTabBody(tab, project, overview, taskSummary) {
	if (tab === 'overview') {
		return workspaceOverviewTab(project, overview, taskSummary);
	}
	if (tab === 'details') {
		return workspaceProjectDetailsTab(project, overview, taskSummary);
	}
	if (tab === 'work' || tab === 'tasks') {
		return workspaceWorkTab(taskSummary);
	}
	if (tab === 'gantt') {
		return workspaceGanttTab();
	}
	if (tab === 'milestones') {
		return workspaceMilestonesTab();
	}
	if (tab === 'activity') {
		return workspaceActivityTab();
	}
	if (tab === 'settings') {
		return workspaceSettingsTab();
	}
	if (tab === 'files') {
		const files = state.workspace && state.workspace.fileCollection ? state.workspace.fileCollection.items || [] : [];
		const list = app.fileList ? app.fileList(files, __('No project files yet. Attach the first file to keep project context together.', 'coordina')) : `<p class="coordina-empty-inline">${escapeHtml(__('No project files yet. Attach the first file to keep project context together.', 'coordina'))}</p>`;
		const actions = app.collaborationActionButtons ? app.collaborationActionButtons({ object_type: 'project', object_id: project.id || '', object_label: project.title || __('Project workspace', 'coordina') }, { canPostUpdate: false, canAttachFile: !!(state.workspace && state.workspace.actions && state.workspace.actions.canAttachFile) }) : '';
		const summary = state.workspace && state.workspace.fileSummary ? state.workspace.fileSummary : {};
		const typeSeries = typeof app.fileTypeSeries === 'function' ? app.fileTypeSeries(files) : [];
		return `<div class="coordina-columns"><section class="coordina-card coordina-card--wide"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Project files', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Files attached to this project and its related work.', 'coordina'))}</p></div>${actions}</div>${list}</section><div class="coordina-project-side-stack"><section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Files by item type', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See which project records are carrying the most file context.', 'coordina'))}</p></div></div><div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-summary-chip"><strong>${Number(summary.total || 0)}</strong>${escapeHtml(__('Linked files', 'coordina'))}</span></div>${typeof app.rankingChart === 'function' ? app.rankingChart(typeSeries, __('No file distribution to chart yet.', 'coordina')) : `<p class="coordina-empty-inline">${escapeHtml(__('No file distribution to chart yet.', 'coordina'))}</p>`}</section></div></div>`;
	}
	if (tab === 'discussion') {
		const discussions = state.workspace && state.workspace.discussionCollection ? state.workspace.discussionCollection.items || [] : [];
		const list = app.discussionTimeline ? app.discussionTimeline(discussions, __('No updates have been posted for this project yet.', 'coordina'), { metaOptions: { showProjectLabel: false } }) : `<p class="coordina-empty-inline">${escapeHtml(__('No updates have been posted for this project yet.', 'coordina'))}</p>`;
		const authorSeries = typeof app.updateUserSeries === 'function' ? app.updateUserSeries(discussions) : [];
		const datedSeries = typeof app.updateDateSeries === 'function' ? app.updateDateSeries(discussions) : { mode: 'day', series: [] };
		return `<div class="coordina-columns"><section class="coordina-card coordina-card--wide"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Project updates', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Updates from this project and its related work appear here together.', 'coordina'))}</p></div></div>${list}</section><div class="coordina-project-side-stack"><section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Updates by person', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See who is contributing most often across the project and its active work items.', 'coordina'))}</p></div></div><div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-summary-chip"><strong>${Number(discussions.length || 0)}</strong>${escapeHtml(__('Updates logged', 'coordina'))}</span></div>${typeof app.rankingChart === 'function' ? app.rankingChart(authorSeries, __('No update activity to chart yet.', 'coordina')) : `<p class="coordina-empty-inline">${escapeHtml(__('No update activity to chart yet.', 'coordina'))}</p>`}</section><section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Updates over time', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(`${__('Grouped by', 'coordina')} ${nice(datedSeries.mode || 'day')}`)}</p></div></div>${typeof app.columnsChart === 'function' && datedSeries.series.length ? app.columnsChart(datedSeries.series) : `<p class="coordina-empty-inline">${escapeHtml(__('No timeline data to chart yet.', 'coordina'))}</p>`}</section></div></div>`;
	}
	if (tab === 'risks-issues') {
		const items = state.workspace && state.workspace.riskIssueCollection ? state.workspace.riskIssueCollection.items || [] : [];
		const actions = state.workspace && state.workspace.actions ? state.workspace.actions : {};
		const prefs = workspaceCardDisplayPrefs();
		const riskActions = actions.canCreateRiskIssue ? `<div class="coordina-action-bar__actions"><button class="button" data-action="open-project-risk-create" data-type="risk">${escapeHtml(__('Add risk', 'coordina'))}</button><button class="button button-primary" data-action="open-project-risk-create" data-type="issue">${escapeHtml(__('Add issue', 'coordina'))}</button></div>` : '';
		const summary = state.workspace && state.workspace.riskIssueSummary ? state.workspace.riskIssueSummary : {};
		const list = items.length ? `<div class="coordina-project-item-grid">${items.map((item) => {
			const nextStep = ['resolved', 'closed'].includes(String(item.status || '')) ? __('Confirm closure and lessons learned', 'coordina') : item.status === 'escalated' ? __('Escalation needs review now', 'coordina') : item.object_type === 'issue' ? __('Drive resolution and remove the blocker', 'coordina') : ['high', 'critical'].includes(String(item.severity || '')) ? __('Mitigate now and report exposure', 'coordina') : __('Track mitigation and owner follow-up', 'coordina');
			const actionsMarkup = prefs.showActions ? `<div class="coordina-inline-actions">${item.can_post_update ? `<button class="button button-small" data-action="open-discussion-create" data-object-type="${escapeHtml(item.object_type || 'risk')}" data-object-id="${item.id}" data-object-label="${escapeHtml(item.title || __('Risk or issue', 'coordina'))}" data-lock-context="1">${escapeHtml(__('Update', 'coordina'))}</button>` : ''}<button class="button button-small button-primary" data-action="open-risk-issue-page" data-id="${item.id}" data-project-id="${item.project_id || ''}" data-project-tab="${item.project_id ? 'risks-issues' : ''}">${escapeHtml(__('Open', 'coordina'))}</button></div>` : '';
			return `<article class="coordina-project-item-card"><div class="coordina-project-item-card__heading">${openRiskIssueButton(item.id, item.title, item.project_id, item.project_id ? 'risks-issues' : '')}<p class="coordina-project-item-card__note">${escapeHtml(workspaceItemDescription(item.mitigation_plan || item.description || '', __('No mitigation plan has been added yet.', 'coordina'), 155))}</p></div><div class="coordina-work-meta"><span class="coordina-status-badge status-${escapeHtml(item.status)}">${escapeHtml(nice(item.status))}</span><span class="coordina-status-badge">${escapeHtml(nice(item.object_type || 'risk'))}</span><span class="coordina-status-badge">${escapeHtml(nice(item.severity || 'medium'))}</span><span>${escapeHtml(item.owner_label || __('No owner assigned', 'coordina'))}</span><span>${escapeHtml(dateLabel(item.target_resolution_date))}</span></div>${prefs.showGuidance ? `<p class="coordina-project-item-card__hint">${escapeHtml(nextStep)}</p>` : ''}${actionsMarkup}</article>`;
		}).join('')}</div>` : `<p class="coordina-empty-inline">${escapeHtml(__('Risks and issues you can access will appear here.', 'coordina'))}</p>`;
		return `<section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Project risks and issues', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Open risks, issues, severity, and next steps in one list.', 'coordina'))}</p></div>${riskActions}</div><div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-summary-chip"><strong>${Number(summary.total || 0)}</strong>${escapeHtml(__('Open records', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(summary.highSeverity || 0)}</strong>${escapeHtml(__('High severity', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(summary.escalated || 0)}</strong>${escapeHtml(__('Escalated', 'coordina'))}</span></div>${list}</section>`;
	}
	if (tab === 'approvals') {
		const items = state.workspace && state.workspace.approvalCollection ? state.workspace.approvalCollection.items || [] : [];
		const summary = state.workspace && state.workspace.approvalSummary ? state.workspace.approvalSummary : {};
		const prefs = workspaceCardDisplayPrefs();
		const list = items.length ? `<div class="coordina-project-item-grid">${items.map((item) => {
			const nextStep = item.status === 'pending' ? __('Decision needed before work can proceed', 'coordina') : item.status === 'rejected' ? __('Rework or follow-up is needed', 'coordina') : __('Decision captured and ready for reference', 'coordina');
			const fallbackNote = item.status === 'pending'
				? `${nice(item.object_type || 'approval')} ${__('submitted for review by', 'coordina')} ${item.submitted_by_label || __('Unknown submitter', 'coordina')}.`
				: item.status === 'rejected'
					? __('The latest decision blocked progress. Review the note and follow-up required next steps.', 'coordina')
					: __('The decision is recorded here for traceability and follow-up.', 'coordina');
			const note = workspaceItemDescription(item.rejection_reason || '', fallbackNote, 155);
			const actionsMarkup = prefs.showActions ? `<div class="coordina-inline-actions"><button class="button button-small button-primary" data-action="open-record" data-module="approvals" data-id="${item.id}">${escapeHtml(__('Open approval', 'coordina'))}</button></div>` : '';
			return `<article class="coordina-project-item-card"><div class="coordina-project-item-card__heading"><button class="coordina-link-button" data-action="open-record" data-module="approvals" data-id="${item.id}">${escapeHtml(item.object_label || nice(item.object_type || 'approval'))}</button><p class="coordina-project-item-card__note">${escapeHtml(note)}</p></div><div class="coordina-work-meta"><span class="coordina-status-badge status-${escapeHtml(item.status)}">${escapeHtml(nice(item.status))}</span><span class="coordina-status-badge">${escapeHtml(nice(item.object_type || 'approval'))}</span><span>${escapeHtml(item.approver_label || __('No approver assigned', 'coordina'))}</span><span>${escapeHtml(item.submitted_by_label || __('Unknown submitter', 'coordina'))}</span><span>${escapeHtml(dateLabel(item.submitted_at))}</span></div>${prefs.showGuidance ? `<p class="coordina-project-item-card__hint">${escapeHtml(nextStep)}</p>` : ''}${actionsMarkup}</article>`;
		}).join('')}</div>` : `<p class="coordina-empty-inline">${escapeHtml(__('No approvals are linked to this project yet.', 'coordina'))}</p>`;
		return `<section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Project approvals', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Pending decisions and the items they affect.', 'coordina'))}</p></div><div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-summary-chip"><strong>${Number(summary.pending || 0)}</strong>${escapeHtml(__('Pending', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(summary.approved || 0)}</strong>${escapeHtml(__('Approved', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(summary.rejected || 0)}</strong>${escapeHtml(__('Rejected', 'coordina'))}</span></div></div>${list}</section>`;
	}
	if (tab === 'board') {
		state.workspaceView = 'board';
		return workspaceWorkTab(taskSummary);
	}
	return workspaceTabBody('overview', project, overview, taskSummary);
}

function workspacePage() {
	const workspace = state.workspace || {};
	const project = workspace.project || {};
	const overview = workspace.overview || {};
	const metrics = overview.metrics || {};
	const taskSummary = workspace.taskSummary || {};
	const riskSummary = workspace.riskIssueSummary || {};
	const approvalSummary = workspace.approvalSummary || {};
	const milestoneSummary = workspace.milestoneSummary || {};
	const actions = workspace.actions || {};
	const tabs = workspace.tabs || [];
	const activeTab = workspace.activeTab || state.projectContext.tab || 'overview';
	const headerActions = [
		actions.canEditProject ? `<button class="button" data-action="open-project-details-edit" data-id="${project.id || ''}">${escapeHtml(__('Edit project', 'coordina'))}</button>` : '',
		actions.canPostUpdate ? `<button class="button" data-action="open-discussion-create" data-object-type="project" data-object-id="${project.id || ''}" data-object-label="${escapeHtml(project.title || __('Project workspace', 'coordina'))}" data-lock-context="1">${escapeHtml(__('Add update', 'coordina'))}</button>` : '',
		actions.canAttachFile ? `<button class="button" data-action="open-file-create" data-object-type="project" data-object-id="${project.id || ''}" data-object-label="${escapeHtml(project.title || __('Project workspace', 'coordina'))}" data-lock-context="1">${escapeHtml(__('Attach file', 'coordina'))}</button>` : '',
		actions.canCreateRiskIssue ? `<button class="button" data-action="open-project-risk-create" data-type="risk">${escapeHtml(__('Add risk', 'coordina'))}</button>` : '',
		actions.canCreateTask ? `<button class="button button-primary" data-action="open-project-task-create">${escapeHtml(__('Add task', 'coordina'))}</button>` : '',
		project.can_delete ? `<button class="button button-link-delete" data-action="delete-record" data-module="projects" data-id="${project.id || ''}" data-label="${escapeHtml(project.title || __('Project', 'coordina'))}">${escapeHtml(__('Delete project', 'coordina'))}</button>` : '',
	].filter(Boolean).join('');
	const metricCards = [
		{ label: __('Completion', 'coordina'), value: `${Number(metrics.completionPercent || 0)}%`, tone: 'accent' },
		{ label: __('Open tasks', 'coordina'), value: Number(metrics.openTasks || 0), tone: 'neutral' },
		{ label: __('Blocked', 'coordina'), value: Number(metrics.blockedTasks || 0), tone: Number(metrics.blockedTasks || 0) > 0 ? 'danger' : 'neutral' },
		{ label: __('Overdue', 'coordina'), value: Number(metrics.overdueTasks || 0), tone: Number(metrics.overdueTasks || 0) > 0 ? 'warning' : 'neutral' },
		{ label: __('Open milestones', 'coordina'), value: Number(milestoneSummary.open || 0), tone: Number(milestoneSummary.overdue || 0) > 0 ? 'warning' : 'neutral' },
		{ label: __('Open risks/issues', 'coordina'), value: Number(riskSummary.total || 0), tone: Number(riskSummary.total || 0) > 0 ? 'warning' : 'neutral' },
		{ label: __('Pending approvals', 'coordina'), value: Number(approvalSummary.pending || 0), tone: Number(approvalSummary.pending || 0) > 0 ? 'accent' : 'neutral' },
	].map((item) => `<article class="coordina-card coordina-metric-card coordina-metric-card--${escapeHtml(item.tone || 'neutral')}"><span class="coordina-metric-card__label">${escapeHtml(item.label)}</span><strong class="coordina-metric-card__value">${escapeHtml(item.value)}</strong></article>`).join('');
	const overviewMetrics = activeTab === 'overview' ? `<div class="coordina-summary-grid coordina-summary-grid--workspace">${metricCards}</div>` : '';
	void headerActions;
	return `<section class="coordina-page coordina-project-workspace"><div class="coordina-workspace-tabs">${tabs.map((tab) => `<button class="coordina-tab ${activeTab === tab.key ? 'is-active' : ''}" data-action="switch-project-tab" data-tab="${tab.key}">${iconLabel(workspaceTabIconType(tab.key), tab.label, 'coordina-tab__label')}${typeof tab.count !== 'undefined' ? `<span class="coordina-tab-count">${Number(tab.count)}</span>` : ''}</button>`).join('')}</div>${overviewMetrics}${workspaceTabBody(activeTab, project, overview, taskSummary)}</section>`;
}

function normalizeChecklistCollection(collection, fallback) {
	const base = collection || {};
	const fallbackValue = fallback || {};
	const checklists = Array.isArray(base.checklists) ? base.checklists : (Array.isArray(fallbackValue.checklists) ? fallbackValue.checklists : []);
	const items = Array.isArray(base.items)
		? base.items
		: checklists.reduce((result, list) => result.concat(Array.isArray(list.items) ? list.items : []), []);
	const summary = Object.assign({ total: items.length, done: items.filter((item) => !!item.is_done).length, open: Math.max(0, items.length - items.filter((item) => !!item.is_done).length) }, fallbackValue.summary || {}, base.summary || {});
	const permissions = Object.assign({ canManage: false, canToggle: false }, fallbackValue.permissions || {}, base.permissions || {});
	return {
		checklists,
		items,
		summary,
		permissions,
		object_type: base.object_type || fallbackValue.object_type || '',
		object_id: base.object_id || fallbackValue.object_id || '',
		object_label: base.object_label || fallbackValue.object_label || '',
	};
}

function checklistArrowButton(action, options) {
	const icon = action === 'up' ? '&uarr;' : '&darr;';
	const label = action === 'up' ? __('Move up', 'coordina') : __('Move down', 'coordina');
	const disabled = options && options.disabled ? 'disabled' : '';
	return `<button class="button button-small coordina-checklist-arrow" type="button" data-action="${escapeHtml(options.buttonAction || '')}" data-direction="${escapeHtml(action)}" data-id="${escapeHtml(options.id || '')}" data-object-type="${escapeHtml(options.objectType || '')}" ${disabled} aria-label="${escapeHtml(label)}" title="${escapeHtml(label)}">${icon}</button>`;
}

function checklistItemsMarkup(list, collection, config) {
	const data = normalizeChecklistCollection(collection, config && config.fallback ? config.fallback : {});
	const options = config || {};
	const checklist = list || {};
	const items = Array.isArray(checklist.items) ? checklist.items : [];
	const permissions = data.permissions || {};
	const canManage = !!permissions.canManage;
	const canToggle = !!permissions.canToggle;
	const objectType = data.object_type || options.objectType || '';
	const objectId = data.object_id || options.objectId || '';
	const objectLabel = data.object_label || options.objectLabel || '';

	if (!items.length) {
		const action = canManage ? `<div class="coordina-form-actions"><button class="button button-small" data-action="open-checklist-item-create" data-checklist-id="${escapeHtml(checklist.id || '')}" data-checklist-title="${escapeHtml(checklist.title || __('Checklist', 'coordina'))}" data-object-type="${escapeHtml(objectType)}" data-object-id="${escapeHtml(objectId)}" data-object-label="${escapeHtml(objectLabel)}">${escapeHtml(options.addLabel || __('Add item', 'coordina'))}</button></div>` : '';
		return `<p class="coordina-empty-inline">${escapeHtml(options.emptyMessage || __('No checklist items yet.', 'coordina'))}</p>${action}`;
	}

	return `<ul class="coordina-task-checklist">${items.map((item, index) => `<li class="${item.is_done ? 'is-done' : 'is-open'}"><div class="coordina-task-checklist-item"><label class="coordina-task-checklist-item__main"><input type="checkbox" data-checklist-toggle="1" data-id="${item.id}" data-object-type="${escapeHtml(objectType)}" ${item.is_done ? 'checked' : ''} ${canToggle ? '' : 'disabled'} /><span>${escapeHtml(item.item_text || __('Checklist item', 'coordina'))}</span></label>${canManage ? `<div class="coordina-task-checklist-item__actions">${checklistArrowButton('up', { buttonAction: 'move-checklist-item', id: item.id, objectType, disabled: index === 0 })}${checklistArrowButton('down', { buttonAction: 'move-checklist-item', id: item.id, objectType, disabled: index === items.length - 1 })}<button class="button button-small" type="button" data-action="open-checklist-item-edit" data-id="${item.id}" data-checklist-id="${escapeHtml(checklist.id || '')}" data-checklist-title="${escapeHtml(checklist.title || __('Checklist', 'coordina'))}" data-object-type="${escapeHtml(objectType)}" data-object-id="${escapeHtml(objectId)}" data-object-label="${escapeHtml(objectLabel)}" data-item-text="${escapeHtml(item.item_text || '')}" data-is-done="${item.is_done ? '1' : '0'}">${escapeHtml(__('Edit', 'coordina'))}</button><button class="button button-small button-link-delete" type="button" data-action="delete-checklist-item" data-id="${item.id}" data-object-type="${escapeHtml(objectType)}" data-label="${escapeHtml(item.item_text || __('Checklist item', 'coordina'))}">${escapeHtml(__('Delete', 'coordina'))}</button></div>` : ''}</div></li>`).join('')}</ul>`;
}

function checklistCard(collection, config) {
	const data = normalizeChecklistCollection(collection, config && config.fallback ? config.fallback : {});
	const summary = data.summary || { total: 0, done: 0 };
	const completion = Number(summary.total || 0) ? Math.round((Number(summary.done || 0) / Number(summary.total || 1)) * 100) : 0;
	const permissions = data.permissions || {};
	const headerAction = permissions.canManage ? `<button class="button button-small" data-action="open-checklist-create" data-object-type="${escapeHtml(data.object_type || '')}" data-object-id="${escapeHtml(data.object_id || '')}" data-object-label="${escapeHtml(data.object_label || '')}">${escapeHtml((config && config.addChecklistLabel) || __('Add checklist', 'coordina'))}</button>` : '';
	const overallProgress = config && config.hideOverallProgress ? '' : progressBar(completion, __('Checklist completion', 'coordina'));
	const checklists = Array.isArray(data.checklists) ? data.checklists : [];
	const body = checklists.length
		? `<div class="coordina-checklist-groups">${checklists.map((checklist, index) => {
			const listSummary = checklist.summary || { total: 0, done: 0 };
			const listCompletion = Number(listSummary.total || 0) ? Math.round((Number(listSummary.done || 0) / Number(listSummary.total || 1)) * 100) : 0;
			const actions = permissions.canManage ? `<div class="coordina-task-checklist-item__actions">${checklistArrowButton('up', { buttonAction: 'move-checklist', id: checklist.id, objectType: data.object_type, disabled: index === 0 })}${checklistArrowButton('down', { buttonAction: 'move-checklist', id: checklist.id, objectType: data.object_type, disabled: index === checklists.length - 1 })}<button class="button button-small" type="button" data-action="open-checklist-edit" data-id="${checklist.id}" data-object-type="${escapeHtml(data.object_type || '')}" data-object-id="${escapeHtml(data.object_id || '')}" data-object-label="${escapeHtml(data.object_label || '')}" data-title="${escapeHtml(checklist.title || __('Checklist', 'coordina'))}">${escapeHtml(__('Edit', 'coordina'))}</button><button class="button button-small" type="button" data-action="open-checklist-item-create" data-checklist-id="${checklist.id}" data-checklist-title="${escapeHtml(checklist.title || __('Checklist', 'coordina'))}" data-object-type="${escapeHtml(data.object_type || '')}" data-object-id="${escapeHtml(data.object_id || '')}" data-object-label="${escapeHtml(data.object_label || '')}">${escapeHtml((config && config.addLabel) || __('Add item', 'coordina'))}</button><button class="button button-small button-link-delete" type="button" data-action="delete-checklist" data-id="${checklist.id}" data-object-type="${escapeHtml(data.object_type || '')}" data-label="${escapeHtml(checklist.title || __('Checklist', 'coordina'))}">${escapeHtml(__('Delete', 'coordina'))}</button></div>` : '';
			return `<section class="coordina-card coordina-card--subtle coordina-checklist-group"><div class="coordina-section-header"><div><h4>${escapeHtml(checklist.title || __('Checklist', 'coordina'))}</h4><p class="coordina-section-note">${escapeHtml(`${Number(listSummary.done || 0)} / ${Number(listSummary.total || 0)} ${__('items complete', 'coordina')}`)}</p></div>${actions}</div>${progressBar(listCompletion, __('Checklist completion', 'coordina'))}${checklistItemsMarkup(checklist, data, config)}</section>`;
		}).join('')}</div>`
		: `<p class="coordina-empty-inline">${escapeHtml((config && config.emptyChecklistMessage) || __('No checklists are attached to this record yet.', 'coordina'))}</p>${permissions.canManage ? `<div class="coordina-form-actions"><button class="button button-small" data-action="open-checklist-create" data-object-type="${escapeHtml(data.object_type || '')}" data-object-id="${escapeHtml(data.object_id || '')}" data-object-label="${escapeHtml(data.object_label || '')}">${escapeHtml((config && config.addChecklistLabel) || __('Add checklist', 'coordina'))}</button></div>` : ''}`;
	return `<section class="coordina-card ${escapeHtml((config && config.className) || '')}"><div class="coordina-section-header"><div><h3>${escapeHtml((config && config.title) || __('Checklists', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml((config && config.note) || __('Named checklist groups linked directly to this record.', 'coordina'))}</p></div>${headerAction}</div>${overallProgress}${body}</section>`;
}

function taskPage() {
	const detail = state.taskDetail || { task: null, files: { items: [] }, discussions: { items: [] }, activity: { items: [] } };
	const task = detail.task || {};
	if (!task.id) {
		return `<section class="coordina-page"><section class="coordina-card coordina-card--notice"><h3>${escapeHtml(__('Task not available', 'coordina'))}</h3><p>${escapeHtml(__('This task could not be loaded or you no longer have access to it.', 'coordina'))}</p></section></section>`;
	}

	const files = detail.files || { items: [] };
	const discussions = detail.discussions || { items: [] };
	const activity = detail.activity || { items: [] };
	const checklist = detail.checklist || { items: task.checklist || [], summary: task.checklist_summary || {}, permissions: { canManage: !!task.can_manage_checklist, canToggle: !!task.can_toggle_checklist }, object_type: 'task', object_id: task.id, object_label: task.title || __('Task', 'coordina') };
	const backButton = Number(task.project_id || 0) > 0
		? `<button class="button" data-action="open-route" data-page="coordina-projects" data-project-id="${task.project_id}" data-project-tab="work">${escapeHtml(__('Back to project work', 'coordina'))}</button>`
		: canAccessPage('coordina-tasks')
			? `<button class="button" data-action="open-route" data-page="coordina-tasks">${escapeHtml(__('Back to task list', 'coordina'))}</button>`
			: `<button class="button" data-action="open-route" data-page="coordina-my-work">${escapeHtml(__('Back to My Work', 'coordina'))}</button>`;
	const headerActions = [
		backButton,
		task.can_post_update ? `<button class="button" data-action="open-discussion-create" data-object-type="task" data-object-id="${task.id}" data-object-label="${escapeHtml(task.title || __('Task', 'coordina'))}" data-lock-context="1">${escapeHtml(__('Add update', 'coordina'))}</button>` : '',
		task.can_attach_files ? `<button class="button" data-action="open-file-create" data-object-type="task" data-object-id="${task.id}" data-object-label="${escapeHtml(task.title || __('Task', 'coordina'))}" data-lock-context="1">${escapeHtml(__('Attach file', 'coordina'))}</button>` : '',
		task.can_edit ? `<button class="button button-primary" data-action="toggle-task-edit">${escapeHtml(state.taskDetailEditing ? __('Hide edit form', 'coordina') : __('Edit task', 'coordina'))}</button>` : '',
		task.can_delete ? `<button class="button button-link-delete" data-action="delete-record" data-module="tasks" data-id="${task.id}" data-label="${escapeHtml(task.title || __('Task', 'coordina'))}" data-project-id="${task.project_id || ''}">${escapeHtml(__('Delete task', 'coordina'))}</button>` : '',
	].filter(Boolean).join('');
	const overviewText = plainText(task.description || '');
	const blockerReasonText = meaningfulText(task.blocked_reason || '');
	const editFormHtml = typeof app.formHtml === 'function' ? app.formHtml(modules['coordina-tasks'], task).replace(/data-action="close-modal"/g, 'data-action="cancel-task-edit"') : '';
	const editSection = state.taskDetailEditing && editFormHtml
		? `<section class="coordina-card coordina-task-page__edit"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Edit task', 'coordina'))}</h3></div><button class="button" data-action="cancel-task-edit">${escapeHtml(__('Cancel', 'coordina'))}</button></div>${editFormHtml}</section>`
		: '';
	const quickEditForm = task.can_update_progress && !task.can_edit
		? `<form class="coordina-form coordina-task-page__quick-edit" data-action="save-form" data-module="tasks" data-id="${task.id}"><div class="coordina-form-grid"><label><span>${escapeHtml(__('Status', 'coordina'))}</span><select name="status">${(state.shell && state.shell.statuses && state.shell.statuses.tasks ? state.shell.statuses.tasks : []).map((item) => `<option value="${item}" ${task.status === item ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select></label><label><span>${escapeHtml(__('Completion', 'coordina'))}</span><input type="number" name="completion_percent" value="${escapeHtml(task.completion_percent || 0)}" min="0" max="100" /></label><label><span>${escapeHtml(__('Actual finish date', 'coordina'))}</span><input type="datetime-local" name="actual_finish_date" value="${escapeHtml(app.dateTimeInputValue(task.actual_finish_date || ''))}" /></label></div><div class="coordina-form-actions"><button class="button button-primary" type="submit">${escapeHtml(__('Save progress', 'coordina'))}</button></div></form>`
		: '';
	const taskGroupLabel = detail.taskGroupLabel ? nice(detail.taskGroupLabel) : __('Group', 'coordina');
	const overviewMeta = `<dl class="coordina-key-value coordina-key-value--task-overview"><div><dt>${escapeHtml(__('Project', 'coordina'))}</dt><dd>${Number(task.project_id || 0) > 0 ? openProjectButton(task.project_id, task.project_label, 'work') : escapeHtml(__('Standalone task', 'coordina'))}</dd></div><div><dt>${escapeHtml(taskGroupLabel)}</dt><dd>${escapeHtml(task.task_group_label || __('Ungrouped', 'coordina'))}</dd></div><div><dt>${escapeHtml(__('Assignee', 'coordina'))}</dt><dd>${escapeHtml(task.assignee_label || __('Unassigned', 'coordina'))}</dd></div><div><dt>${escapeHtml(__('Reporter', 'coordina'))}</dt><dd>${escapeHtml(task.reporter_label || __('Unknown', 'coordina'))}</dd></div><div><dt>${escapeHtml(__('Status', 'coordina'))}</dt><dd>${escapeHtml(nice(task.status || 'new'))}</dd></div><div><dt>${escapeHtml(__('Completion', 'coordina'))}</dt><dd>${escapeHtml(`${Number(task.completion_percent || 0)}%`)}</dd></div><div><dt>${escapeHtml(__('Priority', 'coordina'))}</dt><dd>${escapeHtml(nice(task.priority || 'normal'))}</dd></div><div><dt>${escapeHtml(__('Approval', 'coordina'))}</dt><dd>${escapeHtml(task.approval_required ? task.approval_label || __('Required', 'coordina') : __('Not required', 'coordina'))}</dd></div><div><dt>${escapeHtml(__('Start date', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(task.start_date))}</dd></div><div><dt>${escapeHtml(__('Due date', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(task.due_date))}</dd></div><div><dt>${escapeHtml(__('Actual finish date', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(task.actual_finish_date))}</dd></div><div><dt>${escapeHtml(__('Last updated', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(task.updated_at))}</dd></div></dl>`;
	const descriptionCard = `<section class="coordina-card coordina-task-page__overview"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Overview', 'coordina'))}</h3></div></div><div class="coordina-task-page__overview-stack"><section class="coordina-task-page__overview-section"><h4>${escapeHtml(__('Title', 'coordina'))}</h4><p class="coordina-task-page__lead">${escapeHtml(task.title || __('Task', 'coordina'))}</p></section><section class="coordina-task-page__overview-section"><h4>${escapeHtml(__('Description', 'coordina'))}</h4>${overviewText ? `<p class="coordina-task-page__description">${escapeHtml(overviewText)}</p>` : `<p class="coordina-empty-inline">${escapeHtml(__('No task description has been added yet.', 'coordina'))}</p>`}</section>${task.blocked || blockerReasonText ? `<section class="coordina-task-page__overview-section coordina-card coordina-card--notice"><h4>${escapeHtml(__('Blocker context', 'coordina'))}</h4><p>${escapeHtml(blockerReasonText || __('This task is marked blocked, but no blocker details were recorded yet.', 'coordina'))}</p></section>` : ''}${quickEditForm ? `<section class="coordina-task-page__overview-section"><h4>${escapeHtml(__('Quick progress update', 'coordina'))}</h4>${quickEditForm}</section>` : ''}</div></section>`;
	const taskDetailsCard = `<section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Task details', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See ownership, status, schedule, and project context.', 'coordina'))}</p></div></div><dl class="coordina-key-value">${overviewMeta.replace('coordina-key-value coordina-key-value--task-overview', '').replace(/^<dl[^>]*>/, '').replace(/<\/dl>$/, '')}</dl></section>`;
	const activityCard = `<details class="coordina-card coordina-collapsible-card"><summary class="coordina-collapsible-card__summary"><div><h3>${escapeHtml(__('Task activity', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Recent change history and related system activity for this task.', 'coordina'))}</p></div><span class="coordina-collapsible-card__chevron" aria-hidden="true"></span></summary><div class="coordina-collapsible-card__body">${groupedActivityTimeline(activity, __('No activity has been recorded for this task yet.', 'coordina'), { showContextLink: false, showProjectLabel: false })}${activityPager(activity, 'task')}</div></details>`;
	const updatesCard = `<section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Updates', 'coordina'))}</h3></div>${task.can_post_update ? `<button class="button button-small" data-action="open-discussion-create" data-object-type="task" data-object-id="${task.id}" data-object-label="${escapeHtml(task.title || __('Task', 'coordina'))}" data-lock-context="1">${escapeHtml(__('Post update', 'coordina'))}</button>` : ''}</div>${app.discussionTimeline ? app.discussionTimeline(discussions.items || [], __('No updates have been posted for this task yet.', 'coordina'), { metaOptions: { showObjectType: false, showContextLink: false, showProjectLabel: false } }) : ''}</section>`;
	const filesCard = `<section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Files', 'coordina'))}</h3></div>${task.can_attach_files ? `<button class="button button-small" data-action="open-file-create" data-object-type="task" data-object-id="${task.id}" data-object-label="${escapeHtml(task.title || __('Task', 'coordina'))}" data-lock-context="1">${escapeHtml(__('Attach file', 'coordina'))}</button>` : ''}</div>${app.fileList ? app.fileList(files.items || [], __('No files are attached to this task yet.', 'coordina')) : ''}</section>`;
	const checklistPanel = checklistCard(checklist, { title: __('Checklists', 'coordina'), note: __('Use multiple named checklists to group execution steps under this task.', 'coordina'), emptyChecklistMessage: __('No checklists are attached to this task yet.', 'coordina'), addChecklistLabel: __('Add checklist', 'coordina'), addLabel: __('Add item', 'coordina'), hideOverallProgress: true });
	void headerActions;
	return `<section class="coordina-page coordina-task-page"><div class="coordina-task-page__layout"><div class="coordina-task-page__main">${descriptionCard}${editSection}${checklistPanel}${updatesCard}${filesCard}</div><div class="coordina-task-page__side">${taskDetailsCard}${activityCard}</div></div></section>`;
}

function milestonePage() {
	const detail = state.milestoneDetail || { milestone: null, files: { items: [] }, discussions: { items: [] }, activity: { items: [] }, checklist: { items: [] } };
	const milestone = detail.milestone || {};
	if (!milestone.id) {
		return `<section class="coordina-page"><section class="coordina-card coordina-card--notice"><h3>${escapeHtml(__('Milestone not available', 'coordina'))}</h3><p>${escapeHtml(__('This milestone could not be loaded or you no longer have access to it.', 'coordina'))}</p></section></section>`;
	}

	const files = detail.files || { items: [] };
	const discussions = detail.discussions || { items: [] };
	const activity = detail.activity || { items: [] };
	const checklist = detail.checklist || { items: [], summary: { total: 0, done: 0, open: 0 }, permissions: { canManage: false, canToggle: false }, object_type: 'milestone', object_id: milestone.id, object_label: milestone.title || __('Milestone', 'coordina') };
	const notesText = plainText(milestone.notes || '');
	const backButton = Number(milestone.project_id || 0) > 0
		? `<button class="button" data-action="open-route" data-page="coordina-projects" data-project-id="${milestone.project_id}" data-project-tab="milestones">${escapeHtml(__('Back to project milestones', 'coordina'))}</button>`
		: canAccessPage('coordina-milestones')
			? `<button class="button" data-action="open-route" data-page="coordina-milestones">${escapeHtml(__('Back to milestone list', 'coordina'))}</button>`
			: `<button class="button" data-action="open-route" data-page="coordina-my-work">${escapeHtml(__('Back to My Work', 'coordina'))}</button>`;
	const headerActions = [
		backButton,
		milestone.can_post_update ? `<button class="button" data-action="open-discussion-create" data-object-type="milestone" data-object-id="${milestone.id}" data-object-label="${escapeHtml(milestone.title || __('Milestone', 'coordina'))}" data-lock-context="1">${escapeHtml(__('Add update', 'coordina'))}</button>` : '',
		milestone.can_attach_files ? `<button class="button" data-action="open-file-create" data-object-type="milestone" data-object-id="${milestone.id}" data-object-label="${escapeHtml(milestone.title || __('Milestone', 'coordina'))}" data-lock-context="1">${escapeHtml(__('Attach file', 'coordina'))}</button>` : '',
		milestone.can_edit ? `<button class="button button-primary" data-action="toggle-milestone-edit">${escapeHtml(state.milestoneDetailEditing ? __('Hide edit form', 'coordina') : __('Edit fields', 'coordina'))}</button>` : '',
		milestone.can_delete ? `<button class="button button-link-delete" data-action="delete-record" data-module="milestones" data-id="${milestone.id}" data-label="${escapeHtml(milestone.title || __('Milestone', 'coordina'))}" data-project-id="${milestone.project_id || ''}">${escapeHtml(__('Delete milestone', 'coordina'))}</button>` : '',
	].filter(Boolean).join('');
	const editFormHtml = typeof app.formHtml === 'function' ? app.formHtml(modules['coordina-milestones'], milestone).replace(/data-action="close-modal"/g, 'data-action="cancel-milestone-edit"') : '';
	const editSection = state.milestoneDetailEditing && editFormHtml
		? `<section class="coordina-card coordina-task-page__edit"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Edit milestone', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Update milestone details here.', 'coordina'))}</p></div><button class="button" data-action="cancel-milestone-edit">${escapeHtml(__('Cancel', 'coordina'))}</button></div>${editFormHtml}</section>`
		: '';
	const overviewCard = `<section class="coordina-card coordina-task-page__overview"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Overview', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See what this milestone is for and any planning notes attached to it.', 'coordina'))}</p></div></div><div class="coordina-task-page__overview-stack"><section class="coordina-task-page__overview-section"><h4>${escapeHtml(__('Title', 'coordina'))}</h4><p class="coordina-task-page__lead">${escapeHtml(milestone.title || __('Milestone', 'coordina'))}</p></section><section class="coordina-task-page__overview-section"><h4>${escapeHtml(__('Notes', 'coordina'))}</h4>${notesText ? `<p class="coordina-task-page__description">${escapeHtml(notesText)}</p>` : `<p class="coordina-empty-inline">${escapeHtml(__('No milestone notes have been added yet.', 'coordina'))}</p>`}</section></div></section>`;
	const updatesCard = `<section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Updates', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See the latest progress notes for this milestone.', 'coordina'))}</p></div>${milestone.can_post_update ? `<button class="button button-small" data-action="open-discussion-create" data-object-type="milestone" data-object-id="${milestone.id}" data-object-label="${escapeHtml(milestone.title || __('Milestone', 'coordina'))}" data-lock-context="1">${escapeHtml(__('Post update', 'coordina'))}</button>` : ''}</div>${app.discussionTimeline ? app.discussionTimeline(discussions.items || [], __('No updates have been posted for this milestone yet.', 'coordina'), { metaOptions: { showObjectType: false, showContextLink: false, showProjectLabel: false } }) : ''}</section>`;
	const activityCard = `<details class="coordina-card coordina-collapsible-card"><summary class="coordina-collapsible-card__summary"><div><h3>${escapeHtml(__('Milestone activity', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See recent changes, updates, and file activity for this milestone.', 'coordina'))}</p></div><span class="coordina-collapsible-card__chevron" aria-hidden="true"></span></summary><div class="coordina-collapsible-card__body">${groupedActivityTimeline(activity, __('No activity has been recorded for this milestone yet.', 'coordina'), { showContextLink: false, showProjectLabel: false })}${activityPager(activity, 'milestone')}</div></details>`;
	const filesCard = `<section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Files', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See files attached to this milestone.', 'coordina'))}</p></div>${milestone.can_attach_files ? `<button class="button button-small" data-action="open-file-create" data-object-type="milestone" data-object-id="${milestone.id}" data-object-label="${escapeHtml(milestone.title || __('Milestone', 'coordina'))}" data-lock-context="1">${escapeHtml(__('Attach file', 'coordina'))}</button>` : ''}</div>${app.fileList ? app.fileList(files.items || [], __('No files are attached to this milestone yet.', 'coordina')) : ''}</section>`;
	const checklistPanel = checklistCard(checklist, { title: __('Checklists', 'coordina'), note: __('Group milestone work into named checklists when one list is not enough.', 'coordina'), emptyChecklistMessage: __('No checklists are attached to this milestone yet.', 'coordina'), addChecklistLabel: __('Add checklist', 'coordina'), addLabel: __('Add item', 'coordina'), hideOverallProgress: true });
	const frameCard = `<section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Milestone details', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See ownership, status, schedule, and project context.', 'coordina'))}</p></div></div><dl class="coordina-key-value"><div><dt>${escapeHtml(__('Project', 'coordina'))}</dt><dd>${Number(milestone.project_id || 0) > 0 ? openProjectButton(milestone.project_id, milestone.project_label, 'milestones') : escapeHtml(__('Project milestone', 'coordina'))}</dd></div><div><dt>${escapeHtml(__('Status', 'coordina'))}</dt><dd>${escapeHtml(nice(milestone.status || 'planned'))}</dd></div><div><dt>${escapeHtml(__('Owner', 'coordina'))}</dt><dd>${escapeHtml(milestone.owner_label || __('Unassigned', 'coordina'))}</dd></div><div><dt>${escapeHtml(__('Due date', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(milestone.due_date))}</dd></div><div><dt>${escapeHtml(__('Completion', 'coordina'))}</dt><dd>${escapeHtml(`${Number(milestone.completion_percent || 0)}%`)}</dd></div><div><dt>${escapeHtml(__('Dependency milestone', 'coordina'))}</dt><dd>${escapeHtml(milestone.dependency_flag ? __('Yes', 'coordina') : __('No', 'coordina'))}</dd></div><div><dt>${escapeHtml(__('Last updated', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(milestone.updated_at))}</dd></div></dl></section>`;
	void headerActions;
	return `<section class="coordina-page coordina-task-page coordina-milestone-page"><div class="coordina-task-page__layout"><div class="coordina-task-page__main">${overviewCard}${editSection}${checklistPanel}${updatesCard}${filesCard}</div><div class="coordina-task-page__side">${frameCard}${activityCard}</div></div></section>`;
}

function riskIssuePage() {
	const detail = state.riskIssueDetail || { riskIssue: null, files: { items: [] }, discussions: { items: [] }, activity: { items: [] }, checklist: { items: [] } };
	const riskIssue = detail.riskIssue || {};
	if (!riskIssue.id) {
		return `<section class="coordina-page"><section class="coordina-card coordina-card--notice"><h3>${escapeHtml(__('Risk or issue not available', 'coordina'))}</h3><p>${escapeHtml(__('This record could not be loaded or you no longer have access to it.', 'coordina'))}</p></section></section>`;
	}

	const files = detail.files || { items: [] };
	const discussions = detail.discussions || { items: [] };
	const activity = detail.activity || { items: [] };
	const checklist = detail.checklist || { items: [], summary: { total: 0, done: 0, open: 0 }, permissions: { canManage: false, canToggle: false }, object_type: riskIssue.object_type || 'risk', object_id: riskIssue.id, object_label: riskIssue.title || __('Risk or issue', 'coordina') };
	const backButton = Number(riskIssue.project_id || 0) > 0
		? `<button class="button" data-action="open-route" data-page="coordina-projects" data-project-id="${riskIssue.project_id}" data-project-tab="risks-issues">${escapeHtml(__('Back to project risks', 'coordina'))}</button>`
		: canAccessPage('coordina-risks-issues')
			? `<button class="button" data-action="open-route" data-page="coordina-risks-issues">${escapeHtml(__('Back to risks & issues', 'coordina'))}</button>`
			: `<button class="button" data-action="open-route" data-page="coordina-my-work">${escapeHtml(__('Back to My Work', 'coordina'))}</button>`;
	const headerActions = [
		backButton,
		riskIssue.can_post_update ? `<button class="button" data-action="open-discussion-create" data-object-type="${escapeHtml(riskIssue.object_type || 'risk')}" data-object-id="${riskIssue.id}" data-object-label="${escapeHtml(riskIssue.title || __('Risk or issue', 'coordina'))}" data-lock-context="1">${escapeHtml(__('Add update', 'coordina'))}</button>` : '',
		riskIssue.can_attach_files ? `<button class="button" data-action="open-file-create" data-object-type="${escapeHtml(riskIssue.object_type || 'risk')}" data-object-id="${riskIssue.id}" data-object-label="${escapeHtml(riskIssue.title || __('Risk or issue', 'coordina'))}" data-lock-context="1">${escapeHtml(__('Attach file', 'coordina'))}</button>` : '',
		riskIssue.can_edit ? `<button class="button button-primary" data-action="toggle-risk-issue-edit">${escapeHtml(state.riskIssueDetailEditing ? __('Hide edit form', 'coordina') : __('Edit fields', 'coordina'))}</button>` : '',
		riskIssue.can_delete ? `<button class="button button-link-delete" data-action="delete-record" data-module="risks-issues" data-id="${riskIssue.id}" data-label="${escapeHtml(riskIssue.title || __('Risk or issue', 'coordina'))}" data-project-id="${riskIssue.project_id || ''}">${escapeHtml(__('Delete record', 'coordina'))}</button>` : '',
	].filter(Boolean).join('');
	const overviewText = plainText(riskIssue.description || '');
	const mitigationText = plainText(riskIssue.mitigation_plan || '');
	const editFormHtml = typeof app.formHtml === 'function' ? app.formHtml(modules['coordina-risks-issues'], riskIssue).replace(/data-action="close-modal"/g, 'data-action="cancel-risk-issue-edit"') : '';
	const editSection = state.riskIssueDetailEditing && editFormHtml
		? `<section class="coordina-card coordina-task-page__edit"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Edit risk or issue', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Update this record here.', 'coordina'))}</p></div><button class="button" data-action="cancel-risk-issue-edit">${escapeHtml(__('Cancel', 'coordina'))}</button></div>${editFormHtml}</section>`
		: '';
	const overviewCard = `<section class="coordina-card coordina-task-page__overview"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Overview', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See the background and current context for this record.', 'coordina'))}</p></div></div><div class="coordina-task-page__overview-stack"><section class="coordina-task-page__overview-section"><h4>${escapeHtml(__('Title', 'coordina'))}</h4><p class="coordina-task-page__lead">${escapeHtml(riskIssue.title || __('Risk or issue', 'coordina'))}</p></section><section class="coordina-task-page__overview-section"><h4>${escapeHtml(__('Description', 'coordina'))}</h4>${overviewText ? `<p class="coordina-task-page__description">${escapeHtml(overviewText)}</p>` : `<p class="coordina-empty-inline">${escapeHtml(__('No background description has been added yet.', 'coordina'))}</p>`}</section></div></section>`;
	const responsePlanCard = `<section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Response plan', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See the current mitigation, workaround, or resolution plan.', 'coordina'))}</p></div></div>${mitigationText ? `<p class="coordina-task-page__description">${escapeHtml(mitigationText)}</p>` : `<p class="coordina-empty-inline">${escapeHtml(__('No mitigation plan has been added yet.', 'coordina'))}</p>`}</section>`;
	const activityCard = `<details class="coordina-card coordina-collapsible-card"><summary class="coordina-collapsible-card__summary"><div><h3>${escapeHtml(__('Risk or issue activity', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See recent changes, updates, and file activity for this record.', 'coordina'))}</p></div><span class="coordina-collapsible-card__chevron" aria-hidden="true"></span></summary><div class="coordina-collapsible-card__body">${groupedActivityTimeline(activity, __('No activity has been recorded for this risk or issue yet.', 'coordina'), { showContextLink: false, showProjectLabel: false })}${activityPager(activity, 'risk-issue')}</div></details>`;
	const updatesCard = `<section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Updates', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See the latest updates for this record.', 'coordina'))}</p></div>${riskIssue.can_post_update ? `<button class="button button-small" data-action="open-discussion-create" data-object-type="${escapeHtml(riskIssue.object_type || 'risk')}" data-object-id="${riskIssue.id}" data-object-label="${escapeHtml(riskIssue.title || __('Risk or issue', 'coordina'))}" data-lock-context="1">${escapeHtml(__('Post update', 'coordina'))}</button>` : ''}</div>${app.discussionTimeline ? app.discussionTimeline(discussions.items || [], __('No updates have been posted for this risk or issue yet.', 'coordina'), { metaOptions: { showObjectType: false, showContextLink: false, showProjectLabel: false } }) : ''}</section>`;
	const filesCard = `<section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Files', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See files attached to this record.', 'coordina'))}</p></div>${riskIssue.can_attach_files ? `<button class="button button-small" data-action="open-file-create" data-object-type="${escapeHtml(riskIssue.object_type || 'risk')}" data-object-id="${riskIssue.id}" data-object-label="${escapeHtml(riskIssue.title || __('Risk or issue', 'coordina'))}" data-lock-context="1">${escapeHtml(__('Attach file', 'coordina'))}</button>` : ''}</div>${app.fileList ? app.fileList(files.items || [], __('No files are attached to this risk or issue yet.', 'coordina')) : ''}</section>`;
	const checklistPanel = checklistCard(checklist, { title: __('Checklists', 'coordina'), note: __('Use named checklists to separate mitigation, response, handover, or follow-up work.', 'coordina'), emptyChecklistMessage: __('No checklists are attached to this record yet.', 'coordina'), addChecklistLabel: __('Add checklist', 'coordina'), addLabel: __('Add item', 'coordina'), hideOverallProgress: true });
	const frameCard = `<section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Risk or issue details', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See ownership, status, and project context.', 'coordina'))}</p></div></div><dl class="coordina-key-value"><div><dt>${escapeHtml(__('Project', 'coordina'))}</dt><dd>${Number(riskIssue.project_id || 0) > 0 ? openProjectButton(riskIssue.project_id, riskIssue.project_label, 'risks-issues') : escapeHtml(__('Standalone exception', 'coordina'))}</dd></div><div><dt>${escapeHtml(__('Type', 'coordina'))}</dt><dd>${escapeHtml(nice(riskIssue.object_type || 'risk'))}</dd></div><div><dt>${escapeHtml(__('Status', 'coordina'))}</dt><dd>${escapeHtml(nice(riskIssue.status || 'identified'))}</dd></div><div><dt>${escapeHtml(__('Severity', 'coordina'))}</dt><dd>${escapeHtml(nice(riskIssue.severity || 'medium'))}</dd></div><div><dt>${escapeHtml(__('Impact', 'coordina'))}</dt><dd>${escapeHtml(nice(riskIssue.impact || 'medium'))}</dd></div><div><dt>${escapeHtml(__('Likelihood', 'coordina'))}</dt><dd>${escapeHtml(nice(riskIssue.likelihood || 'medium'))}</dd></div><div><dt>${escapeHtml(__('Owner', 'coordina'))}</dt><dd>${escapeHtml(riskIssue.owner_label || __('Unassigned', 'coordina'))}</dd></div><div><dt>${escapeHtml(__('Created by', 'coordina'))}</dt><dd>${escapeHtml(riskIssue.created_by_label || __('Unknown', 'coordina'))}</dd></div><div><dt>${escapeHtml(__('Target resolution', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(riskIssue.target_resolution_date))}</dd></div><div><dt>${escapeHtml(__('Last updated', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(riskIssue.updated_at))}</dd></div></dl></section>`;
	void headerActions;
	return `<section class="coordina-page coordina-task-page coordina-risk-issue-page"><div class="coordina-task-page__layout"><div class="coordina-task-page__main">${overviewCard}${responsePlanCard}${editSection}${checklistPanel}${updatesCard}${filesCard}</div><div class="coordina-task-page__side">${frameCard}${activityCard}</div></div></section>`;
}

function routeButton(label, route) {
	if (!route || !route.page) {
		return '';
	}
	if (!canAccessPage(route.page) && !(route.page === 'coordina-projects' && Number(route.project_id || 0) > 0) && !(route.page === 'coordina-task' && Number(route.task_id || 0) > 0) && !(route.page === 'coordina-milestone' && Number(route.milestone_id || 0) > 0) && !(route.page === 'coordina-risk-issue' && Number(route.risk_issue_id || 0) > 0)) {
		return '';
	}
	return `<button class="button button-small" data-action="open-route" data-page="${route.page || ''}" data-project-id="${route.project_id || ''}" data-project-tab="${route.project_tab || ''}" data-task-id="${route.task_id || ''}" data-milestone-id="${route.milestone_id || ''}" data-risk-issue-id="${route.risk_issue_id || ''}">${iconLabel(pageIconType(route.page), label)}</button>`;
}

function dashboardList(items, emptyMessage, renderItem) {
	return items.length ? `<ul class="coordina-work-list">${items.map(renderItem).join('')}</ul>` : `<p class="coordina-empty-inline">${escapeHtml(emptyMessage)}</p>`;
}

function dashboardPage() {
	const data = state.dashboard || { kpis: {}, widgets: {}, roleMode: 'team', scope: 'personal' };
	const kpis = data.kpis || {};
	const widgets = data.widgets || {};
	const roleLabel = data.roleMode === 'executive' ? __('Executive overview', 'coordina') : data.roleMode === 'manager' ? __('Manager overview', 'coordina') : data.roleMode === 'admin' ? __('Admin overview', 'coordina') : __('Personal overview', 'coordina');
	const actions = [
		canAccessPage('coordina-projects') ? `<button class="button" data-action="open-route" data-page="coordina-projects">${escapeHtml(__('View projects', 'coordina'))}</button>` : '',
		`<button class="button button-primary" data-action="open-route" data-page="coordina-my-work">${escapeHtml(__('Go to My Work', 'coordina'))}</button>`,
	].filter(Boolean).join('');
	const alertCards = [{ label: __('At risk', 'coordina'), value: Number(kpis.atRiskProjects || 0), tone: Number(kpis.atRiskProjects || 0) > 0 ? 'danger' : 'neutral' }, { label: __('Overdue tasks', 'coordina'), value: Number(kpis.overdueTasks || 0), tone: Number(kpis.overdueTasks || 0) > 0 ? 'warning' : 'neutral' }, { label: __('Pending approvals', 'coordina'), value: Number(kpis.pendingApprovals || 0), tone: Number(kpis.pendingApprovals || 0) > 0 ? 'accent' : 'neutral' }].map((item) => `<article class="coordina-card coordina-metric-card coordina-metric-card--${escapeHtml(item.tone)}"><span class="coordina-metric-card__label">${escapeHtml(item.label)}</span><strong class="coordina-metric-card__value">${escapeHtml(item.value)}</strong></article>`).join('');
	const atRisk = dashboardList(widgets.atRiskProjects || [], __('No at-risk or blocked projects right now.', 'coordina'), (item) => `<li><button class="coordina-link-button" data-action="open-route" data-page="coordina-projects" data-project-id="${item.id}" data-project-tab="overview">${iconLabel('project', item.title)}</button><div class="coordina-work-meta"><span class="coordina-status-badge status-${escapeHtml(item.status)}">${escapeHtml(nice(item.status))}</span><span class="coordina-status-badge status-${escapeHtml(item.health)}">${escapeHtml(nice(item.health))}</span><span>${escapeHtml(dateLabel(item.targetEndDate))}</span></div></li>`);
	const overdue = dashboardList(widgets.overdueTasks || [], __('No overdue tasks in this scope.', 'coordina'), (item) => `<li>${openTaskButton(item.id, item.title, item.projectId || item.project_id || 0, (item.projectId || item.project_id || 0) > 0 ? 'work' : '')}<div class="coordina-work-meta"><span class="coordina-status-badge status-${escapeHtml(item.status)}">${escapeHtml(nice(item.status))}</span>${openProjectButton(item.projectId || item.project_id || 0, item.projectLabel, 'work')}<span>${escapeHtml(dateLabel(item.dueDate))}</span></div></li>`);
	const approvals = dashboardList(widgets.pendingApprovals || [], __('No pending approvals in this scope.', 'coordina'), (item) => `<li><button class="coordina-link-button" data-action="open-record" data-module="approvals" data-id="${item.id}">${iconLabel(item.objectType || 'approval', item.objectLabel || nice(item.objectType))}</button><div class="coordina-work-meta">${openProjectButton(item.projectId || item.project_id || 0, item.projectLabel, 'approvals')}<span>${escapeHtml(item.ownerLabel || __('Unknown owner', 'coordina'))}</span><span>${escapeHtml(dateLabel(item.submittedAt))}</span></div></li>`);
	const recentActivity = widgets.recentActivity || { items: [] };
	const activity = `${activityList(recentActivity.items || recentActivity || [], __('No recent activity has been logged yet.', 'coordina'), { showContextLink: true, showProjectLabel: true, linkLabelMode: 'type' })}${activityPager(recentActivity, 'dashboard')}`;
	const deadlines = dashboardList(widgets.upcomingDeadlines || [], __('No upcoming deadlines found.', 'coordina'), (item) => `<li><button class="coordina-link-button" data-action="open-route" data-page="${item.route && item.route.page ? item.route.page : 'coordina-task'}" data-project-id="${item.route && item.route.project_id ? item.route.project_id : ''}" data-project-tab="${item.route && item.route.project_tab ? item.route.project_tab : ''}" data-task-id="${item.route && item.route.task_id ? item.route.task_id : ''}" data-milestone-id="${item.route && item.route.milestone_id ? item.route.milestone_id : ''}" data-risk-issue-id="${item.route && item.route.risk_issue_id ? item.route.risk_issue_id : ''}">${iconLabel(pageIconType(item.route && item.route.page ? item.route.page : 'coordina-task'), item.title)}</button><div class="coordina-work-meta"><span>${escapeHtml(item.label)}</span><span class="coordina-status-badge status-${escapeHtml(item.status)}">${escapeHtml(nice(item.status))}</span><span>${escapeHtml(dateLabel(item.date))}</span></div></li>`);
	return `<section class="coordina-page">${pageHeading('coordina-dashboard', actions, { title: __('Dashboard', 'coordina'), description: `${roleLabel}. ${__('Use this screen to review what needs attention across your work.', 'coordina')}` })}<div class="coordina-summary-grid coordina-summary-grid--workspace">${alertCards}</div><div class="coordina-columns"><section class="coordina-card coordina-card--wide"><div class="coordina-section-header"><h3>${escapeHtml(__('Projects needing attention now', 'coordina'))}</h3>${routeButton(__('Projects', 'coordina'), { page: 'coordina-projects' })}</div>${atRisk}</section><section class="coordina-card"><div class="coordina-section-header"><h3>${escapeHtml(__('Upcoming deadlines', 'coordina'))}</h3></div>${deadlines}</section></div><div class="coordina-columns"><section class="coordina-card coordina-card--wide"><div class="coordina-section-header"><h3>${escapeHtml(__('Overdue tasks', 'coordina'))}</h3>${routeButton(__('My Work', 'coordina'), { page: 'coordina-my-work' })}</div>${overdue}</section><section class="coordina-card"><div class="coordina-section-header"><h3>${escapeHtml(__('Pending approvals', 'coordina'))}</h3>${routeButton(__('Approvals', 'coordina'), { page: 'coordina-approvals' })}</div>${approvals}</section></div><div class="coordina-columns"><section class="coordina-card coordina-card--wide"><div class="coordina-section-header"><h3>${escapeHtml(__('Recent activity', 'coordina'))}</h3></div>${activity}</section><section class="coordina-card"><div class="coordina-section-header"><h3>${escapeHtml(__('Scope', 'coordina'))}</h3></div><div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-summary-chip"><strong>${escapeHtml(data.scope || '')}</strong>${escapeHtml(__('Data scope', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${escapeHtml(data.roleMode || '')}</strong>${escapeHtml(__('Role mode', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(kpis.activeProjects || 0)}</strong>${escapeHtml(__('Active projects', 'coordina'))}</span></div><p class="coordina-empty-inline">${escapeHtml(__('Use Dashboard to monitor progress. Go to My Work when you are ready to act.', 'coordina'))}</p></section></div></section>`;
}
function calendarItem(item) {
	const route = item.route || { page: 'coordina-task' };
	return `<button class="coordina-calendar__item type-${escapeHtml(item.type || 'task')}" data-action="open-route" data-page="${route.page || ''}" data-project-id="${route.project_id || ''}" data-project-tab="${route.project_tab || ''}" data-task-id="${route.task_id || ''}" data-milestone-id="${route.milestone_id || ''}" data-risk-issue-id="${route.risk_issue_id || ''}"><span class="coordina-calendar__item-label">${escapeHtml(item.label)}</span><strong>${iconLabel(item.type || pageIconType(route.page), item.title)}</strong><span class="coordina-calendar__item-meta">${escapeHtml(item.projectLabel || __('Standalone', 'coordina'))}${item.personLabel ? ` - ${escapeHtml(item.personLabel)}` : ''}</span></button>`;
}

function calendarPage() {
	const data = state.calendar || { summary: {}, days: [], range: {}, view: 'month', focusDate: todayKey() };
	const filters = state.calendarFilters || defaultCalendarFilters();
	const shell = state.shell || {};
	const projectOptions = (shell.projects || []).map((project) => `<option value="${project.id}" ${String(filters.project_id) === String(project.id) ? 'selected' : ''}>${escapeHtml(project.label)}</option>`).join('');
	const personOptions = (shell.users || []).map((user) => `<option value="${user.id}" ${String(filters.person_user_id) === String(user.id) ? 'selected' : ''}>${escapeHtml(user.label)}</option>`).join('');
	const summary = data.summary || {};
	const summaryRow = `<div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-summary-chip"><strong>${Number(summary.total || 0)}</strong>${escapeHtml(__('Scheduled items', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(summary.tasks || 0)}</strong>${escapeHtml(__('Task due dates', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(summary.projects || 0)}</strong>${escapeHtml(__('Project target ends', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(summary.overdue || 0)}</strong>${escapeHtml(__('Overdue tasks', 'coordina'))}</span></div>`;
	const weekdayRow = (data.days || []).slice(0, 7).map((day) => `<div class="coordina-calendar__weekday">${escapeHtml(day.weekdayLabel)}</div>`).join('');
	const dayCells = (data.days || []).map((day) => {
		const items = (day.items || []).slice(0, 4).map(calendarItem).join('');
		const overflow = (day.items || []).length > 4 ? `<div class="coordina-calendar__more">+${(day.items || []).length - 4} ${escapeHtml(__('more', 'coordina'))}</div>` : '';
		return `<article class="coordina-calendar__day ${day.isToday ? 'is-today' : ''} ${day.isCurrentPeriod ? '' : 'is-outside'}"><header class="coordina-calendar__day-head"><span>${escapeHtml(day.weekdayLabel)}</span><strong>${escapeHtml(day.dayNumber)}</strong></header>${items || `<p class="coordina-empty-inline">${escapeHtml(__('No dated work', 'coordina'))}</p>`}${overflow}</article>`;
	}).join('');
	return `<section class="coordina-page">${pageHeading('coordina-calendar', `${canAccessPage('coordina-projects') ? `<button class="button" data-action="open-route" data-page="coordina-projects">${escapeHtml(__('Projects', 'coordina'))}</button>` : ''}${canAccessPage('coordina-tasks') ? `<button class="button button-primary" data-action="open-route" data-page="coordina-tasks">${escapeHtml(__('Open task list', 'coordina'))}</button>` : ''}`, { title: __('Calendar', 'coordina'), description: __('See work by date and open the related item when you need details.', 'coordina') })}<section class="coordina-card coordina-card--notice"><p>${escapeHtml(__('Use Calendar to review dates and deadlines. Open the task, request, or project to make changes.', 'coordina'))}</p></section>${summaryRow}<div class="coordina-card coordina-period-bar"><div class="coordina-period-nav"><button class="button" data-action="calendar-shift" data-direction="-1">${escapeHtml(__('Previous', 'coordina'))}</button><button class="button" data-action="calendar-today">${escapeHtml(__('Today', 'coordina'))}</button><button class="button" data-action="calendar-shift" data-direction="1">${escapeHtml(__('Next', 'coordina'))}</button></div><div class="coordina-period-label"><strong>${escapeHtml((data.range && data.range.label) || '')}</strong></div><div class="coordina-period-actions"><input type="date" name="calendar-focus-date" value="${escapeHtml(filters.focus_date || todayKey())}" /><select name="calendar-view"><option value="month" ${filters.view === 'month' ? 'selected' : ''}>${escapeHtml(__('Month', 'coordina'))}</option><option value="week" ${filters.view === 'week' ? 'selected' : ''}>${escapeHtml(__('Week', 'coordina'))}</option></select></div></div><div class="coordina-filter-bar coordina-card coordina-filter-bar--calendar"><select name="calendar-object-type"><option value="all">${escapeHtml(__('All dated items', 'coordina'))}</option><option value="task" ${filters.object_type === 'task' ? 'selected' : ''}>${escapeHtml(__('Tasks', 'coordina'))}</option><option value="project" ${filters.object_type === 'project' ? 'selected' : ''}>${escapeHtml(__('Projects', 'coordina'))}</option></select><select name="calendar-person"><option value="">${escapeHtml(__('All people', 'coordina'))}</option>${personOptions}</select><select name="calendar-project"><option value="">${escapeHtml(__('All projects', 'coordina'))}</option><option value="0" ${String(filters.project_id) === '0' ? 'selected' : ''}>${escapeHtml(__('Standalone tasks', 'coordina'))}</option>${projectOptions}</select><button class="button" data-action="calendar-apply">${escapeHtml(__('Apply filters', 'coordina'))}</button></div><div class="coordina-card coordina-calendar-shell"><div class="coordina-calendar__weekdays">${weekdayRow}</div><div class="coordina-calendar__grid view-${escapeHtml(filters.view || 'month')}">${dayCells || `<p class="coordina-empty-inline">${escapeHtml(__('No dated work falls in this range yet.', 'coordina'))}</p>`}</div></div></section>`;
}

function workloadPressureBadge(value) {
	const tone = value === 'high' ? 'status-blocked' : value === 'medium' ? 'status-waiting' : 'status-clear';
	return `<span class="coordina-status-badge ${tone}">${escapeHtml(nice(value))}</span>`;
}

function workloadTaskList(tasks) {
	if (!tasks.length) {
		return `<p class="coordina-empty-inline">${escapeHtml(__('No highlighted tasks in this window.', 'coordina'))}</p>`;
	}
	return `<ul class="coordina-work-list coordina-work-list--compact">${tasks.map((task) => `<li><button class="coordina-link-button" data-action="open-route" data-page="${task.route && task.route.page ? task.route.page : 'coordina-task'}" data-project-id="${task.route && task.route.project_id ? task.route.project_id : ''}" data-project-tab="${task.route && task.route.project_tab ? task.route.project_tab : ''}" data-task-id="${task.route && task.route.task_id ? task.route.task_id : ''}" data-milestone-id="${task.route && task.route.milestone_id ? task.route.milestone_id : ''}" data-risk-issue-id="${task.route && task.route.risk_issue_id ? task.route.risk_issue_id : ''}">${iconLabel('task', task.title)}</button><div class="coordina-work-meta"><span class="coordina-status-badge status-${escapeHtml(task.status)}">${escapeHtml(nice(task.status))}</span><span>${escapeHtml(task.projectLabel || __('Standalone', 'coordina'))}</span><span>${escapeHtml(dateLabel(task.dueDate))}</span></div></li>`).join('')}</ul>`;
}

function workloadPage() {
	const data = state.workload || { summary: {}, rows: [], week: {} };
	const filters = state.workloadFilters || defaultWorkloadFilters();
	const summary = data.summary || {};
	const rows = data.rows || [];
	const shell = state.shell || { statuses: {} };
	const personOptions = (shell.users || []).map((user) => `<option value="${user.id}" ${String(filters.person_user_id) === String(user.id) ? 'selected' : ''}>${escapeHtml(user.label)}</option>`).join('');
	const projectOptions = (shell.projects || []).map((project) => `<option value="${project.id}" ${String(filters.project_id) === String(project.id) ? 'selected' : ''}>${escapeHtml(project.label)}</option>`).join('');
	const statusOptions = ((shell.statuses && shell.statuses.tasks) || []).filter((status) => !['done', 'cancelled'].includes(status)).map((status) => `<option value="${status}" ${String(filters.status) === String(status) ? 'selected' : ''}>${escapeHtml(nice(status))}</option>`).join('');
	const priorityOptions = (shell.priorities || []).map((priority) => `<option value="${priority}" ${String(filters.priority) === String(priority) ? 'selected' : ''}>${escapeHtml(nice(priority))}</option>`).join('');
	const summaryRow = `<div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-summary-chip"><strong>${Number(summary.people || 0)}</strong>${escapeHtml(__('People in view', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(summary.overloaded || 0)}</strong>${escapeHtml(__('High pressure', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(summary.watchList || 0)}</strong>${escapeHtml(__('Watch list', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(summary.unassigned || 0)}</strong>${escapeHtml(__('Unassigned', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(summary.overdueTasks || 0)}</strong>${escapeHtml(__('Overdue tasks', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(summary.blockedTasks || 0)}</strong>${escapeHtml(__('Blocked tasks', 'coordina'))}</span></div>`;
	const tableRows = rows.map((row) => `<tr><td><strong>${escapeHtml(row.personLabel)}</strong></td><td>${Number(row.openTasks || 0)}</td><td>${Number(row.overdue || 0)}</td><td>${Number(row.dueThisWeek || 0)}</td><td>${Number(row.blocked || 0)}</td><td>${Number(row.loadScore || 0)}</td><td>${workloadPressureBadge(row.pressure || 'low')}</td><td>${workloadTaskList(row.tasks || [])}</td></tr>`).join('');
	return `<section class="coordina-page">${pageHeading('coordina-workload', `${canAccessPage('coordina-calendar') ? `<button class="button" data-action="open-route" data-page="coordina-calendar">${escapeHtml(__('Calendar', 'coordina'))}</button>` : ''}${canAccessPage('coordina-projects') ? `<button class="button button-primary" data-action="open-route" data-page="coordina-projects">${escapeHtml(__('Managed projects', 'coordina'))}</button>` : ''}`, { title: __('Workload', 'coordina'), description: __('See who is busy and where work may need to be rebalanced.', 'coordina') })}<section class="coordina-card coordina-card--notice"><p>${escapeHtml(__('Use Workload to spot pressure and rebalance assignments. Open the project or task to make changes.', 'coordina'))}</p></section>${summaryRow}<div class="coordina-card coordina-period-bar"><div class="coordina-period-nav"><button class="button" data-action="workload-shift" data-direction="-1">${escapeHtml(__('Previous week', 'coordina'))}</button><button class="button" data-action="workload-today">${escapeHtml(__('Current week', 'coordina'))}</button><button class="button" data-action="workload-shift" data-direction="1">${escapeHtml(__('Next week', 'coordina'))}</button></div><div class="coordina-period-label"><strong>${escapeHtml((data.week && data.week.label) || '')}</strong></div><div class="coordina-period-actions"><input type="date" name="workload-week-start" value="${escapeHtml(filters.week_start || app.weekStartKey())}" /></div></div><div class="coordina-filter-bar coordina-card coordina-filter-bar--workload"><select name="workload-status"><option value="">${escapeHtml(__('All open statuses', 'coordina'))}</option>${statusOptions}</select><select name="workload-priority"><option value="">${escapeHtml(__('All priorities', 'coordina'))}</option>${priorityOptions}</select><select name="workload-person"><option value="">${escapeHtml(__('All assignees', 'coordina'))}</option>${personOptions}</select><select name="workload-project"><option value="">${escapeHtml(__('All projects', 'coordina'))}</option><option value="0" ${String(filters.project_id) === '0' ? 'selected' : ''}>${escapeHtml(__('Standalone tasks', 'coordina'))}</option>${projectOptions}</select><button class="button" data-action="workload-apply">${escapeHtml(__('Apply filters', 'coordina'))}</button></div><div class="coordina-card coordina-table-wrap">${rows.length ? `<table class="coordina-table widefat striped"><thead><tr><th>${escapeHtml(__('Person', 'coordina'))}</th><th>${escapeHtml(__('Open', 'coordina'))}</th><th>${escapeHtml(__('Overdue', 'coordina'))}</th><th>${escapeHtml(__('Due this week', 'coordina'))}</th><th>${escapeHtml(__('Blocked', 'coordina'))}</th><th>${escapeHtml(__('Load score', 'coordina'))}</th><th>${escapeHtml(__('Pressure', 'coordina'))}</th><th>${escapeHtml(__('Focus tasks', 'coordina'))}</th></tr></thead><tbody>${tableRows}</tbody></table>` : `<section class="coordina-empty-state"><h3>${escapeHtml(__('No workload pressure found for this filter set', 'coordina'))}</h3><p>${escapeHtml(__('Try another week or widen the current filters to see assignment pressure across your managed work.', 'coordina'))}</p></section>`}</div></section>`;
}


Object.assign(app, {
pageHeading,
	openProjectButton,
	openTaskButton,
modulePage,
workspaceBoard,
workspaceWorkTab,
workspaceGanttTab,
activityList,
	activityPager,
	shortText,
	optionList,
	settingsTextarea,
	settingsCheckbox,
	settingsHint,
workspaceActivityTab,
workspaceSettingsTab,
workspaceTabBody,
workspacePage,
	routeButton,
	dashboardList,
taskPage,
milestonePage,
riskIssuePage,
});

window.CoordinaAdminApp = app;
}());
