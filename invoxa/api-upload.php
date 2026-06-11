<?php
require_once 'backend/database.php';
require_once 'backend/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File upload error.']);
    exit;
}

$tmpPath   = $_FILES['file']['tmp_name'];
$fileName  = $_FILES['file']['name'];
$fileSize  = $_FILES['file']['size'];
$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!in_array($extension, ['pdf', 'png', 'jpg', 'jpeg'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid file format. Only PDF, JPG, PNG supported.']);
    exit;
}

// Save the file
$newName = uniqid('invoice_ocr_', true) . '.' . $extension;
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$destination = $uploadDir . $newName;
if (!move_uploaded_file($tmpPath, $destination)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file.']);
    exit;
}

$relativePath = 'uploads/' . $newName;

// ---- SIMULATED OCR: Match filename keywords to known vendors ----
$vendorLookup = [
    [['telecom', 'maroc'], 'Maroc Telecom', 1250.00, '2023-10-24', 1],
    [['marjane'], 'Marjane', 4500.00, '2023-10-22', 4],
    [['electro', 'planet'], 'Electroplanet', 850.00, '2023-05-10', 2],
    [['essalam', 'coffee'], 'Boutique Essalam', 120.50, '2023-10-18', 4],
    [['attijari', 'bank'], 'Attijariwafa Bank', 3200.00, '2023-10-15', 3],
    [['redal', 'utility'], 'Redal', 3250.00, '2023-10-15', 5],
];

$extractedVendor   = 'Boutique Essalam';
$extractedAmount   = 120.50;
$extractedDate     = date('Y-m-d');
$extractedCategory = 4;
$extractedDesc     = 'Extracted invoice from file: ' . $fileName;

$lowerName = strtolower($fileName);
$matched = false;
foreach ($vendorLookup as $v) {
    foreach ($v[0] as $keyword) {
        if (strpos($lowerName, $keyword) !== false) {
            $extractedVendor   = $v[1];
            $extractedAmount   = $v[2];
            $extractedDate     = $v[3];
            $extractedCategory = $v[4];
            $matched = true;
            break 2;
        }
    }
}

if (!$matched) {
    $vendors = ['Inwi', 'Orange Maroc', 'Marjane', 'Redal', 'Lydec', 'Electroplanet', 'Decathlon Maroc'];
    $extractedVendor   = $vendors[array_rand($vendors)];
    $extractedAmount   = round(rand(50, 5000) + (rand(0, 99) / 100), 2);
    $extractedDate     = date('Y-m-d', strtotime('-' . rand(1, 28) . ' days'));
    $extractedCategory = rand(1, 6);
}

echo json_encode([
    'success'   => true,
    'message'   => 'OCR Extraction Completed.',
    'file_path' => $relativePath,
    'file_name' => $fileName,
    'file_size' => round($fileSize / (1024 * 1024), 1) . ' MB',
    'data'      => [
        'vendor'      => $extractedVendor,
        'amount'      => $extractedAmount,
        'date'        => $extractedDate,
        'category_id' => $extractedCategory,
        'description' => $extractedDesc
    ]
]);
