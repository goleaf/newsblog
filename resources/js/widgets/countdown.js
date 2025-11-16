export function initCountdownWidgets() {
    document.querySelectorAll('[data-widget="countdown"]').forEach((root) => initWidget(root));
}

function initWidget(root) {
    const targetStr = root.getAttribute('data-target');
    const labels = safeJson(root.getAttribute('data-labels')) || {};
    const $days = root.querySelector('[data-days]');
    const $hours = root.querySelector('[data-hours]');
    const $minutes = root.querySelector('[data-minutes]');
    const $seconds = root.querySelector('[data-seconds]');
    const $done = root.querySelector('[data-done]');
    const $updated = root.querySelector('[data-updated]');

    const target = new Date(targetStr).getTime();
    if (isNaN(target)) {
        return;
    }

    const render = () => {
        const now = Date.now();
        let delta = Math.max(0, Math.floor((target - now) / 1000));
        const d = Math.floor(delta / 86400); delta -= d * 86400;
        const h = Math.floor(delta / 3600); delta -= h * 3600;
        const m = Math.floor(delta / 60); delta -= m * 60;
        const s = delta;

        $days.textContent = String(d);
        $hours.textContent = String(h).padStart(2, '0');
        $minutes.textContent = String(m).padStart(2, '0');
        $seconds.textContent = String(s).padStart(2, '0');
        $updated.textContent = new Date().toLocaleTimeString();

        if (now >= target) {
            $done?.classList.remove('hidden');
            clearInterval(timer);
        }
    };

    render();
    const timer = setInterval(render, 1000);
}

function safeJson(s) {
    try {
        return JSON.parse(s);
    } catch {
        return null;
    }
}



