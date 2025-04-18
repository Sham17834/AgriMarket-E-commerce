<?php
session_start();

// Check if vendor is logged in
if (!isset($_SESSION['vendor_id'])) {
    header("Location: login.php");
    exit;
}

try {
    $pdo = require_once 'db_connect.php';
    $errors = [];
    $success = '';

    $vendor_id = $_SESSION['vendor_id'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file'];
        if ($file['type'] == 'text/csv' && $file['size'] > 0 && $file['error'] == UPLOAD_ERR_OK) {
            $handle = fopen($file['tmp_name'], 'r');
            if ($handle === false) {
                $errors[] = "Failed to open the uploaded file.";
            } else {
                // Skip the header row
                fgetcsv($handle);
                $query = "INSERT INTO products (vendor_id, name, category_id, description, price, discounted_price, stock_quantity, minimum_order_quantity, packaging_type, weight_kg, is_organic, harvest_date, image_url, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
                $stmt = $pdo->prepare($query);
                $pdo->beginTransaction();
                try {
                    $row_count = 0;
                    while (($data = fgetcsv($handle)) !== false) {
                        if (count($data) < 12) {
                            $errors[] = "Invalid CSV format at row " . ($row_count + 2) . ": Expected 12 columns.";
                            continue;
                        }
                        $name = trim($data[0] ?? '');
                        $category_id = (int)($data[1] ?? 0);
                        $description = trim($data[2] ?? '');
                        $price = (float)($data[3] ?? 0);
                        $discounted_price = !empty($data[4]) ? (float)$data[4] : null;
                        $stock_quantity = (int)($data[5] ?? 0);
                        $minimum_order_quantity = (int)($data[6] ?? 0);
                        $packaging_type = trim($data[7] ?? '');
                        $weight_kg = !empty($data[8]) ? (float)$data[8] : null;
                        $is_organic = ($data[9] ?? '0') === '1' ? 1 : 0;
                        $harvest_date = !empty($data[10]) ? $data[10] : null;
                        $image_url = trim($data[11] ?? '');
                        if (empty($name) || $category_id <= 0 || $price <= 0 || empty($image_url)) {
                            $errors[] = "Missing or invalid required fields at row " . ($row_count + 2) . " (name, category_id, price, image_url).";
                            continue;
                        }
                        $category_check = $pdo->prepare("SELECT category_id FROM product_categories WHERE category_id = ?");
                        $category_check->execute([$category_id]);
                        if (!$category_check->fetch()) {
                            $errors[] = "Invalid category_id at row " . ($row_count + 2) . ".";
                            continue;
                        }
                        $image_url_array = [$image_url];
                        $stmt->execute([
                            $vendor_id, $name, $category_id, $description, $price, $discounted_price, $stock_quantity,
                            $minimum_order_quantity, $packaging_type, $weight_kg, $is_organic, $harvest_date, json_encode($image_url_array)
                        ]);
                        $row_count++;
                    }
                    $pdo->commit();
                    if ($row_count > 0) {
                        $success = "$row_count products uploaded successfully.";
                    } else {
                        $errors[] = "No valid products were uploaded.";
                    }
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $errors[] = "Error uploading products: " . $e->getMessage();
                }
                fclose($handle);
            }
        } else {
            $errors[] = "Please upload a valid CSV file (text/csv, non-empty).";
        }
    } else {
        $errors[] = "No file uploaded.";
    }

    // Store the result in session and redirect back to vendor_dashboard.php
    if ($success) {
        $_SESSION['bulk_upload_success'] = $success;
    }
    if ($errors) {
        $_SESSION['bulk_upload_errors'] = $errors;
    }
    header("Location: vendor_dashboard.php");
    exit;
} catch (PDOException $e) {
    $_SESSION['bulk_upload_errors'] = ["Database error: " . $e->getMessage()];
    header("Location: vendor_dashboard.php");
    exit;
}
?>