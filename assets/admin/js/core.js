(function () {
const app = window.CoordinaAdminApp || {};
const root = document.getElementById('coordina-admin-app');
const config = window.coordinaAdmin;
const i18n = window.wp && window.wp.i18n ? window.wp.i18n : null;
const __ = i18n ? i18n.__ : (text) => text;

if (!root || !config) {
	return;
}

const apiBase = config.restUrl.replace(/\/$/, '');
const state = {
	shell: null,
	page: config.currentPage,
	loading: true,
	collection: null,
	myWork: null,
	notifications: null,
	savedViews: [],
	selection: [],
	notices: [],
	drawer: null,
	modal: null,
	filters: null,
	workspace: null,
	workspaceView: 'list',
	myWorkView: 'queue',
	myWorkTasksCollection: null,
	myWorkTasksFilters: null,
	dashboard: null,
	calendar: null,
	workload: null,
	collaboration: null,
	settings: null,
	settingsTab: 'defaults',
	notificationFilter: 'unread',
	calendarFilters: null,
	workloadFilters: null,
	collaborationFilters: null,
	projectContext: Object.assign({ id: 0, tab: 'overview' }, config.projectContext || {}),
	taskContext: Object.assign({ id: 0 }, config.taskContext || {}),
	milestoneContext: Object.assign({ id: 0 }, config.milestoneContext || {}),
	riskIssueContext: Object.assign({ id: 0 }, config.riskIssueContext || {}),
	workspaceActivityPage: 1,
	taskActivityPage: 1,
	milestoneActivityPage: 1,
	riskIssueActivityPage: 1,
	dashboardActivityPage: 1,
	projectDetailEditing: false,
	taskDetail: null,
	taskDetailEditing: false,
	milestoneDetail: null,
	milestoneDetailEditing: false,
	riskIssueDetail: null,
	riskIssueDetailEditing: false,
	pageMeta: Object.assign({}, config.pages || {}),
	contextDefinitions: Object.assign({}, config.contextDefinitions || {}),
	visiblePages: Array.isArray(config.visiblePages) ? config.visiblePages : [],
};

const modules = {
	'coordina-projects': {
		key: 'projects', endpoint: 'projects', title: __('Projects', 'coordina'), singular: __('project', 'coordina'), statuses: 'projects', bulk: ['draft', 'planned', 'active', 'on-hold', 'completed', 'archived'], columns: ['title', 'status', 'health', 'priority', 'manager_label', 'target_end_date'], fields: ['title', 'code', 'description', 'status', 'health', 'priority', 'manager_user_id', 'sponsor_user_id', 'start_date', 'target_end_date', 'actual_end_date', 'closeout_notes'],
	},
	'coordina-tasks': {
		key: 'tasks', endpoint: 'tasks', title: __('Tasks', 'coordina'), singular: __('task', 'coordina'), statuses: 'tasks', bulk: ['new', 'to-do', 'in-progress', 'waiting', 'blocked', 'in-review', 'done'], columns: ['title', 'project_label', 'task_group_label', 'status', 'priority', 'assignee_label', 'due_date', 'checklist_summary', 'approval_state', 'blocked'], fields: ['title', 'project_id', 'task_group_id', 'description', 'checklist', 'status', 'priority', 'assignee_user_id', 'start_date', 'due_date', 'completion_percent', 'actual_finish_date', 'blocked', 'blocked_reason', 'approval_required'], contextFilters: true,
	},
	'coordina-requests': {
		key: 'requests', endpoint: 'requests', title: __('Requests', 'coordina'), singular: __('request', 'coordina'), statuses: 'requests', bulk: ['submitted', 'under-review', 'awaiting-info', 'approved', 'rejected', 'closed'], columns: ['title', 'status', 'priority', 'approval_status', 'requester_label', 'triage_owner_label', 'desired_due_date'], fields: ['title', 'request_type', 'business_reason', 'status', 'priority', 'triage_owner_user_id', 'desired_due_date'],
	},
	'coordina-approvals': {
		key: 'approvals', endpoint: 'approvals', title: __('Approvals', 'coordina'), singular: __('approval', 'coordina'), statuses: 'approvals', bulk: ['pending', 'approved', 'rejected', 'cancelled'], columns: ['object_label', 'object_type', 'project_label', 'status', 'approver_label', 'submitted_at'], fields: ['status', 'rejection_reason'], approvalFilters: true, createEnabled: false,
	},
	'coordina-risks-issues': {
		key: 'risks-issues', endpoint: 'risks-issues', title: __('Risks & Issues', 'coordina'), singular: __('risk / issue', 'coordina'), statuses: 'risksIssues', bulk: ['identified', 'monitoring', 'mitigation-in-progress', 'escalated', 'resolved', 'closed'], columns: ['title', 'object_type', 'project_label', 'status', 'severity', 'owner_label', 'target_resolution_date'], fields: ['title', 'project_id', 'object_type', 'description', 'status', 'severity', 'impact', 'likelihood', 'owner_user_id', 'mitigation_plan', 'target_resolution_date'], riskFilters: true,
	},
	'coordina-milestones': {
		key: 'milestones', endpoint: 'milestones', title: __('Milestones', 'coordina'), singular: __('milestone', 'coordina'), statuses: 'milestones', bulk: ['planned', 'in-progress', 'at-risk', 'completed', 'skipped'], columns: ['title', 'status', 'owner_label', 'due_date', 'completion_percent', 'dependency_flag'], fields: ['title', 'project_id', 'status', 'owner_user_id', 'due_date', 'completion_percent', 'dependency_flag', 'notes'], createEnabled: true,
	},
	'coordina-files': {
		key: 'files', endpoint: 'files', title: __('Files', 'coordina'), singular: __('file', 'coordina'), columns: ['file_name', 'object_label', 'project_label', 'created_by_label', 'created_at'], fields: ['object_type', 'object_id', 'attachment_id', 'note'], createEnabled: false,
	},
	'coordina-discussions': {
		key: 'discussions', endpoint: 'discussions', title: __('Updates', 'coordina'), singular: __('update', 'coordina'), columns: ['excerpt', 'object_label', 'project_label', 'created_by_label', 'created_at'], fields: ['object_type', 'object_id', 'body'], createEnabled: false,
	},
};

const currentModule = () => modules[state.page] || null;
const hasProjectWorkspace = () => state.page === 'coordina-projects' && Number(state.projectContext.id || 0) > 0;
const hasTaskPage = () => state.page === 'coordina-task' && Number(state.taskContext.id || 0) > 0;
const hasMilestonePage = () => state.page === 'coordina-milestone' && Number(state.milestoneContext.id || 0) > 0;
const hasRiskIssuePage = () => state.page === 'coordina-risk-issue' && Number(state.riskIssueContext.id || 0) > 0;
const getPageMeta = (page) => state.pageMeta[String(page || state.page)] || {};
const canAccessPage = (page) => state.visiblePages.includes(String(page || ''));
const isDateKey = (key) => key.indexOf('date') !== -1 || key.endsWith('_at');
const featureState = (featureKey) => (state.shell && state.shell.featureStates ? (state.shell.featureStates[String(featureKey || '')] || null) : null);
const featureEnabled = (featureKey) => !!(featureState(featureKey) && featureState(featureKey).enabled);

function escapeHtml(value) {
	return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function nice(value) {
	return value ? String(value).replace(/[-_]/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase()) : '--';
}

function parseDateValue(value) {
	if (!value) {
		return null;
	}
	const raw = String(value).trim();
	if (!raw) {
		return null;
	}
	const parsed = new Date(raw.replace(' ', 'T'));
	if (Number.isNaN(parsed.getTime())) {
		return null;
	}
	return { raw, parsed };
}

function activeDateDisplayMode() {
	const mode = String((state.shell && state.shell.dateDisplay) || 'site');
	return ['site', 'relative', 'absolute'].includes(mode) ? mode : 'site';
}

function startOfDay(date) {
	const next = new Date(date);
	next.setHours(0, 0, 0, 0);
	return next;
}

function relativeDateLabel(date) {
	const today = startOfDay(new Date());
	const target = startOfDay(date);
	const diffDays = Math.round((target.getTime() - today.getTime()) / 86400000);
	if (diffDays === 0) {
		return __('Today', 'coordina');
	}
	if (diffDays === 1) {
		return __('Tomorrow', 'coordina');
	}
	if (diffDays === -1) {
		return __('Yesterday', 'coordina');
	}
	if (diffDays > 1) {
		return `${__('In', 'coordina')} ${diffDays} ${diffDays === 1 ? __('day', 'coordina') : __('days', 'coordina')}`;
	}
	const past = Math.abs(diffDays);
	return `${past} ${past === 1 ? __('day', 'coordina') : __('days', 'coordina')} ${__('ago', 'coordina')}`;
}

function formatTimeLabel(date) {
	return new Intl.DateTimeFormat(document.documentElement.lang || undefined, { hour: '2-digit', minute: '2-digit' }).format(date);
}

function dateLabel(value, options = {}) {
	if (!value) {
		return '--';
	}
	try {
		const parsedValue = parseDateValue(value);
		if (!parsedValue) {
			return String(value);
		}
		const { raw, parsed } = parsedValue;
		const mode = options.mode || activeDateDisplayMode();
		if (mode === 'relative') {
			return relativeDateLabel(parsed);
		}
		if (mode === 'absolute') {
			return raw.slice(0, 10);
		}
		return new Intl.DateTimeFormat(document.documentElement.lang || undefined, { year: 'numeric', month: 'short', day: 'numeric' }).format(parsed);
	} catch (error) {
		return String(value);
	}
}

function dateTimeLabel(value, options = {}) {
	if (!value) {
		return '--';
	}
	try {
		const parsedValue = parseDateValue(value);
		if (!parsedValue) {
			return String(value);
		}
		const { raw, parsed } = parsedValue;
		const mode = options.mode || activeDateDisplayMode();
		const time = formatTimeLabel(parsed);
		if (mode === 'absolute') {
			return `${raw.slice(0, 10)} ${time}`;
		}
		return `${dateLabel(value, { mode })}, ${time}`;
	} catch (error) {
		return String(value);
	}
}

function activityPageSize() {
	return Math.max(5, Math.min(50, Number((state.shell && state.shell.activityPageSize) || 10)));
}

function dateTimeInputValue(value) {
	if (!value) {
		return '';
	}
	const normalized = String(value).trim().replace(' ', 'T');
	const match = normalized.match(/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2})/);
	return match ? match[1] : normalized;
}

function isCheckedValue(value) {
	return value === true || value === 1 || value === '1';
}

function dateKey(date) {
	const year = date.getFullYear();
	const month = `${date.getMonth() + 1}`.padStart(2, '0');
	const day = `${date.getDate()}`.padStart(2, '0');
	return `${year}-${month}-${day}`;
}

function todayKey() {
	return dateKey(new Date());
}

function weekStartKey(input) {
	const date = input ? new Date(`${input}T00:00:00`) : new Date();
	const day = (date.getDay() + 6) % 7;
	date.setDate(date.getDate() - day);
	return dateKey(date);
}

function shiftDate(value, unit, amount) {
	const date = new Date(`${value}T00:00:00`);
	if (Number.isNaN(date.getTime())) {
		return unit === 'month' ? todayKey() : weekStartKey();
	}
	if (unit === 'month') {
		date.setMonth(date.getMonth() + amount);
	} else {
		date.setDate(date.getDate() + (amount * 7));
	}
	return dateKey(date);
}

function getFilters(key) {
	const stored = window.localStorage.getItem(`coordina:${key}`);
	const fallback = { search: '', status: '', page: 1, per_page: 10, orderby: 'updated_at', order: 'desc', project_mode: 'all', project_id: '', object_type: '', severity: '', owner_user_id: '', approver_user_id: '' };
	if (!stored) {
		return fallback;
	}
	try {
		return Object.assign(fallback, JSON.parse(stored));
	} catch (error) {
		return fallback;
	}
}

function saveFilters(key) {
	window.localStorage.setItem(`coordina:${key}`, JSON.stringify(state.filters));
}

function getStoredFilters(key, fallback) {
	const stored = window.localStorage.getItem(`coordina:${key}`);
	if (!stored) {
		return Object.assign({}, fallback);
	}
	try {
		return Object.assign({}, fallback, JSON.parse(stored));
	} catch (error) {
		return Object.assign({}, fallback);
	}
}

function saveStoredFilters(key, value) {
	window.localStorage.setItem(`coordina:${key}`, JSON.stringify(value));
}

function defaultCalendarFilters() {
	return { view: 'month', focus_date: todayKey(), object_type: 'all', person_user_id: '', project_id: '' };
}

function defaultWorkloadFilters() {
	return { week_start: weekStartKey(), status: '', priority: '', project_id: '', person_user_id: '' };
}

function defaultCollaborationFilters() {
	return { search: '', object_type: '', project_id: '', recency: '', per_page: 10, order: 'desc' };
}

function defaultMyWorkTaskFilters() {
	return { search: '', status: '', project_mode: 'all', project_id: '', page: 1, per_page: 12, orderby: 'updated_at', order: 'desc' };
}

async function api(path, options) {
	const response = await window.fetch(apiBase + path, {
		credentials: 'same-origin',
		headers: { 'X-WP-Nonce': config.nonce, 'Content-Type': 'application/json' },
		method: options && options.method ? options.method : 'GET',
		body: options && options.body ? JSON.stringify(options.body) : undefined,
	});
	const payload = await response.json();
	if (!response.ok || !payload.success) {
		throw new Error(payload.error || __('Request failed.', 'coordina'));
	}
	return payload.data;
}

function notify(type, message) {
	state.notices.push({ id: Date.now(), type, message });
	app.render();
	window.setTimeout(() => {
		state.notices = state.notices.slice(1);
		app.render();
	}, 3500);
}

async function loadCollection() { state.collection = await api(`/${currentModule().endpoint}?${new URLSearchParams(state.filters)}`); }
async function loadViews() { const data = await api(`/saved-views?module=${currentModule().key}`); state.savedViews = data.items || []; }
async function loadWorkspace() {
	const params = new URLSearchParams({ tab: state.projectContext.tab || 'overview' });
	if ((state.projectContext.tab || 'overview') === 'activity') {
		params.set('activity_page', String(state.workspaceActivityPage || 1));
	}
	state.workspace = await api(`/projects/${state.projectContext.id}/workspace?${params.toString()}`);
}
async function loadTaskDetail() {
	const taskId = Number(state.taskContext.id || 0);
	if (taskId <= 0) {
		state.taskDetail = null;
		return;
	}

	const task = await api(`/tasks/${taskId}`);
	if (!task || !task.id) {
		state.taskDetail = null;
		return;
	}

	const detailRequests = [
		api(`/files?${new URLSearchParams({ object_type: 'task', object_id: taskId, per_page: 25, order: 'desc' })}`),
		api(`/discussions?${new URLSearchParams({ object_type: 'task', object_id: taskId, per_page: 25, order: 'desc' })}`),
		api(`/activity?${new URLSearchParams({ object_type: 'task', object_id: taskId, page: state.taskActivityPage || 1, per_page: activityPageSize() })}`),
		api(`/checklists?${new URLSearchParams({ object_type: 'task', object_id: taskId })}`),
	];

	if (Number(task.project_id || 0) > 0) {
		detailRequests.push(api(`/projects/${task.project_id}/task-groups`));
	}

	const [files, discussions, activity, checklist, taskGroupData] = await Promise.all(detailRequests);

	state.taskDetail = {
		task,
		files,
		discussions,
		activity,
		checklist,
		taskGroups: taskGroupData && Array.isArray(taskGroupData.items) ? taskGroupData.items : [],
		taskGroupLabel: taskGroupData && taskGroupData.taskGroupLabel ? taskGroupData.taskGroupLabel : (state.shell && state.shell.taskGroupLabel ? state.shell.taskGroupLabel : 'stage'),
	};
}
async function loadMilestoneDetail() {
	const milestoneId = Number(state.milestoneContext.id || 0);
	if (milestoneId <= 0) {
		state.milestoneDetail = null;
		return;
	}

	const milestone = await api(`/milestones/${milestoneId}`);
	if (!milestone || !milestone.id) {
		state.milestoneDetail = null;
		return;
	}

	const [files, discussions, activity, checklist] = await Promise.all([
		api(`/files?${new URLSearchParams({ object_type: 'milestone', object_id: milestoneId, per_page: 25, order: 'desc' })}`),
		api(`/discussions?${new URLSearchParams({ object_type: 'milestone', object_id: milestoneId, per_page: 25, order: 'desc' })}`),
		api(`/activity?${new URLSearchParams({ object_type: 'milestone', object_id: milestoneId, page: state.milestoneActivityPage || 1, per_page: activityPageSize() })}`),
		api(`/checklists?${new URLSearchParams({ object_type: 'milestone', object_id: milestoneId })}`),
	]);

	state.milestoneDetail = {
		milestone,
		files,
		discussions,
		activity,
		checklist,
	};
}
async function loadRiskIssueDetail() {
	const riskIssueId = Number(state.riskIssueContext.id || 0);
	if (riskIssueId <= 0) {
		state.riskIssueDetail = null;
		return;
	}

	const riskIssue = await api(`/risks-issues/${riskIssueId}`);
	if (!riskIssue || !riskIssue.id) {
		state.riskIssueDetail = null;
		return;
	}

	const detailRequests = [
		api(`/files?${new URLSearchParams({ object_type: riskIssue.object_type || 'risk', object_id: riskIssueId, per_page: 25, order: 'desc' })}`),
		api(`/discussions?${new URLSearchParams({ object_type: riskIssue.object_type || 'risk', object_id: riskIssueId, per_page: 25, order: 'desc' })}`),
		api(`/activity?${new URLSearchParams({ object_type: riskIssue.object_type || 'risk', object_id: riskIssueId, page: state.riskIssueActivityPage || 1, per_page: activityPageSize() })}`),
		api(`/checklists?${new URLSearchParams({ object_type: riskIssue.object_type || 'risk', object_id: riskIssueId })}`),
	];

	const [files, discussions, activity, checklist] = await Promise.all(detailRequests);

	state.riskIssueDetail = {
		riskIssue,
		files,
		discussions,
		activity,
		checklist,
	};
}
async function loadMyWork() { state.myWork = await api('/my-work'); }
async function loadNotifications() { state.notifications = await api('/notifications'); }
async function loadDashboard() {
	state.dashboard = await api(`/dashboard?${new URLSearchParams({ activity_page: state.dashboardActivityPage || 1 })}`);
}
async function loadCalendar() { state.calendar = await api(`/calendar?${new URLSearchParams(state.calendarFilters)}`); }
async function loadWorkload() { state.workload = await api(`/workload?${new URLSearchParams(state.workloadFilters)}`); }
async function loadSettings() { state.settings = await api('/settings'); }
async function loadCollaboration() {
	const query = new URLSearchParams(state.collaborationFilters || defaultCollaborationFilters()).toString();
	const [files, discussions] = await Promise.all([api(`/files?${query}`), api(`/discussions?${query}`)]);
	state.collaboration = { files, discussions };
}

async function loadMyWorkTasks() {
	const filters = Object.assign({}, defaultMyWorkTaskFilters(), state.myWorkTasksFilters || {});
	filters.assignee_user_id = state.shell && state.shell.user ? state.shell.user.id : 0;
	state.myWorkTasksCollection = await api(`/tasks?${new URLSearchParams(filters)}`);
}

async function boot() {
	try {
		state.shell = await api('/admin-shell');
		state.notificationFilter = getStoredFilters('notifications-ui', { filter: 'unread' }).filter === 'all' ? 'all' : 'unread';
		if (hasProjectWorkspace()) {
			await loadWorkspace();
		} else if (hasTaskPage()) {
			await loadTaskDetail();
		} else if (hasMilestonePage()) {
			await loadMilestoneDetail();
		} else if (hasRiskIssuePage()) {
			await loadRiskIssueDetail();
		} else if (state.page === 'coordina-dashboard') {
			await loadDashboard();
		} else if (state.page === 'coordina-my-work') {
			const savedMyWorkView = getStoredFilters('my-work-ui', { view: 'queue' }).view;
			state.myWorkView = ['board', 'tasks'].includes(savedMyWorkView) ? savedMyWorkView : 'queue';
			state.myWorkTasksFilters = getStoredFilters('my-work-tasks', defaultMyWorkTaskFilters());
			await Promise.all([loadMyWork(), loadNotifications(), loadMyWorkTasks()]);
		} else if (state.page === 'coordina-calendar') {
			state.calendarFilters = getStoredFilters('calendar', defaultCalendarFilters());
			await loadCalendar();
		} else if (state.page === 'coordina-workload') {
			state.workloadFilters = getStoredFilters('workload', defaultWorkloadFilters());
			await loadWorkload();
		} else if (state.page === 'coordina-files-discussion') {
			state.collaborationFilters = getStoredFilters('collaboration', defaultCollaborationFilters());
			await loadCollaboration();
		} else if (state.page === 'coordina-settings') {
			await loadSettings();
		} else if (currentModule()) {
			state.filters = getFilters(currentModule().key);
			await Promise.all([loadCollection(), loadViews()]);
		}

		if (!state.notifications) {
			await loadNotifications().catch(() => {
				state.notifications = { items: [], preferences: { digest: false, project_updates: true, approval_alerts: true } };
			});
		}
	} catch (error) {
		notify('error', error.message);
	}
	state.loading = false;
	app.render();
}

Object.assign(app, {
	root,
	config,
	__,
	apiBase,
	state,
	modules,
	currentModule,
	hasProjectWorkspace,
	hasTaskPage,
	hasMilestonePage,
	hasRiskIssuePage,
	getPageMeta,
	canAccessPage,
	isDateKey,
	featureState,
	featureEnabled,
	escapeHtml,
	nice,
	dateLabel,
	dateTimeLabel,
	dateTimeInputValue,
	activityPageSize,
	isCheckedValue,
	dateKey,
	todayKey,
	weekStartKey,
	shiftDate,
	getFilters,
	saveFilters,
	getStoredFilters,
	saveStoredFilters,
	defaultCalendarFilters,
	defaultWorkloadFilters,
	defaultCollaborationFilters,
	defaultMyWorkTaskFilters,
	api,
	notify,
	loadCollection,
	loadViews,
	loadWorkspace,
	loadTaskDetail,
	loadMilestoneDetail,
	loadRiskIssueDetail,
	loadMyWork,
	loadNotifications,
	loadDashboard,
	loadCalendar,
	loadWorkload,
	loadSettings,
	loadCollaboration,
	loadMyWorkTasks,
	boot,
	render: app.render || function () {},
});

window.CoordinaAdminApp = app;
}());
