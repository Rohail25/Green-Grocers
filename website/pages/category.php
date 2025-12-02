<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

$categoryName = $_GET['name'] ?? 'Vegetables';
$products = getProductsByCategory($categoryName);
$packages = getFeaturedPackages(2);

$categoryData = [
    'Vegetables' => ['heading' => 'Fresh Vegetables', 'description' => 'Discover hand-picked, farm-fresh vegetables delivered daily.'],
    'Fruits' => ['heading' => 'Fresh Fruits', 'description' => 'Juicy and sweet seasonal fruits, delivered to your door.'],
    'Dairy' => ['heading' => 'Fresh Dairy Products', 'description' => 'Quality dairy essentials straight from trusted farms.'],
    'Meat' => ['heading' => 'Fresh Meat', 'description' => 'Premium cuts, hygienically packed and delivered fresh.'],
    'Beverages' => ['heading' => 'Refreshing Beverages', 'description' => 'Stay refreshed with our curated beverage collection.']
];

$category = $categoryData[$categoryName] ?? $categoryData['Vegetables'];
$pageTitle = $category['heading'] . ' - Green Grocers';
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="w-full">
    <section class="relative w-full py-20 bg-cover bg-center text-center text-white mt-[6rem]" style="background-image: url('<?php echo imagePath('category.jpg'); ?>')">
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="relative z-10 max-w-3xl mx-auto px-6 py-20 md:py-28">
            <h1 class="text-4xl md:text-6xl font-bold text-gray-700"><?php echo htmlspecialchars($category['heading']); ?></h1>
            <p class="mt-4 text-xl md:text-2xl text-gray-700 font-semibold"><?php echo htmlspecialchars($category['description']); ?></p>
            <div class="mt-8 bg-white rounded-lg shadow flex overflow-hidden max-w-xl mx-auto">
                <div class="flex items-center px-3">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" placeholder="Search <?php echo htmlspecialchars($categoryName); ?>..." class="flex-1 px-3 py-2 outline-none text-gray-700" />
                <button class="px-6 py-2 bg-green-600 text-white hover:bg-green-700">Search</button>
            </div>
        </div>
    </section>

    <section class="px-6 py-12">
        <h2 class="text-5xl font-bold mb-6 flex justify-center">Available <?php echo htmlspecialchars($category['heading']); ?></h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($products as $product): ?>
                <?php 
                $discountPrice = $product['retail_price'] - ($product['retail_price'] * ($product['discount']['value'] ?? 0) / 100);
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
                        <p class="font-semibold text-gray-800 text-sm"><?php echo htmlspecialchars($product['name']); ?> <span class="text-gray-500">(<?php echo htmlspecialchars($product['item_size'] ?? ''); ?>)</span></p>
                        <div class="mt-2 flex items-center gap-3">
                            <p class="text-lg font-bold text-black">$<?php echo number_format($discountPrice, 2); ?></p>
                            <p class="text-sm line-through text-gray-500">$<?php echo number_format($product['retail_price'], 2); ?></p>
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
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

