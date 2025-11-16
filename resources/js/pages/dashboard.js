import Chart from 'chart.js/auto';

function initAdminPostsChart() {
    const canvas = document.getElementById('postsChart');
    if (!canvas) {
        return;
    }

    const labels = JSON.parse(canvas.getAttribute('data-labels') || '[]');
    const values = JSON.parse(canvas.getAttribute('data-values') || '[]');

    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#9CA3AF' : '#6B7280';
    const gridColor = isDark ? '#374151' : '#E5E7EB';

    // eslint-disable-next-line no-new
    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Posts Published',
                data: values,
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointHoverRadius: 5,
                pointBackgroundColor: '#3B82F6',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: isDark ? '#1F2937' : '#fff',
                    titleColor: textColor,
                    bodyColor: textColor,
                    borderColor: gridColor,
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label(context) {
                            return `Posts: ${context.parsed.y}`;
                        },
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        color: textColor,
                        font: {
                            size: 11,
                        },
                    },
                    grid: {
                        color: gridColor,
                        drawBorder: false,
                    },
                },
                x: {
                    ticks: {
                        color: textColor,
                        font: {
                            size: 11,
                        },
                        maxRotation: 45,
                        minRotation: 45,
                    },
                    grid: {
                        display: false,
                        drawBorder: false,
                    },
                },
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false,
            },
        },
    });
}

// Dashboard page-specific JavaScript
import Alpine from 'alpinejs';

// Import components needed for dashboard
import bookmarkButton from '../components/bookmark-button';

// Register dashboard-specific components
Alpine.data('bookmarkButton', bookmarkButton);

export default {
    init() {
        // Initialize admin chart if present
        initAdminPostsChart();
        // Other dashboard initializations can be added here
    },
};
