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
$pageTitle = 'Create Package';

$error = '';
$success = '';

// Simple UUIDv4 generator for package IDs (matches VARCHAR(36) schema)
function generatePackageId(): string {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // version 4
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variant

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['package_name'] ?? '';
    $packageDay = $_POST['assigned_day'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $discountValue = floatval($_POST['discount_value'] ?? 0);
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $items = $_POST['items'] ?? [];
    
    if (empty($name) || $price <= 0) {
        $error = 'Please fill in all required fields';
    } else {
        $conn = getDBConnection();
        // Generate UUID for package id
        $packageId = generatePackageId();

        // Handle image upload
        $imagePath = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/packages/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $imagePath = BASE_PATH . '/public/uploads/packages/' . $fileName;
            }
        }
        
        // Build items JSON for new schema
        $itemsPayload = [];
        if (!empty($items) && is_array($items)) {
            foreach ($items as $item) {
                if (!empty($item['name'])) {
                    $itemsPayload[] = [
                        'name'     => $item['name'],
                        'quantity' => $item['quantity'] ?? '',
                    ];
                }
            }
        }

        $itemsJson = json_encode($itemsPayload);
        $discountJson = json_encode(['type' => 'percentage', 'value' => $discountValue]);

        // Insert package (new schema fields, including id)
        $stmt = $conn->prepare("INSERT INTO packages (id, name, packageDay, retailPrice, discount, isFeatured, image, items, status) 
                                VALUES (:id, :name, :packageDay, :price, :discount, :isFeatured, :image, :items, 'active')");
        $executed = $stmt->execute([
            ':id'         => $packageId,
            ':name'       => $name,
            ':packageDay' => $packageDay,
            ':price'      => $price,
            ':discount'   => $discountJson,
            ':isFeatured' => $isFeatured ? 1 : 0,
            ':image'      => $imagePath,
            ':items'      => $itemsJson,
        ]);
        
        if ($executed) {
            $success = 'Package created successfully!';
            header('Location: packages.php?success=1');
            exit;
        } else {
            $error = 'Failed to create package';
        }
    }
}

$products = getAllProducts();
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-3xl font-bold text-gray-800">Create Package</h2>
        <a href="<?php echo BASE_PATH; ?>/dashboard/pages/packages.php" class="text-gray-600 hover:text-gray-800">← Back to Packages</a>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-sm p-6 space-y-6" id="packageForm">
        <!-- Package Name -->
        <div>
            <label class="block mb-2 font-semibold">Package Name <span class="text-red-500">*</span></label>
            <input type="text" name="package_name" required class="w-full border px-4 py-3 rounded-md" placeholder="Enter package name" value="<?php echo htmlspecialchars($_POST['package_name'] ?? ''); ?>">
        </div>

        <!-- Assigned Day and Price -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block mb-2 font-semibold">Assigned Day</label>
                <input type="text" name="assigned_day" class="w-full border px-4 py-3 rounded-md" placeholder="e.g., Monday" value="<?php echo htmlspecialchars($_POST['assigned_day'] ?? ''); ?>">
            </div>
            <div>
                <label class="block mb-2 font-semibold">Price ($) <span class="text-red-500">*</span></label>
                <input type="number" name="price" step="0.01" required min="0" class="w-full border px-4 py-3 rounded-md" placeholder="0.00" value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
            </div>
        </div>

        <!-- Discount -->
        <div>
            <label class="block mb-2 font-semibold">Discount (%)</label>
            <input type="number" name="discount_value" step="0.01" min="0" max="100" class="w-full border px-4 py-3 rounded-md" placeholder="0" value="<?php echo htmlspecialchars($_POST['discount_value'] ?? '0'); ?>">
        </div>

        <!-- Image Upload -->
        <div>
            <label class="block mb-2 font-semibold">Package Image</label>
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

        <!-- Package Items -->
        <div>
            <h3 class="font-semibold mb-3">Package Items</h3>
            <div id="packageItems" class="space-y-4">
                <!-- Items will be added here dynamically -->
            </div>
            <button type="button" onclick="addItem()" class="mt-4 w-full py-3 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:bg-gray-100">
                + Add Product to Package
            </button>
        </div>

        <!-- Featured -->
        <div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_featured" value="1" <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>>
                <span class="font-semibold">Mark as Featured Package</span>
            </label>
        </div>

        <!-- Buttons -->
        <div class="flex justify-center gap-4 pt-4">
            <a href="packages.php" class="px-6 py-2 rounded-md border border-gray-300 hover:bg-gray-100">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 rounded-md bg-green-600 text-white hover:bg-green-700">
                Save Package
            </button>
        </div>
    </form>
</div>

<script>
let itemCount = 0;

function addItem() {
    const container = document.getElementById('packageItems');
    const itemDiv = document.createElement('div');
    itemDiv.className = 'flex items-start gap-4 bg-gray-50 rounded-lg p-4 border';
    itemDiv.innerHTML = `
        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block mb-1 text-sm font-semibold">Product Name</label>
                <input type="text" name="items[${itemCount}][name]" required class="w-full border px-3 py-2 rounded-md" placeholder="Product name">
            </div>
            <div>
                <label class="block mb-1 text-sm font-semibold">Quantity</label>
                <input type="text" name="items[${itemCount}][quantity]" class="w-full border px-3 py-2 rounded-md" placeholder="e.g., 1kg, 2 pieces">
            </div>
        </div>
        <button type="button" onclick="removeItem(this)" class="text-red-500 hover:text-red-700 mt-6">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    `;
    container.appendChild(itemDiv);
    itemCount++;
}

function removeItem(button) {
    button.parentElement.remove();
}

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

// Add one item by default
addItem();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

