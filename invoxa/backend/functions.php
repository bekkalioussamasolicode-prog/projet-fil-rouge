<?php
// ---- USER FUNCTIONS -------------------------------------------------------

function getUserByEmail($pdo, $email)
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    return $stmt->fetch(); // Returns array or false
}

function createUser($pdo, $firstName, $lastName, $email, $password)
{
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES (:fn, :ln, :em, :ph, 'user')");
    $stmt->execute(['fn' => $firstName, 'ln' => $lastName, 'em' => $email, 'ph' => $hash]);
    return $pdo->lastInsertId();
}

function updateUserProfile($pdo, $id, $firstName, $lastName, $email)
{
    $stmt = $pdo->prepare("UPDATE users SET first_name=:fn, last_name=:ln, email=:em WHERE id=:id");
    return $stmt->execute(['fn' => $firstName, 'ln' => $lastName, 'em' => $email, 'id' => $id]);
}

function updateUserPassword($pdo, $id, $newPassword)
{
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash=:ph WHERE id=:id");
    return $stmt->execute(['ph' => $hash, 'id' => $id]);
}


// ---- CATEGORY FUNCTIONS ---------------------------------------------------

function getAllCategories($pdo)
{
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    return $stmt->fetchAll();
}


// ---- INVOICE FUNCTIONS ----------------------------------------------------

function createInvoice($pdo, $title, $amount, $description, $invoiceDate, $paidAt, $filePath, $userId, $categoryId)
{
    $stmt = $pdo->prepare("INSERT INTO factures (title, amount, description, invoice_date, paid_at, file_path, user_id, category_id) VALUES (:t, :a, :d, :id, :pa, :fp, :ui, :ci)");
    return $stmt->execute([
        't' => $title,
        'a' => $amount,
        'd' => $description,
        'id' => $invoiceDate,
        'pa' => $paidAt ?: null,
        'fp' => $filePath,
        'ui' => $userId,
        'ci' => $categoryId
    ]);
}

function getInvoiceById($pdo, $id, $userId)
{
    $stmt = $pdo->prepare("SELECT f.*, c.name as category_name FROM factures f JOIN categories c ON f.category_id = c.id WHERE f.id = :id AND f.user_id = :uid LIMIT 1");
    $stmt->execute(['id' => $id, 'uid' => $userId]);
    return $stmt->fetch();
}

function deleteInvoice($pdo, $id, $userId)
{
    // First get the invoice to delete its file
    $invoice = getInvoiceById($pdo, $id, $userId);
    if ($invoice && !empty($invoice['file_path'])) {
        $fullPath = __DIR__ . '/../' . $invoice['file_path'];
        if (file_exists($fullPath)) unlink($fullPath);
    }
    $stmt = $pdo->prepare("DELETE FROM factures WHERE id = :id AND user_id = :uid");
    return $stmt->execute(['id' => $id, 'uid' => $userId]);
}

function updateInvoice($pdo, $id, $title, $amount, $description, $invoiceDate, $paidAt, $filePath, $categoryId, $userId)
{
    $sql = "UPDATE factures SET title=:t, amount=:a, description=:d, invoice_date=:id, paid_at=:pa, category_id=:ci";
    $params = ['t' => $title, 'a' => $amount, 'd' => $description, 'id' => $invoiceDate, 'pa' => $paidAt ?: null, 'ci' => $categoryId, 'iid' => $id, 'uid' => $userId];
    if ($filePath !== null) {
        $sql .= ", file_path=:fp";
        $params['fp'] = $filePath;
    }
    $sql .= " WHERE id=:iid AND user_id=:uid";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

function getFilteredInvoices($pdo, $userId, $filters = [], $limit = 10, $offset = 0)
{
    $sql = "SELECT f.*, c.name as category_name FROM factures f JOIN categories c ON f.category_id = c.id WHERE f.user_id = :uid";
    $params = ['uid' => $userId];

    // Add filters
    if (!empty($filters['search'])) {
        $sql .= " AND (f.title LIKE :search OR f.description LIKE :search)";
        $params['search'] = '%' . $filters['search'] . '%';
    }
    if (!empty($filters['category_id'])) {
        $sql .= " AND f.category_id = :cid";
        $params['cid'] = $filters['category_id'];
    }
    if (isset($filters['min_amount']) && $filters['min_amount'] !== '') {
        $sql .= " AND f.amount >= :min";
        $params['min'] = $filters['min_amount'];
    }
    if (isset($filters['max_amount']) && $filters['max_amount'] !== '') {
        $sql .= " AND f.amount <= :max";
        $params['max'] = $filters['max_amount'];
    }
    if (!empty($filters['date_range'])) {
        if ($filters['date_range'] === '30') $sql .= " AND f.invoice_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        elseif ($filters['date_range'] === '90') $sql .= " AND f.invoice_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
        elseif ($filters['date_range'] === 'year') $sql .= " AND YEAR(f.invoice_date) = YEAR(CURDATE())";
    }

    $sql .= " ORDER BY f.invoice_date DESC, f.id DESC";
    $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getFilteredInvoiceCount($pdo, $userId, $filters = [])
{
    $sql = "SELECT COUNT(*) as total FROM factures f WHERE f.user_id = :uid";
    $params = ['uid' => $userId];

    if (!empty($filters['search'])) {
        $sql .= " AND (f.title LIKE :search OR f.description LIKE :search)";
        $params['search'] = '%' . $filters['search'] . '%';
    }
    if (!empty($filters['category_id'])) {
        $sql .= " AND f.category_id = :cid";
        $params['cid'] = $filters['category_id'];
    }
    if (isset($filters['min_amount']) && $filters['min_amount'] !== '') {
        $sql .= " AND f.amount >= :min";
        $params['min'] = $filters['min_amount'];
    }
    if (isset($filters['max_amount']) && $filters['max_amount'] !== '') {
        $sql .= " AND f.amount <= :max";
        $params['max'] = $filters['max_amount'];
    }
    if (!empty($filters['date_range'])) {
        if ($filters['date_range'] === '30') $sql .= " AND f.invoice_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        elseif ($filters['date_range'] === '90') $sql .= " AND f.invoice_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
        elseif ($filters['date_range'] === 'year') $sql .= " AND YEAR(f.invoice_date) = YEAR(CURDATE())";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ? (int)$row['total'] : 0;
}

function getDashboardStats($pdo, $userId)
{
    // Total expenses
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM factures WHERE user_id = :uid");
    $stmt->execute(['uid' => $userId]);
    $row = $stmt->fetch();
    $totalExpenses = $row ? (float)$row['total'] : 0.0;

    // Pending count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM factures WHERE user_id = :uid AND paid_at IS NULL");
    $stmt->execute(['uid' => $userId]);
    $row = $stmt->fetch();
    $pendingInvoices = $row ? (int)$row['total'] : 0;

    // This month total
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM factures WHERE user_id = :uid AND MONTH(invoice_date) = MONTH(CURDATE()) AND YEAR(invoice_date) = YEAR(CURDATE())");
    $stmt->execute(['uid' => $userId]);
    $row = $stmt->fetch();
    $currMonthTotal = $row ? (float)$row['total'] : 0.0;

    // Last month total
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM factures WHERE user_id = :uid AND MONTH(invoice_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(invoice_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
    $stmt->execute(['uid' => $userId]);
    $row = $stmt->fetch();
    $lastMonthTotal = $row ? (float)$row['total'] : 0.0;

    // Trend %
    $trendPercentage = 0;
    if ($lastMonthTotal > 0) {
        $trendPercentage = (($currMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100;
    }

    return [
        'total_expenses' => $totalExpenses,
        'pending_invoices' => $pendingInvoices,
        'current_month_total' => $currMonthTotal,
        'last_month_total' => $lastMonthTotal,
        'trend_percentage' => round($trendPercentage, 1)
    ];
}

function getExpensesByCategory($pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT c.name as category_name, SUM(f.amount) as total FROM factures f JOIN categories c ON f.category_id = c.id WHERE f.user_id = :uid GROUP BY f.category_id");
    $stmt->execute(['uid' => $userId]);
    $rows = $stmt->fetchAll();

    $grandTotal = 0;
    foreach ($rows as $row) $grandTotal += $row['total'];

    $result = [];
    foreach ($rows as $row) {
        $result[] = [
            'category_name' => $row['category_name'],
            'total' => (float)$row['total'],
            'percentage' => ($grandTotal > 0) ? round(($row['total'] / $grandTotal) * 100) : 0
        ];
    }
    return $result;
}

function getMonthlySpendingHistory($pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(invoice_date, '%b') as month_label, DATE_FORMAT(invoice_date, '%Y-%m') as year_month_key, SUM(amount) as total FROM factures WHERE user_id = :uid AND invoice_date >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH) GROUP BY year_month_key ORDER BY year_month_key ASC");
    $stmt->execute(['uid' => $userId]);
    $results = $stmt->fetchAll();

    $history = [];
    for ($i = 5; $i >= 0; $i--) {
        $timestamp = strtotime("-$i month");
        $label = date('M', $timestamp);
        $key = date('Y-m', $timestamp);
        $total = 0.0;
        foreach ($results as $res) {
            if ($res['year_month_key'] === $key) {
                $total = (float)$res['total'];
                break;
            }
        }
        $history[] = ['month' => $label, 'total' => $total];
    }
    return $history;
}


// ---- FILE HELPERS ---------------------------------------------------------

function uploadFile($fileInputName)
{
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $tmpPath = $_FILES[$fileInputName]['tmp_name'];
    $originalName = $_FILES[$fileInputName]['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($extension, ['pdf', 'png', 'jpg', 'jpeg'])) return null;

    $newName = uniqid('invoice_', true) . '.' . $extension;
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $destination = $uploadDir . $newName;
    if (move_uploaded_file($tmpPath, $destination)) {
        return 'uploads/' . $newName;
    }
    return null;
}

function deleteUploadedFile($filePath)
{
    if (empty($filePath)) return;
    $fullPath = __DIR__ . '/../' . $filePath;
    if (file_exists($fullPath)) unlink($fullPath);
}
