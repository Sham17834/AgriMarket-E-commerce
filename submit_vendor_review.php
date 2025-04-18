<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'You must be logged in as a customer to submit a review.']);
    exit;
}

$vendor_id = isset($_POST['vendor_id']) ? (int)$_POST['vendor_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

// Validate input
if ($vendor_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid vendor.']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Please select a rating between 1 and 5 stars.']);
    exit;
}

if (empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Please enter your review comments.']);
    exit;
}

// Check if user has purchased from this vendor (only if you want to maintain this requirement)
$order_check = $pdo->prepare("SELECT * FROM order_items oi 
                             JOIN orders o ON oi.order_id = o.order_id 
                             JOIN products p ON oi.product_id = p.product_id 
                             WHERE p.vendor_id = ? AND o.customer_id = ?");
$order_check->execute([$vendor_id, $_SESSION['user_id']]);
if ($order_check->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'You can only review vendors you have purchased from.']);
    exit;
}

// Insert the review (now allowing multiple reviews)
try {
    $pdo->beginTransaction();
    
    // Insert review
    $stmt = $pdo->prepare("INSERT INTO vendor_reviews (vendor_id, customer_id, rating, comment, review_date) 
                          VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$vendor_id, $_SESSION['user_id'], $rating, $comment]);
    
    // Get username for the response
    $user_stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
    $user_stmt->execute([$_SESSION['user_id']]);
    $user = $user_stmt->fetch();
    
    // Get updated average rating and count
    $avg_rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
                                     FROM vendor_reviews 
                                     WHERE vendor_id = ?");
    $avg_rating_stmt->execute([$vendor_id]);
    $rating_data = $avg_rating_stmt->fetch();
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'username' => $user['username'],
        'rating' => $rating,
        'comment' => htmlspecialchars($comment),
        'review_date' => date('Y-m-d H:i:s'),
        'new_average_rating' => (float)$rating_data['avg_rating'],
        'review_count' => (int)$rating_data['review_count']
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}