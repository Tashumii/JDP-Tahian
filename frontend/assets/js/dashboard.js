$(function() {
    let salesChart;

    function loadSummary() {
        api('analytics/summary.php').done(function(res) {
            const data = res.data;
            
            $('#salesDay').text('₱' + data.day.total_sales.toFixed(2));
            $('#ordersDay').text(data.day.total_orders + ' Orders');
            
            $('#salesWeek').text('₱' + data.week.total_sales.toFixed(2));
            $('#ordersWeek').text(data.week.total_orders + ' Orders');
            
            $('#salesMonth').text('₱' + data.month.total_sales.toFixed(2));
            $('#ordersMonth').text(data.month.total_orders + ' Orders');
            
            $('#salesYear').text('₱' + data.year.total_sales.toFixed(2));
            $('#ordersYear').text(data.year.total_orders + ' Orders');

            updateChart(data);
        });
    }

    function loadConfirmationSummary() {
        api('orders/summary.php').done(function(res) {
            const data = res.data || {};
            $('#emailConfirmedOrders').text((data.email_confirmed || 0) + ' confirmed by email');
            $('#pendingConfirmations').text((data.pending_confirmation || 0) + ' awaiting confirmation');
        });
    }

    function updateChart(data) {
        const ctx = document.getElementById('salesChart').getContext('2d');
        
        if (salesChart) {
            salesChart.destroy();
        }
        
        salesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Today', 'This Week', 'This Month', 'This Year'],
                datasets: [{
                    label: 'Sales (₱)',
                    data: [data.day.total_sales, data.week.total_sales, data.month.total_sales, data.year.total_sales],
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.5)', // Green
                        'rgba(59, 130, 246, 0.5)', // Blue
                        'rgba(139, 92, 246, 0.5)', // Purple
                        'rgba(245, 158, 11, 0.5)'  // Orange
                    ],
                    borderColor: [
                        '#10b981',
                        '#3b82f6',
                        '#8b5cf6',
                        '#f59e0b'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                plugins: {
                    legend: {
                        labels: { color: 'rgba(0,0,0,0.7)' }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.1)' },
                        ticks: { color: 'rgba(0,0,0,0.7)' }
                    },
                    x: {
                        grid: { color: 'rgba(0,0,0,0.1)' },
                        ticks: { color: 'rgba(0,0,0,0.7)' }
                    }
                }
            }
        });
    }

    loadSummary();
    loadConfirmationSummary();
    // Auto refresh every 5 minutes
    setInterval(loadSummary, 300000);
    setInterval(loadConfirmationSummary, 30000);

    $('#logoutBtn').click(function() {
        api('auth/logout.php', 'POST').done(function() {
            window.location.href = 'login.html';
        });
    });
});

