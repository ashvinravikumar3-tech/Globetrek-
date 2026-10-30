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
        $stmt = $pdo->prepare("DELETE FROM destinations WHERE destination_id = ?");
        $stmt->execute([$_GET['id']]);
        header('Location: staff_destinations.php');
        exit;
    } catch (PDOException $e) {
        // likely a foreign key constraint (packages still reference this destination)
        $error = "Cannot delete: this destination is still linked to one or more packages.";
        $action = 'list';
    }
}

// ---------- ADD / EDIT FORM SUBMIT ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    if (empty($name)) {
        $error = "Destination name is required.";
    } else {
        try {
            if (isset($_POST['destination_id']) && $_POST['destination_id'] !== '') {
                // edit
                $stmt = $pdo->prepare("UPDATE destinations SET name = ?, description = ? WHERE destination_id = ?");
                $stmt->execute([$name, $description, $_POST['destination_id']]);
            } else {
                // add
                $stmt = $pdo->prepare("INSERT INTO destinations (name, description, created_by) VALUES (?, ?, ?)");
                $stmt->execute([$name, $description, $_SESSION['user_id']]);
            }
            header('Location: staff_destinations.php');
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// ---------- EDIT: fetch existing record ----------
$editing = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM destinations WHERE destination_id = ?");
    $stmt->execute([$_GET['id']]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="section-header">
    <div>
        <h2>Manage Destinations</h2>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">Create, edit, and view travel locations and regions.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="contact-message error" style="margin-top: 20px;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="card" style="padding: 2.5rem; margin-top: 30px; margin-bottom: 40px; border-radius: 20px;">
    <h3 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 25px; font-weight: 700;">
        <?= $editing ? 'Edit Destination' : 'Add New Destination' ?>
    </h3>
    <form method="POST" action="staff_destinations.php" style="display: flex; flex-direction: column; gap: 20px;">
        <?php if ($editing): ?>
            <input type="hidden" name="destination_id" value="<?= $editing['destination_id'] ?>">
        <?php endif; ?>

        <div style="display: flex; flex-direction: column; gap: 8px;">
            <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-secondary);">Name:</label>
            <input type="text" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required style="margin-bottom: 0; max-width: 100%;">
        </div>

        <div style="display: flex; flex-direction: column; gap: 8px;">
            <label style="font-weight: 600; font-size: 0.95rem; color: var(--text-secondary);">Description:</label>
            <textarea name="description" rows="4" style="margin-bottom: 0; max-width: 100%; resize: vertical;"><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
        </div>

        <div style="display: flex; gap: 15px; margin-top: 10px; align-items: center;">
            <button type="submit" class="btn" style="margin-top: 0; padding: 0.8rem 2rem;">
                <?= $editing ? 'Update Destination' : 'Create Destination' ?>
            </button>
            <?php if ($editing): ?>
                <a href="staff_destinations.php" class="btn" style="margin-top: 0; padding: 0.8rem 2rem; background: rgba(255,255,255,0.06); border: 1px solid var(--glass-border); color: var(--text-primary);">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card" style="padding: 2.5rem; margin-bottom: 40px; border-radius: 20px;">
    <h3 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 25px; font-weight: 700;">Existing Destinations</h3>
    
    <?php
    $stmt = $pdo->query("SELECT * FROM destinations ORDER BY name");
    $destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php if (empty($destinations)): ?>
        <p style="color: var(--text-secondary);">No destinations added yet.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="margin-top: 0; width: 100%;">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($destinations as $dest): ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--text-primary); white-space: nowrap;"><?= htmlspecialchars($dest['name']) ?></td>
                            <td><?= htmlspecialchars($dest['description']) ?></td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="staff_destinations.php?action=edit&id=<?= $dest['destination_id'] ?>" class="btn" style="margin-top: 0; padding: 6px 14px; font-size: 0.85rem; background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.3); color: #a5b4fc; border-radius: 8px;">Edit</a>
                                <a href="staff_destinations.php?action=delete&id=<?= $dest['destination_id'] ?>" class="btn" style="margin-top: 0; padding: 6px 14px; font-size: 0.85rem; background: rgba(244, 63, 94, 0.1); border: 1px solid rgba(244, 63, 94, 0.3); color: #fca5a5; border-radius: 8px; margin-left: 8px;" onclick="return confirm('Delete this destination?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require 'footer.php'; ?>