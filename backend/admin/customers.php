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

// Handle delete
if (isset($_GET['delete'])) {
    $customer_id = $_GET['delete'];
    
    // Check if customer has orders
    $query = "SELECT COUNT(*) as order_count FROM orders WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $customer_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['order_count'] > 0) {
        $_SESSION['error'] = 'Cannot delete customer with existing orders';
    } else {
        $query = "DELETE FROM users WHERE id = :id AND role = 'customer'";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $customer_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Customer deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete customer';
        }
    }
    
    header('Location: customers.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Search and filter
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

// Build query
$query = "SELECT SQL_CALC_FOUND_ROWS u.*, 
          (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
          (SELECT SUM(total) FROM orders WHERE user_id = u.id AND status = 'delivered') as total_spent
          FROM users u 
          WHERE u.role = 'customer'";

$params = [];

if (!empty($search)) {
    $query .= " AND (u.name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($status === 'active') {
    $query .= " AND u.status = 'active'";
} elseif ($status === 'inactive') {
    $query .= " AND u.status = 'inactive'";
}

$query .= " ORDER BY u.created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count
$stmt = $conn->query("SELECT FOUND_ROWS() as total");
$totalCustomers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalCustomers / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers - FreshMart Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <!-- Same sidebar -->
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1>Manage Customers</h1>
                    <p>View and manage customer accounts</p>
                </div>
                <div class="header-right">
                    <button class="btn-secondary" onclick="exportCustomers()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </header>

            <!-- Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="filters">
                <form method="GET" class="filter-form">
                    <div class="filter-row">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Search customers..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <select name="status">
                                <option value="">All Customers</option>
                                <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        
                        <a href="customers.php" class="btn-reset">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Customers Grid -->
            <div class="customers-grid">
                <?php if (empty($customers)): ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>No customers found</h3>
                        <p>Try adjusting your search or filter</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($customers as $customer): ?>
                    <div class="customer-card">
                        <div class="customer-header">
                            <div class="customer-avatar">
                                <?php 
                                $initial = strtoupper(substr($customer['name'], 0, 1));
                                $colors = ['#4CAF50', '#2196F3', '#FF9800', '#9C27B0'];
                                $color = $colors[rand(0, 3)];
                                ?>
                                <div class="avatar" style="background-color: <?php echo $color; ?>">
                                    <?php echo $initial; ?>
                                </div>
                            </div>
                            <div class="customer-actions">
                                <div class="dropdown">
                                    <button class="dropdown-toggle">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a href="customer-details.php?id=<?php echo $customer['id']; ?>">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                        <a href="edit-customer.php?id=<?php echo $customer['id']; ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="#" onclick="sendMessage(<?php echo $customer['id']; ?>)">
                                            <i class="fas fa-envelope"></i> Send Message
                                        </a>
                                        <a href="?delete=<?php echo $customer['id']; ?>" 
                                           onclick="return confirm('Are you sure?')"
                                           class="text-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="customer-info">
                            <h3><?php echo htmlspecialchars($customer['name']); ?></h3>
                            <p class="customer-email">
                                <i class="fas fa-envelope"></i>
                                <?php echo htmlspecialchars($customer['email']); ?>
                            </p>
                            <?php if (!empty($customer['phone'])): ?>
                            <p class="customer-phone">
                                <i class="fas fa-phone"></i>
                                <?php echo htmlspecialchars($customer['phone']); ?>
                            </p>
                            <?php endif; ?>
                            
                            <div class="customer-stats">
                                <div class="stat">
                                    <strong><?php echo $customer['order_count']; ?></strong>
                                    <span>Orders</span>
                                </div>
                                <div class="stat">
                                    <strong>$<?php echo number_format($customer['total_spent'] ?? 0, 2); ?></strong>
                                    <span>Total Spent</span>
                                </div>
                            </div>
                            
                            <div class="customer-meta">
                                <span class="status-badge <?php echo $customer['status'] == 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($customer['status']); ?>
                                </span>
                                <span class="join-date">
                                    Joined <?php echo date('M Y', strtotime($customer['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Customer Statistics -->
            <div class="stats-cards">
                <?php
                // Total customers
                $query = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                ?>
                <div class="stat-card">
                    <div class="stat-icon customers">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Customers</h3>
                        <p class="stat-value"><?php echo $total; ?></p>
                    </div>
                </div>
                
                <?php
                // New customers this month
                $query = "SELECT COUNT(*) as total FROM users 
                          WHERE role = 'customer' 
                          AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                          AND YEAR(created_at) = YEAR(CURRENT_DATE())";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $newThisMonth = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                ?>
                <div class="stat-card">
                    <div class="stat-icon new">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stat-info">
                        <h3>New This Month</h3>
                        <p class="stat-value"><?php echo $newThisMonth; ?></p>
                    </div>
                </div>
                
                <?php
                // Active customers (ordered in last 30 days)
                $query = "SELECT COUNT(DISTINCT user_id) as total FROM orders 
                          WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $active = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                ?>
                <div class="stat-card">
                    <div class="stat-icon active">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Active Customers</h3>
                        <p class="stat-value"><?php echo $active; ?></p>
                    </div>
                </div>
                
                <?php
                // Average order value
                $query = "SELECT AVG(total) as avg FROM orders WHERE status = 'delivered'";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $avgOrderValue = $stmt->fetch(PDO::FETCH_ASSOC)['avg'] ?? 0;
                ?>
                <div class="stat-card">
                    <div class="stat-icon revenue">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Avg. Order Value</h3>
                        <p class="stat-value">$<?php echo number_format($avgOrderValue, 2); ?></p>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <!-- Same pagination -->
            </div>
        </main>
    </div>

    <!-- Send Message Modal -->
    <div class="modal" id="messageModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Send Message to Customer</h3>
                <button class="modal-close" onclick="closeMessageModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="messageCustomerId">
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" id="messageSubject" placeholder="Enter message subject">
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea id="messageContent" rows="5" placeholder="Enter your message..."></textarea>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="sendEmailCopy">
                        Send email copy to customer
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeMessageModal()">Cancel</button>
                <button class="btn-primary" onclick="sendCustomerMessage()">Send Message</button>
            </div>
        </div>
    </div>

    <script>
        // Send message functionality
        let currentCustomerId = null;
        
        function sendMessage(customerId) {
            currentCustomerId = customerId;
            document.getElementById('messageModal').style.display = 'block';
            document.getElementById('messageCustomerId').value = customerId;
        }

        function sendCustomerMessage() {
            const subject = document.getElementById('messageSubject').value;
            const content = document.getElementById('messageContent').value;
            const sendEmail = document.getElementById('sendEmailCopy').checked;
            
            if (!subject || !content) {
                alert('Please fill in all fields');
                return;
            }
            
            fetch('../api/admin/send-message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    customer_id: currentCustomerId,
                    subject: subject,
                    message: content,
                    send_email: sendEmail
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Message sent successfully');
                    closeMessageModal();
                } else {
                    alert('Error sending message');
                }
            });
        }

        function closeMessageModal() {
            document.getElementById('messageModal').style.display = 'none';
            document.getElementById('messageSubject').value = '';
            document.getElementById('messageContent').value = '';
        }

        // Export customers
        function exportCustomers() {
            window.location.href = '../api/admin/export-customers.php';
        }

        // Customer search with debounce
        let searchTimeout;
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.form.submit();
                }, 500);
            });
        }
    </script>
</body>
</html>