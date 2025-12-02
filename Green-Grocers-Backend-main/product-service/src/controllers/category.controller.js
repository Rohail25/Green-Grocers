const prisma = require('../utils/prisma');
const { saveFileLocally } = require('../utils/localFileUploader');

// CREATE category
const createCategory = async (req, res) => {
  const { title } = req.body;
  try {
    let imageUrl;
    if (req.file) {
      imageUrl = saveFileLocally(req.file);
    }
    const category = await prisma.category.create({
      data: {
        title,
        image: imageUrl || "",
      }
    });
    res.status(201).json({ message: 'Category created', category });
  } catch (err) {
    console.error('[Create Category Error]', err);
    res.status(500).json({ message: 'Failed to create category' });
  }
};

// GET all categories
const getCategories = async (req, res) => {
  try {
    const categories = await prisma.category.findMany({
      orderBy: { createdAt: 'desc' }
    });
    res.json(categories);
  } catch (err) {
    console.error('[Get Categories Error]', err);
    res.status(500).json({ message: 'Failed to fetch categories' });
  }
};

// GET single category
const getCategory = async (req, res) => {
  const { categoryId } = req.params;
  try {
    const category = await prisma.category.findUnique({
      where: { id: categoryId }
    });
    if (!category) return res.status(404).json({ message: 'Category not found' });
    res.json(category);
  } catch (err) {
    console.error('[Get Category Error]', err);
    res.status(500).json({ message: 'Failed to fetch category' });
  }
};

// UPDATE category
const updateCategory = async (req, res) => {
  const { categoryId } = req.params;
  const { title } = req.body;
  try {
    const updates = {};
    if (title !== undefined) updates.title = title;
    if (req.file) {
      updates.image = saveFileLocally(req.file);
    }
    // Check if category exists first
    const existing = await prisma.category.findUnique({
      where: { id: categoryId }
    });
    if (!existing) return res.status(404).json({ message: 'Category not found' });

    const category = await prisma.category.update({
      where: { id: categoryId },
      data: updates
    });
    if (!category) return res.status(404).json({ message: 'Category not found' });
    res.json({ message: 'Category updated', category });
  } catch (err) {
    console.error('[Update Category Error]', err);
    res.status(500).json({ message: 'Failed to update category' });
  }
};

// DELETE category
const deleteCategory = async (req, res) => {
  const { categoryId } = req.params;
  try {
    // Check if category exists first
    const existing = await prisma.category.findUnique({
      where: { id: categoryId }
    });
    if (!existing) return res.status(404).json({ message: 'Category not found' });

    await prisma.category.delete({
      where: { id: categoryId }
    });
    
    const category = existing; // Return the deleted category info
    if (!category) return res.status(404).json({ message: 'Category not found' });
    res.json({ message: 'Category deleted' });
  } catch (err) {
    console.error('[Delete Category Error]', err);
    res.status(500).json({ message: 'Failed to delete category' });
  }
};

module.exports = {
  createCategory,
  getCategories,
  getCategory,
  updateCategory,
  deleteCategory
}; 