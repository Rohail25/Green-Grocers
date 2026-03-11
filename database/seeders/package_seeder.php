<?php
/**
 * Package Seeder - Creates one daily package for each day of the week
 *
 * Usage: php database/seeders/package_seeder.php
 */

require_once __DIR__ . '/../../config/database.php';

echo "🌱 Starting Package Seeder...\n\n";

try {
    $conn = getDBConnection();
    echo "✅ Database connection successful\n";
    echo "📊 Database: " . DB_NAME . "\n";

    $tableCheck = $conn->query("SHOW TABLES LIKE 'packages'");
    if ($tableCheck->rowCount() == 0) {
        echo "❌ ERROR: 'packages' table does not exist in database!\n";
        exit(1);
    }

    $productsCheck = $conn->query("SHOW TABLES LIKE 'products'");
    if ($productsCheck->rowCount() == 0) {
        echo "❌ ERROR: 'products' table does not exist in database!\n";
        exit(1);
    }

    echo "✅ Packages table exists\n";
    echo "✅ Products table exists\n\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

function packageSeederGenerateUuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function packageSeederTableHasColumn(PDO $conn, $tableName, $columnName) {
    $stmt = $conn->prepare(
        "SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :tableName
           AND COLUMN_NAME = :columnName"
    );
    $stmt->execute([
        ':tableName' => $tableName,
        ':columnName' => $columnName
    ]);

    return (int)$stmt->fetchColumn() > 0;
}

$productStmt = $conn->query("SELECT name FROM products WHERE status = 'active' ORDER BY name ASC");
$allProductNames = [];
while ($row = $productStmt->fetch(PDO::FETCH_ASSOC)) {
    $name = trim((string)($row['name'] ?? ''));
    if ($name !== '') {
        $allProductNames[] = $name;
    }
}

$allProductNames = array_values(array_unique($allProductNames));

if (count($allProductNames) < 4) {
    echo "❌ Not enough active products found to build daily packages.\n";
    echo "   At least 4 active products are required.\n";
    exit(1);
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$packageTemplates = [
    ['name' => 'Fresh Start Basket', 'description' => 'Balanced fruits and vegetables for the start of the week.', 'discount' => 10, 'price' => 18.50, 'category' => 'Daily Essentials', 'image' => 'public/uploads/packages/6931279746992_grocery.png'],
    ['name' => 'Midweek Market Box', 'description' => 'Kitchen staples and produce for quick midweek meals.', 'discount' => 12, 'price' => 21.00, 'category' => 'Family Pack', 'image' => 'public/uploads/packages/6931271f4656d_bakery.jpg'],
    ['name' => 'Weekend Family Pack', 'description' => 'A fuller package for weekend cooking and sharing.', 'discount' => 15, 'price' => 28.00, 'category' => 'Weekend Special', 'image' => 'public/uploads/packages/692ffe912ac03_car-3.jpg']
];

$hasDescription = packageSeederTableHasColumn($conn, 'packages', 'description');
$hasCategory = packageSeederTableHasColumn($conn, 'packages', 'category');
$hasIsFeatured = packageSeederTableHasColumn($conn, 'packages', 'isFeatured');
$hasTags = packageSeederTableHasColumn($conn, 'packages', 'tags');
$hasRating = packageSeederTableHasColumn($conn, 'packages', 'rating');

$inserted = 0;
$skipped = 0;
$errors = [];

foreach ($days as $index => $dayName) {
    $template = $packageTemplates[$index % count($packageTemplates)];
    $packageName = $dayName . ' ' . $template['name'];

    try {
        $checkStmt = $conn->prepare("SELECT id FROM packages WHERE name = :name AND packageDay = :packageDay");
        $checkStmt->execute([
            ':name' => $packageName,
            ':packageDay' => $dayName
        ]);

        if ($checkStmt->fetch()) {
            echo "⏭️  Skipped: {$packageName} ({$dayName}) already exists\n";
            $skipped++;
            continue;
        }

        $items = [];
        for ($offset = 0; $offset < 4; $offset++) {
            $productIndex = ($index * 2 + $offset) % count($allProductNames);
            $quantityLabels = ['1 pack', '1 kg', '2 pcs', '500 g'];
            $items[] = [
                'name' => $allProductNames[$productIndex],
                'quantity' => $quantityLabels[$offset % count($quantityLabels)]
            ];
        }

        $columns = ['id', 'name', 'packageDay', 'items', 'retailPrice', 'discount', 'status', 'image'];
        $placeholders = [':id', ':name', ':packageDay', ':items', ':retailPrice', ':discount', ':status', ':image'];
        $params = [
            ':id' => packageSeederGenerateUuid(),
            ':name' => $packageName,
            ':packageDay' => $dayName,
            ':items' => json_encode($items),
            ':retailPrice' => number_format($template['price'] + ($index * 1.25), 2, '.', ''),
            ':discount' => json_encode(['type' => 'percentage', 'value' => $template['discount']]),
            ':status' => 'active',
            ':image' => $template['image']
        ];

        if ($hasDescription) {
            $columns[] = 'description';
            $placeholders[] = ':description';
            $params[':description'] = $template['description'];
        }

        if ($hasIsFeatured) {
            $columns[] = 'isFeatured';
            $placeholders[] = ':isFeatured';
            $params[':isFeatured'] = 1;
        }

        if ($hasTags) {
            $columns[] = 'tags';
            $placeholders[] = ':tags';
            $params[':tags'] = json_encode([$dayName, 'daily-package', 'featured']);
        }

        if ($hasCategory) {
            $columns[] = 'category';
            $placeholders[] = ':category';
            $params[':category'] = $template['category'];
        }

        if ($hasRating) {
            $columns[] = 'rating';
            $placeholders[] = ':rating';
            $params[':rating'] = 4.5;
        }

        $sql = 'INSERT INTO packages (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        $inserted++;
        echo "✅ Created: {$packageName} ({$dayName}) with " . count($items) . " product(s)\n";
    } catch (Exception $e) {
        $errors[] = "{$packageName}: " . $e->getMessage();
        echo "❌ Error creating {$packageName}: " . $e->getMessage() . "\n";
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "📊 Package Seeder Summary:\n";
echo str_repeat('=', 60) . "\n";
echo "✅ Inserted: {$inserted} package(s)\n";
echo "⏭️  Skipped: {$skipped} package(s)\n";
echo "❌ Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}

echo "\n✅ Package Seeder completed!\n";
?>