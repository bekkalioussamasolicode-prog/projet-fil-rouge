<?php
// =========================================================================
// invoice-delete.php - DELETE AN INVOICE
// =========================================================================
// No HTML page - just deletes and redirects back to the list.
// =========================================================================

require_once 'backend/database.php';
require_once 'backend/auth.php';
require_once 'backend/functions.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

if (deleteInvoice($pdo, $id, $userId)) {
    header("Location: invoices.php?success=Invoice deleted successfully.");
} else {
    header("Location: invoices.php?error=Failed to delete invoice.");
}
exit;