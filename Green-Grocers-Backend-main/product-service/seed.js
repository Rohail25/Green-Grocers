const mongoose = require('mongoose');
const Category = require('./src/models/category.model');
const Product = require('./src/models/product.model');
const Package = require('./src/models/package.model');

// Connect to MongoDB
const connectDB = async () => {
  try {
    await mongoose.connect(process.env.MONGODB_URI || 'mongodb://localhost:27017/greengrocer-products');
    console.log('MongoDB connected');
  } catch (error) {
    console.error('MongoDB connection error:', error);
    process.exit(1);
  }
};

// Sample Categories
const sampleCategories = [
  { title: 'Vegetables', image: '/vege.png' },
  { title: 'Fruits', image: '/fruits.png' },
  { title: 'Beverages', image: '/beverages.png' },
  { title: 'Dairy', image: '/dairy.png' },
  { title: 'Groceries', image: '/grocery.png' },
  { title: 'Bakery', image: '/bakery.png' },
  { title: 'Meat', image: '/meat.png' }
];

// Sample Products
const sampleProducts = [
  {
    name: 'Fresh Tomatoes',
    description: 'Farm-fresh tomatoes perfect for salads and cooking',
    images: ['/product.jpg'],
    retailPrice: 99,
    discount: { type: 'percentage', value: 20 },
    itemSize: '1kg',
    totalQuantityInStock: 50,
    category: 'Vegetables',
    status: 'active',
    isFeatured: true
  },
  {
    name: 'Organic Apples',
    description: 'Sweet and crisp organic apples',
    images: ['/product.jpg'],
    retailPrice: 149,
    discount: { type: 'percentage', value: 15 },
    itemSize: '1kg',
    totalQuantityInStock: 30,
    category: 'Fruits',
    status: 'active',
    isFeatured: true
  },
  {
    name: 'Fresh Milk',
    description: 'Pure cow milk, pasteurized',
    images: ['/product.jpg'],
    retailPrice: 89,
    discount: { type: 'percentage', value: 10 },
    itemSize: '1L',
    totalQuantityInStock: 25,
    category: 'Dairy',
    status: 'active',
    isFeatured: true
  },
  {
    name: 'Whole Wheat Bread',
    description: 'Freshly baked whole wheat bread',
    images: ['/product.jpg'],
    retailPrice: 45,
    discount: { type: 'percentage', value: 5 },
    itemSize: '1pc',
    totalQuantityInStock: 20,
    category: 'Bakery',
    status: 'active',
    isFeatured: false
  },
  {
    name: 'Orange Juice',
    description: 'Freshly squeezed orange juice',
    images: ['/product.jpg'],
    retailPrice: 120,
    discount: { type: 'percentage', value: 25 },
    itemSize: '1L',
    totalQuantityInStock: 15,
    category: 'Beverages',
    status: 'active',
    isFeatured: true
  },
  {
    name: 'Fresh Carrots',
    description: 'Crunchy fresh carrots',
    images: ['/product.jpg'],
    retailPrice: 75,
    discount: { type: 'percentage', value: 12 },
    itemSize: '2kg',
    totalQuantityInStock: 40,
    category: 'Vegetables',
    status: 'active',
    isFeatured: true
  }
];

// Sample Packages
const samplePackages = [
  {
    name: 'Healthy Combo',
    description: 'Perfect combination of fresh vegetables and dairy',
    image: '/package.jpg',
    packageDay: 'Monday',
    items: [
      { productName: 'Fresh Milk', quantity: '1L', price: 89 },
      { productName: 'Fresh Tomatoes', quantity: '1kg', price: 99 },
      { productName: 'Whole Wheat Bread', quantity: '1pc', price: 45 },
      { productName: 'Fresh Carrots', quantity: '1kg', price: 75 }
    ],
    retailPrice: 199,
    discount: { type: 'percentage', value: 20 },
    status: 'active',
    isFeatured: true,
    rating: 4.5,
    totalOrders: 25,
    category: 'Healthy'
  },
  {
    name: 'Fresh Pack',
    description: 'Daily essentials for a healthy lifestyle',
    image: '/package.jpg',
    packageDay: 'Tuesday',
    items: [
      { productName: 'Organic Apples', quantity: '1kg', price: 149 },
      { productName: 'Fresh Milk', quantity: '1L', price: 89 },
      { productName: 'Orange Juice', quantity: '1L', price: 120 },
      { productName: 'Fresh Tomatoes', quantity: '1kg', price: 99 }
    ],
    retailPrice: 299,
    discount: { type: 'percentage', value: 15 },
    status: 'active',
    isFeatured: true,
    rating: 4.8,
    totalOrders: 18,
    category: 'Fresh'
  },
  {
    name: 'Family Pack',
    description: 'Large family essentials package',
    image: '/package.jpg',
    packageDay: 'Wednesday',
    items: [
      { productName: 'Fresh Carrots', quantity: '2kg', price: 150 },
      { productName: 'Fresh Tomatoes', quantity: '2kg', price: 198 },
      { productName: 'Organic Apples', quantity: '2kg', price: 298 },
      { productName: 'Fresh Milk', quantity: '2L', price: 178 }
    ],
    retailPrice: 399,
    discount: { type: 'percentage', value: 25 },
    status: 'active',
    isFeatured: false,
    rating: 4.2,
    totalOrders: 12,
    category: 'Family'
  }
];

// Seed Database
const seedDatabase = async () => {
  try {
    await connectDB();

    // Clear existing data
    await Category.deleteMany({});
    await Product.deleteMany({});
    await Package.deleteMany({});

    // Insert categories
    const categories = await Category.insertMany(sampleCategories);
    console.log('✅ Categories seeded:', categories.length);

    // Create category mapping
    const categoryMap = {};
    categories.forEach(cat => {
      categoryMap[cat.title] = cat._id;
    });

    // Insert products with category references
    const productsWithCategories = sampleProducts.map(product => ({
      ...product,
      categoryId: categoryMap[product.category]
    }));
    
    const products = await Product.insertMany(productsWithCategories);
    console.log('✅ Products seeded:', products.length);

    // Insert packages
    const packages = await Package.insertMany(samplePackages);
    console.log('✅ Packages seeded:', packages.length);

    console.log('🎉 Database seeded successfully!');
    process.exit(0);
  } catch (error) {
    console.error('❌ Seeding error:', error);
    process.exit(1);
  }
};

// Run seeding
if (require.main === module) {
  seedDatabase();
}

module.exports = { seedDatabase };
