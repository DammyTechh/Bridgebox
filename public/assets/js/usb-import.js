/**
 * BridgeBox USB Panel — admin & teacher dashboards.
 *
 * Shows detected flash drive storage info (label, path, size, file-type counts)
 * and the imported content library grouped by folder type.
 *
 * Copy / import controls have been removed from this panel.
 * To import content from a flash drive, use Create Lesson on the admin or
 * teacher screens where the full copy workflow is available.
 *
 * Vanilla JS only — no build step.
 */
(() => {
    const root = document.querySelector('[data-usb-panel]');
    if (!root) return;

    const urls = {
        drives : root.dataset.urlDrives || null,
        list   : root.dataset.urlList   || null,
    };

    const $ = (sel) => root.querySelector(sel);

    const els = {
        drivesList   : $('[data-usb-drives]'),
        empty        : $('[data-usb-empty]'),
        library      : $('[data-usb-library]'),
        libraryEmpty : $('[data-usb-library-empty]'),
        refreshBtn   : $('[data-usb-refresh]'),
    };

    let pollTimer = null;
    let listTimer = null;

    const CATEGORY_META = {
        video    : { icon: 'fa-film',        label: 'Video',    folder: 'video folder'    },
        audio    : { icon: 'fa-music',       label: 'Audio',    folder: 'audio folder'    },
        document : { icon: 'fa-file-lines',  label: 'Document', folder: 'document folder' },
        image    : { icon: 'fa-image',       label: 'Image',    folder: 'image folder'    },
        archive  : { icon: 'fa-file-zipper', label: 'Archive',  folder: 'archive folder'  },
        other    : { icon: 'fa-file',        label: 'Other',    folder: 'other folder'    },
    };

    const CATEGORY_ORDER = ['video', 'audio', 'document', 'image', 'archive', 'other'];

    const escapeHtml = (s) => {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    };

    // ── Render: detected drives ──────────────────────────────────────────────
    // Shows drive info + file-type tags only. No copy button.

    const renderDrives = (drives) => {
        if (!els.drivesList) return;
        if (!drives || drives.length === 0) {
            els.drivesList.innerHTML = '';
            if (els.empty) els.empty.hidden = false;
            return;
        }
        if (els.empty) els.empty.hidden = true;

        els.drivesList.innerHTML = drives.map((d) => {
            const ins    = d.inspect || { files: 0, size_human: '—' };
            const counts = ins.by_category || {};
            const tags   = Object.keys(counts).map((c) => {
                const meta = CATEGORY_META[c] || CATEGORY_META.other;
                return `<span class="usb-tag">` +
                    `<i class="fa-solid ${meta.icon}"></i> ` +
                    `${counts[c]} ${escapeHtml(meta.label)}</span>`;
            }).join('');

            return `
            <div class="usb-drive">
                <div class="usb-drive-info">
                    <div class="usb-drive-icon">
                        <i class="fa-brands fa-usb" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p class="usb-drive-name">${escapeHtml(d.label)}</p>
                        <span class="usb-drive-meta">
                            ${escapeHtml(d.path)} &middot; ${escapeHtml(d.size_human)} &middot; ${ins.files || 0} file(s)
                        </span>
                        <div class="usb-tags">${tags}</div>
                    </div>
                </div>
            </div>`;
        }).join('');
    };

    // ── Render: imported content library ────────────────────────────────────

    const renderLibrary = (items) => {
        if (!els.library) return;
        if (!items || items.length === 0) {
            els.library.innerHTML = '';
            if (els.libraryEmpty) els.libraryEmpty.hidden = false;
            return;
        }
        if (els.libraryEmpty) els.libraryEmpty.hidden = true;

        // Group by category
        const groups = {};
        items.forEach((it) => {
            const cat = it.category || 'other';
            if (!groups[cat]) groups[cat] = [];
            groups[cat].push(it);
        });

        const html = CATEGORY_ORDER
            .filter((cat) => groups[cat] && groups[cat].length > 0)
            .map((cat) => {
                const meta  = CATEGORY_META[cat] || CATEGORY_META.other;
                const cards = groups[cat].map((it) => `
                    <div class="usb-item">
                        <div class="usb-item-icon usb-cat-${escapeHtml(cat)}">
                            <i class="fa-solid ${meta.icon}" aria-hidden="true"></i>
                        </div>
                        <div class="usb-item-body">
                            <p class="usb-item-name" title="${escapeHtml(it.name)}">${escapeHtml(it.name)}</p>
                            <span class="usb-item-meta">${escapeHtml(it.size)}</span>
                        </div>
                        <a class="btn ghost btn-small" href="${escapeHtml(it.url)}"
                           target="_blank" rel="noopener" title="Preview / play">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <a class="btn ghost btn-small" href="${escapeHtml(it.url)}"
                           download title="Download">
                            <i class="fa-solid fa-download"></i>
                        </a>
                    </div>`).join('');

                return `
                <div class="usb-folder-group">
                    <h5 class="usb-folder-title">
                        <i class="fa-solid ${meta.icon}" aria-hidden="true"></i>
                        ${escapeHtml(meta.folder)}
                        <span class="usb-folder-count">${groups[cat].length}</span>
                    </h5>
                    <div class="usb-library-grid">${cards}</div>
                </div>`;
            }).join('');

        els.library.innerHTML = html;
    };

    // ── Data loaders ─────────────────────────────────────────────────────────

    const fetchJson = async (url) => {
        if (!url) throw new Error('No URL configured');
        const r = await fetch(url, {
            credentials : 'same-origin',
            headers     : {
                'Accept'             : 'application/json',
                'X-Requested-With'   : 'XMLHttpRequest',
            },
        });
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    };

    const loadDrives = async () => {
        if (!urls.drives) return;
        try {
            const data = await fetchJson(urls.drives);
            renderDrives(data.drives || []);
        } catch (e) { /* silent */ }
    };

    const loadLibrary = async () => {
        if (!urls.list || !els.library) return;
        try {
            const data = await fetchJson(urls.list);
            renderLibrary(data.items || []);
        } catch (e) { /* silent */ }
    };

    // ── Polling ───────────────────────────────────────────────────────────────

    const startPolling = () => {
        stopPolling();
        // Refresh drives every 5 s so newly inserted flash drives appear quickly.
        if (urls.drives) {
            pollTimer = setInterval(loadDrives, 5000);
        }
        // Refresh library every 15 s.
        if (urls.list) {
            listTimer = setInterval(loadLibrary, 15000);
        }
    };

    const stopPolling = () => {
        if (pollTimer) clearInterval(pollTimer);
        if (listTimer) clearInterval(listTimer);
    };

    // ── Boot ──────────────────────────────────────────────────────────────────

    if (els.refreshBtn) {
        els.refreshBtn.addEventListener('click', loadDrives);
    }

    loadDrives();
    loadLibrary();
    startPolling();
})();
