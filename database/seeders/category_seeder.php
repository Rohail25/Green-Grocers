<?php
/**
 * Category Seeder - Creates categories with icons
 * 
 * Usage: Run this file to seed the database with categories
 * 
 * This seeder creates:
 * - Fruit
 * - Veges
 * - Frozen Item
 * - Grocery
 * - Juices and Smoothies
 */

require_once __DIR__ . '/../../config/database.php';

echo "🌱 Starting Category Seeder...\n\n";

// Test database connection
try {
    $conn = getDBConnection();
    echo "✅ Database connection successful\n";
    echo "📊 Database: " . DB_NAME . "\n";
    
    // Check if categories table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'categories'");
    if ($tableCheck->rowCount() == 0) {
        echo "❌ ERROR: 'categories' table does not exist in database!\n";
        echo "   Please create the categories table first.\n";
        exit(1);
    }
    echo "✅ Categories table exists\n\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Function to generate UUID (for category ID)
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Categories data with image paths
// Images are stored in: public/uploads/categories/
// Only two fields: title and image
$categories = [
    [
        'title' => 'Fruit',
        'image' => 'uploads/categories/icon (1).png',
    ],
    [
        'title' => 'Veges',
        'image' => 'uploads/categories/icon.png',
    ],
    [
        'title' => 'Frozen Item',
        'image' => 'uploads/categories/icon (4).png',
    ],
    [
        'title' => 'Grocery',
        'image' => 'uploads/categories/icon (3).png',
    ],
    [
        'title' => 'Juices and Smoothies',
        'image' => 'uploads/categories/icon (2).png',
    ],
];

// Insert categories
$inserted = 0;
$skipped = 0;
$errors = [];

foreach ($categories as $categoryData) {
    try {
        // Check if category already exists
        $checkStmt = $conn->prepare("SELECT id FROM categories WHERE title = :title");
        $checkStmt->execute([
            ':title' => $categoryData['title']
        ]);
        
        if ($checkStmt->fetch()) {
            echo "⏭️  Skipped: {$categoryData['title']} (already exists)\n";
            $skipped++;
            continue;
        }
        
        // Generate UUID for category ID
        $categoryId = generateUUID();
        
        // Verify image file exists
        $imagePath = __DIR__ . '/../../public/uploads/categories/' . basename($categoryData['image']);
        if (!file_exists($imagePath)) {
            echo "⚠️  Warning: Image not found for {$categoryData['title']}: {$categoryData['image']}\n";
            echo "   Expected at: {$imagePath}\n";
            echo "   Continuing anyway...\n";
        } else {
            echo "   ✓ Image file verified: " . basename($categoryData['image']) . "\n";
        }
        
        // Insert category - Only title and image fields
        $stmt = $conn->prepare("
            INSERT INTO categories (
                id, title, image
            ) VALUES (
                :id, :title, :image
            )
        ");
        
        $result = $stmt->execute([
            ':id' => $categoryId,
            ':title' => $categoryData['title'],
            ':image' => $categoryData['image'],
        ]);
        
        if (!$result) {
            throw new Exception("Insert failed for {$categoryData['title']}");
        }
        
        $inserted++;
        echo "✅ Created: {$categoryData['title']} (Image: {$categoryData['image']})\n";
        
    } catch (PDOException $e) {
        $errorMsg = "❌ Error creating {$categoryData['title']}: " . $e->getMessage();
        echo $errorMsg . "\n";
        echo "   SQL Error Code: " . $e->getCode() . "\n";
        if (isset($stmt) && $stmt->errorInfo()) {
            echo "   SQL Error Info: " . print_r($stmt->errorInfo(), true) . "\n";
        }
        $errors[] = $errorMsg;
    } catch (Exception $e) {
        $errorMsg = "❌ Error creating {$categoryData['title']}: " . $e->getMessage();
        echo $errorMsg . "\n";
        $errors[] = $errorMsg;
    }
}

// Summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 Seeder Summary:\n";
echo str_repeat("=", 60) . "\n";
echo "✅ Inserted: {$inserted} categories\n";
echo "⏭️  Skipped: {$skipped} categories (already exist)\n";
echo "❌ Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}

// Display created categories
echo "\n" . str_repeat("=", 60) . "\n";
echo "📋 Created Categories:\n";
echo str_repeat("=", 60) . "\n";

$displayStmt = $conn->query("
    SELECT id, title, image 
    FROM categories 
    ORDER BY title
");

$displayCategories = $displayStmt->fetchAll(PDO::FETCH_ASSOC);

echo sprintf("%-20s %-50s\n", 
    "Title", "Image Path");
echo str_repeat("-", 80) . "\n";

foreach ($displayCategories as $category) {
    echo sprintf("%-20s %-50s\n",
        $category['title'],
        $category['image']
    );
}

echo "\n✅ Category Seeder completed!\n";
echo "\n💡 Categories are now available on the website!\n";
?>
