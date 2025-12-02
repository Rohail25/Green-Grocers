<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAuth('admin');
$pageTitle = 'Add New Product';

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $categoryName = $_POST['category'] ?? '';
    $itemSize = $_POST['item_size'] ?? '';
    $description = $_POST['description'] ?? '';
    $discountValue = floatval($_POST['discount_value'] ?? 0);
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Get category ID
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id FROM categories WHERE title = :title");
    $stmt->execute([':title' => $categoryName]);
    $category = $stmt->fetch();
    
    if (!$category) {
        $error = 'Category not found';
    } elseif (empty($name) || $price <= 0) {
        $error = 'Please fill in all required fields';
    } else {
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
        
        // Insert product (new schema: categoryId, retailPrice, status, isFeatured, discount JSON)
        $images = $imagePath ? json_encode([$imagePath]) : json_encode([]);
        $discount = json_encode(['type' => 'percentage', 'value' => $discountValue]);
        $stmt = $conn->prepare("INSERT INTO products (name, categoryId, retailPrice, totalQuantityInStock, itemSize, description, discount, isFeatured, images, status) 
                                VALUES (:name, :categoryId, :price, :stock, :itemSize, :description, :discount, :isFeatured, :images, 'active')");
        
        $executed = $stmt->execute([
            ':name'       => $name,
            ':categoryId' => $category['id'],
            ':price'      => $price,
            ':stock'      => $stock,
            ':itemSize'   => $itemSize,
            ':description'=> $description,
            ':discount'   => $discount,
            ':isFeatured' => $isFeatured ? 1 : 0,
            ':images'     => $images,
        ]);
        
        if ($executed) {
            $success = 'Product added successfully!';
            header('Location: products.php?success=1');
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
        <a href="products.php" class="text-gray-600 hover:text-gray-800">← Back to Products</a>
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

        <!-- Price and Stock -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block mb-2 font-semibold">Price ($) <span class="text-red-500">*</span></label>
                <input type="number" name="price" step="0.01" required min="0" class="w-full border px-4 py-3 rounded-md" placeholder="0.00" value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
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
            <a href="products.php" class="px-6 py-2 rounded-md border border-gray-300 hover:bg-gray-100">
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

