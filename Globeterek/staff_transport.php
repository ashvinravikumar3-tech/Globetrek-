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
        $stmt = $pdo->prepare("DELETE FROM transport WHERE transport_id = ?");
        $stmt->execute([$_GET['id']]);
        header('Location: staff_transport.php');
        exit;
    } catch (PDOException $e) {
        $error = "Cannot delete: this transport option is linked to existing bookings.";
        $action = 'list';
    }
}

// ---------- ADD / EDIT FORM SUBMIT ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = trim($_POST['type']);
    $provider = trim($_POST['provider']);
    $price = $_POST['price'];

    if (empty($type) || !is_numeric($price)) {
        $error = "Please fill all fields correctly.";
    } else {
        try {
            if (isset($_POST['transport_id']) && $_POST['transport_id'] !== '') {
                $stmt = $pdo->prepare("UPDATE transport SET type=?, provider=?, price=? WHERE transport_id=?");
                $stmt->execute([$type, $provider, $price, $_POST['transport_id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO transport (type, provider, price, created_by) VALUES (?, ?, ?, ?)");
                $stmt->execute([$type, $provider, $price, $_SESSION['user_id']]);
            }
            header('Location: staff_transport.php');
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// ---------- EDIT: fetch existing record ----------
$editing = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM transport WHERE transport_id = ?");
    $stmt->execute([$_GET['id']]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<h2>Manage Transport</h2>

<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<h3><?= $editing ? 'Edit Transport' : 'Add New Transport' ?></h3>
<form method="POST" action="staff_transport.php">
    <?php if ($editing): ?>
        <input type="hidden" name="transport_id" value="<?= $editing['transport_id'] ?>">
    <?php endif; ?>

    <label>Type (e.g. Car, Van, Bus):</label><br>
    <input type="text" name="type" value="<?= htmlspecialchars($editing['type'] ?? '') ?>" required><br><br>

    <label>Provider:</label><br>
    <input type="text" name="provider" value="<?= htmlspecialchars($editing['provider'] ?? '') ?>"><br><br>

    <label>Price (Rs.):</label><br>
    <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($editing['price'] ?? '') ?>" required><br><br>

    <button type="submit"><?= $editing ? 'Update' : 'Add' ?></button>
    <?php if ($editing): ?>
        <a href="staff_transport.php">Cancel</a>
    <?php endif; ?>
</form>

<hr>

<h3>Existing Transport Options</h3>
<?php
$stmt = $pdo->query("SELECT * FROM transport ORDER BY type");
$transports = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (empty($transports)): ?>
    <p>No transport options added yet.</p>
<?php else: ?>
    <table border="1" cellpadding="5">
        <tr><th>Type</th><th>Provider</th><th>Price</th><th>Actions</th></tr>
        <?php foreach ($transports as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['type']) ?></td>
                <td><?= htmlspecialchars($t['provider']) ?></td>
                <td>Rs. <?= number_format($t['price'], 2) ?></td>
                <td>
                    <a href="staff_transport.php?action=edit&id=<?= $t['transport_id'] ?>">Edit</a>
                    |
                    <a href="staff_transport.php?action=delete&id=<?= $t['transport_id'] ?>"
                       onclick="return confirm('Delete this transport option?');">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<?php require 'footer.php'; ?>