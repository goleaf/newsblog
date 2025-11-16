export function initWeatherWidgets() {
    const widgets = document.querySelectorAll('[data-widget="weather"]');
    widgets.forEach((el) => initWidget(el));
}

async function initWidget(root) {
    const endpoint = root.getAttribute('data-endpoint');
    const defaultLat = parseFloat(root.getAttribute('data-default-lat'));
    const defaultLon = parseFloat(root.getAttribute('data-default-lon'));
    const defaultLabel = root.getAttribute('data-default-label') || '—';

    const $temp = root.querySelector('[data-weather-temp]');
    const $desc = root.querySelector('[data-weather-desc]');
    const $loc = root.querySelector('[data-weather-location]');
    const $updated = root.querySelector('[data-weather-updated]');

    const render = (payload, label) => {
        if (!payload || !payload.data) return;
        const t = payload.data.temperature;
        const units = payload.data.units?.temperature || '°C';
        $temp.textContent = (t !== null && t !== undefined) ? `${Math.round(t)}${units}` : '--';
        $desc.textContent = '';
        $loc.textContent = label || defaultLabel;
        $updated.textContent = new Date().toLocaleTimeString();
    };

    const fetchWeather = async (lat, lon, label) => {
        try {
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('lat', lat);
            url.searchParams.set('lon', lon);
            const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Network');
            const json = await res.json();
            render(json, label);
        } catch {
            // Fallback to default if anything fails
            render(null, defaultLabel);
        }
    };

    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude;
                const lon = pos.coords.longitude;
                fetchWeather(lat, lon, defaultLabel);
            },
            () => fetchWeather(defaultLat, defaultLon, defaultLabel),
            { maximumAge: 10 * 60 * 1000, timeout: 5000 }
        );
    } else {
        fetchWeather(defaultLat, defaultLon, defaultLabel);
    }
}


