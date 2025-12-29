<?php
// Enable Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Connection & Auth
if (file_exists(__DIR__ . '/../includes/config.php')) {
    require_once __DIR__ . '/../includes/config.php';
}
if (file_exists(__DIR__ . '/../classes/Auth.php')) {
    require_once __DIR__ . '/../classes/Auth.php';
    if (isset($pdo)) {
        $auth = new Auth($pdo);
        $auth->requireLogin();
    }
} else {
    if (!isset($_SESSION['admin_logged_in'])) {
        header("Location: login.php");
        exit;
    }
}

// Fallback DB
if (!isset($pdo)) {
    try {
        $pdo = new PDO("mysql:host=localhost;port=3307;dbname=tourism_cms;charset=utf8", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) { die("DB Error: " . $e->getMessage()); }
}

$success = "";
$error = "";

// 2. SELF-HEALING: Create Tables
try {
    $pdo->query("SELECT 1 FROM menus LIMIT 1");
} catch (PDOException $e) {
    // Create Menus Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `menus` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(100) NOT NULL,
      `location` varchar(50) DEFAULT 'primary',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Create Menu Items Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `menu_items` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `menu_id` int(11) NOT NULL,
      `title` varchar(255) NOT NULL,
      `url` varchar(255) NOT NULL,
      `parent_id` int(11) DEFAULT 0,
      `order_index` int(11) DEFAULT 0,
      `type` varchar(50) DEFAULT 'custom',
      `object_id` int(11) DEFAULT 0,
      `target` varchar(20) DEFAULT '_self',
      `is_active` tinyint(1) DEFAULT 1,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Insert Default Main Menu
    $pdo->exec("INSERT INTO menus (id, name) VALUES (1, 'Main Menu')");
    
    // Seed Links from old hardcoded list
    $defaults = [
        ['Home', 'home', 0, 1, 'page'],
        ['About Us', 'about', 0, 2, 'page'],
        ['Packages', 'packages', 0, 3, 'page'],
        ['Services', 'services', 0, 4, 'page'],
        ['Gallery', 'gallery', 0, 5, 'page'],
        ['Book Now', 'contact', 0, 6, 'page']
    ];
    foreach ($defaults as $link) {
        $stmt = $pdo->prepare("INSERT INTO menu_items (menu_id, title, url, parent_id, order_index, type) VALUES (1, ?, ?, ?, ?, ?)");
        $stmt->execute([$link[0], $link[1], $link[2], $link[3], $link[4]]);
    }
}

// 3. ACTION HANDLERS

// A. Add Item (Page/Package/Custom)
if (isset($_POST['add_item'])) {
    $menuId = 1; // Default Main Menu
    $type = $_POST['source_type'];
    
    // Determine Order (Append to end)
    $stmt = $pdo->query("SELECT MAX(order_index) FROM menu_items WHERE menu_id = $menuId");
    $maxOrder = $stmt->fetchColumn() ?: 0;
    
    if ($type === 'custom') {
        $title = trim($_POST['custom_title']);
        $url = trim($_POST['custom_url']);
        if ($title && $url) {
            $stmt = $pdo->prepare("INSERT INTO menu_items (menu_id, title, url, parent_id, order_index, type) VALUES (?, ?, ?, 0, ?, 'custom')");
            $stmt->execute([$menuId, $title, $url, $maxOrder + 1]);
            $success = "Custom link added!";
        }
    } elseif ($type === 'pages' && !empty($_POST['page_ids'])) {
        foreach ($_POST['page_ids'] as $pid) {
            // Fetch Page Info
            $p = $pdo->query("SELECT title, slug FROM pages WHERE id = $pid")->fetch();
            if ($p) {
                $maxOrder++;
                $stmt = $pdo->prepare("INSERT INTO menu_items (menu_id, title, url, parent_id, order_index, type, object_id) VALUES (?, ?, ?, 0, ?, 'page', ?)");
                $stmt->execute([$menuId, $p['title'], $p['slug'], $maxOrder, $pid]);
            }
        }
        $success = "Pages added to menu!";
    } elseif ($type === 'packages' && !empty($_POST['package_ids'])) {
         foreach ($_POST['package_ids'] as $pid) {
            // Fetch Package Info
            $p = $pdo->query("SELECT title FROM packages WHERE id = $pid")->fetch();
            if ($p) {
                $maxOrder++;
                $stmt = $pdo->prepare("INSERT INTO menu_items (menu_id, title, url, parent_id, order_index, type, object_id) VALUES (?, ?, ?, 0, ?, 'package', ?)");
                $stmt->execute([$menuId, $p['title'], "package-detail.php?id=$pid", $maxOrder, $pid]);
            }
        }
        $success = "Packages added to menu!";
    }
}

// B. Delete Item
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->query("DELETE FROM menu_items WHERE id = $id");
    header("Location: menus.php");
    exit;
}

// C. Save Structure (Update All)
if (isset($_POST['save_menu'])) {
    if (!empty($_POST['items'])) {
        foreach ($_POST['items'] as $id => $data) {
            $id = (int)$id;
            $title = $data['title'];
            $url = $data['url'] ?? ''; // Only custom links update URL
            $parentId = (int)$data['parent_id'];
            $order = (int)$data['order'];
            $isActive = isset($data['hidden']) ? 0 : 1; // Checkbox checked = hidden? No, logic usually check=active. Let's say check=hide as per prompt. 
            // Prompt says: "Checkbox: Hide Link (Toggle visibility)". So Checked = Hidden (0), Unchecked = Active (1).
            $isActive = isset($data['hide_link']) ? 0 : 1; 

            // Update
            $sql = "UPDATE menu_items SET title = ?, parent_id = ?, order_index = ?, is_active = ?";
            $params = [$title, $parentId, $order, $isActive];
            
            // If custom type, update URL too
            // Easier: Just update URL for everyone, but for pages/packages URL is auto (but user might want to override). 
            // Let's generic update URL if provided.
            if (isset($data['url'])) {
                $sql .= ", url = ?";
                $params[] = $data['url'];
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }
        $success = "Menu structure updated successfully!";
    }
}

// 4. FETCH DATA
// Get all items for Parents Dropdown and Rendering
$menuItems = $pdo->query("SELECT * FROM menu_items WHERE menu_id = 1 ORDER BY order_index ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get Available Pages
$pages = $pdo->query("SELECT id, title FROM pages ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get Available Packages
$packages = $pdo->query("SELECT id, title FROM packages ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);

?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Navbar Structure Manager</h1>
        <a href="../home" target="_blank" class="btn btn-outline-primary"><i class="bi bi-eye"></i> View Site</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- LEFT COL: Sources -->
        <div class="col-lg-4">
            <!-- 1. Custom Links -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">Custom Link</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="source_type" value="custom">
                        <div class="mb-3">
                            <label>URL</label>
                            <input type="text" class="form-control" name="custom_url" placeholder="http://">
                        </div>
                        <div class="mb-3">
                            <label>Link Text</label>
                            <input type="text" class="form-control" name="custom_title" placeholder="Menu Item">
                        </div>
                        <button type="submit" name="add_item" class="btn btn-sm btn-outline-dark float-end">Add to Menu</button>
                    </form>
                </div>
            </div>

            <!-- 2. Pages -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-white" data-bs-toggle="collapse" data-bs-target="#collapsePages" style="cursor:pointer;">
                    <h6 class="m-0 font-weight-bold text-primary d-flex justify-content-between">Pages <i class="bi bi-chevron-down"></i></h6>
                </div>
                <div id="collapsePages" class="collapse show">
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="source_type" value="pages">
                            <div style="max-height: 200px; overflow-y: auto;">
                                <?php foreach($pages as $p): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="page_ids[]" value="<?php echo $p['id']; ?>" id="page<?php echo $p['id']; ?>">
                                    <label class="form-check-label" for="page<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <hr>
                            <button type="submit" name="add_item" class="btn btn-sm btn-outline-dark float-end">Add to Menu</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 3. Packages -->
             <div class="card shadow mb-4">
                <div class="card-header py-3 bg-white" data-bs-toggle="collapse" data-bs-target="#collapsePkgs" style="cursor:pointer;">
                    <h6 class="m-0 font-weight-bold text-primary d-flex justify-content-between">Tour Packages <i class="bi bi-chevron-down"></i></h6>
                </div>
                <div id="collapsePkgs" class="collapse">
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="source_type" value="packages">
                            <div style="max-height: 200px; overflow-y: auto;">
                                <?php foreach($packages as $p): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="package_ids[]" value="<?php echo $p['id']; ?>" id="pkg<?php echo $p['id']; ?>">
                                    <label class="form-check-label" for="pkg<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <hr>
                            <button type="submit" name="add_item" class="btn btn-sm btn-outline-dark float-end">Add to Menu</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COL: Structure -->
        <div class="col-lg-8">
            <form method="POST">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-primary text-white d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold">Menu Structure (Main Menu)</h6>
                        <button type="submit" name="save_menu" class="btn btn-light btn-sm fw-bold">Save Menu</button>
                    </div>
                    <div class="card-body bg-light">
                        <?php if (empty($menuItems)): ?>
                            <p class="text-center text-muted py-4">No items yet. Add some from the left!</p>
                        <?php else: ?>
                            <div id="menu-list">
                                <?php foreach ($menuItems as $item): ?>
                                <div class="card mb-3 menu-item-card border-left-primary shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#itemConfig<?php echo $item['id']; ?>" style="cursor:pointer;">
                                            <div>
                                                <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                                                <small class="text-muted ms-2 badge bg-secondary"><?php echo ucfirst($item['type']); ?></small>
                                                <?php if(!$item['is_active']): ?>
                                                    <span class="badge bg-warning text-dark">Hidden</span>
                                                <?php endif; ?>
                                                <?php if($item['parent_id'] != 0): ?>
                                                    <span class="badge bg-info text-dark">Sub Item</span>
                                                <?php endif; ?>
                                            </div>
                                            <i class="bi bi-chevron-down"></i>
                                        </div>
                                        
                                        <!-- Configuration Area -->
                                        <div id="itemConfig<?php echo $item['id']; ?>" class="collapse mt-3 border-top pt-3">
                                            <input type="hidden" name="items[<?php echo $item['id']; ?>][id]" value="<?php echo $item['id']; ?>">
                                            
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-bold">Navigation Label</label>
                                                    <input type="text" class="form-control form-control-sm" name="items[<?php echo $item['id']; ?>][title]" value="<?php echo htmlspecialchars($item['title']); ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-bold">URL</label>
                                                    <input type="text" class="form-control form-control-sm" name="items[<?php echo $item['id']; ?>][url]" value="<?php echo htmlspecialchars($item['url']); ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small fw-bold">Parent Item</label>
                                                    <select class="form-select form-select-sm" name="items[<?php echo $item['id']; ?>][parent_id]">
                                                        <option value="0">-- No Parent (Top Level) --</option>
                                                        <?php foreach ($menuItems as $parent): ?>
                                                            <?php if ($parent['id'] != $item['id']): // Prevent self-parenting ?>
                                                                <option value="<?php echo $parent['id']; ?>" <?php echo $item['parent_id'] == $parent['id'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($parent['title']); ?>
                                                                </option>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small fw-bold">Order (1=First)</label>
                                                    <input type="number" class="form-control form-control-sm" name="items[<?php echo $item['id']; ?>][order]" value="<?php echo $item['order_index']; ?>">
                                                </div>
                                                <div class="col-md-4 pt-4">
                                                     <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="items[<?php echo $item['id']; ?>][hide_link]" id="hide<?php echo $item['id']; ?>" <?php echo !$item['is_active'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label small" for="hide<?php echo $item['id']; ?>">Hide Link</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-3 text-end"> 
                                                <a href="?delete=<?php echo $item['id']; ?>" class="text-danger small text-decoration-none" onclick="return confirm('Remove this link?');">Remove</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
