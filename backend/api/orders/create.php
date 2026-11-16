<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/validate.php';
require_once __DIR__ . '/../../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = read_request_data();
foreach (['customer_name', 'email', 'mobile', 'address', 'payment_method', 'items'] as $field) {
    if (!array_key_exists($field, $input)) {
        json_error("Missing required field: $field", 400);
    }
}

$customerName = validated_string($input['customer_name'], 'name', 2, 100);
if (!preg_match('/\p{L}/u', $customerName)) {
    json_error('Enter a valid customer name.', 400);
}

$email = validated_string($input['email'], 'email address', 5, 100);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_error('Enter a valid email address.', 400);
}

$mobile = validated_string($input['mobile'], 'mobile number', 10, 30);
if (!valid_phone_number($mobile)) {
    json_error('Enter a valid Philippine mobile number.', 400);
}

$address = validated_string($input['address'], 'shipping address', 8, 255);
if (!preg_match('/\p{L}/u', $address)) {
    json_error('Enter a valid shipping address.', 400);
}

if ($input['payment_method'] !== 'COD') {
    json_error('Cash on Delivery is the only available payment method.', 400);
}

if (!is_array($input['items']) || count($input['items']) < 1 || count($input['items']) > 50) {
    json_error('Choose between 1 and 50 order items.', 400);
}

$items = [];
$seenIds = [];
foreach ($input['items'] as $item) {
    if (!is_array($item) || !isset($item['id'], $item['qty'], $item['unit_price'])) {
        json_error('Each order item must include an item ID, quantity, and displayed price.', 400);
    }

    $id = filter_var($item['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $qty = filter_var($item['qty'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10000]]);
    $displayedPrice = is_string($item['unit_price']) ? $item['unit_price'] : '';
    if ($id === false || $qty === false || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $displayedPrice)) {
        json_error('Item IDs and quantities must be whole numbers, and prices must be valid.', 400);
    }
    if (isset($seenIds[$id])) {
        json_error('Each item can only appear once in the order.', 400);
    }

    $seenIds[$id] = true;
    $priceParts = explode('.', $displayedPrice, 2);
    $displayedPriceCents = ((int)$priceParts[0] * 100) + (int)str_pad($priceParts[1] ?? '0', 2, '0');
    $items[] = ['id' => $id, 'qty' => $qty, 'displayed_price_cents' => $displayedPriceCents];
}

// Lock rows in a consistent order so concurrent checkouts cannot oversell stock.
usort($items, function ($a, $b) { return $a['id'] <=> $b['id']; });

try {
    $pdo->beginTransaction();
    $selectItem = $pdo->prepare("SELECT id, name, price, quantity, item_condition FROM inventory WHERE id = ? FOR UPDATE");
    $insertOrderItem = $pdo->prepare('INSERT INTO order_items (order_id, inventory_id, qty, unit_price) VALUES (?, ?, ?, ?)');
    $deductStock = $pdo->prepare('UPDATE inventory SET quantity = quantity - ? WHERE id = ? AND quantity >= ?');

    $totalCents = 0;
    $checkedItems = [];
    foreach ($items as $item) {
        $selectItem->execute([$item['id']]);
        $inventoryItem = $selectItem->fetch();
        if (!$inventoryItem || $inventoryItem['item_condition'] === 'returned_damaged') {
            throw new DomainException('One of the selected items is no longer available. Refresh the page and try again.');
        }
        if ((int)$inventoryItem['quantity'] < $item['qty']) {
            throw new DomainException('Not enough stock for ' . $inventoryItem['name'] . '. Please adjust the quantity.');
        }

        $unitPriceCents = (int)round((float)$inventoryItem['price'] * 100);
        if ($unitPriceCents < 1) {
            throw new DomainException($inventoryItem['name'] . ' is not currently available to order.');
        }
        if ($unitPriceCents !== $item['displayed_price_cents']) {
            throw new DomainException('An item price has changed. Refresh the page and review your order.');
        }
        $totalCents += $unitPriceCents * $item['qty'];
        $checkedItems[] = [
            'id' => (int)$inventoryItem['id'],
            'name' => $inventoryItem['name'],
            'qty' => $item['qty'],
            'unit_price' => number_format($unitPriceCents / 100, 2, '.', '')
        ];
    }

    if ($totalCents < 1 || $totalCents > 999999999999) {
        throw new DomainException('The order total is outside the allowed range.');
    }

    $orderNo = 'ORD-' . strtoupper(bin2hex(random_bytes(8)));
    $total = number_format($totalCents / 100, 2, '.', '');
    $confirmationToken = bin2hex(random_bytes(32));
    $confirmationTokenHash = hash('sha256', $confirmationToken);
    $confirmationExpiresAt = gmdate('Y-m-d H:i:s', time() + 86400);
    // The original schema required contact; newer installations store email and mobile separately.
    $hasLegacyContact = $pdo->query("SHOW COLUMNS FROM orders LIKE 'contact'")->fetch() !== false;
    if ($hasLegacyContact) {
        $insertOrder = $pdo->prepare('INSERT INTO orders (order_no, customer_name, contact, email, mobile, address, total, payment_method, confirmation_token_hash, confirmation_expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $insertOrder->execute([$orderNo, $customerName, $mobile, $email, $mobile, $address, $total, 'COD', $confirmationTokenHash, $confirmationExpiresAt]);
    } else {
        $insertOrder = $pdo->prepare('INSERT INTO orders (order_no, customer_name, email, mobile, address, total, payment_method, confirmation_token_hash, confirmation_expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $insertOrder->execute([$orderNo, $customerName, $email, $mobile, $address, $total, 'COD', $confirmationTokenHash, $confirmationExpiresAt]);
    }
    $orderId = (int)$pdo->lastInsertId();

    foreach ($checkedItems as $item) {
        $insertOrderItem->execute([$orderId, $item['id'], $item['qty'], $item['unit_price']]);
        $deductStock->execute([$item['qty'], $item['id'], $item['qty']]);
        if ($deductStock->rowCount() !== 1) {
            throw new DomainException('Stock changed while placing the order. Please try again.');
        }
    }

    $pdo->commit();

    $emailSent = false;
    try {
        send_order_confirmation_email($email, $customerName, $orderNo, $total, $confirmationToken, $checkedItems);
        $emailSent = true;
        $sentAt = $pdo->prepare('UPDATE orders SET confirmation_email_sent_at = UTC_TIMESTAMP() WHERE id = ?');
        $sentAt->execute([$orderId]);
    } catch (Throwable $mailError) {
        error_log('Order confirmation email failed for ' . $orderNo . ': ' . $mailError->getMessage());
    }

    json_success(
        ['order_no' => $orderNo, 'email_sent' => $emailSent],
        $emailSent ? 'Order placed. Check your email to confirm it.' : 'Order placed, but the confirmation email could not be sent.'
    );
} catch (DomainException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_error($e->getMessage(), 409);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Order creation failed: ' . $e->getMessage());
    json_error('We could not place your order right now. Please try again.', 500);
}
