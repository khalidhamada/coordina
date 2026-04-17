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
	return `<div class="coordina-filter-bar coordina-filter-bar--tasks coordina-my-work-task-filters"><input type="search" name="my-work-task-search" value="${escapeHtml(filters.search || '')}" placeholder="${escapeHtml(__('Search your tasks', 'coordina'))}" /><select name="my-work-task-status"><option value="">${escapeHtml(__('All statuses', 'coordina'))}</option>${taskStatuses.map((status) => `<option value="${status}" ${String(filters.status || '') === String(status) ? 'selected' : ''}>${escapeHtml(nice(status))}</option>`).join('')}</select><select name="my-work-task-project-mode"><option value="all" ${filters.project_mode === 'all' ? 'selected' : ''}>${escapeHtml(__('All task types', 'coordina'))}</option><option value="project" ${filters.project_mode === 'project' ? 'selected' : ''}>${escapeHtml(__('Project-linked', 'coordina'))}</option><option value="standalone" ${filters.project_mode === 'standalone' ? 'selected' : ''}>${escapeHtml(__('Standalone', 'coordina'))}</option></select><select name="my-work-task-project"><option value="">${escapeHtml(__('All projects', 'coordina'))}</option>${projectOptions}</select><select class="coordina-my-work-task-filters__orderby" name="my-work-task-orderby">${orderOptions.map((option) => `<option value="${option.value}" ${String(filters.orderby || 'updated_at') === option.value ? 'selected' : ''}>${escapeHtml(option.label)}</option>`).join('')}</select><select class="coordina-my-work-task-filters__order" name="my-work-task-order"><option value="desc" ${String(filters.order || 'desc') === 'desc' ? 'selected' : ''}>${escapeHtml(__('Newest first', 'coordina'))}</option><option value="asc" ${String(filters.order || 'desc') === 'asc' ? 'selected' : ''}>${escapeHtml(__('Oldest first', 'coordina'))}</option></select><button class="button" data-action="apply-my-work-task-filters">${escapeHtml(__('Apply', 'coordina'))}</button></div>`;
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
	const status = String(item.status || '').trim();
	if (!status) {
		return 'new';
	}
	if (status === 'not-started') {
		return 'new';
	}
	if (status === 'completed') {
		return 'done';
	}
	if (status === 'todo') {
		return 'to-do';
	}
	return status;
}

function myWorkBoard(items) {
	const shell = state.shell || {};
	const configuredStatuses = Array.isArray(shell.statuses && shell.statuses.tasks) ? shell.statuses.tasks.map((status) => myWorkBoardLaneKey({ status })) : [];
	const extraKeys = Array.from(new Set((items || []).map((item) => myWorkBoardLaneKey(item)).filter((key) => key && !configuredStatuses.includes(key))));
	const lanes = Array.from(new Set(configuredStatuses.concat(extraKeys).filter(Boolean))).map((key) => ({ key, label: nice(key) }));
	return `<div class="coordina-my-work-board">${lanes.map((lane) => {
		const laneItems = (items || []).filter((item) => myWorkBoardLaneKey(item) === lane.key);
		const laneNote = lane.key === 'blocked'
			? __('Items that need a blocker cleared before they can move.', 'coordina')
			: lane.key === 'waiting'
				? __('Items paused until someone else responds or finishes their part.', 'coordina')
				: lane.key === 'new'
					? __('Work that has not been picked up yet.', 'coordina')
					: __('Work currently sitting in this stage.', 'coordina');
		return `<section class="coordina-my-work-section coordina-my-work-board-lane coordina-my-work-board-lane--${escapeHtml(lane.key)}"><div class="coordina-section-header"><div><h4>${escapeHtml(lane.label)}</h4><p class="coordina-section-note">${escapeHtml(laneNote)}</p></div><span class="coordina-summary-chip"><strong>${laneItems.length}</strong>${escapeHtml(__('Items', 'coordina'))}</span></div>${laneItems.length ? `<div class="coordina-my-work-board-lane__stack">${laneItems.map((item) => myWorkBoardCard(item)).join('')}</div>` : `<p class="coordina-empty-inline">${escapeHtml(__('Nothing is in this lane right now.', 'coordina'))}</p>`}</section>`;
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
	return `<section class="coordina-my-work-section coordina-my-work-section--calendar"><div class="coordina-section-header"><div><h3>${escapeHtml(__('This month', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('A quick scan of your dated work this month. Hover a day to preview its tasks.', 'coordina'))}</p></div>${canAccessPage('coordina-calendar') ? `<button class="button button-small" data-action="open-route" data-page="coordina-calendar">${escapeHtml(__('Open calendar', 'coordina'))}</button>` : ''}</div><div class="coordina-my-work-mini-calendar"><div class="coordina-my-work-mini-calendar__weekdays">${['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map((day) => `<span>${escapeHtml(__(day, 'coordina'))}</span>`).join('')}</div><div class="coordina-my-work-mini-calendar__grid">${days.join('')}</div></div></section>`;
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
	].map((item) => `<article class="coordina-metric-card coordina-my-work-signal-card ${item.tone !== 'neutral' ? `coordina-metric-card--${escapeHtml(item.tone)}` : ''}"><span class="coordina-metric-card__label">${escapeHtml(item.label)}</span><strong class="coordina-metric-card__value">${escapeHtml(item.value)}</strong></article>`).join('');
	const focusGroups = [
		{ title: __('Needs attention now', 'coordina'), note: __('Handle overdue and blocked work first.', 'coordina'), items: focusQueue.attention || [], tone: 'danger' },
		{ title: __('Do today', 'coordina'), note: __('These items should be finished or updated today.', 'coordina'), items: focusQueue.today || [], tone: 'warning' },
		{ title: __('Coming next', 'coordina'), note: __('These are ready when today\'s urgent work is under control.', 'coordina'), items: focusQueue.upNext || [], tone: 'neutral' },
	].filter((group) => group.items.length).map((group) => `<section class="coordina-my-work-section coordina-my-work-focus-group coordina-my-work-focus-group--${escapeHtml(group.tone)}"><div class="coordina-section-header"><div><h4>${escapeHtml(group.title)}</h4><p class="coordina-section-note">${escapeHtml(group.note)}</p></div><span class="coordina-summary-chip"><strong>${group.items.length}</strong>${escapeHtml(__('Items', 'coordina'))}</span></div>${myWorkTaskList(group.items, __('Nothing is here right now.', 'coordina'), { compact: group.tone === 'neutral', showActions: group.tone !== 'neutral' })}</section>`).join('');
	const focusBody = focusGroups || `<section class="coordina-my-work-section coordina-card--notice"><div class="coordina-section-header"><div><h4>${escapeHtml(__('Clear runway', 'coordina'))}</h4><p class="coordina-section-note">${escapeHtml(__('You have no urgent items right now. Check waiting work, approvals, or recent updates next.', 'coordina'))}</p></div></div></section>`;
	const primaryView = view === 'board'
		? `<section class="coordina-my-work-panel coordina-my-work-focus"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Your board', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Scan your assigned work by status and move it forward without leaving My Work.', 'coordina'))}</p></div><span class="coordina-summary-chip"><strong>${items.length}</strong>${escapeHtml(__('Open items', 'coordina'))}</span></div>${myWorkBoard(items)}</section>`
		: view === 'tasks'
			? `<section class="coordina-my-work-panel coordina-my-work-focus"><div class="coordina-section-header"><div><h3>${escapeHtml(__('All your tasks', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Filter, sort, and page through your assigned tasks across project and standalone work.', 'coordina'))}</p></div><span class="coordina-summary-chip"><strong>${Number(taskCollection.total || 0)}</strong>${escapeHtml(__('Matching tasks', 'coordina'))}</span></div>${myWorkTasksFilterBar(taskFilters)}${myWorkTasksGrid(taskCollection)}${myWorkTasksPager(taskCollection)}</section>`
		: `<section class="coordina-my-work-panel coordina-my-work-focus"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Start here', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Check this list first and work from top to bottom.', 'coordina'))}</p></div></div><div class="coordina-my-work-signal-strip">${signals}</div><div class="coordina-my-work-focus-groups">${focusBody}</div></section>`;
	const waitingPanel = myWorkTaskList(sections.waiting || [], __('Nothing is waiting right now.', 'coordina'), { compact: true, showActions: false, fallbackReason: 'waiting' });
	const newWorkPanel = myWorkTaskList(sections.assignedRecently || [], __('Nothing new was assigned recently.', 'coordina'), { compact: true, showActions: false, fallbackReason: 'assigned-recently' });
	const secondarySections = view === 'queue'
		? `<section class="coordina-my-work-section coordina-my-work-section--wide"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Decisions waiting on you', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Review approvals here and record a decision when you are ready.', 'coordina'))}</p></div>${canAccessPage('coordina-approvals') ? `<button class="button button-small" data-action="open-route" data-page="coordina-approvals">${escapeHtml(__('Open queue', 'coordina'))}</button>` : ''}</div>${myWorkApprovalList(approvals)}</section><section class="coordina-my-work-section coordina-my-work-section--wide"><div class="coordina-section-header"><div><h3>${escapeHtml(__('New on your plate', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Recently assigned items you may want to review before they join the main flow.', 'coordina'))}</p></div></div>${newWorkPanel}</section>`
		: '';
	const sideColumn = view === 'queue'
		? `<aside class="coordina-my-work-side">${myWorkMiniCalendar(items)}<section class="coordina-my-work-section"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Waiting on others', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Items paused until someone else replies or finishes their part.', 'coordina'))}</p></div></div>${waitingPanel}</section><section class="coordina-my-work-section"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Recent updates', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Recent notifications and updates for work you follow.', 'coordina'))}</p></div><button class="button button-small" data-action="open-notifications">${escapeHtml(__('Manage', 'coordina'))}</button></div>${myWorkNotificationSummary(notificationItems)}</section></aside>`
		: '';
	return `<section class="coordina-page coordina-page--my-work">${pageHeading('coordina-my-work', actions, { title: __('My Work', 'coordina'), description: __('See what needs your attention today, switch to a board when you want flow, and hand off to Calendar for date planning.', 'coordina') })}${viewTabs}<div class="coordina-my-work-layout ${view !== 'queue' ? 'coordina-my-work-layout--board' : ''}"><div class="coordina-my-work-main">${primaryView}${secondarySections}</div>${sideColumn}</div></section>`;
}

function dashboardMetricCards(kpis) {
	const cards = [
		{ label: __('Total projects', 'coordina'), value: Number(kpis.totalProjects || 0), tone: 'accent' },
		{ label: __('Active projects', 'coordina'), value: Number(kpis.activeProjects || 0), tone: 'accent' },
		{ label: __('At risk', 'coordina'), value: Number(kpis.atRiskProjects || 0), tone: Number(kpis.atRiskProjects || 0) > 0 ? 'warning' : 'accent' },
		{ label: __('Blocked', 'coordina'), value: Number(kpis.blockedProjects || 0), tone: Number(kpis.blockedProjects || 0) > 0 ? 'danger' : 'accent' },
		{ label: __('Overdue tasks', 'coordina'), value: Number(kpis.overdueTasks || 0), tone: Number(kpis.overdueTasks || 0) > 0 ? 'warning' : 'accent' },
		{ label: __('Pending approvals', 'coordina'), value: Number(kpis.pendingApprovals || 0), tone: Number(kpis.pendingApprovals || 0) > 0 ? 'accent' : 'neutral' },
	];
	return cards.map((item) => `<article class="coordina-metric-card ${item.tone !== 'neutral' ? `coordina-metric-card--${escapeHtml(item.tone)}` : ''}"><span class="coordina-metric-card__label">${escapeHtml(item.label)}</span><strong class="coordina-metric-card__value">${escapeHtml(item.value)}</strong></article>`).join('');
}

function dashboardBarChart(series, emptyMessage) {
	const rows = (series || []).filter((item) => Number(item.value || 0) > 0 || item.showZero);
	if (!rows.length) {
		return `<p class="coordina-empty-inline">${escapeHtml(emptyMessage)}</p>`;
	}
	const max = Math.max(...rows.map((item) => Number(item.value || 0)), 1);
	return `<div class="coordina-dashboard-bar-chart">${rows.map((item) => {
		const width = Math.max(4, Math.round((Number(item.value || 0) / max) * 100));
		return `<div class="coordina-dashboard-bar-chart__row"><div class="coordina-dashboard-bar-chart__head"><span>${escapeHtml(item.label)}</span><strong>${escapeHtml(item.value)}</strong></div><div class="coordina-dashboard-bar-chart__track"><span class="tone-${escapeHtml(item.tone || 'accent')}" style="width:${width}%"></span></div>${item.note ? `<p class="coordina-dashboard-bar-chart__note">${escapeHtml(item.note)}</p>` : ''}</div>`;
	}).join('')}</div>`;
}

function dashboardColumnChart(series, emptyMessage) {
	const columns = (series || []).filter((item) => Number(item.value || 0) > 0);
	if (!columns.length) {
		return `<p class="coordina-empty-inline">${escapeHtml(emptyMessage)}</p>`;
	}
	const max = Math.max(...columns.map((item) => Number(item.value || 0)), 1);
	return `<div class="coordina-dashboard-column-chart">${columns.map((item) => {
		const height = Math.max(12, Math.round((Number(item.value || 0) / max) * 100));
		return `<div class="coordina-dashboard-column-chart__item"><div class="coordina-dashboard-column-chart__value">${escapeHtml(item.value)}</div><div class="coordina-dashboard-column-chart__bar"><span class="tone-${escapeHtml(item.tone || 'accent')}" style="height:${height}%"></span></div><div class="coordina-dashboard-column-chart__label">${escapeHtml(item.label)}</div></div>`;
	}).join('')}</div>`;
}

function dashboardActivityDateKey(value) {
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

function dashboardActivityDateLabel(key) {
	if (key === '__unknown') {
		return __('Unknown date', 'coordina');
	}
	return dateLabel(key);
}

function dashboardActivityDateTile(key) {
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


function dashboardActivityByUser(items) {
	const rows = Array.isArray(items) && items.some((item) => item && Object.prototype.hasOwnProperty.call(item, 'count'))
		? items.map((item) => ({
			label: String((item && item.label) || __('System', 'coordina')).trim() || __('System', 'coordina'),
			count: Number((item && item.count) || 0),
		})).sort((left, right) => Number(right.count || 0) - Number(left.count || 0)).slice(0, 6)
		: Object.values((items || []).reduce((result, item) => {
			const key = String(item.actorLabel || __('System', 'coordina')).trim() || __('System', 'coordina');
			if (!result[key]) {
				result[key] = { label: key, count: 0 };
			}
			result[key].count += 1;
			return result;
		}, {})).sort((left, right) => Number(right.count || 0) - Number(left.count || 0)).slice(0, 6);
	if (!rows.length) {
		return `<p class="coordina-empty-inline">${escapeHtml(__('No people activity yet.', 'coordina'))}</p>`;
	}
	const max = Math.max(1, ...rows.map((item) => Number(item.count || 0)));
	return `<div class="coordina-activity-chart coordina-activity-chart--ranking">${rows.map((item) => `<div class="coordina-activity-chart__row"><div class="coordina-activity-chart__row-head"><span>${escapeHtml(item.label || '')}</span><strong>${Number(item.count || 0)}</strong></div><span class="coordina-activity-chart__row-bar"><span style="width:${Math.max(10, Math.round((Number(item.count || 0) / max) * 100))}%"></span></span></div>`).join('')}</div>`;
}

function dashboardGroupedActivityTimeline(collection, emptyMessage) {
	const items = collection && Array.isArray(collection.items) ? collection.items : [];
	if (!items.length) {
		return activityList([], emptyMessage, { showContextLink: true, showProjectLabel: true, linkLabelMode: 'type' });
	}
	const groups = items.reduce((carry, item) => {
		const key = dashboardActivityDateKey(item.createdAt);
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
	return `<div class="coordina-activity-groups">${groupKeys.map((key) => `<section class="coordina-activity-group coordina-activity-group--dated">${dashboardActivityDateTile(key)}<div class="coordina-activity-group__content"><div class="coordina-section-header coordina-section-header--activity-group"><div><h4>${escapeHtml(dashboardActivityDateLabel(key))}</h4></div></div>${activityList(groups[key], __('No activity recorded for this date.', 'coordina'), { showContextLink: true, showProjectLabel: true, linkLabelMode: 'type', timestampMode: 'time', listClass: 'coordina-timeline--activity-group' })}</div></section>`).join('')}</div>`;
}

function dashboardWatchlistTable(widgets) {
	const items = [];
	(widgets.atRiskProjects || []).forEach((item) => {
		items.push({
			weight: item.health === 'blocked' || item.status === 'blocked' ? 100 : 85,
			signal: item.health === 'blocked' || item.status === 'blocked' ? __('Blocked project', 'coordina') : __('At-risk project', 'coordina'),
			context: __('Project', 'coordina'),
			owner: item.managerLabel || __('No manager', 'coordina'),
			when: item.targetEndDate,
			status: nice(item.health || item.status || 'active'),
			link: `<button class="coordina-link-button" data-action="open-route" data-page="coordina-projects" data-project-id="${item.id}" data-project-tab="overview">${escapeHtml(item.title)}</button>`,
		});
	});
	(widgets.overdueTasks || []).forEach((item) => {
		items.push({
			weight: 70,
			signal: __('Overdue task', 'coordina'),
			context: item.projectId > 0 ? (item.projectLabel || __('Project task', 'coordina')) : __('Standalone task', 'coordina'),
			owner: item.assigneeLabel || __('Unassigned', 'coordina'),
			when: item.dueDate,
			status: nice(item.status || 'new'),
			link: openTaskButton(item.id, item.title, item.projectId || 0, (item.projectId || 0) > 0 ? 'work' : ''),
		});
	});
	(widgets.pendingApprovals || []).forEach((item) => {
		items.push({
			weight: 60,
			signal: __('Decision required', 'coordina'),
			context: item.projectLabel || nice(item.objectType || 'approval'),
			owner: item.ownerLabel || __('Unknown owner', 'coordina'),
			when: item.submittedAt,
			status: nice(item.status || 'pending'),
			link: `<button class="coordina-link-button" data-action="open-record" data-module="approvals" data-id="${item.id}">${escapeHtml(item.objectLabel || nice(item.objectType || 'approval'))}</button>`,
		});
	});
	const rows = items.sort((left, right) => Number(right.weight || 0) - Number(left.weight || 0)).slice(0, 8);
	if (!rows.length) {
		return `<p class="coordina-empty-inline">${escapeHtml(__('No urgent watchlist items are visible in this scope right now.', 'coordina'))}</p>`;
	}
	return `<div class="coordina-table-wrap"><table class="coordina-table coordina-dashboard-table"><thead><tr><th>${escapeHtml(__('Signal', 'coordina'))}</th><th>${escapeHtml(__('Item', 'coordina'))}</th><th>${escapeHtml(__('Context', 'coordina'))}</th><th>${escapeHtml(__('Owner', 'coordina'))}</th><th>${escapeHtml(__('When', 'coordina'))}</th><th>${escapeHtml(__('Status', 'coordina'))}</th></tr></thead><tbody>${rows.map((item) => `<tr><td><span class="coordina-dashboard-table__signal">${escapeHtml(item.signal)}</span></td><td>${item.link}</td><td>${escapeHtml(item.context)}</td><td>${escapeHtml(item.owner)}</td><td>${escapeHtml(dateLabel(item.when))}</td><td>${escapeHtml(item.status)}</td></tr>`).join('')}</tbody></table></div>`;
}

function dashboardPage() {
	const data = state.dashboard || { kpis: {}, widgets: {}, roleMode: 'team', scope: 'personal' };
	const kpis = data.kpis || {};
	const widgets = data.widgets || {};
	const actions = [
		canAccessPage('coordina-projects') ? `<button class="button" data-action="open-route" data-page="coordina-projects">${escapeHtml(__('View projects', 'coordina'))}</button>` : '',
		canAccessPage('coordina-workload') ? `<button class="button" data-action="open-route" data-page="coordina-workload">${escapeHtml(__('Workload', 'coordina'))}</button>` : '',
		`<button class="button button-primary" data-action="open-route" data-page="coordina-my-work">${escapeHtml(__('Go to My Work', 'coordina'))}</button>`,
	].filter(Boolean).join('');
	const metricCards = dashboardMetricCards(kpis);
	const overviewSeries = [
		{ label: __('Total projects', 'coordina'), value: Number(kpis.totalProjects || 0), tone: 'accent', showZero: true },
		{ label: __('Active projects', 'coordina'), value: Number(kpis.activeProjects || 0), tone: 'accent', showZero: true },
		{ label: __('At risk', 'coordina'), value: Number(kpis.atRiskProjects || 0), tone: 'warning', showZero: true },
		{ label: __('Blocked', 'coordina'), value: Number(kpis.blockedProjects || 0), tone: 'danger', showZero: true },
	];
	const pressureSeries = [
		{ label: __('Overdue tasks', 'coordina'), value: Number(kpis.overdueTasks || 0), tone: 'warning', showZero: true },
		{ label: __('Pending approvals', 'coordina'), value: Number(kpis.pendingApprovals || 0), tone: 'accent', showZero: true },
		{ label: __('Projects needing review', 'coordina'), value: Array.isArray(widgets.atRiskProjects) ? widgets.atRiskProjects.length : 0, tone: 'danger', showZero: true },
		{ label: __('Active approvals and risks', 'coordina'), value: Number(kpis.pendingApprovals || 0) + Number(kpis.atRiskProjects || 0), tone: 'accent', showZero: true },
	];
	const atRisk = dashboardList(widgets.atRiskProjects || [], __('No at-risk or blocked projects right now.', 'coordina'), (item) => `<li><button class="coordina-link-button" data-action="open-route" data-page="coordina-projects" data-project-id="${item.id}" data-project-tab="overview">${escapeHtml(item.title)}</button><div class="coordina-work-meta"><span class="coordina-status-badge status-${escapeHtml(item.status)}">${escapeHtml(nice(item.status))}</span><span class="coordina-status-badge status-${escapeHtml(item.health)}">${escapeHtml(nice(item.health))}</span><span>${escapeHtml(dateLabel(item.targetEndDate))}</span></div></li>`);
	const overdue = dashboardList(widgets.overdueTasks || [], __('No overdue tasks in this scope.', 'coordina'), (item) => `<li>${openTaskButton(item.id, item.title, item.projectId || item.project_id || 0, (item.projectId || item.project_id || 0) > 0 ? 'work' : '')}<div class="coordina-work-meta"><span class="coordina-status-badge status-${escapeHtml(item.status)}">${escapeHtml(nice(item.status))}</span>${openProjectButton(item.projectId || item.project_id || 0, item.projectLabel, 'work')}<span>${escapeHtml(dateLabel(item.dueDate))}</span></div></li>`);
	const approvals = dashboardList(widgets.pendingApprovals || [], __('No pending approvals in this scope.', 'coordina'), (item) => `<li><button class="coordina-link-button" data-action="open-record" data-module="approvals" data-id="${item.id}">${escapeHtml(item.objectLabel || nice(item.objectType))}</button><div class="coordina-work-meta">${openProjectButton(item.projectId || item.project_id || 0, item.projectLabel, 'approvals')}<span>${escapeHtml(item.ownerLabel || __('Unknown owner', 'coordina'))}</span><span>${escapeHtml(dateLabel(item.submittedAt))}</span></div></li>`);
	const recentActivity = widgets.recentActivity || { items: [] };
	const activityTimeline = `${dashboardGroupedActivityTimeline(recentActivity, __('No recent activity has been logged yet.', 'coordina'))}${activityPager(recentActivity, 'dashboard')}`;
	const activitySummary = widgets.activitySummary && widgets.activitySummary.charts ? widgets.activitySummary.charts : {};
	const activityByUser = dashboardActivityByUser(activitySummary.actors || []);
	return `<section class="coordina-page coordina-dashboard">${pageHeading('coordina-dashboard', actions, { title: __('Dashboard', 'coordina'), description: __('Review portfolio signals, current movement, and the queues that need attention before routing into execution.', 'coordina') })}<div class="coordina-summary-grid coordina-dashboard-metrics">${metricCards}</div><div class="coordina-dashboard-grid coordina-dashboard-grid--three"><section class="coordina-card coordina-dashboard-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Portfolio profile', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See how much of the visible portfolio is active, at risk, or blocked.', 'coordina'))}</p></div></div>${dashboardBarChart(overviewSeries, __('No portfolio counts are visible in this scope.', 'coordina'))}</section><section class="coordina-card coordina-dashboard-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Execution pressure', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Track the signals that most often slow progress down.', 'coordina'))}</p></div></div>${dashboardBarChart(pressureSeries, __('No active pressure signals are visible right now.', 'coordina'))}</section><section class="coordina-card coordina-dashboard-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Activity by user', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('This chart uses the full visible activity period, not only the current activity page.', 'coordina'))}</p></div></div>${activityByUser}</section></div><div class="coordina-dashboard-grid coordina-dashboard-grid--split"><div class="coordina-dashboard-stack"><section class="coordina-card coordina-card--wide coordina-dashboard-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Priority watchlist', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('A mixed table of the most important projects, tasks, and approvals that deserve attention next.', 'coordina'))}</p></div></div>${dashboardWatchlistTable(widgets)}</section><section class="coordina-card coordina-dashboard-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Recent activity', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Use the same grouped activity view as project workspaces to understand current movement and context.', 'coordina'))}</p></div></div>${activityTimeline}</section></div><div class="coordina-dashboard-stack"><section class="coordina-card coordina-dashboard-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Projects needing review', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('The projects most likely to need follow-up or escalation.', 'coordina'))}</p></div>${routeButton(__('Projects', 'coordina'), { page: 'coordina-projects' })}</div>${atRisk}</section><section class="coordina-card coordina-dashboard-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Pending approvals', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Keep decisions moving so work does not wait on review longer than necessary.', 'coordina'))}</p></div>${canAccessPage('coordina-approvals') ? `<button class="button button-small" data-action="open-route" data-page="coordina-approvals">${escapeHtml(__('Approvals', 'coordina'))}</button>` : ''}</div>${approvals}</section><section class="coordina-card coordina-dashboard-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Overdue tasks', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Reset stale due plans and unblock the oldest commitments first.', 'coordina'))}</p></div>${routeButton(__('My Work', 'coordina'), { page: 'coordina-my-work' })}</div>${overdue}</section></div></div></section>`;
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
