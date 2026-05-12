<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Handle delete
if (isset($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    $query = "DELETE FROM products WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $product_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Product deleted successfully';
    } else {
        $_SESSION['error'] = 'Failed to delete product';
    }
    
    header('Location: products.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total products
$query = "SELECT COUNT(*) as total FROM products";
$stmt = $conn->prepare($query);
$stmt->execute();
$totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalProducts / $limit);

// Get products with pagination
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          ORDER BY p.created_at DESC 
          LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($query);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - FreshMart Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar (Same as index.php) -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shopping-basket"></i> FreshMart Admin</h2>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="products.php">
                            <i class="fas fa-box"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <!-- ... other menu items ... -->
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <h1>Manage Products</h1>
                    <p>View, add, edit or delete products</p>
                </div>
                <div class="header-right">
                    <a href="add-product.php" class="btn-primary">
                        <i class="fas fa-plus"></i> Add New Product
                    </a>
                    <button class="btn-secondary" onclick="exportProducts()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </header>

            <!-- Success/Error Messages -->
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
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search products..." id="searchInput">
                </div>
                <div class="filter-group">
                    <select id="categoryFilter">
                        <option value="">All Categories</option>
                        <?php
                        $catQuery = "SELECT * FROM categories ORDER BY name";
                        $catStmt = $conn->prepare($catQuery);
                        $catStmt->execute();
                        $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($categories as $category):
                        ?>
                        <option value="<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select id="statusFilter">
                        <option value="">All Status</option>
                        <option value="in_stock">In Stock</option>
                        <option value="out_of_stock">Out of Stock</option>
                        <option value="low_stock">Low Stock</option>
                    </select>
                    
                    <button class="btn-filter" onclick="applyFilters()">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </div>

            <!-- Products Table -->
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    <i class="fas fa-box-open"></i>
                                    <p>No products found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="product-checkbox" value="<?php echo $product['id']; ?>">
                                </td>
                                <td>
                                    <div class="product-cell">
                                        <img src="../assets/images/products/<?php echo $product['image']; ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             width="50" height="50">
                                        <div>
                                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                            <small>SKU: <?php echo $product['sku'] ?? 'N/A'; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                <td>
                                    <strong>$<?php echo number_format($product['price'], 2); ?></strong>
                                    <?php if ($product['compare_price'] > 0): ?>
                                        <br>
                                        <small class="text-muted">
                                            <del>$<?php echo number_format($product['compare_price'], 2); ?></del>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="stock-badge 
                                        <?php 
                                        if ($product['stock'] == 0) echo 'danger';
                                        elseif ($product['stock'] < 10) echo 'warning';
                                        else echo 'success';
                                        ?>">
                                        <?php echo $product['stock']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge 
                                        <?php echo $product['status'] == 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($product['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit-product.php?id=<?php echo $product['id']; ?>" 
                                           class="btn-edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?php echo $product['id']; ?>" 
                                           class="btn-delete" 
                                           onclick="return confirm('Are you sure you want to delete this product?')"
                                           title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <a href="../product.php?id=<?php echo $product['id']; ?>" 
                                           class="btn-view" target="_blank" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn-quick" 
                                                onclick="quickUpdateStock(<?php echo $product['id']; ?>)"
                                                title="Update Stock">
                                            <i class="fas fa-boxes"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Bulk Actions -->
            <div class="bulk-actions">
                <select id="bulkAction">
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete Selected</option>
                    <option value="activate">Activate</option>
                    <option value="deactivate">Deactivate</option>
                    <option value="out_of_stock">Mark Out of Stock</option>
                </select>
                <button class="btn-apply" onclick="applyBulkAction()">Apply</button>
                <span class="selected-count">0 selected</span>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" class="page-btn">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <div class="page-numbers">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                            <a href="?page=<?php echo $i; ?>" 
                               class="<?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                            <span class="dots">...</span>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="page-btn">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
                
                <div class="page-info">
                    Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $limit, $totalProducts); ?> of <?php echo $totalProducts; ?> products
                </div>
            </div>
        </main>
    </div>

    <!-- Quick Update Modal -->
    <div class="modal" id="stockModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Stock</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="updateProductId">
                <div class="form-group">
                    <label>Current Stock: <span id="currentStock">0</span></label>
                    <input type="number" id="newStock" min="0" placeholder="Enter new stock quantity">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="btn-primary" onclick="updateStock()">Update</button>
            </div>
        </div>
    </div>

    <script>
        // Bulk selection
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.product-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedCount();
        });

        function updateSelectedCount() {
            const selected = document.querySelectorAll('.product-checkbox:checked').length;
            document.querySelector('.selected-count').textContent = selected + ' selected';
        }

        document.querySelectorAll('.product-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCount);
        });

        // Bulk actions
        function applyBulkAction() {
            const action = document.getElementById('bulkAction').value;
            const selectedIds = Array.from(document.querySelectorAll('.product-checkbox:checked'))
                .map(cb => cb.value);
            
            if (selectedIds.length === 0) {
                alert('Please select at least one product');
                return;
            }
            
            if (action === 'delete') {
                if (confirm(`Delete ${selectedIds.length} product(s)?`)) {
                    // In real app, send AJAX request
                    fetch('../api/admin/bulk-delete.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            product_ids: selectedIds
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Products deleted successfully');
                            location.reload();
                        }
                    });
                }
            }
        }

        // Quick update stock
        let currentProductId = null;
        
        function quickUpdateStock(productId) {
            currentProductId = productId;
            // In real app, fetch current stock via AJAX
            document.getElementById('currentStock').textContent = 'Loading...';
            document.getElementById('stockModal').style.display = 'block';
            
            fetch(`../api/admin/get-stock.php?id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('currentStock').textContent = data.stock;
                    document.getElementById('updateProductId').value = productId;
                });
        }

        function updateStock() {
            const newStock = document.getElementById('newStock').value;
            
            if (!newStock || isNaN(newStock) || newStock < 0) {
                alert('Please enter a valid stock quantity');
                return;
            }
            
            fetch('../api/admin/update-stock.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: currentProductId,
                    stock: parseInt(newStock)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Stock updated successfully');
                    closeModal();
                    location.reload();
                }
            });
        }

        function closeModal() {
            document.getElementById('stockModal').style.display = 'none';
            document.getElementById('newStock').value = '';
        }

        // Export products
        function exportProducts() {
            window.location.href = '../api/admin/export-products.php';
        }

        // Live search
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                const searchTerm = document.getElementById('searchInput').value;
                const category = document.getElementById('categoryFilter').value;
                const status = document.getElementById('statusFilter').value;
                
                // In real app, implement AJAX search
                console.log('Searching:', {searchTerm, category, status});
            }, 500);
        });
    </script>
</body>
</html>