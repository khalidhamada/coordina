(function () {
const app = window.CoordinaAdminApp || {};

if (!app.root || !app.state) {
return;
}

const { state, escapeHtml, __, nice, pageHeading, optionList, settingsTextarea, settingsCheckbox, settingsHint } = app;

function settingsPageLegacy() {
	const settings = state.settings || {};
	const general = settings.general || {};
	const dropdowns = settings.dropdowns || {};
	const statuses = dropdowns.statuses || {};
	const access = settings.access || {};
	const workflows = settings.workflows || {};
	const notifications = settings.notifications || {};
	const portal = settings.portal || {};
	const data = settings.data || {};
	const automation = settings.automation || {};
	const landingOptions = optionList(['coordina-my-work', 'coordina-dashboard', 'coordina-projects'], general.default_landing_page || 'coordina-my-work');
	const dateOptions = optionList(['site', 'relative', 'absolute'], general.date_display || 'site');
	const tabOptions = optionList(['overview', 'work', 'milestones', 'risks-issues', 'approvals', 'files', 'discussion', 'activity'], general.workspace_default_tab || 'overview');
	const taskGroupLabelOptions = optionList(['stage', 'phase', 'bucket'], general.task_group_label || 'stage');
	const activityPageSizeValue = Number(general.activity_page_size || 10);
	const accessOptions = optionList(dropdowns.visibilityLevels || ['team', 'private', 'public'], access.project_access_default || 'team');
	const portalAccessOptions = optionList(['disabled', 'requesters', 'logged-in-users'], access.portal_access_default || 'requesters');
	const workspaceVisibilityOptions = optionList(['members-only', 'members-and-assignees', 'all-coordina-users'], access.project_workspace_visibility || 'members-and-assignees');
	const projectListVisibilityOptions = optionList(['assigned-projects-only', 'all-accessible-projects', 'all-projects'], access.project_list_visibility || 'all-accessible-projects');
	const navigationScopeOptions = optionList(['dashboard-my-work-only', 'dashboard-my-work-projects', 'dashboard-my-work-projects-tasks'], access.non_admin_navigation_scope || 'dashboard-my-work-projects');
	const projectTaskVisibilityOptions = optionList(['assigned-tasks-only', 'all-tasks-in-accessible-projects'], access.project_task_visibility || 'all-tasks-in-accessible-projects');
	const taskEditPolicyOptions = optionList(['assignee-only', 'assignee-or-reporter', 'all-project-members'], access.task_edit_policy || 'assignee-only');
	const attachmentRules = access.file_attachment_rules || {};
	const projectAttachmentOptions = optionList(['project-leads-only', 'project-members'], attachmentRules.project || 'project-leads-only');
	const taskAttachmentOptions = optionList(['assignee-and-project-leads', 'task-participants-and-project-leads', 'project-members'], attachmentRules.task || 'assignee-and-project-leads');
	const milestoneAttachmentOptions = optionList(['owner-and-project-leads', 'project-members'], attachmentRules.milestone || 'owner-and-project-leads');
	const riskAttachmentOptions = optionList(['owner-and-project-leads', 'project-members'], attachmentRules.risk_issue || 'owner-and-project-leads');
	const requestAttachmentOptions = optionList(['request-participants', 'triage-only'], attachmentRules.request || 'request-participants');
	const checklistManageRules = access.checklist_manage_rules || {};
	const checklistToggleRules = access.checklist_toggle_rules || {};
	const projectChecklistOptions = optionList(['project-leads-only', 'project-members'], checklistManageRules.project || 'project-leads-only');
	const projectChecklistToggleOptions = optionList(['project-leads-only', 'project-members'], checklistToggleRules.project || 'project-leads-only');
	const taskChecklistManageOptions = optionList(['project-leads-only', 'assignee-and-project-leads', 'task-participants-and-project-leads', 'project-members'], checklistManageRules.task || 'project-leads-only');
	const taskChecklistToggleOptions = optionList(['project-leads-only', 'assignee-and-project-leads', 'task-participants-and-project-leads', 'project-members'], checklistToggleRules.task || 'assignee-and-project-leads');
	const milestoneChecklistManageOptions = optionList(['project-leads-only', 'owner-and-project-leads', 'project-members'], checklistManageRules.milestone || 'project-leads-only');
	const milestoneChecklistToggleOptions = optionList(['project-leads-only', 'owner-and-project-leads', 'project-members'], checklistToggleRules.milestone || 'owner-and-project-leads');
	const riskChecklistManageOptions = optionList(['project-leads-only', 'owner-and-project-leads', 'project-members'], checklistManageRules.risk_issue || 'project-leads-only');
	const riskChecklistToggleOptions = optionList(['project-leads-only', 'owner-and-project-leads', 'project-members'], checklistToggleRules.risk_issue || 'owner-and-project-leads');
	const conversionOptions = optionList(['task', 'project'], workflows.request_conversion_default || 'task');
	const requesterVisibilityOptions = optionList(['own-requests', 'project-requests', 'none'], portal.requester_visibility || 'own-requests');
	const dropdownFields = [
		settingsTextarea(__('Project statuses', 'coordina'), 'dropdowns.statuses.projects', statuses.projects || []),
		settingsTextarea(__('Task statuses', 'coordina'), 'dropdowns.statuses.tasks', statuses.tasks || []),
		settingsTextarea(__('Request statuses', 'coordina'), 'dropdowns.statuses.requests', statuses.requests || []),
		settingsTextarea(__('Approval statuses', 'coordina'), 'dropdowns.statuses.approvals', statuses.approvals || []),
		settingsTextarea(__('Risk/issue statuses', 'coordina'), 'dropdowns.statuses.risksIssues', statuses.risksIssues || []),
		settingsTextarea(__('Milestone statuses', 'coordina'), 'dropdowns.statuses.milestones', statuses.milestones || []),
		settingsTextarea(__('Priorities', 'coordina'), 'dropdowns.priorities', dropdowns.priorities || []),
		settingsTextarea(__('Health values', 'coordina'), 'dropdowns.health', dropdowns.health || []),
		settingsTextarea(__('Severity values', 'coordina'), 'dropdowns.severities', dropdowns.severities || []),
		settingsTextarea(__('Impact values', 'coordina'), 'dropdowns.impacts', dropdowns.impacts || []),
		settingsTextarea(__('Likelihood values', 'coordina'), 'dropdowns.likelihoods', dropdowns.likelihoods || []),
		settingsTextarea(__('Visibility levels', 'coordina'), 'dropdowns.visibilityLevels', dropdowns.visibilityLevels || []),
		settingsTextarea(__('Project notification policies', 'coordina'), 'dropdowns.projectNotificationPolicies', dropdowns.projectNotificationPolicies || []),
		settingsTextarea(__('Request types', 'coordina'), 'dropdowns.requestTypes', dropdowns.requestTypes || []),
		settingsTextarea(__('Project types', 'coordina'), 'dropdowns.projectTypes', dropdowns.projectTypes || []),
		settingsTextarea(__('File categories', 'coordina'), 'dropdowns.fileCategories', dropdowns.fileCategories || []),
		settingsTextarea(__('Update types', 'coordina'), 'dropdowns.updateTypes', dropdowns.updateTypes || []),
	].join('');
	return `<section class="coordina-page"><div class="coordina-action-bar"><div><h2>${escapeHtml(__('Settings', 'coordina'))}</h2><p>${escapeHtml(__('Global defaults for Coordina. Project-specific governance stays inside each project workspace.', 'coordina'))}</p></div><div class="coordina-action-bar__actions"><button class="button button-primary" data-action="submit-settings">${escapeHtml(__('Save settings', 'coordina'))}</button></div></div><form class="coordina-form" data-action="settings-form"><section class="coordina-card"><div class="coordina-section-header"><h3>${escapeHtml(__('General', 'coordina'))}</h3></div><div class="coordina-form-grid"><label><span>${escapeHtml(__('Default landing page', 'coordina'))}</span><select data-setting-path="general.default_landing_page">${landingOptions}</select></label><label><span>${escapeHtml(__('Date display', 'coordina'))}</span><select data-setting-path="general.date_display">${dateOptions}</select></label><label><span>${escapeHtml(__('Project workspace default tab', 'coordina'))}</span><select data-setting-path="general.workspace_default_tab">${tabOptions}</select></label></div></section><section class="coordina-card"><div class="coordina-section-header"><h3>${escapeHtml(__('Dropdown values', 'coordina'))}</h3></div><p class="coordina-empty-inline">${escapeHtml(__('Use one value per line. Values are stored as safe tokens and defaults are restored if a list is emptied.', 'coordina'))}</p><div class="coordina-form-grid">${dropdownFields}</div></section><section class="coordina-card"><div class="coordina-section-header"><h3>${escapeHtml(__('Roles and permissions defaults', 'coordina'))}</h3></div><div class="coordina-form-grid"><label><span>${escapeHtml(__('Project access default', 'coordina'))}</span><select data-setting-path="access.project_access_default">${accessOptions}</select></label><label><span>${escapeHtml(__('Portal access default', 'coordina'))}</span><select data-setting-path="access.portal_access_default">${portalAccessOptions}</select></label><label><span>${escapeHtml(__('Project file attachments', 'coordina'))}</span><select data-setting-path="access.file_attachment_rules.project">${projectAttachmentOptions}</select></label><label><span>${escapeHtml(__('Task file attachments', 'coordina'))}</span><select data-setting-path="access.file_attachment_rules.task">${taskAttachmentOptions}</select></label><label><span>${escapeHtml(__('Milestone file attachments', 'coordina'))}</span><select data-setting-path="access.file_attachment_rules.milestone">${milestoneAttachmentOptions}</select></label><label><span>${escapeHtml(__('Risk and issue file attachments', 'coordina'))}</span><select data-setting-path="access.file_attachment_rules.risk_issue">${riskAttachmentOptions}</select></label><label><span>${escapeHtml(__('Request file attachments', 'coordina'))}</span><select data-setting-path="access.file_attachment_rules.request">${requestAttachmentOptions}</select></label></div></section><section class="coordina-card"><div class="coordina-section-header"><h3>${escapeHtml(__('Workflow defaults', 'coordina'))}</h3></div><div class="coordina-form-grid"><label><span>${escapeHtml(__('Request conversion default', 'coordina'))}</span><select data-setting-path="workflows.request_conversion_default">${conversionOptions}</select></label>${settingsCheckbox(__('Allow direct close-out', 'coordina'), 'workflows.allow_direct_closeout', workflows.allow_direct_closeout)}${settingsCheckbox(__('Only archive completed work', 'coordina'), 'workflows.archive_completed_only', workflows.archive_completed_only)}${settingsCheckbox(__('Require approval by default', 'coordina'), 'workflows.approval_required_default', workflows.approval_required_default)}</div></section><section class="coordina-card"><div class="coordina-section-header"><h3>${escapeHtml(__('Notifications', 'coordina'))}</h3></div><div class="coordina-form-grid">${settingsCheckbox(__('Assignment notices', 'coordina'), 'notifications.assignment', notifications.assignment)}${settingsCheckbox(__('Mention notices', 'coordina'), 'notifications.mention', notifications.mention)}${settingsCheckbox(__('Approval notices', 'coordina'), 'notifications.approval', notifications.approval)}${settingsCheckbox(__('Due date notices', 'coordina'), 'notifications.due_date', notifications.due_date)}${settingsCheckbox(__('Overdue notices', 'coordina'), 'notifications.overdue', notifications.overdue)}${settingsCheckbox(__('Project update notices', 'coordina'), 'notifications.project_update', notifications.project_update)}${settingsCheckbox(__('Milestone update notices', 'coordina'), 'notifications.milestone_update', notifications.milestone_update)}${settingsCheckbox(__('Digest by default', 'coordina'), 'notifications.digest', notifications.digest)}</div></section><section class="coordina-card"><div class="coordina-section-header"><h3>${escapeHtml(__('Portal', 'coordina'))}</h3></div><div class="coordina-form-grid">${settingsTextarea(__('Allowed request types', 'coordina'), 'portal.allowed_request_types', portal.allowed_request_types || [])}<label><span>${escapeHtml(__('Requester visibility', 'coordina'))}</span><select data-setting-path="portal.requester_visibility">${requesterVisibilityOptions}</select></label>${settingsCheckbox(__('Allow portal uploads', 'coordina'), 'portal.uploads_enabled', portal.uploads_enabled)}</div></section><section class="coordina-card"><div class="coordina-section-header"><h3>${escapeHtml(__('Data, privacy, and logs', 'coordina'))}</h3></div><div class="coordina-form-grid"><label><span>${escapeHtml(__('Activity retention days', 'coordina'))}</span><input type="number" min="30" max="3650" data-setting-path="data.activity_retention_days" value="${escapeHtml(data.activity_retention_days || 365)}" /></label><label><span>${escapeHtml(__('Notification retention days', 'coordina'))}</span><input type="number" min="30" max="3650" data-setting-path="data.notification_retention_days" value="${escapeHtml(data.notification_retention_days || 180)}" /></label>${settingsCheckbox(__('Enable exports', 'coordina'), 'data.export_enabled', data.export_enabled)}</div></section><div class="coordina-form-actions"><button class="button button-primary" type="submit">${escapeHtml(__('Save settings', 'coordina'))}</button></div></form></section>`;
}

function settingsPanel(title, description, content) {
	return `<section class="coordina-card coordina-settings-panel"><div class="coordina-section-header"><div><h3>${escapeHtml(title)}</h3><p class="coordina-settings-panel__intro">${escapeHtml(description)}</p></div></div>${content}</section>`;
}

function settingsPage() {
	const settings = state.settings || {};
	const general = settings.general || {};
	const activityPageSizeValue = Number(general.activity_page_size || 10);
	const pageDescriptionsEnabled = general.page_descriptions_enabled !== false;
	const sectionDescriptionsEnabled = general.section_descriptions_enabled !== false;
	const myWorkGuidanceEnabled = !!general.my_work_card_guidance_enabled;
	const myWorkActionsEnabled = !!general.my_work_card_actions_enabled;
	const dropdowns = settings.dropdowns || {};
	const statuses = dropdowns.statuses || {};
	const access = settings.access || {};
	const workflows = settings.workflows || {};
	const notifications = settings.notifications || {};
	const portal = settings.portal || {};
	const data = settings.data || {};
	const automation = settings.automation || {};
	const landingOptions = optionList(['coordina-my-work', 'coordina-dashboard', 'coordina-projects'], general.default_landing_page || 'coordina-my-work');
	const dateOptions = optionList(['site', 'relative', 'absolute'], general.date_display || 'site');
	const tabOptions = optionList(['overview', 'work', 'milestones', 'risks-issues', 'approvals', 'files', 'discussion', 'activity'], general.workspace_default_tab || 'overview');
	const taskGroupLabelOptions = optionList(['stage', 'phase', 'bucket'], general.task_group_label || 'stage');
	const accessOptions = optionList(dropdowns.visibilityLevels || ['team', 'private', 'public'], access.project_access_default || 'team');
	const portalAccessOptions = optionList(['disabled', 'requesters', 'logged-in-users'], access.portal_access_default || 'requesters');
	const workspaceVisibilityOptions = optionList(['members-only', 'members-and-assignees', 'all-coordina-users'], access.project_workspace_visibility || 'members-and-assignees');
	const projectListVisibilityOptions = optionList(['assigned-projects-only', 'all-accessible-projects', 'all-projects'], access.project_list_visibility || 'all-accessible-projects');
	const navigationScopeOptions = optionList(['dashboard-my-work-only', 'dashboard-my-work-projects', 'dashboard-my-work-projects-tasks'], access.non_admin_navigation_scope || 'dashboard-my-work-projects');
	const projectTaskVisibilityOptions = optionList(['assigned-tasks-only', 'all-tasks-in-accessible-projects'], access.project_task_visibility || 'all-tasks-in-accessible-projects');
	const taskEditPolicyOptions = optionList(['assignee-only', 'assignee-or-reporter', 'all-project-members'], access.task_edit_policy || 'assignee-only');
	const attachmentRules = access.file_attachment_rules || {};
	const projectAttachmentOptions = optionList(['project-leads-only', 'project-members'], attachmentRules.project || 'project-leads-only');
	const taskAttachmentOptions = optionList(['assignee-and-project-leads', 'task-participants-and-project-leads', 'project-members'], attachmentRules.task || 'assignee-and-project-leads');
	const milestoneAttachmentOptions = optionList(['owner-and-project-leads', 'project-members'], attachmentRules.milestone || 'owner-and-project-leads');
	const riskAttachmentOptions = optionList(['owner-and-project-leads', 'project-members'], attachmentRules.risk_issue || 'owner-and-project-leads');
	const requestAttachmentOptions = optionList(['request-participants', 'triage-only'], attachmentRules.request || 'request-participants');
	const checklistManageRules = access.checklist_manage_rules || {};
	const checklistToggleRules = access.checklist_toggle_rules || {};
	const projectChecklistOptions = optionList(['project-leads-only', 'project-members'], checklistManageRules.project || 'project-leads-only');
	const projectChecklistToggleOptions = optionList(['project-leads-only', 'project-members'], checklistToggleRules.project || 'project-leads-only');
	const taskChecklistManageOptions = optionList(['project-leads-only', 'assignee-and-project-leads', 'task-participants-and-project-leads', 'project-members'], checklistManageRules.task || 'project-leads-only');
	const taskChecklistToggleOptions = optionList(['project-leads-only', 'assignee-and-project-leads', 'task-participants-and-project-leads', 'project-members'], checklistToggleRules.task || 'assignee-and-project-leads');
	const milestoneChecklistManageOptions = optionList(['project-leads-only', 'owner-and-project-leads', 'project-members'], checklistManageRules.milestone || 'project-leads-only');
	const milestoneChecklistToggleOptions = optionList(['project-leads-only', 'owner-and-project-leads', 'project-members'], checklistToggleRules.milestone || 'owner-and-project-leads');
	const riskChecklistManageOptions = optionList(['project-leads-only', 'owner-and-project-leads', 'project-members'], checklistManageRules.risk_issue || 'project-leads-only');
	const riskChecklistToggleOptions = optionList(['project-leads-only', 'owner-and-project-leads', 'project-members'], checklistToggleRules.risk_issue || 'owner-and-project-leads');
	const conversionOptions = optionList(['task', 'project'], workflows.request_conversion_default || 'task');
	const requesterVisibilityOptions = optionList(['own-requests', 'project-requests', 'none'], portal.requester_visibility || 'own-requests');
	const dropdownFields = [
		settingsTextarea(__('Project statuses', 'coordina'), 'dropdowns.statuses.projects', statuses.projects || []),
		settingsTextarea(__('Task statuses', 'coordina'), 'dropdowns.statuses.tasks', statuses.tasks || []),
		settingsTextarea(__('Request statuses', 'coordina'), 'dropdowns.statuses.requests', statuses.requests || []),
		settingsTextarea(__('Approval statuses', 'coordina'), 'dropdowns.statuses.approvals', statuses.approvals || []),
		settingsTextarea(__('Risk/issue statuses', 'coordina'), 'dropdowns.statuses.risksIssues', statuses.risksIssues || []),
		settingsTextarea(__('Milestone statuses', 'coordina'), 'dropdowns.statuses.milestones', statuses.milestones || []),
		settingsTextarea(__('Priorities', 'coordina'), 'dropdowns.priorities', dropdowns.priorities || []),
		settingsTextarea(__('Health values', 'coordina'), 'dropdowns.health', dropdowns.health || []),
		settingsTextarea(__('Severity values', 'coordina'), 'dropdowns.severities', dropdowns.severities || []),
		settingsTextarea(__('Impact values', 'coordina'), 'dropdowns.impacts', dropdowns.impacts || []),
		settingsTextarea(__('Likelihood values', 'coordina'), 'dropdowns.likelihoods', dropdowns.likelihoods || []),
		settingsTextarea(__('Visibility levels', 'coordina'), 'dropdowns.visibilityLevels', dropdowns.visibilityLevels || []),
		settingsTextarea(__('Project notification policies', 'coordina'), 'dropdowns.projectNotificationPolicies', dropdowns.projectNotificationPolicies || []),
		settingsTextarea(__('Request types', 'coordina'), 'dropdowns.requestTypes', dropdowns.requestTypes || []),
		settingsTextarea(__('Project types', 'coordina'), 'dropdowns.projectTypes', dropdowns.projectTypes || []),
		settingsTextarea(__('File categories', 'coordina'), 'dropdowns.fileCategories', dropdowns.fileCategories || []),
		settingsTextarea(__('Update types', 'coordina'), 'dropdowns.updateTypes', dropdowns.updateTypes || []),
	].join('');
	const accessPolicyGuide = `<section class="coordina-card coordina-card--notice"><div class="coordina-section-header"><h3>${escapeHtml(__('Access policy guide', 'coordina'))}</h3></div><p>${escapeHtml(__('Keep the defaults conservative. Project lists control discovery, workspace visibility controls who can open a project, task visibility controls what visible work appears, edit rules control who can change it, and attachment rules control who can add supporting files. Form pickers only offer projects the current user can actually act on.', 'coordina'))}</p></section>`;
	const sections = {
		defaults: {
			label: __('Defaults', 'coordina'),
			title: __('Common defaults', 'coordina'),
			description: __('Choose the basic behavior people notice first.', 'coordina'),
			content: `<div class="coordina-form-grid coordina-form-grid--settings"><section class="coordina-settings-block is-notice"><div class="coordina-section-header"><h4>${escapeHtml(__('Workspace defaults', 'coordina'))}</h4></div><div class="coordina-form-grid"><div><label><span>${escapeHtml(__('Default landing page', 'coordina'))}</span><select data-setting-path="general.default_landing_page">${landingOptions}</select></label>${settingsHint(__('Choose the page people open first after entering Coordina.', 'coordina'))}</div><div><label><span>${escapeHtml(__('Project workspace default tab', 'coordina'))}</span><select data-setting-path="general.workspace_default_tab">${tabOptions}</select></label>${settingsHint(__('Choose which tab opens first inside a project workspace.', 'coordina'))}</div><div><label><span>${escapeHtml(__('Default task group name', 'coordina'))}</span><select data-setting-path="general.task_group_label">${taskGroupLabelOptions}</select></label>${settingsHint(__('Choose the label used for project task buckets.', 'coordina'))}</div></div></section></div>`,
		},
		display: {
			label: __('Display', 'coordina'),
			title: __('Display and helper text', 'coordina'),
			description: __('Choose how much guidance, helper copy, and density the interface should show by default.', 'coordina'),
			content: `<div class="coordina-form-grid coordina-form-grid--settings"><section class="coordina-settings-block is-notice"><div class="coordina-section-header"><h4>${escapeHtml(__('Shell and page guidance', 'coordina'))}</h4></div><div class="coordina-form-grid"><div><label><span>${escapeHtml(__('Date display', 'coordina'))}</span><select data-setting-path="general.date_display">${dateOptions}</select></label>${settingsHint(__('Choose how dates are shown across the app.', 'coordina'))}</div><div><label><span>${escapeHtml(__('Activity items per page', 'coordina'))}</span><input type="number" min="5" max="50" data-setting-path="general.activity_page_size" value="${escapeHtml(activityPageSizeValue)}" /></label>${settingsHint(__('Choose how many activity entries each feed page shows across dashboard, project, and record detail surfaces.', 'coordina'))}</div><div>${settingsCheckbox(__('Show page header descriptions', 'coordina'), 'general.page_descriptions_enabled', pageDescriptionsEnabled)}${settingsHint(__('Show or hide the shared page header description text across Coordina.', 'coordina'))}</div><div>${settingsCheckbox(__('Show section descriptions', 'coordina'), 'general.section_descriptions_enabled', sectionDescriptionsEnabled)}${settingsHint(__('Show or hide section-level helper descriptions such as notes under card and panel titles.', 'coordina'))}</div></div></section><section class="coordina-settings-block"><div class="coordina-section-header"><h4>${escapeHtml(__('My Work card display', 'coordina'))}</h4></div><div class="coordina-form-grid"><div>${settingsCheckbox(__('Show task card guidance text', 'coordina'), 'general.my_work_card_guidance_enabled', myWorkGuidanceEnabled)}${settingsHint(__('Show or hide helper guidance lines inside My Work task cards and board cards.', 'coordina'))}</div><div>${settingsCheckbox(__('Show quick task card actions', 'coordina'), 'general.my_work_card_actions_enabled', myWorkActionsEnabled)}${settingsHint(__('Show or hide quick actions like Start, Waiting, and Done inside My Work task cards and board cards.', 'coordina'))}</div></div></section></div>`,
		},
		intake: {
			label: __('Intake & access', 'coordina'),
			title: __('Intake and access defaults', 'coordina'),
			description: __('Choose who can see work and how the request portal behaves.', 'coordina'),
			content: `${accessPolicyGuide}<div class="coordina-form-grid coordina-form-grid--settings"><section class="coordina-settings-block is-notice"><div class="coordina-section-header"><h4>${escapeHtml(__('Project access defaults', 'coordina'))}</h4></div><div class="coordina-form-grid"><div><label><span>${escapeHtml(__('Project access default', 'coordina'))}</span><select data-setting-path="access.project_access_default">${accessOptions}</select></label>${settingsHint(__('Set the default visibility for new projects.', 'coordina'))}</div><div><label><span>${escapeHtml(__('Project workspace visibility', 'coordina'))}</span><select data-setting-path="access.project_workspace_visibility">${workspaceVisibilityOptions}</select></label>${settingsHint(__('Choose who can open a project workspace.', 'coordina'))}</div><div><label><span>${escapeHtml(__('Project list visibility', 'coordina'))}</span><select data-setting-path="access.project_list_visibility">${projectListVisibilityOptions}</select></label>${settingsHint(__('Choose how widely projects appear in the main list.', 'coordina'))}</div><div><label><span>${escapeHtml(__('Non-admin navigation', 'coordina'))}</span><select data-setting-path="access.non_admin_navigation_scope">${navigationScopeOptions}</select></label>${settingsHint(__('Choose which main pages non-admin users can see in the menu.', 'coordina'))}</div><div><label><span>${escapeHtml(__('Portal access default', 'coordina'))}</span><select data-setting-path="access.portal_access_default">${portalAccessOptions}</select></label>${settingsHint(__('Choose who can open the request portal.', 'coordina'))}</div></div></section><section class="coordina-settings-block"><div class="coordina-section-header"><h4>${escapeHtml(__('Portal defaults', 'coordina'))}</h4></div><div class="coordina-form-grid">${settingsTextarea(__('Allowed request types', 'coordina'), 'portal.allowed_request_types', portal.allowed_request_types || [])}<div><label><span>${escapeHtml(__('Requester visibility', 'coordina'))}</span><select data-setting-path="portal.requester_visibility">${requesterVisibilityOptions}</select></label>${settingsHint(__('Choose what request history a requester can see.', 'coordina'))}</div><div>${settingsCheckbox(__('Allow portal uploads', 'coordina'), 'portal.uploads_enabled', portal.uploads_enabled)}${settingsHint(__('Allow requesters to add files in the portal.', 'coordina'))}</div></div></section></div>`,
		},
		governance: {
			label: __('Governance', 'coordina'),
			title: __('Workflow and task rules', 'coordina'),
			description: __('Choose who can work on visible tasks, who can attach files, and how request conversion behaves.', 'coordina'),
			content: `<div class="coordina-form-grid coordina-form-grid--settings"><section class="coordina-settings-block is-notice"><div class="coordina-section-header"><h4>${escapeHtml(__('Task visibility and edit rules', 'coordina'))}</h4></div><div class="coordina-form-grid"><div><label><span>${escapeHtml(__('Project task visibility', 'coordina'))}</span><select data-setting-path="access.project_task_visibility">${projectTaskVisibilityOptions}</select></label>${settingsHint(__('Choose whether users see all tasks in visible projects or only their own assigned tasks.', 'coordina'))}</div><div><label><span>${escapeHtml(__('Task edit policy', 'coordina'))}</span><select data-setting-path="access.task_edit_policy">${taskEditPolicyOptions}</select></label>${settingsHint(__('Choose which visible task participants can update status, completion, and actual finish date when they are not project leads.', 'coordina'))}</div><div><p class="coordina-section-note">${escapeHtml(__('Project leads always keep full task editing, including title, description, ownership, planning, and blockers.', 'coordina'))}</p></div></div></section><section class="coordina-settings-block"><div class="coordina-section-header"><h4>${escapeHtml(__('Checklist rules', 'coordina'))}</h4></div><div class="coordina-form-grid"><div><label><span>${escapeHtml(__('Project checklist editing', 'coordina'))}</span><select data-setting-path="access.checklist_manage_rules.project">${projectChecklistOptions}</select></label>${settingsHint(__('Choose who can add, edit, delete, or reorder checklist items on a project.', 'coordina'))}</div><div><label><span>${escapeHtml(__('Project checklist ticking', 'coordina'))}</span><select data-setting-path="access.checklist_toggle_rules.project">${projectChecklistToggleOptions}</select></label>${settingsHint(__('Choose who can tick and untick project checklist items.', 'coordina'))}</div><div><label><span>${escapeHtml(__('Task checklist editing', 'coordina'))}</span><select data-setting-path="access.checklist_manage_rules.task">${taskChecklistManageOptions}</select></label>${settingsHint(__('Keep this on project leads only, or let assignees and task participants manage their own task checklist items.', 'coordina'))}</div><div><label><span>${escapeHtml(__('Task checklist ticking', 'coordina'))}</span><select data-setting-path="access.checklist_toggle_rules.task">${taskChecklistToggleOptions}</select></label>${settingsHint(__('Default is safest: let the assignee tick task checklist items, with project leads kept as an override.', 'coordina'))}</div><div><label><span>${escapeHtml(__('Milestone checklist editing', 'coordina'))}</span><select data-setting-path="access.checklist_manage_rules.milestone">${milestoneChecklistManageOptions}</select></label>${settingsHint(__('Choose whether only project leads or also the milestone owner can manage checklist items.', 'coordina'))}</div><div><label><span>${escapeHtml(__('Milestone checklist ticking', 'coordina'))}</span><select data-setting-path="access.checklist_toggle_rules.milestone">${milestoneChecklistToggleOptions}</select></label>${settingsHint(__('Choose whether only project leads or also the milestone owner can tick checklist items.', 'coordina'))}</div><div><label><span>${escapeHtml(__('Risk and issue checklist editing', 'coordina'))}</span><select data-setting-path="access.checklist_manage_rules.risk_issue">${riskChecklistManageOptions}</select></label>${settingsHint(__('Choose whether only project leads or also the record owner can manage checklist items.', 'coordina'))}</div><div><label><span>${escapeHtml(__('Risk and issue checklist ticking', 'coordina'))}</span><select data-setting-path="access.checklist_toggle_rules.risk_issue">${riskChecklistToggleOptions}</select></label>${settingsHint(__('Choose whether only project leads or also the record owner can tick checklist items.', 'coordina'))}</div></div></section><section class="coordina-settings-block"><div class="coordina-section-header"><h4>${escapeHtml(__('File attachment rules', 'coordina'))}</h4></div><div class="coordina-form-grid"><div><label><span>${escapeHtml(__('Project files', 'coordina'))}</span><select data-setting-path="access.file_attachment_rules.project">${projectAttachmentOptions}</select></label>${settingsHint(__('Default is safest: only the project manager or creator can attach files to a project.', 'coordina'))}</div><div><label><span>${escapeHtml(__('Task files', 'coordina'))}</span><select data-setting-path="access.file_attachment_rules.task">${taskAttachmentOptions}</select></label>${settingsHint(__('Default is safest: only the task assignee can attach files, with the project manager or creator kept as an override.', 'coordina'))}</div><div><label><span>${escapeHtml(__('Milestone files', 'coordina'))}</span><select data-setting-path="access.file_attachment_rules.milestone">${milestoneAttachmentOptions}</select></label>${settingsHint(__('Default is safest: only the milestone owner can attach files, with the project manager or creator kept as an override.', 'coordina'))}</div><div><label><span>${escapeHtml(__('Risk and issue files', 'coordina'))}</span><select data-setting-path="access.file_attachment_rules.risk_issue">${riskAttachmentOptions}</select></label>${settingsHint(__('Default is safest: only the owner can attach files, with the project manager or creator kept as an override.', 'coordina'))}</div><div><label><span>${escapeHtml(__('Request files', 'coordina'))}</span><select data-setting-path="access.file_attachment_rules.request">${requestAttachmentOptions}</select></label>${settingsHint(__('Allow request attachments for the requester and triage owner, or tighten them to triage only.', 'coordina'))}</div></div></section><section class="coordina-settings-block"><div class="coordina-section-header"><h4>${escapeHtml(__('Workflow defaults', 'coordina'))}</h4></div><div class="coordina-form-grid"><div><label><span>${escapeHtml(__('Request conversion default', 'coordina'))}</span><select data-setting-path="workflows.request_conversion_default">${conversionOptions}</select></label>${settingsHint(__('Choose whether approved requests become tasks or projects by default.', 'coordina'))}</div><div>${settingsCheckbox(__('Allow direct close-out', 'coordina'), 'workflows.allow_direct_closeout', workflows.allow_direct_closeout)}${settingsHint(__('Allow records to be closed without a longer workflow.', 'coordina'))}</div><div>${settingsCheckbox(__('Only archive completed work', 'coordina'), 'workflows.archive_completed_only', workflows.archive_completed_only)}${settingsHint(__('Only allow archiving when work is already complete.', 'coordina'))}</div><div>${settingsCheckbox(__('Require approval by default', 'coordina'), 'workflows.approval_required_default', workflows.approval_required_default)}${settingsHint(__('Turn on approvals automatically for new work that supports them.', 'coordina'))}</div></div></section></div>`,
		},
		dropdowns: {
			label: __('Dropdowns', 'coordina'),
			title: __('Dropdown values', 'coordina'),
			description: __('Manage the controlled values users see in forms. Use one value per line. If a list is emptied, safe defaults are restored.', 'coordina'),
			content: `<div class="coordina-form-grid coordina-form-grid--settings">${dropdownFields}</div>`,
		},
		advanced: {
			label: __('Advanced', 'coordina'),
			title: __('Advanced controls', 'coordina'),
			description: __('Keep only the maintenance options teams need occasionally.', 'coordina'),
			content: `<div class="coordina-form-grid coordina-form-grid--settings"><section class="coordina-settings-block is-notice"><div class="coordina-section-header"><h4>${escapeHtml(__('Data, privacy, and logs', 'coordina'))}</h4></div><div class="coordina-form-grid"><div><label><span>${escapeHtml(__('Activity retention days', 'coordina'))}</span><input type="number" min="30" max="3650" data-setting-path="data.activity_retention_days" value="${escapeHtml(data.activity_retention_days || 365)}" /></label>${settingsHint(__('Choose how long activity history is kept.', 'coordina'))}</div><div><label><span>${escapeHtml(__('Notification retention days', 'coordina'))}</span><input type="number" min="30" max="3650" data-setting-path="data.notification_retention_days" value="${escapeHtml(data.notification_retention_days || 180)}" /></label>${settingsHint(__('Choose how long notifications are kept.', 'coordina'))}</div><div>${settingsCheckbox(__('Enable exports', 'coordina'), 'data.export_enabled', data.export_enabled)}${settingsHint(__('Allow export features where available.', 'coordina'))}</div></div></section></div>`,
		},
	};
	const activeKey = sections[state.settingsTab] ? state.settingsTab : 'defaults';
	const tabs = Object.keys(sections).map((key) => `<button type="button" class="coordina-tab ${key === activeKey ? 'is-active' : ''}" data-action="switch-settings-tab" data-tab="${key}" role="tab" aria-selected="${key === activeKey ? 'true' : 'false'}">${escapeHtml(sections[key].label)}</button>`).join('');
	const active = sections[activeKey];
	const helper = `<section class="coordina-card coordina-card--notice"><div class="coordina-section-header"><h3>${escapeHtml(__('How to use Settings', 'coordina'))}</h3></div><p>${escapeHtml(__('Start with Defaults for behavior, Display for interface guidance, and Intake & access plus Governance for policy. Project-specific rules still belong inside each project workspace.', 'coordina'))}</p></section>`;

	return `<section class="coordina-page">${pageHeading('coordina-settings', `<button class="button" data-action="open-route" data-page="coordina-projects">${escapeHtml(__('Project workspaces', 'coordina'))}</button><button class="button button-primary" data-action="submit-settings">${escapeHtml(__('Save section', 'coordina'))}</button>`, { title: __('Settings', 'coordina'), description: __('Manage defaults, access, dropdowns, and other plugin settings.', 'coordina') })}${helper}<form class="coordina-form coordina-settings-form" data-action="settings-form"><nav class="coordina-settings-tabs" role="tablist" aria-label="${escapeHtml(__('Settings sections', 'coordina'))}">${tabs}</nav>${settingsPanel(active.title, active.description, active.content)}<div class="coordina-form-actions"><button class="button button-primary" type="submit">${escapeHtml(__('Save this section', 'coordina'))}</button></div></form></section>`;
}


Object.assign(app, {
settingsPageLegacy,
settingsPanel,
settingsPage,
});

window.CoordinaAdminApp = app;
}());
