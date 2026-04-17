(function () {
const app = window.CoordinaAdminApp || {};

if (!app.root || !app.state) {
	return;
}

const { state, modules, root, escapeHtml, __, nice, dateLabel, dateTimeInputValue, isDateKey, isCheckedValue, currentModule, hasProjectWorkspace, hasTaskPage, hasMilestonePage, hasRiskIssuePage, noticesHtml, api, modulePage, workspacePage, taskPage, milestonePage, riskIssuePage, collaborationPage, fileList, discussionTimeline, collaborationActionButtons, canAccessPage, getPageMeta } = app;

function renderAppView(name) {
	return typeof app[name] === 'function' ? app[name]() : '';
}

function pageDescriptionsEnabled() {
	if (state.page === 'coordina-settings') {
		return true;
	}
	return !state.shell || state.shell.pageDescriptionsEnabled !== false;
}

function sectionDescriptionsEnabled() {
	if (state.page === 'coordina-settings') {
		return true;
	}
	return !state.shell || state.shell.sectionDescriptionsEnabled !== false;
}

function buildRouteAttributes(route) {
	if (!route || !route.page) {
		return '';
	}
	const attrs = [
		`data-action="open-route"`,
		`data-page="${escapeHtml(route.page || '')}"`,
		`data-project-id="${escapeHtml(route.project_id || '')}"`,
		`data-project-tab="${escapeHtml(route.project_tab || '')}"`,
		`data-task-id="${escapeHtml(route.task_id || '')}"`,
		`data-milestone-id="${escapeHtml(route.milestone_id || '')}"`,
		`data-risk-issue-id="${escapeHtml(route.risk_issue_id || '')}"`,
	];
	return attrs.join(' ');
}

function buildDataAttributes(data) {
	if (!data || typeof data !== 'object') {
		return '';
	}
	return Object.keys(data).map((key) => {
		const attr = key.replace(/[A-Z]/g, (char) => `-${char.toLowerCase()}`);
		return `data-${attr}="${escapeHtml(data[key] == null ? '' : data[key])}"`;
	}).join(' ');
}

function routeAction(label, route, tone) {
	return { kind: 'route', label, route: route || {}, tone: tone || 'neutral' };
}

function buttonAction(label, data, tone) {
	return { kind: 'button', label, data: data || {}, tone: tone || 'neutral' };
}

function renderHeaderAction(action) {
	if (!action || !action.label) {
		return '';
	}
	const toneClass = action.tone ? ` is-${escapeHtml(action.tone)}` : '';
	if (action.kind === 'route') {
		return `<button type="button" class="coordina-shell-menu__item${toneClass}" ${buildRouteAttributes(action.route)}>${escapeHtml(action.label)}</button>`;
	}
	return `<button type="button" class="coordina-shell-menu__item${toneClass}" ${buildDataAttributes(action.data)}>${escapeHtml(action.label)}</button>`;
}

function renderHeaderActions(actions) {
	if (!Array.isArray(actions) || !actions.length) {
		return '';
	}
	return `<details class="coordina-shell-menu"><summary class="coordina-shell-trigger coordina-shell-trigger--icon coordina-shell-trigger--menu" aria-label="${escapeHtml(__('Open actions', 'coordina'))}">${shellIcon('actions')}<span class="screen-reader-text">${escapeHtml(__('Open actions', 'coordina'))}</span><span class="coordina-shell-trigger__badge">${actions.length}</span></summary><div class="coordina-shell-menu__panel">${actions.map(renderHeaderAction).join('')}</div></details>`;
}

function shellIcon(type) {
	if (type === 'actions') {
		return `<span class="coordina-shell-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M5 7.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm7 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm7 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM5 13.5A1.5 1.5 0 1 0 5 10.5a1.5 1.5 0 0 0 0 3Zm7 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm7 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM5 19.5A1.5 1.5 0 1 0 5 16.5a1.5 1.5 0 0 0 0 3Zm7 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm7 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"></path></svg></span>`;
	}
	if (type === 'inbox') {
		return `<span class="coordina-shell-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v10A2.5 2.5 0 0 1 17.5 18H15a3 3 0 0 1-6 0H6.5A2.5 2.5 0 0 1 4 15.5v-10Zm2.5-.5a.5.5 0 0 0-.5.5v10c0 .276.224.5.5.5h4.085l.278 1.106a1 1 0 0 0 .97.894h.334a1 1 0 0 0 .97-.894L13.415 16H17.5a.5.5 0 0 0 .5-.5v-10a.5.5 0 0 0-.5-.5h-11Z"></path></svg></span>`;
	}
	return '';
}

function entityIconSvg(type) {
	const map = {
		dashboard: '<svg viewBox="0 0 24 24" focusable="false"><rect x="4" y="4" width="7" height="7" rx="1.5"></rect><rect x="13" y="4" width="7" height="4" rx="1.5"></rect><rect x="13" y="10" width="7" height="10" rx="1.5"></rect><rect x="4" y="13" width="7" height="7" rx="1.5"></rect></svg>',
		'my-work': '<svg viewBox="0 0 24 24" focusable="false"><path d="M6 12h12"></path><path d="M12 6v12"></path><circle cx="12" cy="12" r="7"></circle></svg>',
		project: '<svg viewBox="0 0 24 24" focusable="false"><path d="M4 7.5h7"></path><path d="M4 12h16"></path><path d="M4 16.5h10"></path><rect x="3" y="4" width="18" height="16" rx="2"></rect></svg>',
		task: '<svg viewBox="0 0 24 24" focusable="false"><path d="M9 7h10"></path><path d="M9 12h10"></path><path d="M9 17h10"></path><path d="m4.5 7 1.5 1.5 2.5-3"></path><path d="m4.5 12 1.5 1.5 2.5-3"></path><path d="m4.5 17 1.5 1.5 2.5-3"></path></svg>',
		milestone: '<svg viewBox="0 0 24 24" focusable="false"><path d="M5 19V5"></path><path d="M6 6h11l-2.5 3 2.5 3H6"></path><path d="M5 19h14"></path></svg>',
		'risk-issue': '<svg viewBox="0 0 24 24" focusable="false"><path d="M12 4 20 19H4Z"></path><path d="M12 9v4.5"></path><path d="M12 17h.01"></path></svg>',
		risk: '<svg viewBox="0 0 24 24" focusable="false"><path d="M12 4 20 19H4Z"></path><path d="M12 9v4.5"></path><path d="M12 17h.01"></path></svg>',
		issue: '<svg viewBox="0 0 24 24" focusable="false"><path d="M12 3 20 12 12 21 4 12Z"></path><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>',
		request: '<svg viewBox="0 0 24 24" focusable="false"><path d="M5 7.5h9"></path><path d="M5 12h14"></path><path d="M5 16.5h10"></path><path d="m15 4 4 4-4 4"></path></svg>',
		approval: '<svg viewBox="0 0 24 24" focusable="false"><path d="m5 12.5 4.2 4.2L19 7"></path></svg>',
		calendar: '<svg viewBox="0 0 24 24" focusable="false"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M8 3v4"></path><path d="M16 3v4"></path><path d="M3 10h18"></path><path d="M8 14h3"></path><path d="M13 14h3"></path><path d="M8 18h3"></path></svg>',
		workload: '<svg viewBox="0 0 24 24" focusable="false"><path d="M4 17h4"></path><path d="M10 17h4"></path><path d="M16 17h4"></path><path d="M6 17V9"></path><path d="M12 17V5"></path><path d="M18 17v-7"></path></svg>',
		file: '<svg viewBox="0 0 24 24" focusable="false"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"></path><path d="M14 3v5h5"></path><path d="M8 13h8"></path><path d="M8 17h5"></path></svg>',
		discussion: '<svg viewBox="0 0 24 24" focusable="false"><path d="M5 18.5V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H9l-4 2.5Z"></path><path d="M8 9h8"></path><path d="M8 13h5"></path></svg>',
		settings: '<svg viewBox="0 0 24 24" focusable="false"><path d="M4 7h6"></path><path d="M14 7h6"></path><path d="M4 17h10"></path><path d="M18 17h2"></path><circle cx="12" cy="7" r="2"></circle><circle cx="16" cy="17" r="2"></circle></svg>',
		inbox: '<svg viewBox="0 0 24 24" focusable="false"><path d="M4 6h16v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2Z"></path><path d="M4 8.5 12 13l8-4.5"></path></svg>',
	};
	return map[String(type || '')] || '<svg viewBox="0 0 24 24" focusable="false"><path d="M12 6v12"></path><path d="M6 12h12"></path></svg>';
}

function entityIcon(type, className) {
	const classes = ['coordina-entity-icon', `coordina-entity-icon--${escapeHtml(String(type || 'generic'))}`];
	if (className) {
		classes.push(className);
	}
	return `<span class="${classes.join(' ')}" aria-hidden="true">${entityIconSvg(type)}</span>`;
}

function iconLabel(type, label, className) {
	const classes = ['coordina-entity-label'];
	if (className) {
		classes.push(className);
	}
	return `<span class="${classes.join(' ')}">${entityIcon(type)}<span class="coordina-entity-label__text">${escapeHtml(label || '')}</span></span>`;
}

function moduleIconType(moduleKey) {
	const map = {
		projects: 'project',
		tasks: 'task',
		requests: 'request',
		approvals: 'approval',
		milestones: 'milestone',
		'risks-issues': 'risk-issue',
	};
	return map[String(moduleKey || '')] || 'dashboard';
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

function normalizeHexColor(value) {
	const color = String(value || '').trim();
	if (!/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(color)) {
		return '';
	}
	if (color.length === 4) {
		return `#${color.slice(1).split('').map((char) => `${char}${char}`).join('')}`.toLowerCase();
	}
	return color.toLowerCase();
}

function hexToRgb(value) {
	const hex = normalizeHexColor(value);
	if (!hex) {
		return null;
	}
	const normalized = hex.slice(1);
	return {
		r: parseInt(normalized.slice(0, 2), 16),
		g: parseInt(normalized.slice(2, 4), 16),
		b: parseInt(normalized.slice(4, 6), 16),
	};
}

function rgbaColor(value, alpha) {
	const rgb = hexToRgb(value);
	if (!rgb) {
		return '';
	}
	return `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${alpha})`;
}

function darkenHex(value, factor) {
	const rgb = hexToRgb(value);
	if (!rgb) {
		return '';
	}
	const scale = Math.max(0, Math.min(1, Number(factor || 1)));
	const toHex = (channel) => Math.max(0, Math.min(255, Math.round(channel * scale))).toString(16).padStart(2, '0');
	return `#${toHex(rgb.r)}${toHex(rgb.g)}${toHex(rgb.b)}`;
}

function shellInlineStyle() {
	const styles = [];
	const primary = state.shell && state.shell.themePrimaryCustomColor ? normalizeHexColor(state.shell.themePrimaryCustomColor) : '';
	const accent = state.shell && state.shell.themeAccentCustomColor ? normalizeHexColor(state.shell.themeAccentCustomColor) : '';
	if (primary) {
		styles.push(`--coordina-primary:${primary}`);
		styles.push(`--coordina-primary-strong:${darkenHex(primary, 0.84) || primary}`);
		styles.push(`--coordina-primary-soft:${rgbaColor(primary, 0.14)}`);
		styles.push(`--coordina-primary-lighter:${rgbaColor(primary, 0.08)}`);
		styles.push(`--coordina-info:${primary}`);
		styles.push(`--coordina-info-strong:${darkenHex(primary, 0.84) || primary}`);
		styles.push(`--coordina-info-soft:${rgbaColor(primary, 0.14)}`);
		styles.push(`--coordina-info-lighter:${rgbaColor(primary, 0.08)}`);
		styles.push(`--coordina-gradient-info:${primary}`);
	}
	if (accent) {
		styles.push(`--coordina-accent:${accent}`);
		styles.push(`--coordina-accent-soft:${rgbaColor(accent, 0.16)}`);
		styles.push(`--coordina-accent-lighter:${rgbaColor(accent, 0.09)}`);
	}
	return styles.join(';');
}

function shellPrimaryNav() {
	const fallbackPage = canAccessPage('coordina-dashboard') ? 'coordina-dashboard' : 'coordina-my-work';
	return `<div class="coordina-shell__primary-nav"><button type="button" class="coordina-shell__brand" data-action="open-route" data-page="${escapeHtml(fallbackPage)}">${escapeHtml(__('Coordina', 'coordina'))}</button></div>`;
}

function breadcrumbHtml(items) {
	if (!Array.isArray(items) || !items.length) {
		return '';
	}
	return `<nav class="coordina-shell__breadcrumbs" aria-label="${escapeHtml(__('Breadcrumb', 'coordina'))}">${items.map((item, index) => {
		const isLast = index === items.length - 1;
		const label = escapeHtml(item.label || '');
		const separator = isLast ? '' : `<span class="coordina-shell__breadcrumb-separator" aria-hidden="true">/</span>`;
		if (!isLast && item.route && item.route.page) {
			return `<span class="coordina-shell__breadcrumb">` +
				`<button class="coordina-shell__breadcrumb-link" type="button" ${buildRouteAttributes(item.route)}>${label}</button>` +
				`${separator}</span>`;
		}
		return `<span class="coordina-shell__breadcrumb is-current"><span class="coordina-shell__breadcrumb-current">${label}</span>${separator}</span>`;
	}).join('')}</nav>`;
}

function topLevelHeaderKind(page) {
	const kinds = {
		'coordina-dashboard': __('Overview', 'coordina'),
		'coordina-my-work': __('Execution hub', 'coordina'),
		'coordina-projects': __('Portfolio', 'coordina'),
		'coordina-requests': __('Intake queue', 'coordina'),
		'coordina-approvals': __('Decision queue', 'coordina'),
		'coordina-tasks': __('Task library', 'coordina'),
		'coordina-calendar': __('Planning surface', 'coordina'),
		'coordina-workload': __('Manager view', 'coordina'),
		'coordina-risks-issues': __('Exception register', 'coordina'),
		'coordina-files-discussion': __('Collaboration', 'coordina'),
		'coordina-settings': __('Global settings', 'coordina'),
	};
	return kinds[String(page || '')] || __('Coordina', 'coordina');
}

function pageTitleFor(page) {
	const pageMeta = getPageMeta(page) || {};
	return pageMeta.title || nice(String(page || 'coordina').replace(/^coordina-/, ''));
}

function pageDescriptionFor(page) {
	const pageMeta = getPageMeta(page) || {};
	return pageMeta.description || '';
}

function workspaceTabLabel(tabKey, tabs) {
	const found = Array.isArray(tabs) ? tabs.find((tab) => String(tab.key || '') === String(tabKey || '')) : null;
	return found && found.label ? found.label : nice(tabKey || 'overview');
}

function moduleCanCreate(module) {
	if (!module || module.createEnabled === false) {
		return false;
	}
	const caps = state.shell && state.shell.capabilities ? state.shell.capabilities : {};
	if (module.key === 'projects') {
		return !!caps.canManageProjects;
	}
	if (module.key === 'tasks') {
		return !!caps.canManageTasks;
	}
	if (module.key === 'requests') {
		return !!caps.canManageRequests;
	}
	if (module.key === 'milestones' || module.key === 'risks-issues') {
		return !!caps.canManageProjects;
	}
	return true;
}

function moduleHeaderActions(module) {
	if (!module) {
		return [];
	}
	const actions = [buttonAction(__('Save view', 'coordina'), { action: 'save-view' })];
	if (moduleCanCreate(module)) {
		actions.push(buttonAction(`${__('New', 'coordina')} ${module.singular || __('record', 'coordina')}`, { action: 'open-create' }, 'primary'));
	}
	return actions;
}

function workspaceHeaderActions(project, actions) {
	const list = [routeAction(__('Back to projects', 'coordina'), { page: 'coordina-projects' })];
	if (actions && actions.canEditProject) {
		list.push(buttonAction(__('Edit project', 'coordina'), { action: 'open-project-details-edit', id: project.id || '' }));
	}
	if (actions && actions.canPostUpdate) {
		list.push(buttonAction(__('Add update', 'coordina'), { action: 'open-discussion-create', objectType: 'project', objectId: project.id || '', objectLabel: project.title || __('Project workspace', 'coordina'), lockContext: '1' }));
	}
	if (actions && actions.canAttachFile) {
		list.push(buttonAction(__('Attach file', 'coordina'), { action: 'open-file-create', objectType: 'project', objectId: project.id || '', objectLabel: project.title || __('Project workspace', 'coordina'), lockContext: '1' }));
	}
	if (actions && actions.canCreateRiskIssue) {
		list.push(buttonAction(__('Add risk', 'coordina'), { action: 'open-project-risk-create', type: 'risk' }));
	}
	if (actions && actions.canCreateTask) {
		list.push(buttonAction(__('Add task', 'coordina'), { action: 'open-project-task-create' }, 'primary'));
	}
	if (project && project.can_delete) {
		list.push(buttonAction(__('Delete project', 'coordina'), { action: 'delete-record', module: 'projects', id: project.id || '', label: project.title || __('Project', 'coordina') }, 'danger'));
	}
	return list;
}

function taskHeaderActions(task) {
	const actions = [];
	if (Number(task.project_id || 0) > 0) {
		actions.push(routeAction(__('Back to project work', 'coordina'), { page: 'coordina-projects', project_id: task.project_id, project_tab: 'work' }));
	} else if (canAccessPage('coordina-tasks')) {
		actions.push(routeAction(__('Back to task list', 'coordina'), { page: 'coordina-tasks' }));
	} else {
		actions.push(routeAction(__('Back to My Work', 'coordina'), { page: 'coordina-my-work' }));
	}
	if (task.can_post_update) {
		actions.push(buttonAction(__('Add update', 'coordina'), { action: 'open-discussion-create', objectType: 'task', objectId: task.id || '', objectLabel: task.title || __('Task', 'coordina'), lockContext: '1' }));
	}
	if (task.can_attach_files) {
		actions.push(buttonAction(__('Attach file', 'coordina'), { action: 'open-file-create', objectType: 'task', objectId: task.id || '', objectLabel: task.title || __('Task', 'coordina'), lockContext: '1' }));
	}
	if (task.can_edit) {
		actions.push(buttonAction(state.taskDetailEditing ? __('Hide edit form', 'coordina') : __('Edit task', 'coordina'), { action: 'toggle-task-edit' }, 'primary'));
	}
	if (task.can_delete) {
		actions.push(buttonAction(__('Delete task', 'coordina'), { action: 'delete-record', module: 'tasks', id: task.id || '', label: task.title || __('Task', 'coordina'), projectId: task.project_id || '' }, 'danger'));
	}
	return actions;
}

function milestoneHeaderActions(milestone) {
	const actions = [];
	if (Number(milestone.project_id || 0) > 0) {
		actions.push(routeAction(__('Back to project milestones', 'coordina'), { page: 'coordina-projects', project_id: milestone.project_id, project_tab: 'milestones' }));
	} else {
		actions.push(routeAction(__('Back to My Work', 'coordina'), { page: 'coordina-my-work' }));
	}
	if (milestone.can_post_update) {
		actions.push(buttonAction(__('Add update', 'coordina'), { action: 'open-discussion-create', objectType: 'milestone', objectId: milestone.id || '', objectLabel: milestone.title || __('Milestone', 'coordina'), lockContext: '1' }));
	}
	if (milestone.can_attach_files) {
		actions.push(buttonAction(__('Attach file', 'coordina'), { action: 'open-file-create', objectType: 'milestone', objectId: milestone.id || '', objectLabel: milestone.title || __('Milestone', 'coordina'), lockContext: '1' }));
	}
	if (milestone.can_edit) {
		actions.push(buttonAction(state.milestoneDetailEditing ? __('Hide edit form', 'coordina') : __('Edit fields', 'coordina'), { action: 'toggle-milestone-edit' }, 'primary'));
	}
	if (milestone.can_delete) {
		actions.push(buttonAction(__('Delete milestone', 'coordina'), { action: 'delete-record', module: 'milestones', id: milestone.id || '', label: milestone.title || __('Milestone', 'coordina'), projectId: milestone.project_id || '' }, 'danger'));
	}
	return actions;
}

function riskIssueHeaderActions(riskIssue) {
	const actions = [];
	if (Number(riskIssue.project_id || 0) > 0) {
		actions.push(routeAction(__('Back to project risks', 'coordina'), { page: 'coordina-projects', project_id: riskIssue.project_id, project_tab: 'risks-issues' }));
	} else if (canAccessPage('coordina-risks-issues')) {
		actions.push(routeAction(__('Back to risks & issues', 'coordina'), { page: 'coordina-risks-issues' }));
	} else {
		actions.push(routeAction(__('Back to My Work', 'coordina'), { page: 'coordina-my-work' }));
	}
	if (riskIssue.can_post_update) {
		actions.push(buttonAction(__('Add update', 'coordina'), { action: 'open-discussion-create', objectType: riskIssue.object_type || 'risk', objectId: riskIssue.id || '', objectLabel: riskIssue.title || __('Risk or issue', 'coordina'), lockContext: '1' }));
	}
	if (riskIssue.can_attach_files) {
		actions.push(buttonAction(__('Attach file', 'coordina'), { action: 'open-file-create', objectType: riskIssue.object_type || 'risk', objectId: riskIssue.id || '', objectLabel: riskIssue.title || __('Risk or issue', 'coordina'), lockContext: '1' }));
	}
	if (riskIssue.can_edit) {
		actions.push(buttonAction(state.riskIssueDetailEditing ? __('Hide edit form', 'coordina') : __('Edit fields', 'coordina'), { action: 'toggle-risk-issue-edit' }, 'primary'));
	}
	if (riskIssue.can_delete) {
		actions.push(buttonAction(__('Delete record', 'coordina'), { action: 'delete-record', module: 'risks-issues', id: riskIssue.id || '', label: riskIssue.title || __('Risk or issue', 'coordina'), projectId: riskIssue.project_id || '' }, 'danger'));
	}
	return actions;
}

function approvalSourceRoute(item) {
	const objectType = String(item && item.object_type ? item.object_type : '');
	const objectId = Number(item && item.object_id ? item.object_id : 0);
	const projectId = Number(item && item.project_id ? item.project_id : 0);
	if (objectType === 'project' && objectId > 0) {
		return { page: 'coordina-projects', project_id: objectId, project_tab: 'approvals' };
	}
	if (objectType === 'task') {
		return { page: 'coordina-task', task_id: objectId, project_id: projectId, project_tab: projectId > 0 ? 'work' : '' };
	}
	if (objectType === 'milestone') {
		return { page: 'coordina-milestone', milestone_id: objectId, project_id: projectId, project_tab: projectId > 0 ? 'milestones' : '' };
	}
	if (objectType === 'request') {
		return { page: 'coordina-requests' };
	}
	if (objectType === 'risk' || objectType === 'issue') {
		return { page: 'coordina-risk-issue', risk_issue_id: objectId, project_id: projectId, project_tab: projectId > 0 ? 'risks-issues' : '' };
	}
	return null;
}

function approvalDecisionForm(values) {
	if (!values || !values.id) {
		return `<section class="coordina-card coordina-card--notice"><p>${escapeHtml(__('Approvals are generated from linked work items. Create or update the parent project, task, request, or other record instead of creating an approval directly.', 'coordina'))}</p><div class="coordina-form-actions"><button class="button" type="button" data-action="close-drawer">${escapeHtml(__('Close', 'coordina'))}</button></div></section>`;
	}
	if (values.can_edit === false) {
		return `<section class="coordina-card coordina-card--notice"><p>${escapeHtml(__('You can review this approval, but only the assigned approver can record the decision.', 'coordina'))}</p><div class="coordina-form-actions"><button class="button" type="button" data-action="close-drawer">${escapeHtml(__('Close', 'coordina'))}</button></div></section>`;
	}
	const currentStatus = String(values.status || 'pending');
	const decisionNotes = {
		pending: __('Leave this as pending if more information is still needed before a decision can be recorded.', 'coordina'),
		approved: __('Choose approved when the linked work can move ahead without further decision changes.', 'coordina'),
		rejected: __('Choose rejected when the work should not proceed in its current form. Explain what needs to change.', 'coordina'),
		cancelled: __('Choose cancelled when the approval is no longer needed or the underlying work has been withdrawn.', 'coordina'),
	};
	const statusChoices = (state.shell.statuses.approvals || []).map((item) => `<label class="coordina-approval-choice ${currentStatus === item ? 'is-selected' : ''}"><input type="radio" name="status" value="${item}" ${currentStatus === item ? 'checked' : ''} /><span class="coordina-approval-choice__body"><strong>${escapeHtml(nice(item))}</strong><span>${escapeHtml(decisionNotes[item] || __('Record the outcome clearly so the requester knows what happens next.', 'coordina'))}</span></span></label>`).join('');
	return `<form class="coordina-form coordina-approval-decision-form" data-action="save-form" data-module="approvals" data-id="${values.id}"><section class="coordina-card coordina-card--notice coordina-approval-decision-form__intro"><h4>${escapeHtml(__('Record a decision', 'coordina'))}</h4><p>${escapeHtml(__('Choose the outcome for this approval and add a short note if the requester or team needs context.', 'coordina'))}</p></section><div class="coordina-approval-decision-form__layout"><section class="coordina-approval-panel"><div class="coordina-section-header"><div><h4>${escapeHtml(__('Decision', 'coordina'))}</h4><p class="coordina-section-note">${escapeHtml(__('Pick the outcome that best matches what should happen next.', 'coordina'))}</p></div></div><div class="coordina-approval-choice-grid">${statusChoices}</div></section><section class="coordina-approval-panel"><div class="coordina-section-header"><div><h4>${escapeHtml(__('Decision note', 'coordina'))}</h4><p class="coordina-section-note">${escapeHtml(__('Use this to explain the decision, especially when rejecting or cancelling.', 'coordina'))}</p></div></div><label class="coordina-approval-note-field"><span>${escapeHtml(__('Reason or note', 'coordina'))}</span><textarea name="rejection_reason" rows="6" placeholder="${escapeHtml(__('Example: Approved for this phase. If rejected, explain what needs to change before resubmission.', 'coordina'))}">${escapeHtml(values.rejection_reason || '')}</textarea></label><p class="coordina-approval-note-hint">${escapeHtml(__('Keep the note short, specific, and actionable so the next step is obvious to the requester.', 'coordina'))}</p></section></div><div class="coordina-form-actions"><button class="button button-primary" type="submit">${escapeHtml(__('Save decision', 'coordina'))}</button><button class="button" type="button" data-action="close-drawer">${escapeHtml(__('Cancel', 'coordina'))}</button></div></form>`;
}

function collaborationContextTypes() {
	return (state.shell.contextObjectTypes || []).filter((item) => item !== 'approval');
}

function contextTypeOptions(selected) {
	return collaborationContextTypes().map((item) => `<option value="${item}" ${String(selected || '') === String(item) ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('');
}

function lockedContextSummary(values, fallbackLabel) {
	if (!values || !values.object_type || !values.object_id) {
		return '';
	}
	const label = values.object_label || fallbackLabel || nice(values.object_type || __('linked work', 'coordina'));
	return `<div class="coordina-card coordina-card--notice"><p>${escapeHtml(__('This item will stay attached to its parent context.', 'coordina'))}</p><div class="coordina-work-meta"><span>${escapeHtml(nice(values.object_type))}</span><span>${escapeHtml(label)}</span></div><input type="hidden" name="object_type" value="${escapeHtml(values.object_type)}" /><input type="hidden" name="object_id" value="${escapeHtml(values.object_id)}" /><input type="hidden" name="lock_context" value="1" /></div>`;
}

function fileForm(values) {
	const objectType = values && values.object_type ? values.object_type : '';
	const objectId = values && values.object_id ? values.object_id : '';
	const attachmentId = values && values.attachment_id ? values.attachment_id : '';
	const attachmentLabel = values && values.attachment_title ? values.attachment_title : __('No file selected yet.', 'coordina');
	const locked = !!(values && values.lock_context && objectType && objectId);
	const contextFields = locked
		? lockedContextSummary(values, values && values.object_label ? values.object_label : __('Linked work', 'coordina'))
		: `<div class="coordina-form-grid"><label><span>${escapeHtml(__('Context type', 'coordina'))}</span><select name="object_type">${contextTypeOptions(objectType)}</select></label><label><span>${escapeHtml(__('Context id', 'coordina'))}</span><input type="number" name="object_id" min="1" value="${escapeHtml(objectId)}" required /></label></div>`;
	return `<form class="coordina-form" data-action="save-form" data-module="files" data-id="${values && values.id ? values.id : ''}">${contextFields}<div class="coordina-form-grid"><label class="coordina-file-picker"><span>${escapeHtml(__('Selected file', 'coordina'))}</span><input type="hidden" name="attachment_id" value="${escapeHtml(attachmentId)}" /><span data-role="selected-file-label">${escapeHtml(attachmentLabel)}</span><button class="button" type="button" data-action="select-file">${escapeHtml(__('Choose file', 'coordina'))}</button></label><label><span>${escapeHtml(__('Note', 'coordina'))}</span><textarea name="note">${escapeHtml(values && values.note ? values.note : '')}</textarea></label></div><div class="coordina-form-actions"><button class="button button-primary" type="submit">${escapeHtml(__('Attach file', 'coordina'))}</button><button class="button" type="button" data-action="close-modal">${escapeHtml(__('Cancel', 'coordina'))}</button></div></form>`;
}

function discussionForm(values) {
	const objectType = values && values.object_type ? values.object_type : '';
	const objectId = values && values.object_id ? values.object_id : '';
	const locked = !!(values && values.lock_context && objectType && objectId);
	const contextFields = locked
		? lockedContextSummary(values, values && values.object_label ? values.object_label : __('Linked work', 'coordina'))
		: `<div class="coordina-form-grid"><label><span>${escapeHtml(__('Context type', 'coordina'))}</span><select name="object_type">${contextTypeOptions(objectType)}</select></label><label><span>${escapeHtml(__('Context id', 'coordina'))}</span><input type="number" name="object_id" min="1" value="${escapeHtml(objectId)}" required /></label></div>`;
	return `<form class="coordina-form" data-action="save-form" data-module="discussions" data-id="${values && values.id ? values.id : ''}">${contextFields}<div class="coordina-form-grid"><label class="coordina-form-grid__wide"><span>${escapeHtml(__('Update', 'coordina'))}</span><textarea name="body" required>${escapeHtml(values && values.body ? values.body : '')}</textarea></label></div><div class="coordina-form-actions"><button class="button button-primary" type="submit">${escapeHtml(__('Post update', 'coordina'))}</button><button class="button" type="button" data-action="close-modal">${escapeHtml(__('Cancel', 'coordina'))}</button></div></form>`;
}

function checklistForm(values) {
	const checklist = values || {};
	const objectType = checklist.object_type || '';
	const objectId = checklist.object_id || '';
	const objectLabel = checklist.object_label || __('Linked work', 'coordina');
	const isEdit = !!checklist.id;
	return `<form class="coordina-form" data-action="checklist-form" data-id="${checklist.id || ''}"><div class="coordina-card coordina-card--notice"><p>${escapeHtml(__('Each checklist stays attached to its parent record as a complete named checklist.', 'coordina'))}</p><div class="coordina-work-meta"><span>${escapeHtml(nice(objectType || 'task'))}</span><span>${escapeHtml(objectLabel)}</span></div><input type="hidden" name="object_type" value="${escapeHtml(objectType)}" /><input type="hidden" name="object_id" value="${escapeHtml(objectId)}" /></div><div class="coordina-form-grid"><label class="coordina-form-grid__wide"><span>${escapeHtml(__('Checklist name', 'coordina'))}</span><input type="text" name="title" value="${escapeHtml(checklist.title || '')}" placeholder="${escapeHtml(__('Checklist name', 'coordina'))}" required /></label></div><div class="coordina-form-actions"><button class="button button-primary" type="submit">${escapeHtml(isEdit ? __('Save checklist', 'coordina') : __('Add checklist', 'coordina'))}</button><button class="button" type="button" data-action="close-modal">${escapeHtml(__('Cancel', 'coordina'))}</button></div></form>`;
}

function checklistItemForm(values) {
	const item = values || {};
	const objectType = item.object_type || '';
	const objectId = item.object_id || '';
	const objectLabel = item.object_label || __('Linked work', 'coordina');
	const checklistId = item.checklist_id || '';
	const checklistTitle = item.checklist_title || __('Checklist', 'coordina');
	const isEdit = !!item.id;
	return `<form class="coordina-form" data-action="checklist-item-form" data-id="${item.id || ''}"><div class="coordina-card coordina-card--notice"><p>${escapeHtml(__('Checklist items stay under their checklist header and parent record.', 'coordina'))}</p><div class="coordina-work-meta"><span>${escapeHtml(nice(objectType || 'task'))}</span><span>${escapeHtml(objectLabel)}</span><span>${escapeHtml(checklistTitle)}</span></div><input type="hidden" name="checklist_id" value="${escapeHtml(checklistId)}" /><input type="hidden" name="object_type" value="${escapeHtml(objectType)}" /><input type="hidden" name="object_id" value="${escapeHtml(objectId)}" /></div><div class="coordina-form-grid"><label class="coordina-form-grid__wide"><span>${escapeHtml(__('Checklist item', 'coordina'))}</span><input type="text" name="item_text" value="${escapeHtml(item.item_text || '')}" required /></label><label class="coordina-checkbox"><input type="checkbox" name="is_done" value="1" ${item.is_done ? 'checked' : ''} /><span>${escapeHtml(__('Done', 'coordina'))}</span></label></div><div class="coordina-form-actions"><button class="button button-primary" type="submit">${escapeHtml(isEdit ? __('Save item', 'coordina') : __('Add item', 'coordina'))}</button><button class="button" type="button" data-action="close-modal">${escapeHtml(__('Cancel', 'coordina'))}</button></div></form>`;
}

function contextSeed(module, item) {
	if (module.key === 'tasks') {
		return { object_type: 'task', object_id: item.id, object_label: item.title || __('Task', 'coordina') };
	}
	if (module.key === 'requests') {
		return { object_type: 'request', object_id: item.id, object_label: item.title || __('Request', 'coordina') };
	}
	if (module.key === 'risks-issues') {
		return { object_type: item.object_type || 'risk', object_id: item.id, object_label: item.title || nice(item.object_type || 'risk') };
	}
	if (module.key === 'milestones') {
		return { object_type: 'milestone', object_id: item.id, object_label: item.title || __('Milestone', 'coordina') };
	}
	return null;
}

function contextSections(module, item, files, discussions) {
	const seed = contextSeed(module, item);
	if (!seed) {
		return '';
	}
	const permissions = { canPostUpdate: !!item.can_post_update, canAttachFile: !!item.can_attach_files };
	return `<div class="coordina-drawer-section"><div class="coordina-section-header"><h4>${escapeHtml(__('Updates', 'coordina'))}</h4>${collaborationActionButtons(seed, permissions)}</div>${discussionTimeline(discussions.items || [], __('No updates attached to this record yet.', 'coordina'))}</div><div class="coordina-drawer-section"><div class="coordina-section-header"><h4>${escapeHtml(__('Files', 'coordina'))}</h4></div>${fileList(files.items || [], __('No files attached to this record yet.', 'coordina'))}</div>`;
}

function checklistEditorItems(values) {
	if (values && Array.isArray(values.checklist) && values.checklist.length) {
		return values.checklist.map((item) => ({
			text: String(item && item.item_text ? item.item_text : '').trim(),
			done: !!(item && item.is_done),
		})).filter((item) => item.text);
	}

	const source = values && typeof values.checklist_text !== 'undefined' ? values.checklist_text : (values && values.checklist ? values.checklist : '');
	return String(source || '').split(/\r\n|\r|\n/).map((line) => {
		const text = String(line || '').trim();
		if (!text) {
			return null;
		}
		const checkedMatch = text.match(/^\[(x|X)\]\s*(.+)$/);
		if (checkedMatch) {
			return { text: checkedMatch[2].trim(), done: true };
		}
		const openMatch = text.match(/^\[\s\]\s*(.+)$/);
		if (openMatch) {
			return { text: openMatch[1].trim(), done: false };
		}
		return { text, done: false };
	}).filter(Boolean);
}

function checklistSerializedValue(items) {
	return (items || []).map((item) => `${item.done ? '[x]' : '[ ]'} ${item.text}`).join('\n');
}

function checklistEditor(values) {
	const items = checklistEditorItems(values || {});
	const rows = (items.length ? items : [{ text: '', done: false }]).map((item, index) => `<div class="coordina-checklist-editor__item" data-checklist-item><label class="coordina-checklist-editor__toggle"><input type="checkbox" data-checklist-item-done ${item.done ? 'checked' : ''} /><span>${escapeHtml(__('Done', 'coordina'))}</span></label><input type="text" data-checklist-item-text value="${escapeHtml(item.text)}" placeholder="${escapeHtml(__('Checklist item', 'coordina'))}" /><button class="button button-small" type="button" data-action="remove-checklist-item" ${items.length === 1 && index === 0 && !item.text ? 'disabled' : ''}>${escapeHtml(__('Remove', 'coordina'))}</button></div>`).join('');
	return `<label class="coordina-form-grid__wide"><span>${escapeHtml(__('Checklist', 'coordina'))}</span><div class="coordina-checklist-editor" data-role="checklist-editor">${rows}</div><input type="hidden" name="checklist" data-role="checklist-value" value="${escapeHtml(checklistSerializedValue(items))}" /><div class="coordina-checklist-editor__actions"><button class="button" type="button" data-action="add-checklist-item">${escapeHtml(__('Add checklist item', 'coordina'))}</button><small>${escapeHtml(__('Use the checkbox to mark an item done and edit the text inline.', 'coordina'))}</small></div></label>`;
}

function fieldHint(text) {
	return text ? `<small class="coordina-field-hint">${escapeHtml(text)}</small>` : '';
}

function actionableProjects(moduleKey) {
	if (['tasks', 'milestones', 'risks-issues'].includes(String(moduleKey || ''))) {
		return state.shell.editableProjects || state.shell.projects || [];
	}
	return state.shell.projects || [];
}

function taskDetailForm(values) {
	const task = values || {};
	const users = state.shell.users || [];
	const projects = actionableProjects('tasks');
	const priorities = state.shell.priorities || [];
	const statuses = state.shell.statuses.tasks || [];
	const groups = state.workspace && Array.isArray(state.workspace.taskGroups)
		? state.workspace.taskGroups
		: (state.taskDetail && Array.isArray(state.taskDetail.taskGroups) ? state.taskDetail.taskGroups : []);
	const groupLabel = nice((state.workspace && state.workspace.taskGroupLabel) || (state.taskDetail && state.taskDetail.taskGroupLabel) || state.shell.taskGroupLabel || 'group');
	const wrapField = (label, control, hint, wide) => `<label class="${wide ? 'coordina-form-grid__wide' : ''}"><span>${escapeHtml(label)}</span>${control}${fieldHint(hint)}</label>`;
	const wrapBlock = (label, content, hint, wide) => `<div class="${wide ? 'coordina-form-grid__wide' : ''}"><span class="coordina-task-field-label">${escapeHtml(label)}</span>${content}${fieldHint(hint)}</div>`;
	const wrapToggle = (control, hint, wide) => `<div class="coordina-task-toggle-field ${wide ? 'coordina-form-grid__wide' : ''}">${control}${fieldHint(hint)}</div>`;
	const textValue = (name) => escapeHtml(task && typeof task[name] !== 'undefined' ? task[name] : '');
	const dateValue = (name) => escapeHtml(dateTimeInputValue(task && typeof task[name] !== 'undefined' ? task[name] : ''));
	const selectedUser = String(task.assignee_user_id || '');
	const selectedProject = String(task.project_id || 0);
	const selectedGroup = String(task.task_group_id || 0);
	const section = (title, description, fields) => `<section class="coordina-form-section coordina-task-edit-section"><div class="coordina-section-header"><div><h4>${escapeHtml(title)}</h4><p class="coordina-section-note">${escapeHtml(description)}</p></div></div><div class="coordina-form-grid">${fields.filter(Boolean).join('')}</div></section>`;

	return `<form class="coordina-form coordina-task-detail-form" data-action="save-form" data-module="tasks" data-id="${task.id || ''}">${section(
		__('Summary', 'coordina'),
		__('Start with the task name and expected outcome. Manage checklist groups from the task detail sidebar after saving.', 'coordina'),
		[
			wrapField(__('Title', 'coordina'), `<input type="text" name="title" value="${textValue('title')}" required />`, __('Use a short action-oriented title that makes the next step obvious.', 'coordina'), false),
			wrapField(__('Description', 'coordina'), `<textarea name="description">${textValue('description')}</textarea>`, __('Capture the outcome, important context, or handoff notes for this task.', 'coordina'), true),
		]
	)}${section(
		__('Execution', 'coordina'),
		__('Set ownership, workflow state, current completion, and whether completion needs approval.', 'coordina'),
		[
			wrapField(__('Status', 'coordina'), `<select name="status">${statuses.map((item) => `<option value="${item}" ${task.status === item ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select>`, __('Reflect the current workflow state rather than a future intention.', 'coordina'), false),
			wrapField(__('Priority', 'coordina'), `<select name="priority">${priorities.map((item) => `<option value="${item}" ${task.priority === item ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select>`, __('Use priority sparingly so high-priority work still stands out.', 'coordina'), false),
			wrapField(__('Assignee', 'coordina'), `<select name="assignee_user_id"><option value="">${escapeHtml(__('Unassigned', 'coordina'))}</option>${users.map((user) => `<option value="${user.id}" ${selectedUser === String(user.id) ? 'selected' : ''}>${escapeHtml(user.label)}</option>`).join('')}</select>`, __('Choose the person responsible for moving this task forward.', 'coordina'), false),
			wrapField(__('Completion', 'coordina'), `<input type="number" name="completion_percent" value="${escapeHtml(task.completion_percent || 0)}" min="0" max="100" />`, __('Update this to show real progress, not only the final done state.', 'coordina'), false),
			wrapToggle(`<label class="coordina-checkbox"><input type="checkbox" name="approval_required" value="1" ${isCheckedValue(task.approval_required) ? 'checked' : ''} /><span>${escapeHtml(__('Approval required', 'coordina'))}</span></label>`, __('Turn this on when the task should pause at completion until someone approves it.', 'coordina'), false),
		]
	)}${section(
		__('Planning', 'coordina'),
		__('Keep the task anchored to the right project context and planning dates.', 'coordina'),
		[
			wrapField(__('Project', 'coordina'), `<select name="project_id"><option value="0">${escapeHtml(__('Standalone task', 'coordina'))}</option>${projects.map((project) => `<option value="${project.id}" ${selectedProject === String(project.id) ? 'selected' : ''}>${escapeHtml(project.label)}</option>`).join('')}</select>`, __('Keep this linked to a project when the work belongs inside a project workspace.', 'coordina'), false),
			wrapField(groupLabel, `<select name="task_group_id"><option value="0">${escapeHtml(__('Ungrouped', 'coordina'))}</option>${groups.map((group) => `<option value="${group.id}" ${selectedGroup === String(group.id) ? 'selected' : ''}>${escapeHtml(group.title)}</option>`).join('')}</select>`, __('Use a group when this task should sit under a project stage, phase, or bucket.', 'coordina'), false),
			wrapField(__('Start date', 'coordina'), `<input type="datetime-local" name="start_date" value="${dateValue('start_date')}" />`, __('Set this only when the work should begin on a specific date.', 'coordina'), false),
			wrapField(__('Due date', 'coordina'), `<input type="datetime-local" name="due_date" value="${dateValue('due_date')}" />`, __('Use the target finish date for planning, Calendar, and Workload views.', 'coordina'), false),
			wrapField(__('Actual finish date', 'coordina'), `<input type="datetime-local" name="actual_finish_date" value="${dateValue('actual_finish_date')}" />`, __('Record when the task actually finished so planned and real dates stay visible.', 'coordina'), false),
		]
	)}${section(
		__('Blockers', 'coordina'),
		__('Record whether progress is stopped and explain what is in the way.', 'coordina'),
		[
			wrapToggle(`<label class="coordina-checkbox"><input type="checkbox" name="blocked" value="1" ${isCheckedValue(task.blocked) ? 'checked' : ''} /><span>${escapeHtml(__('Blocked', 'coordina'))}</span></label>`, __('Mark this when someone needs to clear an issue before work can continue.', 'coordina'), false),
			wrapField(__('Blocked reason', 'coordina'), `<input type="text" name="blocked_reason" value="${textValue('blocked_reason')}" />`, __('Explain the dependency, decision, or issue that is preventing progress.', 'coordina'), true),
		]
	)}<div class="coordina-form-actions"><button class="button button-primary" type="submit">${escapeHtml(__('Save task', 'coordina'))}</button><button class="button" type="button" data-action="close-modal">${escapeHtml(__('Cancel', 'coordina'))}</button></div></form>`;
}

function projectDetailForm(values) {
	const project = values || {};
	const users = state.shell.users || [];
	const statuses = state.shell.statuses.projects || [];
	const priorities = state.shell.priorities || [];
	const health = state.shell.health || [];
	const wrapField = (label, control, hint, wide) => `<label class="${wide ? 'coordina-form-grid__wide' : ''}"><span>${escapeHtml(label)}</span>${control}${fieldHint(hint)}</label>`;
	const section = (title, description, fields) => `<section class="coordina-form-section coordina-task-edit-section"><div class="coordina-section-header"><div><h4>${escapeHtml(title)}</h4><p class="coordina-section-note">${escapeHtml(description)}</p></div></div><div class="coordina-form-grid">${fields.filter(Boolean).join('')}</div></section>`;
	const textValue = (name) => escapeHtml(project && typeof project[name] !== 'undefined' ? project[name] : '');
	const dateValue = (name) => escapeHtml(dateTimeInputValue(project && typeof project[name] !== 'undefined' ? project[name] : ''));
	const managerId = String(project.manager_user_id || '');
	const sponsorId = String(project.sponsor_user_id || '');
	const intro = `<section class="coordina-card coordina-card--notice"><p>${escapeHtml(__('Use this form for the core project record. Team membership, visibility, notifications, and workspace defaults stay in project Settings.', 'coordina'))}</p></section>`;

	return `<form class="coordina-form coordina-task-detail-form" data-action="save-form" data-module="projects" data-id="${project.id || ''}">${intro}${section(
		__('Summary', 'coordina'),
		__('Start with the project name, the outcome it should deliver, and the basic internal reference if your team uses one.', 'coordina'),
		[
			wrapField(__('Project name', 'coordina'), `<input type="text" name="title" value="${textValue('title')}" required />`, __('Use the name people will recognize in workspaces, lists, and updates.', 'coordina'), false),
			wrapField(__('Project code', 'coordina'), `<input type="text" name="code" value="${textValue('code')}" />`, __('Optional. Use this only when your team already tracks projects by a short internal code.', 'coordina'), false),
			wrapField(__('Description', 'coordina'), `<textarea name="description">${textValue('description')}</textarea>`, __('Describe the goal, scope, or expected outcome so people understand what this project is trying to achieve.', 'coordina'), true),
		]
	)}${section(
		__('Leadership And Delivery', 'coordina'),
		__('Set the people responsible for steering the work and the current delivery signal the team should use.', 'coordina'),
		[
			wrapField(__('Status', 'coordina'), `<select name="status">${statuses.map((item) => `<option value="${item}" ${project.status === item ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select>`, __('Reflect the current delivery state, not the state you hope to reach next.', 'coordina'), false),
			wrapField(__('Health', 'coordina'), `<select name="health">${health.map((item) => `<option value="${item}" ${String(project.health || 'neutral') === String(item) ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select>`, __('Use health to show whether the project is on track, at risk, or blocked.', 'coordina'), false),
			wrapField(__('Priority', 'coordina'), `<select name="priority">${priorities.map((item) => `<option value="${item}" ${String(project.priority || 'normal') === String(item) ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select>`, __('Reserve higher priority for work that genuinely needs earlier attention.', 'coordina'), false),
			wrapField(__('Project manager', 'coordina'), `<select name="manager_user_id"><option value="">${escapeHtml(__('Unassigned', 'coordina'))}</option>${users.map((user) => `<option value="${user.id}" ${managerId === String(user.id) ? 'selected' : ''}>${escapeHtml(user.label)}</option>`).join('')}</select>`, __('Choose the person accountable for coordinating and steering delivery.', 'coordina'), false),
			wrapField(__('Project sponsor', 'coordina'), `<select name="sponsor_user_id"><option value="">${escapeHtml(__('Unassigned', 'coordina'))}</option>${users.map((user) => `<option value="${user.id}" ${sponsorId === String(user.id) ? 'selected' : ''}>${escapeHtml(user.label)}</option>`).join('')}</select>`, __('Choose the sponsor or executive owner who backs the project and should stay informed on major decisions.', 'coordina'), false),
		]
	)}${section(
		__('Schedule And Close-out', 'coordina'),
		__('Keep the key dates together and capture the final wrap-up details once the work finishes.', 'coordina'),
		[
			wrapField(__('Start date', 'coordina'), `<input type="datetime-local" name="start_date" value="${dateValue('start_date')}" />`, __('Use this when the project has a meaningful planned start date.', 'coordina'), false),
			wrapField(__('Target end date', 'coordina'), `<input type="datetime-local" name="target_end_date" value="${dateValue('target_end_date')}" />`, __('Use the expected finish date for planning, reporting, and portfolio review.', 'coordina'), false),
			wrapField(__('Actual end date', 'coordina'), `<input type="datetime-local" name="actual_end_date" value="${dateValue('actual_end_date')}" />`, __('Record when the project actually finished so planned and real timing stay visible.', 'coordina'), false),
			wrapField(__('Close-out notes', 'coordina'), `<textarea name="closeout_notes">${textValue('closeout_notes')}</textarea>`, __('Capture the outcome, handover details, or final notes once the project wraps up.', 'coordina'), true),
		]
	)}<div class="coordina-form-actions"><button class="button button-primary" type="submit">${escapeHtml(project.id ? __('Save project', 'coordina') : __('Create project', 'coordina'))}</button><button class="button" type="button" data-action="close-modal">${escapeHtml(__('Cancel', 'coordina'))}</button></div></form>`;
}

function milestoneReadOnly(values) {
	return `<dl class="coordina-key-value"><div><dt>${escapeHtml(__('Status', 'coordina'))}</dt><dd>${escapeHtml(nice(values.status || 'planned'))}</dd></div><div><dt>${escapeHtml(__('Owner', 'coordina'))}</dt><dd>${escapeHtml(values.owner_label || __('Unassigned', 'coordina'))}</dd></div><div><dt>${escapeHtml(__('Due date', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(values.due_date))}</dd></div><div><dt>${escapeHtml(__('Completion', 'coordina'))}</dt><dd>${Number(values.completion_percent || 0)}%</dd></div><div><dt>${escapeHtml(__('Dependency', 'coordina'))}</dt><dd>${escapeHtml(isCheckedValue(values.dependency_flag) ? __('Yes', 'coordina') : __('No', 'coordina'))}</dd></div></dl>${values.notes ? `<p>${escapeHtml(values.notes)}</p>` : ''}`;
}

function milestoneDetailForm(values) {
	const milestone = values || {};
	const users = state.shell.users || [];
	const projects = actionableProjects('milestones');
	const statuses = state.shell.statuses.milestones || [];
	const wrapField = (label, control, hint, wide) => `<label class="${wide ? 'coordina-form-grid__wide' : ''}"><span>${escapeHtml(label)}</span>${control}${fieldHint(hint)}</label>`;
	const wrapToggle = (control, hint, wide) => `<div class="coordina-task-toggle-field ${wide ? 'coordina-form-grid__wide' : ''}">${control}${fieldHint(hint)}</div>`;
	const textValue = (name) => escapeHtml(milestone && typeof milestone[name] !== 'undefined' ? milestone[name] : '');
	const dateValue = (name) => escapeHtml(dateTimeInputValue(milestone && typeof milestone[name] !== 'undefined' ? milestone[name] : ''));
	const selectedUser = String(milestone.owner_user_id || '');
	const selectedProject = String(milestone.project_id || '');
	const section = (title, description, fields) => `<section class="coordina-form-section coordina-task-edit-section"><div class="coordina-section-header"><div><h4>${escapeHtml(title)}</h4><p class="coordina-section-note">${escapeHtml(description)}</p></div></div><div class="coordina-form-grid">${fields.join('')}</div></section>`;

	return `<form class="coordina-form coordina-task-detail-form" data-action="save-form" data-module="milestones" data-id="${milestone.id || ''}">${section(
		__('Checkpoint', 'coordina'),
		__('Describe the milestone clearly so people understand what should be true when this checkpoint is reached.', 'coordina'),
		[
			wrapField(__('Title', 'coordina'), `<input type="text" name="title" value="${textValue('title')}" required />`, __('Use a short checkpoint name the team will recognize in planning conversations.', 'coordina'), false),
			wrapField(__('Project', 'coordina'), `<select name="project_id" required><option value="">${escapeHtml(__('Choose project', 'coordina'))}</option>${projects.map((project) => `<option value="${project.id}" ${selectedProject === String(project.id) ? 'selected' : ''}>${escapeHtml(project.label)}</option>`).join('')}</select>`, __('Milestones belong to a project workspace, so keep this linked to the correct project.', 'coordina'), false),
			wrapField(__('Notes', 'coordina'), `<textarea name="notes">${textValue('notes')}</textarea>`, __('Capture planning details, handoff notes, or success criteria that explain this checkpoint.', 'coordina'), true),
		]
	)}${section(
		__('Progress', 'coordina'),
		__('Set the current state, owner, and timing signal people should use for planning.', 'coordina'),
		[
			wrapField(__('Status', 'coordina'), `<select name="status">${statuses.map((item) => `<option value="${item}" ${milestone.status === item ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select>`, __('Reflect the real milestone state rather than a future intention.', 'coordina'), false),
			wrapField(__('Owner', 'coordina'), `<select name="owner_user_id"><option value="">${escapeHtml(__('Unassigned', 'coordina'))}</option>${users.map((user) => `<option value="${user.id}" ${selectedUser === String(user.id) ? 'selected' : ''}>${escapeHtml(user.label)}</option>`).join('')}</select>`, __('Choose the person responsible for coordinating or reaching this checkpoint.', 'coordina'), false),
			wrapField(__('Completion', 'coordina'), `<input type="number" name="completion_percent" value="${escapeHtml(milestone.completion_percent || 0)}" min="0" max="100" />`, __('Use this when progress is partially complete, not only when the milestone is finished.', 'coordina'), false),
			wrapField(__('Due date', 'coordina'), `<input type="datetime-local" name="due_date" value="${dateValue('due_date')}" />`, __('Use the date other work should align around in planning views.', 'coordina'), false),
		]
	)}${section(
		__('Dependencies', 'coordina'),
		__('Mark whether other work depends on this checkpoint so schedule risk is easier to spot.', 'coordina'),
		[
			wrapToggle(`<label class="coordina-checkbox"><input type="checkbox" name="dependency_flag" value="1" ${isCheckedValue(milestone.dependency_flag) ? 'checked' : ''} /><span>${escapeHtml(__('Dependency milestone', 'coordina'))}</span></label>`, __('Turn this on when reaching this milestone unlocks or protects other work.', 'coordina'), true),
		]
	)}<div class="coordina-form-actions"><button class="button button-primary" type="submit">${escapeHtml(__('Save milestone', 'coordina'))}</button><button class="button" type="button" data-action="close-modal">${escapeHtml(__('Cancel', 'coordina'))}</button></div></form>`;
}

function riskIssueReadOnly(values) {
	return `<section class="coordina-card coordina-card--notice"><p>${escapeHtml(__('You can review this risk or issue, but you do not have permission to edit its fields.', 'coordina'))}</p><dl class="coordina-key-value"><div><dt>${escapeHtml(__('Status', 'coordina'))}</dt><dd>${escapeHtml(nice(values.status || 'identified'))}</dd></div><div><dt>${escapeHtml(__('Type', 'coordina'))}</dt><dd>${escapeHtml(nice(values.object_type || 'risk'))}</dd></div><div><dt>${escapeHtml(__('Owner', 'coordina'))}</dt><dd>${escapeHtml(values.owner_label || __('Unassigned', 'coordina'))}</dd></div><div><dt>${escapeHtml(__('Target resolution', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(values.target_resolution_date))}</dd></div></dl></section>`;
}

function taskReadOnly(values) {
	const summary = values.checklist_summary || {};
	const checklist = Number(summary.total || 0) > 0 ? `${Number(summary.done || 0)} / ${Number(summary.total || 0)}` : __('None', 'coordina');
	return `<section class="coordina-card coordina-card--notice"><p>${escapeHtml(__('You can review this task here, but only project leads can edit the full task setup.', 'coordina'))}</p></section><dl class="coordina-key-value"><div><dt>${escapeHtml(__('Status', 'coordina'))}</dt><dd>${escapeHtml(nice(values.status || 'new'))}</dd></div><div><dt>${escapeHtml(__('Completion', 'coordina'))}</dt><dd>${escapeHtml(`${Number(values.completion_percent || 0)}%`)}</dd></div><div><dt>${escapeHtml(__('Actual finish date', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(values.actual_finish_date))}</dd></div><div><dt>${escapeHtml(__('Assignee', 'coordina'))}</dt><dd>${escapeHtml(values.assignee_label || __('Unassigned', 'coordina'))}</dd></div><div><dt>${escapeHtml(__('Project', 'coordina'))}</dt><dd>${escapeHtml(values.project_label || __('Standalone', 'coordina'))}</dd></div><div><dt>${escapeHtml(__('Due date', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(values.due_date))}</dd></div><div><dt>${escapeHtml(__('Checklist', 'coordina'))}</dt><dd>${escapeHtml(checklist)}</dd></div></dl>${values.description ? `<p>${escapeHtml(String(values.description).replace(/<[^>]*>/g, ' ').trim())}</p>` : ''}`;
}

function riskIssueDetailForm(values) {
	const riskIssue = values || {};
	const users = state.shell.users || [];
	const projects = actionableProjects('risks-issues');
	const statuses = state.shell.statuses['risksIssues'] || [];
	const severities = state.shell.severities || [];
	const impacts = state.shell.impacts || [];
	const likelihoods = state.shell.likelihoods || [];
	const wrapField = (label, control, hint, wide) => `<label class="${wide ? 'coordina-form-grid__wide' : ''}"><span>${escapeHtml(label)}</span>${control}${fieldHint(hint)}</label>`;
	const textValue = (name) => escapeHtml(riskIssue && typeof riskIssue[name] !== 'undefined' ? riskIssue[name] : '');
	const dateValue = (name) => escapeHtml(dateTimeInputValue(riskIssue && typeof riskIssue[name] !== 'undefined' ? riskIssue[name] : ''));
	const section = (title, description, fields) => `<section class="coordina-form-section coordina-task-edit-section"><div class="coordina-section-header"><div><h4>${escapeHtml(title)}</h4><p class="coordina-section-note">${escapeHtml(description)}</p></div></div><div class="coordina-form-grid">${fields.join('')}</div></section>`;

	return `<form class="coordina-form coordina-task-detail-form" data-action="save-form" data-module="risks-issues" data-id="${riskIssue.id || ''}">${section(
		__('Summary', 'coordina'),
		__('State the exception clearly so the team can understand what is happening before they decide how to respond.', 'coordina'),
		[
			wrapField(__('Title', 'coordina'), `<input type="text" name="title" value="${textValue('title')}" required />`, __('Use a short title that explains the exposure or blocker without extra wording.', 'coordina'), false),
			wrapField(__('Type', 'coordina'), `<select name="object_type"><option value="risk" ${String(riskIssue.object_type || 'risk') === 'risk' ? 'selected' : ''}>${escapeHtml(__('Risk', 'coordina'))}</option><option value="issue" ${String(riskIssue.object_type || '') === 'issue' ? 'selected' : ''}>${escapeHtml(__('Issue', 'coordina'))}</option></select>`, __('Choose risk for a possible future problem or issue for an active blocker already affecting the work.', 'coordina'), false),
			wrapField(__('Description', 'coordina'), `<textarea name="description">${textValue('description')}</textarea>`, __('Capture the situation, impact, and context people need before they act.', 'coordina'), true),
		]
	)}${section(
		__('Ownership And Timing', 'coordina'),
		__('Keep the record tied to the right context, owner, and handling state.', 'coordina'),
		[
			wrapField(__('Project', 'coordina'), `<select name="project_id"><option value="0">${escapeHtml(__('Standalone exception', 'coordina'))}</option>${projects.map((project) => `<option value="${project.id}" ${String(riskIssue.project_id || 0) === String(project.id) ? 'selected' : ''}>${escapeHtml(project.label)}</option>`).join('')}</select>`, __('Link this to a project when the team should manage it inside that workspace.', 'coordina'), false),
			wrapField(__('Owner', 'coordina'), `<select name="owner_user_id"><option value="">${escapeHtml(__('Unassigned', 'coordina'))}</option>${users.map((user) => `<option value="${user.id}" ${String(riskIssue.owner_user_id || '') === String(user.id) ? 'selected' : ''}>${escapeHtml(user.label)}</option>`).join('')}</select>`, __('Choose the person responsible for mitigation, follow-up, or resolution.', 'coordina'), false),
			wrapField(__('Status', 'coordina'), `<select name="status">${statuses.map((item) => `<option value="${item}" ${String(riskIssue.status || 'identified') === String(item) ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select>`, __('Set the current handling state so the next action is clear to everyone.', 'coordina'), false),
			wrapField(__('Target resolution date', 'coordina'), `<input type="datetime-local" name="target_resolution_date" value="${dateValue('target_resolution_date')}" />`, __('Use the date when this should be resolved or materially reduced.', 'coordina'), false),
		]
	)}${section(
		__('Exposure', 'coordina'),
		__('Describe how serious the item is and how likely it is to happen or persist.', 'coordina'),
		[
			wrapField(__('Severity', 'coordina'), `<select name="severity">${severities.map((item) => `<option value="${item}" ${String(riskIssue.severity || 'medium') === String(item) ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select>`, __('Use severity for the overall seriousness of the risk or issue.', 'coordina'), false),
			wrapField(__('Impact', 'coordina'), `<select name="impact">${impacts.map((item) => `<option value="${item}" ${String(riskIssue.impact || 'medium') === String(item) ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select>`, __('Describe how large the effect would be if this happens or continues.', 'coordina'), false),
			wrapField(__('Likelihood', 'coordina'), `<select name="likelihood">${likelihoods.map((item) => `<option value="${item}" ${String(riskIssue.likelihood || 'medium') === String(item) ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select>`, __('Estimate how likely the event is or how likely the blocker is to continue.', 'coordina'), false),
		]
	)}${section(
		__('Response Plan', 'coordina'),
		__('Record the mitigation plan, workaround, or concrete next actions.', 'coordina'),
		[
			wrapField(__('Mitigation plan', 'coordina'), `<textarea name="mitigation_plan">${textValue('mitigation_plan')}</textarea>`, __('Capture the actions, owners, or fallback approach the team should follow now.', 'coordina'), true),
		]
	)}<div class="coordina-form-actions"><button class="button button-primary" type="submit">${escapeHtml(__('Save risk or issue', 'coordina'))}</button><button class="button" type="button" data-action="close-modal">${escapeHtml(__('Cancel', 'coordina'))}</button></div></form>`;
}

function isAdvancedField(module, name) {
	const advancedByModule = {
		projects: ['code', 'start_date', 'target_end_date', 'actual_end_date', 'closeout_notes'],
		tasks: ['task_group_id', 'start_date', 'blocked', 'blocked_reason', 'approval_required'],
		requests: ['desired_due_date'],
		'risks-issues': ['impact', 'likelihood'],
		milestones: ['dependency_flag'],
	};
	return (advancedByModule[module.key] || []).includes(name);
}

function fieldHelpText(moduleKey, name) {
	const copy = {
		projects: {
			title: __('Use a clear project name people will recognize in lists and workspaces.', 'coordina'),
			code: __('Add an internal code only when your team already uses one consistently.', 'coordina'),
			description: __('Summarize the project goal and the outcome this work should deliver.', 'coordina'),
			status: __('Choose the current delivery state for this project.', 'coordina'),
			health: __('Reflect whether the project is on track, at risk, or needs attention.', 'coordina'),
			priority: __('Reserve higher priority for work that genuinely needs earlier attention.', 'coordina'),
			manager_user_id: __('Choose the person accountable for steering this project.', 'coordina'),
			sponsor_user_id: __('Choose the sponsor or executive owner who backs this project.', 'coordina'),
			start_date: __('Set the planned start only when the project should begin on a specific date.', 'coordina'),
			target_end_date: __('Use the expected finish date for planning and reporting.', 'coordina'),
			actual_end_date: __('Record when the project actually finished so planned and real dates stay visible.', 'coordina'),
			closeout_notes: __('Capture the close-out summary, outcome notes, or handover details once the work wraps up.', 'coordina'),
		},
		tasks: {
			title: __('Use a short action-oriented task title people can scan quickly.', 'coordina'),
			project_id: __('Link the task to a project when it should live inside a project workspace.', 'coordina'),
			task_group_id: __('Use a task group when the work belongs under a stage, phase, or bucket.', 'coordina'),
			description: __('Capture the outcome, supporting context, or handoff notes the assignee needs.', 'coordina'),
			status: __('Set the current workflow state rather than the state you hope to reach next.', 'coordina'),
			priority: __('Use priority sparingly so urgent work keeps its meaning.', 'coordina'),
			assignee_user_id: __('Choose the person responsible for moving the task forward.', 'coordina'),
			start_date: __('Set the planned start only when the work should begin on a specific date.', 'coordina'),
			due_date: __('Use the target finish date for planning and workload views.', 'coordina'),
			completion_percent: __('Update this to reflect partial progress, not just final completion.', 'coordina'),
			actual_finish_date: __('Record when the work actually finished so planned and real dates stay visible.', 'coordina'),
			blocked: __('Mark this when the task cannot move until someone clears an issue.', 'coordina'),
			blocked_reason: __('Explain what is stopping progress so the team can unblock it quickly.', 'coordina'),
			approval_required: __('Turn this on when the task should pause at completion until approval is recorded.', 'coordina'),
		},
		requests: {
			title: __('Name the request in a way that makes the need immediately clear.', 'coordina'),
			request_type: __('Use the closest request type so triage and routing stay consistent.', 'coordina'),
			business_reason: __('Explain why this request matters and what outcome is needed.', 'coordina'),
			status: __('Choose the current review state for this request.', 'coordina'),
			priority: __('Use priority to signal urgency, not general importance.', 'coordina'),
			triage_owner_user_id: __('Assign the person who should review and route this request.', 'coordina'),
			desired_due_date: __('Use this only when the requester has a real target date in mind.', 'coordina'),
		},
		'risks-issues': {
			title: __('State the risk or issue in a way the team can scan quickly.', 'coordina'),
			project_id: __('Link this to a project when it should be managed in a project workspace.', 'coordina'),
			object_type: __('Choose whether this record is a forward-looking risk or an active issue.', 'coordina'),
			description: __('Capture the situation, impact, and context the team needs to understand.', 'coordina'),
			status: __('Set the current handling state for this record.', 'coordina'),
			severity: __('Use severity to show the overall seriousness of this record.', 'coordina'),
			impact: __('Describe how large the effect would be if this happens or continues.', 'coordina'),
			likelihood: __('Estimate how likely this is to happen or persist.', 'coordina'),
			owner_user_id: __('Choose the person responsible for driving mitigation or resolution.', 'coordina'),
			mitigation_plan: __('Record the response plan, next actions, or workaround.', 'coordina'),
			target_resolution_date: __('Set the expected date to resolve or materially reduce this item.', 'coordina'),
		},
		milestones: {
			title: __('Name the checkpoint people should recognize in planning conversations.', 'coordina'),
			project_id: __('Milestones belong to a project workspace, so link this to the right project.', 'coordina'),
			status: __('Set the current state of this checkpoint.', 'coordina'),
			owner_user_id: __('Choose the person responsible for reaching or coordinating this milestone.', 'coordina'),
			due_date: __('Use the milestone date that planning should align around.', 'coordina'),
			completion_percent: __('Update this when the milestone is partially complete, not just at the end.', 'coordina'),
			dependency_flag: __('Turn this on when other work depends on reaching this checkpoint.', 'coordina'),
			notes: __('Add planning or coordination notes that help explain the checkpoint.', 'coordina'),
		},
	};

	return copy[moduleKey] && copy[moduleKey][name] ? copy[moduleKey][name] : '';
}

function formSectionCopy(module, values) {
	if (module.key === 'tasks') {
		return {
			title: __('Task details', 'coordina'),
			description: __('Set the task, owner, timing, and current status.', 'coordina'),
			advanced: __('Grouping, blockers, and approval details stay visible so the full task setup is easier to scan.', 'coordina'),
		};
	}
	if (module.key === 'projects') {
		return {
			title: values && values.id ? __('Project details', 'coordina') : __('New project', 'coordina'),
			description: __('Name the project and set its current state.', 'coordina'),
			advanced: __('Planning and internal reference details.', 'coordina'),
		};
	}
	if (module.key === 'requests') {
		return {
			title: __('Request details', 'coordina'),
			description: __('Capture the request, owner, priority, and status.', 'coordina'),
			advanced: __('Scheduling details.', 'coordina'),
		};
	}
	if (module.key === 'risks-issues') {
		return {
			title: __('Risk or issue details', 'coordina'),
			description: __('Capture the item, owner, severity, and resolution plan.', 'coordina'),
			advanced: __('Impact and likelihood details.', 'coordina'),
		};
	}
	if (module.key === 'milestones') {
		return {
			title: __('Milestone details', 'coordina'),
			description: __('Track the checkpoint, owner, due date, and progress.', 'coordina'),
			advanced: __('Dependency tracking details.', 'coordina'),
		};
	}
	return {
		title: __('Details', 'coordina'),
		description: __('Review the main details first, then update anything else as needed.', 'coordina'),
		advanced: __('Additional details.', 'coordina'),
	};
}

function formHtml(module, values) {
	if (module.key === 'projects') {
		return projectDetailForm(values || {});
	}
	if (module.key === 'tasks' && values && values.id && values.can_edit === false) {
		return taskReadOnly(values);
	}
	if (module.key === 'tasks') {
		return taskDetailForm(values || {});
	}
	if (module.key === 'risks-issues' && values && values.id && values.can_edit === false) {
		return riskIssueReadOnly(values);
	}
	if (module.key === 'risks-issues') {
		return riskIssueDetailForm(values || {});
	}
	if (module.key === 'milestones' && values && values.id && values.can_edit === false) {
		return milestoneReadOnly(values);
	}
	if (module.key === 'milestones') {
		return milestoneDetailForm(values || {});
	}
	if (module.key === 'approvals') {
		return approvalDecisionForm(values);
	}
	if (module.key === 'files') {
		return fileForm(values || {});
	}
	if (module.key === 'discussions') {
		return discussionForm(values || {});
	}
	const users = state.shell.users || [];
	const projects = actionableProjects(module.key);
	const statusOptions = state.shell.statuses[module.statuses] || [];
	const renderField = (name) => {
		const rawValue = values && typeof values[name] !== 'undefined' ? values[name] : '';
		const value = isDateKey(name) ? dateTimeInputValue(rawValue) : rawValue;
		const help = fieldHelpText(module.key, name);
		const withHint = (markup) => help ? markup.replace(/<\/label>$/, `${fieldHint(help)}</label>`) : markup;
		if (['description', 'business_reason', 'mitigation_plan', 'rejection_reason', 'notes', 'closeout_notes'].includes(name)) {
			return withHint(`<label><span>${escapeHtml(nice(name))}</span><textarea name="${name}">${escapeHtml(value)}</textarea></label>`);
		}
		if (name === 'checklist') {
			return checklistEditor(values || {});
		}
		if (name === 'status') {
			return withHint(`<label><span>${escapeHtml(__('Status', 'coordina'))}</span><select name="status">${statusOptions.map((item) => `<option value="${item}" ${value === item ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select></label>`);
		}
		if (name === 'priority') {
			return withHint(`<label><span>${escapeHtml(__('Priority', 'coordina'))}</span><select name="priority">${(state.shell.priorities || []).map((item) => `<option value="${item}" ${value === item ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select></label>`);
		}
		if (name === 'request_type') {
			return withHint(`<label><span>${escapeHtml(__('Request type', 'coordina'))}</span><select name="request_type">${(state.shell.requestTypes || ['general', 'project', 'task', 'support']).map((item) => `<option value="${item}" ${String(value || 'general') === String(item) ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select></label>`);
		}
		if (name === 'health') {
			return withHint(`<label><span>${escapeHtml(__('Health', 'coordina'))}</span><select name="health">${(state.shell.health || []).map((item) => `<option value="${item}" ${value === item ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select></label>`);
		}
		if (name === 'project_id') {
			const standaloneLabel = module.key === 'risks-issues' ? __('Standalone exception', 'coordina') : __('Standalone task', 'coordina');
			if (module.key === 'milestones') {
				return withHint(`<label><span>${escapeHtml(__('Project', 'coordina'))}</span><select name="project_id" required><option value="">${escapeHtml(__('Choose project', 'coordina'))}</option>${projects.map((project) => `<option value="${project.id}" ${String(value || 0) === String(project.id) ? 'selected' : ''}>${escapeHtml(project.label)}</option>`).join('')}</select></label>`);
			}
			return withHint(`<label><span>${escapeHtml(__('Project', 'coordina'))}</span><select name="project_id"><option value="0">${escapeHtml(standaloneLabel)}</option>${projects.map((project) => `<option value="${project.id}" ${String(value || 0) === String(project.id) ? 'selected' : ''}>${escapeHtml(project.label)}</option>`).join('')}</select></label>`);
		}
		if (name === 'task_group_id') {
			const groups = state.workspace && state.workspace.taskGroups
				? state.workspace.taskGroups
				: (state.taskDetail && Array.isArray(state.taskDetail.taskGroups) ? state.taskDetail.taskGroups : []);
			const label = nice((state.workspace && state.workspace.taskGroupLabel) || (state.taskDetail && state.taskDetail.taskGroupLabel) || state.shell.taskGroupLabel || 'stage');
			return withHint(`<label><span>${escapeHtml(label)}</span><select name="task_group_id"><option value="0">${escapeHtml(__('Ungrouped', 'coordina'))}</option>${groups.map((group) => `<option value="${group.id}" ${String(value || 0) === String(group.id) ? 'selected' : ''}>${escapeHtml(group.title)}</option>`).join('')}</select></label>`);
		}
		if (name === 'object_type') {
			const source = module.key === 'approvals' ? (state.shell.approvalObjectTypes || []) : (state.shell.objectTypes || []);
			const fallback = module.key === 'approvals' ? 'task' : 'risk';
			return withHint(`<label><span>${escapeHtml(__('Type', 'coordina'))}</span><select name="object_type">${source.map((item) => `<option value="${item}" ${String(value || fallback) === String(item) ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select></label>`);
		}
		if (name === 'severity') {
			return withHint(`<label><span>${escapeHtml(__('Severity', 'coordina'))}</span><select name="severity">${(state.shell.severities || []).map((item) => `<option value="${item}" ${String(value || 'medium') === String(item) ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select></label>`);
		}
		if (name === 'impact') {
			return withHint(`<label><span>${escapeHtml(__('Impact', 'coordina'))}</span><select name="impact">${(state.shell.impacts || []).map((item) => `<option value="${item}" ${String(value || 'medium') === String(item) ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select></label>`);
		}
		if (name === 'likelihood') {
			return withHint(`<label><span>${escapeHtml(__('Likelihood', 'coordina'))}</span><select name="likelihood">${(state.shell.likelihoods || []).map((item) => `<option value="${item}" ${String(value || 'medium') === String(item) ? 'selected' : ''}>${escapeHtml(nice(item))}</option>`).join('')}</select></label>`);
		}
		if (['manager_user_id', 'sponsor_user_id', 'assignee_user_id', 'triage_owner_user_id', 'owner_user_id', 'approver_user_id'].includes(name)) {
			return withHint(`<label><span>${escapeHtml(nice(name.replace(/_user_id/, '')))}</span><select name="${name}"><option value="">${escapeHtml(__('Unassigned', 'coordina'))}</option>${users.map((user) => `<option value="${user.id}" ${String(value) === String(user.id) ? 'selected' : ''}>${escapeHtml(user.label)}</option>`).join('')}</select></label>`);
		}
		if (['blocked', 'approval_required', 'dependency_flag'].includes(name)) {
			return withHint(`<label class="coordina-checkbox"><input type="checkbox" name="${name}" value="1" ${isCheckedValue(value) ? 'checked' : ''} /><span>${escapeHtml(nice(name))}</span></label>`);
		}
		if (name === 'completion_percent') {
			return withHint(`<label><span>${escapeHtml(__('Completion', 'coordina'))}</span><input type="number" name="completion_percent" value="${escapeHtml(value || 0)}" min="0" max="100" /></label>`);
		}
		if (name === 'object_id') {
			return withHint(`<label><span>${escapeHtml(__('Object id', 'coordina'))}</span><input type="number" name="object_id" value="${escapeHtml(value)}" min="1" required /></label>`);
		}
		const type = isDateKey(name) ? 'datetime-local' : 'text';
		const required = name === 'title' ? 'required' : '';
		return withHint(`<label><span>${escapeHtml(nice(name))}</span><input type="${type}" name="${name}" value="${escapeHtml(value)}" ${required} /></label>`);
	};
	const copy = formSectionCopy(module, values || {});
	const primaryFields = module.fields.filter((name) => !isAdvancedField(module, name)).map(renderField).join('');
	const advancedFields = module.fields.filter((name) => isAdvancedField(module, name)).map(renderField).join('');
	return `<form class="coordina-form" data-action="save-form" data-module="${module.key}" data-id="${values && values.id ? values.id : ''}"><section class="coordina-form-section"><div class="coordina-section-header"><div><h4>${escapeHtml(copy.title)}</h4><p class="coordina-section-note">${escapeHtml(copy.description)}</p></div></div><div class="coordina-form-grid">${primaryFields}</div></section>${advancedFields ? `<section class="coordina-form-section coordina-form-section--secondary"><div class="coordina-section-header"><div><h4>${escapeHtml(__('Additional details', 'coordina'))}</h4><p class="coordina-section-note">${escapeHtml(copy.advanced)}</p></div></div><div class="coordina-form-grid">${advancedFields}</div></section>` : ''}<div class="coordina-form-actions"><button class="button button-primary" type="submit">${escapeHtml(__('Save', 'coordina'))}</button><button class="button" type="button" data-action="close-modal">${escapeHtml(__('Cancel', 'coordina'))}</button></div></form>`;
}

function drawerHtml() {
	if (!state.drawer) { return ''; }
	return `<div class="coordina-overlay" data-action="close-drawer"></div><aside class="coordina-drawer" role="dialog" aria-modal="true"><header class="coordina-drawer__header"><div><h3>${escapeHtml(state.drawer.title)}</h3><p>${escapeHtml(state.drawer.subtitle)}</p></div><button class="button" data-action="close-drawer">${escapeHtml(__('Close', 'coordina'))}</button></header><div class="coordina-drawer__body">${state.drawer.body}</div></aside>`;
}

function modalHtml() {
	if (!state.modal) { return ''; }
	return `<div class="coordina-overlay" data-action="close-modal"></div><div class="coordina-modal" role="dialog" aria-modal="true"><header class="coordina-modal__header"><h3>${escapeHtml(state.modal.title)}</h3><button class="button" data-action="close-modal">${escapeHtml(__('Close', 'coordina'))}</button></header><div class="coordina-modal__body">${state.modal.body}</div></div>`;
}

function drawerSubtitle(module) {
	const subtitles = {
		tasks: __('Review the task and update it here.', 'coordina'),
		requests: __('Review the request before triage or conversion.', 'coordina'),
		approvals: __('Review the decision details and record the outcome.', 'coordina'),
		'risks-issues': __('Review the exposure, owner, and resolution plan.', 'coordina'),
		milestones: __('Review the checkpoint, due date, and progress.', 'coordina'),
		projects: __('Review the project and update the key details.', 'coordina'),
		files: __('Attach the file to the right record.', 'coordina'),
		discussions: __('Post an update on the right record.', 'coordina'),
	};
	return subtitles[module.key] || __('Review the details and update this record.', 'coordina');
}

function drawerSummary(module, item, sourceButton) {
	const badges = [];
	if (item.status) {
		badges.push(`<span class="coordina-status-badge status-${escapeHtml(item.status)}">${escapeHtml(nice(item.status))}</span>`);
	}
	if (module.key === 'risks-issues' && item.severity) {
		badges.push(`<span class="coordina-status-badge status-${escapeHtml(item.severity)}">${escapeHtml(nice(item.severity))}</span>`);
	}
	if (module.key === 'approvals' && item.object_type) {
		badges.push(`<span class="coordina-status-badge">${escapeHtml(nice(item.object_type))}</span>`);
	}
	if (item.project_label) {
		badges.push(`<span class="coordina-status-badge">${escapeHtml(item.project_label)}</span>`);
	}
	const rows = [];
	if (module.key === 'tasks') {
		rows.push(`<div><dt>${escapeHtml(__('Assignee', 'coordina'))}</dt><dd>${escapeHtml(item.assignee_label || __('Unassigned', 'coordina'))}</dd></div>`);
		rows.push(`<div><dt>${escapeHtml(__('Due date', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(item.due_date))}</dd></div>`);
	}
	if (module.key === 'requests') {
		rows.push(`<div><dt>${escapeHtml(__('Requester', 'coordina'))}</dt><dd>${escapeHtml(item.requester_label || __('Unknown', 'coordina'))}</dd></div>`);
		rows.push(`<div><dt>${escapeHtml(__('Triage owner', 'coordina'))}</dt><dd>${escapeHtml(item.triage_owner_label || __('Unassigned', 'coordina'))}</dd></div>`);
	}
	if (module.key === 'risks-issues') {
		rows.push(`<div><dt>${escapeHtml(__('Owner', 'coordina'))}</dt><dd>${escapeHtml(item.owner_label || __('Unassigned', 'coordina'))}</dd></div>`);
		rows.push(`<div><dt>${escapeHtml(__('Target resolution', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(item.target_resolution_date))}</dd></div>`);
	}
	if (module.key === 'milestones') {
		rows.push(`<div><dt>${escapeHtml(__('Owner', 'coordina'))}</dt><dd>${escapeHtml(item.owner_label || __('Unassigned', 'coordina'))}</dd></div>`);
		rows.push(`<div><dt>${escapeHtml(__('Due date', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(item.due_date))}</dd></div>`);
		rows.push(`<div><dt>${escapeHtml(__('Completion', 'coordina'))}</dt><dd>${Number(item.completion_percent || 0)}%</dd></div>`);
	}
	if (module.key === 'approvals') {
		rows.push(`<div><dt>${escapeHtml(__('Approver', 'coordina'))}</dt><dd>${escapeHtml(item.approver_label || __('Unassigned', 'coordina'))}</dd></div>`);
		rows.push(`<div><dt>${escapeHtml(__('Submitted', 'coordina'))}</dt><dd>${escapeHtml(dateLabel(item.submitted_at))}</dd></div>`);
	}
	const note = module.key === 'risks-issues'
		? (item.mitigation_plan || item.description || __('No mitigation plan recorded yet.', 'coordina'))
		: module.key === 'approvals'
			? (item.object_label || __('This approval is linked to a tracked item.', 'coordina'))
			: (item.description || item.business_reason || item.notes || '');
	return `<section class="coordina-drawer-summary"><div class="coordina-summary-row">${badges.join('')}</div>${note ? `<p>${escapeHtml(note)}</p>` : ''}${rows.length ? `<dl class="coordina-key-value">${rows.join('')}</dl>` : ''}${sourceButton ? `<div class="coordina-inline-actions">${sourceButton}</div>` : ''}</section>`;
}

function render() {
	const shellHeader = shellHeaderHtml();
	const shellClasses = ['coordina-shell'];
	const shellColorSource = state.shell && state.shell.themeColorSource ? String(state.shell.themeColorSource) : 'custom';
	const shellPrimary = state.shell && state.shell.themePrimaryColor ? String(state.shell.themePrimaryColor) : 'cobalt';
	const shellAccent = state.shell && state.shell.themeAccentColor ? String(state.shell.themeAccentColor) : 'amber';
	const shellMode = state.shell && state.shell.themeMode ? String(state.shell.themeMode) : 'auto';
	const inlineStyle = shellInlineStyle();
	shellClasses.push(`coordina-shell--source-${shellColorSource}`);
	shellClasses.push(`coordina-shell--primary-${shellPrimary}`);
	shellClasses.push(`coordina-shell--accent-${shellAccent}`);
	shellClasses.push(`coordina-shell--mode-${shellMode}`);
	if (state.page !== 'coordina-settings' && !pageDescriptionsEnabled()) {
		shellClasses.push('coordina-shell--hide-page-descriptions');
	}
	if (state.page !== 'coordina-settings' && !sectionDescriptionsEnabled()) {
		shellClasses.push('coordina-shell--hide-section-descriptions');
	}
	const body = state.loading
		? `<div class="coordina-loading">${escapeHtml(__('Loading Coordina shell...', 'coordina'))}</div>`
		: hasProjectWorkspace()
			? workspacePage()
			: hasTaskPage()
				? taskPage()
			: hasMilestonePage()
				? milestonePage()
			: hasRiskIssuePage()
				? riskIssuePage()
			: state.page === 'coordina-dashboard'
				? renderAppView('dashboardPage')
				: state.page === 'coordina-my-work'
					? renderAppView('myWorkPage')
					: state.page === 'coordina-files-discussion'
						? collaborationPage()
						: state.page === 'coordina-calendar'
							? renderAppView('calendarPage')
							: state.page === 'coordina-workload'
								? renderAppView('workloadPage')
								: state.page === 'coordina-settings'
									? renderAppView('settingsPage')
									: currentModule()
										? modulePage()
										: `<section class="coordina-card coordina-card--notice"><h2>${escapeHtml(__('Module shell ready', 'coordina'))}</h2><p>${escapeHtml(__('This screen keeps the shared patterns while deeper implementation is phased in.', 'coordina'))}</p></section>`;
	root.innerHTML = `<div class="${shellClasses.join(' ')}"${inlineStyle ? ` style="${inlineStyle}"` : ''}>${noticesHtml()}${shellHeader}${body}</div>${drawerHtml()}${modalHtml()}`;
}

function shellHeaderHtml() {
	const meta = currentPageHeaderMeta();
	const items = state.notifications && Array.isArray(state.notifications.items) ? state.notifications.items : [];
	const unreadCount = items.filter((item) => !item.is_read).length;
	return `<section class="coordina-shell__header coordina-shell__header--global"><div class="coordina-shell__contextbar"><div class="coordina-shell__header-copy">${breadcrumbHtml(meta.breadcrumbs)}<div class="coordina-shell__title-block"><h2 class="coordina-shell__title">${iconLabel(meta.iconType || 'dashboard', meta.title, 'coordina-shell__title-label')}</h2>${pageDescriptionsEnabled() && meta.description ? `<p class="coordina-shell__description">${escapeHtml(meta.description)}</p>` : ''}</div></div><div class="coordina-shell__header-meta">${renderHeaderActions(meta.actions)}<button class="coordina-shell-trigger coordina-shell-trigger--icon coordina-inbox-trigger ${unreadCount > 0 ? 'has-unread' : ''}" type="button" data-action="open-notifications" aria-label="${escapeHtml(__('Open inbox', 'coordina'))}">${shellIcon('inbox')}<span class="screen-reader-text">${escapeHtml(__('Open inbox', 'coordina'))}</span><span class="coordina-shell-trigger__badge coordina-inbox-trigger__count">${unreadCount}</span></button></div></div></section>`;
}

function currentPageHeaderMeta() {
	if (hasProjectWorkspace() && state.workspace && state.workspace.project) {
		const activeTab = state.workspace.activeTab || state.projectContext.tab || 'overview';
		const tabs = state.workspace.tabs || [];
		const actions = state.workspace && state.workspace.actions ? state.workspace.actions : {};
		const project = state.workspace.project;
		const breadcrumbs = [
			{ label: __('Projects', 'coordina'), route: { page: 'coordina-projects' } },
			{ label: project.title || __('Project workspace', 'coordina') },
		];
		if (activeTab && activeTab !== 'overview') {
			breadcrumbs.push({ label: workspaceTabLabel(activeTab, tabs) });
		}
		return {
			iconType: 'project',
			title: project.title || __('Project workspace', 'coordina'),
			description: project.description || __('Track progress, decisions, and delivery details in one project workspace.', 'coordina'),
			actions: workspaceHeaderActions(project, actions),
			breadcrumbs,
		};
	}

	if (hasTaskPage() && state.taskDetail && state.taskDetail.task) {
		const task = state.taskDetail.task;
		const breadcrumbs = Number(task.project_id || 0) > 0
			? [
				{ label: __('Projects', 'coordina'), route: { page: 'coordina-projects' } },
				{ label: task.project_label || __('Project workspace', 'coordina'), route: { page: 'coordina-projects', project_id: task.project_id, project_tab: 'work' } },
				{ label: task.title || __('Task', 'coordina') },
			]
			: [
				{ label: canAccessPage('coordina-tasks') ? __('Tasks', 'coordina') : __('My Work', 'coordina'), route: { page: canAccessPage('coordina-tasks') ? 'coordina-tasks' : 'coordina-my-work' } },
				{ label: task.title || __('Task', 'coordina') },
			];
		return {
			iconType: 'task',
			title: task.title || __('Task', 'coordina'),
			description: Number(task.project_id || 0) > 0
				? __('Review progress, updates, and files without leaving the parent project.', 'coordina')
				: __('Review progress, updates, and files for this standalone task.', 'coordina'),
			actions: taskHeaderActions(task),
			breadcrumbs,
		};
	}

	if (hasMilestonePage() && state.milestoneDetail && state.milestoneDetail.milestone) {
		const milestone = state.milestoneDetail.milestone;
		const breadcrumbs = Number(milestone.project_id || 0) > 0
			? [
				{ label: __('Projects', 'coordina'), route: { page: 'coordina-projects' } },
				{ label: milestone.project_label || __('Project workspace', 'coordina'), route: { page: 'coordina-projects', project_id: milestone.project_id, project_tab: 'milestones' } },
				{ label: milestone.title || __('Milestone', 'coordina') },
			]
			: [
				{ label: __('Milestones', 'coordina'), route: { page: 'coordina-projects' } },
				{ label: milestone.title || __('Milestone', 'coordina') },
			];
		return {
			iconType: 'milestone',
			title: milestone.title || __('Milestone', 'coordina'),
			description: __('Review the checkpoint, owner, due signal, and related updates in one place.', 'coordina'),
			actions: milestoneHeaderActions(milestone),
			breadcrumbs,
		};
	}

	if (hasRiskIssuePage() && state.riskIssueDetail && state.riskIssueDetail.riskIssue) {
		const riskIssue = state.riskIssueDetail.riskIssue;
		const breadcrumbs = Number(riskIssue.project_id || 0) > 0
			? [
				{ label: __('Projects', 'coordina'), route: { page: 'coordina-projects' } },
				{ label: riskIssue.project_label || __('Project workspace', 'coordina'), route: { page: 'coordina-projects', project_id: riskIssue.project_id, project_tab: 'risks-issues' } },
				{ label: riskIssue.title || __('Risk or issue', 'coordina') },
			]
			: [
				{ label: canAccessPage('coordina-risks-issues') ? __('Risks & Issues', 'coordina') : __('My Work', 'coordina'), route: { page: canAccessPage('coordina-risks-issues') ? 'coordina-risks-issues' : 'coordina-my-work' } },
				{ label: riskIssue.title || __('Risk or issue', 'coordina') },
			];
		return {
			iconType: riskIssue.object_type === 'issue' ? 'issue' : 'risk',
			title: riskIssue.title || __('Risk or issue', 'coordina'),
			description: __('Review the exposure, owner, response plan, and supporting context here.', 'coordina'),
			actions: riskIssueHeaderActions(riskIssue),
			breadcrumbs,
		};
	}

	const module = currentModule();
	if (module) {
		const actions = moduleHeaderActions(module);
		return {
			iconType: moduleIconType(module.key),
			title: module.title,
			description: pageDescriptionFor(state.page),
			actions,
			breadcrumbs: [
				{ label: __('Coordina', 'coordina'), route: { page: canAccessPage('coordina-dashboard') ? 'coordina-dashboard' : 'coordina-my-work' } },
				{ label: module.title },
			],
		};
	}

	const pageMeta = getPageMeta(state.page) || {};
	const title = pageTitleFor(state.page);
	let actions = [];
	if (state.page === 'coordina-my-work') {
		actions = [
			canAccessPage('coordina-calendar') ? routeAction(__('Calendar', 'coordina'), { page: 'coordina-calendar' }) : null,
			canAccessPage('coordina-files-discussion') ? routeAction(__('Files & discussions', 'coordina'), { page: 'coordina-files-discussion' }) : null,
			buttonAction(__('Quick task', 'coordina'), { action: 'open-task-create' }, 'primary'),
		].filter(Boolean);
	}
	if (state.page === 'coordina-dashboard') {
		actions = [
			canAccessPage('coordina-projects') ? routeAction(__('View projects', 'coordina'), { page: 'coordina-projects' }) : null,
			routeAction(__('Go to My Work', 'coordina'), { page: 'coordina-my-work' }, 'primary'),
		].filter(Boolean);
	}
	if (state.page === 'coordina-calendar') {
		actions = [
			canAccessPage('coordina-projects') ? routeAction(__('Projects', 'coordina'), { page: 'coordina-projects' }) : null,
			canAccessPage('coordina-tasks') ? routeAction(__('Open task list', 'coordina'), { page: 'coordina-tasks' }, 'primary') : null,
		].filter(Boolean);
	}
	if (state.page === 'coordina-workload') {
		actions = [
			canAccessPage('coordina-calendar') ? routeAction(__('Calendar', 'coordina'), { page: 'coordina-calendar' }) : null,
			canAccessPage('coordina-projects') ? routeAction(__('Managed projects', 'coordina'), { page: 'coordina-projects' }, 'primary') : null,
		].filter(Boolean);
	}
	if (state.page === 'coordina-files-discussion') {
		actions = [
			routeAction(__('Go to My Work', 'coordina'), { page: 'coordina-my-work' }),
			routeAction(__('Open projects', 'coordina'), { page: 'coordina-projects' }, 'primary'),
		];
	}
	if (state.page === 'coordina-settings') {
		actions = [
			routeAction(__('Project workspaces', 'coordina'), { page: 'coordina-projects' }),
			buttonAction(__('Save section', 'coordina'), { action: 'submit-settings' }, 'primary'),
		];
	}
	return {
		iconType: pageIconType(state.page),
		title: title,
		description: pageMeta.description || __('Stay on top of work, approvals, and updates from one shell.', 'coordina'),
		actions,
		breadcrumbs: [
			{ label: __('Coordina', 'coordina'), route: { page: canAccessPage('coordina-dashboard') ? 'coordina-dashboard' : 'coordina-my-work' } },
			{ label: title },
		],
	};
}

function inboxDrawerBody() {
	const prefs = state.notifications && state.notifications.preferences ? state.notifications.preferences : { digest: false, project_updates: true, approval_alerts: true };
	const items = state.notifications && Array.isArray(state.notifications.items) ? state.notifications.items : [];
	const unreadItems = items.filter((item) => !item.is_read);
	const visibleItems = state.notificationFilter === 'all' ? items : unreadItems;
	const unreadCount = unreadItems.length;
	const notificationListMarkup = typeof app.notificationList === 'function'
		? app.notificationList(visibleItems, false, { showOpenAction: true })
		: `<p class="coordina-empty-inline">${escapeHtml(__('Notifications are not available right now.', 'coordina'))}</p>`;
	return `<section class="coordina-inbox"><div class="coordina-drawer-summary coordina-inbox__summary"><div class="coordina-summary-row"><span class="coordina-status-badge ${unreadCount > 0 ? 'status-under-review' : ''}">${escapeHtml(unreadCount ? sprintfUnread(unreadCount) : __('All caught up', 'coordina'))}</span><span class="coordina-status-badge">${escapeHtml(`${items.length} ${__('recent', 'coordina')}`)}</span></div><p>${escapeHtml(__('Use the inbox for assignments and approvals that need action. Keep the Queue focused on execution.', 'coordina'))}</p><div class="coordina-inline-actions"><button class="coordina-tab ${state.notificationFilter === 'unread' ? 'is-active' : ''}" type="button" data-action="switch-notification-filter" data-filter="unread">${escapeHtml(__('Unread', 'coordina'))}</button><button class="coordina-tab ${state.notificationFilter === 'all' ? 'is-active' : ''}" type="button" data-action="switch-notification-filter" data-filter="all">${escapeHtml(__('All', 'coordina'))}</button>${unreadCount > 0 ? `<button class="button" type="button" data-action="mark-all-notifications-read">${escapeHtml(__('Mark all read', 'coordina'))}</button>` : ''}</div></div><div class="coordina-drawer-section">${notificationListMarkup}</div><div class="coordina-drawer-section"><div class="coordina-section-header"><div><h4>${escapeHtml(__('Notification preferences', 'coordina'))}</h4><p class="coordina-section-note">${escapeHtml(__('Keep the inbox high signal by limiting which updates create notices.', 'coordina'))}</p></div></div><form class="coordina-form" data-action="save-prefs"><label class="coordina-checkbox"><input type="checkbox" name="digest" value="1" ${prefs.digest ? 'checked' : ''} /><span>${escapeHtml(__('Digest emails scaffold', 'coordina'))}</span></label><label class="coordina-checkbox"><input type="checkbox" name="projectUpdates" value="1" ${prefs.project_updates ? 'checked' : ''} /><span>${escapeHtml(__('Project updates', 'coordina'))}</span></label><label class="coordina-checkbox"><input type="checkbox" name="approvalAlerts" value="1" ${prefs.approval_alerts ? 'checked' : ''} /><span>${escapeHtml(__('Approval alerts', 'coordina'))}</span></label><div class="coordina-form-actions"><button class="button button-primary" type="submit">${escapeHtml(__('Save preferences', 'coordina'))}</button></div></form></div></section>`;
}

function sprintfUnread(count) {
	return count === 1 ? __('1 unread item', 'coordina') : `${count} ${__('unread items', 'coordina')}`;
}

function backToProjects() {
	const url = new URL(window.location.href);
	url.searchParams.set('page', 'coordina-projects');
	url.searchParams.delete('project_id');
	url.searchParams.delete('project_tab');
	window.location.href = url.toString();
}

function openProjectWorkspace(id, tab) {
	const url = new URL(window.location.href);
	url.searchParams.set('page', 'coordina-projects');
	url.searchParams.set('project_id', id);
	url.searchParams.set('project_tab', tab || 'overview');
	url.searchParams.delete('task_id');
	window.location.href = url.toString();
}

function openTaskPage(id, route) {
	const taskId = Number(id || 0);
	if (taskId <= 0) {
		return;
	}

	const url = new URL(window.location.href);
	url.searchParams.set('page', 'coordina-task');
	url.searchParams.set('task_id', taskId);
	if (route && route.project_id) { url.searchParams.set('project_id', route.project_id); } else { url.searchParams.delete('project_id'); }
	if (route && route.project_tab) { url.searchParams.set('project_tab', route.project_tab); } else { url.searchParams.delete('project_tab'); }
	window.location.href = url.toString();
}

function openMilestonePage(id, route) {
	const milestoneId = Number(id || 0);
	if (milestoneId <= 0) {
		return;
	}

	const url = new URL(window.location.href);
	url.searchParams.set('page', 'coordina-milestone');
	url.searchParams.set('milestone_id', milestoneId);
	if (route && route.project_id) { url.searchParams.set('project_id', route.project_id); } else { url.searchParams.delete('project_id'); }
	if (route && route.project_tab) { url.searchParams.set('project_tab', route.project_tab); } else { url.searchParams.delete('project_tab'); }
	url.searchParams.delete('task_id');
	window.location.href = url.toString();
}

function openRiskIssuePage(id, route) {
	const riskIssueId = Number(id || 0);
	if (riskIssueId <= 0) {
		return;
	}

	const url = new URL(window.location.href);
	url.searchParams.set('page', 'coordina-risk-issue');
	url.searchParams.set('risk_issue_id', riskIssueId);
	if (route && route.project_id) { url.searchParams.set('project_id', route.project_id); } else { url.searchParams.delete('project_id'); }
	if (route && route.project_tab) { url.searchParams.set('project_tab', route.project_tab); } else { url.searchParams.delete('project_tab'); }
	url.searchParams.delete('task_id');
	window.location.href = url.toString();
}

function openRoute(route) {
	const url = new URL(window.location.href);
	const targetPage = route.page || 'coordina-dashboard';
	const projectId = Number(route.project_id || 0);
	const taskId = Number(route.task_id || 0);
	const milestoneId = Number(route.milestone_id || 0);
	const riskIssueId = Number(route.risk_issue_id || 0);
	const allowProjectWorkspace = targetPage === 'coordina-projects' && projectId > 0;
	const allowTaskPage = targetPage === 'coordina-task' && taskId > 0;
	const allowMilestonePage = targetPage === 'coordina-milestone' && milestoneId > 0;
	const allowRiskIssuePage = targetPage === 'coordina-risk-issue' && riskIssueId > 0;
	const fallbackPage = canAccessPage('coordina-dashboard') ? 'coordina-dashboard' : 'coordina-my-work';
	url.searchParams.set('page', (canAccessPage(targetPage) || allowProjectWorkspace || allowTaskPage || allowMilestonePage || allowRiskIssuePage) ? targetPage : fallbackPage);
	if (route.project_id) { url.searchParams.set('project_id', route.project_id); } else { url.searchParams.delete('project_id'); }
	if (route.project_tab) { url.searchParams.set('project_tab', route.project_tab); } else { url.searchParams.delete('project_tab'); }
	if (route.task_id) { url.searchParams.set('task_id', route.task_id); } else { url.searchParams.delete('task_id'); }
	if (route.milestone_id) { url.searchParams.set('milestone_id', route.milestone_id); } else { url.searchParams.delete('milestone_id'); }
	if (route.risk_issue_id) { url.searchParams.set('risk_issue_id', route.risk_issue_id); } else { url.searchParams.delete('risk_issue_id'); }
	window.location.href = url.toString();
}

async function openCreate(moduleKey, seedValues) {
	const module = modules[moduleKey ? `coordina-${moduleKey}` : state.page] || currentModule() || modules['coordina-tasks'];
	state.modal = { title: `${__('Create', 'coordina')} ${module.singular}`, body: formHtml(module, Object.assign({}, seedValues || {})) };
	render();
}

async function loadRecordCollaboration(seed) {
	if (!seed) {
		return [{ items: [] }, { items: [] }];
	}
	return Promise.all([
		api(`/files?${new URLSearchParams({ object_type: seed.object_type, object_id: seed.object_id, per_page: 8, order: 'desc' })}`),
		api(`/discussions?${new URLSearchParams({ object_type: seed.object_type, object_id: seed.object_id, per_page: 8, order: 'desc' })}`),
	]);
}

async function openRecord(moduleKey, id) {
	const module = modules[`coordina-${moduleKey}`] || currentModule() || modules['coordina-tasks'];
	if (module.key === 'milestones') {
		openMilestonePage(id);
		return;
	}
	if (module.key === 'risks-issues') {
		openRiskIssuePage(id);
		return;
	}
	const item = await api(`/${module.endpoint}/${id}`);
	const seed = contextSeed(module, item);
	const [files, discussions] = await loadRecordCollaboration(seed);
	const route = module.key === 'approvals' ? approvalSourceRoute(item) : null;
	const sourceButton = route ? `<button class="button button-small" data-action="open-route" data-page="${route.page || ''}" data-project-id="${route.project_id || ''}" data-project-tab="${route.project_tab || ''}" data-milestone-id="${route.milestone_id || ''}" data-risk-issue-id="${route.risk_issue_id || ''}">${escapeHtml(__('Open source item', 'coordina'))}</button>` : '';
	const summary = drawerSummary(module, item, sourceButton);
	state.drawer = { title: module.key === 'approvals' ? (item.object_label || __('Approval', 'coordina')) : item.title, subtitle: drawerSubtitle(module), body: `${summary}<div class="coordina-drawer-section">${formHtml(module, item)}</div>${module.key === 'requests' && item.can_convert ? `<div class="coordina-drawer-section"><button class="button" data-action="open-convert" data-id="${item.id}">${escapeHtml(__('Convert request', 'coordina'))}</button></div>` : ''}${contextSections(module, item, files, discussions)}` };
	render();
}

async function editProject(id) {
	const project = await api(`/projects/${id}`);
	state.modal = { title: __('Edit project', 'coordina'), body: formHtml(modules['coordina-projects'], project) };
	render();
}

function openNotifications() {
	state.drawer = { title: __('Inbox', 'coordina'), subtitle: __('Assignments, approvals, and other notices that need attention.', 'coordina'), body: inboxDrawerBody() };
	state.modal = null;
	render();
}

Object.assign(app, {
	entityIcon,
	iconLabel,
	approvalSourceRoute,
	approvalDecisionForm,
	contextSections,
	checklistForm,
	checklistItemForm,
	milestoneReadOnly,
	formHtml,
	drawerHtml,
	modalHtml,
	render,
	backToProjects,
	openProjectWorkspace,
	openTaskPage,
	openMilestonePage,
	openRiskIssuePage,
	openRoute,
	openCreate,
	openRecord,
	editProject,
	openNotifications,
});

window.CoordinaAdminApp = app;
}());
