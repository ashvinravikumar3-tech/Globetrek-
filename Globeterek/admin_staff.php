<?php
$allowed_roles = ['admin'];
require 'auth_check.php';
require 'db.php';
require 'header.php';

$action = $_GET['action'] ?? 'list';
$error = '';

// ---------- DELETE ----------
if ($action === 'delete' && isset($_GET['id'])) {
    // prevent admin from deleting their own account
    if ($_GET['id'] == $_SESSION['user_id']) {
        $error = "You cannot delete your own account while logged in.";
        $action = 'list';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role IN ('staff','admin')");
            $stmt->execute([$_GET['id']]);
            header('Location: admin_staff.php');
            exit;
        } catch (PDOException $e) {
            $error = "Cannot delete: this account is linked to existing records (e.g. packages they created).";
            $action = 'list';
        }
    }
}

// ---------- ADD / EDIT FORM SUBMIT ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || !in_array($role, ['staff', 'admin'])) {
        $error = "Please fill all fields correctly.";
    } else {
        try {
            if (isset($_POST['user_id']) && $_POST['user_id'] !== '') {
                // editing existing staff/admin
                if (!empty($password)) {
                    if (strlen($password) < 6) {
                        $error = "Password must be at least 6 characters.";
                    } else {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=?, password=? WHERE user_id=?");
                        $stmt->execute([$name, $email, $role, $hashed, $_POST['user_id']]);
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=? WHERE user_id=?");
                    $stmt->execute([$name, $email, $role, $_POST['user_id']]);
                }
            } else {
                // creating new staff/admin
                if (empty($password) || strlen($password) < 6) {
                    $error = "Password is required and must be at least 6 characters.";
                } else {
                    $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
                    $check->execute([$email]);
                    if ($check->fetch()) {
                        $error = "Email already registered.";
                    } else {
                        $hashed = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$name, $email, $hashed, $role]);
                    }
                }
            }

            if (empty($error)) {
                header('Location: admin_staff.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// ---------- EDIT: fetch existing record ----------
$editing = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role IN ('staff','admin')");
    $stmt->execute([$_GET['id']]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="section-header">
    <div>
        <h2>Manage Staff & Admin Accounts</h2>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">Manage workspace access, create staff roles, and configure system permissions.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="contact-message error" style="margin-top: 20px;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="card" style="padding: 2.5rem; margin-top: 30px; margin-bottom: 40px; border-radius: 20px;">
    <h3 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 25px; font-weight: 700;">
        <?= $editing ? 'Edit Account' : 'Add New Staff / Admin Account' ?>
    </h3>
    <form method="POST" action="admin_staff.php" style="display: flex; flex-direction: column; gap: 20px;">
        <?php if ($editing): ?>
            <input type="hidden" name="user_id" value="<?= $editing['user_id'] ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-secondary);">Name:</label>
                <input type="text" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required style="margin-bottom: 0; max-width: 100%;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-secondary);">Email Address:</label>
                <input type="email" name="email" value="<?= htmlspecialchars($editing['email'] ?? '') ?>" required style="margin-bottom: 0; max-width: 100%;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-secondary);">Role / Permissions:</label>
                <select name="role" required style="margin-bottom: 0; max-width: 100%;">
                    <option value="staff" <?= (isset($editing['role']) && $editing['role'] === 'staff') ? 'selected' : '' ?>>Staff</option>
                    <option value="admin" <?= (isset($editing['role']) && $editing['role'] === 'admin') ? 'selected' : '' ?>>Administrator</option>
                </select>
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-secondary);">Password <?= $editing ? '<span style="font-weight: normal; font-size: 0.85rem; opacity: 0.8;">(leave blank to keep unchanged)</span>' : '' ?>:</label>
                <input type="password" name="password" style="margin-bottom: 0; max-width: 100%;">
            </div>
        </div>

        <div style="display: flex; gap: 15px; margin-top: 10px; align-items: center;">
            <button type="submit" class="btn" style="margin-top: 0; padding: 0.8rem 2rem;">
                <?= $editing ? 'Update Account' : 'Create Account' ?>
            </button>
            <?php if ($editing): ?>
                <a href="admin_staff.php" class="btn" style="margin-top: 0; padding: 0.8rem 2rem; background: rgba(255,255,255,0.06); border: 1px solid var(--glass-border); color: var(--text-primary);">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card" style="padding: 2.5rem; margin-bottom: 40px; border-radius: 20px;">
    <h3 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 25px; font-weight: 700;">Existing Staff & Admin Accounts</h3>
    
    <?php
    $stmt = $pdo->query("SELECT * FROM users WHERE role IN ('staff','admin') ORDER BY created_at DESC");
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php if (empty($accounts)): ?>
        <p style="color: var(--text-secondary);">No staff/admin accounts yet.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="margin-top: 0; width: 100%;">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created At</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accounts as $acc): ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($acc['name']) ?></td>
                            <td><?= htmlspecialchars($acc['email']) ?></td>
                            <td>
                                <?php if ($acc['role'] === 'admin'): ?>
                                    <span style="background: rgba(244, 63, 94, 0.1); color: #f43f5e; padding: 4px 10px; border-radius: 999px; font-size: 0.85rem; font-weight: 500; border: 1px solid rgba(244, 63, 94, 0.2);">Administrator</span>
                                <?php else: ?>
                                    <span style="background: rgba(99, 102, 241, 0.1); color: #818cf8; padding: 4px 10px; border-radius: 999px; font-size: 0.85rem; font-weight: 500; border: 1px solid rgba(99, 102, 241, 0.2);">Staff</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($acc['created_at']) ?></td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="admin_staff.php?action=edit&id=<?= $acc['user_id'] ?>" class="btn" style="margin-top: 0; padding: 6px 14px; font-size: 0.85rem; background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.3); color: #a5b4fc; border-radius: 8px;">Edit</a>
                                <?php if ($acc['user_id'] != $_SESSION['user_id']): ?>
                                    <a href="admin_staff.php?action=delete&id=<?= $acc['user_id'] ?>" class="btn" style="margin-top: 0; padding: 6px 14px; font-size: 0.85rem; background: rgba(244, 63, 94, 0.1); border: 1px solid rgba(244, 63, 94, 0.3); color: #fca5a5; border-radius: 8px; margin-left: 8px;" onclick="return confirm('Delete this account?');">Delete</a>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary); font-size: 0.85rem; font-style: italic; margin-left: 8px;">(you)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require 'footer.php'; ?>