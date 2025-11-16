import Chart from 'chart.js/auto';

function renderPageLoadChart(ctx, pageLoads) {
	if (!pageLoads || pageLoads.length === 0) {
		return;
	}
	const labels = pageLoads.map((p) => p.hour);
	const data = pageLoads.map((p) => p.average);
	// eslint-disable-next-line no-new
	new Chart(ctx, {
		type: 'line',
		data: {
			labels,
			datasets: [
				{
					label: 'Average Load Time (ms)',
					data,
					borderColor: 'rgb(59, 130, 246)',
					backgroundColor: 'rgba(59, 130, 246, 0.1)',
					tension: 0.4,
				},
			],
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: { legend: { display: true } },
			scales: {
				y: {
					beginAtZero: true,
					title: { display: true, text: 'Milliseconds' },
				},
			},
		},
	});
}

function renderCacheStatsChart(ctx, cacheStats) {
	if (!cacheStats || cacheStats.length === 0) {
		return;
	}
	const labels = cacheStats.map((s) => s.date);
	const hits = cacheStats.map((s) => s.hits);
	const misses = cacheStats.map((s) => s.misses);
	// eslint-disable-next-line no-new
	new Chart(ctx, {
		type: 'bar',
		data: {
			labels,
			datasets: [
				{
					label: 'Cache Hits',
					data: hits,
					backgroundColor: 'rgba(34, 197, 94, 0.5)',
					borderColor: 'rgb(34, 197, 94)',
					borderWidth: 1,
				},
				{
					label: 'Cache Misses',
					data: misses,
					backgroundColor: 'rgba(239, 68, 68, 0.5)',
					borderColor: 'rgb(239, 68, 68)',
					borderWidth: 1,
				},
			],
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: { legend: { display: true } },
			scales: {
				y: {
					beginAtZero: true,
					title: { display: true, text: 'Count' },
				},
			},
		},
	});
}

window.loadPageModule = (page) => {
	if (page !== 'performance') {
		return;
	}
	const dataEl = document.getElementById('performance-data');
	if (!dataEl) {
		return;
	}
	let data;
	try {
		data = JSON.parse(dataEl.textContent || '{}');
	} catch {
		data = {};
	}
	const pageLoadCanvas = document.getElementById('pageLoadChart');
	const cacheStatsCanvas = document.getElementById('cacheStatsChart');
	if (pageLoadCanvas) {
		renderPageLoadChart(pageLoadCanvas.getContext('2d'), data.pageLoads || []);
	}
	if (cacheStatsCanvas) {
		renderCacheStatsChart(cacheStatsCanvas.getContext('2d'), data.cacheStats || []);
	}
};


