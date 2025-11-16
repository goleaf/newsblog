import Alpine from 'alpinejs';
import {
    Chart,
    LineController,
    LineElement,
    BarController,
    BarElement,
    PointElement,
    PieController,
    DoughnutController,
    ArcElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

Chart.register(
    LineController,
    LineElement,
    BarController,
    BarElement,
    PointElement,
    PieController,
    DoughnutController,
    ArcElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
    Filler,
);

function parseCsv(csv) {
    if (!csv || typeof csv !== 'string') {
        return null;
    }
    const lines = csv.trim().split(/\r?\n/);
    const [header, ...rows] = lines;
    const headers = header.split(',').map((h) => h.trim());
    // Expect simple "label,value" CSV
    const labelIdx = headers.findIndex((h) => /label/i.test(h));
    const valueIdx = headers.findIndex((h) => /value/i.test(h));
    if (labelIdx === -1 || valueIdx === -1) {
        return null;
    }
    const labels = [];
    const data = [];
    rows.forEach((row) => {
        const cols = row.split(',');
        if (cols.length >= Math.max(labelIdx, valueIdx) + 1) {
            labels.push(cols[labelIdx].trim());
            data.push(Number(cols[valueIdx]));
        }
    });
    return {
        labels,
        datasets: [
            {
                label: 'Series',
                data,
                fill: true,
            },
        ],
    };
}

function createChartComponent({ type = 'line', data = {}, csv = null, options = {} }) {
    return {
        type,
        data,
        csv,
        options,
        chart: null,
        init(root) {
            const canvas = root.querySelector('canvas');
            let chartData = this.data && Object.keys(this.data).length ? this.data : parseCsv(this.csv);
            if (!chartData) {
                chartData = { labels: [], datasets: [] };
            }
            const chartType = this.type === 'area' ? 'line' : this.type;
            if (this.type === 'area') {
                chartData.datasets = chartData.datasets.map((ds) => ({ ...ds, fill: true }));
            }
            const baseOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'top' },
                    tooltip: { enabled: true, mode: 'nearest', intersect: false },
                },
                interaction: { mode: 'nearest', intersect: false },
                scales: {
                    x: { display: true },
                    y: { display: true, beginAtZero: true },
                },
            };
            const mergedOptions = { ...baseOptions, ...this.options };
            this.chart = new Chart(canvas, {
                type: chartType,
                data: chartData,
                options: mergedOptions,
            });
        },
        destroy() {
            this.chart?.destroy();
            this.chart = null;
        },
    };
}

Alpine.data('chartComponent', createChartComponent);

export default createChartComponent;



