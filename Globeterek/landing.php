<?php
require 'db.php';
require 'header.php';

$stmt = $pdo->query("SELECT p.package_id, p.title, p.price, p.duration_days, p.image, d.name AS destination, p.description
                      FROM packages p
                      JOIN destinations d ON p.destination_id = d.destination_id
                      ORDER BY p.created_at DESC LIMIT 3");
$featuredPackages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT accommodation_id, name, location, price_per_night, type, image
                      FROM accommodations
                      ORDER BY accommodation_id DESC LIMIT 3");
$featuredAccommodations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT destination_id, name, description, image
                      FROM destinations
                      ORDER BY destination_id DESC LIMIT 3");
$featuredDestinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="landing-page">
    <section class="hero-banner">
        <div class="hero-content">
            <h1 class="hero-title">Discover <span class="gradient-text">Sri Lanka</span></h1>
            <p class="hero-subtitle">Plan your perfect vacation with premium packages, elegant stays, and unforgettable destinations curated for modern travelers.</p>
            <div style="margin-top: 20px;">
                <a href="packages.php" class="btn">Start Exploring</a>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="section-header">
            <h2>Featured Packages</h2>
            <a href="packages.php">View All</a>
        </div>
        <div class="card-grid">
            <?php if (empty($featuredPackages)): ?>
                <p>No packages available yet.</p>
            <?php else: ?>
                <?php foreach ($featuredPackages as $pkg): ?>
                    <article class="card">
                        <?php if(!empty($pkg['image'])): ?>
                            <img src="<?= htmlspecialchars($pkg['image']) ?>" alt="<?= htmlspecialchars($pkg['title']) ?>" class="card-img">
                        <?php endif; ?>
                        <div class="card-content">
                            <div class="card-badge">Package</div>
                            <h3><?= htmlspecialchars($pkg['title']) ?></h3>
                            <p class="card-meta"><?= htmlspecialchars($pkg['destination']) ?> • <?= (int)$pkg['duration_days'] ?> Days</p>
                            <p><?= htmlspecialchars($pkg['description'] ?: 'A handpicked holiday experience crafted for comfort and adventure.') ?></p>
                            <div class="card-footer">
                                <span class="price">Rs. <?= number_format($pkg['price'], 2) ?></span>
                                <a href="packages.php?id=<?= (int)$pkg['package_id'] ?>">Book Now</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="section">
        <div class="section-header">
            <h2>Comfortable Accommodations</h2>
            <a href="showcase.php">Explore More</a>
        </div>
        <div class="card-grid">
            <?php if (empty($featuredAccommodations)): ?>
                <p>No accommodations available yet.</p>
            <?php else: ?>
                <?php foreach ($featuredAccommodations as $stay): ?>
                    <article class="card">
                        <?php if(!empty($stay['image'])): ?>
                            <img src="<?= htmlspecialchars($stay['image']) ?>" alt="<?= htmlspecialchars($stay['name']) ?>" class="card-img">
                        <?php endif; ?>
                        <div class="card-content">
                            <div class="card-badge">Stay</div>
                            <h3><?= htmlspecialchars($stay['name']) ?></h3>
                            <p class="card-meta"><?= htmlspecialchars($stay['type']) ?> • <?= htmlspecialchars($stay['location']) ?></p>
                            <p>Relax in a serene and well-rated accommodation tailored for leisure and comfort.</p>
                            <div class="card-footer">
                                <span class="price">Rs. <?= number_format($stay['price_per_night'], 2) ?>/night</span>
                                <a href="staff_accommodations.php">Details</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="section">
        <div class="section-header">
            <h2>Popular Destinations</h2>
            <a href="showcase.php">See All</a>
        </div>
        <div class="card-grid">
            <?php if (empty($featuredDestinations)): ?>
                <p>No destinations available yet.</p>
            <?php else: ?>
                <?php foreach ($featuredDestinations as $destination): ?>
                    <article class="card">
                        <?php if(!empty($destination['image'])): ?>
                            <img src="<?= htmlspecialchars($destination['image']) ?>" alt="<?= htmlspecialchars($destination['name']) ?>" class="card-img">
                        <?php endif; ?>
                        <div class="card-content">
                            <div class="card-badge">Destination</div>
                            <h3><?= htmlspecialchars($destination['name']) ?></h3>
                            <p><?= htmlspecialchars($destination['description'] ?: 'A must-visit destination with amazing scenery and cultural experiences.') ?></p>
                            <div class="card-footer">
                                <span class="price">Scenic Escape</span>
                                <a href="showcase.php">Explore</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php require 'footer.php'; ?>