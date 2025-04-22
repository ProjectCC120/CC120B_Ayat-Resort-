<?php
    session_start();
    require_once 'includes/db_connect.php';

    // Get all dining options
    $query = "SELECT * FROM dining_options ORDER BY name";
    $result = mysqli_query($conn, $query);
    $dining_options = [];

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $dining_options[] = $row;
        }
    }

    // Get cuisine types for filter
    $query = "SELECT DISTINCT cuisine_type FROM dining_options ORDER BY cuisine_type";
    $result = mysqli_query($conn, $query);
    $cuisine_types = [];

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $cuisine_types[] = $row['cuisine_type'];
        }
    }

    // Filter by cuisine type if set
    $filtered_cuisine = isset($_GET['cuisine']) ? $_GET['cuisine'] : '';
    if (!empty($filtered_cuisine)) {
        $filtered_cuisine = mysqli_real_escape_string($conn, $filtered_cuisine);
        $query = "SELECT * FROM dining_options WHERE cuisine_type = '$filtered_cuisine' ORDER BY name";
        $result = mysqli_query($conn, $query);
        $dining_options = [];
        
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $dining_options[] = $row;
            }
        }
    }

    // Filter by meal time if set
    $filtered_meal = isset($_GET['meal']) ? $_GET['meal'] : '';
    if (!empty($filtered_meal)) {
        $filtered_meal = mysqli_real_escape_string($conn, $filtered_meal);
        $query = "SELECT * FROM dining_options WHERE meal_times LIKE '%$filtered_meal%' ORDER BY name";
        $result = mysqli_query($conn, $query);
        $dining_options = [];
        
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $dining_options[] = $row;
            }
        }
    }

    $page_title = "Dining Options";
    include 'includes/header.php';
    ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dining Options - AYAT Resort</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* Dining Page Styles */
        .page-banner {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url("assets/images/dining/dining-banner.jpg");
            background-size: cover;
            background-position: center;
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #fff;
            margin-bottom: 50px;
        }

        .page-banner h1 {
            font-size: 3rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .page-banner p {
            font-size: 1.2rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .section-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-header h2 {
            font-size: 2.2rem;
            color: #2c7a50;
            margin-bottom: 10px;
        }

        .section-header p {
            font-size: 1.1rem;
            color: #666;
        }

        .dining-intro {
            padding: 50px 0;
            background-color: #fff;
        }

        .intro-content {
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .intro-text {
            flex: 1;
            line-height: 1.8;
        }

        .intro-text p {
            margin-bottom: 20px;
        }

        .intro-image {
            flex: 1;
        }

        .intro-image img {
            width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .dining-filters {
            padding: 20px 0 40px;
            background-color: #f9f9f9;
        }

        .filter-options {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-group label {
            font-weight: 600;
            color: #333;
        }

        .filter-group select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #fff;
            cursor: pointer;
        }

        .filter-reset {
            margin-left: 10px;
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 0.9rem;
        }

        .dining-options {
            padding: 50px 0;
            background-color: #fff;
        }

        .dining-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }

        .dining-card {
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .dining-card:hover {
            transform: translateY(-10px);
        }

        .dining-image {
            height: 250px;
            overflow: hidden;
        }

        .dining-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .dining-card:hover .dining-image img {
            transform: scale(1.1);
        }

        .dining-details {
            padding: 20px;
        }

        .dining-details h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #2c7a50;
        }

        .dining-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .cuisine, .price-range {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .cuisine {
            background-color: #e8f5e9;
            color: #2c7a50;
        }

        .price-range {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .dining-description {
            margin-bottom: 15px;
            line-height: 1.6;
            color: #555;
        }

        .dining-hours {
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #666;
        }

        .dining-hours p {
            margin-bottom: 5px;
        }

        .dining-actions {
            display: flex;
            gap: 10px;
        }

        .no-results {
            text-align: center;
            padding: 50px 20px;
            background-color: #f9f9f9;
            border-radius: 10px;
        }

        .special-offers {
            padding: 50px 0;
            background-color: #f5f5f5;
        }

        .offers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        .offer-card {
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .offer-image {
            height: 200px;
            overflow: hidden;
        }

        .offer-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .offer-card:hover .offer-image img {
            transform: scale(1.1);
        }

        .offer-content {
            padding: 20px;
            text-align: center;
        }

        .offer-content h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: #2c7a50;
        }

        .offer-content p {
            margin-bottom: 15px;
            color: #555;
        }

        @media (max-width: 992px) {
            .intro-content {
                flex-direction: column;
            }
            
            .intro-image {
                order: -1;
                margin-bottom: 30px;
            }
            
            .dining-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .page-banner {
                height: 200px;
            }
            
            .page-banner h1 {
                font-size: 2.2rem;
            }
            
            .dining-grid {
                grid-template-columns: 1fr;
            }
            
            .offers-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main>
        <section class="page-banner">
            <div class="container">
                <h1>Dining Experiences</h1>
                <p>Discover culinary excellence at AYAT Resort</p>
            </div>
        </section>

        <section class="dining-intro">
            <div class="container">
                <div class="section-header">
                    <h2>Culinary Delights</h2>
                    <p>Experience exceptional dining with our world-class restaurants and cafes</p>
                </div>
                <div class="intro-content">
                    <div class="intro-text">
                        <p>At AYAT Resort, we believe that exceptional dining is an essential part of your stay. Our restaurants offer a diverse range of cuisines prepared by award-winning chefs using the freshest local ingredients.</p>
                        <p>From casual poolside snacks to elegant fine dining, we have options to satisfy every palate and occasion.</p>
                    </div>
                    <div class="intro-image">
                        <img src="assets/images/dining/dining-main.jpg" alt="AYAT Resort Dining Experience">
                    </div>
                </div>
            </div>
        </section>

        <section class="dining-filters">
            <div class="container">
                <div class="filter-options">
                    <div class="filter-group">
                        <label for="cuisine-filter">Filter by Cuisine:</label>
                        <select id="cuisine-filter" onchange="window.location.href='dining.php?cuisine='+this.value">
                            <option value="">All Cuisines</option>
                            <?php foreach ($cuisine_types as $cuisine): ?>
                                <option value="<?php echo htmlspecialchars($cuisine); ?>" <?php echo ($filtered_cuisine == $cuisine) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cuisine); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="meal-filter">Filter by Meal:</label>
                        <select id="meal-filter" onchange="window.location.href='dining.php?meal='+this.value">
                            <option value="">All Meals</option>
                            <option value="breakfast" <?php echo ($filtered_meal == 'breakfast') ? 'selected' : ''; ?>>Breakfast</option>
                            <option value="lunch" <?php echo ($filtered_meal == 'lunch') ? 'selected' : ''; ?>>Lunch</option>
                            <option value="dinner" <?php echo ($filtered_meal == 'dinner') ? 'selected' : ''; ?>>Dinner</option>
                        </select>
                    </div>
                    <?php if (!empty($filtered_cuisine) || !empty($filtered_meal)): ?>
                        <div class="filter-reset">
                            <a href="dining.php" class="btn btn-sm">Reset Filters</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="dining-options">
            <div class="container">
                <?php if (empty($dining_options)): ?>
                    <div class="no-results">
                        <p>No dining options found with the selected filters. Please try different criteria.</p>
                        <a href="dining.php" class="btn">View All Dining Options</a>
                    </div>
                <?php else: ?>
                    <div class="dining-grid">
                        <?php foreach ($dining_options as $dining): ?>
                            <div class="dining-card">
                                <div class="dining-image">
                                    <img src="<?php echo htmlspecialchars($dining['image_url']); ?>" alt="<?php echo htmlspecialchars($dining['name']); ?>">
                                </div>
                                <div class="dining-details">
                                    <h3><?php echo htmlspecialchars($dining['name']); ?></h3>
                                    <div class="dining-meta">
                                        <span class="cuisine"><?php echo htmlspecialchars($dining['cuisine_type']); ?></span>
                                        <span class="price-range"><?php echo htmlspecialchars($dining['price_range']); ?></span>
                                    </div>
                                    <p class="dining-description"><?php echo htmlspecialchars($dining['description']); ?></p>
                                    <div class="dining-hours">
                                        <p><strong>Hours:</strong> <?php echo htmlspecialchars($dining['hours']); ?></p>
                                        <p><strong>Meals:</strong> <?php echo htmlspecialchars($dining['meal_times']); ?></p>
                                    </div>
                                    <div class="dining-actions">
                                        <a href="dining-details.php?id=<?php echo $dining['id']; ?>" class="btn">View Menu</a>
                                        <?php if (isset($dining['reservation_link']) && !empty($dining['reservation_link'])): ?>
                                            <a href="<?php echo htmlspecialchars($dining['reservation_link']); ?>" class="btn btn-outline">Reserve a Table</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="special-offers">
            <div class="container">
                <div class="section-header">
                    <h2>Special Dining Experiences</h2>
                    <p>Create unforgettable memories with our unique dining options</p>
                </div>
                <div class="offers-grid">
                    <div class="offer-card">
                        <div class="offer-image">
                            <img src="assets/images/dining/private-dining.jpg" alt="Private Beach Dining">
                        </div>
                        <div class="offer-content">
                            <h3>Private Beach Dining</h3>
                            <p>Enjoy a romantic dinner under the stars with the sound of waves as your backdrop.</p>
                            <a href="contact.php?inquiry=private-dining" class="btn btn-sm">Inquire Now</a>
                        </div>
                    </div>
                    <div class="offer-card">
                        <div class="offer-image">
                            <img src="assets/images/dining/cooking-class.jpg" alt="Cooking Classes">
                        </div>
                        <div class="offer-content">
                            <h3>Cooking Classes</h3>
                            <p>Learn to prepare local delicacies with our expert chefs in an interactive session.</p>
                            <a href="activities.php?category=cooking" class="btn btn-sm">Learn More</a>
                        </div>
                    </div>
                    <div class="offer-card">
                        <div class="offer-image">
                            <img src="assets/images/dining/wine-tasting.jpg" alt="Wine Tasting">
                        </div>
                        <div class="offer-content">
                            <h3>Wine Tasting</h3>
                            <p>Sample premium wines from around the world guided by our sommelier.</p>
                            <a href="activities.php?category=wine-tasting" class="btn btn-sm">View Schedule</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
