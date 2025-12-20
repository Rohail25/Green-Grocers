<?php
// Note: database.php, config.php, auth.php, and functions.php are already included in index.php
// Only include if not already included (for direct access testing)
if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/functions.php';
}

$pageTitle = 'Categories - Fresh Vegetables & Packages - Green Grocers';
$allProducts = getAllProducts();
$allPackages = getAllPackages();
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="w-full">
    <!-- Hero Section -->
    <section class="relative px-6 py-20 md:py-28 flex flex-col items-center justify-center overflow-hidden mt-[8rem]" style="background-color: #f5f5f5; background-image: url('<?php echo imagePath('category.jpg'); ?>'); background-size: cover; background-position: center;">
        <div class="absolute inset-0 bg-white/50 backdrop-blur-md"></div>
        <div class="max-w-2xl text-center z-10 relative">
            <h2 class="text-4xl md:text-6xl font-bold text-gray-900 leading-tight" style="font-family: 'Arial', sans-serif; line-height: 1.1;">Fresh Vegetables</h2>
            <p class="text-lg md:text-xl text-gray-700 mt-4" style="font-family: 'Arial', sans-serif;">Choose from a wide variety of fresh, organic vegetables delivered daily</p>
            
            <!-- Search Bar Section -->
            <div class="max-w-2xl w-full z-10 mt-8">
                <div class="flex gap-2 items-center">
                    <div class="relative flex-1">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="text" id="hero-search-input" placeholder="Search for items..." class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-white" style="font-family: 'Arial', sans-serif;" />
                    </div>
                    <button onclick="performHeroSearch()" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium" style="font-family: 'Arial', sans-serif;">Search</button>
                </div>
                
                <!-- Suggested Items -->
                <div class="mt-4 bg-white rounded-lg p-4 shadow-md flex items-center gap-1 flex-wrap" style="font-family: 'Arial', sans-serif;">
                    <p class="text-sm font-bold text-black">Suggested:</p>
                    <?php 
                    $suggestedItems = getSuggestedCategories(); // implement this
                     foreach ($suggestedItems as $item): ?>
                        <a href="<?= BASE_PATH ?>/category?name=<?= urlencode($item) ?>"
                           class="px-4 py-1.5 bg-gray-300 text-black rounded-2xl text-sm hover:bg-green-200 transition">
                            <?= htmlspecialchars($item) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Dynamic Text Section -->
    <?php 
    $dynamicTexts = getActiveDynamicTexts();
    if (!empty($dynamicTexts)): 
    ?>
    <section class="px-6 py-10 bg-white">
        <div class="max-w-6xl mx-auto">
            <?php foreach ($dynamicTexts as $text): ?>
                <div class="mb-8 last:mb-0">
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4 text-center" style="font-family: 'Arial', sans-serif;">
                        <?php echo htmlspecialchars($text['title']); ?>
                    </h2>
                    <div class="text-gray-700 text-lg leading-relaxed text-center" style="font-family: 'Arial', sans-serif;">
                        <?php echo nl2br(htmlspecialchars($text['content'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Available Fresh Vegetables Section -->
    <section class="px-6 py-10 bg-white">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-800 text-center mb-8" style="font-family: 'Arial', sans-serif;">Available Fresh Vegetables</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
            <?php if (count($allProducts) > 0): ?>
                <?php foreach ($allProducts as $product): ?>
                    <?php 
                    $basePrice = $product['retailPrice'] ?? 0;
                    $discountValue = $product['discount']['value'] ?? 0;
                    $discountPrice = $basePrice - ($basePrice * $discountValue / 100);
                    $image = getProductImage($product);
                    $rating = 4;
                    $reviewCount = 2;
                    ?>
                    <div class="bg-white shadow-md rounded-xl overflow-hidden p-4">
                        <div class="relative w-full h-44 flex items-center justify-center bg-gray-100 rounded-lg">
                            <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="object-contain h-full" />
                            <div class="absolute top-2 right-2">
                                <button onclick="toggleWishlist('<?php echo $product['id']; ?>')" class="text-gray-400 hover:text-red-500 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="absolute bottom-2 right-2 flex items-center">
                                <span class="bg-black text-white font-semibold text-xs px-2 py-1 rounded-l-md">FRESH</span>
                                <span class="bg-orange-500 text-white font-semibold text-xs px-2 py-1 rounded-r-md">-<?php echo $product['discount']['value'] ?? 0; ?>%</span>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="font-semibold text-gray-800 text-sm" style="font-family: 'Arial', sans-serif;">
                                <?php echo htmlspecialchars($product['name']); ?> 
                                <span class="text-gray-500">(<?php echo htmlspecialchars($product['itemSize'] ?? ''); ?>)</span>
                            </p>
                            
                            <!-- Rating -->
                            <div class="mt-2 flex items-center gap-1">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $rating): ?>
                                        <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                    <?php else: ?>
                                        <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <span class="text-xs text-gray-600 ml-1">(<?php echo $reviewCount; ?>)</span>
                            </div>
                            
                            <div class="mt-2 flex items-center gap-3">
                                <p class="text-lg font-bold text-gray-800" style="font-family: 'Arial', sans-serif;">$<?php echo number_format($discountPrice, 2); ?></p>
                                <p class="text-sm line-through text-gray-500">$<?php echo number_format($basePrice, 2); ?></p>
                            </div>
                            
                            <!-- Quantity Selector and Cart Button -->
                            <div class="mt-3 flex items-center gap-2">
                                <!-- Quantity Selector -->
                                <div class="flex items-center gap-1 border border-gray-300 rounded">
                                    <button onclick="updateQuantity('<?php echo $product['id']; ?>', -1)" class="w-8 h-8 flex items-center justify-center bg-green-600 text-white hover:bg-green-700 text-sm rounded-l">-</button>
                                    <span id="qty-<?php echo $product['id']; ?>" class="w-8 text-center font-medium text-sm bg-white">1</span>
                                    <button onclick="updateQuantity('<?php echo $product['id']; ?>', 1)" class="w-8 h-8 flex items-center justify-center bg-green-600 text-white hover:bg-green-700 text-sm rounded-r">+</button>
                                </div>
                                
                                <!-- Cart Button -->
                                <form method="POST" action="<?php echo BASE_PATH; ?>/includes/cart-action.php" class="flex-1 add-to-cart-form" data-product-id="<?php echo $product['id']; ?>" data-type="product">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="type" value="product">
                                    <input type="hidden" name="quantity" value="1" id="qty-input-<?php echo $product['id']; ?>">
                                    <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm" style="font-family: 'Arial', sans-serif;">Cart</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-12">
                    <p class="text-gray-500 text-lg">No products available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Don't Miss These Fresh Packages Section -->
    <section class="px-6 py-10 bg-green-50">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-800 text-center mb-8" style="font-family: 'Arial', sans-serif;">Don't Miss These Fresh Packages</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
            <?php if (count($allPackages) > 0): ?>
                <?php foreach ($allPackages as $pkg): ?>
                    <?php 
                    $basePrice = $pkg['retailPrice'] ?? 0;
                    $discountValue = $pkg['discount']['value'] ?? 0;
                    $discountPrice = $basePrice - ($basePrice * $discountValue / 100);
                    $image = !empty($pkg['image']) ? $pkg['image'] : imagePath('package.jpg');
                    $packageDay = strtoupper($pkg['packageDay'] ?? 'MONDAY');
                    $rating = 4;
                    $reviewCount = 4;
                    ?>
                    <div class="bg-white rounded-xl overflow-hidden border border-gray-200 shadow-md" style="font-family: 'Arial', sans-serif;">
                        <div class="relative w-full h-48 flex items-center justify-center bg-gray-100">
                            <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($pkg['name']); ?>" class="object-cover w-full h-full" />
                            <div class="absolute top-2 left-2">
                                <span class="bg-orange-500 text-white font-semibold text-xs px-3 py-1 rounded-md">
                                    <?php echo $packageDay; ?> PACKAGE
                                </span>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-2">
                                <p class="font-semibold text-gray-800 text-sm"><?php echo htmlspecialchars($pkg['name']); ?></p>
                            </div>
                            
                            <!-- Rating -->
                            <div class="flex items-center gap-1 mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $rating): ?>
                                        <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                    <?php else: ?>
                                        <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <span class="text-xs text-gray-600 ml-1">(<?php echo $reviewCount; ?>)</span>
                            </div>
                            
                            <p class="text-xs text-gray-600 mb-3">Includes: Fresh vegetables and fruits...</p>
                            
                            <div class="mt-3 flex items-center gap-3 mb-4">
                                <p class="text-lg font-bold text-black">$<?php echo number_format($discountPrice, 2); ?></p>
                                <p class="text-sm line-through text-gray-500">$<?php echo number_format($basePrice, 2); ?></p>
                            </div>
                            
                            <div class="flex gap-2">
                                <button class="flex-1 border border-green-600 text-green-600 px-4 py-2 rounded-md hover:bg-green-50 transition">Customize</button>
                                <form method="POST" action="<?php echo BASE_PATH; ?>/includes/cart-action.php" class="flex-1 add-to-cart-form" data-product-id="<?php echo $pkg['id']; ?>" data-type="package">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="id" value="<?php echo $pkg['id']; ?>">
                                    <input type="hidden" name="type" value="package">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="w-full bg-orange-500 text-white px-4 py-2 rounded-md hover:bg-orange-600">Buy Now</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-12">
                    <p class="text-gray-500 text-lg">No packages available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- View All Packages Button -->
        <?php if (count($allPackages) > 0): ?>
        <div class="text-center mt-8">
            <button class="px-8 py-3 bg-orange-500 text-white rounded-lg hover:bg-orange-600 text-lg font-medium" style="font-family: 'Arial', sans-serif;">View All Packages</button>
        </div>
        <?php endif; ?>
    </section>
</div>

<script>
function updateQuantity(productId, change) {
    const qtySpan = document.getElementById('qty-' + productId);
    const qtyInput = document.getElementById('qty-input-' + productId);
    let currentQty = parseInt(qtySpan.textContent) || 1;
    currentQty = Math.max(1, currentQty + change);
    qtySpan.textContent = currentQty;
    if (qtyInput) {
        qtyInput.value = currentQty;
    }
}

function toggleWishlist(itemId) {
    console.log('Toggle wishlist for item:', itemId);
}

function performHeroSearch() {
    const searchInput = document.getElementById('hero-search-input');
    const query = searchInput ? searchInput.value.trim() : '';
    if (query) {
        window.location.href = '<?php echo BASE_PATH; ?>/category?search=' + encodeURIComponent(query);
    }
}

function searchSuggestedItem(item) {
    window.location.href = '<?php echo BASE_PATH; ?>/category?search=' + encodeURIComponent(item);
}

// Allow Enter key to trigger search in hero section
document.addEventListener('DOMContentLoaded', function() {
    const heroSearchInput = document.getElementById('hero-search-input');
    if (heroSearchInput) {
        heroSearchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performHeroSearch();
            }
        });
    }
    
    // Handle Add to Cart with AJAX (prevent page refresh)
    const basePath = (typeof BASE_PATH !== 'undefined') ? BASE_PATH : '<?php echo BASE_PATH; ?>';
    const addToCartForms = document.querySelectorAll('.add-to-cart-form');
    
    addToCartForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            formData.append('ajax', '1');
            
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Adding...';
            
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
                    const cartBadge = document.querySelector('.cart-badge');
                    if (cartBadge) {
                        if (data.cart && data.cart.count > 0) {
                            cartBadge.textContent = data.cart.count;
                            cartBadge.style.display = 'flex';
                        } else {
                            cartBadge.style.display = 'none';
                        }
                    }
                    
                    submitButton.textContent = 'Added!';
                    submitButton.classList.add('bg-green-700');
                    setTimeout(() => {
                        submitButton.textContent = originalText;
                        submitButton.classList.remove('bg-green-700');
                    }, 1500);
                    
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

