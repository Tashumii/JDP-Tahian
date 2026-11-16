<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_guard.php';
require_once __DIR__ . '/../../includes/validate.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = read_request_data();
if (!isset($input['id'], $input['status'])) {
    json_error('Quote ID and new status are required.', 400);
}

$quoteId = filter_var($input['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$newStatus = is_string($input['status']) ? trim($input['status']) : '';
$transitions = [
    'pending' => 'reviewed',
    'reviewed' => 'contacted',
    'contacted' => 'closed',
    'closed' => null
];
if ($quoteId === false || !array_key_exists($newStatus, ['reviewed' => true, 'contacted' => true, 'closed' => true])) {
    json_error('Invalid quote ID or status.', 400);
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT id, status FROM quotes WHERE id = ? FOR UPDATE');
    $stmt->execute([$quoteId]);
    $quote = $stmt->fetch();
    if (!$quote) {
        $pdo->rollBack();
        json_error('Quote request not found.', 404);
    }
    if (($transitions[$quote['status']] ?? null) !== $newStatus) {
        $pdo->rollBack();
        json_error('Quote requests must move from pending to reviewed, contacted, then closed.', 409);
    }

    $update = $pdo->prepare('UPDATE quotes SET status = ? WHERE id = ?');
    $update->execute([$newStatus, $quoteId]);
    $pdo->commit();
    json_success(null, 'Quote request updated.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Quote status update failed: ' . $e->getMessage());
    json_error('We could not update the quote request right now. Please try again.', 500);
}
