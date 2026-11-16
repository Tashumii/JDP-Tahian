$(function() {
    $('#logoutBtn').click(function() {
        api('auth/logout.php', 'POST').done(function() {
            window.location.href = 'login.html';
        });
    });
});

