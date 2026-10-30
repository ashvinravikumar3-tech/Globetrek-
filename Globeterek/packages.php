<?php
require 'db.php';
require 'header.php';

$id = $_GET['id'] ?? null;

// ---------- DETAIL VIEW ----------
if ($id) {
    $stmt = $pdo->prepare("SELECT p.*, d.name AS destination_name
                            FROM packages p
                            JOIN destinations d ON p.destination_id = d.destination_id
                            WHERE p.package_id = ?");
    $stmt->execute([$id]);
    $package = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$package) {
        echo "<p>Package not found.</p>";
        require 'footer.php';
        exit;
    }

    $actStmt = $pdo->prepare("SELECT * FROM activities WHERE package_id = ?");
    $actStmt->execute([$id]);
    $activities = $actStmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <div class="card" style="max-width: 800px; margin: 0 auto; overflow: hidden;">
        <?php if(!empty($package['image'])): ?>
            <img src="<?= htmlspecialchars($package['image']) ?>" alt="<?= htmlspecialchars($package['title']) ?>" style="width: 100%; height: 400px; object-fit: cover;">
        <?php endif; ?>
        <div class="card-content">
            <div class="card-badge">Package Details</div>
            <h2 style="margin-bottom: 10px;"><?= htmlspecialchars($package['title']) ?></h2>
            <p class="card-meta"><strong>Destination:</strong> <?= htmlspecialchars($package['destination_name']) ?> | <strong>Duration:</strong> <?= (int)$package['duration_days'] ?> days</p>
            <p class="price" style="font-size: 1.5rem; color: var(--accent-solid); font-weight: bold; margin-bottom: 20px;">Rs. <?= number_format($package['price'], 2) ?></p>
            
            <h3 style="margin-top: 15px;">Description</h3>
            <p style="margin-bottom: 20px;"><?= nl2br(htmlspecialchars($package['description'])) ?></p>

            <h3>Activities Included</h3>
            <?php if (empty($activities)): ?>
                <p>No activities listed for this package.</p>
            <?php else: ?>
                <ul style="margin-left: 20px; margin-bottom: 20px;">
                <?php foreach ($activities as $act): ?>
                    <li style="margin-bottom: 5px;"><strong><?= htmlspecialchars($act['name']) ?></strong> - <?= htmlspecialchars($act['description']) ?></li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div class="card-footer" style="border-top: none; justify-content: flex-start; gap: 15px;">
                <?php if (($_SESSION['role'] ?? null) === 'customer'): ?>
                    <a href="cust_bookings.php?action=new&package_id=<?= $package['package_id'] ?>" class="btn" style="color: white; text-decoration: none;">Book This Package</a>
                <?php elseif (!isset($_SESSION['role'])): ?>
                    <a href="login.php" class="btn" style="color: white; text-decoration: none;">Login to book this package</a>
                <?php endif; ?>
                <a href="packages.php" style="padding: 0.75rem 1.5rem; text-decoration: underline;">Back to all packages</a>
            </div>
        </div>
    </div>

<?php
// ---------- LIST + SEARCH VIEW ----------
} else {
    $search = trim($_GET['search'] ?? '');
    $maxPrice = trim($_GET['max_price'] ?? '');

    $sql = "SELECT p.package_id, p.title, p.price, p.duration_days, p.image, d.name AS destination, p.description
            FROM packages p
            JOIN destinations d ON p.destination_id = d.destination_id
            WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (p.title LIKE ? OR d.name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($maxPrice !== '' && is_numeric($maxPrice)) {
        $sql .= " AND p.price <= ?";
        $params[] = $maxPrice;
    }

    $sql .= " ORDER BY p.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <h2>Browse Tour Packages</h2>

    <form method="GET" action="packages.php">
        <input type="text" name="search" placeholder="Search by title or destination"
               value="<?= htmlspecialchars($search) ?>">
        <input type="number" name="max_price" placeholder="Max price"
               value="<?= htmlspecialchars($maxPrice) ?>">
        <button type="submit">Search</button>
        <a href="packages.php">Reset</a>
    </form>

    <br>

    <?php if (empty($packages)): ?>
        <p>No packages match your search.</p>
    <?php else: ?>
        <div class="card-grid">
        <?php foreach ($packages as $pkg): ?>
            <article class="card">
                <?php if(!empty($pkg['image'])): ?>
                    <img src="<?= htmlspecialchars($pkg['image']) ?>" alt="<?= htmlspecialchars($pkg['title']) ?>" class="card-img">
                <?php endif; ?>
                <div class="card-content">
                    <div class="card-badge">Package</div>
                    <h3><?= htmlspecialchars($pkg['title']) ?></h3>
                    <p class="card-meta"><?= htmlspecialchars($pkg['destination']) ?> • <?= (int)$pkg['duration_days'] ?> Days</p>
                    <p><?= htmlspecialchars($pkg['description'] ?? 'A handpicked holiday experience crafted for comfort and adventure.') ?></p>
                    <div class="card-footer">
                        <span class="price">Rs. <?= number_format($pkg['price'], 2) ?></span>
                        <a href="packages.php?id=<?= $pkg['package_id'] ?>">View Details</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php
}
require 'footer.php';
?>