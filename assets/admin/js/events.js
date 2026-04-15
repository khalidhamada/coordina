(function () {
const app = window.CoordinaAdminApp || {};

if (!app.root || !app.state || app.eventsBound) {
	return;
}

if (typeof app.handleAdminClickAction !== 'function' || typeof app.handleAdminChangeEvent !== 'function' || typeof app.handleAdminInputEvent !== 'function' || typeof app.handleAdminSubmitEvent !== 'function') {
	return;
}

app.eventsBound = true;

const { root, boot } = app;

root.addEventListener('click', async (event) => {
	const button = event.target.closest('button[data-action], a[data-action], input[data-action], select[data-action], textarea[data-action], [role="button"][data-action]');
	if (!button) { return; }
	event.preventDefault();
	try {
		await app.handleAdminClickAction(button);
	} catch (error) {
		app.notify('error', error.message);
	}
});

root.addEventListener('change', async (event) => {
		await app.handleAdminChangeEvent(event.target);
});

root.addEventListener('input', (event) => {
		app.handleAdminInputEvent(event.target);
});

root.addEventListener('submit', async (event) => {
	event.preventDefault();
	try {
		await app.handleAdminSubmitEvent(event.target);
	} catch (error) {
		app.notify('error', error.message);
	}
});

boot();
}());
