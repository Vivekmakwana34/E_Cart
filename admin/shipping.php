<?php
session_start();
require_once __DIR__ . '/../Database_file/config.php';

// Create shipping_zones table if not exists
$createTableSQL = "CREATE TABLE IF NOT EXISTS shipping_zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone_name VARCHAR(100) NOT NULL,
    locations TEXT NOT NULL,
    shipping_charge DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($createTableSQL);

// Insert default shipping zones if table is empty
$checkEmpty = $conn->query("SELECT COUNT(*) as count FROM shipping_zones");
$count = $checkEmpty->fetch_assoc()['count'];

if ($count == 0) {
    $defaultZones = [
        ['Local City', 'Pune, PCMC', 0.00, 'active'],
        ['Maharashtra', 'Mumbai, Thane, Nashik, Nagpur', 49.00, 'active'],
        ['Rest of India', 'All other states', 99.00, 'active']
    ];
    
    $insertStmt = $conn->prepare("INSERT INTO shipping_zones (zone_name, locations, shipping_charge, status) VALUES (?, ?, ?, ?)");
    foreach ($defaultZones as $zone) {
        $insertStmt->bind_param("ssds", $zone[0], $zone[1], $zone[2], $zone[3]);
        $insertStmt->execute();
    }
    $insertStmt->close();
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_zone':
            $zoneName = $_POST['zone_name'] ?? '';
            $locations = $_POST['locations'] ?? '';
            $charge = $_POST['shipping_charge'] ?? 0;
            $status = $_POST['status'] ?? 'active';
            
            if ($zoneName && $locations) {
                $stmt = $conn->prepare("INSERT INTO shipping_zones (zone_name, locations, shipping_charge, status) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssds", $zoneName, $locations, $charge, $status);
                $stmt->execute();
                $stmt->close();
            }
            header('Location: shipping.php?success=added');
            exit;
            
        case 'edit_zone':
            $id = $_POST['zone_id'] ?? 0;
            $zoneName = $_POST['zone_name'] ?? '';
            $locations = $_POST['locations'] ?? '';
            $charge = $_POST['shipping_charge'] ?? 0;
            $status = $_POST['status'] ?? 'active';
            
            if ($id && $zoneName && $locations) {
                $stmt = $conn->prepare("UPDATE shipping_zones SET zone_name = ?, locations = ?, shipping_charge = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssdsi", $zoneName, $locations, $charge, $status, $id);
                $stmt->execute();
                $stmt->close();
            }
            header('Location: shipping.php?success=updated');
            exit;
            
        case 'delete_zone':
            $id = $_POST['zone_id'] ?? 0;
            if ($id) {
                $stmt = $conn->prepare("DELETE FROM shipping_zones WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }
            header('Location: shipping.php?success=deleted');
            exit;
            
        case 'toggle_status':
            $id = $_POST['zone_id'] ?? 0;
            $currentStatus = $_POST['current_status'] ?? 'active';
            $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';
            
            if ($id) {
                $stmt = $conn->prepare("UPDATE shipping_zones SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $newStatus, $id);
                $stmt->execute();
                $stmt->close();
            }
            header('Location: shipping.php?success=status_updated');
            exit;
    }
}

// Fetch all shipping zones
$zones = [];
$result = $conn->query("SELECT * FROM shipping_zones ORDER BY id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $zones[] = $row;
    }
}

// Get success message
$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Shipping Management - Admin Panel</title>
    <link rel="stylesheet" href="../admin/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .container {
            display: flex;
        }

        body {
            background-color: #f0f2f5;
            color: #333333;
            min-height: 100vh;
        }

        .main-content {
            flex-grow: 1;
            padding: 20px;
            margin-left: 290px;
        }

        header {
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 20px;
        }

        .shipping-management {
            margin-top: 20px;
        }

        .shipping-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .shipping-header h2 {
            margin: 0;
            font-size: 20px;
        }

        .search-box-container {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .search-input {
            padding: 10px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            font-size: 14px;
            width: 300px;
            outline: none;
            transition: all 0.2s;
        }

        .search-input:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        .shipping-header h2 {
            margin: 0;
            font-size: 20px;
        }

        .card {
            background-color: #ffffff;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            padding: 16px 18px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.04);
            transition: none !important;
            animation: none !important;
        }

        .card:hover {
            transform: none !important;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.04) !important;
        }

        .shipping-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .shipping-table th,
        .shipping-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .shipping-table th {
            background-color: #f9fafb;
            font-weight: 600;
        }

        .shipping-table tr {
            transition: none !important;
            animation: none !important;
        }

        .shipping-table th:nth-child(3),
        .shipping-table td:nth-child(3),
        .shipping-table th:nth-child(4),
        .shipping-table td:nth-child(4) {
            text-align: center;
        }

        .shipping-table th:last-child,
        .shipping-table td:last-child {
            text-align: center;
            white-space: nowrap;
        }

        .shipping-table td:last-child {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
        }

        .btn {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
        }

        .btn:hover {
            /* No movement or shadow on hover; color changes are handled by variant classes */
        }

        .btn-primary {
            background-color: #ff9800;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #f57c00;
            color: #ffffff;
        }

        .btn-secondary {
            background-color: #e5e7eb;
            color: #333333;
        }

        .btn-secondary:hover {
            background-color: #d1d5db;
        }

        .btn-danger {
            background-color: #e74c3c;
            color: #ffffff;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background-color: #e3fcef;
            color: #0e9f6e;
        }

        .status-inactive {
            background-color: #fdecea;
            color: #e02424;
        }

        .modal-backdrop {
            position: fixed;
            inset: 0;
            background-color: rgba(15, 23, 42, 0.45);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 50;
        }

        .modal-backdrop.hidden {
            display: none;
        }

        .modal {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px 22px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.15);
            position: relative;
        }

        .modal-header {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            border: none;
            background: none;
            font-size: 20px;
            cursor: pointer;
            color: #6b7280;
        }

        .modal-close:hover {
            color: #111827;
        }

        .modal-body {
            margin-bottom: 16px;
            font-size: 14px;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 12px;
        }

        .form-group label {
            display: block;
            margin-bottom: 4px;
            font-size: 14px;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px 10px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #ff9800;
            box-shadow: 0 0 0 1px rgba(255, 152, 0, 0.25);
        }

        .error-text {
            display: block;
            font-size: 12px;
            color: #dc2626;
            margin-top: 4px;
        }

        .input-error {
            border-color: #dc2626 !important;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            margin: 20px 20px 0 20px;
            border-radius: 8px;
            border: 1px solid #c3e6cb;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .main-content {
                margin-left: 0;
                padding: 12px;
            }

            .shipping-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .shipping-header button {
                width: 100%;
            }

            .shipping-table {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .shipping-table thead,
            .shipping-table tbody,
            .shipping-table tr,
            .shipping-table th,
            .shipping-table td {
                white-space: nowrap;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <nav class="navbar" id="sidebarNav">
            <div class="navbar-header">
                <h2>Admin Panel</h2>
                <button class="navbar-toggle" id="navbarToggle" aria-label="Toggle navigation">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
            <ul>
               <li><a href="home.php"><i class="fa-solid fa-gauge" style="margin-right: 8px;"></i>Dashboard</a></li>
                <li><a href="users.php"><i class="fa-solid fa-users" style="margin-right: 8px;"></i>Users</a></li>
                <li><a href="banner.php"><i class="fa-solid fa-image" style="margin-right: 8px;"></i>Banner</a></li>
                <li><a href="categories.php"><i class="fa-solid fa-list" style="margin-right: 8px;"></i>Categories</a></li>
                <li><a href="products.php"><i class="fa-solid fa-box" style="margin-right: 8px;"></i>Products</a></li>
                <li><a href="orders.php"><i class="fa-solid fa-cart-shopping" style="margin-right: 8px;"></i>Orders</a></li>
                <li><a href="coupons.php"><i class="fa-solid fa-ticket" style="margin-right: 8px;"></i>Coupons</a></li>
                <li><a href="Gift.php"><i class="fa-solid fa-gift" style="margin-right: 8px;"></i>Gift Cards</a></li>
                <li><a href="payments.php"><i class="fa-solid fa-credit-card" style="margin-right: 8px;"></i>Payments</a></li>
                <li><a href="shipping.php"><i class="fa-solid fa-truck" style="margin-right: 8px;"></i>Shipping</a></li>
                <li><a href="reports.php"><i class="fa-solid fa-chart-line" style="margin-right: 8px;"></i>Reports</a></li>
                <li><a href="reviews.php"><i class="fa-solid fa-star" style="margin-right: 8px;"></i>Reviews</a></li>
                <li><a href="transactions.php"><i class="fa-solid fa-wallet" style="margin-right: 8px;"></i>Transactions</a></li>
                <li><a href="contact.php"><i class="fa-solid fa-envelope" style="margin-right: 8px;"></i>Contact</a></li>
                <li><a href="support.php"><i class="fa-solid fa-headset" style="margin-right: 8px;"></i>Support</a></li>
                <li><a href="settings.php"><i class="fa-solid fa-gear" style="margin-right: 8px;"></i>Settings</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header>
                <h1>Shipping Management</h1>
            </header>

            <section class="shipping-management">
                <div class="card">
                <?php if ($success): ?>
                <div style="background: #d4edda; color: #155724; padding: 12px 20px; margin: 20px 20px 0 20px; border-radius: 8px; border: 1px solid #c3e6cb;">
                    <i class="fa-solid fa-check-circle" style="margin-right: 8px;"></i>
                    <?php
                    switch ($success) {
                        case 'added': echo 'Shipping zone added successfully!'; break;
                        case 'updated': echo 'Shipping zone updated successfully!'; break;
                        case 'deleted': echo 'Shipping zone deleted successfully!'; break;
                        case 'status_updated': echo 'Status updated successfully!'; break;
                        default: echo 'Operation completed successfully!';
                    }
                    ?>
                </div>
                <?php endif; ?>
                    <div class="shipping-header">
                        <h2>Shipping Zones</h2>
                        <div class="search-box-container">
                            <input type="text" id="zoneSearch" class="search-input" placeholder="Search by city or zone name...">
                            <button type="button" class="btn btn-primary" id="openAddZone">Add Shipping Zone</button>
                        </div>
                    </div>

                    <table class="shipping-table">
                        <thead>
                            <tr>
                                <th>Zone Name</th>
                                <th>Locations (Cities / Areas)</th>
                                <th>Base Delivery Charge</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="shippingTableBody">
                            <?php if (!empty($zones)): ?>
                                <?php foreach ($zones as $zone): ?>
                                <tr data-zone-id="<?php echo $zone['id']; ?>" data-zone-name="<?php echo htmlspecialchars($zone['zone_name']); ?>" data-zone-status="<?php echo $zone['status']; ?>">
                                    <td><?php echo htmlspecialchars($zone['zone_name']); ?></td>
                                    <td><?php echo htmlspecialchars($zone['locations']); ?></td>
                                    <td>₹<?php echo number_format($zone['shipping_charge'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $zone['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo ucfirst($zone['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-secondary btn-edit-zone">Edit</button>
                                        <button type="button" class="btn btn-secondary btn-toggle-zone"><?php echo $zone['status'] === 'active' ? 'Disable' : 'Enable'; ?></button>
                                        <button type="button" class="btn btn-danger btn-delete-zone">Delete</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 20px;">No shipping zones found. Add your first zone!</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- Add Shipping Zone Modal -->
    <div id="addZoneModalBackdrop" class="modal-backdrop hidden">
        <div class="modal">
            <button type="button" class="modal-close" id="closeAddZone">&times;</button>
            <div class="modal-header">Add Shipping Zone</div>
            <div class="modal-body">
                <form id="addZoneForm" method="post" action="shipping.php">
                    <input type="hidden" name="action" value="add_zone">
                    <div class="form-group">
                        <label for="addZoneName">Zone Name</label>
                        <input type="text" id="addZoneName" name="zone_name" placeholder="e.g. Local City">
                        <span class="error-text" id="addZoneNameError"></span>
                    </div>
                    <div class="form-group">
                        <label for="addZoneLocations">Cities / Areas (comma separated)</label>
                        <textarea id="addZoneLocations" name="locations" placeholder="e.g. Mumbai, Thane, Navi Mumbai"></textarea>
                        <span class="error-text" id="addZoneLocationsError"></span>
                    </div>
                    <div class="form-group">
                        <label for="addZoneCharge">Base Delivery Charge (₹)</label>
                        <input type="number" id="addZoneCharge" name="shipping_charge" placeholder="e.g. 40" min="0" step="0.01">
                        <span class="error-text" id="addZoneChargeError"></span>
                    </div>
                    <div class="form-group">
                        <label for="addZoneStatus">Status</label>
                        <select id="addZoneStatus" name="status">
                            <option value="">Select status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <span class="error-text" id="addZoneStatusError"></span>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" id="cancelAddZone">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Zone</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Shipping Zone Modal -->
    <div id="editZoneModalBackdrop" class="modal-backdrop hidden">
        <div class="modal">
            <button type="button" class="modal-close" id="closeEditZone">&times;</button>
            <div class="modal-header">Edit Shipping Zone</div>
            <div class="modal-body">
                <form id="editZoneForm" method="post" action="shipping.php">
                    <input type="hidden" name="action" value="edit_zone">
                    <input type="hidden" name="zone_id" id="editZoneId">
                    <div class="form-group">
                        <label for="editZoneName">Zone Name</label>
                        <input type="text" id="editZoneName" name="zone_name">
                        <span class="error-text" id="editZoneNameError"></span>
                    </div>
                    <div class="form-group">
                        <label for="editZoneLocations">Cities / Areas (comma separated)</label>
                        <textarea id="editZoneLocations" name="locations"></textarea>
                        <span class="error-text" id="editZoneLocationsError"></span>
                    </div>
                    <div class="form-group">
                        <label for="editZoneCharge">Base Delivery Charge (₹)</label>
                        <input type="number" id="editZoneCharge" name="shipping_charge" min="0" step="0.01">
                        <span class="error-text" id="editZoneChargeError"></span>
                    </div>
                    <div class="form-group">
                        <label for="editZoneStatus">Status</label>
                        <select id="editZoneStatus" name="status">
                            <option value="">Select status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <span class="error-text" id="editZoneStatusError"></span>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" id="cancelEditZone">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Zone</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Shipping Zone Modal -->
    <div id="deleteZoneModalBackdrop" class="modal-backdrop hidden">
        <div class="modal">
            <button type="button" class="modal-close" id="closeDeleteZone">&times;</button>
            <div class="modal-header">Delete Shipping Zone</div>
            <div class="modal-body">
                <p id="deleteZoneText">Are you sure you want to delete this shipping zone?</p>
                <form id="deleteZoneForm" method="post" action="shipping.php" style="display: none;">
                    <input type="hidden" name="action" value="delete_zone">
                    <input type="hidden" name="zone_id" id="deleteZoneId">
                </form>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelDeleteZone">Cancel</button>
                <button type="submit" class="btn btn-danger" id="confirmDeleteZone" form="deleteZoneForm">Delete</button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const navbar = document.getElementById('sidebarNav');
            const navbarToggle = document.getElementById('navbarToggle');
            if (navbar && navbarToggle) {
                navbarToggle.addEventListener('click', function () {
                    navbar.classList.toggle('open');
                });
            }

            // Highlight current page in sidebar
            if (navbar) {
                const links = navbar.querySelectorAll('a');
                const currentPage = window.location.pathname.split('/').pop();

                links.forEach(link => {
                    const href = link.getAttribute('href');
                    if (!href) return;
                    const hrefPage = href.split('/').pop();
                    if (hrefPage === currentPage) {
                        link.classList.add('active');
                    }
                });
            }

            const shippingTableBody = document.getElementById('shippingTableBody');
            const zoneSearch = document.getElementById('zoneSearch');

            // Search functionality
            if (zoneSearch && shippingTableBody) {
                zoneSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    const rows = shippingTableBody.querySelectorAll('tr');
                    
                    rows.forEach(row => {
                        const zoneName = row.dataset.zoneName || '';
                        const locations = row.children[1] ? row.children[1].textContent.toLowerCase() : '';
                        
                        if (searchTerm === '' || zoneName.toLowerCase().includes(searchTerm) || locations.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }

            const openAddZoneBtn = document.getElementById('openAddZone');
            const addZoneModalBackdrop = document.getElementById('addZoneModalBackdrop');
            const closeAddZoneBtn = document.getElementById('closeAddZone');
            const cancelAddZoneBtn = document.getElementById('cancelAddZone');
            const addZoneForm = document.getElementById('addZoneForm');

            const editZoneModalBackdrop = document.getElementById('editZoneModalBackdrop');
            const closeEditZoneBtn = document.getElementById('closeEditZone');
            const cancelEditZoneBtn = document.getElementById('cancelEditZone');
            const editZoneForm = document.getElementById('editZoneForm');

            const deleteZoneModalBackdrop = document.getElementById('deleteZoneModalBackdrop');
            const closeDeleteZoneBtn = document.getElementById('closeDeleteZone');
            const cancelDeleteZoneBtn = document.getElementById('cancelDeleteZone');
            const confirmDeleteZoneBtn = document.getElementById('confirmDeleteZone');
            const deleteZoneText = document.getElementById('deleteZoneText');

            const addZoneNameError = document.getElementById('addZoneNameError');
            const addZoneLocationsError = document.getElementById('addZoneLocationsError');
            const addZoneChargeError = document.getElementById('addZoneChargeError');
            const addZoneStatusError = document.getElementById('addZoneStatusError');

            const editZoneNameError = document.getElementById('editZoneNameError');
            const editZoneLocationsError = document.getElementById('editZoneLocationsError');
            const editZoneChargeError = document.getElementById('editZoneChargeError');
            const editZoneStatusError = document.getElementById('editZoneStatusError');

            let zoneRowBeingEdited = null;
            let zoneRowBeingDeleted = null;

            function clearFieldError(input, errorElement) {
                if (input) {
                    input.classList.remove('input-error');
                }
                if (errorElement) {
                    errorElement.textContent = '';
                }
            }

            function showFieldError(input, errorElement, message) {
                if (input) {
                    input.classList.add('input-error');
                }
                if (errorElement) {
                    errorElement.textContent = message || '';
                }
            }

            function zoneNameExists(name, ignoreRow) {
                const rows = shippingTableBody.querySelectorAll('tr');
                const target = name.trim().toLowerCase();
                for (const row of rows) {
                    if (ignoreRow && row === ignoreRow) continue;
                    const nameCell = row.children[0];
                    if (!nameCell) continue;
                    if (nameCell.textContent.trim().toLowerCase() === target) {
                        return true;
                    }
                }
                return false;
            }

            function validateZoneForm(prefix, ignoreRow) {
                const isEdit = prefix === 'edit';

                const nameInput = document.getElementById(prefix + 'ZoneName');
                const locationsInput = document.getElementById(prefix + 'ZoneLocations');
                const chargeInput = document.getElementById(prefix + 'ZoneCharge');
                const statusSelect = document.getElementById(prefix + 'ZoneStatus');

                const nameError = prefix === 'add' ? addZoneNameError : editZoneNameError;
                const locationsError = prefix === 'add' ? addZoneLocationsError : editZoneLocationsError;
                const chargeError = prefix === 'add' ? addZoneChargeError : editZoneChargeError;
                const statusError = prefix === 'add' ? addZoneStatusError : editZoneStatusError;

                clearFieldError(nameInput, nameError);
                clearFieldError(locationsInput, locationsError);
                clearFieldError(chargeInput, chargeError);
                clearFieldError(statusSelect, statusError);

                const name = nameInput ? nameInput.value.trim() : '';
                const locations = locationsInput ? locationsInput.value.trim() : '';
                const chargeValue = chargeInput ? chargeInput.value.trim() : '';
                const status = statusSelect ? statusSelect.value : '';

                let isValid = true;

                if (!name) {
                    showFieldError(nameInput, nameError, 'Please enter zone name.');
                    isValid = false;
                } else if (name.length < 3) {
                    showFieldError(nameInput, nameError, 'Zone name should be at least 3 characters long.');
                    isValid = false;
                } else if (zoneNameExists(name, ignoreRow)) {
                    showFieldError(nameInput, nameError, isEdit ? 'Another zone with this name already exists.' : 'A zone with this name already exists.');
                    isValid = false;
                }

                if (!locations) {
                    showFieldError(locationsInput, locationsError, 'Please enter at least one city/area.');
                    isValid = false;
                }

                if (!chargeValue) {
                    showFieldError(chargeInput, chargeError, 'Please enter base delivery charge.');
                    isValid = false;
                } else {
                    const chargeNumber = parseFloat(chargeValue);
                    if (isNaN(chargeNumber) || chargeNumber < 0) {
                        showFieldError(chargeInput, chargeError, 'Please enter a valid charge (0 or more).');
                        isValid = false;
                    }
                }

                if (!status) {
                    showFieldError(statusSelect, statusError, 'Please select status.');
                    isValid = false;
                }

                return {
                    isValid,
                    values: {
                        name,
                        locations,
                        charge: parseFloat(chargeValue || '0'),
                        status
                    }
                };
            }

            // Open Add Zone modal
            if (openAddZoneBtn && addZoneModalBackdrop && addZoneForm) {
                openAddZoneBtn.addEventListener('click', function () {
                    addZoneForm.reset();
                    clearFieldError(document.getElementById('addZoneName'), addZoneNameError);
                    clearFieldError(document.getElementById('addZoneLocations'), addZoneLocationsError);
                    clearFieldError(document.getElementById('addZoneCharge'), addZoneChargeError);
                    clearFieldError(document.getElementById('addZoneStatus'), addZoneStatusError);
                    addZoneModalBackdrop.classList.remove('hidden');
                });
            }

            if (cancelAddZoneBtn && addZoneModalBackdrop) {
                cancelAddZoneBtn.addEventListener('click', function () {
                    addZoneModalBackdrop.classList.add('hidden');
                });
            }

            if (closeAddZoneBtn && addZoneModalBackdrop) {
                closeAddZoneBtn.addEventListener('click', function () {
                    addZoneModalBackdrop.classList.add('hidden');
                });
            }

            if (cancelEditZoneBtn && editZoneModalBackdrop) {
                cancelEditZoneBtn.addEventListener('click', function () {
                    editZoneModalBackdrop.classList.add('hidden');
                    zoneRowBeingEdited = null;
                });
            }

            if (closeEditZoneBtn && editZoneModalBackdrop) {
                closeEditZoneBtn.addEventListener('click', function () {
                    editZoneModalBackdrop.classList.add('hidden');
                    zoneRowBeingEdited = null;
                });
            }

            if (cancelDeleteZoneBtn && deleteZoneModalBackdrop) {
                cancelDeleteZoneBtn.addEventListener('click', function () {
                    deleteZoneModalBackdrop.classList.add('hidden');
                    zoneRowBeingDeleted = null;
                });
            }

            if (closeDeleteZoneBtn && deleteZoneModalBackdrop) {
                closeDeleteZoneBtn.addEventListener('click', function () {
                    deleteZoneModalBackdrop.classList.add('hidden');
                    zoneRowBeingDeleted = null;
                });
            }

            // Add Zone submit
            if (addZoneForm) {
                addZoneForm.addEventListener('submit', function (e) {
                    const result = validateZoneForm('add');
                    if (!result.isValid) {
                        e.preventDefault();
                        return;
                    }
                    // Allow normal form submission to PHP backend
                    actionsTd.appendChild(deleteBtn);

                    tr.appendChild(nameTd);
                    tr.appendChild(locationsTd);
                    tr.appendChild(chargeTd);
                    tr.appendChild(statusTd);
                    tr.appendChild(actionsTd);

                    shippingTableBody.appendChild(tr);

                    addZoneModalBackdrop.classList.add('hidden');
                });
            }

            // Edit / Toggle / Delete events for zones
            if (shippingTableBody) {
                shippingTableBody.addEventListener('click', function (e) {
                    const target = e.target;
                    const row = target.closest('tr');
                    if (!row) return;

                    if (target.classList.contains('btn-edit-zone')) {
                        zoneRowBeingEdited = row;

                        const zoneId = row.dataset.zoneId;
                        const nameCell = row.children[0];
                        const locationsCell = row.children[1];
                        const chargeCell = row.children[2];
                        const statusBadge = row.querySelector('.status-badge');

                        const zoneIdInput = document.getElementById('editZoneId');
                        const nameInput = document.getElementById('editZoneName');
                        const locationsInput = document.getElementById('editZoneLocations');
                        const chargeInput = document.getElementById('editZoneCharge');
                        const statusSelect = document.getElementById('editZoneStatus');

                        zoneIdInput.value = zoneId;
                        nameInput.value = nameCell ? nameCell.textContent.trim() : '';
                        locationsInput.value = locationsCell ? locationsCell.textContent.trim() : '';

                        let chargeText = chargeCell ? chargeCell.textContent.trim() : '';
                        if (chargeText.startsWith('₹')) {
                            chargeText = chargeText.substring(1);
                        }
                        chargeInput.value = chargeText;

                        let statusValue = 'active';
                        if (statusBadge && statusBadge.classList.contains('status-inactive')) {
                            statusValue = 'inactive';
                        }
                        statusSelect.value = statusValue;

                        clearFieldError(nameInput, editZoneNameError);
                        clearFieldError(locationsInput, editZoneLocationsError);
                        clearFieldError(chargeInput, editZoneChargeError);
                        clearFieldError(statusSelect, editZoneStatusError);

                        editZoneModalBackdrop.classList.remove('hidden');
                    }

                    if (target.classList.contains('btn-toggle-zone')) {
                        const zoneId = row.dataset.zoneId;
                        const currentStatus = row.dataset.zoneStatus;
                        
                        // Submit form to toggle status
                        const form = document.createElement('form');
                        form.method = 'post';
                        form.action = 'shipping.php';
                        form.style.display = 'none';
                        
                        const actionInput = document.createElement('input');
                        actionInput.type = 'hidden';
                        actionInput.name = 'action';
                        actionInput.value = 'toggle_status';
                        
                        const zoneIdInput = document.createElement('input');
                        zoneIdInput.type = 'hidden';
                        zoneIdInput.name = 'zone_id';
                        zoneIdInput.value = zoneId;
                        
                        const statusInput = document.createElement('input');
                        statusInput.type = 'hidden';
                        statusInput.name = 'current_status';
                        statusInput.value = currentStatus;
                        
                        form.appendChild(actionInput);
                        form.appendChild(zoneIdInput);
                        form.appendChild(statusInput);
                        document.body.appendChild(form);
                        form.submit();
                    }

                    if (target.classList.contains('btn-delete-zone')) {
                        zoneRowBeingDeleted = row;
                        const zoneId = row.dataset.zoneId;
                        const nameCell = row.children[0];
                        const zoneName = nameCell ? nameCell.textContent.trim() : 'this zone';
                        deleteZoneText.textContent = 'Are you sure you want to delete shipping zone "' + zoneName + '"?';
                        
                        const deleteZoneIdInput = document.getElementById('deleteZoneId');
                        deleteZoneIdInput.value = zoneId;
                        
                        deleteZoneModalBackdrop.classList.remove('hidden');
                    }
                });
            }

            // Edit zone submit - remove preventDefault since we want normal form submission
            if (editZoneForm) {
                editZoneForm.addEventListener('submit', function (e) {
                    if (!zoneRowBeingEdited) return;

                    const result = validateZoneForm('edit', zoneRowBeingEdited);
                    if (!result.isValid) {
                        e.preventDefault();
                        return;
                    }
                    // Allow normal form submission to PHP backend
                });
            }

            // Confirm delete zone
            if (confirmDeleteZoneBtn) {
                confirmDeleteZoneBtn.addEventListener('click', function () {
                    if (zoneRowBeingDeleted) {
                        zoneRowBeingDeleted.remove();
                    }
                    deleteZoneModalBackdrop.classList.add('hidden');
                    zoneRowBeingDeleted = null;
                });
            }
        })();
    </script>
</body>

</html>
