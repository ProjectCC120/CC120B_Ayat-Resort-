<?php
session_start();
include 'includes/db_connect.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    // Store intended destination in session
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit();
}

// Check if room_id is provided
if(!isset($_GET['room_id']) || empty($_GET['room_id'])) {
    header('Location: rooms.php');
    exit();
}

$room_id = $_GET['room_id'];
$user_id = $_SESSION['user_id'];

// Get room details
$room_query = "SELECT * FROM rooms WHERE id = $room_id AND is_available = TRUE";
$room_result = mysqli_query($conn, $room_query);

if(mysqli_num_rows($room_result) == 0) {
    header('Location: rooms.php');
    exit();
}

$room = mysqli_fetch_assoc($room_result);

// Process booking form submission
$error = '';
$success = '';

if(isset($_POST['book'])) {
    $check_in = mysqli_real_escape_string($conn, $_POST['check_in']);
    $check_out = mysqli_real_escape_string($conn, $_POST['check_out']);
    $guests = (int)$_POST['guests'];
    $total_price = (float)$_POST['total_price'];
    
    // Validate dates
    $today = date('Y-m-d');
    $check_in_date = new DateTime($check_in);
    $check_out_date = new DateTime($check_out);
    $today_date = new DateTime($today);
    
    if($check_in_date < $today_date) {
        $error = 'Check-in date cannot be in the past';
    } elseif($check_out_date <= $check_in_date) {
        $error = 'Check-out date must be after check-in date';
    } elseif($guests < 1 || $guests > $room['capacity']) {
        $error = 'Invalid number of guests';
    } else {
        // Check if room is available for the selected dates
        $availability_query = "SELECT * FROM bookings 
                              WHERE room_id = $room_id 
                              AND status != 'cancelled' 
                              AND ((check_in_date <= '$check_in' AND check_out_date > '$check_in') 
                              OR (check_in_date < '$check_out' AND check_out_date >= '$check_out') 
                              OR (check_in_date >= '$check_in' AND check_out_date <= '$check_out'))";
        
        $availability_result = mysqli_query($conn, $availability_query);
        
        if(mysqli_num_rows($availability_result) > 0) {
            $error = 'Room is not available for the selected dates';
        } else {
            // Insert booking
            $insert_query = "INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, guests, total_price, status, payment_status) 
                            VALUES ($user_id, $room_id, '$check_in', '$check_out', $guests, $total_price, 'pending', 'pending')";
            
            if(mysqli_query($conn, $insert_query)) {
                $booking_id = mysqli_insert_id($conn);
                header("Location: payment.php?booking_id=$booking_id");
                exit();
            } else {
                $error = 'Booking failed: ' . mysqli_error($conn);
            }
        }
    }
}

// Get user details
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Get check-in and check-out dates from URL if available
$check_in = isset($_GET['check_in']) ? $_GET['check_in'] : date('Y-m-d');
$check_out = isset($_GET['check_out']) ? $_GET['check_out'] : date('Y-m-d', strtotime('+1 day'));
$guests = isset($_GET['guests']) ? $_GET['guests'] : 1;

// Calculate number of nights and total price
$check_in_date = new DateTime($check_in);
$check_out_date = new DateTime($check_out);
$interval = $check_in_date->diff($check_out_date);
$nights = $interval->days;
$subtotal = $nights * $room['price_per_night'];
$taxes = $subtotal * 0.12; // 12% tax
$total_price = $subtotal + $taxes;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Room - AYAT Resort</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .booking-container {
            padding: 80px 0;
        }
        
        .booking-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
        }
        
        .booking-form-container {
            background-color: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .booking-form-container h2 {
            font-size: 2rem;
            color: #2c7a50;
            margin-bottom: 20px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        .error-message {
            color: #e74c3c;
            margin-bottom: 20px;
        }
        
        .success-message {
            color: #2ecc71;
            margin-bottom: 20px;
        }
        
        .booking-summary {
            background-color: #f5f5f5;
            border-radius: 10px;
            padding: 30px;
            position: sticky;
            top: 100px;
        }
        
        .booking-summary h3 {
            font-size: 1.5rem;
            color: #2c7a50;
            margin-bottom: 20px;
        }
        
        .room-info {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .room-info img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .room-details h4 {
            margin-bottom: 5px;
        }
        
        .price-details {
            margin-bottom: 20px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .price-row.total {
            font-weight: bold;
            font-size: 1.2rem;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        
        @media (max-width: 992px) {
            .booking-grid {
                grid-template-columns: 1fr;
            }
            
            .booking-summary {
                position: static;
            }
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <div class="booking-container container">
            <div class="booking-grid">
                <div class="booking-form-container">
                    <h2>Book Your Stay</h2>
                    
                    <?php if($error): ?>
                        <div class="error-message"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if($success): ?>
                        <div class="success-message"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form action="booking.php?room_id=<?php echo $room_id; ?>" method="post" id="booking-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="check_in">Check In Date</label>
                                <input type="date" id="check_in" name="check_in" value="<?php echo $check_in; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="check_out">Check Out Date</label>
                                <input type="date" id="check_out" name="check_out" value="<?php echo $check_out; ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="guests">Number of Guests</label>
                                <input type="number" id="guests" name="guests" value="<?php echo $guests; ?>" min="1" max="<?php echo $room['capacity']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="room_type">Room Type</label>
                                <input type="text" id="room_type" value="<?php echo ucfirst($room['type']) . ' - ' . $room['name']; ?>" readonly>
                            </div>
                            
                            <div class="form-group full-width">
                                <h3>Guest Information</h3>
                            </div>
                            
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo $user['name']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo $user['phone']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="special_requests">Special Requests (Optional)</label>
                                <input type="text" id="special_requests" name="special_requests">
                            </div>
                        </div>
                        
                        <input type="hidden" name="total_price" id="total_price" value="<?php echo $total_price; ?>">
                        <button type="submit" name="book" class="btn" style="width: 100%; margin-top: 20px;">Proceed to Payment</button>
                    </form>
                </div>
                
                <div class="booking-summary">
                    <h3>Booking Summary</h3>
                    
                    <div class="room-info">
                        <img src="<?php echo $room['image_url']; ?>" alt="<?php echo $room['name']; ?>">
                        <div class="room-details">
                            <h4><?php echo $room['name']; ?></h4>
                            <p><?php echo ucfirst($room['type']); ?> Room</p>
                            <p><i class="fas fa-user"></i> Max <?php echo $room['capacity']; ?> Guests</p>
                        </div>
                    </div>
                    
                    <div class="price-details">
                        <div class="price-row">
                            <span>Check In:</span>
                            <span id="summary_check_in"><?php echo date('M d, Y', strtotime($check_in)); ?></span>
                        </div>
                        <div class="price-row">
                            <span>Check Out:</span>
                            <span id="summary_check_out"><?php echo date('M d, Y', strtotime($check_out)); ?></span>
                        </div>
                        <div class="price-row">
                            <span>Nights:</span>
                            <span id="summary_nights"><?php echo $nights; ?></span>
                        </div>
                        <div class="price-row">
                            <span>Guests:</span>
                            <span id="summary_guests"><?php echo $guests; ?></span>
                        </div>
                        <div class="price-row">
                            <span>Room Rate:</span>
                            <span>₱<?php echo number_format($room['price_per_night'], 2); ?> per night</span>
                        </div>
                        <div class="price-row">
                            <span>Subtotal:</span>
                            <span id="summary_subtotal">₱<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="price-row">
                            <span>Taxes & Fees (12%):</span>
                            <span id="summary_taxes">₱<?php echo number_format($taxes, 2); ?></span>
                        </div>
                        <div class="price-row total">
                            <span>Total:</span>
                            <span id="summary_total">₱<?php echo number_format($total_price, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/script.js"></script>
    <script>
        // Update booking summary when dates or guests change
        const checkInInput = document.getElementById('check_in');
        const checkOutInput = document.getElementById('check_out');
        const guestsInput = document.getElementById('guests');
        const summaryCheckIn = document.getElementById('summary_check_in');
        const summaryCheckOut = document.getElementById('summary_check_out');
        const summaryNights = document.getElementById('summary_nights');
        const summaryGuests = document.getElementById('summary_guests');
        const summarySubtotal = document.getElementById('summary_subtotal');
        const summaryTaxes = document.getElementById('summary_taxes');
        const summaryTotal = document.getElementById('summary_total');
        const totalPriceInput = document.getElementById('total_price');
        
        const pricePerNight = <?php echo $room['price_per_night']; ?>;
        
        function updateSummary() {
            const checkIn = new Date(checkInInput.value);
            const checkOut = new Date(checkOutInput.value);
            
            if(checkIn && checkOut && checkOut > checkIn) {
                const timeDiff = checkOut.getTime() - checkIn.getTime();
                const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
                
                const subtotal = nights * pricePerNight;
                const taxes = subtotal * 0.12;
                const total = subtotal + taxes;
                
                // Format dates
                const checkInFormatted = checkIn.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                const checkOutFormatted = checkOut.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                
                // Update summary
                summaryCheckIn.textContent = checkInFormatted;
                summaryCheckOut.textContent = checkOutFormatted;
                summaryNights.textContent = nights;
                summaryGuests.textContent = guestsInput.value;
                summarySubtotal.textContent = '₱' + subtotal.toFixed(2);
                summaryTaxes.textContent = '₱' + taxes.toFixed(2);
                summaryTotal.textContent = '₱' + total.toFixed(2);
                
                // Update hidden total price input
                totalPriceInput.value = total;
            }
        }
        
        checkInInput.addEventListener('change', function() {
            // Update check-out min date
            const checkInDate = new Date(this.value);
            const nextDay = new Date(checkInDate);
            nextDay.setDate(nextDay.getDate() + 1);
            
            const year = nextDay.getFullYear();
            const month = String(nextDay.getMonth() + 1).padStart(2, '0');
            const day = String(nextDay.getDate()).padStart(2, '0');
            
            checkOutInput.min = `${year}-${month}-${day}`;
            
            if(checkOutInput.value && new Date(checkOutInput.value) <= new Date(this.value)) {
                checkOutInput.value = `${year}-${month}-${day}`;
            }
            
            updateSummary();
        });
        
        checkOutInput.addEventListener('change', updateSummary);
        guestsInput.addEventListener('change', updateSummary);
        
        // Initialize summary
        updateSummary();
    </script>
</body>
</html>

