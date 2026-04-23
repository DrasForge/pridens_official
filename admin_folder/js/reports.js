// admin_folder/js/reports.js

document.addEventListener('DOMContentLoaded', () => {
    let trendChart = null;
    let categoryChart = null;

    const reportRange = document.getElementById('report-range');
    const valRevenue = document.getElementById('val-revenue');
    const valCost = document.getElementById('val-cost');
    const valProfit = document.getElementById('val-profit');
    const valCount = document.getElementById('val-count');
    const topProductsTbody = document.getElementById('top-products-tbody');

    // Initial Load
    fetchReports();

    // Event Listeners
    reportRange.addEventListener('change', fetchReports);

    async function fetchReports() {
        const range = reportRange.value;
        try {
            const res = await fetch(`../admin_API/get_pos_reports.php?range=${range}`);
            const r = await res.json();
            
            if (r.success) {
                updateUI(r.data);
            } else {
                console.error('Failed to load reports:', r.error);
            }
        } catch (e) {
            console.error('Connection error while fetching reports');
        }
    }

    function updateUI(data) {
        // 1. Update KPI Cards
        valRevenue.innerText = `₱${data.aggregates.revenue.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
        valCost.innerText = `₱${data.aggregates.cost.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
        valProfit.innerText = `₱${data.aggregates.profit.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
        valCount.innerText = data.aggregates.count.toLocaleString();

        // 2. Render Trend Chart
        renderTrendChart(data.trends);

        // 3. Render Category Chart
        renderCategoryChart(data.categories);

        // 4. Render Top Products Table
        renderTopProducts(data.top_products);
    }

    function renderTrendChart(trends) {
        const ctx = document.getElementById('trendChart').getContext('2d');
        const labels = trends.map(t => new Date(t.date).toLocaleDateString(undefined, {month: 'short', day: 'numeric'}));
        const revenue = trends.map(t => parseFloat(t.revenue));
        const profit = trends.map(t => parseFloat(t.revenue) - parseFloat(t.cost));

        if (trendChart) trendChart.destroy();

        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Gross Sales',
                        data: revenue,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Net Profit',
                        data: profit,
                        borderColor: '#4ade80',
                        backgroundColor: 'rgba(74, 222, 128, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: '#94a3b8' } }
                },
                scales: {
                    x: { ticks: { color: '#94a3b8' }, grid: { display: false } },
                    y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } }
                }
            }
        });
    }

    function renderCategoryChart(categories) {
        const ctx = document.getElementById('categoryChart').getContext('2d');
        const labels = categories.map(c => c.category);
        const values = categories.map(c => parseFloat(c.revenue));

        if (categoryChart) categoryChart.destroy();

        categoryChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: [
                        '#6366f1', '#4ade80', '#fbbf24', '#f87171', '#a78bfa', '#f472b6'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: { color: '#94a3b8', padding: 20 }
                    }
                }
            }
        });
    }

    function renderTopProducts(products) {
        if (products.length === 0) {
            topProductsTbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 20px;">No sales data available.</td></tr>';
            return;
        }

        topProductsTbody.innerHTML = products.map(p => {
            const revenue = parseFloat(p.revenue);
            const profit = parseFloat(p.profit);
            const margin = revenue > 0 ? (profit / revenue * 100).toFixed(1) : 0;
            
            return `
                <tr>
                    <td style="font-weight: 500;">${p.name}</td>
                    <td>${p.total_qty}</td>
                    <td>₱${revenue.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td style="color: #4ade80; font-weight: 600;">₱${profit.toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td><span style="font-size: 11px; padding: 2px 6px; background: rgba(74, 222, 128, 0.1); color: #4ade80; border-radius: 4px;">${margin}%</span></td>
                </tr>
            `;
        }).join('');
    }
});
