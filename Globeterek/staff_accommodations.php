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
        $stmt = $pdo->prepare("DELETE FROM accommodations WHERE accommodation_id = ?");
        $stmt->execute([$_GET['id']]);
        header('Location: staff_accommodations.php');
        exit;
    } catch (PDOException $e) {
        $error = "Cannot delete: this accommodation is linked to existing bookings.";
        $action = 'list';
    }
}

// ---------- ADD / EDIT FORM SUBMIT ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $price_per_night = $_POST['price_per_night'];
    $type = trim($_POST['type']);

    if (empty($name) || empty($location) || !is_numeric($price_per_night)) {
        $error = "Please fill all fields correctly.";
    } else {
        try {
            if (isset($_POST['accommodation_id']) && $_POST['accommodation_id'] !== '') {
                $stmt = $pdo->prepare("UPDATE accommodations SET name=?, location=?, price_per_night=?, type=? WHERE accommodation_id=?");
                $stmt->execute([$name, $location, $price_per_night, $type, $_POST['accommodation_id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO accommodations (name, location, price_per_night, type, created_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $location, $price_per_night, $type, $_SESSION['user_id']]);
            }
            header('Location: staff_accommodations.php');
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// ---------- EDIT: fetch existing record ----------
$editing = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM accommodations WHERE accommodation_id = ?");
    $stmt->execute([$_GET['id']]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="section-header">
    <div>
        <h2>Manage Accommodations</h2>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">Create, edit, and manage high-quality stays and lodging options.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="contact-message error" style="margin-top: 20px;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="card" style="padding: 2.5rem; margin-top: 30px; margin-bottom: 40px; border-radius: 20px;">
    <h3 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 25px; font-weight: 700;">
        <?= $editing ? 'Edit Accommodation' : 'Add New Accommodation' ?>
    </h3>
    <form method="POST" action="staff_accommodations.php" style="display: flex; flex-direction: column; gap: 20px;">
        <?php if ($editing): ?>
            <input type="hidden" name="accommodation_id" value="<?= $editing['accommodation_id'] ?>">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-secondary);">Name:</label>
                <input type="text" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required style="margin-bottom: 0; max-width: 100%;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-secondary);">Location:</label>
                <input type="text" name="location" value="<?= htmlspecialchars($editing['location'] ?? '') ?>" required style="margin-bottom: 0; max-width: 100%;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-secondary);">Price Per Night (Rs.):</label>
                <input type="number" step="0.01" name="price_per_night" value="<?= htmlspecialchars($editing['price_per_night'] ?? '') ?>" required style="margin-bottom: 0; max-width: 100%;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 8px;">
                <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-secondary);">Type (e.g. Hotel, Villa, Resort):</label>
                <input type="text" name="type" value="<?= htmlspecialchars($editing['type'] ?? '') ?>" style="margin-bottom: 0; max-width: 100%;">
            </div>
        </div>

        <div style="display: flex; gap: 15px; margin-top: 10px; align-items: center;">
            <button type="submit" class="btn" style="margin-top: 0; padding: 0.8rem 2rem;">
                <?= $editing ? 'Update Accommodation' : 'Create Accommodation' ?>
            </button>
            <?php if ($editing): ?>
                <a href="staff_accommodations.php" class="btn" style="margin-top: 0; padding: 0.8rem 2rem; background: rgba(255,255,255,0.06); border: 1px solid var(--glass-border); color: var(--text-primary);">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card" style="padding: 2.5rem; margin-bottom: 40px; border-radius: 20px;">
    <h3 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 25px; font-weight: 700;">Existing Accommodations</h3>
    
    <?php
    $stmt = $pdo->query("SELECT * FROM accommodations ORDER BY name");
    $accommodations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php if (empty($accommodations)): ?>
        <p style="color: var(--text-secondary);">No accommodations added yet.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="margin-top: 0; width: 100%;">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Price/Night</th>
                        <th>Type</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accommodations as $acc): ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($acc['name']) ?></td>
                            <td><?= htmlspecialchars($acc['location']) ?></td>
                            <td style="font-family: var(--font-heading); font-weight: 700;">Rs. <?= number_format($acc['price_per_night'], 2) ?></td>
                            <td><span style="background: rgba(236, 72, 153, 0.1); color: #f472b6; padding: 4px 10px; border-radius: 999px; font-size: 0.85rem; font-weight: 500; border: 1px solid rgba(236, 72, 153, 0.2);"><?= htmlspecialchars($acc['type']) ?: 'Standard' ?></span></td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="staff_accommodations.php?action=edit&id=<?= $acc['accommodation_id'] ?>" class="btn" style="margin-top: 0; padding: 6px 14px; font-size: 0.85rem; background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.3); color: #a5b4fc; border-radius: 8px;">Edit</a>
                                <a href="staff_accommodations.php?action=delete&id=<?= $acc['accommodation_id'] ?>" class="btn" style="margin-top: 0; padding: 6px 14px; font-size: 0.85rem; background: rgba(244, 63, 94, 0.1); border: 1px solid rgba(244, 63, 94, 0.3); color: #fca5a5; border-radius: 8px; margin-left: 8px;" onclick="return confirm('Delete this accommodation?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require 'footer.php'; ?>