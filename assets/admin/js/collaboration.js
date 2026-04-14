(function () {
const app = window.CoordinaAdminApp || {};

if (!app.root || !app.state) {
	return;
}

const { state, escapeHtml, __, nice, dateLabel } = app;
const baseWorkspaceTabBody = app.workspaceTabBody;

function collaborationProjectButton(item, tab) {
	if (!item || Number(item.project_id || 0) <= 0) {
		return `<span>${escapeHtml(__('Standalone', 'coordina'))}</span>`;
	}

	return `<button class="coordina-link-button" data-action="open-route" data-page="coordina-projects" data-project-id="${item.project_id || ''}" data-project-tab="${tab || 'overview'}">${escapeHtml(item.project_label || __('Project workspace', 'coordina'))}</button>`;
}

function collaborationContextButton(item, tab) {
	if (!item || !item.object_type) {
		return '';
	}
	if (item.object_type === 'project') {
		return `<button class="coordina-link-button" data-action="open-route" data-page="coordina-projects" data-project-id="${item.object_id || item.project_id || ''}" data-project-tab="${tab || 'overview'}">${escapeHtml(item.object_label || __('Project workspace', 'coordina'))}</button>`;
	}
	if (item.object_type === 'task') {
		return `<button class="coordina-link-button" data-action="open-task-page" data-id="${item.object_id || ''}" data-project-id="${item.project_id || ''}" data-project-tab="${item.project_id ? 'work' : ''}">${escapeHtml(item.object_label || __('Task', 'coordina'))}</button>`;
	}
	if (item.object_type === 'milestone') {
		return `<button class="coordina-link-button" data-action="open-milestone-page" data-id="${item.object_id || ''}" data-project-id="${item.project_id || ''}" data-project-tab="${item.project_id ? 'milestones' : ''}">${escapeHtml(item.object_label || __('Milestone', 'coordina'))}</button>`;
	}
	if (item.object_type === 'request') {
		return `<button class="coordina-link-button" data-action="open-record" data-module="requests" data-id="${item.object_id || ''}">${escapeHtml(item.object_label || __('Request', 'coordina'))}</button>`;
	}
	if (item.object_type === 'risk' || item.object_type === 'issue') {
		return `<button class="coordina-link-button" data-action="open-risk-issue-page" data-id="${item.object_id || ''}" data-project-id="${item.project_id || ''}" data-project-tab="${item.project_id ? 'risks-issues' : ''}">${escapeHtml(item.object_label || nice(item.object_type))}</button>`;
	}
	if (item.object_type === 'approval') {
		return `<button class="coordina-link-button" data-action="open-record" data-module="approvals" data-id="${item.object_id || ''}">${escapeHtml(item.object_label || __('Approval', 'coordina'))}</button>`;
	}
	return `<span>${escapeHtml(item.object_label || __('Linked work', 'coordina'))}</span>`;
}

function collaborationMeta(item, tab, authorLabel) {
	const context = collaborationContextButton(item, tab);
	const project = item && item.object_type === 'project' ? '' : collaborationProjectButton(item, tab);
	return `<div class="coordina-work-meta"><span class="coordina-status-badge">${escapeHtml(nice(item.object_type || 'context'))}</span>${context}${project}<span>${escapeHtml(authorLabel)}</span><span>${escapeHtml(dateLabel(item.created_at))}</span></div>`;
}

function fileList(items, emptyMessage) {
	return items.length ? `<ul class="coordina-work-list">${items.map((item) => `<li><a class="coordina-link-button" href="${escapeHtml(item.attachment_url || '#')}" target="_blank" rel="noopener noreferrer">${escapeHtml(item.file_name || item.attachment_title || __('File', 'coordina'))}</a>${collaborationMeta(item, 'files', item.created_by_label || __('Unknown uploader', 'coordina'))}${item.note ? `<p>${escapeHtml(item.note)}</p>` : ''}</li>`).join('')}</ul>` : `<p class="coordina-empty-inline">${escapeHtml(emptyMessage)}</p>`;
}

function discussionTimeline(items, emptyMessage) {
	return items.length ? `<ul class="coordina-timeline">${items.map((item) => `<li><strong>${escapeHtml(item.created_by_label || __('System', 'coordina'))}</strong><p>${escapeHtml(item.body || item.excerpt || '')}</p>${collaborationMeta(item, 'discussion', item.created_by_label || __('System', 'coordina'))}</li>`).join('')}</ul>` : `<p class="coordina-empty-inline">${escapeHtml(emptyMessage)}</p>`;
}

function collaborationActionButtons(seed, permissions) {
	const actions = permissions || (state.workspace && state.workspace.actions ? state.workspace.actions : {});
	const canPostUpdate = !!(actions && actions.canPostUpdate);
	const canAttachFile = !!(actions && actions.canAttachFile);
	const updateButton = canPostUpdate ? `<button class="button" data-action="open-discussion-create" data-object-type="${escapeHtml(seed.object_type || '')}" data-object-id="${escapeHtml(seed.object_id || '')}" data-object-label="${escapeHtml(seed.object_label || '')}" data-lock-context="1">${escapeHtml(__('Add update', 'coordina'))}</button>` : '';
	const fileButton = canAttachFile ? `<button class="button button-primary" data-action="open-file-create" data-object-type="${escapeHtml(seed.object_type || '')}" data-object-id="${escapeHtml(seed.object_id || '')}" data-object-label="${escapeHtml(seed.object_label || '')}" data-lock-context="1">${escapeHtml(__('Attach file', 'coordina'))}</button>` : '';
	const buttons = `${updateButton}${fileButton}`;

	return buttons ? `<div class="coordina-action-bar__actions">${buttons}</div>` : '';
}

function collaborationPage() {
	const filters = state.collaborationFilters || app.defaultCollaborationFilters();
	const shell = state.shell || {};
	const projectOptions = (shell.projects || []).map((project) => `<option value="${project.id}" ${String(filters.project_id) === String(project.id) ? 'selected' : ''}>${escapeHtml(project.label)}</option>`).join('');
	const objectOptions = (shell.contextObjectTypes || []).map((type) => `<option value="${type}" ${String(filters.object_type) === String(type) ? 'selected' : ''}>${escapeHtml(nice(type))}</option>`).join('');
	const recencyOptions = [
		{ value: '', label: __('Any time', 'coordina') },
		{ value: 'today', label: __('Today', 'coordina') },
		{ value: '7', label: __('Last 7 days', 'coordina') },
		{ value: '30', label: __('Last 30 days', 'coordina') },
	].map((item) => `<option value="${item.value}" ${String(filters.recency || '') === String(item.value) ? 'selected' : ''}>${escapeHtml(item.label)}</option>`).join('');
	const files = state.collaboration && state.collaboration.files ? state.collaboration.files : { items: [], total: 0 };
	const discussions = state.collaboration && state.collaboration.discussions ? state.collaboration.discussions : { items: [], total: 0 };
	return `<section class="coordina-page">${app.pageHeading ? app.pageHeading('coordina-files-discussion', `<button class="button" data-action="open-route" data-page="coordina-my-work">${escapeHtml(__('Go to My Work', 'coordina'))}</button><button class="button button-primary" data-action="open-route" data-page="coordina-projects">${escapeHtml(__('Open projects', 'coordina'))}</button>`, { title: __('Files & Discussions', 'coordina'), description: __('Use this to find recent files and updates, then return to the parent work item to act.', 'coordina') }) : `<div class="coordina-action-bar"><div><h2>${escapeHtml(__('Files & Discussions', 'coordina'))}</h2></div></div>`}<section class="coordina-card coordina-card--notice"><p>${escapeHtml(__('Use this screen to find recent files and updates. Add new files or updates from the project workspace or the parent drawer so the work context stays intact.', 'coordina'))}</p></section><div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-summary-chip"><strong>${Number(files.total || 0)}</strong>${escapeHtml(__('Files in view', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(discussions.total || 0)}</strong>${escapeHtml(__('Updates in view', 'coordina'))}</span></div><div class="coordina-filter-bar coordina-card coordina-filter-bar--tasks"><input type="search" name="collaboration-search" value="${escapeHtml(filters.search || '')}" placeholder="${escapeHtml(__('Search files or updates', 'coordina'))}" /><select name="collaboration-object-type"><option value="">${escapeHtml(__('All contexts', 'coordina'))}</option>${objectOptions}</select><select name="collaboration-project"><option value="">${escapeHtml(__('All projects', 'coordina'))}</option>${projectOptions}</select><select name="collaboration-recency">${recencyOptions}</select><button class="button" data-action="collaboration-apply">${escapeHtml(__('Apply', 'coordina'))}</button></div><div class="coordina-columns"><section class="coordina-card coordina-card--wide"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Files to review', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Open the file or its parent context when you need to act on it.', 'coordina'))}</p></div></div>${fileList(files.items || [], __('No files match these filters yet.', 'coordina'))}</section><section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Updates to review', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Read the latest updates, then jump back into the source work item.', 'coordina'))}</p></div></div>${discussionTimeline(discussions.items || [], __('No updates match these filters yet.', 'coordina'))}</section></div></section>`;
}

app.workspaceTabBody = function (tab, project, overview, taskSummary) {
	if (tab === 'files') {
		const files = state.workspace && state.workspace.fileCollection ? state.workspace.fileCollection.items || [] : [];
		return `<section class="coordina-card"><div class="coordina-section-header"><h3>${escapeHtml(__('Project files', 'coordina'))}</h3>${collaborationActionButtons({ object_type: 'project', object_id: project.id || '', object_label: project.title || __('Project workspace', 'coordina') })}</div>${fileList(files, __('No project files yet. Attach the first file to keep project context together.', 'coordina'))}</section>`;
	}
	if (tab === 'discussion') {
		const discussions = state.workspace && state.workspace.discussionCollection ? state.workspace.discussionCollection.items || [] : [];
		return `<section class="coordina-card"><div class="coordina-section-header"><h3>${escapeHtml(__('Project updates', 'coordina'))}</h3>${collaborationActionButtons({ object_type: 'project', object_id: project.id || '', object_label: project.title || __('Project workspace', 'coordina') })}</div>${discussionTimeline(discussions, __('No project updates yet. Add a quick update to keep the team aligned.', 'coordina'))}</section>`;
	}
	return baseWorkspaceTabBody(tab, project, overview, taskSummary);
};

Object.assign(app, {
	collaborationContextButton,
	collaborationProjectButton,
	collaborationMeta,
	fileList,
	discussionTimeline,
	collaborationActionButtons,
	collaborationPage,
});

window.CoordinaAdminApp = app;
}());
