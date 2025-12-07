<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = 'Green Grocers - Fresh Groceries Delivered Daily';
$categories = getCategories();
$featuredProducts = getFeaturedProducts(6);
$featuredPackages = getFeaturedPackages(6);
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="w-full">
    <!-- Hero Section -->
    <section class="relative bg-green-50 px-6 py-20 md:py-28 flex items-center justify-center overflow-hidden mt-[6rem]">
        <div class="max-w-2xl text-center z-10">
            <h2 class="text-4xl md:text-6xl font-bold text-green-600">Fresh Groceries</h2>
            <h3 class="text-4xl md:text-6xl font-bold text-gray-900 mt-2">Delivered Daily to Your Door</h3>
            <p class="text-gray-600 text-xl md:text-2xl mt-4">Shop smarter with hand-picked daily packages and real-time pricing.</p>
            <div class="flex gap-4 mt-6 justify-center">
                <button class="px-6 py-2 border border-orange-500 text-orange-500 bg-white rounded-lg hover:bg-orange-50">Shop Now</button>
                <button class="px-6 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">Browse Daily Packages</button>
            </div>
        </div>
        <div class="absolute inset-0 pointer-events-none">
            <img src="<?php echo imagePath('random1.png'); ?>" alt="veg" class="hidden md:block absolute top-18 left-40 w-20 md:w-24 h-20 md:h-24 shadow-lg rounded-xl" />
            <img src="<?php echo imagePath('random2.png'); ?>" alt="fruit" class="hidden md:block absolute bottom-16 left-44 w-20 md:w-24 h-20 md:h-24 shadow-lg rounded-xl" />
            <img src="<?php echo imagePath('random3.png'); ?>" alt="milk" class="hidden md:block absolute top-20 right-44 w-20 md:w-24 h-20 md:h-24 shadow-lg rounded-xl" />
            <img src="<?php echo imagePath('random4.png'); ?>" alt="bread" class="hidden md:block absolute bottom-14 right-36 w-20 md:w-24 h-20 md:h-24 shadow-lg rounded-xl" />
        </div>
    </section>

    <!-- Explore Categories -->
    <section class="px-6 py-8 bg-white">
        <!-- Header with Title, Filters, and Navigation -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
            <h3 class="text-2xl md:text-3xl font-bold text-gray-800">Explore Categories</h3>
            
            <!-- Filter Buttons -->
            <div class="flex items-center gap-3 md:gap-4 flex-wrap">
                <button onclick="filterCategory('all')" class="category-filter px-4 py-2 rounded-full text-sm font-medium bg-green-600 text-white hover:bg-green-700 transition" data-filter="all">
                    All
                </button>
                <button onclick="filterCategory('Veges')" class="category-filter px-4 py-2 rounded-full text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition" data-filter="Veges">
                    Veges
                </button>
                <button onclick="filterCategory('Fruit')" class="category-filter px-4 py-2 rounded-full text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition" data-filter="Fruit">
                    Fruits
                </button>
                <button onclick="filterCategory('Juices and Smoothies')" class="category-filter px-4 py-2 rounded-full text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition" data-filter="Juices and Smoothies">
                    Juices & Smoothies
                </button>
                <button onclick="filterCategory('Grocery')" class="category-filter px-4 py-2 rounded-full text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition" data-filter="Grocery">
                    Grocery
                </button>
            </div>
            
            <!-- Navigation Arrows -->
            <div class="flex gap-2 ml-auto">
                <button onclick="scrollCategories('left')" class="p-2 border border-gray-300 rounded-full hover:bg-gray-100 transition">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <button onclick="scrollCategories('right')" class="p-2 border border-gray-300 rounded-full hover:bg-gray-100 transition">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>
        </div>
        
        <!-- Categories Grid -->
        <div id="categories-scroll" class="flex overflow-x-auto gap-8 md:gap-10 lg:gap-12 justify-start pb-4 px-2 scrollbar-hide">
            <?php foreach ($categories as $cat): ?>
                <a href="<?php echo BASE_PATH; ?>/category?name=<?php echo urlencode($cat['title']); ?>" 
                   class="category-item text-center flex-shrink-0 w-28 md:w-32 lg:w-36 cursor-pointer flex flex-col items-center gap-3 group"
                   data-category="<?php echo htmlspecialchars($cat['title']); ?>">
                    <?php 
                    // Use full image path if available, otherwise use basename for backward compatibility
                    $imgPath = !empty($cat['image']) ? imagePath($cat['image']) : imagePath('category.jpg');
                    ?>
                    <!-- Circular Container with Background -->
                    <div class="relative w-24 h-24 md:w-28 md:h-28 rounded-full bg-gray-100 flex items-center justify-center shadow-lg border-2 border-gray-200 group-hover:border-green-500 group-hover:shadow-xl transition-all duration-300">
                        <img src="<?php echo $imgPath; ?>" 
                             alt="<?php echo htmlspecialchars($cat['title']); ?>" 
                             class="w-20 h-20 md:w-24 md:h-24 object-contain rounded-full" />
                    </div>
                    <p class="text-sm font-semibold text-gray-800 group-hover:text-green-600 transition-colors"><?php echo htmlspecialchars($cat['title']); ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="px-6 py-10 bg-gray-50">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold">Featured Products</h3>
            <div class="flex gap-2">
                <button onclick="scrollProducts('left')" class="p-2 border rounded-full hover:bg-gray-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <button onclick="scrollProducts('right')" class="p-2 border rounded-full hover:bg-gray-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>
        </div>
        <div id="products-scroll" class="flex gap-6 overflow-x-auto no-scrollbar">
            <?php if (count($featuredProducts) > 0): ?>
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="flex-shrink-0 w-64">
                        <?php 
                        // retailPrice & itemSize come from new schema
                        $basePrice = $product['retailPrice'] ?? 0;
                        $discountValue = $product['discount']['value'] ?? 0;
                        $discountPrice = $basePrice - ($basePrice * $discountValue / 100);
                        $image = !empty($product['images']) ? $product['images'][0] : imagePath('product.jpg');
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
                                <p class="font-semibold text-gray-800 text-sm">
                                    <?php echo htmlspecialchars($product['name']); ?> 
                                    <span class="text-gray-500">(<?php echo htmlspecialchars($product['itemSize'] ?? ''); ?>)</span>
                                </p>
                                <div class="mt-2 flex items-center gap-3">
                                    <div>
                                        <p class="text-lg font-bold text-black">$<?php echo number_format($discountPrice, 2); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($product['priceUnit'] ?? 'Per Each'); ?></p>
                                    </div>
                                    <p class="text-sm line-through text-gray-500">$<?php echo number_format($basePrice, 2); ?></p>
                                </div>
                                <form method="POST" action="<?php echo BASE_PATH; ?>/includes/cart-action.php" class="mt-4">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="type" value="product">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">Add to Cart</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Daily Packages -->
    <section class="px-6 py-10 bg-white">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold">Daily Packages</h3>
            <div class="flex gap-2">
                <button onclick="scrollPackages('left')" class="p-2 border rounded-full hover:bg-gray-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <button onclick="scrollPackages('right')" class="p-2 border rounded-full hover:bg-gray-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>
        </div>
        <div id="packages-scroll" class="flex gap-6 overflow-x-auto no-scrollbar">
            <?php foreach ($featuredPackages as $pkg): ?>
                <div class="flex-shrink-0 w-72">
                    <?php 
                    $basePrice = $pkg['retailPrice'] ?? 0;
                    $discountValue = $pkg['discount']['value'] ?? 0;
                    $discountPrice = $basePrice - ($basePrice * $discountValue / 100);
                    // Use stored image path if present (already includes BASE_PATH), else fallback
                    $image = !empty($pkg['image']) ? $pkg['image'] : imagePath('package.jpg');
                    ?>
                    <div class="bg-white shadow-md rounded-xl overflow-hidden p-4">
                        <div class="relative w-full h-44 flex items-center justify-center bg-gray-100 rounded-lg">
                            <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($pkg['name']); ?>" class="object-contain h-full" />
                            <div class="absolute top-2 left-2">
                                <span class="bg-green-600 text-white font-semibold text-xs px-3 py-1 rounded-md shadow">
                                    <?php echo htmlspecialchars($pkg['packageDay'] ?? ''); ?> Package
                                </span>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="font-semibold text-gray-800 text-sm"><?php echo htmlspecialchars($pkg['name']); ?></p>
                            <div class="mt-3 flex items-center gap-3">
                                    <p class="text-lg font-bold text-black">$<?php echo number_format($discountPrice, 2); ?></p>
                                    <p class="text-sm line-through text-gray-500">$<?php echo number_format($basePrice, 2); ?></p>
                            </div>
                            <form method="POST" action="<?php echo BASE_PATH; ?>/includes/cart-action.php" class="mt-4">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="id" value="<?php echo $pkg['id']; ?>">
                                <input type="hidden" name="type" value="package">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">Add to Cart</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Promotion Section -->
    <section class="w-full py-12">
        <div class="relative lg:px-20">
            <div class="flex gap-6 overflow-x-auto scrollbar-hide lg:grid lg:grid-cols-4 lg:gap-8 lg:overflow-visible px-6 lg:px-0">
                <?php 
                $features = [
                    ['title' => 'Browse Products', 'desc' => 'Find fresh groceries and daily packages', 'img' => imagePath('Search.png')],
                    ['title' => 'Add to Cart', 'desc' => 'Choose what you need and adjust quantities', 'img' => imagePath('Cart.png')],
                    ['title' => 'Checkout', 'desc' => 'Enter details and pay securely', 'img' => imagePath('Checkout.png')],
                    ['title' => 'Receive Order', 'desc' => 'Fast, fresh delivery at your door', 'img' => imagePath('Order.png')]
                ];
                foreach ($features as $feature): ?>
                    <div class="bg-white rounded-xl shadow-lg p-6 text-center flex-shrink-0 w-64 lg:w-auto flex flex-col items-center">
                        <img src="<?php echo htmlspecialchars($feature['img']); ?>" alt="<?php echo htmlspecialchars($feature['title']); ?>" class="w-20 h-20 mb-4" />
                        <h3 class="text-lg font-bold mb-2"><?php echo htmlspecialchars($feature['title']); ?></h3>
                        <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($feature['desc']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="relative mt-20 bg-green-600 px-6 lg:px-10 flex flex-col lg:flex-row items-center justify-between overflow-visible">
            <div class="text-white max-w-lg z-10 py-12 text-center lg:text-left">
                <h2 class="text-3xl font-bold mb-2">Join Our Newsletter</h2>
                <p class="mb-6">Signup for deals, new products and promotions</p>
                <div class="flex bg-white rounded-md overflow-hidden shadow-md max-w-md mx-auto lg:mx-0">
                    <div class="flex items-center px-3 text-gray-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                    <input type="email" placeholder="Email address" class="flex-1 px-3 py-3 text-gray-700 focus:outline-none" />
                    <button class="bg-green-700 text-white px-6 font-semibold hover:bg-green-800">Sign Up</button>
                </div>
            </div>
            <div class="absolute right-6 lg:right-10 top-1/2 -translate-y-1/2">
                <img src="<?php echo imagePath('Hexa.png'); ?>" alt="Hexagon" class="w-[22rem] h-[22rem] lg:w-[22rem] lg:h-[22rem] object-contain" />
            </div>
        </div>
    </section>
</div>

<style>
.scrollbar-hide {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;  /* Chrome, Safari and Opera */
}
</style>

<script>
function scrollCategories(direction) {
    const el = document.getElementById('categories-scroll');
    el.scrollBy({ left: direction === 'left' ? -300 : 300, behavior: 'smooth' });
}

function filterCategory(categoryName) {
    // Update filter button styles
    document.querySelectorAll('.category-filter').forEach(btn => {
        btn.classList.remove('bg-green-600', 'text-white');
        btn.classList.add('bg-gray-100', 'text-gray-600');
    });
    
    // Highlight selected filter
    const selectedBtn = document.querySelector(`[data-filter="${categoryName}"]`);
    if (selectedBtn) {
        selectedBtn.classList.remove('bg-gray-100', 'text-gray-600');
        selectedBtn.classList.add('bg-green-600', 'text-white');
    }
    
    // Filter categories (if categoryName is 'all', show all)
    const categoryItems = document.querySelectorAll('.category-item');
    categoryItems.forEach(item => {
        if (categoryName === 'all') {
            item.style.display = 'flex';
        } else {
            const itemCategory = item.getAttribute('data-category');
            if (itemCategory === categoryName) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        }
    });
}

function scrollProducts(direction) {
    const el = document.getElementById('products-scroll');
    el.scrollBy({ left: direction === 'left' ? -300 : 300, behavior: 'smooth' });
}
function scrollPackages(direction) {
    const el = document.getElementById('packages-scroll');
    el.scrollBy({ left: direction === 'left' ? -300 : 300, behavior: 'smooth' });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

