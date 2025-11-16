export default {
    init() {
        const container = document.getElementById('views-over-time-chart');
        if (!container) return;
        const canvas = document.getElementById('viewsOverTimeCanvas');
        if (!canvas) return;

        const raw = container.getAttribute('data-views-over-time');
        if (!raw) return;
        let data = [];
        try {
            data = JSON.parse(raw);
        } catch (e) {
            console.warn('Invalid views_over_time data');
            return;
        }

        // Prepare labels and values
        const labels = data.map(d => d.date);
        const values = data.map(d => Number(d.views));

        // Simple canvas bar chart (no external deps)
        const ctx = canvas.getContext('2d');
        const width = canvas.width = canvas.clientWidth || 800;
        const height = canvas.height = canvas.clientHeight || 240;
        const padding = 32;
        const chartWidth = width - padding * 2;
        const chartHeight = height - padding * 2;
        const maxVal = Math.max(...values, 1);
        const barWidth = Math.max(4, Math.floor(chartWidth / Math.max(values.length, 1)) - 4);

        // Clear
        ctx.clearRect(0, 0, width, height);

        // Axes
        ctx.strokeStyle = 'rgba(100,116,139,0.5)';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(padding, padding);
        ctx.lineTo(padding, height - padding);
        ctx.lineTo(width - padding, height - padding);
        ctx.stroke();

        // Bars
        const barColor = getComputedStyle(document.documentElement).classList.contains('dark')
            ? 'rgba(99,102,241,0.8)'
            : 'rgba(37,99,235,0.8)';

        values.forEach((val, i) => {
            const x = padding + i * (barWidth + 4);
            const barHeight = (val / maxVal) * chartHeight;
            const y = height - padding - barHeight;
            ctx.fillStyle = barColor;
            ctx.fillRect(x, y, barWidth, barHeight);
        });
    }
};


