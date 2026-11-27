$(function() {
    const itemsById = {};

    function safeImageUrl(value) {
        try {
            const url = new URL(value, window.location.href);
            return ['http:', 'https:'].includes(url.protocol) ? url.href : '';
        } catch (error) {
            return '';
        }
    }

    function loadInventory() {
        api('inventory/list.php').done(function(res) {
            let tbody = $('#inventoryTable tbody');
            tbody.empty();
            Object.keys(itemsById).forEach(key => delete itemsById[key]);
            (res.data || []).forEach(item => {
                itemsById[item.id] = item;
                let badgeClass = item.item_condition === 'new' ? 'bg-success bg-opacity-10 text-success border border-success' : 
                               (item.item_condition === 'returned_good' ? 'bg-warning bg-opacity-10 text-warning border border-warning' : 'bg-danger bg-opacity-10 text-danger border border-danger');
                const src = safeImageUrl(item.image_url || '');
                let imgHtml = src 
                    ? `<img src="${$('<div>').text(src).html()}" alt="item" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px;">`
                    : `<div style="width: 40px; height: 40px; background: rgba(0,0,0,0.05); border-radius: 8px; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-image text-secondary"></i></div>`;
                
                let tr = `<tr>
                    <td>${item.id}</td>
                    <td>${imgHtml}</td>
                    <td>${$('<div>').text(item.name).html()}</td>
                    <td>${$('<div>').text(item.category).html()}</td>
                    <td>₱${parseFloat(item.price).toFixed(2)}</td>
                    <td>${item.quantity}</td>
                    <td>${item.stage.replace('_', ' ')}</td>
                    <td><span class="badge ${badgeClass} text-uppercase">${item.item_condition.replace('_', ' ')}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-info btn-edit rounded-pill" data-id="${item.id}">Edit</button>
                        <button class="btn btn-sm btn-outline-warning btn-return rounded-pill" data-id="${item.id}">Return</button>
                    </td>
                </tr>`;
                tbody.append(tr);
            });
        });
    }

    loadInventory();

    $('#btnAddItem').click(function() {
        $('#inventoryForm')[0].reset();
        $('#itemId').val('');
        $('#inventoryModalLabel').text('Add New Item');
    });

    $(document).on('click', '.btn-edit', function() {
        let item = itemsById[$(this).data('id')];
        if (!item) return;
        $('#itemId').val(item.id);
        $('#itemName').val(item.name);
        $('#itemImageUrl').val(item.image_url || '');
        $('#itemCategory').val(item.category);
        $('#itemPrice').val(item.price);
        $('#itemQty').val(item.quantity);
        $('#itemStage').val(item.stage);
        $('#itemCondition').val(item.item_condition);
        $('#inventoryModalLabel').text('Edit Item');
        var myModal = new bootstrap.Modal(document.getElementById('inventoryModal'));
        myModal.show();
    });

    $(document).on('click', '.btn-return', function() {
        $('#returnItemId').val($(this).data('id'));
        $('#returnForm')[0].reset();
        var myModal = new bootstrap.Modal(document.getElementById('returnModal'));
        myModal.show();
    });

    $('#inventoryForm').submit(function(e) {
        e.preventDefault();
        api('inventory/save.php', 'POST', $(this).serialize()).done(function() {
            bootstrap.Modal.getInstance(document.getElementById('inventoryModal')).hide();
            loadInventory();
        });
    });

    $('#returnForm').submit(function(e) {
        e.preventDefault();
        api('inventory/return_item.php', 'POST', $(this).serialize()).done(function() {
            bootstrap.Modal.getInstance(document.getElementById('returnModal')).hide();
            loadInventory();
        });
    });

    $('#logoutBtn').click(function() {
        api('auth/logout.php', 'POST').done(function() {
            sessionStorage.removeItem('joey_csrf_token');
            window.location.href = 'login.html';
        });
    });
});

