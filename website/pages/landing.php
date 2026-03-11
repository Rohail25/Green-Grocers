<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = 'Green Grocers - Fresh Groceries Delivered Daily';
$categories = getCategories();
$featuredProducts = getFeaturedProducts(6);

// Get dynamic daily packages instead of hardcoded logic
$packagesByDay = getDailyPackages();
$weekDays = array_keys($packagesByDay);  // ['Monday', 'Tuesday', ... 'Sunday']
$allDailyPackages = [];

foreach ($packagesByDay as $dayName => $dayPackages) {
    foreach ($dayPackages as $package) {
        $package['assignedDay'] = $dayName;
        $allDailyPackages[] = $package;
    }
}

function getTopCategories(int $limit = 8): array {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT title FROM categories ORDER BY title ASC LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'title');
}


?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="w-full">
   
<div class="h-[80px] w-full"></div>
    <!-- Dynamic Textection -->
    <?php 
    $dynamicTexts = getActiveDynamicTexts();
    if (!empty($dynamicTexts)): 
    ?>
    <!-- <section class="px-6 py-10 bg-white mt-3">
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
    </section> -->
    <?php endif; ?>

    <!-- Explore Categories -->
    <section class="px-6 py-8 bg-white">
        <!-- Header with Title, Filters, and Navigation -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
            <h3 class="text-2xl md:text-3xl font-bold text-gray-800" style="font-family: 'Arial', sans-serif;">Explore Categories</h3>
            
            <!-- Filter Buttons -->
            <div class="flex items-center gap-3 md:gap-4 flex-wrap">
                <button onclick="filterCategory('all')" class="category-filter px-4 py-2 rounded-full text-sm font-medium bg-green-600 text-white hover:bg-green-700 transition" data-filter="all" style="font-family: 'Arial', sans-serif;">
                    All
                </button>
                <button onclick="filterCategory('Vegetable')" class="category-filter px-4 py-2 rounded-full text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition" data-filter="Vegetable" style="font-family: 'Arial', sans-serif;">
                    Vegetables
                </button>
                <button onclick="filterCategory('Fruit')" class="category-filter px-4 py-2 rounded-full text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition" data-filter="Fruit" style="font-family: 'Arial', sans-serif;">
                    Fruits
                </button>
                <button onclick="filterCategory('Juices and Smoothies')" class="category-filter px-4 py-2 rounded-full text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition" data-filter="Juices and Smoothies" style="font-family: 'Arial', sans-serif;">
                    Coffe & teas
                </button>
                <button onclick="filterCategory('Global Pantry')" class="category-filter px-4 py-2 rounded-full text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition" data-filter="Global Pantry" style="font-family: 'Arial', sans-serif;">
                    Global Pantry
                </button>
            </div>
            
        </div>
        
        <!-- Categories Grid -->
        <div class="relative px-10 md:px-14">
            <button onclick="scrollCategories('left')" class="absolute left-0 top-1/2 -translate-y-1/2 z-10 p-2 border border-gray-300 rounded-full bg-white hover:bg-gray-100 transition">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </button>
            <button onclick="scrollCategories('right')" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 p-2 border border-gray-300 rounded-full bg-white hover:bg-gray-100 transition">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </button>

            <div id="categories-scroll" class="flex overflow-x-auto gap-8 md:gap-10 lg:gap-12 justify-center pb-4 px-2 scrollbar-hide">
            <?php 
            // Map category titles to specific icons/images and colors
            $categoryIcons = [
                'Vegetable' => 'vege.png',
                'Vegetables' => 'vege.png',
                'Fruit' => 'fruits.png',
                'Fruits' => 'fruits.png',
                'Beverages' => 'beverages.png',
                'Juices and Smoothies' => 'beverages.png',
                'Dairy' => 'dairy.png',
                'Grocery' => 'grocery.png',
                'Groceries' => 'grocery.png',
                'Global Pantry' => 'grocery.png',
                'Bakery' => 'bakery.png',
                'Frozen Item' => 'bakery.png'
            ];
            
            $categoryColors = [
                'Vegetable' => 'bg-orange-100',
                'Vegetables' => 'bg-orange-100',
                'Fruit' => 'bg-pink-100',
                'Fruits' => 'bg-pink-100',
                'Beverages' => 'bg-gray-100',
                'Juices and Smoothies' => 'bg-gray-100',
                'Dairy' => 'bg-yellow-100',
                'Grocery' => 'bg-amber-100',
                'Groceries' => 'bg-amber-100',
                'Global Pantry' => 'bg-purple-100',
                'Bakery' => 'bg-gray-100',
                'Frozen Item' => 'bg-gray-100'
            ];
            
            foreach ($categories as $cat): 
                $catTitle = $cat['title'];
                // Use specific icon if available, otherwise use category image or fallback
                if (isset($categoryIcons[$catTitle])) {
                    $imgPath = imagePath($categoryIcons[$catTitle]);
                } else {
                    $imgPath = !empty($cat['image']) ? imagePath($cat['image']) : imagePath('category.jpg');
                }
                
                // Map category to specific page URLs
                $categoryPageMap = [
                    'Fruit' => '/website/pages/fruit.php',
                    'Fruits' => '/website/pages/fruit.php',
                    'Vegetable' => '/website/pages/vegetable.php',
                    'Vegetables' => '/website/pages/vegetable.php',
                    'Veges' => '/website/pages/vegetable.php',
                    'Global Pantry' => '/website/pages/global-pantry.php'
                ];
                
                $categoryUrl = $categoryPageMap[$catTitle] ?? ('/category?name=' . urlencode($catTitle));
                $categoryLink = BASE_PATH . $categoryUrl;
                
                $bgColor = $categoryColors[$catTitle] ?? 'bg-gray-100';
            ?>
                <a href="<?php echo $categoryLink; ?>" 
                   class="category-item text-center flex-shrink-0 w-28 md:w-32 lg:w-36 cursor-pointer flex flex-col items-center gap-3 group"
                   data-category="<?php echo htmlspecialchars($cat['title']); ?>">
                    <!-- Circular Container with Background -->
                    <div class="relative w-24 h-24 md:w-28 md:h-28 rounded-full <?php echo $bgColor; ?> flex items-center justify-center shadow-lg border-2 border-gray-200 group-hover:border-green-500 group-hover:shadow-xl transition-all duration-300">
                        <img src="<?php echo $imgPath; ?>" 
                             alt="<?php echo htmlspecialchars($cat['title']); ?>" 
                             class="w-20 h-20 md:w-24 md:h-24 object-contain rounded-full" />
                    </div>
                    <p class="text-sm font-semibold text-gray-800 group-hover:text-green-600 transition-colors" style="font-family: 'Arial', sans-serif;"><?php echo htmlspecialchars($cat['title']); ?></p>
                </a>
            <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section id="featured-products" class="px-6 py-10 bg-gray-50">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
            <h3 class="text-xl md:text-2xl font-bold text-gray-800" style="font-family: 'Arial', sans-serif;">Featured Products</h3>
            
            <div class="flex items-center gap-4">
                <!-- Filter Buttons -->
                <div class="flex items-center gap-3 md:gap-4 flex-wrap">
                    <button onclick="filterProducts('all')" class="product-filter px-4 py-2 rounded-full text-sm font-normal bg-green-600 text-white hover:bg-green-700 transition" data-filter="all" style="font-family: 'Arial', sans-serif;">
                        All
                    </button>
                    <button onclick="filterProducts('Vegetable')" class="product-filter px-4 py-2 rounded-full text-sm font-normal text-gray-600 bg-gray-100 hover:bg-gray-200 transition" data-filter="Vegetable" style="font-family: 'Arial', sans-serif;">
                        Vegetables
                    </button>
                    <button onclick="filterProducts('Fruit')" class="product-filter px-4 py-2 rounded-full text-sm font-normal text-gray-600 bg-gray-100 hover:bg-gray-200 transition" data-filter="Fruit" style="font-family: 'Arial', sans-serif;">
                        Fruits
                    </button>
                    <button onclick="filterProducts('Juices and Smoothies')" class="product-filter px-4 py-2 rounded-full text-sm font-normal text-gray-600 bg-gray-100 hover:bg-gray-200 transition" data-filter="Juices and Smoothies" style="font-family: 'Arial', sans-serif;">
                        Coffe & teas
                    </button>
                    <button onclick="filterProducts('Global Pantry')" class="product-filter px-4 py-2 rounded-full text-sm font-normal text-gray-600 bg-gray-100 hover:bg-gray-200 transition" data-filter="Global Pantry" style="font-family: 'Arial', sans-serif;">
                        Global Pantry
                    </button>
                </div>
                
                <!-- Navigation Arrows -->
                <div class="flex gap-2">
                    <button onclick="scrollProducts('left')" class="p-2 border border-gray-300 rounded-full hover:bg-gray-100 transition">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    <button onclick="scrollProducts('right')" class="p-2 border border-gray-300 rounded-full hover:bg-gray-100 transition">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>
            </div>
        </div>
        <div id="products-scroll" class="flex gap-6 overflow-x-auto no-scrollbar">
            <?php if (count($featuredProducts) > 0): ?>
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="flex-shrink-0 w-64 product-item" data-category="<?php echo htmlspecialchars($product['category_name'] ?? ''); ?>">
                        <?php 
                        // retailPrice & itemSize come from new schema
                        $basePrice = $product['retailPrice'] ?? 0;
                        $discountValue = $product['discount']['value'] ?? 0;
                        $discountPrice = $basePrice - ($basePrice * $discountValue / 100);
                        $image = getProductImage($product);
                        // Default rating (can be replaced with actual rating from database)
                        $rating = 4.5;
                        $reviewCount = 4;
                        ?>
                        <div class="bg-white shadow-md rounded-xl overflow-hidden p-4">
                            <div class="relative w-full h-44 flex items-center justify-center bg-gray-100 rounded-lg">
                                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="object-contain h-full" />
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
                                
                                <!-- Rating and Wishlist -->
                                <div class="mt-2 flex items-center justify-between">
                                    <div class="flex items-center gap-1">
                                        <?php 
                                        // Show 4 filled stars (rating of 4)
                                        $rating = 4;
                                        for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $rating): ?>
                                                <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                            <?php else: ?>
                                                <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        <span class="text-xs text-gray-600 ml-1">(<?php echo $reviewCount; ?>)</span>
                                    </div>
                                    <button onclick="toggleWishlist('<?php echo $product['id']; ?>')" class="text-gray-400 hover:text-red-500 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                        </svg>
                                    </button>
                                </div>
                                
                                <div class="mt-2 flex items-center gap-3">
                                    <p class="text-lg font-bold text-gray-800" style="font-family: 'Arial', sans-serif;">$<?php echo number_format($discountPrice, 2); ?></p>
                                    <p class="text-sm line-through text-gray-500">$<?php echo number_format($basePrice, 2); ?></p>
                                </div>
                                
                                <!-- Quantity Selector and Cart Button (Horizontal Layout) -->
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
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Daily Packages -->
    <section id="daily-packages" class="px-6 py-10 bg-white">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
            <div>
                <h3 class="text-2xl md:text-3xl font-bold text-gray-800" style="font-family: 'Arial', sans-serif;">Daily Packages</h3>
                <p class="text-sm text-gray-600 mt-1" style="font-family: 'Arial', sans-serif;">All package cards below are loaded from the packages table and filtered by assigned day.</p>
            </div>

            <div class="flex items-center gap-3 md:gap-4 flex-wrap">
                <button onclick="filterPackages('all')" class="package-filter px-4 py-2 rounded-full text-sm font-medium bg-green-600 text-white hover:bg-green-700 transition" data-filter="all" style="font-family: 'Arial', sans-serif;">
                    All
                </button>
                <?php foreach ($weekDays as $dayName): ?>
                    <button onclick="filterPackages('<?php echo htmlspecialchars($dayName); ?>')" class="package-filter px-4 py-2 rounded-full text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition" data-filter="<?php echo htmlspecialchars($dayName); ?>" style="font-family: 'Arial', sans-serif;">
                        <?php echo htmlspecialchars($dayName); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="relative px-10 md:px-14">
            <button onclick="scrollPackages('left')" class="absolute left-0 top-1/2 -translate-y-1/2 z-10 p-2 border border-gray-300 rounded-full bg-white hover:bg-gray-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
            <button onclick="scrollPackages('right')" class="absolute right-0 top-1/2 -translate-y-1/2 z-10 p-2 border border-gray-300 rounded-full bg-white hover:bg-gray-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>

            <div id="packages-scroll" class="flex gap-6 overflow-x-auto scrollbar-hide px-2 py-2 scroll-smooth lg:grid lg:grid-cols-3 lg:gap-6 lg:overflow-visible">
                <?php if (!empty($allDailyPackages)): ?>
                    <?php foreach ($allDailyPackages as $pkg): ?>
                        <?php
                        $basePrice = (float)($pkg['retailPrice'] ?? 0);
                        $discountValue = (float)($pkg['discount']['value'] ?? 0);
                        $discountPrice = $basePrice;
                        if ($discountValue > 0) {
                            $discountPrice = $basePrice - ($basePrice * $discountValue / 100);
                        }
                        $pkgItems = is_array($pkg['items'] ?? null) ? $pkg['items'] : [];
                        $itemSummaries = [];
                        foreach ($pkgItems as $item) {
                            $itemName = trim((string)($item['name'] ?? ''));
                            $itemQty = trim((string)($item['quantity'] ?? ''));
                            if ($itemName === '') {
                                continue;
                            }
                            $itemSummaries[] = $itemQty !== '' ? $itemName . ' (' . $itemQty . ')' : $itemName;
                        }
                        $image = trim((string)($pkg['image'] ?? ''));
                        if ($image === '') {
                            $image = imagePath('package.jpg');
                        }
                        $rating = max(0, min(5, (float)($pkg['rating'] ?? 4)));
                        $reviewCount = max(1, count($pkgItems));
                        $assignedDay = $pkg['assignedDay'] ?? ucfirst(strtolower((string)($pkg['packageDay'] ?? '')));
                        ?>
                        <div class="package-item bg-white rounded-xl overflow-hidden border border-gray-200 shadow-md flex-shrink-0 w-[18rem] md:w-[20rem] lg:w-auto" data-day="<?php echo htmlspecialchars($assignedDay); ?>" style="font-family: 'Arial', sans-serif;">
                            <div class="relative w-full h-48 flex items-center justify-center bg-gray-100">
                                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($pkg['name'] ?? 'Package'); ?>" class="object-cover w-full h-full" />
                                <div class="absolute top-2 left-2">
                                    <span class="bg-orange-500 text-white font-semibold text-xs px-3 py-1 rounded-md">
                                        <?php echo htmlspecialchars(strtoupper($assignedDay)); ?> PACKAGE
                                    </span>
                                </div>
                            </div>
                            <div class="p-4">
                                <div class="flex items-center justify-between gap-3 mb-2">
                                    <p class="font-semibold text-gray-800 text-sm"><?php echo htmlspecialchars($pkg['name'] ?? 'Package'); ?></p>
                                </div>

                                <div class="flex items-center gap-1 mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= round($rating)): ?>
                                            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                        <?php else: ?>
                                            <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    <span class="text-xs text-gray-600 ml-1">(<?php echo $reviewCount; ?>)</span>
                                </div>

                                <p class="text-xs text-gray-600 mb-3 min-h-[40px]">
                                    <?php echo htmlspecialchars(!empty($itemSummaries) ? implode(', ', array_slice($itemSummaries, 0, 3)) : 'Fresh grocery package ready for the day.'); ?>
                                </p>

                                <div class="mt-3 flex items-center gap-3 mb-4">
                                    <p class="text-lg font-bold text-black">$<?php echo number_format($discountPrice, 2); ?></p>
                                    <?php if ($discountValue > 0): ?>
                                        <p class="text-sm line-through text-gray-500">$<?php echo number_format($basePrice, 2); ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-4">
                                    <p class="text-xs font-semibold text-gray-600 mb-2">Included Items</p>
                                    <?php if (!empty($pkgItems)): ?>
                                        <ul class="list-disc list-inside text-xs text-gray-700 space-y-1 max-h-24 overflow-y-auto pr-1">
                                            <?php foreach ($pkgItems as $item): ?>
                                                <?php
                                                $itemName = trim((string)($item['name'] ?? ''));
                                                $itemQty = trim((string)($item['quantity'] ?? ''));
                                                if ($itemName === '') {
                                                    continue;
                                                }
                                                ?>
                                                <li>
                                                    <?php echo htmlspecialchars($itemName); ?>
                                                    <?php if ($itemQty !== ''): ?>
                                                        <span class="text-gray-500">(<?php echo htmlspecialchars($itemQty); ?>)</span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-xs text-gray-500">No items listed for this package.</p>
                                    <?php endif; ?>
                                </div>

                                <div class="flex gap-2">
                                    <button type="button" onclick="filterPackages('<?php echo htmlspecialchars($assignedDay); ?>')" class="flex-1 border border-green-600 text-green-600 px-4 py-2 rounded-md hover:bg-green-50 transition">View Day</button>
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
                    <div class="col-span-full text-center py-12 w-full">
                        <p class="text-gray-500 text-lg">No daily packages available at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Promotion Section -->
    <section class="w-full py-12 bg-white">
        <div class="relative lg:px-20">
            <div class="flex gap-6 overflow-x-auto scrollbar-hide lg:grid lg:grid-cols-4 lg:gap-8 lg:overflow-visible px-6 lg:px-0">
                <?php 
                $features = [
                    ['title' => 'Browse Products', 'desc' => 'Find fresh groceries and daily packages', 'img' => imagePath('Search.png')],
                    ['title' => 'Add to Cart', 'desc' => 'Choose what you need and adjust quantities', 'img' => imagePath('Cart.png')],
                    ['title' => 'Checkout', 'desc' => 'Enter details and pay securely', 'img' => imagePath('Checkout.png')],
                    ['title' => 'Receive Order', 'desc' => 'Fast, fresh delivery to your door', 'img' => imagePath('Order.png')]
                ];
                foreach ($features as $feature): ?>
                    <div class="bg-white border border-gray-300 rounded-xl p-8 text-center flex-shrink-0 w-64 lg:w-auto flex flex-col items-center" style="min-height: 320px; font-family: 'Arial', sans-serif;">
                        <img src="<?php echo htmlspecialchars($feature['img']); ?>" alt="<?php echo htmlspecialchars($feature['title']); ?>" class="w-24 h-24 mb-4" />
                        <h3 class="text-lg font-bold mb-2"><?php echo htmlspecialchars($feature['title']); ?></h3>
                        <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($feature['desc']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="relative mt-40 bg-green-600 px-6 lg:px-10 flex flex-col lg:flex-row items-center justify-between overflow-visible mb-16">
            <div class="text-white max-w-lg z-10 py-12 text-center lg:text-left" style="font-family: 'Arial', sans-serif;">
                <h2 class="text-3xl font-normal mb-2">Join Our Newsletter</h2>
                <p class="mb-6 font-normal">Sign up for deals, new products and promotions</p>
                <div class="flex bg-white rounded-md overflow-hidden max-w-md mx-auto lg:mx-0">
                    <div class="flex items-center px-3 text-gray-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                    <input type="email" placeholder="Email address" class="flex-1 px-3 py-3 text-gray-700 focus:outline-none" />
                    <button class="bg-green-700 text-white px-6 font-normal hover:bg-green-800">Signup</button>
                </div>
            </div>
            <div class="absolute right-6 lg:right-10 top-1/2 -translate-y-1/2">
                <img src="<?php echo imagePath('Hexa.png'); ?>" alt="Store" class="w-[32rem] h-[32rem] lg:w-[36rem] lg:h-[36rem] object-contain" />
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
    // Map filter button labels to actual category titles
    const categoryMap = {
        'all': 'all',
        'Vegetable': 'Vegetable',
        'Vegetables': 'Vegetable',
        'Fruit': 'Fruit',
        'Fruits': 'Fruit',
        'Juices and Smoothies': 'Juices and Smoothies',
        'Coffe & teas': 'Juices and Smoothies',
        'Global Pantry': 'Global Pantry'
    };
    
    const actualCategory = categoryMap[categoryName] || categoryName;
    
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
        if (actualCategory === 'all') {
            item.style.display = 'flex';
        } else {
            const itemCategory = item.getAttribute('data-category');
            if (itemCategory === actualCategory) {
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

function filterProducts(categoryName) {
    // Map filter button labels to actual category titles
    const categoryMap = {
        'all': 'all',
        'Vegetable': 'Vegetable',
        'Vegetables': 'Vegetable',
        'Fruit': 'Fruit',
        'Fruits': 'Fruit',
        'Juices and Smoothies': 'Juices and Smoothies',
        'Coffe & teas': 'Juices and Smoothies',
        'Global Pantry': 'Global Pantry'
    };
    
    const actualCategory = categoryMap[categoryName] || categoryName;
    
    // Update filter button styles
    document.querySelectorAll('.product-filter').forEach(btn => {
        btn.classList.remove('bg-green-600', 'text-white');
        btn.classList.add('bg-gray-100', 'text-gray-600');
    });
    
    // Highlight selected filter
    const selectedBtn = document.querySelector(`.product-filter[data-filter="${categoryName}"]`);
    if (selectedBtn) {
        selectedBtn.classList.remove('bg-gray-100', 'text-gray-600');
        selectedBtn.classList.add('bg-green-600', 'text-white');
    }
    
    // Filter products
    const productItems = document.querySelectorAll('.product-item');
    productItems.forEach(item => {
        if (actualCategory === 'all') {
            item.style.display = 'block';
        } else {
            const itemCategory = item.getAttribute('data-category');
            if (itemCategory === actualCategory) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        }
    });
}

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
    // Placeholder for wishlist functionality
    console.log('Toggle wishlist for item:', itemId);
}

function scrollToFeaturedProducts() {
    const section = document.getElementById('featured-products');
    if (section) {
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function scrollToDailyPackages() {
    const section = document.getElementById('daily-packages');
    if (section) {
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
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
});
function scrollPackages(direction) {
    const el = document.getElementById('packages-scroll');
    el.scrollBy({ left: direction === 'left' ? -300 : 300, behavior: 'smooth' });
}

function filterPackages(filterType) {
    // Update filter button styles
    document.querySelectorAll('.package-filter').forEach(btn => {
        btn.classList.remove('bg-green-600', 'text-white');
        btn.classList.add('bg-gray-100', 'text-gray-600');
    });
    
    // Highlight selected filter
    const selectedBtn = document.querySelector(`.package-filter[data-filter="${filterType}"]`);
    if (selectedBtn) {
        selectedBtn.classList.remove('bg-gray-100', 'text-gray-600');
        selectedBtn.classList.add('bg-green-600', 'text-white');
    }
    
    // Filter packages by assigned day from the database
    const packageItems = document.querySelectorAll('.package-item');
    packageItems.forEach(item => {
        if (filterType === 'all') {
            item.style.display = 'block';
        } else {
            item.style.display = item.dataset.day === filterType ? 'block' : 'none';
        }
    });
}

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

// Scroll to daily-packages section if hash is present on page load
if (window.location.hash === '#daily-packages') {
    const section = document.getElementById('daily-packages');
    if (section) {
        setTimeout(function() {
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 300);
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

