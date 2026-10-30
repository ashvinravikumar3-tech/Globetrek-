<?php
$allowed_roles = ['staff', 'admin'];
require 'auth_check.php';
require 'db.php';
require 'header.php';

$action = $_GET['action'] ?? 'list';
$error = '';

// ---------- DELETE ----------
if ($action === 'delete' && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM packages WHERE package_id = ?");
        $stmt->execute([$_GET['id']]);
        header('Location: staff_packages.php');
        exit;
    } catch (PDOException $e) {
        $error = "Cannot delete: this package is linked to existing bookings.";
        $action = 'list';
    }
}

// ---------- ADD / EDIT FORM SUBMIT ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $destination_id = $_POST['destination_id'];
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $duration_days = $_POST['duration_days'];

    if (empty($title) || empty($destination_id) || !is_numeric($price) || !is_numeric($duration_days)) {
        $error = "Please fill all fields correctly.";
    } else {
        try {
            if (isset($_POST['package_id']) && $_POST['package_id'] !== '') {
                $stmt = $pdo->prepare("UPDATE packages SET title=?, destination_id=?, description=?, price=?, duration_days=? WHERE package_id=?");
                $stmt->execute([$title, $destination_id, $description, $price, $duration_days, $_POST['package_id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO packages (title, destination_id, description, price, duration_days, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $destination_id, $description, $price, $duration_days, $_SESSION['user_id']]);
            }
            header('Location: staff_packages.php');
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// ---------- EDIT: fetch existing record ----------
$editing = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE package_id = ?");
    $stmt->execute([$_GET['id']]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC);
}

// destinations for dropdown
$destStmt = $pdo->query("SELECT * FROM destinations ORDER BY name");
$destinations = $destStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="section-header">
    <div>
        <h2>Manage Packages</h2>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">Create, edit, and curate travel packages for modern travelers.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="contact-message error" style="margin-top: 20px;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if (empty($destinations)): ?>
    <div class="card" style="padding: 2rem; margin-top: 30px; text-align: center;">
        <p>You must <a href="staff_destinations.php" style="color: var(--accent-solid); text-decoration: underline;">add a destination</a> before creating a package.</p>
    </div>
<?php else: ?>

<div class="card" style="padding: 2.5rem; margin-top: 30px; margin-bottom: 40px; border-radius: 20px;">
    <h3 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 25px; font-weight: 700;">
        <?= $editing ? 'Edit Package' : 'Add New Package' ?>
    </h3>
    <form method="POST" action="staff_packages.php" style="display: flex; flex-direction: column; gap: 20px;">
        <?php if ($editing): ?>
            <input type="hidden" name="package_id" value="<?= $editing['package_id'] ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-secondary);">Title:</label>
                <input type="text" name="title" value="<?= htmlspecialchars($editing['title'] ?? '') ?>" required style="margin-bottom: 0; max-width: 100%;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-secondary);">Destination:</label>
                <select name="destination_id" required style="margin-bottom: 0; max-width: 100%;">
                    <option value="">-- Select Destination --</option>
                    <?php foreach ($destinations as $dest): ?>
                        <option value="<?= $dest['destination_id'] ?>"
                            <?= (isset($editing['destination_id']) && $editing['destination_id'] == $dest['destination_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dest['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 8px;">
            <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-secondary);">Description:</label>
            <textarea name="description" rows="4" style="margin-bottom: 0; max-width: 100%; resize: vertical;"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-secondary);">Price (Rs.):</label>
                <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($editing['price'] ?? '') ?>" required style="margin-bottom: 0; max-width: 100%;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-secondary);">Duration (days):</label>
                <input type="number" name="duration_days" value="<?= htmlspecialchars($editing['duration_days'] ?? '') ?>" required style="margin-bottom: 0; max-width: 100%;">
            </div>
        </div>

        <div style="display: flex; gap: 15px; margin-top: 10px; align-items: center;">
            <button type="submit" class="btn" style="margin-top: 0; padding: 0.8rem 2rem;">
                <?= $editing ? 'Update Package' : 'Create Package' ?>
            </button>
            <?php if ($editing): ?>
                <a href="staff_packages.php" class="btn" style="margin-top: 0; padding: 0.8rem 2rem; background: rgba(255,255,255,0.06); border: 1px solid var(--glass-border); color: var(--text-primary);">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php endif; ?>

<div class="card" style="padding: 2.5rem; margin-bottom: 40px; border-radius: 20px;">
    <h3 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 25px; font-weight: 700;">Existing Packages</h3>
    
    <?php
    $stmt = $pdo->query("SELECT p.*, d.name AS destination_name
                          FROM packages p
                          JOIN destinations d ON p.destination_id = d.destination_id
                          ORDER BY p.created_at DESC");
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php if (empty($packages)): ?>
        <p style="color: var(--text-secondary);">No packages added yet.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="margin-top: 0; width: 100%;">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Destination</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($packages as $pkg): ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($pkg['title']) ?></td>
                            <td><span style="background: rgba(99, 102, 241, 0.1); color: #818cf8; padding: 4px 10px; border-radius: 999px; font-size: 0.85rem; font-weight: 500; border: 1px solid rgba(99, 102, 241, 0.2);"><?= htmlspecialchars($pkg['destination_name']) ?></span></td>
                            <td style="font-family: var(--font-heading); font-weight: 700;">Rs. <?= number_format($pkg['price'], 2) ?></td>
                            <td><?= (int)$pkg['duration_days'] ?> Days</td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="staff_packages.php?action=edit&id=<?= $pkg['package_id'] ?>" class="btn" style="margin-top: 0; padding: 6px 14px; font-size: 0.85rem; background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.3); color: #a5b4fc; border-radius: 8px;">Edit</a>
                                <a href="staff_packages.php?action=delete&id=<?= $pkg['package_id'] ?>" class="btn" style="margin-top: 0; padding: 6px 14px; font-size: 0.85rem; background: rgba(244, 63, 94, 0.1); border: 1px solid rgba(244, 63, 94, 0.3); color: #fca5a5; border-radius: 8px; margin-left: 8px;" onclick="return confirm('Delete this package?');">Delete</a>
                                <a href="packages.php?id=<?= $pkg['package_id'] ?>" class="btn" style="margin-top: 0; padding: 6px 14px; font-size: 0.85rem; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--glass-border); color: var(--text-primary); border-radius: 8px; margin-left: 8px;" target="_blank">View Site &rarr;</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require 'footer.php'; ?>