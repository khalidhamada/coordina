(function () {
const app = window.CoordinaAdminApp || {};

if (!app.root || !app.state) {
	return;
}

function readSettingField(root, path, fallback) {
	const field = root.querySelector(`[data-setting-path="${path}"]`);
	return field ? field.value : fallback;
}

function slugifyThemeLabel(label) {
	return String(label || '')
		.toLowerCase()
		.replace(/[^a-z0-9]+/g, '-')
		.replace(/^-+|-+$/g, '')
		.slice(0, 48);
}

function readAppearanceTheme(root, state) {
	const appearance = (state.settings && state.settings.appearance) || {};
	return {
		color_source: readSettingField(root, 'appearance.color_source', appearance.color_source || 'custom'),
		primary_color: readSettingField(root, 'appearance.primary_color', appearance.primary_color || 'cobalt'),
		accent_color: readSettingField(root, 'appearance.accent_color', appearance.accent_color || 'amber'),
		theme_mode: readSettingField(root, 'appearance.theme_mode', appearance.theme_mode || 'auto'),
		primary_custom_color: readSettingField(root, 'appearance.primary_custom_color', appearance.primary_custom_color || '#2563eb'),
		accent_custom_color: readSettingField(root, 'appearance.accent_custom_color', appearance.accent_custom_color || '#f59e0b'),
	};
}

app.handleAdminClickAction = async function (button) {
	const { root, state, modules, currentModule, openCreate, openRecord, openProjectWorkspace, openTaskPage, openMilestonePage, openRiskIssuePage, openRoute, editProject, backToProjects, render, saveStoredFilters, todayKey, loadCalendar, loadWorkload, weekStartKey, loadCollection, saveFilters, api, notify, loadNotifications, openNotifications, loadMyWork, loadMyWorkTasks, defaultMyWorkTaskFilters, hasProjectWorkspace, loadWorkspace, hasTaskPage, loadTaskDetail, loadViews, loadCollaboration, __, escapeHtml, nice, checklistForm, checklistItemForm, refreshChecklistViews, deletePrompt, refreshAfterDelete, syncChecklistEditor, checklistEditorRowHtml, updateChecklistRemoveButtons } = app;

	if (button.dataset.action === 'open-create') { await openCreate(); }
	if (button.dataset.action === 'open-task-create') { await openCreate('tasks'); }
	if (button.dataset.action === 'open-project-task-create') { await openCreate('tasks', { project_id: state.projectContext.id, status: 'to-do' }); }
	if (button.dataset.action === 'open-task-group-create') {
		const label = nice((state.workspace && state.workspace.taskGroupLabel) || state.shell.taskGroupLabel || 'stage');
		state.modal = { title: `${__('Add', 'coordina')} ${label}`, body: `<form class="coordina-form" data-action="task-group-form" data-project-id="${state.projectContext.id || ''}"><label><span>${escapeHtml(label)} ${escapeHtml(__('name', 'coordina'))}</span><input type="text" name="title" required /></label><div class="coordina-form-actions"><button class="button button-primary" type="submit">${escapeHtml(__('Save', 'coordina'))}</button><button class="button" type="button" data-action="close-modal">${escapeHtml(__('Cancel', 'coordina'))}</button></div></form>` };
		render();
	}
	if (button.dataset.action === 'open-task-group-edit') {
		const label = nice((state.workspace && state.workspace.taskGroupLabel) || state.shell.taskGroupLabel || 'stage');
		state.modal = { title: `${__('Edit', 'coordina')} ${label}`, body: `<form class="coordina-form" data-action="task-group-form" data-project-id="${state.projectContext.id || ''}" data-id="${button.dataset.id || ''}"><label><span>${escapeHtml(label)} ${escapeHtml(__('name', 'coordina'))}</span><input type="text" name="title" value="${escapeHtml(button.dataset.title || '')}" required /></label><div class="coordina-form-actions"><button class="button button-primary" type="submit">${escapeHtml(__('Save', 'coordina'))}</button><button class="button" type="button" data-action="close-modal">${escapeHtml(__('Cancel', 'coordina'))}</button></div></form>` };
		render();
	}
	if (button.dataset.action === 'delete-task-group') {
		if (!confirm(button.dataset.title ? `${__('Delete', 'coordina')} "${button.dataset.title}"?` : __('Delete this task group?', 'coordina'))) {
			return;
		}
		await api(`/task-groups/${button.dataset.id}`, { method: 'DELETE' });
		await loadWorkspace();
		notify('success', __('Task group deleted.', 'coordina'));
		render();
	}
	if (button.dataset.action === 'open-project-milestone-create') { await openCreate('milestones', { project_id: state.projectContext.id, status: 'planned', completion_percent: 0 }); }
	if (button.dataset.action === 'open-project-risk-create') { await openCreate('risks-issues', { project_id: state.projectContext.id, object_type: button.dataset.type || 'risk', status: 'identified', severity: 'medium', impact: 'medium', likelihood: 'medium' }); }
	if (button.dataset.action === 'open-file-create') { await openCreate('files', { object_type: button.dataset.objectType || 'project', object_id: button.dataset.objectId || state.projectContext.id || '', object_label: button.dataset.objectLabel || '', lock_context: button.dataset.lockContext === '1' }); }
	if (button.dataset.action === 'open-discussion-create') { await openCreate('discussions', { object_type: button.dataset.objectType || 'project', object_id: button.dataset.objectId || state.projectContext.id || '', object_label: button.dataset.objectLabel || '', lock_context: button.dataset.lockContext === '1' }); }
	if (button.dataset.action === 'select-file') {
		const frame = window.wp && window.wp.media ? window.wp.media({ title: window.coordinaAdmin && window.coordinaAdmin.i18n ? window.coordinaAdmin.i18n.chooseFile : 'Choose file', multiple: false, library: { type: null } }) : null;
		if (frame) {
			frame.on('select', () => {
				const attachment = frame.state().get('selection').first().toJSON();
				const form = button.closest('form');
				if (form) {
					const input = form.querySelector('[name="attachment_id"]');
					const label = form.querySelector('[data-role="selected-file-label"]');
					if (input) { input.value = attachment.id || ''; }
					if (label) { label.textContent = attachment.title || attachment.filename || 'Selected file'; }
				}
			});
			frame.open();
		}
	}
	if (button.dataset.action === 'open-record') {
		if (button.dataset.module === 'tasks') {
			openTaskPage(button.dataset.id, { project_id: button.dataset.projectId, project_tab: button.dataset.projectTab });
		} else if (button.dataset.module === 'milestones') {
			openMilestonePage(button.dataset.id, { project_id: button.dataset.projectId, project_tab: button.dataset.projectTab });
		} else if (button.dataset.module === 'risks-issues') {
			openRiskIssuePage(button.dataset.id, { project_id: button.dataset.projectId, project_tab: button.dataset.projectTab });
		} else {
			await openRecord(button.dataset.module, button.dataset.id);
		}
	}
	if (button.dataset.action === 'open-task-page') { openTaskPage(button.dataset.id, { project_id: button.dataset.projectId, project_tab: button.dataset.projectTab }); }
	if (button.dataset.action === 'open-milestone-page') { openMilestonePage(button.dataset.id, { project_id: button.dataset.projectId, project_tab: button.dataset.projectTab }); }
	if (button.dataset.action === 'open-risk-issue-page') { openRiskIssuePage(button.dataset.id, { project_id: button.dataset.projectId, project_tab: button.dataset.projectTab }); }
	if (button.dataset.action === 'open-project') { openProjectWorkspace(button.dataset.id, 'overview'); }
	if (button.dataset.action === 'open-route') { openRoute({ page: button.dataset.page, project_id: button.dataset.projectId, project_tab: button.dataset.projectTab, task_id: button.dataset.taskId, milestone_id: button.dataset.milestoneId, risk_issue_id: button.dataset.riskIssueId }); }
	if (button.dataset.action === 'edit-project') { await editProject(button.dataset.id); }
	if (button.dataset.action === 'open-project-details-edit') {
		const projectId = Number(button.dataset.id || state.projectContext.id || 0);
		state.projectDetailEditing = true;
		if (hasProjectWorkspace() && projectId > 0 && projectId === Number(state.projectContext.id || 0)) {
			state.projectContext.tab = 'details';
			await loadWorkspace();
			render();
		} else {
			openProjectWorkspace(projectId, 'details');
		}
	}
	if (button.dataset.action === 'back-to-projects') { backToProjects(); }
	if (button.dataset.action === 'toggle-project-edit') { state.projectDetailEditing = !state.projectDetailEditing; render(); }
	if (button.dataset.action === 'cancel-project-edit') { state.projectDetailEditing = false; render(); }
	if (button.dataset.action === 'toggle-task-edit') { state.taskDetailEditing = !state.taskDetailEditing; render(); }
	if (button.dataset.action === 'cancel-task-edit') { state.taskDetailEditing = false; render(); }
	if (button.dataset.action === 'toggle-milestone-edit') { state.milestoneDetailEditing = !state.milestoneDetailEditing; render(); }
	if (button.dataset.action === 'cancel-milestone-edit') { state.milestoneDetailEditing = false; render(); }
	if (button.dataset.action === 'toggle-risk-issue-edit') { state.riskIssueDetailEditing = !state.riskIssueDetailEditing; render(); }
	if (button.dataset.action === 'cancel-risk-issue-edit') { state.riskIssueDetailEditing = false; render(); }
	if (button.dataset.action === 'open-checklist-create') {
		state.modal = { title: __('Add checklist', 'coordina'), body: checklistForm({ object_type: button.dataset.objectType || 'task', object_id: button.dataset.objectId || '', object_label: button.dataset.objectLabel || __('Linked work', 'coordina') }) };
		render();
	}
	if (button.dataset.action === 'open-checklist-edit') {
		state.modal = { title: __('Edit checklist', 'coordina'), body: checklistForm({ id: button.dataset.id || '', object_type: button.dataset.objectType || 'task', object_id: button.dataset.objectId || '', object_label: button.dataset.objectLabel || __('Linked work', 'coordina'), title: button.dataset.title || '' }) };
		render();
	}
	if (button.dataset.action === 'move-checklist') {
		await api(`/checklists/${button.dataset.id}/move`, { method: 'POST', body: { direction: button.dataset.direction || 'up' } });
		await refreshChecklistViews(button.dataset.objectType || '');
		notify('success', __('Checklist order updated.', 'coordina'));
		render();
	}
	if (button.dataset.action === 'delete-checklist') {
		if (!confirm(button.dataset.label ? `${__('Delete', 'coordina')} "${button.dataset.label}"?` : __('Delete this checklist?', 'coordina'))) {
			return;
		}
		await api(`/checklists/${button.dataset.id}`, { method: 'DELETE' });
		state.modal = null;
		await refreshChecklistViews(button.dataset.objectType || '');
		notify('success', __('Checklist deleted.', 'coordina'));
		render();
	}
	if (button.dataset.action === 'open-checklist-item-create') {
		state.modal = { title: __('Add checklist item', 'coordina'), body: checklistItemForm({ checklist_id: button.dataset.checklistId || '', checklist_title: button.dataset.checklistTitle || __('Checklist', 'coordina'), object_type: button.dataset.objectType || 'task', object_id: button.dataset.objectId || '', object_label: button.dataset.objectLabel || __('Linked work', 'coordina') }) };
		render();
	}
	if (button.dataset.action === 'open-checklist-item-edit') {
		state.modal = { title: __('Edit checklist item', 'coordina'), body: checklistItemForm({ id: button.dataset.id || '', checklist_id: button.dataset.checklistId || '', checklist_title: button.dataset.checklistTitle || __('Checklist', 'coordina'), object_type: button.dataset.objectType || 'task', object_id: button.dataset.objectId || '', object_label: button.dataset.objectLabel || __('Linked work', 'coordina'), item_text: button.dataset.itemText || '', is_done: button.dataset.isDone === '1' }) };
		render();
	}
	if (button.dataset.action === 'move-checklist-item') {
		await api(`/checklist-items/${button.dataset.id}/move`, { method: 'POST', body: { direction: button.dataset.direction || 'up' } });
		await refreshChecklistViews(button.dataset.objectType || '');
		notify('success', __('Checklist order updated.', 'coordina'));
		render();
	}
	if (button.dataset.action === 'delete-checklist-item') {
		if (!confirm(button.dataset.label ? `${__('Delete', 'coordina')} "${button.dataset.label}"?` : __('Delete this checklist item?', 'coordina'))) {
			return;
		}
		await api(`/checklist-items/${button.dataset.id}`, { method: 'DELETE' });
		state.modal = null;
		await refreshChecklistViews(button.dataset.objectType || '');
		notify('success', __('Checklist item deleted.', 'coordina'));
		render();
	}
	if (button.dataset.action === 'add-checklist-item') {
		const form = button.closest('form');
		const editor = form ? form.querySelector('[data-role="checklist-editor"]') : null;
		if (editor) {
			editor.insertAdjacentHTML('beforeend', checklistEditorRowHtml(false));
			syncChecklistEditor(form);
			updateChecklistRemoveButtons(editor);
			const newField = editor.querySelector('[data-checklist-item]:last-child [data-checklist-item-text]');
			if (newField) {
				newField.focus();
			}
		}
	}
	if (button.dataset.action === 'remove-checklist-item') {
		const form = button.closest('form');
		const row = button.closest('[data-checklist-item]');
		const editor = form ? form.querySelector('[data-role="checklist-editor"]') : null;
		if (row && editor) {
			row.remove();
			if (!editor.querySelector('[data-checklist-item]')) {
				editor.insertAdjacentHTML('beforeend', checklistEditorRowHtml(true));
			}
			updateChecklistRemoveButtons(editor);
			syncChecklistEditor(form);
		}
	}
	if (button.dataset.action === 'switch-project-tab') { openProjectWorkspace(state.projectContext.id, button.dataset.tab); }
	if (button.dataset.action === 'switch-work-view') { state.workspaceView = button.dataset.view || 'list'; render(); }
	if (button.dataset.action === 'switch-my-work-view') {
		state.myWorkView = ['board', 'tasks'].includes(button.dataset.view) ? button.dataset.view : 'queue';
		saveStoredFilters('my-work-ui', { view: state.myWorkView });
		if (state.myWorkView === 'tasks' && !state.myWorkTasksCollection) {
			state.myWorkTasksFilters = state.myWorkTasksFilters || defaultMyWorkTaskFilters();
			await loadMyWorkTasks().catch(() => null);
		}
		render();
	}
	if (button.dataset.action === 'apply-my-work-task-filters') {
		state.myWorkTasksFilters = {
			search: root.querySelector('[name="my-work-task-search"]') ? root.querySelector('[name="my-work-task-search"]').value : '',
			status: root.querySelector('[name="my-work-task-status"]') ? root.querySelector('[name="my-work-task-status"]').value : '',
			project_mode: root.querySelector('[name="my-work-task-project-mode"]') ? root.querySelector('[name="my-work-task-project-mode"]').value : 'all',
			project_id: root.querySelector('[name="my-work-task-project"]') ? root.querySelector('[name="my-work-task-project"]').value : '',
			orderby: root.querySelector('[name="my-work-task-orderby"]') ? root.querySelector('[name="my-work-task-orderby"]').value : 'updated_at',
			order: root.querySelector('[name="my-work-task-order"]') ? root.querySelector('[name="my-work-task-order"]').value : 'desc',
			per_page: state.myWorkTasksFilters && state.myWorkTasksFilters.per_page ? state.myWorkTasksFilters.per_page : 12,
			page: 1,
		};
		saveStoredFilters('my-work-tasks', state.myWorkTasksFilters);
		await loadMyWorkTasks();
		render();
	}
	if (button.dataset.action === 'page-my-work-tasks') {
		state.myWorkTasksFilters = Object.assign({}, defaultMyWorkTaskFilters(), state.myWorkTasksFilters || {}, { page: Math.max(1, Number(button.dataset.page || 1)) });
		saveStoredFilters('my-work-tasks', state.myWorkTasksFilters);
		await loadMyWorkTasks();
		render();
	}
	if (button.dataset.action === 'close-modal') { state.modal = null; render(); }
	if (button.dataset.action === 'close-drawer') { state.drawer = null; render(); }
	if (button.dataset.action === 'toggle-all') { state.selection = button.checked ? ((state.collection && state.collection.items ? state.collection.items : []).map((item) => item.id)) : []; render(); }
	if (button.dataset.action === 'toggle-selection') { const id = Number(button.value); state.selection = state.selection.includes(id) ? state.selection.filter((item) => item !== id) : state.selection.concat(id); render(); }
	if (button.dataset.action === 'calendar-shift') {
		state.calendarFilters.focus_date = shiftDate(state.calendarFilters.focus_date || todayKey(), state.calendarFilters.view === 'week' ? 'week' : 'month', Number(button.dataset.direction || 0));
		saveStoredFilters('calendar', state.calendarFilters);
		await loadCalendar();
		render();
	}
	if (button.dataset.action === 'calendar-today') {
		state.calendarFilters.focus_date = todayKey();
		saveStoredFilters('calendar', state.calendarFilters);
		await loadCalendar();
		render();
	}
	if (button.dataset.action === 'calendar-apply') {
		state.calendarFilters = { view: root.querySelector('[name="calendar-view"]').value, focus_date: root.querySelector('[name="calendar-focus-date"]').value || todayKey(), object_type: root.querySelector('[name="calendar-object-type"]').value, person_user_id: root.querySelector('[name="calendar-person"]').value, project_id: root.querySelector('[name="calendar-project"]').value };
		saveStoredFilters('calendar', state.calendarFilters);
		await loadCalendar();
		render();
	}
	if (button.dataset.action === 'workload-shift') {
		state.workloadFilters.week_start = weekStartKey(shiftDate(state.workloadFilters.week_start || weekStartKey(), 'week', Number(button.dataset.direction || 0)));
		saveStoredFilters('workload', state.workloadFilters);
		await loadWorkload();
		render();
	}
	if (button.dataset.action === 'workload-today') {
		state.workloadFilters.week_start = weekStartKey();
		saveStoredFilters('workload', state.workloadFilters);
		await loadWorkload();
		render();
	}
	if (button.dataset.action === 'workload-apply') {
		state.workloadFilters = { week_start: weekStartKey(root.querySelector('[name="workload-week-start"]').value || weekStartKey()), status: root.querySelector('[name="workload-status"]').value, priority: root.querySelector('[name="workload-priority"]').value, person_user_id: root.querySelector('[name="workload-person"]').value, project_id: root.querySelector('[name="workload-project"]').value };
		saveStoredFilters('workload', state.workloadFilters);
		await loadWorkload();
		render();
	}
	if (button.dataset.action === 'collaboration-apply') {
		state.collaborationFilters = { search: root.querySelector('[name="collaboration-search"]').value, object_type: root.querySelector('[name="collaboration-object-type"]').value, project_id: root.querySelector('[name="collaboration-project"]').value, recency: root.querySelector('[name="collaboration-recency"]').value, per_page: 10, order: 'desc' };
		saveStoredFilters('collaboration', state.collaborationFilters);
		await loadCollaboration();
		render();
	}
	if (button.dataset.action === 'apply-filters' && currentModule()) {
		state.filters.search = root.querySelector('[name="search"]') ? root.querySelector('[name="search"]').value : '';
		state.filters.status = root.querySelector('[name="status"]') ? root.querySelector('[name="status"]').value : '';
		if (root.querySelector('[name="project_mode"]')) { state.filters.project_mode = root.querySelector('[name="project_mode"]').value; }
		if (root.querySelector('[name="project_id"]')) { state.filters.project_id = root.querySelector('[name="project_id"]').value; }
		if (root.querySelector('[name="object_type"]')) { state.filters.object_type = root.querySelector('[name="object_type"]').value; }
		if (root.querySelector('[name="severity"]')) { state.filters.severity = root.querySelector('[name="severity"]').value; }
		if (root.querySelector('[name="owner_user_id"]')) { state.filters.owner_user_id = root.querySelector('[name="owner_user_id"]').value; }
		if (root.querySelector('[name="approver_user_id"]')) { state.filters.approver_user_id = root.querySelector('[name="approver_user_id"]').value; }
		state.filters.page = 1;
		saveFilters(currentModule().key);
		await loadCollection();
		render();
	}
	if (button.dataset.action === 'sort' && currentModule()) { state.filters.orderby = button.dataset.orderby; state.filters.order = state.filters.order === 'asc' ? 'desc' : 'asc'; saveFilters(currentModule().key); await loadCollection(); render(); }
	if (button.dataset.action === 'page' && currentModule()) { state.filters.page = Number(button.dataset.page); saveFilters(currentModule().key); await loadCollection(); render(); }
	if (button.dataset.action === 'bulk-status' && currentModule()) { await api(`/${currentModule().endpoint}/bulk`, { method: 'POST', body: { ids: state.selection, status: root.querySelector('[name="bulk-status"]').value } }); state.selection = []; await loadCollection(); notify('success', __('Bulk update applied.', 'coordina')); render(); }
	if (button.dataset.action === 'save-view' && currentModule()) { state.modal = { title: __('Save current view', 'coordina'), body: `<form class="coordina-form" data-action="save-view-form"><label><span>${escapeHtml(__('View name', 'coordina'))}</span><input type="text" name="view_name" required /></label><label class="coordina-checkbox"><input type="checkbox" name="is_default" value="1" /><span>${escapeHtml(__('Make default', 'coordina'))}</span></label><div class="coordina-form-actions"><button class="button button-primary" type="submit">${escapeHtml(__('Save view', 'coordina'))}</button></div></form>` }; render(); }
	if (button.dataset.action === 'open-convert') { state.modal = { title: __('Convert request', 'coordina'), body: `<form class="coordina-form" data-action="convert-form" data-id="${button.dataset.id}"><label><span>${escapeHtml(__('Convert to', 'coordina'))}</span><select name="targetType"><option value="project">${escapeHtml(__('Project', 'coordina'))}</option><option value="task">${escapeHtml(__('Task', 'coordina'))}</option></select></label><div class="coordina-form-actions"><button class="button button-primary" type="submit">${escapeHtml(__('Convert now', 'coordina'))}</button></div></form>` }; render(); }
	if (button.dataset.action === 'open-notifications') { if (!state.notifications) { await loadNotifications(); } openNotifications(); }
	if (button.dataset.action === 'switch-notification-filter') {
		state.notificationFilter = button.dataset.filter === 'all' ? 'all' : 'unread';
		saveStoredFilters('notifications-ui', { filter: state.notificationFilter });
		openNotifications();
	}
	if (button.dataset.action === 'save-appearance-theme') {
		const labelField = root.querySelector('[name="appearance-theme-label"]');
		const label = labelField ? labelField.value.trim() : '';
		if (!label) {
			notify('error', __('Enter a theme name before saving.', 'coordina'));
			return;
		}
		state.settings = state.settings || {};
		state.settings.appearance = state.settings.appearance || {};
		const savedThemes = Array.isArray(state.settings.appearance.saved_themes) ? state.settings.appearance.saved_themes.slice() : [];
		const appearanceTheme = readAppearanceTheme(root, state);
		const lowerLabel = label.toLowerCase();
		let themeKey = state.settingsThemeSelectedKey && savedThemes.some((theme) => theme.key === state.settingsThemeSelectedKey) ? state.settingsThemeSelectedKey : '';
		let existingIndex = savedThemes.findIndex((theme) => theme.key === themeKey || String(theme.label || '').toLowerCase() === lowerLabel);
		if (!themeKey) {
			themeKey = slugifyThemeLabel(label) || `theme-${savedThemes.length + 1}`;
			while (savedThemes.some((theme, index) => theme.key === themeKey && index !== existingIndex)) {
				themeKey = `${themeKey}-${savedThemes.length + 1}`;
			}
		}
		const nextTheme = Object.assign({ key: themeKey, label }, appearanceTheme);
		if (existingIndex >= 0) {
			savedThemes[existingIndex] = nextTheme;
		} else {
			savedThemes.unshift(nextTheme);
		}
		state.settings.appearance = Object.assign({}, state.settings.appearance, appearanceTheme, { saved_themes: savedThemes });
		state.settingsThemeSelectedKey = themeKey;
		state.settingsThemeDraftLabel = label;
		render();
		notify('success', __('Theme saved.', 'coordina'));
	}
	if (button.dataset.action === 'apply-appearance-theme') {
		const savedThemes = state.settings && state.settings.appearance && Array.isArray(state.settings.appearance.saved_themes) ? state.settings.appearance.saved_themes : [];
		const theme = savedThemes.find((item) => item.key === button.dataset.themeKey);
		if (!theme) {
			notify('error', __('Theme could not be found.', 'coordina'));
			return;
		}
		state.settings = state.settings || {};
		state.settings.appearance = Object.assign({}, state.settings.appearance || {}, {
			color_source: theme.color_source || 'custom',
			primary_color: theme.primary_color || 'cobalt',
			accent_color: theme.accent_color || 'amber',
			theme_mode: theme.theme_mode || 'auto',
			primary_custom_color: theme.primary_custom_color || '',
			accent_custom_color: theme.accent_custom_color || '',
			saved_themes: savedThemes,
		});
		state.settingsThemeSelectedKey = theme.key;
		state.settingsThemeDraftLabel = theme.label || '';
		render();
		notify('success', __('Theme applied to the form.', 'coordina'));
	}
	if (button.dataset.action === 'delete-appearance-theme') {
		const savedThemes = state.settings && state.settings.appearance && Array.isArray(state.settings.appearance.saved_themes) ? state.settings.appearance.saved_themes : [];
		const nextThemes = savedThemes.filter((theme) => theme.key !== button.dataset.themeKey);
		if (nextThemes.length === savedThemes.length) {
			notify('error', __('Theme could not be found.', 'coordina'));
			return;
		}
		state.settings = state.settings || {};
		state.settings.appearance = Object.assign({}, state.settings.appearance || {}, { saved_themes: nextThemes });
		if (state.settingsThemeSelectedKey === button.dataset.themeKey) {
			state.settingsThemeSelectedKey = '';
		}
		render();
		notify('success', __('Theme removed.', 'coordina'));
	}
	if (button.dataset.action === 'switch-settings-tab') {
		const labelField = root.querySelector('[name="appearance-theme-label"]');
		state.settingsThemeDraftLabel = labelField ? labelField.value : state.settingsThemeDraftLabel || '';
		state.settingsTab = button.dataset.tab || 'workspace';
		render();
	}
	if (button.dataset.action === 'change-activity-page') {
		const page = Math.max(1, Number(button.dataset.page || 1));
		const scope = String(button.dataset.scope || '');
		if (scope === 'project') { state.workspaceActivityPage = page; await app.loadWorkspace(); }
		if (scope === 'task') { state.taskActivityPage = page; await loadTaskDetail(); }
		if (scope === 'milestone') { state.milestoneActivityPage = page; await app.loadMilestoneDetail(); }
		if (scope === 'risk-issue') { state.riskIssueActivityPage = page; await app.loadRiskIssueDetail(); }
		if (scope === 'dashboard') { state.dashboardActivityPage = page; await app.loadDashboard(); }
		render();
	}
	if (button.dataset.action === 'submit-settings') { const form = root.querySelector('form[data-action="settings-form"]'); if (form) { form.requestSubmit(); } }
	if (button.dataset.action === 'toggle-notification') {
		await api(`/notifications/${button.dataset.id}`, { method: 'POST', body: { isRead: button.dataset.read === '1' } });
		await loadNotifications();
		if (state.page === 'coordina-my-work') { await loadMyWork(); }
		if (state.drawer && state.drawer.title === __('Inbox', 'coordina')) {
			openNotifications();
		} else {
			render();
		}
	}
	if (button.dataset.action === 'mark-all-notifications-read') { await api('/notifications/mark-all-read', { method: 'POST', body: {} }); await loadNotifications(); if (state.page === 'coordina-my-work') { await loadMyWork(); } openNotifications(); }
	if (button.dataset.action === 'open-notification-link') { await api(`/notifications/${button.dataset.id}`, { method: 'POST', body: { isRead: true } }); window.location.href = button.dataset.url || window.location.href; }
	if (button.dataset.action === 'quick-status') { const task = await api(`/tasks/${button.dataset.id}`); await api(`/tasks/${button.dataset.id}`, { method: 'POST', body: Object.assign({}, task, { status: button.dataset.status }) }); await loadMyWork(); if (state.page === 'coordina-my-work') { await loadMyWorkTasks().catch(() => null); } if (hasTaskPage() && Number(state.taskContext.id || 0) === Number(button.dataset.id || 0)) { await loadTaskDetail().catch(() => null); } if (state.page === 'coordina-calendar') { await loadCalendar().catch(() => null); } if (state.page === 'coordina-workload') { await loadWorkload().catch(() => null); } notify('success', __('Task updated.', 'coordina')); render(); }
	if (button.dataset.action === 'seed-demo-projects') {
		const type = button.dataset.type || 'all';
		button.disabled = true;
		button.textContent = __('Creating...', 'coordina');
		try {
			const result = await api('/demo-data/seed', { method: 'POST', body: { type } });
			notify('success', `${__('Demo projects created successfully!', 'coordina')} ${result.data.projects.length} ${__('project(s) added.', 'coordina')}`);
			await loadCollection();
			render();
		} finally {
			button.disabled = false;
			button.innerHTML = `<strong>${escapeHtml(__('Create All Projects', 'coordina'))}</strong><span>${escapeHtml(__('Website, Mobile, Support • 20+ tasks • 10+ risks', 'coordina'))}</span>`;
		}
	}
	if (button.dataset.action === 'clear-demo-projects') {
		if (confirm(__('Are you sure you want to delete all projects and related data? This cannot be undone.', 'coordina'))) {
			button.disabled = true;
			button.textContent = __('Clearing...', 'coordina');
			try {
				await api('/demo-data/clear', { method: 'POST', body: {} });
				notify('success', __('All projects cleared.', 'coordina'));
				await loadCollection();
				render();
			} finally {
				button.disabled = false;
				button.textContent = __('Clear All Projects', 'coordina');
			}
		}
	}
	if (button.dataset.action === 'delete-record') {
		if (!confirm(deletePrompt(button))) {
			return;
		}
		const module = modules[`coordina-${button.dataset.module}`];
		if (!module) {
			throw new Error(__('Delete target could not be resolved.', 'coordina'));
		}
		button.disabled = true;
		try {
			await api(`/${module.endpoint}/${button.dataset.id}`, { method: 'DELETE' });
			state.modal = null;
			state.drawer = null;
			await refreshAfterDelete(module.key, button);
			notify('success', __('Deleted successfully.', 'coordina'));
			render();
		} finally {
			button.disabled = false;
		}
	}
};
}());