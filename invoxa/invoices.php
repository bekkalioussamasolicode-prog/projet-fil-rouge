<?php
// =========================================================================
// invoices.php - INVOICE LIST WITH FILTERS
// =========================================================================

require_once 'backend/database.php';
require_once 'backend/auth.php';
require_once 'backend/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$categories = getAllCategories($pdo);

// Read filter values from the URL
$filters = [
    'search'      => trim($_GET['search'] ?? ''),
    'category_id' => trim($_GET['category_id'] ?? ''),
    'date_range'  => trim($_GET['date_range'] ?? ''),
    'min_amount'  => trim($_GET['min_amount'] ?? ''),
    'max_amount'  => trim($_GET['max_amount'] ?? '')
];

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$invoices = getFilteredInvoices($pdo, $userId, $filters, $limit, $offset);
$totalInvoices = getFilteredInvoiceCount($pdo, $userId, $filters);
$totalPages = max(1, ceil($totalInvoices / $limit));

require_once 'header.php';
?>

<div class="overview-header">
    <div><h2>Invoices</h2><p>Manage and track your outgoing invoices.</p></div>
    <a href="invoice-create.php" class="btn-primary"><i class="fa-solid fa-plus"></i><span>Add Invoice</span></a>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert-box alert-box-success" style="max-width:100%;"><i class="fa-solid fa-circle-check"></i><span><?= htmlspecialchars($_GET['success']) ?></span></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="alert-box alert-box-error" style="max-width:100%;"><i class="fa-solid fa-circle-exclamation"></i><span><?= htmlspecialchars($_GET['error']) ?></span></div>
<?php endif; ?>

<div class="filters-panel">
    <div class="panel-title">Filters</div>
    <form method="GET" action="invoices.php">
        <div class="filters-row">
            <div class="filter-group">
                <label for="date_range">Date Range</label>
                <select id="date_range" name="date_range" class="filter-input">
                    <option value="" <?= $filters['date_range'] === '' ? 'selected' : '' ?>>All Time</option>
                    <option value="30" <?= $filters['date_range'] === '30' ? 'selected' : '' ?>>Last 30 Days</option>
                    <option value="90" <?= $filters['date_range'] === '90' ? 'selected' : '' ?>>Last 90 Days</option>
                    <option value="year" <?= $filters['date_range'] === 'year' ? 'selected' : '' ?>>This Year</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" class="filter-input">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $filters['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Amount Range</label>
                <div class="range-inputs">
                    <input type="number" step="0.01" name="min_amount" class="filter-input" placeholder="Min" value="<?= htmlspecialchars($filters['min_amount']) ?>">
                    <span style="color:var(--gray-text);">-</span>
                    <input type="number" step="0.01" name="max_amount" class="filter-input" placeholder="Max" value="<?= htmlspecialchars($filters['max_amount']) ?>">
                </div>
            </div>
            <div class="filter-group">
                <label for="search">Keywords</label>
                <input type="text" id="search" name="search" class="filter-input" placeholder="e.g. Acme Corp" value="<?= htmlspecialchars($filters['search']) ?>">
            </div>
            <div class="filter-actions">
                <a href="invoices.php" class="filter-btn-reset">Reset</a>
                <button type="submit" class="btn-primary" style="padding:10px 20px;">Apply</button>
            </div>
        </div>
    </form>
</div>

<div class="data-card">
    <div class="table-responsive">
        <table>
            <thead><tr><th>Merchant / Description</th><th>Category</th><th>Date</th><th>Amount</th><th style="text-align:right;">Actions</th></tr></thead>
            <tbody>
                <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td>
                        <div class="merchant-name">
                            <?php if (!empty($inv['file_path'])): ?>
                                <a href="<?= htmlspecialchars($inv['file_path']) ?>" target="_blank" style="color:var(--secondary);font-weight:600;display:inline-flex;align-items:center;gap:6px;">
                                    <i class="fa-regular fa-file-pdf"></i><?= htmlspecialchars($inv['title']) ?>
                                </a>
                            <?php else: ?>
                                <?= htmlspecialchars($inv['title']) ?>
                            <?php endif; ?>
                        </div>
                        <div class="merchant-desc"><?= htmlspecialchars($inv['description'] ?: 'No notes added.') ?></div>
                    </td>
                    <td><span class="badge" style="background-color:rgba(0,120,187,0.08);color:var(--secondary);border:1px solid rgba(0,120,187,0.15);"><?= htmlspecialchars($inv['category_name']) ?></span></td>
                    <td style="color:#475569;font-weight:500;"><?= date('M d, Y', strtotime($inv['invoice_date'])) ?></td>
                    <td class="amount-value" style="font-size:15px;"><?= number_format($inv['amount'], 2) ?> MAD</td>
                    <td style="text-align:right;">
                        <?php if (!empty($inv['file_path'])): ?>
                            <a href="<?= htmlspecialchars($inv['file_path']) ?>" target="_blank" class="action-icon-btn" title="View Document" style="color:var(--secondary);margin-right:10px;"><i class="fa-solid fa-eye"></i></a>
                        <?php endif; ?>
                        <a href="invoice-edit.php?id=<?= $inv['id'] ?>" class="action-icon-btn" title="Edit" style="color:var(--secondary);margin-right:10px;"><i class="fa-regular fa-pen-to-square"></i></a>
                        <a href="invoice-delete.php?id=<?= $inv['id'] ?>" class="action-icon-btn" title="Delete" onclick="return confirm('Are you sure you want to delete this invoice?');"><i class="fa-regular fa-trash-can"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($invoices)): ?>
                <tr><td colspan="5" style="text-align:center;color:var(--gray-text);padding:40px 0;">No invoices match your filters. <a href="invoices.php" style="color:var(--secondary);font-weight:600;">Reset Filters</a></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination-row">
        <div class="pagination-info">Showing <strong><?= $offset + 1 ?></strong> to <strong><?= min($offset + $limit, $totalInvoices) ?></strong> of <strong><?= $totalInvoices ?></strong> results</div>
        <div class="pagination-buttons">
            <a href="invoices.php?page=<?= $page - 1 ?>&<?= http_build_query(array_diff_key($filters, ['page'=>''])) ?>" class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>" <?= $page <= 1 ? 'onclick="return false;"' : '' ?>>Previous</a>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="invoices.php?page=<?= $i ?>&<?= http_build_query(array_diff_key($filters, ['page'=>''])) ?>" class="page-btn <?= $page === $i ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <a href="invoices.php?page=<?= $page + 1 ?>&<?= http_build_query(array_diff_key($filters, ['page'=>''])) ?>" class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>" <?= $page >= $totalPages ? 'onclick="return false;"' : '' ?>>Next</a>
        </div>
    </div>
    <?php else: ?>
    <div class="pagination-row">
        <div class="pagination-info">Showing <strong>1</strong> to <strong><?= $totalInvoices ?></strong> of <strong><?= $totalInvoices ?></strong> results</div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
