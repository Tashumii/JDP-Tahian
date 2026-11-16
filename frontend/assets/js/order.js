$(function() {
    const itemPrices = {};
    const errorBox = $('#orderError');
    const submitButton = $('#orderSubmit');

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function imageUrl(value) {
        try {
            const url = new URL(value, window.location.href);
            return ['http:', 'https:'].includes(url.protocol) ? url.href : '';
        } catch (error) {
            return '';
        }
    }

    function showOrderError(message) {
        errorBox.text(message).removeClass('d-none');
    }

    function calculateTotal() {
        let total = 0;
        $('.item-qty').each(function() {
            const id = String($(this).data('id'));
            const qty = Number.parseInt($(this).val(), 10) || 0;
            if (qty > 0 && itemPrices[id] !== undefined) total += qty * itemPrices[id];
        });
        $('#orderTotal').text('₱' + total.toFixed(2));
    }

    function renderItems(items) {
        const container = $('#itemsContainer').empty();
        const availableItems = (items || []).filter(item => Number(item.quantity) > 0);

        if (!availableItems.length) {
            container.html('<div class="text-secondary py-4"><i class="fa-solid fa-box-open fs-1 d-block opacity-30 mb-3"></i>No items are available to order right now.</div>');
            return;
        }

        availableItems.forEach(item => {
            const id = Number.parseInt(item.id, 10);
            const quantity = Number.parseInt(item.quantity, 10);
            const price = Number(item.price);
            if (!Number.isInteger(id) || !Number.isInteger(quantity) || !Number.isFinite(price) || price <= 0) return;

            itemPrices[id] = price;
            const src = imageUrl(item.image_url || '');
            const image = src
                ? `<img src="${escapeHtml(src)}" alt="${escapeHtml(item.name)}" class="w-100 mb-4" style="border-radius:12px;height:180px;object-fit:cover;">`
                : '<div class="w-100 mb-4 d-flex align-items-center justify-content-center" style="border-radius:12px;height:180px;background:rgba(0,0,0,0.04);"><i class="fa-solid fa-image fs-1 text-secondary opacity-30"></i></div>';

            container.append(`
                <div class="glass-card bento-item">
                    <div>
                        ${image}
                        <span class="product-stock text-success bg-success bg-opacity-10 border border-success border-opacity-25 mb-3">
                            <i class="fa-solid fa-box-open"></i> ${quantity} available
                        </span>
                        <h5 class="fw-bold mt-2 mb-1">${escapeHtml(item.name)}</h5>
                        <p class="text-secondary mb-0" style="font-size:0.88rem;">${escapeHtml(item.category || 'General')}</p>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3" style="border-top:1px solid var(--card-border);">
                        <div class="product-price">₱${price.toFixed(2)}</div>
                        <div class="qty-control">
                            <button type="button" class="qty-btn qty-minus" data-id="${id}" aria-label="Remove one ${escapeHtml(item.name)}"><i class="fa-solid fa-minus"></i></button>
                            <input type="number" class="qty-input item-qty" data-id="${id}" min="0" max="${quantity}" value="0" readonly aria-label="Quantity of ${escapeHtml(item.name)}">
                            <button type="button" class="qty-btn qty-plus" data-id="${id}" aria-label="Add one ${escapeHtml(item.name)}"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </div>
                </div>`);
        });

        if (!container.children().length) {
            container.html('<div class="text-secondary py-4">No orderable items are available right now.</div>');
        }
    }

    api('quotes/recommend.php').done(function(response) {
        renderItems(response.data);
    }).fail(function() {
        $('#itemsContainer').html('<div class="alert alert-danger" role="alert">We could not load the catalog. Refresh the page to try again.</div>');
    });

    $(document).on('click', '.qty-plus', function() {
        const input = $(`.item-qty[data-id="${Number($(this).data('id'))}"]`);
        const qty = Number.parseInt(input.val(), 10) || 0;
        const max = Number.parseInt(input.attr('max'), 10) || 0;
        if (qty < max) input.val(qty + 1);
        calculateTotal();
    });

    $(document).on('click', '.qty-minus', function() {
        const input = $(`.item-qty[data-id="${Number($(this).data('id'))}"]`);
        const qty = Number.parseInt(input.val(), 10) || 0;
        if (qty > 0) input.val(qty - 1);
        calculateTotal();
    });

    $('#orderForm').on('submit', function(event) {
        event.preventDefault();
        errorBox.addClass('d-none').text('');
        if (!this.reportValidity()) return;

        const items = [];
        $('.item-qty').each(function() {
            const qty = Number.parseInt($(this).val(), 10) || 0;
            const id = String($(this).data('id'));
            if (qty > 0) items.push({ id: Number(id), qty, unit_price: itemPrices[id].toFixed(2) });
        });
        if (!items.length) {
            showOrderError('Choose at least one item before placing your order.');
            return;
        }

        const payload = {
            customer_name: $('#customer_name').val().trim(),
            email: $('#email').val().trim(),
            mobile: $('#mobile').val().trim(),
            address: $('#address').val().trim(),
            payment_method: $('input[name="payment_method"]:checked').val(),
            items
        };

        submitButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Placing order…');
        api('orders/create.php', 'POST', payload).done(function(response) {
            const orderNo = response.data && response.data.order_no;
            if (!orderNo) {
                showOrderError('The order was received, but we could not load its confirmation number. Please contact us before placing it again.');
                return;
            }
            window.location.href = 'confirmation.html?order_no=' + encodeURIComponent(orderNo)
                + '&email_sent=' + (response.data.email_sent ? '1' : '0');
        }).fail(function(xhr) {
            const message = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : 'Order processing failed. Please try again.';
            showOrderError(message);
        }).always(function() {
            submitButton.prop('disabled', false).html('Place Order <i class="fa-solid fa-arrow-right ms-2"></i>');
        });
    });
});
