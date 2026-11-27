// frontend/assets/js/api.js

function api(url, method = 'GET', data = {}) {
    // Dynamically calculate the root path based on where /frontend/ is located in the URL
    // This works whether the app is in localhost/joey-tahian/ or just localhost/
    let basePath = window.location.pathname.split('/frontend/')[0] + '/backend/api/';
    
    const headers = {};
    if (method && method.toUpperCase() !== 'GET') {
        const csrfToken = sessionStorage.getItem('joey_csrf_token');
        if (csrfToken) headers['X-CSRF-Token'] = csrfToken;
    }

    return $.ajax({
        url: basePath + url,
        method: method,
        data: data,
        dataType: 'json',
        headers: headers
    }).fail(function(jqXHR) {
        let msg = 'An error occurred';
        if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
            msg = jqXHR.responseJSON.message;
        }
        if (jqXHR.status === 401 && !window.location.pathname.endsWith('login.html')) {
            window.location.href = 'login.html';
        }
        console.error("API Error:", msg);
        // Using alert for simplicity, could be replaced with Bootstrap toasts
        // alert(msg); 
    });
}

