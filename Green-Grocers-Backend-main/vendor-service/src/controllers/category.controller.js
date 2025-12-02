const prisma = require('../utils/prisma');
const { getProductsByCategoryService, updateProductCategoryService } = require('../utils/communicateProductService');
const { createCategoryObject, validateCategoryStructure } = require('../utils/categoryValidator');

// GET all categories
const getCategories = async (req, res) => {
  try {
    const vendor = await prisma.vendor.findUnique({
      where: { vendorId: req.params.vendorId }
    });
    if (!vendor) return res.status(404).json({ message: 'Vendor not found' });

    // Parse categories JSON
    const categories = typeof vendor.categories === 'string' 
      ? JSON.parse(vendor.categories) 
      : (vendor.categories || []);

    res.json({ categories });
  } catch (err) {
    console.error("[Get Categories Error]", err.message);
    res.status(500).json({ message: 'Failed to fetch categories' });
  }
};

// CREATE category
const addCategory = async (req, res) => {
  const { categories } = req.body;

  if (!Array.isArray(categories) || categories.length === 0) {
    return res.status(400).json({ message: 'Categories array is required' });
  }

  try {
    const vendor = await prisma.vendor.findUnique({
      where: { vendorId: req.params.vendorId }
    });
    if (!vendor) return res.status(404).json({ message: 'Vendor not found' });

    // Parse existing categories JSON
    const existingCategories = typeof vendor.categories === 'string' 
      ? JSON.parse(vendor.categories) 
      : (vendor.categories || []);

    const addedCategories = [];

    categories.forEach(cat => {
      // Validate category structure
      const validation = validateCategoryStructure(cat);
      if (!validation.valid) {
        console.warn(`Invalid category skipped: ${validation.errors.join(', ')}`);
        return; // Skip invalid categories
      }

      // Create properly structured category
      const structuredCategory = createCategoryObject(cat);

      // Check if category already exists (compare by categoryId or title)
      const exists = existingCategories.some(existing => 
        existing.categoryId === structuredCategory.categoryId || 
        existing.title === structuredCategory.title
      );
      
      if (!exists) {
        existingCategories.push(structuredCategory);
        addedCategories.push(structuredCategory);
      }
    });

    // Update vendor
    await prisma.vendor.update({
      where: { vendorId: req.params.vendorId },
      data: { categories: existingCategories }
    });

    res.status(201).json({
      message: 'Categories added successfully',
      addedCategories,
      allCategories: existingCategories
    });
  } catch (err) {
    console.error("[Add Category Error]", err.message);
    res.status(500).json({ message: 'Failed to add categories', error: err.message });
  }
};

// DELETE category
const deleteCategory = async (req, res) => {
  const { category } = req.params;
  try {
    const vendor = await prisma.vendor.findUnique({
      where: { vendorId: req.params.vendorId }
    });
    if (!vendor) return res.status(404).json({ message: 'Vendor not found' });

    // Parse categories JSON
    const categories = typeof vendor.categories === 'string' 
      ? JSON.parse(vendor.categories) 
      : (vendor.categories || []);

    // Filter out the category (can match by title or categoryId)
    const updatedCategories = categories.filter(c => 
      c.title !== category && c.categoryId !== category
    );

    // Check if category was found
    if (categories.length === updatedCategories.length) {
      return res.status(404).json({ message: 'Category not found' });
    }

    // Update vendor
    await prisma.vendor.update({
      where: { vendorId: req.params.vendorId },
      data: { categories: updatedCategories }
    });

    res.json({ message: 'Category deleted', categories: updatedCategories });
  } catch (err) {
    console.error("[Delete Category Error]", err.message);
    res.status(500).json({ message: 'Failed to delete category', error: err.message });
  }
};

// GET products by category (from product service)
const getProductsByCategory = async (req, res) => {
  const { category } = req.params;
  const vendorId = req.params.vendorId;

  try {
    const token = req.headers.authorization?.split(' ')[1];
    const products = await getProductsByCategoryService(vendorId, category, token);
    res.json(products);
  } catch (err) {
    console.error("[Get Products By Category Error]", err.message);
    res.status(500).json({ message: 'Failed to fetch products', error: err.message });
  }
};


const getCategoryOverview = async (req, res) => {
  const { vendorId } = req.params;
  try {
    const vendor = await prisma.vendor.findUnique({
      where: { vendorId }
    });
    if (!vendor) return res.status(404).json({ message: 'Vendor not found' });

    // Parse categories JSON
    const categories = typeof vendor.categories === 'string' 
      ? JSON.parse(vendor.categories) 
      : (vendor.categories || []);

    const results = [];
    const token = req.headers.authorization?.split(' ')[1];

    for (const category of categories) {
      // Get products from product service
      const products = await getProductsByCategoryService(vendorId, category.categoryId, token);

      const totalVariants = products.reduce((acc, product) => {
        const variants = typeof product.variants === 'string' 
          ? JSON.parse(product.variants) 
          : (product.variants || []);
        return acc + (variants?.length || 0);
      }, 0);

      // categorySettings is not in the schema, so use null or vendor.createdAt
      results.push({
        category: category,
        dateCreated: vendor.createdAt,
        quantityOfProducts: products.length,
        totalVariants,
      });
    }

    res.json(results);
  } catch (err) {
    console.error('[Category Overview Error]', err.message);
    res.status(500).json({ message: 'Failed to fetch category overview', error: err.message });
  }
};

const updateCategory = async (req, res) => {
  const { vendorId, categoryName } = req.params;
  const { newCategoryName } = req.body;

  try {
    const vendor = await prisma.vendor.findUnique({
      where: { vendorId }
    });
    if (!vendor) return res.status(404).json({ message: 'Vendor not found' });

    // Parse categories JSON
    const categories = typeof vendor.categories === 'string' 
      ? JSON.parse(vendor.categories) 
      : (vendor.categories || []);

    // Find category index
    const categoryIndex = categories.findIndex(c => c.title === categoryName);
    if (categoryIndex === -1) return res.status(404).json({ message: 'Category not found' });

    // Update the category title
    categories[categoryIndex].title = newCategoryName;

    // Update vendor
    await prisma.vendor.update({
      where: { vendorId },
      data: { categories }
    });

    // Update products in Product Service
    await updateProductCategoryService(vendorId, categoryName, newCategoryName);

    res.json({ message: 'Category updated successfully across vendor and products', categories });
  } catch (err) {
    console.error('[Update Category Error]', err);
    res.status(500).json({ message: 'Failed to update category', error: err.message });
  }
};


module.exports = {
  getCategories,
  addCategory,
  deleteCategory,
  getProductsByCategory,
  getCategoryOverview,
  updateCategory
};
