<?php
session_start();
require_once 'includes/db_connect.php';

// Get dining option ID from URL
$dining_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch dining option details
$query = "SELECT * FROM dining_options WHERE id = $dining_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    // Dining option not found, redirect to dining page
    header('Location: dining.php');
    exit;
}

$dining = mysqli_fetch_assoc($result);

// Fetch menu items for this dining option
$query = "SELECT * FROM menu_items WHERE dining_id = $dining_id ORDER BY category, name";
$result = mysqli_query($conn, $query);
$menu_items = [];

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $menu_items[] = $row;
    }
}

// Group menu items by category
$menu_by_category = [];
foreach ($menu_items as $item) {
    $category = $item['category'];
    if (!isset($menu_by_category[$category])) {
        $menu_by_category[$category] = [];
    }
    $menu_by_category[$category][] = $item;
}

$page_title = $dining['name'] . " - Menu";
include 'includes/header.php';
?>

<main>
    <section class="page-banner" style="background-image: url('<?php echo htmlspecialchars($dining['banner_image'] ?? $dining['image_url']); ?>');">
        <div class="container">
            <h1><?php echo htmlspecialchars($dining['name']); ?></h1>
            <p><?php echo htmlspecialchars($dining['tagline'] ?? 'Culinary Excellence at AYAT Resort'); ?></p>
        </div>
    </section>

    <section class="dining-details">
        <div class="container">
            <div class="dining-overview">
                <div class="dining-image">
                    <img src="<?php echo htmlspecialchars($dining['image_url']); ?>" alt="<?php echo htmlspecialchars($dining['name']); ?>">
                </div>
                <div class="dining-info">
                    <div class="dining-meta">
                        <span class="cuisine"><?php echo htmlspecialchars($dining['cuisine_type']); ?></span>
                        <span class="price-range"><?php echo htmlspecialchars($dining['price_range']); ?></span>
                    </div>
                    <p class="dining-description"><?php echo nl2br(htmlspecialchars($dining['description'])); ?></p>
                    <div class="dining-details-list">
                        <div class="detail-item">
                            <span class="detail-label">Location:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($dining['location']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Hours:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($dining['hours']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Dress Code:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($dining['dress_code'] ?? 'Smart Casual'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Reservations:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($dining['reservations_required'] == 1 ? 'Required' : 'Recommended'); ?></span>
                        </div>
                    </div>
                    <?php if (isset($dining['reservation_link']) && !empty($dining['reservation_link'])): ?>
                        <div class="dining-actions">
                            <a href="<?php echo htmlspecialchars($dining['reservation_link']); ?>" class="btn">Reserve a Table</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="menu-section">
        <div class="container">
            <div class="section-header">
                <h2>Our Menu</h2>
                <p>Discover our culinary creations</p>
            </div>
            
            <?php if (empty($menu_items)): ?>
                <div class="no-menu-items">
                    <p>Menu information is currently being updated. Please check back soon or contact us for the latest menu options.</p>
                </div>
            <?php else: ?>
                <div class="menu-categories">
                    <ul class="category-tabs">
                        <?php foreach (array_keys($menu_by_category) as $index => $category): ?>
                            <li class="category-tab <?php echo $index === 0 ? 'active' : ''; ?>" data-category="<?php echo htmlspecialchars($category); ?>">
                                <?php echo htmlspecialchars($category); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="menu-items-container">
                    <?php foreach ($menu_by_category as $category => $items): ?>
                        <div class="menu-category-items" id="category-<?php echo htmlspecialchars($category); ?>" style="display: <?php echo array_keys($menu_by_category)[0] === $category ? 'block' : 'none'; ?>;">
                            <div class="menu-items-grid">
                                <?php foreach ($items as $item): ?>
                                    <div class="menu-item">
                                        <?php if (!empty($item['image_url'])): ?>
                                            <div class="menu-item-image">
                                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            </div>
                                        <?php endif; ?>
                                        <div class="menu-item-details">
                                            <div class="menu-item-header">
                                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                                <span class="menu-item-price"><?php echo htmlspecialchars($item['price']); ?></span>
                                            </div>
                                            <p class="menu-item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                                            <?php if (!empty($item['dietary_info'])): ?>
                                                <div class="dietary-info">
                                                    <?php 
                                                    $dietary_labels = explode(',', $item['dietary_info']);
                                                    foreach ($dietary_labels as $label): 
                                                        $label = trim($label);
                                                        $icon_class = '';
                                                        switch (strtolower($label)) {
                                                            case 'vegetarian': $icon_class = 'fa-leaf'; break;
                                                            case 'vegan': $icon_class = 'fa-seedling'; break;
                                                            case 'gluten-free': $icon_class = 'fa-wheat-awn-circle-exclamation'; break;
                                                            case 'spicy': $icon_class = 'fa-pepper-hot'; break;
                                                            default: $icon_class = 'fa-info-circle';
                                                        }
                                                    ?>
                                                        <span class="dietary-label" title="<?php echo htmlspecialchars($label); ?>">
                                                            <i class="fas <?php echo $icon_class; ?>"></i> <?php echo htmlspecialchars($label); ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="chef-spotlight">
        <div class="container">
            <div class="section-header">
                <h2>Meet Our Chef</h2>
                <p>The culinary genius behind our exceptional dining experience</p>
            </div>
            <div class="chef-profile">
                <div class="chef-image">
                    <img src="assets/images/dining/chef.jpg" alt="Head Chef">
                </div>
                <div class="chef-bio">
                    <h3>Chef Michael Rodriguez</h3>
                    <p class="chef-title">Executive Chef</p>
                    <div class="chef-description">
                        <p>With over 20 years of experience in fine dining, Chef Michael brings his passion for culinary excellence to AYAT Resort. Trained in Paris and having worked in Michelin-starred restaurants across Europe, he specializes in creating innovative dishes that blend international techniques with local flavors.</p>
                        <p>Chef Michael's philosophy centers around using fresh, seasonal ingredients sourced from local farmers and fishermen to create memorable dining experiences for our guests.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="dining-gallery">
        <div class="container">
            <div class="section-header">
                <h2>Gallery</h2>
                <p>A visual taste of our dining experience</p>
            </div>
            <div class="gallery-grid">
                <div class="gallery-item">
                    <img src="assets/images/dining/gallery-1.jpg" alt="Restaurant Interior">
                </div>
                <div class="gallery-item">
                    <img src="assets/images/dining/gallery-2.jpg" alt="Signature Dish">
                </div>
                <div class="gallery-item">
                    <img src="assets/images/dining/gallery-3.jpg" alt="Outdoor Seating">
                </div>
                <div class="gallery-item">
                    <img src="assets/images/dining/gallery-4.jpg" alt="Dessert Selection">
                </div>
                <div class="gallery-item">
                    <img src="assets/images/dining/gallery-5.jpg" alt="Wine Selection">
                </div>
                <div class="gallery-item">
                    <img src="assets/images/dining/gallery-6.jpg" alt="Private Dining Room">
                </div>
            </div>
        </div>
    </section>

    <section class="reviews-section">
        <div class="container">
            <div class="section-header">
                <h2>Guest Reviews</h2>
                <p>What our diners are saying</p>
            </div>
            <div class="reviews-slider">
                <div class="review-item">
                    <div class="review-content">
                        <div class="review-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="review-text">"An unforgettable dining experience! The seafood platter was the freshest I've ever tasted, and the service was impeccable. The ocean view from our table made the evening perfect."</p>
                        <div class="reviewer">
                            <p class="reviewer-name">Sarah J.</p>
                            <p class="review-date">June 2023</p>
                        </div>
                    </div>
                </div>
                <div class="review-item">
                    <div class="review-content">
                        <div class="review-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="review-text">"Chef Michael's tasting menu was a culinary journey I won't forget. Each course was beautifully presented and the flavors were extraordinary. The wine pairings were perfect complements to each dish."</p>
                        <div class="reviewer">
                            <p class="reviewer-name">David M.</p>
                            <p class="review-date">May 2023</p>
                        </div>
                    </div>
                </div>
                <div class="review-item">
                    <div class="review-content">
                        <div class="review-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                        <p class="review-text">"As someone with dietary restrictions, I was impressed by how accommodating the staff was. They created a special menu for me that was just as delicious and creative as the regular offerings."</p>
                        <div class="reviewer">
                            <p class="reviewer-name">Emily R.</p>
                            <p class="review-date">April 2023</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
    // Menu category tabs functionality
    document.addEventListener('DOMContentLoaded', function() {
        const categoryTabs = document.querySelectorAll('.category-tab');
        
        categoryTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                categoryTabs.forEach(t => t.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Hide all category items
                const allCategoryItems = document.querySelectorAll('.menu-category-items');
                allCategoryItems.forEach(item => {
                    item.style.display = 'none';
                });
                
                // Show selected category items
                const categoryToShow = this.getAttribute('data-category');
                document.getElementById('category-' + categoryToShow).style.display = 'block';
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>

