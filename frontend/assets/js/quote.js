$(function() {
    let allItems = [];
    let filterTimeout;
    const result = $('#quoteResult');
    const submitButton = $('#quoteSubmit');

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function renderRecommendations(items) {
        const list = $('#recommendationsList').empty();
        if (!items.length) {
            list.html('<div class="text-secondary text-center py-5" style="font-size:0.9rem;"><i class="fa-solid fa-box-open fs-2 d-block opacity-30 mb-2"></i>No matching items found.</div>');
            return;
        }

        items.forEach(item => {
            const price = Number(item.price);
            if (!Number.isFinite(price)) return;
            list.append(`
                <div class="recommendation-item d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <h6 class="fw-bold mb-1" style="font-size:0.9rem;">${escapeHtml(item.name)}</h6>
                        <small class="text-secondary">${escapeHtml(item.category || 'General')}</small>
                    </div>
                    <div class="text-end flex-shrink-0">
                        <div class="fw-bold" style="font-size:0.95rem;">₱${price.toFixed(2)}</div>
                        <small class="text-success fw-semibold">${Number(item.quantity)} in stock</small>
                    </div>
                </div>`);
        });
    }

    function filterItems() {
        const searchText = $('#search').val().trim().toLowerCase();
        const requestText = $('#requested_items').val().toLowerCase();
        const requestWords = requestText.match(/[\p{L}\p{N}]{3,}/gu) || [];

        if (!searchText && !requestWords.length) {
            renderRecommendations(allItems);
            return;
        }

        const filtered = allItems.filter(item => {
            const name = String(item.name || '').toLowerCase();
            const category = String(item.category || '').toLowerCase();
            if (searchText && (name.includes(searchText) || category.includes(searchText))) return true;
            return requestWords.some(word => name.includes(word) || category.includes(word));
        });
        renderRecommendations(filtered);
    }

    api('quotes/recommend.php').done(function(response) {
        allItems = Array.isArray(response.data) ? response.data : [];
        renderRecommendations(allItems);
    }).fail(function() {
        $('#recommendationsList').html('<div class="alert alert-danger" role="alert">Matching items could not be loaded right now.</div>');
    });

    $('#search, #requested_items').on('input', function() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(filterItems, 250);
    });

    $('#contact').on('input', function() {
        const value = $(this).val().trim();
        const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
        const digits = value.replace(/\D/g, '');
        const isPhone = /^\+?[0-9][0-9\s().-]{5,28}$/.test(value) && digits.length >= 7;
        this.setCustomValidity(value && !isEmail && !isPhone ? 'Enter a valid email address or phone number.' : '');
    });

    $('#quoteForm').on('submit', function(event) {
        event.preventDefault();
        result.addClass('d-none').text('').removeClass('alert-success alert-danger');
        if (!this.reportValidity()) return;

        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Submitting…');
        api('quotes/request.php', 'POST', {
            customer_name: $('#customer_name').val().trim(),
            contact: $('#contact').val().trim(),
            requested_items: $('#requested_items').val().trim()
        }).done(function(response) {
            const quoteId = response.data && response.data.quote_id;
            result.removeClass('d-none alert-danger').addClass('alert alert-success')
                .text(quoteId ? `Request #${quoteId} received. We will contact you soon.` : 'Quote request received. We will contact you soon.');
            $('#quoteForm')[0].reset();
            renderRecommendations(allItems);
        }).fail(function(xhr) {
            const message = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : 'We could not submit your request. Please try again.';
            result.removeClass('d-none alert-success').addClass('alert alert-danger').text(message);
        }).always(function() {
            submitButton.prop('disabled', false).html('Submit Request <i class="fa-solid fa-paper-plane ms-2"></i>');
        });
    });
});
