(function () {
const app = window.CoordinaAdminApp || {};

if (!app.root || !app.state) {
	return;
}

const { state, escapeHtml, __, nice, dateLabel, dateTimeLabel } = app;
const baseWorkspaceTabBody = app.workspaceTabBody;

function fileExtension(item) {
	const raw = String(item.file_name || item.attachment_title || '').trim();
	if (!raw || raw.indexOf('.') === -1) {
		return '';
	}
	return raw.split('.').pop().toLowerCase();
}

function fileSizeLabel(bytes) {
	const size = Number(bytes || 0);
	if (!Number.isFinite(size) || size <= 0) {
		return __('Unknown size', 'coordina');
	}
	const units = ['B', 'KB', 'MB', 'GB'];
	let value = size;
	let unitIndex = 0;
	while (value >= 1024 && unitIndex < units.length - 1) {
		value /= 1024;
		unitIndex += 1;
	}
	return `${value >= 10 || unitIndex === 0 ? Math.round(value) : value.toFixed(1)} ${units[unitIndex]}`;
}

function fileTypeDashicon(item) {
	const extension = fileExtension(item);
	const mimeGroup = String(item.mime_group || '').toLowerCase();
	if (mimeGroup === 'image') {
		return 'dashicons-format-image';
	}
	if (mimeGroup === 'audio') {
		return 'dashicons-format-audio';
	}
	if (mimeGroup === 'video') {
		return 'dashicons-format-video';
	}
	if (['pdf'].includes(extension)) {
		return 'dashicons-media-document';
	}
	if (['zip', 'rar', '7z', 'tar', 'gz'].includes(extension)) {
		return 'dashicons-media-archive';
	}
	if (['csv', 'xls', 'xlsx'].includes(extension)) {
		return 'dashicons-media-spreadsheet';
	}
	if (['doc', 'docx', 'txt', 'rtf', 'md'].includes(extension)) {
		return 'dashicons-media-text';
	}
	if (['js', 'ts', 'css', 'html', 'php', 'json', 'xml', 'yml', 'yaml'].includes(extension)) {
		return 'dashicons-media-code';
	}
	return 'dashicons-media-default';
}

function fileTypeLabel(item) {
	const extension = fileExtension(item);
	if (extension) {
		return extension.toUpperCase();
	}
	return nice(item.mime_group || __('file', 'coordina'));
}

function fileDrawerTrigger(item, className, label) {
	return `<button class="${className}" data-action="open-record" data-module="files" data-id="${item.id}">${label}</button>`;
}

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
	const options = arguments.length > 3 && arguments[3] ? arguments[3] : {};
	const chips = [];
	if (options.showObjectType !== false) {
		chips.push(`<span class="coordina-status-badge">${escapeHtml(nice(item.object_type || 'context'))}</span>`);
	}
	if (options.showContextLink !== false) {
		chips.push(collaborationContextButton(item, tab));
	}
	if (options.showProjectLabel !== false && item && item.object_type !== 'project') {
		chips.push(collaborationProjectButton(item, tab));
	}
	if (options.showAuthor !== false) {
		chips.push(`<span>${escapeHtml(authorLabel)}</span>`);
	}
	chips.push(`<span>${escapeHtml(dateTimeLabel(item.created_at))}</span>`);
	return `<div class="coordina-work-meta">${chips.filter(Boolean).join('')}</div>`;
}

function fileList(items, emptyMessage, options) {
	const metaOptions = Object.assign({ showAuthor: true }, options && options.metaOptions ? options.metaOptions : {});
	return items.length ? `<div class="coordina-file-card-grid">${items.map((item) => `<article class="coordina-file-card"><div class="coordina-file-card__head"><span class="coordina-file-card__type dashicons ${fileTypeDashicon(item)}" aria-hidden="true"></span><div class="coordina-file-card__title-block">${fileDrawerTrigger(item, 'coordina-link-button coordina-file-card__title', escapeHtml(item.file_name || item.attachment_title || __('File', 'coordina')))}<div class="coordina-file-card__subline"><span class="coordina-status-badge">${escapeHtml(fileTypeLabel(item))}</span><span>${escapeHtml(fileSizeLabel(item.file_size))}</span></div></div><div class="coordina-file-card__actions">${fileDrawerTrigger(item, 'coordina-file-card__icon-button', `<span class="dashicons dashicons-edit" aria-hidden="true"></span><span class="screen-reader-text">${escapeHtml(__('Open file details', 'coordina'))}</span>`)}${item.attachment_url ? `<a class="coordina-file-card__icon-button" href="${escapeHtml(item.attachment_url)}" download="${escapeHtml(item.file_name || item.attachment_title || '')}"><span class="dashicons dashicons-download" aria-hidden="true"></span><span class="screen-reader-text">${escapeHtml(__('Download file', 'coordina'))}</span></a>` : ''}</div></div>${collaborationMeta(item, 'files', item.created_by_label || __('Unknown uploader', 'coordina'), metaOptions)}${item.note ? `<p class="coordina-file-card__note">${escapeHtml(item.note)}</p>` : ''}</article>`).join('')}</div>` : `<p class="coordina-empty-inline">${escapeHtml(emptyMessage)}</p>`;
}

function discussionTimeline(items, emptyMessage, options) {
	const metaOptions = Object.assign({ showAuthor: false }, options && options.metaOptions ? options.metaOptions : {});
	return items.length ? `<ul class="coordina-timeline">${items.map((item) => `<li><strong>${escapeHtml(item.created_by_label || __('System', 'coordina'))}</strong><p>${escapeHtml(item.body || item.excerpt || '')}</p>${collaborationMeta(item, 'discussion', item.created_by_label || __('System', 'coordina'), metaOptions)}</li>`).join('')}</ul>` : `<p class="coordina-empty-inline">${escapeHtml(emptyMessage)}</p>`;
}

function rankingChart(series, emptyMessage) {
	if (!series.length) {
		return `<p class="coordina-empty-inline">${escapeHtml(emptyMessage)}</p>`;
	}
	const max = Math.max(1, ...series.map((item) => Number(item.count || 0)));
	return `<div class="coordina-activity-chart coordina-activity-chart--ranking">${series.map((item) => `<div class="coordina-activity-chart__row"><div class="coordina-activity-chart__row-head"><span>${escapeHtml(item.label || '')}</span><strong>${Number(item.count || 0)}</strong></div><span class="coordina-activity-chart__row-bar"><span style="width:${Math.max(10, Math.round((Number(item.count || 0) / max) * 100))}%"></span></span></div>`).join('')}</div>`;
}

function columnsChart(series) {
	const max = Math.max(1, ...series.map((item) => Number(item.count || 0)));
	return `<div class="coordina-activity-chart coordina-activity-chart--columns" style="grid-template-columns:repeat(${Math.max(1, series.length)}, minmax(0, 1fr))">${series.map((item) => `<div class="coordina-activity-chart__column"><span class="coordina-activity-chart__track"><span class="coordina-activity-chart__bar" style="transform:scaleY(${Math.max(0.08, Number(item.count || 0) / max)})"></span></span><strong>${Number(item.count || 0)}</strong><span>${escapeHtml(item.label || '')}</span></div>`).join('')}</div>`;
}

function sortSeriesByCount(series) {
	return series.slice().sort((left, right) => {
		const countDelta = Number(right.count || 0) - Number(left.count || 0);
		if (countDelta !== 0) {
			return countDelta;
		}
		return String(left.label || '').localeCompare(String(right.label || ''));
	});
}

function parseCreatedAt(value) {
	const raw = String(value || '').trim();
	if (!raw) {
		return null;
	}
	const parsed = new Date(raw.replace(' ', 'T'));
	return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function bucketDateKey(date, mode) {
	const year = date.getUTCFullYear();
	const month = `${date.getUTCMonth() + 1}`.padStart(2, '0');
	const day = `${date.getUTCDate()}`.padStart(2, '0');
	if (mode === 'month') {
		return `${year}-${month}-01`;
	}
	if (mode === 'week') {
		const weekDate = new Date(Date.UTC(year, date.getUTCMonth(), date.getUTCDate()));
		const weekDay = weekDate.getUTCDay() || 7;
		weekDate.setUTCDate(weekDate.getUTCDate() - weekDay + 1);
		return `${weekDate.getUTCFullYear()}-${`${weekDate.getUTCMonth() + 1}`.padStart(2, '0')}-${`${weekDate.getUTCDate()}`.padStart(2, '0')}`;
	}
	return `${year}-${month}-${day}`;
}

function bucketLabel(key, mode) {
	const parsed = new Date(`${key}T00:00:00Z`);
	if (Number.isNaN(parsed.getTime())) {
		return key;
	}
	if (mode === 'month') {
		return new Intl.DateTimeFormat(document.documentElement.lang || undefined, { month: 'short', year: 'numeric', timeZone: 'UTC' }).format(parsed);
	}
	if (mode === 'week') {
		return `${__('Week of', 'coordina')} ${new Intl.DateTimeFormat(document.documentElement.lang || undefined, { month: 'short', day: 'numeric', timeZone: 'UTC' }).format(parsed)}`;
	}
	return new Intl.DateTimeFormat(document.documentElement.lang || undefined, { month: 'short', day: 'numeric', timeZone: 'UTC' }).format(parsed);
}

function chooseDateGrouping(items) {
	const dates = items.map((item) => parseCreatedAt(item.created_at)).filter(Boolean).sort((left, right) => left.getTime() - right.getTime());
	if (!dates.length) {
		return 'day';
	}
	const spanDays = Math.max(0, Math.round((dates[dates.length - 1].getTime() - dates[0].getTime()) / 86400000));
	if (spanDays <= 21 || dates.length <= 8) {
		return 'day';
	}
	if (spanDays <= 120) {
		return 'week';
	}
	return 'month';
}

function seriesFromCounts(items, keyFn, labelFn, limit, preserveChronology = false) {
	const counts = items.reduce((carry, item) => {
		const key = keyFn(item);
		if (!key) {
			return carry;
		}
		carry[key] = (carry[key] || 0) + 1;
		return carry;
	}, {});
	let series = Object.keys(counts).map((key) => ({ key, label: labelFn(key), count: counts[key] }));
	if (preserveChronology) {
		series = series.sort((left, right) => String(left.key).localeCompare(String(right.key))).slice(-limit);
	} else {
		series = sortSeriesByCount(series).slice(0, limit);
	}
	return series;
}

function updateUserSeries(items) {
	return seriesFromCounts(items, (item) => String(item.created_by_label || __('System', 'coordina')), (key) => key, 5);
}

function updateDateSeries(items) {
	const mode = chooseDateGrouping(items);
	return {
		mode,
		series: seriesFromCounts(items, (item) => {
			const parsed = parseCreatedAt(item.created_at);
			return parsed ? bucketDateKey(parsed, mode) : '';
		}, (key) => bucketLabel(key, mode), 8, true),
	};
}

function fileTypeSeries(items) {
	return seriesFromCounts(items, (item) => String(item.object_type || 'project'), (key) => nice(key), 6);
}

function childContextItems(items) {
	return (items || []).filter((item) => String(item.object_type || '').toLowerCase() !== 'project');
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
	return `<section class="coordina-page">${app.pageHeading ? app.pageHeading('coordina-files-discussion', `<button class="button" data-action="open-route" data-page="coordina-my-work">${escapeHtml(__('Go to My Work', 'coordina'))}</button><button class="button button-primary" data-action="open-route" data-page="coordina-projects">${escapeHtml(__('Open projects', 'coordina'))}</button>`, { title: __('Files & Discussions', 'coordina'), description: __('Browse recent files and updates, then open the related work item when needed.', 'coordina') }) : `<div class="coordina-action-bar"><div><h2>${escapeHtml(__('Files & Discussions', 'coordina'))}</h2></div></div>`}<section class="coordina-card coordina-card--notice"><p>${escapeHtml(__('Use this screen to review recent files and updates. Add new files or updates from the related project or work item.', 'coordina'))}</p></section><div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-summary-chip"><strong>${Number(files.total || 0)}</strong>${escapeHtml(__('Files in view', 'coordina'))}</span><span class="coordina-summary-chip"><strong>${Number(discussions.total || 0)}</strong>${escapeHtml(__('Updates in view', 'coordina'))}</span></div><div class="coordina-filter-bar coordina-card coordina-filter-bar--tasks"><input type="search" name="collaboration-search" value="${escapeHtml(filters.search || '')}" placeholder="${escapeHtml(__('Search files or updates', 'coordina'))}" /><select name="collaboration-object-type"><option value="">${escapeHtml(__('All contexts', 'coordina'))}</option>${objectOptions}</select><select name="collaboration-project"><option value="">${escapeHtml(__('All projects', 'coordina'))}</option>${projectOptions}</select><select name="collaboration-recency">${recencyOptions}</select><button class="button" data-action="collaboration-apply">${escapeHtml(__('Apply', 'coordina'))}</button></div><div class="coordina-columns"><section class="coordina-card coordina-card--wide"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Files to review', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Open a file or its related work item when you need more context.', 'coordina'))}</p></div></div>${fileList(files.items || [], __('No files match these filters yet.', 'coordina'))}</section><section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Updates to review', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Read the latest updates, then open the related work item if needed.', 'coordina'))}</p></div></div>${discussionTimeline(discussions.items || [], __('No updates match these filters yet.', 'coordina'))}</section></div></section>`;
}

app.workspaceTabBody = function (tab, project, overview, taskSummary) {
	if (tab === 'files') {
		const files = state.workspace && state.workspace.fileCollection ? state.workspace.fileCollection.items || [] : [];
		const summary = state.workspace && state.workspace.fileSummary ? state.workspace.fileSummary : {};
		const workspaceActions = state.workspace && state.workspace.actions ? state.workspace.actions : {};
		const fileActions = collaborationActionButtons({ object_type: 'project', object_id: project.id || '', object_label: project.title || __('Project workspace', 'coordina') }, { canPostUpdate: false, canAttachFile: !!workspaceActions.canAttachFile });
		const typeSeries = fileTypeSeries(files);
		return `<div class="coordina-columns"><section class="coordina-card coordina-card--wide"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Project files', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Files attached to this project and its related work.', 'coordina'))}</p></div>${fileActions}</div>${fileList(files, __('No project files yet. Attach the first file to keep project context together.', 'coordina'))}</section><div class="coordina-project-side-stack"><section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Files by item type', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See which project records are carrying the most file context.', 'coordina'))}</p></div></div><div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-summary-chip"><strong>${Number(summary.total || 0)}</strong>${escapeHtml(__('Linked files', 'coordina'))}</span></div>${rankingChart(typeSeries, __('No file distribution to chart yet.', 'coordina'))}</section></div></div>`;
	}
	if (tab === 'discussion') {
		const discussions = state.workspace && state.workspace.discussionCollection ? state.workspace.discussionCollection.items || [] : [];
		const workspaceActions = state.workspace && state.workspace.actions ? state.workspace.actions : {};
		const discussionActions = collaborationActionButtons({ object_type: 'project', object_id: project.id || '', object_label: project.title || __('Project workspace', 'coordina') }, { canPostUpdate: false, canAttachFile: false });
		const authorSeries = updateUserSeries(discussions);
		const datedSeries = updateDateSeries(discussions);
		return `<div class="coordina-columns"><section class="coordina-card coordina-card--wide"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Project updates', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('Updates from this project and its related work appear here together.', 'coordina'))}</p></div>${discussionActions}</div>${discussionTimeline(discussions, __('No updates have been posted for this project yet.', 'coordina'), { metaOptions: { showProjectLabel: false } })}</section><div class="coordina-project-side-stack"><section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Updates by person', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(__('See who is contributing most often across the project and its active work items.', 'coordina'))}</p></div></div><div class="coordina-summary-row coordina-summary-row--subtle"><span class="coordina-summary-chip"><strong>${Number(discussions.length || 0)}</strong>${escapeHtml(__('Updates logged', 'coordina'))}</span></div>${rankingChart(authorSeries, __('No update activity to chart yet.', 'coordina'))}</section><section class="coordina-card"><div class="coordina-section-header"><div><h3>${escapeHtml(__('Updates over time', 'coordina'))}</h3><p class="coordina-section-note">${escapeHtml(`${__('Grouped by', 'coordina')} ${nice(datedSeries.mode || 'day')}`)}</p></div></div>${datedSeries.series.length ? columnsChart(datedSeries.series) : `<p class="coordina-empty-inline">${escapeHtml(__('No timeline data to chart yet.', 'coordina'))}</p>`}</section></div></div>`;
	}
	return baseWorkspaceTabBody(tab, project, overview, taskSummary);
};

Object.assign(app, {
	collaborationContextButton,
	collaborationProjectButton,
	collaborationMeta,
	fileList,
	discussionTimeline,
	rankingChart,
	columnsChart,
	updateUserSeries,
	updateDateSeries,
	fileTypeSeries,
	childContextItems,
	collaborationActionButtons,
	collaborationPage,
});

window.CoordinaAdminApp = app;
}());
