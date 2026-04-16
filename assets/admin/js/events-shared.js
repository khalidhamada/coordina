(function () {
const app = window.CoordinaAdminApp || {};

if (!app.root || !app.state) {
	return;
}

const { state, modules, currentModule, openRoute, loadWorkspace, loadCollection, hasProjectWorkspace, hasTaskPage, hasMilestonePage, hasRiskIssuePage, loadTaskDetail, loadMilestoneDetail, loadRiskIssueDetail, loadMyWork, loadCalendar, loadWorkload, loadCollaboration, canAccessPage, __, escapeHtml } = app;

function assignPath(target, path, value) {
	const parts = String(path || '').split('.').filter(Boolean);
	let cursor = target;
	parts.forEach((part, index) => {
		if (index === parts.length - 1) {
			cursor[part] = value;
			return;
		}
		cursor[part] = cursor[part] || {};
		cursor = cursor[part];
	});
}

function collectSettingsPayload(form) {
	const payload = {};
	form.querySelectorAll('[data-setting-path]').forEach((field) => {
		let value = field.type === 'checkbox' ? field.checked : field.value;
		if (field.dataset.settingSerializer === 'json') {
			try {
				value = value ? JSON.parse(String(value)) : [];
			} catch (error) {
				value = [];
			}
		}
		if (field.tagName === 'TEXTAREA') {
			value = String(value || '').split(/[\r\n,]+/).map((item) => item.trim()).filter(Boolean);
		}
		if (field.type === 'number') {
			value = Number(value || 0);
		}
		assignPath(payload, field.dataset.settingPath, value);
	});
	return payload;
}

function deletePrompt(button) {
	const module = modules[`coordina-${button.dataset.module}`];
	const label = String(button.dataset.label || '').trim();

	if (button.dataset.module === 'projects') {
		return label
			? __('Delete this project and all related tasks, milestones, risks, files, and updates?', 'coordina')
			: __('Delete this project and all related work?', 'coordina');
	}

	if (label) {
		return `${__('Delete', 'coordina')} "${label}"?`;
	}

	return `${__('Delete this', 'coordina')} ${module ? module.singular : __('record', 'coordina')}?`;
}

async function refreshAfterDelete(moduleKey, button) {
	if (moduleKey === 'projects' && hasProjectWorkspace() && Number(state.projectContext.id || 0) === Number(button.dataset.id || 0)) {
		state.projectContext = { id: 0, tab: 'overview' };
		state.workspace = null;
		await loadCollection();
		return;
	}

	if (moduleKey === 'tasks' && hasTaskPage() && Number(state.taskContext.id || 0) === Number(button.dataset.id || 0)) {
		state.taskContext = { id: 0 };
		state.taskDetail = null;
		if (Number(button.dataset.projectId || 0) > 0) {
			openRoute({ page: 'coordina-projects', project_id: button.dataset.projectId, project_tab: 'work' });
			return;
		}
		if (canAccessPage('coordina-tasks')) {
			openRoute({ page: 'coordina-tasks' });
			return;
		}
		openRoute({ page: 'coordina-my-work' });
		return;
	}

	if (moduleKey === 'milestones' && hasMilestonePage() && Number(state.milestoneContext.id || 0) === Number(button.dataset.id || 0)) {
		state.milestoneContext = { id: 0 };
		state.milestoneDetail = null;
		if (Number(button.dataset.projectId || 0) > 0) {
			openRoute({ page: 'coordina-projects', project_id: button.dataset.projectId, project_tab: 'milestones' });
			return;
		}
		openRoute({ page: canAccessPage('coordina-milestones') ? 'coordina-milestones' : 'coordina-my-work' });
		return;
	}

	if (moduleKey === 'risks-issues' && hasRiskIssuePage() && Number(state.riskIssueContext.id || 0) === Number(button.dataset.id || 0)) {
		state.riskIssueContext = { id: 0 };
		state.riskIssueDetail = null;
		if (Number(button.dataset.projectId || 0) > 0) {
			openRoute({ page: 'coordina-projects', project_id: button.dataset.projectId, project_tab: 'risks-issues' });
			return;
		}
		openRoute({ page: canAccessPage('coordina-risks-issues') ? 'coordina-risks-issues' : 'coordina-my-work' });
		return;
	}

	if (hasProjectWorkspace()) { await loadWorkspace(); }
	if (state.page === 'coordina-files-discussion') { await loadCollaboration(); }
	if (currentModule()) { await loadCollection(); }
	if (moduleKey === 'tasks' || state.page === 'coordina-my-work') { await loadMyWork().catch(() => null); }
	if (state.page === 'coordina-calendar' && ['projects', 'tasks'].includes(moduleKey)) { await loadCalendar().catch(() => null); }
	if (state.page === 'coordina-workload' && moduleKey === 'tasks') { await loadWorkload().catch(() => null); }
}

async function refreshChecklistViews(objectType) {
	if (hasProjectWorkspace()) { await loadWorkspace(); }
	if (hasTaskPage()) { await loadTaskDetail().catch(() => null); }
	if (hasMilestonePage()) { await loadMilestoneDetail().catch(() => null); }
	if (hasRiskIssuePage()) { await loadRiskIssueDetail().catch(() => null); }
	if (currentModule()) { await loadCollection().catch(() => null); }
	if (state.page === 'coordina-my-work' || objectType === 'task') { await loadMyWork().catch(() => null); }
	if (state.page === 'coordina-calendar' && objectType === 'task') { await loadCalendar().catch(() => null); }
	if (state.page === 'coordina-workload' && objectType === 'task') { await loadWorkload().catch(() => null); }
}

function checklistEditorRowHtml(disabled) {
	return `<div class="coordina-checklist-editor__item" data-checklist-item><label class="coordina-checklist-editor__toggle"><input type="checkbox" data-checklist-item-done /><span>${escapeHtml(__('Done', 'coordina'))}</span></label><input type="text" data-checklist-item-text value="" placeholder="${escapeHtml(__('Checklist item', 'coordina'))}" /><button class="button button-small" type="button" data-action="remove-checklist-item" ${disabled ? 'disabled' : ''}>${escapeHtml(__('Remove', 'coordina'))}</button></div>`;
}

function updateChecklistRemoveButtons(editor) {
	if (!editor) {
		return;
	}
	Array.from(editor.querySelectorAll('[data-action="remove-checklist-item"]')).forEach((removeButton) => {
		const textField = removeButton.closest('[data-checklist-item]') && removeButton.closest('[data-checklist-item]').querySelector('[data-checklist-item-text]');
		const hasPeers = editor.querySelectorAll('[data-checklist-item]').length > 1;
		removeButton.disabled = !hasPeers && !String(textField && textField.value ? textField.value : '').trim();
	});
}

function syncChecklistEditor(form) {
	if (!form) {
		return;
	}
	const hidden = form.querySelector('[data-role="checklist-value"]');
	if (!hidden) {
		return;
	}
	const rows = Array.from(form.querySelectorAll('[data-checklist-item]'));
	const lines = rows.map((row) => {
		const textField = row.querySelector('[data-checklist-item-text]');
		const doneField = row.querySelector('[data-checklist-item-done]');
		const text = String(textField && textField.value ? textField.value : '').trim();
		if (!text) {
			return '';
		}
		return `${doneField && doneField.checked ? '[x]' : '[ ]'} ${text}`;
	}).filter(Boolean);
	hidden.value = lines.join('\n');
}

app.collectSettingsPayload = collectSettingsPayload;
app.deletePrompt = deletePrompt;
app.refreshAfterDelete = refreshAfterDelete;
app.refreshChecklistViews = refreshChecklistViews;
app.checklistEditorRowHtml = checklistEditorRowHtml;
app.updateChecklistRemoveButtons = updateChecklistRemoveButtons;
app.syncChecklistEditor = syncChecklistEditor;
}());