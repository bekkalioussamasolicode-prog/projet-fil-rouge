<?php
// =========================================================================
// upload.php - SMART FILE UPLOAD PAGE
// =========================================================================

require_once 'backend/database.php';
require_once 'backend/auth.php';
require_once 'backend/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$recentProcessed = getFilteredInvoices($pdo, $userId, [], 5, 0);

require_once 'header.php';
?>

<div class="overview-header">
    <div><h2>Smart File Upload</h2><p>Upload invoices or receipts for AI-powered data extraction.</p></div>
    <a href="invoice-create.php" class="btn-primary"><i class="fa-solid fa-pen-to-square"></i><span>Manual Invoice Entry</span></a>
</div>

<div class="smart-upload-layout">
    <div class="form-card" style="display:flex;flex-direction:column;justify-content:center;min-height:400px;padding:40px;">
        <div class="drag-drop-zone" id="bulk-drag-zone" style="flex:1;display:flex;flex-direction:column;justify-content:center;align-items:center;padding:60px 40px;">
            <i class="fa-solid fa-cloud-arrow-up zone-icon" style="font-size:54px;margin-bottom:20px;"></i>
            <div class="zone-title" style="font-size:18px;margin-bottom:8px;">Drag & Drop files here</div>
            <div class="zone-desc" style="font-size:13px;margin-bottom:24px;">Supported formats: PDF, JPG, PNG. Maximum file size 25MB.</div>
            <input type="file" id="bulk-file-input" class="browse-input" multiple accept=".pdf,.png,.jpg,.jpeg">
            <button type="button" class="btn-primary" style="padding:12px 28px;" onclick="document.getElementById('bulk-file-input').click()">Browse Files</button>
            <div style="display:flex;gap:20px;margin-top:30px;color:var(--gray-text);font-size:13px;">
                <span><i class="fa-regular fa-file-pdf"></i> PDF</span>
                <span><i class="fa-regular fa-file-image"></i> JPG/PNG</span>
            </div>
        </div>
    </div>
    <div class="upload-sidebar-card">
        <div class="current-activity-section">
            <h4>Current Activity</h4>
            <div id="bulk-active-upload-card" class="file-item" style="display:none;flex-direction:column;align-items:stretch;gap:8px;background-color:var(--bg-color);border:1px solid var(--border-color);padding:14px;border-radius:8px;">
                <div style="display:flex;justify-content:space-between;align-items:center;font-size:12px;font-weight:600;color:var(--primary);">
                    <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                        <i class="fa-solid fa-spinner fa-spin" style="color:var(--secondary);"></i>
                        <span id="bulk-active-filename" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;">Uploading...</span>
                    </div>
                    <span id="bulk-active-percentage">0%</span>
                </div>
                <div class="progress-bar-container"><div class="progress-bar-fill" id="bulk-active-bar-fill" style="width:0%;"></div></div>
                <div class="ocr-loading-status" style="margin-top:2px;"><span id="bulk-active-status" style="font-size:11px;color:var(--secondary);font-weight:600;">AI Processing...</span></div>
            </div>
            <p id="bulk-activity-placeholder" style="font-size:13px;color:var(--gray-text);text-align:center;padding:20px 0;border:1px dashed var(--border-color);border-radius:8px;">No files currently uploading.</p>
        </div>
        <div class="recently-processed-section" style="margin-top:10px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                <h4 style="margin-bottom:0;">Recently Processed</h4>
                <a href="invoices.php" class="link-action" style="font-size:11px;">View All</a>
            </div>
            <div class="uploaded-files-list" id="bulk-processed-list">
                <?php
                $hasProcessed = false;
                foreach ($recentProcessed as $inv):
                    if (!empty($inv['file_path'])):
                        $hasProcessed = true;
                        $ext = strtolower(pathinfo($inv['file_path'], PATHINFO_EXTENSION));
                        $iconClass = ($ext === 'pdf') ? 'fa-regular fa-file-pdf' : 'fa-regular fa-file-image';
                        $iconColor = ($ext === 'pdf') ? 'var(--danger)' : 'var(--secondary)';
                ?>
                    <div class="file-item" style="background-color:var(--white);border:1px solid var(--border-color);padding:12px 14px;">
                        <div class="file-info" style="gap:10px;">
                            <i class="<?= $iconClass ?> file-icon" style="color:<?= $iconColor ?>;font-size:18px;"></i>
                            <div class="file-details">
                                <div class="file-name" style="font-size:12px;font-weight:600;color:var(--primary);"><?= htmlspecialchars(basename($inv['file_path'])) ?></div>
                                <div class="file-meta" style="font-size:11px;color:var(--gray-text);"><?= htmlspecialchars($inv['title']) ?> - <?= date('M d, H:i', strtotime($inv['created_at'])) ?></div>
                            </div>
                        </div>
                        <span style="color:var(--success);font-size:16px;"><i class="fa-solid fa-circle-check"></i></span>
                    </div>
                <?php endif; endforeach; ?>
                <?php if (!$hasProcessed): ?>
                    <p id="no-processed-yet" style="font-size:12px;color:var(--gray-text);text-align:center;padding:15px 0;">No processed uploads yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>