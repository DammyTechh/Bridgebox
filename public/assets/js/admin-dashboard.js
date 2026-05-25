(() => {
    const container = document.querySelector('[data-refresh-url]');
    if (!container) {
        return;
    }

    const refreshUrl = container.dataset.refreshUrl;
    const interval = parseInt(container.dataset.refreshInterval || '0', 10);
    const autoRefresh = container.dataset.autoRefresh !== 'off';

    if (!refreshUrl || !autoRefresh || Number.isNaN(interval) || interval <= 0) {
        return;
    }

    const updateTile = (key, value) => {
        document.querySelectorAll(`[data-status="${key}"]`).forEach((target) => {
            target.textContent = value ?? 'Unknown';
        });
    };

    // Hotspot: flips badge class (hotspot-on / hotspot-off) AND the tile
    // accent CSS variable so the colour changes live without a page reload.
    const updateHotspot = (value) => {
        const isOn = String(value || '').toLowerCase().startsWith('on');
        document.querySelectorAll('[data-status="hotspot"]').forEach((el) => {
            el.textContent = value ?? 'Unknown';
            el.classList.toggle('hotspot-on',  isOn);
            el.classList.toggle('hotspot-off', !isOn);
        });
        document.querySelectorAll('[data-hotspot-tab]').forEach((tab) => {
            tab.style.setProperty('--accent', isOn ? '#56c1a7' : '#e56b6f');
            tab.classList.toggle('tab--on',  isOn);
            tab.classList.toggle('tab--off', !isOn);
        });
    };

    const updateToggle = (key, value) => {
        const toggle = document.querySelector(`[data-toggle-target="${key}"]`);
        if (!toggle || toggle.dataset.locked === 'true') {
            return;
        }

        const text = String(value || '').toLowerCase();
        let isOn = false;
        if (key === 'server') {
            isOn = text.includes('running');
        } else if (key === 'hotspot') {
            isOn = text.startsWith('on');
        }

        toggle.checked = isOn;
    };

    const formatDuration = (totalSeconds) => {
        if (typeof totalSeconds !== 'number' || Number.isNaN(totalSeconds)) {
            return 'Unknown';
        }

        const days = Math.floor(totalSeconds / 86400);
        const hours = Math.floor((totalSeconds % 86400) / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        const parts = [];
        if (days > 0) {
            parts.push(`${days}d`);
        }
        if (hours > 0) {
            parts.push(`${hours}h`);
        }
        parts.push(`${minutes}m`);
        parts.push(`${seconds}s`);

        return parts.join(' ');
    };

    let uptimeSeconds = null;
    let uptimeTimer = null;

    const updateUptime = () => {
        if (uptimeSeconds === null) {
            return;
        }
        updateTile('uptime', formatDuration(uptimeSeconds));
        uptimeSeconds += 1;
    };

    const primeUptimeFromDom = () => {
        const uptimeEl = document.querySelector('[data-status="uptime"]');
        if (!uptimeEl) {
            return;
        }
        const raw = uptimeEl.getAttribute('data-uptime-seconds');
        if (raw) {
            const parsed = Number.parseInt(raw, 10);
            if (!Number.isNaN(parsed)) {
                uptimeSeconds = parsed;
            }
        }
    };

    const applyStatus = (data) => {
        if (!data) {
            return;
        }
        updateTile('server', data.server);
        updateToggle('server', data.server);
        updateHotspot(data.hotspot);
        updateTile('devices', data.devices);
        updateTile('app_health', data.app_health);
        updateTile('storage', data.storage);
        updateTile('power', data.power);
        if (typeof data.uptime_seconds === 'number') {
            uptimeSeconds = data.uptime_seconds;
            updateTile('uptime', formatDuration(uptimeSeconds));
        } else {
            updateTile('uptime', data.uptime);
        }
        updateTile('last_update', data.last_update);
    };

    const fetchStatus = async () => {
        try {
            const response = await fetch(refreshUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json().catch(() => null);
            applyStatus(payload);
        } catch (error) {
            // Silent failure for offline-friendly behavior.
        }
    };

    primeUptimeFromDom();
    updateUptime();

    if (uptimeTimer) {
        clearInterval(uptimeTimer);
    }

    uptimeTimer = window.setInterval(updateUptime, 1000);

    fetchStatus();
    window.setInterval(fetchStatus, interval);
})();
