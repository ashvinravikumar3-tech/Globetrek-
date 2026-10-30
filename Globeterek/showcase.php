<?php
require 'db.php';
require 'header.php';

$destinations = $pdo->query("SELECT * FROM destinations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$packages = $pdo->query("SELECT p.*, d.name AS destination_name
                          FROM packages p
                          JOIN destinations d ON p.destination_id = d.destination_id
                          ORDER BY p.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$accommodations = $pdo->query("SELECT * FROM accommodations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$transports = $pdo->query("SELECT * FROM transport ORDER BY type")->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="explore-banner">
    <div class="hero-content">
        <h1 class="hero-title">Explore <span class="gradient-text">GlobeTrek</span></h1>
        <p class="hero-subtitle">Discover our destinations, tour packages, accommodation options, and transportation services — all in one place.</p>
    </div>
</section>

<section class="section">
    <div class="section-header">
        <h2>Destinations</h2>
    </div>
<?php if (empty($destinations)): ?>
    <p>No destinations available yet.</p>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($destinations as $d): ?>
            <article class="card">
                <?php if(!empty($d['image'])): ?>
                    <img src="<?= htmlspecialchars($d['image']) ?>" alt="<?= htmlspecialchars($d['name']) ?>" class="card-img">
                <?php endif; ?>
                <div class="card-content">
                    <div class="card-badge">Destination</div>
                    <h3><?= htmlspecialchars($d['name']) ?></h3>
                    <p><?= htmlspecialchars($d['description']) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</section>

<section class="section">
    <div class="section-header">
        <h2>Tour Packages</h2>
    </div>
<?php if (empty($packages)): ?>
    <p>No packages available yet.</p>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($packages as $p): ?>
            <article class="card">
                <?php if(!empty($p['image'])): ?>
                    <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>" class="card-img">
                <?php endif; ?>
                <div class="card-content">
                    <div class="card-badge">Package</div>
                    <h3><?= htmlspecialchars($p['title']) ?></h3>
                    <p class="card-meta"><?= htmlspecialchars($p['destination_name']) ?> • <?= (int)$p['duration_days'] ?> Days</p>
                    <div class="card-footer">
                        <span class="price">Rs. <?= number_format($p['price'], 2) ?></span>
                        <a href="packages.php?id=<?= $p['package_id'] ?>">View Details</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</section>

<section class="section">
    <div class="section-header">
        <h2>Accommodations</h2>
    </div>
<?php if (empty($accommodations)): ?>
    <p>No accommodations available yet.</p>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($accommodations as $a): ?>
            <article class="card">
                <?php if(!empty($a['image'])): ?>
                    <img src="<?= htmlspecialchars($a['image']) ?>" alt="<?= htmlspecialchars($a['name']) ?>" class="card-img">
                <?php endif; ?>
                <div class="card-content">
                    <div class="card-badge">Stay</div>
                    <h3><?= htmlspecialchars($a['name']) ?></h3>
                    <p class="card-meta"><?= htmlspecialchars($a['type']) ?> • <?= htmlspecialchars($a['location']) ?></p>
                    <div class="card-footer">
                        <span class="price">Rs. <?= number_format($a['price_per_night'], 2) ?>/night</span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</section>

<section class="section" id="trans">
    <div class="section-header">
        <h2>Transportation</h2>
    </div>
<?php if (empty($transports)): ?>
    <p>No transport options available yet.</p>
<?php else: ?>
    <div class="card-grid" id="trans">
        <?php foreach ($transports as $t): ?>
            <?php 
                $bg_image = '';
                $type_lower = strtolower($t['type']);
                if (strpos($type_lower, 'helicopter') !== false) {
                    $bg_image = 'images/heli.jpg';
                } elseif (strpos($type_lower, 'jeep') !== false || strpos($type_lower, 'safari') !== false) {
                    $bg_image = 'images/safari.jpg';
                } elseif (strpos($type_lower, 'chauffeur') !== false || strpos($type_lower, 'private') !== false) {
                    $bg_image = 'images/chauffer.jpg';
                } elseif (strpos($type_lower, 'train') !== false) {
                    $bg_image = 'images/train2.jpg';
                }
            ?>
            <article class="card card-transport">
                <?php if ($bg_image): ?>
                    <div class="card-bg" style="background-image: url('<?= htmlspecialchars($bg_image) ?>');"></div>
                    <div class="card-overlay"></div>
                <?php endif; ?>
                <div class="card-content">
                    <div class="card-badge">Transport</div>
                    <h3><?= htmlspecialchars($t['type']) ?></h3>
                    <p class="card-meta"><?= htmlspecialchars($t['provider']) ?></p>
                    <div class="card-footer">
                        <span class="price">Rs. <?= number_format($t['price'], 2) ?></span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</section>

<section class="section" style="text-align: center;">
    <a href="packages.php" class="btn">Search Packages</a>
    <?php if(!isset($_SESSION['user_id'])): ?>
        <a href="register.php" class="btn" style="background: rgba(255,255,255,0.1); margin-left: 10px;">Register to Book</a>
    <?php endif; ?>
</section>

<?php require 'footer.php'; ?>