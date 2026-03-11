<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$categoryName = 'Fruit';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$pageTitle = 'Fresh Fruits - Green Grocers';

// Get products for this category
if (!empty($searchQuery)) {
    // Search only within Fruit category
    $products = searchProductsByCategory($searchQuery, $categoryName);
} else {
    // Get all fruits
    $products = getProductsByCategory($categoryName);
}

$categoryData = [
    'heading' => 'Fresh Fruits',
    'description' => 'Juicy and sweet seasonal fruits, delivered to your door.'
];
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="w-full">
    <?php if (empty($searchQuery)): ?>
    <!-- <section class="relative w-full py-20 bg-cover bg-center text-center text-white mt-[6rem]" style="background-image: url('<?php echo imagePath('category.jpg'); ?>')">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="relative z-10 max-w-3xl mx-auto px-6 py-20 md:py-28">
            <h1 class="text-4xl md:text-6xl font-bold text-gray-700"><?php echo htmlspecialchars($categoryData['heading']); ?></h1>
            <p class="mt-4 text-xl md:text-2xl text-gray-700 font-semibold"><?php echo htmlspecialchars($categoryData['description']); ?></p>
        </div>
    </section> -->
    <?php else: ?>
    <section class="relative w-full py-12 bg-green-50 mt-[6rem]">
        <div class="max-w-3xl mx-auto px-6 text-center">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800">Search Results in Fruits</h1>
            <p class="mt-2 text-lg text-gray-600">Found <?php echo count($products); ?> product(s) for "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>"</p>
        </div>
    </section>
    <?php endif; ?>

    <!-- Category-Specific Search Section -->
    <section class="px-6 py-8 bg-white border-b border-gray-200 mt-[5rem]">
        <div class="max-w-4xl mx-auto">
            <div class="flex gap-2 items-center">
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" id="fruit-search-input" placeholder="Search only in Fruits..." class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-500" style="font-family: 'Arial', sans-serif;" />
                </div>
                <button onclick="performFruitSearch()" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium" style="font-family: 'Arial', sans-serif;">Search</button>
            </div>
            
            <!-- Dropdown for search suggestions (only Fruit category) -->
            <div id="fruit-search-suggestions" class="mt-2 bg-white border border-gray-300 rounded-lg shadow-lg max-h-96 overflow-y-auto hidden"></div>
        </div>
    </section>

    <section class="px-6 py-12">
        <h2 class="text-5xl font-bold mb-6 flex justify-center">
            <?php if (!empty($searchQuery)): ?>
                Search Results
            <?php else: ?>
                Available Fruits
            <?php endif; ?>
        </h2>
        
        <?php if (empty($products)): ?>
            <div class="text-center py-12">
                <p class="text-gray-500 text-lg">No fruits found<?php echo !empty($searchQuery) ? ' for "' . htmlspecialchars($searchQuery) . '"' : ''; ?>.</p>
                <a href="<?php echo BASE_PATH; ?>/" class="mt-4 inline-block px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Continue Shopping</a>
            </div>
        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($products as $product): ?>
                <?php 
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
        </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

<style>
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>

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

// Category-specific search with autocomplete
let fruitSearchDebounce;
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('fruit-search-input');
    const suggestionDropdown = document.getElementById('fruit-search-suggestions');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(fruitSearchDebounce);
            const query = this.value.trim();
            
            if (query.length < 2) {
                suggestionDropdown.classList.add('hidden');
                return;
            }
            
            fruitSearchDebounce = setTimeout(() => {
                // Fetch suggestions for Fruit category only
                fetch('<?php echo BASE_PATH; ?>/includes/search-suggestions.php?q=' + encodeURIComponent(query) + '&category=Fruit&limit=8')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.items.length > 0) {
                            suggestionDropdown.innerHTML = '';
                            data.items.forEach(item => {
                                const button = document.createElement('button');
                                button.type = 'button';
                                button.className = 'w-full text-left px-4 py-2 hover:bg-green-100 border-b border-gray-100 last:border-b-0';
                                button.textContent = item.name + ' (' + item.type + ')';
                                button.addEventListener('mousedown', function(e) {
                                    e.preventDefault();
                                    searchInput.value = item.name;
                                    performFruitSearch();
                                });
                                suggestionDropdown.appendChild(button);
                            });
                            suggestionDropdown.classList.remove('hidden');
                        } else {
                            suggestionDropdown.classList.add('hidden');
                        }
                    });
            }, 180);
        });
        
        // Hide dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#fruit-search-input') && !e.target.closest('#fruit-search-suggestions')) {
                suggestionDropdown.classList.add('hidden');
            }
        });
    }
});

function performFruitSearch() {
    const searchInput = document.getElementById('fruit-search-input');
    const query = searchInput ? searchInput.value.trim() : '';
    if (query) {
        window.location.href = '<?php echo BASE_PATH; ?>/fruit?search=' + encodeURIComponent(query);
    }
}

// Allow Enter key to trigger search
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('fruit-search-input');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performFruitSearch();
            }
        });
    }
});

// Handle Add to Cart with AJAX
document.addEventListener('DOMContentLoaded', function() {
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
                headers: {'X-Requested-With': 'XMLHttpRequest'},
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
