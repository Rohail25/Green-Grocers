const prisma = require("../utils/prisma");
const {
  updateVendorInventoryAndCategories,
} = require("../utils/communicateVendorService");
const { saveFileLocally } = require("../utils/localFileUploader");

// Create
const createProduct = async (req, res) => {
  try {
    // 1. Upload main product images
    const imageUrls = [];
    for (const file of req.files?.images || []) {
      const imageUrl = saveFileLocally(file);
      imageUrls.push(imageUrl);
    }

    // 2. Upload variant images
    const variantImageMap = {}; // { 0: [url, url], 1: [url, url] }
    for (const key in req.files) {
      const match = key.match(/^variantImages\[(\d+)\]$/);
      if (match) {
        const index = match[1];
        variantImageMap[index] = [];
        for (const file of req.files[key]) {
          const url = saveFileLocally(file);
          variantImageMap[index].push(url);
        }
      }
    }

    // 3. Construct variants array
    const parsedVariants = JSON.parse(req.body.variants || "[]");
    const variants = parsedVariants.map((variant, index) => ({
      ...variant,
      images: variantImageMap[index] || [],
    }));

    // 4. Parse JSON fields
    const images = imageUrls;
    const tags = typeof req.body.tags === 'string' 
      ? JSON.parse(req.body.tags) 
      : (req.body.tags || []);
    const appliedCoupons = typeof req.body.appliedCoupons === 'string'
      ? JSON.parse(req.body.appliedCoupons)
      : (req.body.appliedCoupons || []);
    const discount = req.body.discount 
      ? (typeof req.body.discount === 'string' ? JSON.parse(req.body.discount) : req.body.discount)
      : null;
    const preSalePeriod = req.body.preSalePeriod
      ? (typeof req.body.preSalePeriod === 'string' ? JSON.parse(req.body.preSalePeriod) : req.body.preSalePeriod)
      : null;

    // 5. Create product
    // Support both camelCase and snake_case field names
    const brandId = req.body.brandId || req.body.brand_id || null;
    const categoryId = req.body.categoryId || req.body.category_id || null;
    const vendorId = req.body.vendorId || req.body.vendor_id || null;
    
    const product = await prisma.product.create({
      data: {
        vendorId: vendorId,
        brandId: brandId,
        categoryId: categoryId,
        name: req.body.name,
        description: req.body.description || null,
        itemSize: req.body.itemSize || null,
        totalQuantityInStock: parseInt(req.body.totalQuantityInStock) || 0,
        images: images,
        variants: variants,
        brand: req.body.brand || null,
        category: req.body.category || null,
        gender: req.body.gender || null,
        collection: req.body.collection || null,
        tags: tags,
        retailPrice: req.body.retailPrice ? parseFloat(req.body.retailPrice) : null,
        wholesalePrice: req.body.wholesalePrice ? parseFloat(req.body.wholesalePrice) : null,
        minWholesaleQty: req.body.minWholesaleQty ? parseInt(req.body.minWholesaleQty) : null,
        preSalePrice: req.body.preSalePrice ? parseFloat(req.body.preSalePrice) : null,
        preSalePeriod: preSalePeriod,
        discount: discount,
        appliedCoupons: appliedCoupons,
        status: req.body.status || 'active',
        isFeatured: req.body.isFeatured === 'true' || req.body.isFeatured === true,
      }
    });

    // Update vendor inventory (non-blocking - don't fail product creation if this fails)
    if (vendorId && process.env.VENDOR_SERVICE_URL) {
      try {
        await updateVendorInventoryAndCategories(
          vendorId,
          req.headers.authorization?.split(" ")[1]
        );
      } catch (vendorError) {
        // Log error but don't fail the product creation
        console.error("[Vendor Inventory Update Error]", vendorError.message);
        console.log("[Product Created Successfully] Vendor inventory update failed, but product was saved.");
      }
    }

    res.status(201).json(product);
  } catch (err) {
    console.error(" Product creation error:", err);
    res
      .status(500)
      .json({ message: "Product creation failed", error: err.message });
  }
};

// Get All (by vendor)
const getAllProductsByVendor = async (req, res) => {
  try {
    const vendorId = req.params.vendorId;

    const products = await prisma.product.findMany({
      where: { vendorId }
    });

    // Get reviews for all products
    const productIds = products.map(p => p.id);
    const reviews = await prisma.reviewRating.findMany({
      where: { productId: { in: productIds } }
    });

    // Get brands and categories
    const brandIds = [...new Set(products.map(p => p.brandId).filter(Boolean))];
    const categoryIds = [...new Set(products.map(p => p.categoryId).filter(Boolean))];
    
    const brands = await prisma.brand.findMany({
      where: { id: { in: brandIds } }
    });
    const categories = await prisma.category.findMany({
      where: { id: { in: categoryIds } }
    });

    // Combine data
    const productsWithRelations = products.map(product => {
      const productReviews = reviews.filter(r => r.productId === product.id);
      const brand = brands.find(b => b.id === product.brandId);
      const category = categories.find(c => c.id === product.categoryId);
      
      return {
        ...product,
        reviews: productReviews,
        brand: brand || null,
        category: category || null
      };
    });

    res.json(productsWithRelations);
  } catch (err) {
    console.error("[Get Products by Vendor Error]", err);
    res.status(500).json({ message: "Failed to fetch vendor products", error: err.message });
  }
};

// Get all products with their reviews
const getAllProducts = async (req, res) => {
  try {
    const products = await prisma.product.findMany({
      orderBy: { createdAt: 'desc' }
    });

    // Get reviews for all products
    const productIds = products.map(p => p.id);
    const reviews = await prisma.reviewRating.findMany({
      where: { productId: { in: productIds } }
    });

    // Get brands and categories
    const brandIds = [...new Set(products.map(p => p.brandId).filter(Boolean))];
    const categoryIds = [...new Set(products.map(p => p.categoryId).filter(Boolean))];
    
    const brands = await prisma.brand.findMany({
      where: { id: { in: brandIds } }
    });
    const categories = await prisma.category.findMany({
      where: { id: { in: categoryIds } }
    });

    // Combine data - only include products with brands and categories
    const productsWithRelations = products
      .map(product => {
        const productReviews = reviews.filter(r => r.productId === product.id);
        const brand = brands.find(b => b.id === product.brandId);
        const category = categories.find(c => c.id === product.categoryId);
        
        if (!brand || !category) return null;
        
        return {
          ...product,
          reviews: productReviews,
          brand: brand,
          category: category
        };
      })
      .filter(Boolean);

    res.json(productsWithRelations);
  } catch (err) {
    console.error("[Get Products Error]", err);
    res.status(500).json({ message: "Failed to fetch products", error: err.message });
  }
};

// Get Single
const getSingleProduct = async (req, res) => {
  try {
    const productId = req.params.productId;
    
    const product = await prisma.product.findUnique({
      where: { id: productId }
    });
    if (!product) return res.status(404).json({ message: "Product not found" });

    const reviews = await prisma.reviewRating.findMany({
      where: { productId },
      orderBy: { createdAt: 'desc' }
    });

    res.json({ ...product, reviews });
  } catch (err) {
    console.error("[Get Single Product Error]", err);
    res.status(500).json({ message: "Failed to fetch product", error: err.message });
  }
};


// Update
const updateProduct = async (req, res) => {
  try {
    const productId = req.params.productId;
    const vendorId = req.params.vendorId;

    // Check if product exists
    const existing = await prisma.product.findUnique({
      where: { id: productId }
    });
    if (!existing || existing.vendorId !== vendorId) {
      return res.status(404).json({ message: "Product not found" });
    }

    // 1. Upload new product images if any
    const imageUrls = [];
    for (const file of req.files?.images || []) {
      const imageUrl = saveFileLocally(file);
      imageUrls.push(imageUrl);
    }

    // 2. Upload variant images
    const variantImageMap = {};
    for (const key in req.files) {
      const match = key.match(/^variantImages\[(\d+)\]$/);
      if (match) {
        const index = match[1];
        variantImageMap[index] = [];
        for (const file of req.files[key]) {
          const url = saveFileLocally(file);
          variantImageMap[index].push(url);
        }
      }
    }

    // 3. Handle variants + image merge
    let updatedVariants = existing.variants;
    if (req.body.variants) {
      if (typeof req.body.variants === "string") {
        const parsed = JSON.parse(req.body.variants);
        updatedVariants = parsed.map((variant, index) => ({
          ...variant,
          images: variantImageMap[index] || variant.images || [],
        }));
      } else {
        updatedVariants = req.body.variants.map((variant, index) => ({
          ...variant,
          images: variantImageMap[index] || variant.images || [],
        }));
      }
    }

    // 4. Prepare update data
    const updates = {};
    
    // Handle regular fields
    if (req.body.name !== undefined) updates.name = req.body.name;
    if (req.body.description !== undefined) updates.description = req.body.description;
    if (req.body.itemSize !== undefined) updates.itemSize = req.body.itemSize;
    if (req.body.totalQuantityInStock !== undefined) updates.totalQuantityInStock = parseInt(req.body.totalQuantityInStock);
    if (req.body.brandId !== undefined) updates.brandId = req.body.brandId || null;
    if (req.body.categoryId !== undefined) updates.categoryId = req.body.categoryId || null;
    if (req.body.brand !== undefined) updates.brand = req.body.brand || null;
    if (req.body.category !== undefined) updates.category = req.body.category || null;
    if (req.body.gender !== undefined) updates.gender = req.body.gender || null;
    if (req.body.collection !== undefined) updates.collection = req.body.collection || null;
    if (req.body.retailPrice !== undefined) updates.retailPrice = req.body.retailPrice ? parseFloat(req.body.retailPrice) : null;
    if (req.body.wholesalePrice !== undefined) updates.wholesalePrice = req.body.wholesalePrice ? parseFloat(req.body.wholesalePrice) : null;
    if (req.body.minWholesaleQty !== undefined) updates.minWholesaleQty = req.body.minWholesaleQty ? parseInt(req.body.minWholesaleQty) : null;
    if (req.body.preSalePrice !== undefined) updates.preSalePrice = req.body.preSalePrice ? parseFloat(req.body.preSalePrice) : null;
    if (req.body.status !== undefined) updates.status = req.body.status;
    if (req.body.isFeatured !== undefined) updates.isFeatured = req.body.isFeatured === 'true' || req.body.isFeatured === true;

    // Handle JSON fields
    if (imageUrls.length > 0) {
      updates.images = imageUrls;
    }
    if (updatedVariants.length > 0) {
      updates.variants = updatedVariants;
    }
    if (req.body.tags !== undefined) {
      updates.tags = typeof req.body.tags === 'string' 
        ? JSON.parse(req.body.tags) 
        : req.body.tags;
    }
    if (req.body.appliedCoupons !== undefined) {
      updates.appliedCoupons = typeof req.body.appliedCoupons === 'string'
        ? JSON.parse(req.body.appliedCoupons)
        : req.body.appliedCoupons;
    }
    if (req.body.discount !== undefined) {
      updates.discount = req.body.discount 
        ? (typeof req.body.discount === 'string' ? JSON.parse(req.body.discount) : req.body.discount)
        : null;
    }
    if (req.body.preSalePeriod !== undefined) {
      updates.preSalePeriod = req.body.preSalePeriod
        ? (typeof req.body.preSalePeriod === 'string' ? JSON.parse(req.body.preSalePeriod) : req.body.preSalePeriod)
        : null;
    }

    const updatedProduct = await prisma.product.update({
      where: { id: productId },
      data: updates
    });

    // Update vendor inventory (non-blocking)
    if (vendorId && process.env.VENDOR_SERVICE_URL) {
      try {
        await updateVendorInventoryAndCategories(
          vendorId,
          req.headers.authorization?.split(" ")[1]
        );
      } catch (vendorError) {
        console.error("[Vendor Inventory Update Error]", vendorError.message);
        console.log("[Product Updated Successfully] Vendor inventory update failed, but product was saved.");
      }
    }

    res.json(updatedProduct);
  } catch (err) {
    console.error("❌ Product update error:", err);
    res.status(500).json({ message: "Update failed", error: err.message });
  }
};

// Delete
const deleteProduct = async (req, res) => {
  try {
    const existing = await prisma.product.findUnique({
      where: { id: req.params.productId }
    });
    
    if (!existing || existing.vendorId !== req.params.vendorId) {
      return res.status(404).json({ message: "Product not found" });
    }

    await prisma.product.delete({
      where: { id: req.params.productId }
    });

    // Update vendor inventory (non-blocking)
    if (req.params.vendorId && process.env.VENDOR_SERVICE_URL) {
      try {
        await updateVendorInventoryAndCategories(
          req.params.vendorId,
          req.headers.authorization?.split(" ")[1]
        );
      } catch (vendorError) {
        console.error("[Vendor Inventory Update Error]", vendorError.message);
        console.log("[Product Deleted Successfully] Vendor inventory update failed, but product was deleted.");
      }
    }
    
    res.json({ message: "Product deleted" });
  } catch (err) {
    res.status(500).json({ message: "Delete failed", error: err.message });
  }
};

const applyCouponToProducts = async (req, res) => {
  const { couponId, updates } = req.body;

  try {
    // Get all products with this coupon
    const products = await prisma.product.findMany();
    
    const productsToUpdate = products.filter(product => {
      const coupons = typeof product.appliedCoupons === 'string' 
        ? JSON.parse(product.appliedCoupons) 
        : (product.appliedCoupons || []);
      return coupons.some(c => c.couponId === couponId);
    });

    // Update each product
    for (const product of productsToUpdate) {
      const coupons = typeof product.appliedCoupons === 'string' 
        ? JSON.parse(product.appliedCoupons) 
        : (product.appliedCoupons || []);
      
      const updatedCoupons = coupons.map(c => 
        c.couponId === couponId ? { ...c, ...updates, couponId } : c
      );

      await prisma.product.update({
        where: { id: product.id },
        data: { appliedCoupons: updatedCoupons }
      });
    }

    res.status(200).json({ message: "Coupon updated in products" });
  } catch (err) {
    console.error("Error updating coupon in products:", err);
    res
      .status(500)
      .json({
        message: "Failed to apply coupon to products",
        error: err.message,
      });
  }
};

// Remove coupon from all products
const removeCouponFromProducts = async (req, res) => {
  const { couponId } = req.body;

  try {
    // Get all products with this coupon
    const products = await prisma.product.findMany();
    
    const productsToUpdate = products.filter(product => {
      const coupons = typeof product.appliedCoupons === 'string' 
        ? JSON.parse(product.appliedCoupons) 
        : (product.appliedCoupons || []);
      return coupons.some(c => c.couponId === couponId);
    });

    // Update each product
    for (const product of productsToUpdate) {
      const coupons = typeof product.appliedCoupons === 'string' 
        ? JSON.parse(product.appliedCoupons) 
        : (product.appliedCoupons || []);
      
      const updatedCoupons = coupons.filter(c => c.couponId !== couponId);

      await prisma.product.update({
        where: { id: product.id },
        data: { appliedCoupons: updatedCoupons }
      });
    }

    res.status(200).json({ message: "Coupon removed from products" });
  } catch (err) {
    res.status(500).json({ message: "Failed to remove coupon from products" });
  }
};

const getProductsByCategory = async (req, res) => {
  const { vendorId, categoryId } = req.params;
  try {
    const products = await prisma.product.findMany({
      where: { vendorId, categoryId }
    });
    res.json(products);
  } catch (err) {
    res
      .status(500)
      .json({ message: "Failed to fetch products", error: err.message });
  }
};

const disableProductsByCategory = async (req, res) => {
  const { vendorId, category } = req.params;

  try {
    // Find products first
    const products = await prisma.product.findMany({
      where: { vendorId, category }
    });

    if (!products.length) {
      return res
        .status(404)
        .json({ message: "No products found for this category" });
    }

    // Toggle each product's status
    const updatePromises = products.map(product => {
      const newStatus = product.status === "active" ? "inactive" : "active";
      return prisma.product.update({
        where: { id: product.id },
        data: { status: newStatus }
      });
    });

    await Promise.all(updatePromises);

    res.json({
      message: "Products status toggled for category",
      toggledCount: products.length,
    });
  } catch (err) {
    console.error("[Toggle Product Status Error]", err);
    res
      .status(500)
      .json({ message: "Failed to toggle products", error: err.message });
  }
};

const updateProductsCategory = async (req, res) => {
  const { vendorId } = req.params;
  const { oldCategory, newCategory } = req.body;

  try {
    const products = await prisma.product.findMany({
      where: { vendorId, category: oldCategory }
    });

    const updatePromises = products.map(product =>
      prisma.product.update({
        where: { id: product.id },
        data: { category: newCategory }
      })
    );

    await Promise.all(updatePromises);

    res.json({
      message: `Updated ${products.length} products category`,
      modifiedCount: products.length,
    });
  } catch (err) {
    console.error("[Product Category Update Error]", err);
    res
      .status(500)
      .json({
        message: "Failed to update products category",
        error: err.message,
      });
  }
};

const updateProductQuantity = async (req, res) => {
  const { productId } = req.params;
  const { quantity, variantIndex } = req.body;
  
  // Validate inputs
  if (isNaN(variantIndex) || variantIndex < 0) {
    return res.status(400).json({ 
      success: false,
      message: 'Variant index must be a positive number' 
    });
  }

  if (typeof quantity !== 'number' || quantity < 0) {
    return res.status(400).json({ 
      success: false,
      message: 'Quantity must be a positive number' 
    });
  }

  try {
    // Find the product
    const product = await prisma.product.findUnique({
      where: { id: productId }
    });
    if (!product) {
      return res.status(404).json({ 
        success: false,
        message: 'Product not found' 
      });
    }
    
    // Parse variants JSON
    const variants = typeof product.variants === 'string' 
      ? JSON.parse(product.variants) 
      : (product.variants || []);

    // Check if variant exists at index
    if (variantIndex >= variants.length) {
      return res.status(404).json({ 
        success: false,
        message: `Variant at index ${variantIndex} not found`,
        availableVariants: variants.length
      });
    }

    // Get the variant
    const variant = variants[variantIndex];

    // Update variant
    variant.quantity -= quantity;
    variant.inStock = variant.quantity > 0;

    // Update total quantity
    const newTotalQuantity = product.totalQuantityInStock - quantity;

    // Save changes
    const updatedProduct = await prisma.product.update({
      where: { id: productId },
      data: {
        variants: variants,
        totalQuantityInStock: newTotalQuantity
      }
    });

    return res.status(200).json({
      success: true,
      message: 'Variant quantity updated successfully',
      data: {
        productId: updatedProduct.id,
        variantIndex: parseInt(variantIndex),
        newQuantity: variant.quantity,
        totalQuantity: updatedProduct.totalQuantityInStock,
        variantDetails: {
          size: variant.size,
          color: variant.color
        }
      }
    });

  } catch (error) {
    console.error('Error updating variant quantity:', error);
    return res.status(500).json({ 
      success: false,
      message: 'Internal server error',
      error: process.env.NODE_ENV === 'development' ? error.message : undefined
    });
  }
};

// Get products by category name (for frontend)
const getProductsByCategoryName = async (req, res) => {
  try {
    const { categoryName } = req.params;

    // Find the category by name (case insensitive)
    const categories = await prisma.category.findMany();
    const category = categories.find(c => 
      c.title.toLowerCase().includes(categoryName.toLowerCase())
    );
    
    if (!category) {
      return res.status(404).json({ message: "Category not found" });
    }
 
    // Fetch products belonging to that category
    const products = await prisma.product.findMany({
      where: {
        categoryId: category.id,
        status: "active",
      }
    });
   
    res.status(200).json(products);
  } catch (err) {
    console.error("Error fetching products by category:", err);
    res.status(500).json({ message: "Failed to fetch products", error: err.message });
  }
};


// Get featured products (for frontend)
const getFeaturedProducts = async (req, res) => {
  try {
    const limit = parseInt(req.query.limit) || 6;

    const products = await prisma.product.findMany({
      where: {
        status: "active",
        isFeatured: true
      },
      take: limit,
      orderBy: { createdAt: 'desc' }
    });

    // Get categories for products
    const categoryIds = [...new Set(products.map(p => p.categoryId).filter(Boolean))];
    const categories = await prisma.category.findMany({
      where: { id: { in: categoryIds } }
    });

    // Add category info to products
    const productsWithCategories = products.map(product => {
      const category = categories.find(c => c.id === product.categoryId);
      return {
        ...product,
        categoryId: category ? { id: category.id, title: category.title, image: category.image } : null
      };
    });

    res.status(200).json(productsWithCategories);
  } catch (err) {
    console.error("Error fetching featured products:", err);
    res.status(500).json({ message: "Failed to fetch featured products", error: err.message });
  }
};


// Get all products for frontend (simplified)
const getAllProductsForFrontend = async (req, res) => {
  try {
    const { category, limit = 20, page = 1 } = req.query;
    const skip = (parseInt(page) - 1) * parseInt(limit);
    
    // Get all active products
    let products = await prisma.product.findMany({
      where: { status: "active" },
      skip: skip,
      take: parseInt(limit),
      orderBy: { createdAt: 'desc' }
    });

    // Filter by category if provided
    if (category) {
      const categories = await prisma.category.findMany();
      const matchingCategory = categories.find(c => 
        c.title.toLowerCase().includes(category.toLowerCase())
      );
      
      if (matchingCategory) {
        products = products.filter(p => p.categoryId === matchingCategory.id);
      }
    }

    // Get categories and reviews
    const categoryIds = [...new Set(products.map(p => p.categoryId).filter(Boolean))];
    const categories = await prisma.category.findMany({
      where: { id: { in: categoryIds } }
    });

    const productIds = products.map(p => p.id);
    const reviews = await prisma.reviewRating.findMany({
      where: { productId: { in: productIds } }
    });

    // Combine data
    const productsWithData = products.map(product => {
      const productReviews = reviews.filter(r => r.productId === product.id);
      const category = categories.find(c => c.id === product.categoryId);
      
      // Calculate average rating
      const ratings = productReviews.map(r => r.rating);
      const averageRating = ratings.length > 0 
        ? ratings.reduce((a, b) => a + b, 0) / ratings.length 
        : 0;
      
      return {
        id: product.id,
        name: product.name,
        description: product.description,
        images: product.images,
        retailPrice: product.retailPrice,
        discount: product.discount,
        itemSize: product.itemSize,
        totalQuantityInStock: product.totalQuantityInStock,
        averageRating: Math.round(averageRating * 10) / 10,
        totalReviews: productReviews.length,
        category: category || null,
        status: product.status,
        createdAt: product.createdAt
      };
    });

    res.json(productsWithData);
  } catch (err) {
    console.error("[Get All Products for Frontend Error]", err);
    res.status(500).json({ message: "Failed to fetch products", error: err.message });
  }
};

// Toggle featured status (admin only)
const toggleFeaturedStatus = async (req, res) => {
  try {
    const { productId } = req.params;
    
    const product = await prisma.product.findUnique({
      where: { id: productId }
    });
    if (!product) {
      return res.status(404).json({ 
        success: false,
        message: 'Product not found' 
      });
    }

    const updatedProduct = await prisma.product.update({
      where: { id: productId },
      data: { isFeatured: !product.isFeatured }
    });

    res.json({ 
      success: true,
      message: `Product ${updatedProduct.isFeatured ? 'marked as' : 'removed from'} featured`,
      product: {
        id: updatedProduct.id,
        name: updatedProduct.name,
        isFeatured: updatedProduct.isFeatured
      }
    });
  } catch (error) {
    console.error('[Toggle Featured Status Error]', error);
    res.status(500).json({ 
      success: false,
      message: 'Failed to toggle featured status',
      error: process.env.NODE_ENV === 'development' ? error.message : undefined
    });
  }
};

module.exports = {
  createProduct,
  getAllProductsByVendor,
  getSingleProduct,
  updateProduct,
  deleteProduct,
  applyCouponToProducts,
  removeCouponFromProducts,
  getProductsByCategory,
  disableProductsByCategory,
  updateProductsCategory,
  getAllProducts,
  updateProductQuantity,
  getProductsByCategoryName,
  getFeaturedProducts,
  getAllProductsForFrontend,
  toggleFeaturedStatus
};
