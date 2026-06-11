<?php
// =========================================================================
// dashboard.php - MAIN OVERVIEW PAGE
// =========================================================================

require_once 'backend/database.php';
require_once 'backend/auth.php';
require_once 'backend/functions.php';
requireLogin();

$userId = $_SESSION['user_id'];
$stats = getDashboardStats($pdo, $userId);
$monthlyHistory = getMonthlySpendingHistory($pdo, $userId);
$categoryBreakdown = getExpensesByCategory($pdo, $userId);
$recentInvoices = getFilteredInvoices($pdo, $userId, [], 5, 0);

$totalSaved = max(0, $stats['last_month_total'] - $stats['current_month_total']);
$savingsGoalPercentage = ($stats['last_month_total'] > 0) ? round(($totalSaved / $stats['last_month_total']) * 100) : 0;

require_once 'header.php';
?>

<div class="overview-header">
    <div>
        <h2>Overview</h2>
        <p>Welcome back, <?= htmlspecialchars($_SESSION['user_first_name']) ?>. Here is your financial summary.</p>
    </div>
    <a href="invoice-create.php" class="btn-primary"><i class="fa-solid fa-plus"></i><span>Upload New Invoice</span></a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div>
            <div class="stat-title">Total Expenses</div>
            <div class="stat-value"><?= number_format($stats['total_expenses'], 2) ?> MAD</div>
            <div class="stat-desc">
                <?php if ($stats['trend_percentage'] > 0): ?>
                    <span class="trend-up"><i class="fa-solid fa-arrow-up-right"></i> +<?= $stats['trend_percentage'] ?>%</span>
                <?php elseif ($stats['trend_percentage'] < 0): ?>
                    <span class="trend-down" style="color:var(--success);"><i class="fa-solid fa-arrow-down-left"></i> <?= $stats['trend_percentage'] ?>%</span>
                <?php else: ?>
                    <span class="trend-neutral">0% change</span>
                <?php endif; ?>
                <span style="color:var(--gray-text);">from last month</span>
            </div>
        </div>
        <div class="stat-icon-box bg-red-light"><i class="fa-solid fa-arrow-trend-up"></i></div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-title">Pending Invoices</div>
            <div class="stat-value"><?= $stats['pending_invoices'] ?></div>
            <div class="stat-desc"><span class="trend-neutral" style="font-weight:600;color:var(--secondary);">Requires approval</span></div>
        </div>
        <div class="stat-icon-box bg-blue-light"><i class="fa-regular fa-clock"></i></div>
    </div>
    <div class="stat-card">
        <div>
            <div class="stat-title">Total Saved</div>
            <div class="stat-value"><?= number_format($totalSaved, 2) ?> MAD</div>
            <div class="stat-desc"><span class="trend-neutral" style="color:var(--success);font-weight:600;">Goal <?= $savingsGoalPercentage ?>% reached</span></div>
        </div>
        <div class="stat-icon-box bg-green-light"><i class="fa-solid fa-wallet"></i></div>
    </div>
</div>

<div class="charts-grid">
    <div class="chart-card">
        <div class="chart-header"><h3>Monthly Spending</h3></div>
        <div class="chart-body"><canvas id="monthlySpendingChart"></canvas></div>
    </div>
    <div class="chart-card">
        <div class="chart-header"><h3>Expenses by Category</h3></div>
        <div class="chart-body" style="height:180px;"><canvas id="expensesByCategoryChart"></canvas></div>
        <div class="donut-legend">
            <?php
            $dotColors = ['#1C335C', '#0078BB', '#98A3A9', '#10B981', '#F59E0B', '#64748B'];
            $colorIdx = 0;
            foreach ($categoryBreakdown as $cat):
                $color = $dotColors[$colorIdx % count($dotColors)]; $colorIdx++;
            ?>
                <div class="legend-item">
                    <div class="legend-label-group">
                        <span class="legend-color-dot" style="background-color:<?= $color ?>"></span>
                        <span><?= htmlspecialchars($cat['category_name']) ?></span>
                    </div>
                    <span class="legend-value"><?= $cat['percentage'] ?>%</span>
                </div>
            <?php endforeach; ?>
            <?php if (empty($categoryBreakdown)): ?>
                <p style="text-align:center;color:var(--gray-text);font-size:12px;padding-top:10px;">No categories recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="data-card">
    <div class="card-header-row">
        <h3>Recent Invoices</h3>
        <a href="invoices.php" class="link-action"><span>View All</span><i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Date</th><th>Merchant</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($recentInvoices as $inv): ?>
                <tr>
                    <td style="color:#475569;font-weight:500;"><?= date('M d, Y', strtotime($inv['invoice_date'])) ?></td>
                    <td>
                        <div class="merchant-name"><?= htmlspecialchars($inv['title']) ?></div>
                        <div class="merchant-desc"><?= htmlspecialchars(substr($inv['description'] ?? 'No notes', 0, 45)) ?>...</div>
                    </td>
                    <td class="amount-value"><?= number_format($inv['amount'], 2) ?> MAD</td>
                    <td>
                        <?php if ($inv['paid_at'] !== null): ?>
                            <span class="badge badge-paid">Paid</span>
                        <?php elseif ($inv['invoice_date'] < date('Y-m-d')): ?>
                            <span class="badge badge-overdue">Overdue</span>
                        <?php else: ?>
                            <span class="badge badge-pending">Processing</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recentInvoices)): ?>
                <tr><td colspan="4" style="text-align:center;color:var(--gray-text);padding:30px 0;">No invoices yet. <a href="invoice-create.php" style="color:var(--secondary);font-weight:600;">Add your first invoice!</a></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const monthlySpendingData = <?= json_encode($monthlyHistory) ?>;
    const categoryBreakdownData = <?= json_encode($categoryBreakdown) ?>;
</script>

<?php require_once 'footer.php'; ?>
