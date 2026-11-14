document.querySelectorAll('[data-toggle-password]').forEach(button => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.togglePassword);
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
        button.querySelector('i').className = visible ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
});
document.querySelector('[data-fullscreen]')?.addEventListener('click', async () => {
    try {
        if (document.fullscreenElement) await document.exitFullscreen();
        else await document.documentElement.requestFullscreen();
    } catch { /* Fullscreen may be unavailable in embedded browsers. */ }
});

const sidebarToggle = document.querySelector('[data-lte-toggle="sidebar"]');
if (sidebarToggle) {
    const mobileSidebar = window.matchMedia('(max-width: 991px)');
    const closeSidebar = () => {
        document.body.classList.remove('sidebar-open');
        document.body.classList.add('sidebar-collapse');
    };
    mobileSidebar.addEventListener('change', event => { if (event.matches) closeSidebar(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && mobileSidebar.matches) closeSidebar(); });
    const syncExpanded = () => sidebarToggle.setAttribute('aria-expanded', String(document.body.classList.contains('sidebar-open')));
    new MutationObserver(syncExpanded).observe(document.body, { attributes: true, attributeFilter: ['class'] });
    syncExpanded();
}

document.querySelectorAll('[data-image-select]').forEach(select => {
    select.addEventListener('change', () => {
        const preview = document.getElementById(select.dataset.imageSelect);
        const url = select.selectedOptions[0]?.dataset.preview;
        preview.hidden = !url;
        if (url) preview.src = url;
        else preview.removeAttribute('src');
    });
});
document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => { if (!window.confirm(form.dataset.confirm)) event.preventDefault(); });
});

const scheduleRows = document.getElementById('schedule-rows');
const addSchedule = document.getElementById('add-schedule');
if (scheduleRows && addSchedule) {
    let nextSchedule = [...scheduleRows.querySelectorAll('select')].reduce((next, select) => Math.max(next, Number(select.name.match(/\[(\d+)\]/)?.[1] ?? -1) + 1), 0);
    const syncScheduleLimit = () => { addSchedule.disabled = scheduleRows.children.length >= 28; };
    addSchedule.addEventListener('click', () => {
        if (scheduleRows.children.length >= 28) return;
        const template = document.getElementById('schedule-row-template');
        scheduleRows.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(nextSchedule++)));
        syncScheduleLimit();
    });
    scheduleRows.addEventListener('click', event => {
        const remove = event.target.closest('[data-remove-schedule]');
        if (remove) { remove.closest('[data-schedule-row]').remove(); syncScheduleLimit(); }
    });
    syncScheduleLimit();
}
const currentPosition = document.querySelector('[data-current-position]');
const endDate = document.querySelector('[data-end-date]');
if (currentPosition && endDate) {
    const syncEndDate = () => {
        endDate.disabled = currentPosition.checked;
        if (currentPosition.checked) endDate.value = '';
    };
    currentPosition.addEventListener('change', syncEndDate);
    syncEndDate();
}
