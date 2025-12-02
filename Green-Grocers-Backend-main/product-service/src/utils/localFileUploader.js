const path = require('path');
const fs = require('fs');

/**
 * Save uploaded file locally and return the file path
 * @param {Object} file - Multer file object
 * @returns {String} - Relative path to the file (e.g., '/uploads/image-1234567890.jpg')
 */
const saveFileLocally = (file) => {
  if (!file) {
    return null;
  }

  // File is already saved by multer middleware
  // Just return the relative path for storing in database
  const fileName = file.filename;
  const relativePath = `/uploads/${fileName}`;
  
  return relativePath;
};

/**
 * Get full URL path for local file
 * @param {String} filePath - Relative file path from database
 * @returns {String} - Full URL or path
 */
const getFileUrl = (filePath) => {
  if (!filePath) return null;
  
  // If it's already a full URL (from S3), return as is
  if (filePath.startsWith('http://') || filePath.startsWith('https://')) {
    return filePath;
  }
  
  // Otherwise, return as relative path or construct full URL
  // Adjust base URL based on your server configuration
  const baseUrl = process.env.BASE_URL || 'http://localhost:3003';
  return filePath.startsWith('/') ? `${baseUrl}${filePath}` : `${baseUrl}/${filePath}`;
};

/**
 * Delete local file
 * @param {String} filePath - Relative or absolute file path
 */
const deleteLocalFile = (filePath) => {
  if (!filePath) return;
  
  // If it's an S3 URL, don't delete
  if (filePath.startsWith('http://') || filePath.startsWith('https://')) {
    return;
  }
  
  try {
    // Extract filename from path
    const fileName = path.basename(filePath);
    const uploadPath = path.join(__dirname, '..', 'uploads', fileName);
    
    if (fs.existsSync(uploadPath)) {
      fs.unlinkSync(uploadPath);
      console.log(`Deleted local file: ${uploadPath}`);
    }
  } catch (error) {
    console.error(`Error deleting local file ${filePath}:`, error.message);
  }
};

module.exports = {
  saveFileLocally,
  getFileUrl,
  deleteLocalFile
};

