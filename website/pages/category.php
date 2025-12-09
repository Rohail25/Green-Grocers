<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$categoryName = $_GET['name'] ?? 'Vegetables';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// If search query is provided, search products; otherwise get by category
if (!empty($searchQuery)) {
    $products = searchProducts($searchQuery);
    $pageTitle = 'Search Results for "' . htmlspecialchars($searchQuery) . '" - Green Grocers';
} else {
    $products = getProductsByCategory($categoryName);
}

// Debug: Log search query and results count (remove in production)
// error_log("Search Query: " . $searchQuery . " | Results: " . count($products));

$packages = getFeaturedPackages(2);

$categoryData = [
    'Vegetables' => ['heading' => 'Fresh Vegetables', 'description' => 'Discover hand-picked, farm-fresh vegetables delivered daily.'],
    'Fruits' => ['heading' => 'Fresh Fruits', 'description' => 'Juicy and sweet seasonal fruits, delivered to your door.'],
    'Dairy' => ['heading' => 'Fresh Dairy Products', 'description' => 'Quality dairy essentials straight from trusted farms.'],
    'Meat' => ['heading' => 'Fresh Meat', 'description' => 'Premium cuts, hygienically packed and delivered fresh.'],
    'Beverages' => ['heading' => 'Refreshing Beverages', 'description' => 'Stay refreshed with our curated beverage collection.']
];

$category = $categoryData[$categoryName] ?? $categoryData['Vegetables'];
if (empty($searchQuery)) {
    $pageTitle = $category['heading'] . ' - Green Grocers';
}
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="w-full">
    <?php if (empty($searchQuery)): ?>
    <section class="relative w-full py-20 bg-cover bg-center text-center text-white mt-[6rem]" style="background-image: url('<?php echo imagePath('category.jpg'); ?>')">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="relative z-10 max-w-3xl mx-auto px-6 py-20 md:py-28">
            <h1 class="text-4xl md:text-6xl font-bold text-gray-700"><?php echo htmlspecialchars($category['heading']); ?></h1>
            <p class="mt-4 text-xl md:text-2xl text-gray-700 font-semibold"><?php echo htmlspecialchars($category['description']); ?></p>
        </div>
    </section>
    <?php else: ?>
    <section class="relative w-full py-12 bg-green-50 mt-[6rem]">
        <div class="max-w-3xl mx-auto px-6 text-center">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800">Search Results</h1>
            <p class="mt-2 text-lg text-gray-600">Found <?php echo count($products); ?> product(s) for "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>"</p>
        </div>
    </section>
    <?php endif; ?>

    <section class="px-6 py-12">
        <h2 class="text-5xl font-bold mb-6 flex justify-center">
            <?php if (!empty($searchQuery)): ?>
                Search Results
            <?php else: ?>
                Available <?php echo htmlspecialchars($category['heading']); ?>
            <?php endif; ?>
        </h2>
        
        <?php if (empty($products)): ?>
            <div class="text-center py-12">
                <p class="text-gray-500 text-lg">No products found<?php echo !empty($searchQuery) ? ' for "' . htmlspecialchars($searchQuery) . '"' : ''; ?>.</p>
                <a href="<?php echo BASE_PATH; ?>/" class="mt-4 inline-block px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Continue Shopping</a>
            </div>
        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($products as $product): ?>
                <?php 
                // Handle both snake_case and camelCase field names
                $basePrice = $product['retailPrice'] ?? $product['retail_price'] ?? 0;
                $itemSize = $product['itemSize'] ?? $product['item_size'] ?? '';
                $discountValue = $product['discount']['value'] ?? 0;
                $discountPrice = $basePrice - ($basePrice * $discountValue / 100);
                $image = getProductImage($product);
                ?>
                <div class="bg-white shadow-md rounded-xl overflow-hidden p-4">
                    <div class="relative w-full h-44 flex items-center justify-center bg-gray-100 rounded-lg">
                        <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="object-contain h-full" />
                        <div class="absolute bottom-2 right-2 flex items-center">
                            <span class="bg-white text-green-700 font-semibold text-xs px-2 py-1 rounded-l-md shadow">FRESH</span>
                            <span class="bg-orange-500 text-white font-semibold text-xs px-2 py-1 rounded-r-md shadow">-<?php echo $product['discount']['value'] ?? 0; ?>%</span>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="font-semibold text-gray-800 text-sm"><?php echo htmlspecialchars($product['name']); ?> <span class="text-gray-500">(<?php echo htmlspecialchars($itemSize); ?>)</span></p>
                        <div class="mt-2 flex items-center gap-3">
                            <div>
                                <p class="text-lg font-bold text-black">$<?php echo number_format($discountPrice, 2); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($product['priceUnit'] ?? 'Per Each'); ?></p>
                            </div>
                            <p class="text-sm line-through text-gray-500">$<?php echo number_format($basePrice, 2); ?></p>
                        </div>
                        <form method="POST" action="<?php echo BASE_PATH; ?>/includes/cart-action.php" class="mt-4 add-to-cart-form" data-product-id="<?php echo $product['id']; ?>" data-type="product">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="type" value="product">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">Add to Cart</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
</div>

<script>
// Handle Add to Cart with AJAX (prevent page refresh)
document.addEventListener('DOMContentLoaded', function() {
    // Use global BASE_PATH if available (from cart-modal.php), otherwise use PHP value
    const basePath = (typeof BASE_PATH !== 'undefined') ? BASE_PATH : '<?php echo BASE_PATH; ?>';
    const addToCartForms = document.querySelectorAll('.add-to-cart-form');
    
    addToCartForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission
            
            const formData = new FormData(form);
            formData.append('ajax', '1'); // Ensure AJAX detection
            
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Adding...';
            
            // Store scroll position
            const scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
            
            fetch(basePath + '/includes/cart-action.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count badge in header
                    const cartBadge = document.querySelector('.cart-badge');
                    if (cartBadge) {
                        if (data.cart && data.cart.count > 0) {
                            cartBadge.textContent = data.cart.count;
                            cartBadge.style.display = 'flex';
                        } else {
                            cartBadge.style.display = 'none';
                        }
                    }
                    
                    // Show success feedback
                    submitButton.textContent = 'Added!';
                    submitButton.classList.add('bg-green-700');
                    setTimeout(() => {
                        submitButton.textContent = originalText;
                        submitButton.classList.remove('bg-green-700');
                    }, 1500);
                    
                    // Restore scroll position
                    window.scrollTo(0, scrollPosition);
                } else {
                    alert(data.message || 'Failed to add item to cart');
                    submitButton.textContent = originalText;
                }
                submitButton.disabled = false;
            })
            .catch(error => {
                console.error('Error adding to cart:', error);
                alert('An error occurred while adding the item to cart');
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

