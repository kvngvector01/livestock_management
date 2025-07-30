<?php
// farmer_dashboard.php
session_start();
include 'db.php';

// Initialize variables with default values
$error = '';
$success = '';
$products = [];
$orders = [];
$vets = [];

// Check if user is logged in and authorized
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'farmer') {
    header("Location: login.php");
    exit();
}

try {
    // Fetch farmer's products
    $sql = "SELECT * FROM products WHERE farmer_id = ?  ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    $stmt->close();

    // Fetch recent orders
    $sql = "SELECT o.id, p.name as product_name, u.name as customer_name, o.quantity, o.status, o.created_at 
            FROM orders o
            JOIN products p ON o.product_id = p.id
            JOIN users u ON o.customer_id = u.id
            WHERE o.farmer_id = ?
            ORDER BY o.created_at DESC
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    $stmt->close();

    // Fetch nearby vets
    $sql = "SELECT id, name, address FROM users WHERE role = 'vet' LIMIT 3";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $vets[] = $row;
        }
    }

} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard - Livestock Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
    <style>
                * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #4CAF50;
            color: white;
            padding: 1.5rem 0;
            position: fixed;
            height: 100%;
        }
        
        .sidebar-header {
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-header p {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.8);
        }
        
        .sidebar-menu {
            padding: 1.5rem 0;
        }
        
        .sidebar-menu ul {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 0.8rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .sidebar-menu a:hover, 
        .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.1);
        }
        
        .sidebar-menu i {
            margin-right: 0.5rem;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            color: #333;
        }
        
        .profile-dropdown {
            position: relative;
        }
        
        .profile-btn {
            display: flex;
            align-items: center;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 0.5rem 1rem;
            cursor: pointer;
        }
        
        .profile-btn img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        
        .profile-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 200px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 4px;
            z-index: 1;
        }
        
        .profile-dropdown-content a {
            display: block;
            padding: 0.8rem 1rem;
            color: #333;
            text-decoration: none;
        }
        
        .profile-dropdown-content a:hover {
            background-color: #f5f5f5;
        }
        
        .profile-dropdown:hover .profile-dropdown-content {
            display: block;
        }
        
        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        
        .card h3 {
            margin-bottom: 1rem;
            color: #4CAF50;
        }
        
        /* Products List */
        .products-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        
        .product-card {
            height: 300px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
        }
        
        .product-image {
            height: 100px;
            background-color: #ddd;
            background-size: cover;
            background-position: center;
        }
        
        .product-info {
            padding: 1rem;
        }
        
        .product-info h3 {
            margin-bottom: 0.5rem;
            color: #4CAF50;
        }
        
        .product-info p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .product-price {
            font-weight: bold;
            color: #333;
        }
        
        /* Recent Orders */
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .orders-table th, 
        .orders-table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .orders-table th {
            background-color: #f5f5f5;
            font-weight: 500;
        }
        
        .status {
            display: inline-block;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fff3e0;
            color: #e65100;
        }
        
        .status-accepted {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-rejected {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .status-completed {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .action-btn {
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .accept-btn {
            background-color: #4CAF50;
            color: white;
        }
        
        .reject-btn {
            background-color: #f44336;
            color: white;
        }
                /* Vets List */
                .vets-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .vet-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s;
        }
        
        .vet-card:hover {
            transform: translateY(-5px);
        }
        
        .vet-image {
            height: 150px;
            background-color: #ddd;
            background-size: cover;
            background-position: center;
        }
        
        .vet-info {
            padding: 1rem;
        }
        
        .vet-info h3 {
            margin-bottom: 0.5rem;
            color: #4CAF50;
        }
        
        .vet-info p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .contact-vet-btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: #4CAF50;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                position: static;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .profile-dropdown {
                margin-top: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 1rem;
            }
            
            .orders-table {
                display: block;
                overflow-x: auto;
            }
        }

        /* Add QR code styles */
        .product-image-container {
            position: relative;
            height: 100px;
        }
        
        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-qr {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 40px;
            height: 40px;
            background: white;
            padding: 2px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .qr-tooltip {
            visibility: hidden;
            width: 120px;
            background-color: #555;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -60px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .product-qr:hover .qr-tooltip {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><?php echo htmlspecialchars($_SESSION['name'] ?? 'Farmer'); ?></h2>
                <p><?php echo htmlspecialchars($_SESSION['farm_name'] ?? ''); ?></p>
            </div>
            
            <div class="sidebar-menu">
                <ul>
                    <li><a href="farmer_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="farmer_products.php"><i class="fas fa-egg"></i> My Products</a></li>
                    <li><a href="farmer_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                    <li><a href="find_vets.php"><i class="fas fa-user-md"></i> Find Vets</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Farmer'); ?></h1>
                
                <div class="profile-dropdown">
                    <div class="profile-btn">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['name'] ?? 'F'); ?>&background=4CAF50&color=fff" alt="Profile">
                        <span><?php echo htmlspecialchars($_SESSION['name'] ?? 'Farmer'); ?></span>
                    </div>
                    <div class="profile-dropdown-content">
                        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-cards">
                <div class="card">
                    <h3>My Products</h3>
                    <p>You have <?php echo count($products); ?> products</p>
                </div>
                
                <div class="card">
                    <h3>Recent Orders</h3>
                    <p><?php echo count($orders); ?> new orders</p>
                </div>
                
                <div class="card">
                    <h3>Find Vets</h3>
                    <p><?php echo count($vets); ?> vets nearby</p>
                </div>
            </div>
            
            <div class="card">
                <h3>My Products</h3>
                <div class="products-list">
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image-container">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                <?php else: ?>
                                    <div style="height:100%; display:flex; align-items:center; justify-content:center; background:#ddd;">
                                        <span>No Image Available</span>
                                    </div>
                                <?php endif; ?>
                                <div class="product-qr" id="qr-<?php echo $product['id']; ?>" title="Scan to view product details">
                                    <div class="qr-tooltip">Scan QR Code</div>
                                </div>
                            </div>
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p><?php echo htmlspecialchars(substr($product['description'], 0, 50)); ?>...</p>
                                <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No products added yet. <a href="add_product.php">Add your first product</a></p>
                    <?php endif; ?>
                </div>
            </div>
                        <div class="card">
                <h3>Recent Orders</h3>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Product</th>
                            <th>Customer</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orders) > 0): ?>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo $order['quantity']; ?></td>
                                <td>
                                    <span class="status status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($order['status'] == 'pending'): ?>
                                        <button class="action-btn accept-btn">Accept</button>
                                        <button class="action-btn reject-btn">Reject</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No recent orders</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h3>Nearby Veterinarians</h3>
                <div class="vets-list">
                    <?php foreach ($vets as $vet): ?>
                    <div class="vet-card">
                        <div class="vet-image" style="background-image: url('https://source.unsplash.com/random/400x300/?veterinarian');"></div>
                        <div class="vet-info">
                            <h3><?php echo htmlspecialchars($vet['name']); ?></h3>
                            <p><?php echo htmlspecialchars($vet['address']); ?></p>
                            <a href="contact_vet.php?id=<?php echo $vet['id']; ?>" class="contact-vet-btn">Contact</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
            
        </div>
    </div>
    
    <script>
        // Generate QR codes for all products
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach ($products as $product): ?>
                generateProductQR(
                    <?php echo $product['id']; ?>, 
                    '<?php echo addslashes($product['name']); ?>',
                    '<?php echo addslashes($product['description']); ?>',
                    <?php echo $product['price']; ?>,
                    '<?php echo addslashes($product['category']); ?>',
                    '<?php echo addslashes($_SESSION['name'] ?? ''); ?>',
                    '<?php echo addslashes($_SESSION['farm_name'] ?? ''); ?>'
                );
            <?php endforeach; ?>
        });

        function generateProductQR(id, name, description, price, category, farmerName, farmName) {
            const productData = {
                product_id: id,
                name: name,
                description: description,
                price: price,
                category: category,
                farmer: farmerName,
                farm: farmName,
                generated_on: new Date().toLocaleString()
            };
            
            const qrContainer = document.getElementById('qr-' + id);
            if (qrContainer) {
                new QRCode(qrContainer, {
                    text: JSON.stringify(productData),
                    width: 40,
                    height: 40,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
            }
        }
                // Simple script to handle order actions
        document.querySelectorAll('.accept-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const row = this.closest('tr');
                const statusCell = row.querySelector('.status');
                statusCell.textContent = 'Accepted';
                statusCell.className = 'status status-accepted';
                row.querySelectorAll('.action-btn').forEach(b => b.remove());
                alert('Order accepted successfully');
            });
        });
        
        document.querySelectorAll('.reject-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const row = this.closest('tr');
                const statusCell = row.querySelector('.status');
                statusCell.textContent = 'Rejected';
                statusCell.className = 'status status-rejected';
                row.querySelectorAll('.action-btn').forEach(b => b.remove());
                alert('Order rejected');
            });
        });
    </script>
</body>
</html>