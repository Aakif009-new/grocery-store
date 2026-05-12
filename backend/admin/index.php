<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Include Database class if not already included
if (!class_exists('Database')) {
    class Database {
        private $host = "localhost";
        private $db_name = "grocery_store";
        private $username = "root";
        private $password = "";
        public $conn;

        public function getConnection() {
            $this->conn = null;
            try {
                $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $exception) {
                echo "Connection error: " . $exception->getMessage();
            }
            return $this->conn;
        }
    }
}

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get statistics
$db = new Database();
$conn = $db->getConnection();

// Total orders
$query = "SELECT COUNT(*) as total FROM orders";
$stmt = $conn->prepare($query);
$stmt->execute();
$totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total revenue
$query = "SELECT SUM(total) as revenue FROM orders WHERE status = 'delivered'";
$stmt = $conn->prepare($query);
$stmt->execute();
$totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['revenue'] ?? 0;

// Total products
$query = "SELECT COUNT(*) as total FROM products";
$stmt = $conn->prepare($query);
$stmt->execute();
$totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total customers
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
$stmt = $conn->prepare($query);
$stmt->execute();
$totalCustomers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent orders
$query = "SELECT o.*, u.name as customer_name 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          ORDER BY o.created_at DESC 
          LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute();
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Low stock products
$query = "SELECT * FROM products WHERE stock < 10 ORDER BY stock ASC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute();
$lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Chart data (last 7 days orders)
$query = "SELECT DATE(created_at) as date, COUNT(*) as count 
          FROM orders 
          WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
          GROUP BY DATE(created_at) 
          ORDER BY date";
$stmt = $conn->prepare($query);
$stmt->execute();
$chartData = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FreshMart</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shopping-basket"></i> FreshMart Admin</h2>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
                        <a href="index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="products.php">
                            <i class="fas fa-box"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li>
                        <a href="orders.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Orders</span>
                            <span class="badge"><?php echo $totalOrders; ?></span>
                        </a>
                    </li>
                    <li>
                        <a href="customers.php">
                            <i class="fas fa-users"></i>
                            <span>Customers</span>
                        </a>
                    </li>
                    <li>
                        <a href="categories.php">
                            <i class="fas fa-tags"></i>
                            <span>Categories</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../index.php" class="view-site">
                    <i class="fas fa-external-link-alt"></i>
                    View Site
                </a>
                <a href="../logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1>Dashboard</h1>
                    <p>Overview of your store performance</p>
                </div>
                <div class="header-right">
                    <div class="date-display">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('F d, Y'); ?></span>
                    </div>
                </div>
            </header>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon revenue">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Revenue</h3>
                        <p class="stat-value">$<?php echo number_format($totalRevenue, 2); ?></p>
                        <p class="stat-change">+12% from last month</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Orders</h3>
                        <p class="stat-value"><?php echo $totalOrders; ?></p>
                        <p class="stat-change">+8% from last month</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon products">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Products</h3>
                        <p class="stat-value"><?php echo $totalProducts; ?></p>
                        <p class="stat-change">45 in stock</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon customers">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Customers</h3>
                        <p class="stat-value"><?php echo $totalCustomers; ?></p>
                        <p class="stat-change">+15 new this week</p>
                    </div>
                </div>
            </div>

            <!-- Charts & Tables Section -->
            <div class="content-grid">
                <!-- Orders Chart -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h3>Orders (Last 7 Days)</h3>
                        <select class="chart-filter">
                            <option value="7">Last 7 Days</option>
                            <option value="30">Last 30 Days</option>
                            <option value="90">Last 90 Days</option>
                        </select>
                    </div>
                    <canvas id="ordersChart"></canvas>
                </div>

                <!-- Recent Orders -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>Recent Orders</h3>
                        <a href="orders.php" class="view-all">View All</a>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>$<?php echo number_format($order['total'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn-view">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Low Stock Products -->
                <div class="table-container">
                    <div class="table-header">
                        <h3>Low Stock Products</h3>
                        <a href="products.php" class="view-all">View All</a>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Stock</th>
                                <th>Price</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lowStockProducts as $product): ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <img src="../assets/images/products/<?php echo $product['image']; ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             width="40" height="40">
                                        <span><?php echo htmlspecialchars($product['name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td>
                                    <span class="stock-badge <?php echo $product['stock'] < 5 ? 'danger' : 'warning'; ?>">
                                        <?php echo $product['stock']; ?>
                                    </span>
                                </td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <button class="btn-restock" data-id="<?php echo $product['id']; ?>">
                                        <i class="fas fa-plus"></i> Restock
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="actions-grid">
                        <a href="add-product.php" class="action-btn">
                            <i class="fas fa-plus-circle"></i>
                            <span>Add New Product</span>
                        </a>
                        <a href="add-category.php" class="action-btn">
                            <i class="fas fa-tag"></i>
                            <span>Add Category</span>
                        </a>
                        <a href="reports.php" class="action-btn">
                            <i class="fas fa-chart-pie"></i>
                            <span>Generate Report</span>
                        </a>
                        <a href="settings.php" class="action-btn">
                            <i class="fas fa-cog"></i>
                            <span>Store Settings</span>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Orders Chart
        const ctx = document.getElementById('ordersChart').getContext('2d');
        const ordersChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($chartData, 'date')); ?>,
                datasets: [{
                    label: 'Orders',
                    data: <?php echo json_encode(array_column($chartData, 'count')); ?>,
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Restock button functionality
        document.querySelectorAll('.btn-restock').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.id;
                const quantity = prompt('Enter quantity to add:', '10');
                
                if (quantity && !isNaN(quantity) && quantity > 0) {
                    // In real app, send AJAX request to update stock
                    fetch('../api/admin/restock.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            product_id: productId,
                            quantity: parseInt(quantity)
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Stock updated successfully!');
                            location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error updating stock');
                    });
                }
            });
        });

        // Chart filter
        document.querySelector('.chart-filter').addEventListener('change', function() {
            const days = this.value;
            // In real app, fetch new data based on days
            console.log('Filter changed to:', days + ' days');
        });
    </script>
</body>
</html>