<?php
session_start();
error_reporting(0); // Suppress Warnings to return clean JSON
require_once 'config/database.php';
require_once 'config/settings_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Keranjang kosong']);
    exit;
}

$cart = $input['cart'];
$userId = $_SESSION['user_id'];
$totalAmount = 0;

// Calculate Total and Validate Stock
// We'll trust the price from DB, not from frontend, for security
$itemsToProcess = [];

foreach ($cart as $item) {
    $productId = intval($item['id']);
    $qty = intval($item['qty']);

    $stmt = $conn->prepare("SELECT price, stock FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan: ID ' . $productId]);
        exit;
    }

    $product = $result->fetch_assoc();
    
    if ($product['stock'] < $qty) {
        echo json_encode(['success' => false, 'message' => 'Stok tidak cukup untuk produk ID ' . $productId]);
        exit;
    }

    $itemsToProcess[] = [
        'id' => $productId,
        'qty' => $qty,
        'price' => $product['price'],
        'subtotal' => $product['price'] * $qty
    ];
    $totalAmount += ($product['price'] * $qty);
}

// Add Tax (Dynamic from Settings)
$taxRate = getSetting('tax_rate') / 100;
$subtotalInitial = $totalAmount;

// Discounts & Points from Frontend (Validated slightly)
$discountAmount = isset($input['discount_amount']) ? floatval($input['discount_amount']) : 0;
$pointsUsedQty = isset($input['points_used_qty']) ? intval($input['points_used_qty']) : 0;
$pointsUsedVal = isset($input['points_used_val']) ? floatval($input['points_used_val']) : 0;

// Validate Logic
$taxable = max(0, $subtotalInitial - $discountAmount - $pointsUsedVal);
$tax = $taxable * $taxRate;
$grandTotal = $taxable + $tax;

// Start Transaction
$conn->begin_transaction();

try {
    $customerName = isset($input['customer_name']) ? $input['customer_name'] : 'Guest';

    // 1. Insert Transaction Header
    $invoiceCode = 'INV/' . date('Ymd') . '/' . strtoupper(uniqid());
    $paymentMethod = isset($input['payment_method']) ? $input['payment_method'] : 'cash';
    $cashReceived = isset($input['cash_received']) ? $input['cash_received'] : 0;
    $changeAmount = isset($input['change_amount']) ? $input['change_amount'] : 0;
    $orderType = isset($input['order_type']) ? $input['order_type'] : 'dine_in';
    // FIX: Discount & Points should be stored. Ensure columns exist or backup plan.
    // Based on user schema, we assume columns exist.
    
    // START FIX: Table names MUST be lowercase for Hosting (Linux)
    $stmt = $conn->prepare("INSERT INTO transactions (invoice_code, user_id, total_amount, payment_method, payment_status, customer_name, cash_received, change_amount, order_type, discount_amount, points_used) VALUES (?, ?, ?, ?, 'paid', ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sidssddsdi", $invoiceCode, $userId, $grandTotal, $paymentMethod, $customerName, $cashReceived, $changeAmount, $orderType, $discountAmount, $pointsUsedQty);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed (Transactions): " . $stmt->error);
    }
    
    $transactionId = $conn->insert_id;

    // 2. Insert Details and Update Stock
    // FIX: Table 'transactiondetails' lowercase
    $stmtDetail = $conn->prepare("INSERT INTO transactiondetails (transaction_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
    if (!$stmtDetail) throw new Exception("Prepare Detail failed: " . $conn->error);

    $stmtUpdateStock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");

    foreach ($itemsToProcess as $item) {
        // Insert Detail
        $stmtDetail->bind_param("iiidd", $transactionId, $item['id'], $item['qty'], $item['price'], $item['subtotal']);
        if (!$stmtDetail->execute()) {
             throw new Exception("Execute Detail failed: " . $stmtDetail->error);
        }

        // Update Stock
        $stmtUpdateStock->bind_param("ii", $item['qty'], $item['id']);
        $stmtUpdateStock->execute();
    }

    // 3. Loyalty Points System (Earn & Burn)
    if ($customerName !== 'Guest') {
        $checkCust = $conn->prepare("SELECT customer_id, loyalty_points FROM customers WHERE customer_name = ?");
        $checkCust->bind_param("s", $customerName);
        $checkCust->execute();
        $resCust = $checkCust->get_result();
        
        if ($resCust->num_rows > 0) {
            $custData = $resCust->fetch_assoc();
            $custId = $custData['customer_id'];
            $currentPoints = $custData['loyalty_points'];

            // A. Deduct Points if used
            if ($pointsUsedQty > 0) {
                if ($currentPoints >= $pointsUsedQty) {
                    $deduct = $conn->prepare("UPDATE customers SET loyalty_points = loyalty_points - ? WHERE customer_id = ?");
                    $deduct->bind_param("ii", $pointsUsedQty, $custId);
                    $deduct->execute();
                }
            }

            // B. Add New Points (Earned from this transaction)
            if ($grandTotal > 0) {
                $pointsEarned = floor($grandTotal / 10000); // 1 point per 10k
                if ($pointsEarned > 0) {
                    $addPoints = $conn->prepare("UPDATE customers SET loyalty_points = loyalty_points + ? WHERE customer_id = ?");
                    $addPoints->bind_param("ii", $pointsEarned, $custId);
                    $addPoints->execute();
                }
            }
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Pembayaran berhasil!', 'invoice' => $invoiceCode]);

} catch (Exception $e) {
    $conn->rollback();
    // Return REAL error message for debugging
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>

