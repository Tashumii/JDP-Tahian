$(function() {
    $('#reportForm').on('submit', function(event) {
        event.preventDefault();
        const period = $('#reportPeriod').val() || 'all';
        let basePath = window.location.pathname.split('/frontend/')[0] + '/backend/api/';
        window.location.href = basePath + 'analytics/export.php?period=' + encodeURIComponent(period);
    });

    $('#logoutBtn').click(function() {
        api('auth/logout.php', 'POST').done(function() {
            sessionStorage.removeItem('joey_csrf_token');
            window.location.href = 'login.html';
        });
    });
});

