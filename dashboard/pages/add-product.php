<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Allow both admin and vendor to access
requireAuth();
$currentUser = getCurrentUser();
if (!in_array($currentUser['role'], ['admin', 'vendor'])) {
    header('Location: ' . BASE_PATH . '/website/pages/dashboard.php');
    exit;
}
$pageTitle = 'Add New Product';

$error = '';
$success = '';

// Simple UUIDv4 generator for product IDs (matches VARCHAR(36) schema)
function generateProductId(): string {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // version 4
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variant

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $categoryName = $_POST['category'] ?? '';
    $itemSize = $_POST['item_size'] ?? '';
    $priceUnit = $_POST['price_unit'] ?? '';
    $description = $_POST['description'] ?? '';
    $discountValue = floatval($_POST['discount_value'] ?? 0);
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Get category ID
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id FROM categories WHERE title = :title");
    $stmt->execute([':title' => $categoryName]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        $error = 'Category not found';
    } elseif (empty($name) || $price <= 0 || empty($priceUnit)) {
        $error = 'Please fill in all required fields including price unit';
    } else {
        // Generate a UUID-like product ID to match Node.js schema
        $productId = generateProductId();

        // Handle image upload
        $imagePath = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/products/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $imagePath = BASE_PATH . '/public/uploads/products/' . $fileName;
            }
        }
        
        // Insert product (new schema: id, categoryId, retailPrice, status, isFeatured, discount JSON)
        $images = $imagePath ? json_encode([$imagePath]) : json_encode([]);
        $discount = json_encode(['type' => 'percentage', 'value' => $discountValue]);
        
        // Check if priceUnit column exists, if not, we'll store it in itemSize or add it later
        // For now, we'll try to insert it and handle gracefully if column doesn't exist
        try {
            $stmt = $conn->prepare("INSERT INTO products (id, name, categoryId, retailPrice, totalQuantityInStock, itemSize, priceUnit, description, discount, isFeatured, images, status) 
                                    VALUES (:id, :name, :categoryId, :price, :stock, :itemSize, :priceUnit, :description, :discount, :isFeatured, :images, 'active')");
            
            $executed = $stmt->execute([
                ':id'         => $productId,
                ':name'       => $name,
                ':categoryId' => $category['id'],
                ':price'      => $price,
                ':stock'      => $stock,
                ':itemSize'   => $itemSize,
                ':priceUnit'  => $priceUnit,
                ':description'=> $description,
                ':discount'   => $discount,
                ':isFeatured' => $isFeatured ? 1 : 0,
                ':images'     => $images,
            ]);
        } catch (PDOException $e) {
            // If priceUnit column doesn't exist, insert without it (will need to add column via migration)
            if (strpos($e->getMessage(), 'priceUnit') !== false || strpos($e->getMessage(), 'Unknown column') !== false) {
                $stmt = $conn->prepare("INSERT INTO products (id, name, categoryId, retailPrice, totalQuantityInStock, itemSize, description, discount, isFeatured, images, status) 
                                        VALUES (:id, :name, :categoryId, :price, :stock, :itemSize, :description, :discount, :isFeatured, :images, 'active')");
                
                $executed = $stmt->execute([
                    ':id'         => $productId,
                    ':name'       => $name,
                    ':categoryId' => $category['id'],
                    ':price'      => $price,
                    ':stock'      => $stock,
                    ':itemSize'   => $itemSize . ($priceUnit ? ' (' . $priceUnit . ')' : ''),
                    ':description'=> $description,
                    ':discount'   => $discount,
                    ':isFeatured' => $isFeatured ? 1 : 0,
                    ':images'     => $images,
                ]);
            } else {
                throw $e;
            }
        }
        
        if ($executed) {
            $success = 'Product added successfully!';
            header('Location: ' . BASE_PATH . '/dashboard/pages/products.php?success=1');
            exit;
        } else {
            $error = 'Failed to add product';
        }
    }
}

$categories = getCategories();
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-3xl font-bold text-gray-800">Add New Product</h2>
        <a href="<?php echo BASE_PATH; ?>/dashboard/pages/products.php" class="text-gray-600 hover:text-gray-800">← Back to Products</a>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-sm p-6 space-y-6">
        <!-- Image Upload -->
        <div>
            <label class="block mb-2 font-semibold">Product Image</label>
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                <input type="file" name="image" id="imageUpload" accept="image/*" class="hidden" onchange="previewImage(this)">
                <label for="imageUpload" class="cursor-pointer">
                    <div id="imagePreview" class="mb-4">
                        <p class="text-gray-500">Click to upload or drag and drop</p>
                    </div>
                    <button type="button" class="px-4 py-2 bg-gray-100 rounded-md hover:bg-gray-200">
                        Upload Image
                    </button>
                </label>
            </div>
        </div>

        <!-- Product Name -->
        <div>
            <label class="block mb-2 font-semibold">Product Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" required class="w-full border px-4 py-3 rounded-md" placeholder="Enter product name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
        </div>

        <!-- Category -->
        <div>
            <label class="block mb-2 font-semibold">Category <span class="text-red-500">*</span></label>
            <select name="category" required class="w-full border px-4 py-3 rounded-md">
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['title']); ?>" <?php echo (($_POST['category'] ?? '') === $cat['title']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Price, Price Unit and Stock -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block mb-2 font-semibold">Price ($) <span class="text-red-500">*</span></label>
                <input type="number" name="price" step="0.01" required min="0" class="w-full border px-4 py-3 rounded-md" placeholder="0.00" value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
            </div>
            <div>
                <label class="block mb-2 font-semibold">Price Unit <span class="text-red-500">*</span></label>
                <select name="price_unit" required class="w-full border px-4 py-3 rounded-md">
                    <option value="">Select Unit</option>
                    <option value="Per KG" <?php echo (($_POST['price_unit'] ?? '') === 'Per KG') ? 'selected' : ''; ?>>Per KG</option>
                    <option value="Per Each" <?php echo (($_POST['price_unit'] ?? '') === 'Per Each') ? 'selected' : ''; ?>>Per Each</option>
                    <option value="Per Dozen" <?php echo (($_POST['price_unit'] ?? '') === 'Per Dozen') ? 'selected' : ''; ?>>Per Dozen</option>
                    <option value="Per Pack" <?php echo (($_POST['price_unit'] ?? '') === 'Per Pack') ? 'selected' : ''; ?>>Per Pack</option>
                    <option value="Per Liter" <?php echo (($_POST['price_unit'] ?? '') === 'Per Liter') ? 'selected' : ''; ?>>Per Liter</option>
                    <option value="Per Piece" <?php echo (($_POST['price_unit'] ?? '') === 'Per Piece') ? 'selected' : ''; ?>>Per Piece</option>
                </select>
            </div>
            <div>
                <label class="block mb-2 font-semibold">Stock Quantity <span class="text-red-500">*</span></label>
                <input type="number" name="stock" required min="0" class="w-full border px-4 py-3 rounded-md" placeholder="0" value="<?php echo htmlspecialchars($_POST['stock'] ?? ''); ?>">
            </div>
        </div>

        <!-- Item Size and Discount -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block mb-2 font-semibold">Item Size</label>
                <input type="text" name="item_size" class="w-full border px-4 py-3 rounded-md" placeholder="e.g., 1kg, 500g" value="<?php echo htmlspecialchars($_POST['item_size'] ?? ''); ?>">
            </div>
            <div>
                <label class="block mb-2 font-semibold">Discount (%)</label>
                <input type="number" name="discount_value" step="0.01" min="0" max="100" class="w-full border px-4 py-3 rounded-md" placeholder="0" value="<?php echo htmlspecialchars($_POST['discount_value'] ?? '0'); ?>">
            </div>
        </div>

        <!-- Description -->
        <div>
            <label class="block mb-2 font-semibold">Description</label>
            <textarea name="description" rows="4" class="w-full border px-4 py-3 rounded-md" placeholder="Product description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>

        <!-- Featured -->
        <div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_featured" value="1" <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>>
                <span class="font-semibold">Mark as Featured Product</span>
            </label>
        </div>

        <!-- Buttons -->
        <div class="flex justify-center gap-4 pt-4">
            <a href="<?php echo BASE_PATH; ?>/dashboard/pages/products.php" class="px-6 py-2 rounded-md border border-gray-300 hover:bg-gray-100">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 rounded-md bg-green-600 text-white hover:bg-green-700">
                Save Product
            </button>
        </div>
    </form>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" class="max-w-full max-h-48 mx-auto rounded">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

