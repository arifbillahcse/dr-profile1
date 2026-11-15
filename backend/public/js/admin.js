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
    const widget = select.closest('[data-inline-image]');
    const preview = document.getElementById(select.dataset.imageSelect);
    const fileInput = widget?.querySelector('[data-inline-file]');
    const status = widget?.querySelector('[data-inline-status]');
    const cancel = widget?.querySelector('[data-inline-cancel]');
    const dropZone = widget?.querySelector('[data-image-dropzone]');
    const previewFrame = widget?.querySelector('[data-inline-preview]');
    const removeImage = widget?.querySelector('[data-inline-remove]');
    const removal = widget?.querySelector('[data-inline-removal]');
    const syncPreviewFrame = () => { if (previewFrame) previewFrame.hidden = preview.hidden; };
    let dragDepth = 0;
    let objectUrl;
    const releasePreview = () => { if (objectUrl) URL.revokeObjectURL(objectUrl); objectUrl = undefined; };
    const showLibraryImage = () => {
        releasePreview();
        if (fileInput) { fileInput.value = ''; fileInput.setCustomValidity(''); }
        const url = select.selectedOptions[0]?.dataset.preview;
        preview.hidden = !url;
        if (url) preview.src = url;
        else preview.removeAttribute('src');
        syncPreviewFrame();
        if (cancel) cancel.hidden = true;
        if (status) status.textContent = url ? 'Current image — choose a file to replace it here.' : 'Choose an image from your device.';
    };
    select.addEventListener('change', () => {
        if (removal) removal.value = select.value ? '0' : '1';
        showLibraryImage();
    });
    cancel?.addEventListener('click', showLibraryImage);
    removeImage?.addEventListener('click', () => {
        select.value = '';
        if (removal) removal.value = '1';
        showLibraryImage();
        status.textContent = 'Image removed. Save changes to apply the removal.';
    });
    fileInput?.addEventListener('change', () => {
        const file = fileInput.files[0];
        if (!file) { showLibraryImage(); return; }
        releasePreview();
        const error = file.size > 5 * 1024 * 1024 ? 'Choose an image smaller than 5 MB.'
            : file.type && !['image/jpeg', 'image/png', 'image/webp'].includes(file.type) ? 'Choose a JPG, PNG or WebP image.' : '';
        fileInput.setCustomValidity(error);
        cancel.hidden = false;
        if (error) {
            status.textContent = error;
            const current = select.selectedOptions[0]?.dataset.preview;
            preview.hidden = !current;
            if (current) preview.src = current;
            else preview.removeAttribute('src');
            syncPreviewFrame();
            return;
        }
        objectUrl = URL.createObjectURL(file);
        preview.src = objectUrl;
        preview.hidden = false;
        if (removal) removal.value = '0';
        syncPreviewFrame();
        status.textContent = 'New image selected. Save the form to upload and use it here.';
    });
    if (fileInput?.files.length) fileInput.dispatchEvent(new Event('change'));
    const isFileDrag = event => Array.from(event.dataTransfer?.types || []).includes('Files');
    dropZone?.addEventListener('dragenter', event => {
        if (!isFileDrag(event)) return;
        event.preventDefault();
        dragDepth++;
        dropZone.classList.add('is-dragover');
    });
    dropZone?.addEventListener('dragover', event => {
        if (!isFileDrag(event)) return;
        event.preventDefault();
        event.dataTransfer.dropEffect = 'copy';
    });
    dropZone?.addEventListener('dragleave', event => {
        dragDepth = Math.max(0, dragDepth - 1);
        if (!dragDepth) dropZone.classList.remove('is-dragover');
    });
    dropZone?.addEventListener('drop', event => {
        if (!isFileDrag(event)) return;
        event.preventDefault();
        dragDepth = 0;
        dropZone.classList.remove('is-dragover');
        const files = Array.from(event.dataTransfer.files);
        if (files.length !== 1) {
            status.textContent = 'Drop one image at a time. The current selection has not changed.';
            return;
        }
        try {
            const transfer = new DataTransfer();
            transfer.items.add(files[0]);
            fileInput.files = transfer.files;
            fileInput.dispatchEvent(new Event('change', { bubbles: true }));
        } catch {
            status.textContent = 'Please use Choose File to select this image in your browser.';
        }
    });
    select.form?.addEventListener('reset', () => setTimeout(showLibraryImage, 0));
    window.addEventListener('pagehide', releasePreview);
});
if (document.querySelector('[data-inline-image]')) {
    ['dragover', 'drop'].forEach(type => document.addEventListener(type, event => {
        if (Array.from(event.dataTransfer?.types || []).includes('Files') && !(event.target instanceof Element && event.target.closest('input[type="file"]'))) {
            event.preventDefault();
        }
    }));
}
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
