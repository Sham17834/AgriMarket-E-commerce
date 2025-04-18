<?php
session_start();
require_once 'db_connect.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?error=Please log in to submit a review.");
    exit;
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php?error=Invalid request method.");
    exit;
}

// Validate form inputs
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
$customer_id = (int)$_SESSION['user_id'];

if ($product_id <= 0 || $rating < 1 || $rating > 5 || empty($title) || empty($comment)) {
    header("Location: product_details.php?id=$product_id&error=Please fill in all fields correctly. Rating must be between 1 and 5.");
    exit;
}

// Insert the review into the database
try {
    $stmt = $pdo->prepare("INSERT INTO product_reviews (product_id, customer_id, order_id, rating, title, comment, review_date, is_approved) 
                           VALUES (:product_id, :customer_id, NULL, :rating, :title, :comment, NOW(), 0)");
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
    $stmt->bindParam(':title', $title, PDO::PARAM_STR);
    $stmt->bindParam(':comment', $comment, PDO::PARAM_STR);
    $stmt->execute();

    // Redirect back to the product details page with a success message
    header("Location: product_details.php?id=$product_id&success=Your review has been submitted and is pending approval.");
    exit;

} catch (PDOException $e) {
    // Log the error and redirect with an error message
    error_log("Error submitting review: " . $e->getMessage());
    header("Location: product_details.php?id=$product_id&error=Error submitting review. Please try again later.");
    exit;
}
?>