<?php
// vet_dashboard.php
session_start();
include 'db.php';
redirectIfNotLoggedIn();
redirectIfNotAuthorized(['vet']);

// Fetch pending requests
$requests = [];
$sql = "SELECT vr.id, u.name as farmer_name, u.farm_name, vr.problem_description, vr.created_at 
        FROM vet_requests vr
        JOIN users u ON vr.farmer_id = u.id
        WHERE vr.vet_id = {$_SESSION['user_id']} AND vr.status = 'pending'
        ORDER BY vr.created_at DESC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vet Dashboard - Livestock Management System</title>
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        
        /* Requests Table */
        .requests-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .requests-table th, 
        .requests-table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .requests-table th {
            background-color: #f5f5f5;
            font-weight: 500;
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
            
            .requests-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><?php echo htmlspecialchars($_SESSION['name']); ?></h2>
                <p>Veterinarian Dashboard</p>
            </div>
            
            <div class="sidebar-menu">
                <ul>
                    <li><a href="vet_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="vet_requests.php"><i class="fas fa-clipboard-list"></i> Requests</a></li>
                    <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Welcome, Dr. <?php echo htmlspecialchars($_SESSION['name']); ?></h1>
                
                <div class="profile-dropdown">
                    <div class="profile-btn">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['name']); ?>&background=4CAF50&color=fff" alt="Profile">
                        <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    </div>
                    <div class="profile-dropdown-content">
                        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-cards">
                <div class="card">
                    <h3>Pending Requests</h3>
                    <p>View and respond to farmer requests</p>
                </div>
                
                <div class="card">
                    <h3>My Schedule</h3>
                    <p>Manage your appointments</p>
                </div>
            </div>
            
            <div class="card">
                <h3>Recent Requests</h3>
                <table class="requests-table">
                    <thead>
                        <tr>
                            <th>Farmer Name</th>
                            <th>Farm</th>
                            <th>Problem Description</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($requests) > 0): ?>
                            <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['farmer_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['farm_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(substr($request['problem_description'], 0, 50)); ?>...</td>
                                <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                <td>
                                    <button class="action-btn accept-btn">Accept</button>
                                    <button class="action-btn reject-btn">Reject</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No pending requests</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    
    <script>
        // Simple script to handle request actions
        document.querySelectorAll('.accept-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const row = this.closest('tr');
                row.querySelectorAll('.action-btn').forEach(b => b.remove());
                row.insertAdjacentHTML('beforeend', '<td>Request accepted</td>');
                alert('Request accepted successfully');
            });
        });
        
        document.querySelectorAll('.reject-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const row = this.closest('tr');
                row.querySelectorAll('.action-btn').forEach(b => b.remove());
                row.insertAdjacentHTML('beforeend', '<td>Request rejected</td>');
                alert('Request rejected');
            });
        });
    </script>
</body>
</html>