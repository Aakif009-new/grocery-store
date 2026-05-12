<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Handle status update
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    $query = "UPDATE orders SET status = :status WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $order_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Order status updated successfully';
    } else {
        $_SESSION['error'] = 'Failed to update order status';
    }
    
    header('Location: orders.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Get filter values
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$query = "SELECT SQL_CALC_FOUND_ROWS o.*, u.name as customer_name, u.email 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE 1=1";

$params = [];

if (!empty($status_filter)) {
    $query .= " AND o.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($date_from)) {
    $query .= " AND DATE(o.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(o.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

$query .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count
$stmt = $conn->query("SELECT FOUND_ROWS() as total");
$totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalOrders / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - FreshMart Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <!-- Same sidebar as before -->
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1>Manage Orders</h1>
                    <p>View and manage customer orders</p>
                </div>
                <div class="header-right">
                    <a href="reports.php?type=orders" class="btn-secondary">
                        <i class="fas fa-chart-bar"></i> Sales Report
                    </a>
                </div>
            </header>

            <!-- Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="filters">
                <form method="GET" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label><i class="fas fa-filter"></i> Status</label>
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label><i class="fas fa-calendar"></i> From Date</label>
                            <input type="date" name="date_from" value="<?php echo $date_from; ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label><i class="fas fa-calendar"></i> To Date</label>
                            <input type="date" name="date_to" value="<?php echo $date_to; ?>">
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="btn-filter">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="orders.php" class="btn-reset">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Orders Table -->
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    <i class="fas fa-shopping-cart"></i>
                                    <p>No orders found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <strong>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                        <small><?php echo htmlspecialchars($order['email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($order['created_at'])); ?><br>
                                    <small><?php echo date('h:i A', strtotime($order['created_at'])); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $itemQuery = "SELECT COUNT(*) as count FROM order_items WHERE order_id = :order_id";
                                    $itemStmt = $conn->prepare($itemQuery);
                                    $itemStmt->bindParam(':order_id', $order['id']);
                                    $itemStmt->execute();
                                    $itemCount = $itemStmt->fetch(PDO::FETCH_ASSOC)['count'];
                                    echo $itemCount;
                                    ?>
                                </td>
                                <td>
                                    <strong>$<?php echo number_format($order['total'], 2); ?></strong>
                                </td>
                                <td>
                                    <?php echo ucfirst($order['payment_method']); ?><br>
                                    <small class="text-success">
                                        <i class="fas fa-check-circle"></i> Paid
                                    </small>
                                </td>
                                <td>
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" class="status-select" 
                                                data-order-id="<?php echo $order['id']; ?>">
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="order-details.php?id=<?php echo $order['id']; ?>" 
                                           class="btn-view" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="invoice.php?id=<?php echo $order['id']; ?>" 
                                           class="btn-print" target="_blank" title="Print Invoice">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <button class="btn-email" 
                                                onclick="sendEmail(<?php echo $order['id']; ?>)"
                                                title="Send Email">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Statistics -->
            <div class="stats-cards">
                <?php
                $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
                foreach ($statuses as $status):
                    $query = "SELECT COUNT(*) as count FROM orders WHERE status = :status";
                    if (!empty($date_from)) $query .= " AND DATE(created_at) >= :date_from";
                    if (!empty($date_to)) $query .= " AND DATE(created_at) <= :date_to";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':status', $status);
                    if (!empty($date_from)) $stmt->bindParam(':date_from', $date_from);
                    if (!empty($date_to)) $stmt->bindParam(':date_to', $date_to);
                    $stmt->execute();
                    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                ?>
                <div class="stat-card mini">
                    <div class="stat-icon status-<?php echo $status; ?>">
                        <i class="fas fa-<?php 
                            switch($status) {
                                case 'pending': echo 'clock';
                                case 'processing': echo 'cog';
                                case 'shipped': echo 'truck';
                                case 'delivered': echo 'check-circle';
                                case 'cancelled': echo 'times-circle';
                            }
                        ?>"></i>
                    </div>
                    <div class="stat-info">
                        <h4><?php echo ucfirst($status); ?></h4>
                        <p class="stat-value"><?php echo $count; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <!-- Same pagination as products.php -->
            </div>
        </main>
    </div>

    <script>
        // Auto-update status on change
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', function() {
                const orderId = this.dataset.orderId;
                const status = this.value;
                
                const formData = new FormData();
                formData.append('order_id', orderId);
                formData.append('status', status);
                formData.append('update_status', '1');
                
                fetch('orders.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(() => {
                    // Show success message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success';
                    alert.innerHTML = `<i class="fas fa-check-circle"></i> Order status updated to ${status}`;
                    document.querySelector('.admin-main').insertBefore(alert, document.querySelector('.filters'));
                    
                    setTimeout(() => alert.remove(), 3000);
                });
            });
        });

        // Send email
        function sendEmail(orderId) {
            const subject = prompt('Email subject:', 'Order Update from FreshMart');
            if (!subject) return;
            
            const message = prompt('Email message:', 'Your order status has been updated.');
            if (!message) return;
            
            // In real app, send AJAX request
            fetch('../api/admin/send-email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    subject: subject,
                    message: message
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Email sent successfully');
                }
            });
        }

        // Export orders
        function exportOrders(format) {
            const params = new URLSearchParams(window.location.search);
            params.append('export', format);
            window.location.href = '../api/admin/export-orders.php?' + params.toString();
        }
    </script>
</body>
</html>