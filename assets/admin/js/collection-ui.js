(function () {
const app = window.CoordinaAdminApp || {};

if (!app.root || !app.state) {
	return;
}

const { state, isDateKey, isCheckedValue, escapeHtml, __, nice, dateLabel } = app;

function columnLabel(key) {
	const labels = {
		title: __('Title', 'coordina'),
		code: __('Code', 'coordina'),
		description: __('Description', 'coordina'),
		status: __('Status', 'coordina'),
		health: __('Health', 'coordina'),
		priority: __('Priority', 'coordina'),
		manager_label: __('Manager', 'coordina'),
		target_end_date: __('Target end date', 'coordina'),
		project_label: __('Project', 'coordina'),
		task_group_label: __('Group', 'coordina'),
		checklist_summary: __('Checklist', 'coordina'),
		assignee_label: __('Assignee', 'coordina'),
		due_date: __('Due date', 'coordina'),
		approval_state: __('Approval', 'coordina'),
		approval_status: __('Approval', 'coordina'),
		blocked: __('Blocked', 'coordina'),
		requester_label: __('Requester', 'coordina'),
		triage_owner_label: __('Triage owner', 'coordina'),
		desired_due_date: __('Desired due date', 'coordina'),
		object_label: __('Linked item', 'coordina'),
		object_type: __('Type', 'coordina'),
		approver_label: __('Approver', 'coordina'),
		submitted_at: __('Submitted', 'coordina'),
		severity: __('Severity', 'coordina'),
		owner_label: __('Owner', 'coordina'),
		target_resolution_date: __('Target resolution', 'coordina'),
		completion_percent: __('Completion', 'coordina'),
		dependency_flag: __('Dependency', 'coordina'),
		file_name: __('File', 'coordina'),
		created_by_label: __('Created by', 'coordina'),
		created_at: __('Created', 'coordina'),
		excerpt: __('Update', 'coordina'),
	};

	return labels[key] || nice(key.replace(/_label$/, ''));
}

function noticesHtml() {
	return `<div class="coordina-notices">${state.notices.map((item) => `<div class="coordina-notice is-${escapeHtml(item.type)}">${escapeHtml(item.message)}</div>`).join('')}</div>`;
}

function renderFilterBar(module, options, savedOptions) {
	const shell = state.shell || {};
	const projectOptions = (shell.projects || []).map((project) => `<option value="${project.id}" ${String(state.filters.project_id) === String(project.id) ? 'selected' : ''}>${escapeHtml(project.label)}</option>`).join('');
	const contextFilter = module.contextFilters ? `<select name="project_mode"><option value="all" ${state.filters.project_mode === 'all' ? 'selected' : ''}>${escapeHtml(__('All task types', 'coordina'))}</option><option value="project" ${state.filters.project_mode === 'project' ? 'selected' : ''}>${escapeHtml(__('Project-linked', 'coordina'))}</option><option value="standalone" ${state.filters.project_mode === 'standalone' ? 'selected' : ''}>${escapeHtml(__('Standalone', 'coordina'))}</option></select><select name="project_id"><option value="">${escapeHtml(__('All projects', 'coordina'))}</option>${projectOptions}</select>` : '';
	const riskFilter = module.riskFilters ? `<select name="project_id"><option value="">${escapeHtml(__('All projects', 'coordina'))}</option><option value="0" ${String(state.filters.project_id) === '0' ? 'selected' : ''}>${escapeHtml(__('Standalone', 'coordina'))}</option>${projectOptions}</select><select name="object_type"><option value="">${escapeHtml(__('All types', 'coordina'))}</option>${(shell.objectTypes || []).map((item) => `<option value="${item}" ${String(state.filters.object_type) === String(item) ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select><select name="severity"><option value="">${escapeHtml(__('All severities', 'coordina'))}</option>${(shell.severities || []).map((item) => `<option value="${item}" ${String(state.filters.severity) === String(item) ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select><select name="owner_user_id"><option value="">${escapeHtml(__('All owners', 'coordina'))}</option>${(shell.users || []).map((user) => `<option value="${user.id}" ${String(state.filters.owner_user_id) === String(user.id) ? 'selected' : ''}>${escapeHtml(user.label)}</option>`).join('')}</select>` : '';
	const approvalFilter = module.approvalFilters ? `<select name="object_type"><option value="">${escapeHtml(__('All object types', 'coordina'))}</option>${(shell.approvalObjectTypes || []).map((item) => `<option value="${item}" ${String(state.filters.object_type) === String(item) ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select><select name="approver_user_id"><option value="">${escapeHtml(__('All approvers', 'coordina'))}</option>${(shell.users || []).map((user) => `<option value="${user.id}" ${String(state.filters.approver_user_id) === String(user.id) ? 'selected' : ''}>${escapeHtml(user.label)}</option>`).join('')}</select>` : '';
	const filterClass = module.contextFilters || module.riskFilters || module.approvalFilters ? 'coordina-filter-bar coordina-card coordina-filter-bar--tasks' : 'coordina-filter-bar coordina-card';
	return `<div class="${filterClass}"><input type="search" name="search" value="${escapeHtml(state.filters.search)}" placeholder="${escapeHtml(__('Search title', 'coordina'))}" /><select name="status"><option value="">${escapeHtml(__('All statuses', 'coordina'))}</option>${options}</select>${contextFilter}${riskFilter}${approvalFilter}<select data-action="apply-view">${savedOptions}</select><button class="button" data-action="apply-filters">${escapeHtml(__('Apply', 'coordina'))}</button></div>`;
}

function renderTable(module, items, mode) {
	const selectionEnabled = mode !== 'workspace' && module.key !== 'projects';
	const bulkHead = selectionEnabled ? `<input type="checkbox" data-action="toggle-all" ${items.length && items.length === state.selection.length ? 'checked' : ''} />` : '';
	const headers = module.columns.map((col) => `<th>${mode === 'workspace' ? escapeHtml(columnLabel(col)) : `<button class="coordina-sort-button" data-action="sort" data-orderby="${col}">${escapeHtml(columnLabel(col))}</button>`}</th>`).join('');
	return `<div class="coordina-card coordina-table-wrap"><table class="coordina-table widefat striped"><thead><tr><th>${bulkHead}</th>${headers}<th>${escapeHtml(__('Actions', 'coordina'))}</th></tr></thead><tbody>${items.map((item) => rowHtml(item, module, mode)).join('')}</tbody></table></div>`;
}

function secondaryCell(text) {
	return text ? `<span class="coordina-cell-secondary">${escapeHtml(text)}</span>` : '';
}

function valueHtml(item, key, module) {
	if (key === 'title') {
		const meta = [];
		if (module.key === 'tasks') {
			meta.push(item.project_label || __('Standalone', 'coordina'));
			if (item.assignee_label) {
				meta.push(item.assignee_label);
			}
		}
		if (module.key === 'projects') {
			meta.push(item.manager_label || __('No manager assigned', 'coordina'));
		}
		if (module.key === 'requests') {
			meta.push(item.requester_label || __('No requester', 'coordina'));
		}
		if (module.key === 'risks-issues') {
			meta.push(item.project_label || __('Standalone', 'coordina'));
		}
		if (module.key === 'milestones') {
			meta.push(item.owner_label || __('No owner assigned', 'coordina'));
		}
		if (module.key === 'projects') {
			return `<div class="coordina-cell-primary"><button class="coordina-link-button" data-action="open-project" data-id="${item.id}">${escapeHtml(item.title)}</button>${secondaryCell(meta.filter(Boolean).join(' | '))}</div>`;
		}
		if (module.key === 'tasks') {
			return `<div class="coordina-cell-primary"><button class="coordina-link-button" data-action="open-task-page" data-id="${item.id}" data-project-id="${item.project_id || ''}" data-project-tab="${item.project_id ? 'work' : ''}">${escapeHtml(item.title)}</button>${secondaryCell(meta.filter(Boolean).join(' | '))}</div>`;
		}
		if (module.key === 'risks-issues') {
			return `<div class="coordina-cell-primary"><button class="coordina-link-button" data-action="open-risk-issue-page" data-id="${item.id}" data-project-id="${item.project_id || ''}" data-project-tab="${item.project_id ? 'risks-issues' : ''}">${escapeHtml(item.title)}</button>${secondaryCell(meta.filter(Boolean).join(' | '))}</div>`;
		}
		if (module.key === 'milestones') {
			return `<div class="coordina-cell-primary"><button class="coordina-link-button" data-action="open-milestone-page" data-id="${item.id}" data-project-id="${item.project_id || ''}" data-project-tab="${item.project_id ? 'milestones' : ''}">${escapeHtml(item.title)}</button>${secondaryCell(meta.filter(Boolean).join(' | '))}</div>`;
		}
		return `<div class="coordina-cell-primary"><button class="coordina-link-button" data-action="open-record" data-module="${module.key}" data-id="${item.id}">${escapeHtml(item.title)}</button>${secondaryCell(meta.filter(Boolean).join(' | '))}</div>`;
	}
	if (key === 'object_label' && module.key === 'approvals') {
		const meta = [nice(item.object_type || 'approval'), item.project_label || __('Standalone', 'coordina')].filter(Boolean).join(' | ');
		return `<div class="coordina-cell-primary"><button class="coordina-link-button" data-action="open-record" data-module="approvals" data-id="${item.id}">${escapeHtml(item.object_label || __('Approval', 'coordina'))}</button>${secondaryCell(meta)}</div>`;
	}
	if (key === 'file_name') {
		const meta = [item.object_label || '', item.project_label || __('Standalone', 'coordina')].filter(Boolean).join(' | ');
		return `<div class="coordina-cell-primary"><span>${escapeHtml(item.file_name || '--')}</span>${secondaryCell(meta)}</div>`;
	}
	if (key === 'excerpt') {
		const title = shortExcerpt(item.excerpt || item.body || __('No update text yet.', 'coordina'));
		const meta = [item.object_label || '', item.project_label || __('Standalone', 'coordina')].filter(Boolean).join(' | ');
		return `<div class="coordina-cell-primary"><span>${escapeHtml(title)}</span>${secondaryCell(meta)}</div>`;
	}
	if (['status', 'priority', 'health', 'severity', 'approval_state', 'approval_status'].includes(key)) {
		const iconClass = key === 'priority' ? ` priority-${escapeHtml(item[key])}` : '';
		return `<span class="coordina-status-badge status-${escapeHtml(item[key])}${iconClass}">${escapeHtml(nice(item[key]))}</span>`;
	}
	if (key === 'object_type') {
		return `<span class="coordina-status-badge">${escapeHtml(nice(item.object_type))}</span>`;
	}
	if (key === 'project_label') {
		return item.project_id
			? `<button class="coordina-link-button" data-action="open-route" data-page="coordina-projects" data-project-id="${item.project_id}" data-project-tab="overview">${escapeHtml(item.project_label || __('Project workspace', 'coordina'))}</button>`
			: `<span class="coordina-status-badge">${escapeHtml(__('Standalone', 'coordina'))}</span>`;
	}
	if (key === 'task_group_label') {
		return item.task_group_label ? `<span class="coordina-status-badge">${escapeHtml(item.task_group_label)}</span>` : `<span class="coordina-empty-inline">${escapeHtml(__('Ungrouped', 'coordina'))}</span>`;
	}
	if (key === 'checklist_summary') {
		const summary = item.checklist_summary || {};
		const total = Number(summary.total || 0);
		return total > 0 ? `<span class="coordina-status-badge">${Number(summary.done || 0)} / ${total}</span>` : `<span class="coordina-empty-inline">${escapeHtml(__('None', 'coordina'))}</span>`;
	}
	if (key === 'blocked') {
		const blocked = isCheckedValue(item.blocked);
		return `<span class="coordina-status-badge ${blocked ? 'status-blocked' : 'status-clear'}">${escapeHtml(blocked ? __('Yes', 'coordina') : __('No', 'coordina'))}</span>`;
	}
	if (key === 'dependency_flag') {
		const flagged = isCheckedValue(item.dependency_flag);
		return `<span class="coordina-status-badge ${flagged ? 'status-waiting' : 'status-clear'}">${escapeHtml(flagged ? __('Dependency', 'coordina') : __('Clear', 'coordina'))}</span>`;
	}
	if (key === 'completion_percent') {
		return `${Number(item.completion_percent || 0)}%`;
	}
	if (isDateKey(key)) {
		return escapeHtml(dateLabel(item[key]));
	}
	return escapeHtml(item[key] || '--');
}

function shortExcerpt(value) {
	const text = String(value || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
	if (text.length <= 80) {
		return text;
	}
	return `${text.slice(0, 77).trim()}...`;
}

function projectCompletion(item) {
	const value = Number(item.completion_percent || item.completionPercent || 0);
	return Math.max(0, Math.min(100, value));
}

function projectProgressBar(percent) {
	return `<div class="coordina-project-card__progress"><span class="coordina-project-card__progress-track"><span class="coordina-project-card__progress-fill" style="width:${percent}%"></span></span><strong>${percent}%</strong></div>`;
}

function iconLabel(type, label, className) {
	if (typeof app.iconLabel === 'function') {
		return app.iconLabel(type, label, className);
	}
	return escapeHtml(label || '');
}

function deleteButton(item, moduleKey, label) {
	if (!item || !item.can_delete) {
		return '';
	}

	return `<button class="button button-small button-link-delete" data-action="delete-record" data-module="${moduleKey}" data-id="${item.id}" data-label="${escapeHtml(label || item.title || '')}">${escapeHtml(__('Delete', 'coordina'))}</button>`;
}

function renderProjectCards(items, canManageModule) {
	return `<div class="coordina-project-cards">${items.map((item) => {
		const completion = projectCompletion(item);
		const description = shortExcerpt(item.description || '');
		const targetEnd = dateLabel(item.target_end_date || '');
		const startDate = dateLabel(item.start_date || '');
		void canManageModule;
		return `<article class="coordina-card coordina-project-card"><div class="coordina-project-card__head"><div class="coordina-work-meta"><span class="coordina-status-badge status-${escapeHtml(item.status || 'draft')}">${escapeHtml(nice(item.status || 'draft'))}</span><span class="coordina-status-badge status-${escapeHtml(item.health || 'neutral')}">${escapeHtml(nice(item.health || 'neutral'))}</span><span class="coordina-status-badge">${escapeHtml(nice(item.priority || 'normal'))}</span></div></div><h3><button class="coordina-link-button" data-action="open-project" data-id="${item.id}">${iconLabel('project', item.title || __('Untitled project', 'coordina'))}</button></h3>${description ? `<p class="coordina-project-card__description">${escapeHtml(description)}</p>` : ''}${projectProgressBar(completion)}<div class="coordina-project-card__meta"><span>${escapeHtml(item.manager_label || __('No manager assigned', 'coordina'))}</span><span>${escapeHtml(__('Start:', 'coordina'))} ${escapeHtml(startDate)}</span><span>${escapeHtml(__('Target end:', 'coordina'))} ${escapeHtml(targetEnd)}</span></div></article>`;
	}).join('')}</div>`;
}

function rowHtml(item, module, mode) {
	const cells = module.columns.map((key) => `<td>${valueHtml(item, key, module)}</td>`).join('');
	const openButton = module.key === 'projects'
		? (item.can_open === false
			? `<button class="button button-small" type="button" disabled>${escapeHtml(__('Assigned access only', 'coordina'))}</button>`
			: `<button class="button button-small button-primary" data-action="open-project" data-id="${item.id}">${escapeHtml(__('Open workspace', 'coordina'))}</button>`)
		: module.key === 'tasks'
			? `<button class="button button-small button-primary" data-action="open-task-page" data-id="${item.id}" data-project-id="${item.project_id || ''}" data-project-tab="${item.project_id ? 'work' : ''}">${escapeHtml(__('Open', 'coordina'))}</button>`
		: module.key === 'milestones'
			? `<button class="button button-small button-primary" data-action="open-milestone-page" data-id="${item.id}" data-project-id="${item.project_id || ''}" data-project-tab="${item.project_id ? 'milestones' : ''}">${escapeHtml(__('Open', 'coordina'))}</button>`
		: module.key === 'risks-issues'
			? `<button class="button button-small button-primary" data-action="open-risk-issue-page" data-id="${item.id}" data-project-id="${item.project_id || ''}" data-project-tab="${item.project_id ? 'risks-issues' : ''}">${escapeHtml(__('Open', 'coordina'))}</button>`
		: `<button class="button button-small button-primary" data-action="open-record" data-module="${module.key}" data-id="${item.id}">${escapeHtml(__('Open', 'coordina'))}</button>`;
	const extra = module.key === 'requests' && item.can_convert ? `<button class="button button-small" data-action="open-convert" data-id="${item.id}">${escapeHtml(__('Convert', 'coordina'))}</button>` : '';
	const checkbox = mode === 'workspace' || module.key === 'projects' ? '' : `<input type="checkbox" data-action="toggle-selection" value="${item.id}" ${state.selection.includes(item.id) ? 'checked' : ''} />`;
	const deleteAction = deleteButton(item, module.key, item.title || item.object_label || item.file_name || __('Record', 'coordina'));
	return `<tr><td>${checkbox}</td>${cells}<td><div class="coordina-row-actions">${openButton}${extra}${deleteAction}</div></td></tr>`;
}

Object.assign(app, {
	noticesHtml,
	renderFilterBar,
	renderTable,
	valueHtml,
	rowHtml,
	columnLabel,
	renderProjectCards,
});

window.CoordinaAdminApp = app;
}());
