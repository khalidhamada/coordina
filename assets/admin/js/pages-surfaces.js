(function () {
const app = window.CoordinaAdminApp || {};

if (!app.root || !app.state) {
return;
}

const { state, escapeHtml, __, nice, dateLabel, todayKey, shiftDate, defaultCalendarFilters, defaultWorkloadFilters, canAccessPage, pageHeading, openProjectButton, openTaskButton, activityList, activityPager, shortText, routeButton, dashboardList } = app;

function iconLabel(type, label, className) {
	if (typeof app.iconLabel === 'function') {
		return app.iconLabel(type, label, className);
	}
	return escapeHtml(label || '');
}

function notificationList(items, compact, options = {}) {
	const config = Object.assign({ showOpenAction: true }, options || {});
	if (!items.length) {
		return `<p class="coordina-empty-inline">${escapeHtml(__('Nothing is waiting in your inbox.', 'coordina'))}</p>`;
	}
	return `<ul class="coordina-notification-list ${compact ? 'is-compact' : ''}">${items.map((item) => `<li class="${item.is_read ? 'is-read' : 'is-unread'}"><div><strong>${escapeHtml(item.title)}</strong><p>${escapeHtml(item.body || __('No message body yet.', 'coordina'))}</p><span class="coordina-cell-secondary">${escapeHtml(dateLabel(item.created_at))}</span></div><div class="coordina-inline-actions">${config.showOpenAction && item.action_url ? `<button class="button button-small" data-action="open-notification-link" data-id="${item.id}" data-url="${escapeHtml(item.action_url)}">${escapeHtml(__('Open', 'coordina'))}</button>` : ''}<button class="button button-small" data-action="toggle-notification" data-id="${item.id}" data-read="${item.is_read ? '0' : '1'}">${escapeHtml(item.is_read ? __('Mark unread', 'coordina') : __('Mark read', 'coordina'))}</button></div></li>`).join('')}</ul>`;
}

function myWorkReason(item, fallbackKey) {
	const key = item.my_work_reason_key || fallbackKey || '';
	const labels = {
		'overdue-blocked': __('Overdue and blocked', 'coordina'),
		overdue: __('Overdue', 'coordina'),
		blocked: __('Blocked', 'coordina'),
		waiting: __('Waiting', 'coordina'),
		'due-today': __('Due today', 'coordina'),
		'assigned-recently': __('Assigned recently', 'coordina'),
		'up-next': __('Coming next', 'coordina'),
	};
	const tones = {
		'overdue-blocked': 'danger',
		overdue: 'danger',
		blocked: 'warning',
		waiting: 'neutral',
		'due-today': 'warning',
		'assigned-recently': 'accent',
		'up-next': 'neutral',
	};
	const guidance = {
		'overdue-blocked': __('Clear the blocker and reset the due plan before it slips further.', 'coordina'),
		overdue: __('Recover the due date or finish the work now.', 'coordina'),
		blocked: __('Resolve the blocker before moving to the rest of the queue.', 'coordina'),
		waiting: __('Follow up with the blocker owner and move it back into active execution when ready.', 'coordina'),
		'due-today': __('Finish this today or reset expectations clearly.', 'coordina'),
		'assigned-recently': __('Review the scope and decide the next concrete step.', 'coordina'),
		'up-next': __('Keep this ready after today\'s priority items are handled.', 'coordina'),
	};
	return {
		key,
		label: item.my_work_reason_label || labels[key] || '',
		tone: item.my_work_reason_tone || tones[key] || 'neutral',
		guidance: item.my_work_guidance || guidance[key] || '',
	};
}

function myWorkReasonBadge(reason) {
	if (!reason || !reason.label) {
		return '';
	}
	return `<span class="coordina-my-work-reason coordina-my-work-reason--${escapeHtml(reason.tone || 'neutral')}">${escapeHtml(reason.label)}</span>`;
}

function myWorkContextReason(item, fallbackKey, options = {}) {
	const config = Object.assign({ allowGeneric: true }, options || {});
	const reason = myWorkReason(item, fallbackKey);
	if (!config.allowGeneric && reason && reason.key === 'up-next') {
		return null;
	}
	return reason;
}

function myWorkShowGuidance() {
	return !!(state.shell && state.shell.myWorkCardGuidanceEnabled);
}

function myWorkShowActions() {
	return !!(state.shell && state.shell.myWorkCardActionsEnabled);
}

function myWorkTaskList(items, emptyMessage, options = {}) {
	const config = Object.assign({ compact: false, showActions: true, fallbackReason: '' }, options || {});
	if (!items.length) {
		return `<p class="coordina-empty-inline">${escapeHtml(emptyMessage)}</p>`;
	}
	return `<ul class="coordina-work-list coordina-my-work-list ${config.compact ? 'coordina-my-work-list--compact' : ''}">${items.map((item) => {
		const reason = myWorkContextReason(item, config.fallbackReason, { allowGeneric: true });
		const note = myWorkShowGuidance() ? shortText(item.blocked_reason || reason.guidance || '', config.compact ? 96 : 140) : '';
		const projectLink = Number(item.project_id || 0) > 0 ? openProjectButton(item.project_id, item.project_label, 'work') : `<span>${escapeHtml(__('Standalone', 'coordina'))}</span>`;
		const dueLabel = item.due_date ? dateLabel(item.due_date) : __('No due date', 'coordina');
		const tone = reason && reason.tone ? reason.tone : (item.blocked ? 'danger' : (String(item.status || '') === 'waiting' ? 'warning' : 'neutral'));
		const ownerLabel = item.assignee_label || __('Unassigned', 'coordina');
		const completion = Number(item.completion_percent || 0);
		const actions = config.showActions && myWorkShowActions()
			? `<div class="coordina-inline-actions coordina-my-work-row__actions"><button class="button button-small" data-action="quick-status" data-id="${item.id}" data-status="done">${escapeHtml(__('Done', 'coordina'))}</button><button class="button button-small" data-action="quick-status" data-id="${item.id}" data-status="waiting">${escapeHtml(__('Waiting', 'coordina'))}</button>${item.can_post_update ? `<button class="button button-small" data-action="open-discussion-create" data-object-type="task" data-object-id="${item.id}" data-object-label="${escapeHtml(item.title || __('Task', 'coordina'))}" data-lock-context="1">${escapeHtml(__('Add update', 'coordina'))}</button>` : ''}</div>`
			: '';
		return `<li><article class="coordina-my-work-row coordina-my-work-row--${escapeHtml(tone)} ${config.compact ? 'coordina-my-work-row--compact' : ''}"><div class="coordina-my-work-row__main"><div class="coordina-my-work-row__top"><div class="coordina-my-work-row__title">${openTaskButton(item.id, item.title, item.project_id, item.project_id ? 'work' : '')}</div><div class="coordina-my-work-row__badges">${myWorkReasonBadge(reason)}<span class="coordina-status-badge status-${escapeHtml(item.status)}">${escapeHtml(nice(item.status || 'new'))}</span>${item.blocked && reason.key !== 'blocked' && reason.key !== 'overdue-blocked' ? `<span class="coordina-status-badge status-blocked">${escapeHtml(__('Blocked', 'coordina'))}</span>` : ''}${item.approval_required && item.approval_state === 'pending' ? `<span class="coordina-status-badge">${escapeHtml(__('Approval pending', 'coordina'))}</span>` : ''}</div></div><div class="coordina-work-meta coordina-my-work-row__meta">${projectLink}<span>${escapeHtml(dueLabel)}</span><span>${escapeHtml(nice(item.priority || 'normal'))}</span><span>${escapeHtml(ownerLabel)}</span></div><div class="coordina-my-work-row__support"><span>${escapeHtml(`${completion}% ${__('complete', 'coordina')}`)}</span>${item.approval_required ? `<span>${escapeHtml(__('Approval path', 'coordina'))}</span>` : ''}</div>${note ? `<p class="coordina-my-work-row__note">${escapeHtml(note)}</p>` : ''}</div>${actions}</article></li>`;
	}).join('')}</ul>`;
}

function myWorkApprovalList(items) {
	if (!items.length) {
		return `<p class="coordina-empty-inline">${escapeHtml(__('Approvals assigned to you will appear here.', 'coordina'))}</p>`;
	}
	return `<ul class="coordina-work-list coordina-work-list--compact coordina-my-work-decision-list">${items.map((item) => `<li><div class="coordina-my-work-row coordina-my-work-row--accent coordina-my-work-row--compact"><div class="coordina-my-work-row__main"><div class="coordina-my-work-row__top"><div class="coordina-my-work-row__title"><button class="coordina-link-button" data-action="open-record" data-module="approvals" data-id="${item.id}">${iconLabel(item.object_type || 'approval', item.object_label || nice(item.object_type || 'approval'))}</button></div><div class="coordina-my-work-row__badges"><span class="coordina-my-work-reason coordina-my-work-reason--accent">${escapeHtml(__('Decision required', 'coordina'))}</span><span class="coordina-status-badge status-${escapeHtml(item.status || 'pending')}">${escapeHtml(nice(item.status || 'pending'))}</span></div></div><div class="coordina-work-meta coordina-my-work-row__meta"><span>${escapeHtml(item.project_label || __('Standalone', 'coordina'))}</span><span>${escapeHtml(nice(item.object_type || 'approval'))}</span><span>${escapeHtml(dateLabel(item.submitted_at))}</span></div></div></div></li>`).join('')}</ul>`;
}

function myWorkNotificationSummary(items) {
	const visible = (items || []).slice(0, 5);
	if (!visible.length) {
		return `<p class="coordina-empty-inline">${escapeHtml(__('No recent updates yet.', 'coordina'))}</p>`;
	}
	return `<ul class="coordina-my-work-note-list">${visible.map((item) => `<li class="coordina-my-work-note ${item.is_read ? 'is-read' : 'is-unread'}"><div class="coordina-my-work-note__head"><strong>${escapeHtml(item.title || __('Update', 'coordina'))}</strong>${!item.is_read ? `<span class="coordina-my-work-note__pill">${escapeHtml(__('Unread', 'coordina'))}</span>` : ''}</div><p>${escapeHtml(shortText(item.body || __('No message body yet.', 'coordina'), 120))}</p><span class="coordina-cell-secondary">${escapeHtml(dateLabel(item.created_at))}</span></li>`).join('')}</ul>`;
}

function myWorkBoardCard(item) {
	const dueLabel = item.due_date ? dateLabel(item.due_date) : __('No due date', 'coordina');
	const projectLabel = Number(item.project_id || 0) > 0 ? openProjectButton(item.project_id, item.project_label, 'work') : `<span>${escapeHtml(__('Standalone', 'coordina'))}</span>`;
	const reason = myWorkContextReason(item, item.status === 'waiting' ? 'waiting' : 'up-next', { allowGeneric: false });
	const note = myWorkShowGuidance() ? shortText(item.blocked_reason || (reason && reason.guidance) || '', 96) : '';
	const isBlocked = !!item.blocked || item.status === 'blocked';
	const tone = reason && reason.tone ? reason.tone : (isBlocked ? 'danger' : (String(item.status || '') === 'waiting' ? 'warning' : 'neutral'));
	const actions = myWorkShowActions() ? [
		!isBlocked && item.status !== 'in-progress' ? `<button class="button button-small" data-action="quick-status" data-id="${item.id}" data-status="in-progress">${escapeHtml(__('Start', 'coordina'))}</button>` : '',
		item.status !== 'waiting' ? `<button class="button button-small" data-action="quick-status" data-id="${item.id}" data-status="waiting">${escapeHtml(__('Waiting', 'coordina'))}</button>` : '',
		`<button class="button button-small" data-action="quick-status" data-id="${item.id}" data-status="done">${escapeHtml(__('Done', 'coordina'))}</button>`,
	] : [];
	const actionMarkup = actions.filter(Boolean).join('');
	return `<article class="coordina-my-work-board-card coordina-my-work-board-card--${escapeHtml(tone)}"><div class="coordina-my-work-board-card__head"><div class="coordina-my-work-board-card__title">${openTaskButton(item.id, item.title, item.project_id, item.project_id ? 'work' : '')}</div><div class="coordina-my-work-board-card__badges">${myWorkReasonBadge(reason)}${item.approval_required && item.approval_state === 'pending' ? `<span class="coordina-status-badge">${escapeHtml(__('Approval pending', 'coordina'))}</span>` : ''}</div></div><div class="coordina-work-meta coordina-my-work-board-card__meta">${projectLabel}<span>${escapeHtml(dueLabel)}</span><span>${escapeHtml(nice(item.priority || 'normal'))}</span></div>${note ? `<p class="coordina-my-work-board-card__note">${escapeHtml(note)}</p>` : ''}${actionMarkup ? `<div class="coordina-inline-actions coordina-my-work-board-card__actions">${actionMarkup}</div>` : ''}</article>`;
}

function myWorkTasksFilterBar(filters) {
	const shell = state.shell || {};
	const projectOptions = (shell.projects || []).map((project) => `<option value="${project.id}" ${String(filters.project_id || '') === String(project.id) ? 'selected' : ''}>${escapeHtml(project.label)}</option>`).join('');
	const taskStatuses = shell.statuses && shell.statuses.tasks ? shell.statuses.tasks : [];
	const orderOptions = [
		{ value: 'updated_at', label: __('Last updated', 'coordina') },
		{ value: 'due_date', label: __('Due date', 'coordina') },
		{ value: 'priority', label: __('Priority', 'coordina') },
		{ value: 'title', label: __('Title', 'coordina') },
	];
	return `<div class="coordina-filter-bar coordina-card coordina-filter-bar--tasks coordina-my-work-task-filters"><input type="search" name="my-work-task-search" value="${escapeHtml(filters.search || '')}" placeholder="${escapeHtml(__('Search your tasks', 'coordina'))}" /><select name="my-work-task-status"><option value="">${escapeHtml(__('All statuses', 'coordina'))}</option>${taskStatuses.map((status) => `<option value="${status}" ${String(filters.status || '') === String(status) ? 'selected' : ''}>${escapeHtml(nice(status))}</option>`).join('')}</select><select name="my-work-task-project-mode"><option value="all" ${filters.project_mode === 'all' ? 'selected' : ''}>${escapeHtml(__('All task types', 'coordina'))}</option><option value="project" ${filters.project_mode === 'project' ? 'selected' : ''}>${escapeHtml(__('Project-linked', 'coordina'))}</option><option value="standalone" ${filters.project_mode === 'standalone' ? 'selected' : ''}>${escapeHtml(__('Standalone', 'coordina'))}</option></select><select name="my-work-task-project"><option value="">${escapeHtml(__('All projects', 'coordina'))}</option>${projectOptions}</select><select name="my-work-task-orderby">${orderOptions.map((option) => `<option value="${option.value}" ${String(filters.orderby || 'updated_at') === option.value ? 'selected' : ''}>${escapeHtml(option.label)}</option>`).join('')}</select><select name="my-work-task-order"><option value="desc" ${String(filters.order || 'desc') === 'desc' ? 'selected' : ''}>${escapeHtml(__('Newest first', 'coordina'))}</option><option value="asc" ${String(filters.order || 'desc') === 'asc' ? 'selected' : ''}>${escapeHtml(__('Oldest first', 'coordina'))}</option></select><button class="button" data-action="apply-my-work-task-filters">${escapeHtml(__('Apply', 'coordina'))}</button></div>`;
}

function myWorkTaskCard(item) {
	const reason = myWorkContextReason(item, item.status === 'waiting' ? 'waiting' : 'up-next', { allowGeneric: false });
	const note = myWorkShowGuidance() ? shortText(item.blocked_reason || (reason && reason.guidance) || '', 108) : '';
	const dueLabel = item.due_date ? dateLabel(item.due_date) : __('No due date', 'coordina');
	const projectLink = Number(item.project_id || 0) > 0 ? openProjectButton(item.project_id, item.project_label, 'work') : `<span>${escapeHtml(__('Standalone', 'coordina'))}</span>`;
	const tone = reason && reason.tone ? reason.tone : (item.blocked ? 'danger' : (String(item.status || '') === 'waiting' ? 'warning' : 'neutral'));
	const actions = myWorkShowActions()
		? `<div class="coordina-inline-actions coordina-my-work-task-card__actions">${String(item.status || '') !== 'in-progress' ? `<button class="button button-small" data-action="quick-status" data-id="${item.id}" data-status="in-progress">${escapeHtml(__('Start', 'coordina'))}</button>` : ''}${String(item.status || '') !== 'waiting' ? `<button class="button button-small" data-action="quick-status" data-id="${item.id}" data-status="waiting">${escapeHtml(__('Waiting', 'coordina'))}</button>` : ''}<button class="button button-small" data-action="quick-status" data-id="${item.id}" data-status="done">${escapeHtml(__('Done', 'coordina'))}</button></div>`
		: '';
	return `<article class="coordina-card coordina-my-work-task-card coordina-my-work-task-card--${escapeHtml(tone)}"><div class="coordina-my-work-task-card__head"><div><h4>${openTaskButton(item.id, item.title, item.project_id, item.project_id ? 'work' : '')}</h4><div class="coordina-work-meta coordina-my-work-task-card__meta">${projectLink}<span>${escapeHtml(dueLabel)}</span><span>${escapeHtml(item.assignee_label || __('Unassigned', 'coordina'))}</span></div></div><div class="coordina-my-work-task-card__badges">${myWorkReasonBadge(reason)}<span class="coordina-status-badge status-${escapeHtml(item.status || 'new')}">${escapeHtml(nice(item.status || 'new'))}</span></div></div>${note ? `<p class="coordina-my-work-task-card__note">${escapeHtml(note)}</p>` : ''}${actions}</article>`;
}

function myWorkTasksGrid(collection) {
	const items = collection && Array.isArray(collection.items) ? collection.items : [];
	if (!items.length) {
		return `<section class="coordina-card coordina-empty-state"><h3>${escapeHtml(__('No tasks match these filters', 'coordina'))}</h3><p>${escapeHtml(__('Adjust the filters to see more of your assigned work.', 'coordina'))}</p></section>`;
	}
	return `<div class="coordina-my-work-task-grid">${items.map((item) => myWorkTaskCard(item)).join('')}</div>`;
}

function myWorkTasksPager(collection) {
	const page = Number(collection && collection.page ? collection.page : 1);
	const totalPages = Number(collection && collection.totalPages ? collection.totalPages : 1);
	if (totalPages <= 1) {
		return '';
	}
	return `<nav class="coordina-pagination coordina-my-work-task-pager"><button class="button" data-action="page-my-work-tasks" data-page="${Math.max(1, page - 1)}" ${page <= 1 ? 'disabled' : ''}>${escapeHtml(__('Previous', 'coordina'))}</button><span>${escapeHtml(`${__('Page', 'coordina')} ${page} ${__('of', 'coordina')} ${totalPages}`)}</span><button class="button" data-action="page-my-work-tasks" data-page="${Math.min(totalPages, page + 1)}" ${page >= totalPages ? 'disabled' : ''}>${escapeHtml(__('Next', 'coordina'))}</button></nav>`;
}

function myWorkBoardLaneKey(item) {
	const status = String(item.status || 'new');
	if (!!item.blocked || status === 'blocked') {
		return 'blocked';
	}
	if (status === 'not-started' || status === 'new') {
		return 'not-started';
	}
	if (status === 'todo') {
		return 'to-do';
	}
	return status;
}

function myWorkBoard(items) {
	const baseLanes = [
		{ key: 'not-started', label: __('Not started', 'coordina') },
		{ key: 'to-do', label: __('To do', 'coordina') },
		{ key: 'in-progress', label: __('In progress', 'coordina') },
		{ key: 'in-review', label: __('In review', 'coordina') },
		{ key: 'waiting', label: __('Waiting', 'coordina') },
		{ key: 'blocked', label: __('Blocked', 'coordina') },
	];
	const extraKeys = Array.from(new Set((items || []).map((item) => myWorkBoardLaneKey(item)).filter((key) => !baseLanes.some((lane) => lane.key === key))));
	const lanes = baseLanes.concat(extraKeys.map((key) => ({ key, label: nice(key) })));
	return `<div class="coordina-my-work-board">${lanes.map((lane) => {
		const laneItems = (items || []).filter((item) => myWorkBoardLaneKey(item) === lane.key);
		const laneNote = lane.key === 'blocked'
			? __('Items that need a blocker cleared before they can move.', 'coordina')
			: lane.key === 'waiting'
				? __('Items paused until someone else responds or finishes their part.', 'coordina')
				: lane.key === 'not-started'
					? __('Work that has not been picked up yet.', 'coordina')
					: __('Work currently sitting in this stage.', 'coordina');
		return `<section class="coordina-card coordina-my-work-board-lane coordina-my-work-board-lane--${escapeHtml(lane.key)}"><div class="coordina-section-header"><div><h4>${escapeHtml(lane.label)}</h4><p class="coordina-section-note">${escapeHtml(laneNote)}</p></div><span class="coordina-summary-chip"><strong>${laneItems.length}</strong>${escapeHtml(__('Items', 'coordina'))}</span></div>${laneItems.length ? `<div class="coordina-my-work-board-lane__stack">${laneItems.map((item) => myWorkBoardCard(item)).join('')}</div>` : `<p class="coordina-empty-inline">${escapeHtml(__('Nothing is in this lane right now.', 'coordina'))}</p>`}</section>`;
	}).join('')}</div>`;
}

function myWorkMiniCalendar(items) {
	const today = new Date(`${todayKey()}T00:00:00`);
	const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
	const gridStart = new Date(monthStart);
	gridStart.setDate(monthStart.getDate() - ((monthStart.getDay() + 6) % 7));
	const days = [];
	const itemBuckets = (items || []).reduce((result, item) => {
		const dueKey = String(item.due_date || '').slice(0, 10);
		if (!dueKey) {
			return result;
		}
		result[dueKey] = result[dueKey] || [];
		result[dueKey].push(item);
		return result;
	}, {});
	for (let index = 0; index < 42; index += 1) {
		const current = new Date(gridStart);
		current.setDate(gridStart.getDate() + index);
		const key = `${current.getFullYear()}-${`${current.getMonth() + 1}`.padStart(2, '0')}-${`${current.getDate()}`.padStart(2, '0')}`;
		const dayItems = itemBuckets[key] || [];
		const hasBlocked = dayItems.some((item) => !!item.blocked || String(item.status || '') === 'blocked');
		const hasWaiting = dayItems.some((item) => String(item.status || '') === 'waiting');
		const tone = hasBlocked ? 'danger' : dayItems.length >= 3 ? 'accent' : hasWaiting ? 'warning' : dayItems.length ? 'soft' : 'empty';
		const title = dayItems.length
			? `${key}\n${dayItems.map((item) => `- ${item.title || __('Task', 'coordina')}`).join('\n')}`
			: key;
		days.push(`<button class="coordina-my-work-mini-calendar__day tone-${escapeHtml(tone)} ${dayItems.length ? 'has-items' : ''} ${current.getMonth() === today.getMonth() ? '' : 'is-outside'} ${key === todayKey() ? 'is-today' : ''}" type="button" data-action="open-route" data-page="coordina-calendar" title="${escapeHtml(title)}"><span>${escapeHtml(current.getDate())}</span>${dayItems.length ? `<strong class="coordina-my-work-mini-calendar__count">${dayItems.length}</strong>` : ''}</button>`);
	}
	return `<section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('This month', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('A quick scan of your dated work this month. Hover a day to preview its tasks.', 'coordina'))}</p></div>${canAccessPage('coordina-calendar') ? `<button class="button button-small" data-action="open-route" data-page="coordina-calendar">${escapeHtml(__('Open calendar', 'coordina'))}</button>` : ''}</div><div class="coordina-my-work-mini-calendar"><div class="coordina-my-work-mini-calendar__weekdays">${['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map((day) => `<span>${escapeHtml(__(day, 'coordina'))}</span>`).join('')}</div><div class="coordina-my-work-mini-calendar__grid">${days.join('')}</div></div></section>`;
}

function myWorkUpcomingList(items) {
	if (!items.length) {
		return `<p class="coordina-empty-inline">${escapeHtml(__('No dated work is coming up in the next week.', 'coordina'))}</p>`;
	}
	return `<ul class="coordina-work-list coordina-work-list--compact coordina-my-work-upcoming-list">${items.map((item) => `<li><div class="coordina-cell-primary">${openTaskButton(item.id, item.title, item.project_id, item.project_id ? 'work' : '')}<span class="coordina-cell-secondary">${escapeHtml(item.project_label || __('Standalone', 'coordina'))}</span></div><div class="coordina-work-meta"><span>${escapeHtml(dateLabel(item.due_date))}</span><span class="coordina-status-badge status-${escapeHtml(item.status || 'new')}">${escapeHtml(nice(item.status || 'new'))}</span></div></li>`).join('')}</ul>`;
}

function myWorkPage() {
	const data = state.myWork || { items: [], focusQueue: {}, sections: {}, summary: {}, pendingApprovals: [] };
	const view = ['board', 'tasks'].includes(state.myWorkView) ? state.myWorkView : 'queue';
	const items = Array.isArray(data.items) ? data.items : [];
	const taskCollection = state.myWorkTasksCollection || { items: [], total: 0, page: 1, totalPages: 1 };
	const taskFilters = state.myWorkTasksFilters || app.defaultMyWorkTaskFilters();
	const focusQueue = data.focusQueue || {};
	const sections = data.sections || {};
	const summary = data.summary || {};
	const approvals = Array.isArray(data.pendingApprovals) ? data.pendingApprovals.slice(0, 5) : [];
	const notificationItems = state.notifications && Array.isArray(state.notifications.items) ? state.notifications.items : [];
	const today = todayKey();
	const nextWeek = shiftDate(today, 'week', 1);
	const upcomingItems = items
		.filter((item) => {
			const dueKey = String(item.due_date || '').slice(0, 10);
			return dueKey && dueKey >= today && dueKey < nextWeek;
		})
		.sort((left, right) => String(left.due_date || '').localeCompare(String(right.due_date || '')))
		.slice(0, 6);
	const unreadNotifications = notificationItems.filter((item) => !item.is_read).length;
	const actions = [
		canAccessPage('coordina-calendar') ? `<button class="button" data-action="open-route" data-page="coordina-calendar">${escapeHtml(__('Calendar', 'coordina'))}</button>` : '',
		canAccessPage('coordina-files-discussion') ? `<button class="button" data-action="open-route" data-page="coordina-files-discussion">${escapeHtml(__('Files & discussions', 'coordina'))}</button>` : '',
		`<button class="button button-primary" data-action="open-task-create">${escapeHtml(__('Quick task', 'coordina'))}</button>`,
	].filter(Boolean).join('');
	const viewDescription = view === 'board'
		? __('Board shows the same personal work by status so you can spot bottlenecks quickly.', 'coordina')
		: view === 'tasks'
			? __('Tasks shows all of your assigned work with filters, sorting, and pagination.', 'coordina')
			: __('Queue keeps your day focused by urgency, commitments, and what needs a decision.', 'coordina');
	const viewTabs = `<div class="coordina-my-work-view-shell"><div class="coordina-my-work-view-header"><div><h3>${escapeHtml(__('How you want to work', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(viewDescription)}</p></div><div class="coordina-my-work-view-tabs" role="tablist" aria-label="${escapeHtml(__('My Work views', 'coordina'))}"><button class="coordina-tab ${view === 'queue' ? 'is-active' : ''}" type="button" role="tab" aria-selected="${view === 'queue' ? 'true' : 'false'}" data-action="switch-my-work-view" data-view="queue">${escapeHtml(__('Queue', 'coordina'))}</button><button class="coordina-tab ${view === 'board' ? 'is-active' : ''}" type="button" role="tab" aria-selected="${view === 'board' ? 'true' : 'false'}" data-action="switch-my-work-view" data-view="board">${escapeHtml(__('Board', 'coordina'))}</button><button class="coordina-tab ${view === 'tasks' ? 'is-active' : ''}" type="button" role="tab" aria-selected="${view === 'tasks' ? 'true' : 'false'}" data-action="switch-my-work-view" data-view="tasks">${escapeHtml(__('Tasks', 'coordina'))}</button></div></div></div>`;
	const signals = [
		{ label: __('Needs attention', 'coordina'), value: Number(summary.attention || 0), tone: Number(summary.attention || 0) > 0 ? 'danger' : 'neutral' },
		{ label: __('Due today', 'coordina'), value: Number(summary.dueToday || 0), tone: Number(summary.dueToday || 0) > 0 ? 'warning' : 'neutral' },
		{ label: __('Waiting', 'coordina'), value: Number(summary.waiting || 0), tone: Number(summary.waiting || 0) > 0 ? 'warning' : 'neutral' },
		{ label: __('Approvals', 'coordina'), value: Number(summary.pendingApprovals || 0), tone: Number(summary.pendingApprovals || 0) > 0 ? 'accent' : 'neutral' },
		{ label: __('Unread', 'coordina'), value: unreadNotifications, tone: unreadNotifications > 0 ? 'accent' : 'neutral' },
	].map((item) => `<article class="coordina-my-work-signal coordina-my-work-signal--${escapeHtml(item.tone)}"><span class="coordina-my-work-signal__label">${escapeHtml(item.label)}</span><strong class="coordina-my-work-signal__value">${escapeHtml(item.value)}</strong></article>`).join('');
	const focusGroups = [
		{ title: __('Needs attention now', 'coordina'), note: __('Handle overdue and blocked work first.', 'coordina'), items: focusQueue.attention || [], tone: 'danger' },
		{ title: __('Do today', 'coordina'), note: __('These items should be finished or updated today.', 'coordina'), items: focusQueue.today || [], tone: 'warning' },
		{ title: __('Coming next', 'coordina'), note: __('These are ready when today\'s urgent work is under control.', 'coordina'), items: focusQueue.upNext || [], tone: 'neutral' },
	].filter((group) => group.items.length).map((group) => `<section class="coordina-my-work-focus-group coordina-my-work-focus-group--${escapeHtml(group.tone)}"><div class="coordina-section-header"><div><h4>${escapeHtml(group.title)}</h4><p class="coordina-section-note">${escapeHtml(group.note)}</p></div><span class="coordina-summary-chip"><strong>${group.items.length}</strong>${escapeHtml(__('Items', 'coordina'))}</span></div>${myWorkTaskList(group.items, __('Nothing is here right now.', 'coordina'), { compact: group.tone === 'neutral', showActions: group.tone !== 'neutral' })}</section>`).join('');
	const focusBody = focusGroups || `<section class="coordina-card coordina-card--notice"><div class="coordina-section-header"><div><h4>${escapeHtml(__('Clear runway', 'coordina'))}</h4><p class="coordina-section-note">${escapeHtml(__('You have no urgent items right now. Check waiting work, approvals, or recent updates next.', 'coordina'))}</p></div></div></section>`;
	const primaryView = view === 'board'
		? `<section class="coordina-card coordina-card--wide coordina-my-work-focus"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Your board', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Scan your assigned work by status and move it forward without leaving My Work.', 'coordina'))}</p></div><span class="coordina-summary-chip"><strong>${items.length}</strong>${escapeHtml(__('Open items', 'coordina'))}</span></div>${myWorkBoard(items)}</section>`
		: view === 'tasks'
			? `<section class="coordina-card coordina-card--wide coordina-my-work-focus"><div class="coordina-section-header"><div><h3>${escapeHtml(__('All your tasks', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Filter, sort, and page through your assigned tasks across project and standalone work.', 'coordina'))}</p></div><span class="coordina-summary-chip"><strong>${Number(taskCollection.total || 0)}</strong>${escapeHtml(__('Matching tasks', 'coordina'))}</span></div>${myWorkTasksFilterBar(taskFilters)}${myWorkTasksGrid(taskCollection)}${myWorkTasksPager(taskCollection)}</section>`
		: `<section class="coordina-card coordina-card--wide coordina-my-work-focus"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Start here', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Check this list first and work from top to bottom.', 'coordina'))}</p></div></div><div class="coordina-my-work-signal-strip">${signals}</div><div class="coordina-my-work-focus-groups">${focusBody}</div></section>`;
	const waitingPanel = myWorkTaskList(sections.waiting || [], __('Nothing is waiting right now.', 'coordina'), { compact: true, showActions: false, fallbackReason: 'waiting' });
	const newWorkPanel = myWorkTaskList(sections.assignedRecently || [], __('Nothing new was assigned recently.', 'coordina'), { compact: true, showActions: false, fallbackReason: 'assigned-recently' });
	const upcomingPanel = myWorkUpcomingList(upcomingItems);
	const secondarySections = view === 'queue'
		? `<section class="coordina-card coordina-card--wide"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Decisions waiting on you', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Review approvals here and record a decision when you are ready.', 'coordina'))}</p></div>${canAccessPage('coordina-approvals') ? `<button class="button button-small" data-action="open-route" data-page="coordina-approvals">${escapeHtml(__('Open queue', 'coordina'))}</button>` : ''}</div>${myWorkApprovalList(approvals)}</section><section class="coordina-card coordina-card--wide"><div class="coordina-section-header"><div><h3>${escapeHtml(__('New on your plate', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Recently assigned items you may want to review before they join the main flow.', 'coordina'))}</p></div></div>${newWorkPanel}</section>`
		: '';
	const sideColumn = view === 'queue'
		? `<aside class="coordina-my-work-side">${myWorkMiniCalendar(items)}<section class="coordina-card coordina-card--notice"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Coming up', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Due dates coming in the next week. Open Calendar when you want the fuller schedule view.', 'coordina'))}</p></div>${canAccessPage('coordina-calendar') ? `<button class="button button-small" data-action="open-route" data-page="coordina-calendar">${escapeHtml(__('Open calendar', 'coordina'))}</button>` : ''}</div>${upcomingPanel}</section><section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Waiting on others', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Items paused until someone else replies or finishes their part.', 'coordina'))}</p></div></div>${waitingPanel}</section><section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Recent updates', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Recent notifications and updates for work you follow.', 'coordina'))}</p></div><button class="button button-small" data-action="open-notifications">${escapeHtml(__('Manage', 'coordina'))}</button></div>${myWorkNotificationSummary(notificationItems)}</section></aside>`
		: '';
	return `<section class="coordina-page coordina-page--my-work">${pageHeading('coordina-my-work', actions, { title: __('My Work', 'coordina'), description: __('See what needs your attention today, switch to a board when you want flow, and hand off to Calendar for date planning.', 'coordina') })}${viewTabs}<div class="coordina-my-work-layout ${view !== 'queue' ? 'coordina-my-work-layout--board' : ''}"><div class="coordina-my-work-main">${primaryView}${secondarySections}</div>${sideColumn}</div></section>`;
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
	const atRisk = dashboardList(widgets.atRiskProjects || [], __('No at-risk or blocked projects right now.', 'coordina'), (item) => `<li><button class="coordina-link-button" data-action="open-route" data-page="coordina-projects" data-project-id="${item.id}" data-project-tab="overview">${escapeHtml(item.title)}</button><div class="coordina-work-meta"><span class="coordina-status-badge status-${escapeHtml(item.status)}">${escapeHtml(nice(item.status))}</span><span class="coordina-status-badge status-${escapeHtml(item.health)}">${escapeHtml(nice(item.health))}</span><span>${escapeHtml(dateLabel(item.targetEndDate))}</span></div></li>`);
	const overdue = dashboardList(widgets.overdueTasks || [], __('No overdue tasks in this scope.', 'coordina'), (item) => `<li>${openTaskButton(item.id, item.title, item.projectId || item.project_id || 0, (item.projectId || item.project_id || 0) > 0 ? 'work' : '')}<div class="coordina-work-meta"><span class="coordina-status-badge status-${escapeHtml(item.status)}">${escapeHtml(nice(item.status))}</span>${openProjectButton(item.projectId || item.project_id || 0, item.projectLabel, 'work')}<span>${escapeHtml(dateLabel(item.dueDate))}</span></div></li>`);
	const approvals = dashboardList(widgets.pendingApprovals || [], __('No pending approvals in this scope.', 'coordina'), (item) => `<li><button class="coordina-link-button" data-action="open-record" data-module="approvals" data-id="${item.id}">${escapeHtml(item.objectLabel || nice(item.objectType))}</button><div class="coordina-work-meta">${openProjectButton(item.projectId || item.project_id || 0, item.projectLabel, 'approvals')}<span>${escapeHtml(item.ownerLabel || __('Unknown owner', 'coordina'))}</span><span>${escapeHtml(dateLabel(item.submittedAt))}</span></div></li>`);
	const recentActivity = widgets.recentActivity || { items: [] };
	const activity = `${activityList(recentActivity.items || recentActivity || [], __('No recent activity has been logged yet.', 'coordina'), { showContextLink: true, showProjectLabel: true, linkLabelMode: 'type' })}${activityPager(recentActivity, 'dashboard')}`;
	const deadlines = dashboardList(widgets.upcomingDeadlines || [], __('No upcoming deadlines found.', 'coordina'), (item) => `<li><button class="coordina-link-button" data-action="open-route" data-page="${item.route && item.route.page ? item.route.page : 'coordina-task'}" data-project-id="${item.route && item.route.project_id ? item.route.project_id : ''}" data-project-tab="${item.route && item.route.project_tab ? item.route.project_tab : ''}" data-task-id="${item.route && item.route.task_id ? item.route.task_id : ''}" data-milestone-id="${item.route && item.route.milestone_id ? item.route.milestone_id : ''}" data-risk-issue-id="${item.route && item.route.risk_issue_id ? item.route.risk_issue_id : ''}">${escapeHtml(item.title)}</button><div class="coordina-work-meta"><span>${escapeHtml(item.label)}</span><span class="coordina-status-badge status-${escapeHtml(item.status)}">${escapeHtml(nice(item.status))}</span><span>${escapeHtml(dateLabel(item.date))}</span></div></li>`);
	return `<section class="coordina-page">${pageHeading('coordina-dashboard', actions, { title: __('Dashboard', 'coordina'), description: `${roleLabel}. ${__('Use this screen to review what needs attention across your work.', 'coordina')}` })}<div class="coordina-summary-grid coordina-summary-grid--workspace">${alertCards}</div><div class="coordina-columns"><section class="coordina-card coordina-card--wide"><div class="coordina-section-header"><h3>${escapeHtml(__('Projects needing attention now', 'coordina'))}</h3>${routeButton(__('Projects', 'coordina'), { page: 'coordina-projects' })}</div>${atRisk}</section><section class="coordina-card"><div class="coordina-section-header"><h3>${escapeHtml(__('Upcoming deadlines', 'coordina'))}</h3></div>${deadlines}</section></div><div class="coordina-columns"><section class="coordina-card coordina-card--wide"><div class="coordina-section-header"><h3>${escapeHtml(__('Overdue tasks', 'coordina'))}</h3>${routeButton(__('My Work', 'coordina'), { page: 'coordina-my-work' })}</div>${overdue}</section><section class="coordina-card"><div class="coordina-section-header"><h3>${escapeHtml(__('Pending approvals', 'coordina'))}</h3>${routeButton(__('Approvals', 'coordina'), { page: 'coordina-approvals' })}</div>${approvals}</section></div><div class="coordina-columns"><section class="coordina-card coordina-card--wide"><div class="coordina-section-header"><h3>${escapeHtml(__('Recent activity', 'coordina'))}</h3></div>${activity}</section><section class="coordina-card"><div class="coordina-section-header"><h3>${escapeHtml(__('Scope', 'coordina'))}</h3></div><div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-summary-chip"><strong>${escapeHtml(data.scope || '')}</strong>${escapeHtml(__('Data scope', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${escapeHtml(data.roleMode || '')}</strong>${escapeHtml(__('Role mode', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(kpis.activeProjects || 0)}</strong>${escapeHtml(__('Active projects', 'coordina'))}</span></div><p class="coordina-empty-inline">${escapeHtml(__('Use Dashboard to monitor progress. Go to My Work when you are ready to act.', 'coordina'))}</p></section></div></section>`;
}

function calendarItem(item) {
	const route = item.route || { page: 'coordina-task' };
	return `<button class="coordina-calendar__item type-${escapeHtml(item.type || 'task')}" data-action="open-route" data-page="${route.page || ''}" data-project-id="${route.project_id || ''}" data-project-tab="${route.project_tab || ''}" data-task-id="${route.task_id || ''}" data-milestone-id="${route.milestone_id || ''}" data-risk-issue-id="${route.risk_issue_id || ''}"><span class="coordina-calendar__item-label">${escapeHtml(item.label)}</span><strong>${escapeHtml(item.title)}</strong><span class="coordina-calendar__item-meta">${escapeHtml(item.projectLabel || __('Standalone', 'coordina'))}${item.personLabel ? ` - ${escapeHtml(item.personLabel)}` : ''}</span></button>`;
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
	return `<ul class="coordina-work-list coordina-work-list--compact">${tasks.map((task) => `<li><button class="coordina-link-button" data-action="open-route" data-page="${task.route && task.route.page ? task.route.page : 'coordina-task'}" data-project-id="${task.route && task.route.project_id ? task.route.project_id : ''}" data-project-tab="${task.route && task.route.project_tab ? task.route.project_tab : ''}" data-task-id="${task.route && task.route.task_id ? task.route.task_id : ''}" data-milestone-id="${task.route && task.route.milestone_id ? task.route.milestone_id : ''}" data-risk-issue-id="${task.route && task.route.risk_issue_id ? task.route.risk_issue_id : ''}">${escapeHtml(task.title)}</button><div class="coordina-work-meta"><span class="coordina-status-badge status-${escapeHtml(task.status)}">${escapeHtml(nice(task.status))}</span><span>${escapeHtml(task.projectLabel || __('Standalone', 'coordina'))}</span><span>${escapeHtml(dateLabel(task.dueDate))}</span></div></li>`).join('')}</ul>`;
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
notificationList,
myWorkPage,
routeButton,
dashboardList,
dashboardPage,
calendarItem,
calendarPage,
workloadPressureBadge,
workloadTaskList,
workloadPage,
});

window.CoordinaAdminApp = app;
}());
