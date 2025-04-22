<?php
include 'includes/db_connect.php';
include 'includes/header.php';

// Initialize variables
$search = '';
$category_filter = '';
$date_filter = '';
$page = 1;
$per_page = 6; // Show more events per page on public site

// Debug: Get table structure
$table_structure_query = "DESCRIBE events";
$table_structure_result = mysqli_query($conn, $table_structure_query);
$columns = [];
while($column = mysqli_fetch_assoc($table_structure_result)) {
    $columns[] = $column['Field'];
}

// Determine field names based on table structure
$title_field = in_array('title', $columns) ? 'title' : (in_array('name', $columns) ? 'name' : 'title');
$status_field = in_array('status', $columns) ? 'status' : (in_array('active', $columns) ? 'active' : (in_array('is_active', $columns) ? 'is_active' : null));
$image_field = in_array('image', $columns) ? 'image' : (in_array('image_path', $columns) ? 'image_path' : 'image');
$date_field = in_array('event_date', $columns) ? 'event_date' : (in_array('date', $columns) ? 'date' : 'event_date');

// Handle search and filters
if(isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
}

if(isset($_GET['category']) && !empty($_GET['category'])) {
    $category_filter = mysqli_real_escape_string($conn, $_GET['category']);
}

if(isset($_GET['date_filter']) && !empty($_GET['date_filter'])) {
    $date_filter = mysqli_real_escape_string($conn, $_GET['date_filter']);
}

if(isset($_GET['page']) && is_numeric($_GET['page'])) {
    $page = (int)$_GET['page'];
}

// Build query with search and filters
$query = "SELECT * FROM events WHERE 1=1";

// Only show active events on public site
if($status_field) {
    if(is_numeric(1)) { // Check if status is stored as numeric
        $query .= " AND $status_field = 1";
    } else {
        $query .= " AND $status_field = 'active'";
    }
}

if(!empty($search)) {
    $search_fields = [$title_field, 'description'];
    $search_conditions = [];
    
    foreach($search_fields as $field) {
        if(in_array($field, $columns)) {
            $search_conditions[] = "$field LIKE '%$search%'";
        }
    }
    
    if(!empty($search_conditions)) {
        $query .= " AND (" . implode(" OR ", $search_conditions) . ")";
    }
}

if(!empty($category_filter) && in_array('category', $columns)) {
    $query .= " AND category = '$category_filter'";
}

if(!empty($date_filter) && in_array($date_field, $columns)) {
    $today = date('Y-m-d');
    
    switch($date_filter) {
        case 'upcoming':
            $query .= " AND $date_field >= '$today'";
            break;
        case 'this_week':
            $end_of_week = date('Y-m-d', strtotime('Sunday this week'));
            $query .= " AND $date_field BETWEEN '$today' AND '$end_of_week'";
            break;
        case 'this_month':
            $end_of_month = date('Y-m-t');
            $query .= " AND $date_field BETWEEN '$today' AND '$end_of_month'";
            break;
        case 'past':
            $query .= " AND $date_field < '$today'";
            break;
    }
}

// Count total events for pagination
$count_query = $query;
$count_result = mysqli_query($conn, $count_query);
$total_events = mysqli_num_rows($count_result);
$total_pages = ceil($total_events / $per_page);

// Ensure page is within valid range
if($page < 1) $page = 1;
if($page > $total_pages && $total_pages > 0) $page = $total_pages;

// Add sorting and pagination
// Sort by date - upcoming events first, then past events
if(in_array($date_field, $columns)) {
    $today = date('Y-m-d');
    $query .= " ORDER BY CASE WHEN $date_field >= '$today' THEN 0 ELSE 1 END, $date_field ASC";
} else {
    $query .= " ORDER BY id DESC";
}

$query .= " LIMIT " . (($page - 1) * $per_page) . ", $per_page";

// Execute query
$result = mysqli_query($conn, $query);

// Get categories for filter dropdown if category field exists
$categories = [];
if(in_array('category', $columns)) {
    $categories_query = "SELECT DISTINCT category FROM events WHERE category != ''";
    
    // Only show categories of active events
    if($status_field) {
        if(is_numeric(1)) {
            $categories_query .= " AND $status_field = 1";
        } else {
            $categories_query .= " AND $status_field = 'active'";
        }
    }
    
    $categories_query .= " ORDER BY category";
    $categories_result = mysqli_query($conn, $categories_query);
    
    if($categories_result) {
        while($category = mysqli_fetch_assoc($categories_result)) {
            if(!empty($category['category'])) {
                $categories[] = $category['category'];
            }
        }
    }
}

// Get upcoming featured events for the hero section
$featured_query = "SELECT * FROM events WHERE 1=1";

if($status_field) {
    if(is_numeric(1)) {
        $featured_query .= " AND $status_field = 1";
    } else {
        $featured_query .= " AND $status_field = 'active'";
    }
}

if(in_array('featured', $columns)) {
    $featured_query .= " AND featured = 1";
}

if(in_array($date_field, $columns)) {
    $today = date('Y-m-d');
    $featured_query .= " AND $date_field >= '$today'";
    $featured_query .= " ORDER BY $date_field ASC";
} else {
    $featured_query .= " ORDER BY id DESC";
}

$featured_query .= " LIMIT 3";
$featured_result = mysqli_query($conn, $featured_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - AYAT Resort</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Hero Section */
        .hero {
            position: relative;
            height: 500px;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/images/events-hero.jpg');
            background-size: cover;
            background-position: center;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        
        .hero-content {
            max-width: 800px;
            padding: 0 20px;
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }
        
        /* Featured Events Slider */
        .featured-events {
            padding: 60px 0;
            background-color: #f9f9f9;
        }
        
        .featured-events h2 {
            text-align: center;
            margin-bottom: 40px;
            color: #2c7a50;
            font-size: 2.2rem;
        }
        
        .featured-slider {
            display: flex;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            gap: 20px;
            padding: 20px 0;
            margin: 0 auto;
            max-width: 1200px;
            padding: 0 20px;
        }
        
        .featured-slider::-webkit-scrollbar {
            display: none;
        }
        
        .featured-event-card {
            scroll-snap-align: start;
            flex-shrink: 0;
            width: 350px;
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .featured-event-card:hover {
            transform: translateY(-10px);
        }
        
        .featured-event-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .featured-event-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
            padding: 20px;
            color: #fff;
        }
        
        .featured-event-date {
            display: inline-block;
            background-color: #2c7a50;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .featured-event-title {
            font-size: 1.5rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .featured-event-location {
            font-size: 0.9rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .featured-event-link {
            display: inline-block;
            background-color: #fff;
            color: #2c7a50;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .featured-event-link:hover {
            background-color: #f0f0f0;
        }
        
        /* Filter Section */
        .filter-section {
            padding: 40px 0;
            background-color: #fff;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .filter-container {
            background-color: #f5f5f5;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 20px;
            background-color: #2c7a50;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #225f3e;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid #2c7a50;
            color: #2c7a50;
        }
        
        .btn-outline:hover {
            background-color: #f5f5f5;
        }
        
        /* Events Grid */
        .events-section {
            padding: 40px 0 80px;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 40px;
            color: #2c7a50;
            font-size: 2.2rem;
        }
        
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .event-card {
            background-color: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .event-card:hover {
            transform: translateY(-10px);
        }
        
        .event-image {
            height: 220px;
            overflow: hidden;
            position: relative;
        }
        
        .event-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .event-card:hover .event-image img {
            transform: scale(1.1);
        }
        
        .category-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background-color: rgba(255, 255, 255, 0.9);
            color: #2c7a50;
        }
        
        .event-details {
            padding: 20px;
        }
        
        .event-title {
            font-size: 1.4rem;
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
        }
        
        .event-description {
            margin-bottom: 15px;
            color: #666;
            font-size: 0.95rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .event-meta {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            color: #555;
            font-size: 0.9rem;
        }
        
        .event-meta i {
            margin-right: 8px;
            color: #2c7a50;
            width: 16px;
            text-align: center;
        }
        
        .event-link {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #2c7a50;
            color: #fff;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .event-link:hover {
            background-color: #225f3e;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 50px;
        }
        
        .pagination a,
        .pagination span {
            display: inline-block;
            padding: 10px 18px;
            margin: 0 5px;
            border-radius: 5px;
            background-color: #fff;
            color: #333;
            text-decoration: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }
        
        .pagination a:hover {
            background-color: #f5f5f5;
        }
        
        .pagination .active {
            background-color: #2c7a50;
            color: #fff;
        }
        
        .pagination .disabled {
            color: #aaa;
            cursor: not-allowed;
        }
        
        /* No Events */
        .no-events {
            text-align: center;
            padding: 50px 0;
            color: #666;
        }
        
        .no-events h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #333;
        }
        
        .no-events p {
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Call to Action */
        .cta-section {
            background-color: #2c7a50;
            color: #fff;
            padding: 80px 0;
            text-align: center;
        }
        
        .cta-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .cta-section h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .cta-section p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .cta-btn {
            display: inline-block;
            padding: 15px 30px;
            background-color: #fff;
            color: #2c7a50;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        
        .cta-btn:hover {
            background-color: #f0f0f0;
            transform: translateY(-5px);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .hero {
                height: 400px;
            }
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .featured-event-card {
                width: 300px;
                height: 350px;
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .events-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .hero {
                height: 350px;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .featured-event-card {
                width: 280px;
                height: 320px;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
            
            .events-grid {
                grid-template-columns: 1fr;
            }
            
            .cta-section h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Upcoming Events & Celebrations</h1>
            <p>Discover exciting events, celebrations, and activities happening at AYAT Resort. Join us for unforgettable experiences!</p>
        </div>
    </section>
    
    <!-- Featured Events Section -->
    <?php if($featured_result && mysqli_num_rows($featured_result) > 0): ?>
    <section class="featured-events">
        <h2>Featured Events</h2>
        <div class="featured-slider">
            <?php while($featured = mysqli_fetch_assoc($featured_result)): ?>
                <?php 
                // Determine event title
                $event_title = isset($featured[$title_field]) ? $featured[$title_field] : 'Upcoming Event';
                
                // Determine event image
                $event_image = '';
                if($image_field && isset($featured[$image_field]) && !empty($featured[$image_field])) {
                    $event_image = $featured[$image_field];
                } else {
                    $event_image = 'assets/images/event-placeholder.jpg';
                }
                
                // Determine event date
                $event_date = '';
                if($date_field && isset($featured[$date_field])) {
                    $event_date = date('F d, Y', strtotime($featured[$date_field]));
                }
                ?>
                <div class="featured-event-card">
                    <img src="<?php echo $event_image; ?>" alt="<?php echo $event_title; ?>" class="featured-event-image">
                    <div class="featured-event-overlay">
                        <?php if(!empty($event_date)): ?>
                            <div class="featured-event-date">
                                <i class="far fa-calendar-alt"></i> <?php echo $event_date; ?>
                                
                                <?php if(isset($featured['time']) && !empty($featured['time'])): ?>
                                    at <?php echo $featured['time']; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <h3 class="featured-event-title"><?php echo $event_title; ?></h3>
                        
                        <?php if(isset($featured['location']) && !empty($featured['location'])): ?>
                            <div class="featured-event-location">
                                <i class="fas fa-map-marker-alt"></i> <?php echo $featured['location']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <a href="event-details.php?id=<?php echo $featured['id']; ?>" class="featured-event-link">View Details</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Filter Section -->
    <section class="filter-section">
        <div class="container">
            <div class="filter-container">
                <form action="events.php" method="get" class="filter-form">
                    <div class="filter-group">
                        <label for="search">Search Events</label>
                        <input type="text" id="search" name="search" value="<?php echo $search; ?>" placeholder="Search by title or description">
                    </div>
                    
                    <?php if(!empty($categories)): ?>
                    <div class="filter-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="">All Categories</option>
                            <?php foreach($categories as $category): ?>
                                <option value="<?php echo $category; ?>" <?php echo ($category_filter == $category) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(in_array($date_field, $columns)): ?>
                    <div class="filter-group">
                        <label for="date_filter">Date Range</label>
                        <select id="date_filter" name="date_filter">
                            <option value="">All Events</option>
                            <option value="upcoming" <?php echo ($date_filter == 'upcoming') ? 'selected' : ''; ?>>Upcoming Events</option>
                            <option value="this_week" <?php echo ($date_filter == 'this_week') ? 'selected' : ''; ?>>This Week</option>
                            <option value="this_month" <?php echo ($date_filter == 'this_month') ? 'selected' : ''; ?>>This Month</option>
                            <option value="past" <?php echo ($date_filter == 'past') ? 'selected' : ''; ?>>Past Events</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="filter-buttons">
                        <button type="submit" class="btn">Filter Events</button>
                        <a href="events.php" class="btn btn-outline">Reset</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
    
    <!-- Events Section -->
    <section class="events-section">
        <div class="container">
            <h2 class="section-title">Discover Our Events</h2>
            
            <?php if($result && mysqli_num_rows($result) > 0): ?>
                <div class="events-grid">
                    <?php while($event = mysqli_fetch_assoc($result)): ?>
                        <?php 
                        // Determine event title
                        $event_title = isset($event[$title_field]) ? $event[$title_field] : 'Event';
                        
                        // Determine event image
                        $event_image = '';
                        if($image_field && isset($event[$image_field]) && !empty($event[$image_field])) {
                            $event_image = $event[$image_field];
                        } else {
                            $event_image = 'assets/images/event-placeholder.jpg';
                        }
                        
                        // Determine event date
                        $event_date = '';
                        if($date_field && isset($event[$date_field])) {
                            $event_date = date('F d, Y', strtotime($event[$date_field]));
                        }
                        ?>
                        <div class="event-card">
                            <div class="event-image">
                                <img src="<?php echo $event_image; ?>" alt="<?php echo $event_title; ?>">
                                
                                <?php if(isset($event['category']) && !empty($event['category'])): ?>
                                    <span class="category-badge">
                                        <?php echo ucfirst($event['category']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="event-details">
                                <h3 class="event-title"><?php echo $event_title; ?></h3>
                                
                                <?php if(isset($event['description']) && !empty($event['description'])): ?>
                                    <div class="event-description">
                                        <?php echo $event['description']; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if(!empty($event_date)): ?>
                                    <div class="event-meta">
                                        <i class="far fa-calendar-alt"></i>
                                        <span><?php echo $event_date; ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if(isset($event['time']) && !empty($event['time'])): ?>
                                    <div class="event-meta">
                                        <i class="far fa-clock"></i>
                                        <span><?php echo $event['time']; ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if(isset($event['location']) && !empty($event['location'])): ?>
                                    <div class="event-meta">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo $event['location']; ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <a href="event-details.php?id=<?php echo $event['id']; ?>" class="event-link">View Details</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <?php if($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if($page > 1): ?>
                            <a href="events.php?page=1&search=<?php echo $search; ?>&category=<?php echo $category_filter; ?>&date_filter=<?php echo $date_filter; ?>"><i class="fas fa-angle-double-left"></i></a>
                            <a href="events.php?page=<?php echo $page - 1; ?>&search=<?php echo $search; ?>&category=<?php echo $category_filter; ?>&date_filter=<?php echo $date_filter; ?>"><i class="fas fa-angle-left"></i></a>
                        <?php else: ?>
                            <span class="disabled"><i class="fas fa-angle-double-left"></i></span>
                            <span class="disabled"><i class="fas fa-angle-left"></i></span>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <?php if($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="events.php?page=<?php echo $i; ?>&search=<?php echo $search; ?>&category=<?php echo $category_filter; ?>&date_filter=<?php echo $date_filter; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <a href="events.php?page=<?php echo $page + 1; ?>&search=<?php echo $search; ?>&category=<?php echo $category_filter; ?>&date_filter=<?php echo $date_filter; ?>"><i class="fas fa-angle-right"></i></a>
                            <a href="events.php?page=<?php echo $total_pages; ?>&search=<?php echo $search; ?>&category=<?php echo $category_filter; ?>&date_filter=<?php echo $date_filter; ?>"><i class="fas fa-angle-double-right"></i></a>
                        <?php else: ?>
                            <span class="disabled"><i class="fas fa-angle-right"></i></span>
                            <span class="disabled"><i class="fas fa-angle-double-right"></i></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-events">
                    <h3>No events found</h3>
                    <p>There are no events matching your search criteria. Please try different filters or check back later for upcoming events.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Call to Action Section -->
    <section class="cta-section">
        <div class="cta-content">
            <h2>Host Your Special Event at AYAT Resort</h2>
            <p>Looking for the perfect venue for your wedding, corporate event, or celebration? AYAT Resort offers stunning venues, professional planning services, and exceptional catering options.</p>
            <a href="contact.php" class="cta-btn">Contact Us for Booking</a>
        </div>
    </section>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Highlight current date in calendar if available
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const todayFormatted = today.toISOString().split('T')[0];
            const dateElements = document.querySelectorAll('.event-date');
            
            dateElements.forEach(function(element) {
                const dateText = element.textContent.trim();
                const eventDate = new Date(dateText);
                
                if(eventDate.toISOString().split('T')[0] === todayFormatted) {
                    element.classList.add('today');
                }
            });
        });
    </script>
</body>
</html>

