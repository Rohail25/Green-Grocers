/**
 * Category Structure Validator
 * Ensures categories match the original Mongoose schema structure
 */

/**
 * Validate category structure matches the original schema
 * @param {Object} category - Category object to validate
 * @returns {{valid: boolean, errors: string[]}}
 */
const validateCategoryStructure = (category) => {
  const errors = [];

  // Required fields based on original schema
  if (!category.categoryId && !category._id) {
    errors.push('categoryId is required');
  }

  if (!category.title) {
    errors.push('title is required');
  }

  return {
    valid: errors.length === 0,
    errors
  };
};

/**
 * Create a properly structured category object from input
 * Matches the original Mongoose category schema structure
 * @param {Object} categoryData - Raw category data
 * @returns {Object} - Structured category object
 */
const createCategoryObject = (categoryData) => {
  const category = {
    categoryId: categoryData.categoryId || categoryData._id || null,
    title: categoryData.title || '',
    image: categoryData.image || null
  };

  // Validate the structure
  const validation = validateCategoryStructure(category);
  if (!validation.valid) {
    throw new Error(`Invalid category structure: ${validation.errors.join(', ')}`);
  }

  return category;
};

/**
 * Get the expected category structure (for documentation/reference)
 * @returns {Object} - Example category structure
 */
const getCategorySchemaStructure = () => {
  return {
    categoryId: "category-uuid-string",
    title: "Category Name",
    image: "https://example.com/image.jpg"
  };
};

module.exports = {
  validateCategoryStructure,
  createCategoryObject,
  getCategorySchemaStructure
};




