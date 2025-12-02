const prisma = require("../utils/prisma");
const { saveFileLocally } = require("../utils/localFileUploader");

// CREATE brand
const createBrand = async (req, res) => {
  const { title, description } = req.body;
  try {
    let imageUrl;
    if (req.file) {
      imageUrl = saveFileLocally(req.file);
    }
    const brand = await prisma.brand.create({
      data: {
        title,
        image: imageUrl || "",
        description: description || null,
      }
    });
    res.status(201).json({ message: "Brand created", brand });
  } catch (err) {
    console.error("[Create Brand Error]", err);
    res.status(500).json({ message: "Failed to create brand" });
  }
};

// GET all brands
const getBrands = async (req, res) => {
  try {
    const brands = await prisma.brand.findMany({
      orderBy: { createdAt: 'desc' }
    });
    res.json(brands);
  } catch (err) {
    console.error("[Get Brands Error]", err);
    res.status(500).json({ message: "Failed to fetch brands" });
  }
};

// GET single brand
const getBrand = async (req, res) => {
  const { brandId } = req.params;
  try {
    const brand = await prisma.brand.findUnique({
      where: { id: brandId }
    });
    if (!brand) return res.status(404).json({ message: "Brand not found" });
    res.json(brand);
  } catch (err) {
    console.error("[Get Brand Error]", err);
    res.status(500).json({ message: "Failed to fetch brand" });
  }
};

// UPDATE brand
const updateBrand = async (req, res) => {
  const { brandId } = req.params;
  const { title, description } = req.body;
  try {
    const updates = {};
    if (title !== undefined) updates.title = title;
    if (description !== undefined) updates.description = description;
    if (req.file) {
      updates.image = saveFileLocally(req.file);
    }
    // Check if brand exists first
    const existing = await prisma.brand.findUnique({
      where: { id: brandId }
    });
    if (!existing) return res.status(404).json({ message: "Brand not found" });

    const brand = await prisma.brand.update({
      where: { id: brandId },
      data: updates
    });
    if (!brand) return res.status(404).json({ message: "Brand not found" });
    res.json({ message: "Brand updated", brand });
  } catch (err) {
    console.error("[Update Brand Error]", err);
    res.status(500).json({ message: "Failed to update brand" });
  }
};

// DELETE brand
const deleteBrand = async (req, res) => {
  const { brandId } = req.params;
  try {
    // Check if brand exists first
    const existing = await prisma.brand.findUnique({
      where: { id: brandId }
    });
    if (!existing) return res.status(404).json({ message: "Brand not found" });

    await prisma.brand.delete({
      where: { id: brandId }
    });
    
    const brand = existing; // Return the deleted brand info
    if (!brand) return res.status(404).json({ message: "Brand not found" });
    res.json({ message: "Brand deleted" });
  } catch (err) {
    console.error("[Delete Brand Error]", err);
    res.status(500).json({ message: "Failed to delete brand" });
  }
};

module.exports = {
  createBrand,
  getBrands,
  getBrand,
  updateBrand,
  deleteBrand,
};
