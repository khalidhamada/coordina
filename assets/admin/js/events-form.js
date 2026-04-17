(function () {
const app = window.CoordinaAdminApp || {};

if (!app.root || !app.state) {
	return;
}

app.handleAdminChangeEvent = async function (target) {
	const { state, currentModule, api, notify, refreshChecklistViews, render, syncChecklistEditor, updateChecklistRemoveButtons, saveFilters, loadCollection, __ } = app;

	if (target && target.dataset.checklistToggle === '1') {
		try {
			await api(`/checklist-items/${target.dataset.id}/toggle`, { method: 'POST', body: { is_done: !!target.checked } });
			await refreshChecklistViews(target.dataset.objectType || '');
			render();
		} catch (error) {
			target.checked = !target.checked;
			notify('error', error.message);
		}
		return;
	}

	if (target && (target.matches('[data-checklist-item-done]') || target.matches('[data-checklist-item-text]'))) {
		const form = target.closest('form');
		if (form) {
			syncChecklistEditor(form);
			updateChecklistRemoveButtons(form.querySelector('[data-role="checklist-editor"]'));
		}
	}

	if (target && target.dataset.action === 'apply-view' && target.value && currentModule()) {
		try {
			const chosen = state.savedViews.find((item) => String(item.id) === String(target.value));
			if (chosen) {
				state.filters = Object.assign({}, state.filters, chosen.view_config || {});
				saveFilters(currentModule().key);
				await loadCollection();
				render();
			}
		} catch (error) {
			notify('error', error.message);
		}
	}
};

app.handleAdminInputEvent = function (target) {
	const { syncChecklistEditor, updateChecklistRemoveButtons } = app;

	if (target && target.matches('[data-checklist-item-text]')) {
		const form = target.closest('form');
		if (form) {
			syncChecklistEditor(form);
			updateChecklistRemoveButtons(form.querySelector('[data-role="checklist-editor"]'));
		}
	}
};

app.handleAdminSubmitEvent = async function (form) {
	const { state, modules, currentModule, api, notify, refreshChecklistViews, syncChecklistEditor, loadWorkspace, hasProjectWorkspace, hasTaskPage, hasMilestonePage, hasRiskIssuePage, loadTaskDetail, loadMilestoneDetail, loadRiskIssueDetail, loadCalendar, loadWorkload, loadCollaboration, loadCollection, loadMyWork, loadViews, openNotifications, loadNotifications, loadSettings, boot, render, __, collectSettingsPayload } = app;
	const values = Object.fromEntries(new window.FormData(form).entries());

	form.querySelectorAll('input[type="checkbox"]').forEach((input) => {
		values[input.name] = input.checked;
	});
	form.querySelectorAll('select[multiple]').forEach((select) => {
		values[select.name] = Array.from(select.selectedOptions).map((option) => option.value);
	});

	if (form.dataset.action === 'checklist-form') {
		const path = form.dataset.id ? `/checklists/${form.dataset.id}` : '/checklists';
		await api(path, { method: 'POST', body: values });
		state.modal = null;
		await refreshChecklistViews(values.object_type || '');
		notify('success', form.dataset.id ? __('Checklist updated.', 'coordina') : __('Checklist added.', 'coordina'));
		render();
	}
	if (form.dataset.action === 'save-form') {
		syncChecklistEditor(form);
		const module = modules[`coordina-${form.dataset.module}`] || currentModule() || modules['coordina-tasks'];
		const path = form.dataset.id ? `/${module.endpoint}/${form.dataset.id}` : `/${module.endpoint}`;
		await api(path, { method: 'POST', body: values });
		state.modal = null;
		state.drawer = null;
		if (hasProjectWorkspace()) { await loadWorkspace(); } else if (hasTaskPage()) { await loadTaskDetail(); } else if (hasMilestonePage()) { await loadMilestoneDetail(); } else if (hasRiskIssuePage()) { await loadRiskIssueDetail(); } else if (state.page === 'coordina-calendar') { await loadCalendar(); } else if (state.page === 'coordina-workload') { await loadWorkload(); } else if (state.page === 'coordina-files-discussion') { await loadCollaboration(); } else if (currentModule()) { await loadCollection(); }
		if (state.page === 'coordina-my-work' || module.key === 'tasks') { await loadMyWork().catch(() => null); }
		if (state.page === 'coordina-calendar' && module.key === 'projects') { await loadCalendar().catch(() => null); }
		if (state.page === 'coordina-workload' && module.key === 'tasks') { await loadWorkload().catch(() => null); }
		if (module.key === 'projects') { state.projectDetailEditing = false; }
		if (module.key === 'tasks') { state.taskDetailEditing = false; }
		if (module.key === 'milestones') { state.milestoneDetailEditing = false; }
		if (module.key === 'risks-issues') { state.riskIssueDetailEditing = false; }
		if ((module.key === 'files' || module.key === 'discussions') && !hasProjectWorkspace() && !hasTaskPage() && !hasMilestonePage() && !hasRiskIssuePage() && state.page !== 'coordina-files-discussion') { await boot(); }
		notify('success', __('Saved successfully.', 'coordina'));
		render();
	}
	if (form.dataset.action === 'checklist-item-form') {
		const path = form.dataset.id ? `/checklist-items/${form.dataset.id}` : '/checklist-items';
		await api(path, { method: 'POST', body: values });
		state.modal = null;
		await refreshChecklistViews(values.object_type || '');
		notify('success', form.dataset.id ? __('Checklist item updated.', 'coordina') : __('Checklist item added.', 'coordina'));
		render();
	}
	if (form.dataset.action === 'save-view-form' && currentModule()) { await api('/saved-views', { method: 'POST', body: { module: currentModule().key, view_name: values.view_name, is_default: !!values.is_default, view_config: state.filters } }); await loadViews(); state.modal = null; notify('success', __('View saved.', 'coordina')); render(); }
	if (form.dataset.action === 'convert-form') { await api(`/requests/${form.dataset.id}/convert`, { method: 'POST', body: values }); await loadCollection(); state.modal = null; notify('success', __('Request converted.', 'coordina')); render(); }
	if (form.dataset.action === 'save-prefs') { await api('/notification-preferences', { method: 'POST', body: values }); await loadNotifications(); state.modal = null; if (state.drawer && state.drawer.title === __('Inbox', 'coordina')) { openNotifications(); } notify('success', __('Notification preferences updated.', 'coordina')); render(); }
	if (form.dataset.action === 'project-settings-form') { await api(`/projects/${form.dataset.projectId}/settings`, { method: 'POST', body: values }); await loadWorkspace(); notify('success', __('Project settings updated.', 'coordina')); render(); }
	if (form.dataset.action === 'task-group-form') { await api(form.dataset.id ? `/task-groups/${form.dataset.id}` : `/projects/${form.dataset.projectId}/task-groups`, { method: 'POST', body: values }); await loadWorkspace(); state.modal = null; notify('success', form.dataset.id ? __('Task group updated.', 'coordina') : __('Task group added.', 'coordina')); render(); }
	if (form.dataset.action === 'settings-form') { await api('/settings', { method: 'POST', body: collectSettingsPayload(form) }); await loadSettings(); state.shell = await api('/admin-shell'); notify('success', __('Settings updated.', 'coordina')); render(); }
};
}());