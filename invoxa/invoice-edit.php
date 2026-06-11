<?php
// =========================================================================
// invoice-edit.php - EDIT AN EXISTING INVOICE
// =========================================================================

require_once 'backend/database.php';
require_once 'backend/auth.php';
require_once 'backend/functions.php';
requireLogin();

$id     = (int)($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];
$invoice = getInvoiceById($pdo, $id, $userId);

if (!$invoice) {
    header("Location: invoices.php?error=Invoice not found.");
    exit;
}

$categories = getAllCategories($pdo);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $amount      = (float)($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $invoiceDate = $_POST['invoice_date'] ?? '';
    $categoryId  = (int)($_POST['category_id'] ?? 0);
    $isPaid      = isset($_POST['is_paid']);
    $paidAt      = $isPaid ? date('Y-m-d H:i:s') : null;
    $filePath    = $_POST['file_path'] ?? null;

    if (empty($title) || $amount <= 0 || empty($invoiceDate) || $categoryId <= 0) {
        header("Location: invoice-edit.php?id=$id&error=Please fill in all required fields.");
        exit;
    }

    $uploadedFile = uploadFile('invoice_file');
    if ($uploadedFile !== null) {
        deleteUploadedFile($invoice['file_path']);
        $filePath = $uploadedFile;
    }

    if (updateInvoice($pdo, $id, $title, $amount, $description, $invoiceDate, $paidAt, $filePath, $categoryId, $userId)) {
        header("Location: invoices.php?success=Invoice updated successfully.");
    } else {
        header("Location: invoice-edit.php?id=$id&error=Failed to update invoice.");
    }
    exit;
}

require_once 'header.php';
?>

<div class="overview-header">
    <div><h2>Edit Invoice</h2><p>Modify the details of your invoice below.</p></div>
    <a href="invoices.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i><span>Back to Invoices</span></a>
</div>

<?php if (isset($_GET['error'])): ?>
    <div class="alert-box alert-box-error" style="max-width:100%;"><i class="fa-solid fa-circle-exclamation"></i><span><?= htmlspecialchars($_GET['error']) ?></span></div>
<?php endif; ?>

<div class="invoice-form-layout">
    <div class="form-card">
        <form method="POST" action="invoice-edit.php?id=<?= $id ?>" enctype="multipart/form-data" id="invoice-creation-form">
            <input type="hidden" name="id" value="<?= $invoice['id'] ?>">
            <input type="hidden" name="file_path" id="form-file-path" value="<?= htmlspecialchars($invoice['file_path'] ?? '') ?>">
            <div class="form-row">
                <div class="form-group">
                    <label for="amount"><span>Invoice Amount *</span><span class="ocr-badge" id="ocr-amount-badge" style="display:none;"><i class="fa-solid fa-wand-magic-sparkles"></i> OCR Detected</span></label>
                    <div class="input-with-symbol"><i class="fa-solid fa-coins input-symbol"></i><input type="number" step="0.01" id="amount" name="amount" class="form-control" placeholder="0.00" required value="<?= htmlspecialchars($invoice['amount']) ?>"><span class="input-suffix">MAD</span></div>
                </div>
                <div class="form-group">
                    <label for="invoice_date"><span>Invoice Date *</span><span class="ocr-badge" id="ocr-date-badge" style="display:none;"><i class="fa-solid fa-wand-magic-sparkles"></i> OCR Detected</span></label>
                    <input type="date" id="invoice_date" name="invoice_date" class="form-control" required value="<?= htmlspecialchars($invoice['invoice_date']) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="title"><span>Vendor Name *</span><span class="ocr-badge" id="ocr-vendor-badge" style="display:none;"><i class="fa-solid fa-wand-magic-sparkles"></i> OCR Detected</span></label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="e.g. Maroc Telecom" required value="<?= htmlspecialchars($invoice['title']) ?>">
                </div>
                <div class="form-group">
                    <label for="category_id"><span>Category *</span><span class="ocr-badge" id="ocr-category-badge" style="display:none;"><i class="fa-solid fa-wand-magic-sparkles"></i> OCR Detected</span></label>
                    <select id="category_id" name="category_id" class="form-control" required>
                        <option value="">Select category...</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $invoice['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group full-width">
                <label for="description">Description / Notes</label>
                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Add any relevant details..."><?= htmlspecialchars($invoice['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group" style="flex-direction:row;align-items:center;gap:10px;margin-top:10px;">
                <input type="checkbox" id="is_paid" name="is_paid" style="width:18px;height:18px;cursor:pointer;" <?= $invoice['paid_at'] !== null ? 'checked' : '' ?>>
                <label for="is_paid" style="cursor:pointer;font-size:13px;font-weight:500;text-transform:none;color:#475569;">Mark this invoice as Paid</label>
            </div>
            <div class="form-actions">
                <a href="invoices.php" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary"><i class="fa-regular fa-floppy-disk"></i><span>Update Invoice</span></button>
            </div>
        </form>
    </div>
    <div class="upload-sidebar-card">
        <h4>Original Document</h4>
        <?php $hasFile = !empty($invoice['file_path']); ?>
        <div class="drag-drop-zone" id="ocr-drag-zone" style="<?= $hasFile ? 'display:none;' : '' ?>">
            <i class="fa-solid fa-cloud-arrow-up zone-icon"></i>
            <div class="zone-title">Drag & drop invoice here</div>
            <div class="zone-desc">Supports PDF, JPG, PNG (Max 10MB)</div>
            <input type="file" id="ocr-file-input" class="browse-input" accept=".pdf,.png,.jpg,.jpeg">
            <button type="button" class="btn-secondary" style="padding:8px 16px;font-size:12px;" onclick="document.getElementById('ocr-file-input').click()">Browse Files</button>
        </div>
        <div id="ocr-upload-progress" style="display:none;padding:10px 0;">
            <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--primary);font-weight:600;">
                <span id="ocr-progress-filename">Uploading...</span><span id="ocr-progress-percentage">0%</span>
            </div>
            <div class="progress-bar-container"><div class="progress-bar-fill" id="ocr-progress-bar-fill" style="width:0%;"></div></div>
            <div class="ocr-loading-status"><i class="fa-solid fa-spinner fa-spin"></i><span id="ocr-status-text">Uploading...</span></div>
        </div>
        <div class="uploaded-files-list" id="ocr-uploaded-file-display" style="<?= $hasFile ? 'display:flex;' : 'display:none;' ?>">
            <div class="file-item">
                <div class="file-info">
                    <i class="fa-regular fa-file-pdf file-icon" id="uploaded-file-icon"></i>
                    <div class="file-details">
                        <div class="file-name" id="uploaded-file-name"><?= $hasFile ? htmlspecialchars(basename($invoice['file_path'])) : '' ?></div>
                        <div class="file-meta" id="uploaded-file-meta">Attached Document</div>
                    </div>
                </div>
                <button type="button" class="file-remove-btn" onclick="clearOcrDocument()" title="Remove file"><i class="fa-solid fa-xmark"></i></button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>