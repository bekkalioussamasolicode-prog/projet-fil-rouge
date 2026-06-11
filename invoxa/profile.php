<?php
// =========================================================================
// profile.php - USER PROFILE SETTINGS
// =========================================================================

require_once 'backend/database.php';
require_once 'backend/auth.php';
require_once 'backend/functions.php';
requireLogin();

$error = '';
$success = '';
$userId = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ---- Update personal info ----
    if ($action === 'update_profile') {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName  = trim($_POST['last_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');

        if (empty($firstName) || empty($lastName) || empty($email)) {
            $error = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            $existingUser = getUserByEmail($pdo, $email);
            if ($existingUser && (int)$existingUser['id'] !== $userId) {
                $error = "This email is already registered to another user.";
            } else {
                if (updateUserProfile($pdo, $userId, $firstName, $lastName, $email)) {
                    $_SESSION['user_first_name'] = $firstName;
                    $_SESSION['user_last_name'] = $lastName;
                    $_SESSION['user_email'] = $email;
                    $success = "Personal information updated successfully!";
                } else {
                    $error = "Failed to update profile.";
                }
            }
        }
    }

    // ---- Change password ----
    if ($action === 'update_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = "All password fields are required.";
        } elseif ($newPassword !== $confirmPassword) {
            $error = "New passwords do not match.";
        } elseif (strlen($newPassword) < 6) {
            $error = "New password must be at least 6 characters long.";
        } else {
            $dbUser = getUserByEmail($pdo, $_SESSION['user_email']);
            if (password_verify($currentPassword, $dbUser['password_hash'])) {
                if (updateUserPassword($pdo, $userId, $newPassword)) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Failed to update password.";
                }
            } else {
                $error = "Current password is incorrect.";
            }
        }
    }
}

// Get fresh user data for the form
$user = getUserByEmail($pdo, $_SESSION['user_email']);

require_once 'header.php';
?>

<div class="profile-card-header">
    <div class="profile-avatar-large"><?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? 'S', 0, 1)) ?></div>
    <div class="profile-meta-details">
        <h3><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h3>
        <p><?= htmlspecialchars($user['email']) ?></p>
        <span class="badge-role"><?= htmlspecialchars(ucfirst($user['role'] ?? 'user')) ?></span>
        <span class="badge-plan">Pro Plan</span>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert-box alert-box-error" style="max-width:800px;"><i class="fa-solid fa-circle-exclamation"></i><span><?= htmlspecialchars($error) ?></span></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert-box alert-box-success" style="max-width:800px;"><i class="fa-solid fa-circle-check"></i><span><?= htmlspecialchars($success) ?></span></div>
<?php endif; ?>

<div class="profile-layout" style="max-width:1000px;">
    <div class="profile-tabs-sidebar">
        <button class="profile-tab-btn active" onclick="switchProfileTab('account-tab', this)"><i class="fa-regular fa-user"></i><span>Account</span></button>
        <button class="profile-tab-btn" onclick="switchProfileTab('security-tab', this)"><i class="fa-solid fa-shield-halved"></i><span>Security</span></button>
        <button class="profile-tab-btn" onclick="switchProfileTab('notifications-tab', this)"><i class="fa-regular fa-bell"></i><span>Notifications</span></button>
    </div>
    <div class="profile-panels-content">
        <!-- Account Tab -->
        <div id="account-tab" class="profile-tab-panel">
            <div class="form-card">
                <h4 style="font-size:18px;color:var(--primary);margin-bottom:20px;font-weight:600;">Personal Information</h4>
                <form method="POST" action="profile.php">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-row">
                        <div class="form-group"><label for="first_name">First Name</label><input type="text" id="first_name" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required></div>
                        <div class="form-group"><label for="last_name">Last Name</label><input type="text" id="last_name" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required></div>
                    </div>
                    <div class="form-group"><label for="email">Email Address</label><input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required></div>
                    <div class="form-group"><label for="business_name">Business Name <span style="font-size:10px;color:var(--gray-text);">(Optional)</span></label><input type="text" id="business_name" name="business_name" class="form-control" placeholder="e.g. Acme Corp Solutions" value="Acme Corp Solutions"></div>
                    <div class="form-actions" style="margin-top:20px;padding-top:15px;">
                        <button type="button" class="btn-secondary" onclick="window.location.reload();">Cancel</button>
                        <button type="submit" class="btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Security Tab -->
        <div id="security-tab" class="profile-tab-panel" style="display:none;">
            <div class="form-card">
                <h4 style="font-size:18px;color:var(--primary);margin-bottom:20px;font-weight:600;">Change Password</h4>
                <form method="POST" action="profile.php">
                    <input type="hidden" name="action" value="update_password">
                    <div class="form-group"><label for="current_password">Current Password</label><input type="password" id="current_password" name="current_password" class="form-control" placeholder="--------" required></div>
                    <div class="form-group"><label for="new_password">New Password</label><input type="password" id="new_password" name="new_password" class="form-control" placeholder="--------" required></div>
                    <div class="form-group"><label for="confirm_password">Confirm New Password</label><input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="--------" required></div>
                    <div class="form-actions" style="margin-top:20px;padding-top:15px;">
                        <button type="button" class="btn-secondary" onclick="window.location.reload();">Cancel</button>
                        <button type="submit" class="btn-primary">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Notifications Tab (demo) -->
        <div id="notifications-tab" class="profile-tab-panel" style="display:none;">
            <div class="form-card">
                <h4 style="font-size:18px;color:var(--primary);margin-bottom:20px;font-weight:600;">Notification Preferences</h4>
                <p style="color:var(--gray-text);font-size:14px;margin-bottom:20px;">Manage how you receive alerts and reminders.</p>
                <div class="form-group" style="flex-direction:row;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border-color);">
                    <div><strong style="display:block;font-size:14px;color:var(--primary);">Email Notifications</strong><span style="font-size:12px;color:var(--gray-text);">Receive summaries of monthly spending.</span></div>
                    <input type="checkbox" checked style="width:20px;height:20px;cursor:pointer;">
                </div>
                <div class="form-group" style="flex-direction:row;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border-color);">
                    <div><strong style="display:block;font-size:14px;color:var(--primary);">Overdue Reminders</strong><span style="font-size:12px;color:var(--gray-text);">Get alerts when invoices are past due.</span></div>
                    <input type="checkbox" checked style="width:20px;height:20px;cursor:pointer;">
                </div>
                <div class="form-actions" style="margin-top:20px;padding-top:15px;">
                    <button type="button" class="btn-secondary" onclick="window.location.reload();">Cancel</button>
                    <button type="button" class="btn-primary" onclick="alert('Notification settings updated (simulation)');">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchProfileTab(panelId, btnElement) {
    document.querySelectorAll('.profile-tab-panel').forEach(p => p.style.display = 'none');
    document.getElementById(panelId).style.display = 'block';
    document.querySelectorAll('.profile-tab-btn').forEach(b => b.classList.remove('active'));
    btnElement.classList.add('active');
}
</script>

<?php require_once 'footer.php'; ?>