export function initStockTickerWidgets() {
    document.querySelectorAll('[data-widget="stock-ticker"]').forEach((root) => initWidget(root));
}

function rowTemplate(item) {
    const change = item.change ?? 0;
    const pct = item.change_percent ?? 0;
    const isUp = change >= 0;
    const color = isUp ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
    const sign = isUp ? '+' : '';
    const href = `https://finance.yahoo.com/quote/${encodeURIComponent(item.symbol)}`;
    return `
    <a href="${href}" target="_blank" rel="noopener" class="flex items-center justify-between rounded-md px-2 py-1 hover:bg-gray-50 dark:hover:bg-gray-700/40">
        <span class="font-medium text-gray-900 dark:text-gray-100">${item.symbol}</span>
        <span class="text-gray-700 dark:text-gray-200">${item.price?.toFixed(2) ?? '--'}</span>
        <span class="${color} text-sm">${sign}${change.toFixed(2)} (${sign}${pct.toFixed(2)}%)</span>
    </a>`;
}

async function initWidget(root) {
    const endpoint = root.getAttribute('data-endpoint');
    const symbols = root.getAttribute('data-symbols') || 'AAPL,MSFT,GOOG';
    const $list = root.querySelector('[data-ticker-list]');
    const $refresh = root.querySelector('[data-refresh]');

    const load = async () => {
        try {
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('symbols', symbols);
            const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Network');
            const { data } = await res.json();
            $list.innerHTML = (data || []).map(rowTemplate).join('');
        } catch {
            $list.innerHTML = '';
        }
    };

    $refresh?.addEventListener('click', (e) => { e.preventDefault(); load(); });
    await load();
    setInterval(load, 60_000);
}


