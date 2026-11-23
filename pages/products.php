<?php
$pageTitle = 'Products';
require_once '../includes/header.php';

$message = '';
$messageType = '';

// Image upload configuration
$uploadDir = __DIR__ . '/../assets/uploads/products/';
$uploadUrl = '../assets/uploads/products/';
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
$maxFileSize = 2 * 1024 * 1024; // 2MB

// Ensure upload directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

function uploadProductImage($file, $uploadDir, $uploadUrl, $allowedTypes, $maxFileSize, $oldImage = null) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return $oldImage;
    }

    // Validate file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['error' => 'Invalid file type. Only JPG, PNG, WebP, and GIF images are allowed.'];
    }

    // Validate file size
    if ($file['size'] > $maxFileSize) {
        return ['error' => 'File size exceeds 2MB limit.'];
    }

    // Delete old image if exists and is not the default placeholder
    if ($oldImage && $oldImage !== 'placeholder.png') {
        $oldPath = $uploadDir . $oldImage;
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFilename = uniqid('prod_', true) . '.' . $extension;
    $destination = $uploadDir . $newFilename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $newFilename;
    }

    return ['error' => 'Failed to upload image.'];
}

// Add product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    $sku = $_POST['sku'] ?? null;
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);

    $image = 'placeholder.png';
    if (!empty($_FILES['image']['name'])) {
        $result = uploadProductImage($_FILES['image'], $uploadDir, $uploadUrl, $allowedTypes, $maxFileSize);
        if (is_array($result) && isset($result['error'])) {
            $message = $result['error'];
            $messageType = 'error';
        } else {
            $image = $result;
        }
    }

    if (empty($name)) {
        $message = 'Product name is required.';
        $messageType = 'error';
    } elseif (isset($messageType) && $messageType === 'error') {
        // image upload error already set
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO products (name, category_id, sku, price, stock, image) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$name, $category_id, $sku, $price, $stock, $image]);
            $message = 'Product added successfully.';
            $messageType = 'success';
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "Duplicate entry") !== false && strpos($e->getMessage(), "for key 'sku'") !== false) {
                $message = 'SKU already exists. Please use a different SKU.';
            } elseif (strpos($e->getMessage(), "foreign key constraint fails") !== false) {
                $message = 'Invalid category selected. Please choose a valid category.';
            } else {
                $message = 'Database error: ' . htmlspecialchars($e->getMessage());
            }
            $messageType = 'error';
        }
    }
}

// Delete product
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    // Fetch and delete associated image
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$deleteId]);
    $prod = $stmt->fetch();
    if ($prod && $prod['image'] && $prod['image'] !== 'placeholder.png') {
        $imgPath = $uploadDir . $prod['image'];
        if (file_exists($imgPath)) {
            unlink($imgPath);
        }
    }
    try {
        $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$deleteId]);
        header('Location: products.php?deleted=1');
        exit;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "foreign key constraint fails") !== false) {
            $message = 'Cannot delete this product because it has existing sales records.';
        } else {
            $message = 'Database error: ' . htmlspecialchars($e->getMessage());
        }
        $messageType = 'error';
    }
}

if (isset($_GET['deleted'])) {
    $message = 'Product deleted.';
    $messageType = 'success';
}

// Edit product - fetch product data for pre-populating form
$editProduct = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$editId]);
    $editProduct = $stmt->fetch();
}

// Update product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $id = intval($_POST['id'] ?? 0);
    $name = $_POST['name'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    $sku = $_POST['sku'] ?? null;
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);

    // Fetch current image for potential deletion
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $currentProduct = $stmt->fetch();
    $currentImage = $currentProduct['image'] ?? 'placeholder.png';

    $image = $currentImage;
    if (!empty($_FILES['image']['name'])) {
        $result = uploadProductImage($_FILES['image'], $uploadDir, $uploadUrl, $allowedTypes, $maxFileSize, $currentImage);
        if (is_array($result) && isset($result['error'])) {
            $message = $result['error'];
            $messageType = 'error';
        } else {
            $image = $result;
        }
    }

    if (empty($name)) {
        $message = 'Product name is required.';
        $messageType = 'error';
    } elseif (isset($messageType) && $messageType === 'error') {
        // image upload error already set
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE products SET name = ?, category_id = ?, sku = ?, price = ?, stock = ?, image = ? WHERE id = ?");
            $stmt->execute([$name, $category_id, $sku, $price, $stock, $image, $id]);
            $message = 'Product updated successfully.';
            $messageType = 'success';
            header('Location: products.php?updated=1');
            exit;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "Duplicate entry") !== false && strpos($e->getMessage(), "for key 'sku'") !== false) {
                $message = 'SKU already exists. Please use a different SKU.';
            } elseif (strpos($e->getMessage(), "foreign key constraint fails") !== false) {
                $message = 'Invalid category selected. Please choose a valid category.';
            } else {
                $message = 'Database error: ' . htmlspecialchars($e->getMessage());
            }
            $messageType = 'error';
        }
    }
}

if (isset($_GET['updated'])) {
    $message = 'Product updated successfully.';
    $messageType = 'success';
}

$products = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.name")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>
<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="table-header" style="margin-bottom:20px;">
    <h3>Product Management</h3>
    <button class="btn btn-primary" onclick="document.getElementById('addForm').style.display='block'"><i class="fa-solid fa-plus"></i> Add Product</button>
</div>

<div id="addForm" class="form-card" style="margin-bottom:30px; display:none;">
    <h3><i class="fa-solid fa-plus" style="margin-right:8px; color:#059669;"></i>Add New Product</h3>
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="add_product" value="1">
        <div class="form-group">
            <label>Product Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Product Image</label>
            <input type="file" name="image" class="form-control" accept="image/*">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" class="form-control">
                    <option value="">None</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>SKU</label>
                <input type="text" name="sku" class="form-control" placeholder="e.g. P001">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Price (₱)</label>
                <input type="number" name="price" class="form-control" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label>Stock</label>
                <input type="number" name="stock" class="form-control" min="0" value="0" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="padding: 6px 14px; font-size: 13px;">Add Product</button>
        <button type="button" class="btn btn-outline" onclick="document.getElementById('addForm').style.display='none'">Cancel</button>
    </form>
</div>

<div id="editForm" class="form-card" style="margin-bottom:30px; display:none;">
    <h3><i class="fa-solid fa-pen" style="margin-right:8px; color:#059669;"></i>Edit Product</h3>
    <form method="POST" action="" enctype="multipart/form-data" id="editProductForm">
        <input type="hidden" name="update_product" value="1">
        <input type="hidden" name="id" id="editId">
        <div class="form-group">
            <label>Product Name</label>
            <input type="text" name="name" id="editName" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Product Image</label>
            <input type="file" name="image" id="editImage" class="form-control" accept="image/*">
            <div id="editImagePreview" style="margin-top:8px;"></div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" id="editCategory" class="form-control">
                    <option value="">None</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>SKU</label>
                <input type="text" name="sku" id="editSku" class="form-control" placeholder="e.g. P001">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Price (₱)</label>
                <input type="number" name="price" id="editPrice" class="form-control" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label>Stock</label>
                <input type="number" name="stock" id="editStock" class="form-control" min="0" value="0" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Update Product</button>
        <button type="button" class="btn btn-outline" onclick="document.getElementById('editForm').style.display='none'">Cancel</button>
    </form>
</div>

<div class="table-container">
    <div class="table-header">
        <h3>All Products</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>SKU</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td>
                    <img src="<?php echo $uploadUrl . htmlspecialchars($p['image'] ?? 'placeholder.png'); ?>" 
                         alt="<?php echo htmlspecialchars($p['name']); ?>" 
                         class="product-img">
                </td>
                <td><?php echo htmlspecialchars($p['name']); ?></td>
                <td><?php echo htmlspecialchars($p['category_name'] ?? '—'); ?></td>
                <td><?php echo htmlspecialchars($p['sku'] ?? '—'); ?></td>
                <td>₱<?php echo number_format($p['price'], 2); ?></td>
                <td>
                    <span class="badge <?php echo $p['stock'] <= 5 ? 'badge-danger' : 'badge-success'; ?>">
                        <?php echo $p['stock']; ?>
                    </span>
                </td>
                <td>
                    <button class="btn btn-outline btn-sm" onclick="editProduct(<?php echo $p['id']; ?>, '<?php echo addslashes(htmlspecialchars($p['name'])); ?>', '<?php echo htmlspecialchars($p['category_name'] ?? ''); ?>', '<?php echo htmlspecialchars($p['sku'] ?? ''); ?>', <?php echo $p['price']; ?>, <?php echo $p['stock']; ?>, '<?php echo htmlspecialchars($p['image'] ?? 'placeholder.png'); ?>')">Edit</button>
                    <a href="products.php?delete=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
            <tr><td colspan="7" style="text-align:center; color:#64748b;">No products found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function editProduct(id, name, category, sku, price, stock, image) {
    document.getElementById('editId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editSku').value = sku;
    document.getElementById('editPrice').value = price;
    document.getElementById('editStock').value = stock;

    // Set image preview
    const imagePreview = document.getElementById('editImagePreview');
    const uploadUrl = '../assets/uploads/products/';
    if (image && image !== 'placeholder.png') {
        imagePreview.innerHTML = '<img src="' + uploadUrl + image + '" alt="Current image" style="max-width:100px; max-height:100px; border:1px solid #e2e8f0; border-radius:6px;">';
    } else {
        imagePreview.innerHTML = '<span style="color:#64748b; font-size:12px;">No image (placeholder)</span>';
    }

    // Set category
    const categorySelect = document.getElementById('editCategory');
    for (let i = 0; i < categorySelect.options.length; i++) {
        if (categorySelect.options[i].text === category) {
            categorySelect.selectedIndex = i;
            break;
        }
    }

    document.getElementById('editForm').style.display = 'block';

    // Scroll to form
    document.getElementById('editForm').scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php require_once '../includes/footer.php'; ?>