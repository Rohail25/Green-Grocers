const prisma = require('../utils/prisma');
const { saveFileLocally } = require('../utils/localFileUploader');

// Create Package
const createPackage = async (req, res) => {
  try {
    let imageUrl;
    if (req.file) {
      imageUrl = saveFileLocally(req.file);
    }

    // Parse JSON fields if they come as strings
    const items = typeof req.body.items === 'string' 
      ? JSON.parse(req.body.items) 
      : (req.body.items || []);
    const discount = typeof req.body.discount === 'string' 
      ? JSON.parse(req.body.discount) 
      : (req.body.discount || { type: 'percentage', value: 0 });
    const tags = typeof req.body.tags === 'string' 
      ? JSON.parse(req.body.tags) 
      : (req.body.tags || []);

    const packageData = {
      name: req.body.name,
      description: req.body.description || null,
      image: imageUrl || null,
      packageDay: req.body.packageDay,
      items: items,
      retailPrice: parseFloat(req.body.retailPrice),
      discount: discount,
      status: req.body.status || 'active',
      isFeatured: req.body.isFeatured === 'true' || req.body.isFeatured === true,
      tags: tags,
      category: req.body.category || null,
      rating: parseFloat(req.body.rating) || 0,
      totalOrders: parseInt(req.body.totalOrders) || 0,
    };

    const newPackage = await prisma.package.create({
      data: packageData
    });
    res.status(201).json({ message: 'Package created successfully', package: newPackage });
  } catch (err) {
    console.error('[Create Package Error]', err);
    res.status(500).json({ message: 'Failed to create package', error: err.message });
  }
};

// Get All Packages
const getAllPackages = async (req, res) => {
  try {
    const { status = 'active', packageDay, limit = 20, page = 1 } = req.query;
    const skip = (parseInt(page) - 1) * parseInt(limit);

    const filter = { status };
    if (packageDay) {
      filter.packageDay = packageDay;
    }

    const packages = await prisma.package.findMany({
      where: filter,
      orderBy: { createdAt: 'desc' },
      skip: skip,
      take: parseInt(limit)
    });

    res.json(packages);
  } catch (err) {
    console.error('[Get All Packages Error]', err);
    res.status(500).json({ message: 'Failed to fetch packages', error: err.message });
  }
};

// Get Featured Packages (for frontend)
const getFeaturedPackages = async (req, res) => {
  try {
    const { limit = 6 } = req.query;

    const packages = await prisma.package.findMany({
      where: {
        status: 'active',
        isFeatured: true
      },
      orderBy: [
        { rating: 'desc' },
        { totalOrders: 'desc' },
        { createdAt: 'desc' }
      ],
      take: parseInt(limit)
    });

    res.json(packages);
  } catch (err) {
    console.error('[Get Featured Packages Error]', err);
    res.status(500).json({ message: 'Failed to fetch featured packages', error: err.message });
  }
};

// Get Packages by Day
const getPackagesByDay = async (req, res) => {
  try {
    const { day } = req.params;
    
    const packages = await prisma.package.findMany({
      where: {
        packageDay: day,
        status: 'active'
      },
      orderBy: { rating: 'desc' }
    });

    res.json(packages);
  } catch (err) {
    console.error('[Get Packages by Day Error]', err);
    res.status(500).json({ message: 'Failed to fetch packages by day', error: err.message });
  }
};

// Get Single Package
const getSinglePackage = async (req, res) => {
  try {
    const { packageId } = req.params;
    
    const package = await prisma.package.findUnique({
      where: { id: packageId }
    });
    if (!package) {
      return res.status(404).json({ message: 'Package not found' });
    }

    res.json(package);
  } catch (err) {
    console.error('[Get Single Package Error]', err);
    res.status(500).json({ message: 'Failed to fetch package', error: err.message });
  }
};

// Update Package
const updatePackage = async (req, res) => {
  try {
    const { packageId } = req.params;
    
    // Check if package exists first
    const existing = await prisma.package.findUnique({
      where: { id: packageId }
    });
    if (!existing) {
      return res.status(404).json({ message: 'Package not found' });
    }

    let updates = {};
    
    // Handle regular fields
    if (req.body.name !== undefined) updates.name = req.body.name;
    if (req.body.description !== undefined) updates.description = req.body.description;
    if (req.body.packageDay !== undefined) updates.packageDay = req.body.packageDay;
    if (req.body.retailPrice !== undefined) updates.retailPrice = parseFloat(req.body.retailPrice);
    if (req.body.status !== undefined) updates.status = req.body.status;
    if (req.body.isFeatured !== undefined) updates.isFeatured = req.body.isFeatured === 'true' || req.body.isFeatured === true;
    if (req.body.category !== undefined) updates.category = req.body.category;
    if (req.body.rating !== undefined) updates.rating = parseFloat(req.body.rating);
    if (req.body.totalOrders !== undefined) updates.totalOrders = parseInt(req.body.totalOrders);
    
    // Handle JSON fields
    if (req.body.items !== undefined) {
      updates.items = typeof req.body.items === 'string' 
        ? JSON.parse(req.body.items) 
        : req.body.items;
    }
    if (req.body.discount !== undefined) {
      updates.discount = typeof req.body.discount === 'string' 
        ? JSON.parse(req.body.discount) 
        : req.body.discount;
    }
    if (req.body.tags !== undefined) {
      updates.tags = typeof req.body.tags === 'string' 
        ? JSON.parse(req.body.tags) 
        : req.body.tags;
    }
    
    if (req.file) {
      updates.image = saveFileLocally(req.file);
    }

    const updatedPackage = await prisma.package.update({
      where: { id: packageId },
      data: updates
    });

    if (!updatedPackage) {
      return res.status(404).json({ message: 'Package not found' });
    }

    res.json({ message: 'Package updated successfully', package: updatedPackage });
  } catch (err) {
    console.error('[Update Package Error]', err);
    res.status(500).json({ message: 'Failed to update package', error: err.message });
  }
};

// Delete Package
const deletePackage = async (req, res) => {
  try {
    const { packageId } = req.params;
    
    // Check if package exists first
    const existing = await prisma.package.findUnique({
      where: { id: packageId }
    });
    if (!existing) {
      return res.status(404).json({ message: 'Package not found' });
    }

    await prisma.package.delete({
      where: { id: packageId }
    });
    
    const deletedPackage = existing;
    if (!deletedPackage) {
      return res.status(404).json({ message: 'Package not found' });
    }

    res.json({ message: 'Package deleted successfully' });
  } catch (err) {
    console.error('[Delete Package Error]', err);
    res.status(500).json({ message: 'Failed to delete package', error: err.message });
  }
};

// Update Package Rating
const updatePackageRating = async (req, res) => {
  try {
    const { packageId } = req.params;
    const { rating } = req.body;

    const package = await prisma.package.findUnique({
      where: { id: packageId }
    });
    if (!package) {
      return res.status(404).json({ message: 'Package not found' });
    }

    // Simple average rating calculation
    const currentRating = Number(package.rating);
    const newRating = (currentRating + parseFloat(rating)) / 2;
    const roundedRating = Math.round(newRating * 10) / 10; // Round to 1 decimal place
    const newTotalOrders = package.totalOrders + 1;

    await prisma.package.update({
      where: { id: packageId },
      data: {
        rating: roundedRating,
        totalOrders: newTotalOrders
      }
    });
    
    // Fetch updated package
    const updatedPackage = await prisma.package.findUnique({
      where: { id: packageId }
    });

    res.json({ 
      message: 'Package rating updated successfully', 
      package: updatedPackage 
    });
  } catch (err) {
    console.error('[Update Package Rating Error]', err);
    res.status(500).json({ message: 'Failed to update package rating', error: err.message });
  }
};

module.exports = {
  createPackage,
  getAllPackages,
  getFeaturedPackages,
  getPackagesByDay,
  getSinglePackage,
  updatePackage,
  deletePackage,
  updatePackageRating
};
