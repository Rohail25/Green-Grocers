# Image Upload Fix - Local Storage

## Problem
Images were being uploaded to AWS S3 and then deleted from local storage. The database stored the S3 URL, but files were not saved in the local `uploads` folder.

## Solution
Changed all image uploads from S3 to local file storage. Images are now saved in the `product-service/src/uploads/` folder and remain there permanently.

## Changes Made

### 1. Created Local File Uploader Utility
**File**: `src/utils/localFileUploader.js`
- `saveFileLocally()` - Saves file locally and returns path
- `getFileUrl()` - Constructs full URL for local files
- `deleteLocalFile()` - Helper to delete local files when needed

### 2. Updated Controllers

#### Brand Controller (`src/controllers/brand.controller.js`)
- ✅ Changed from `uploadFileToS3()` to `saveFileLocally()`
- ✅ Images saved to `/uploads/` folder

#### Category Controller (`src/controllers/category.controller.js`)
- ✅ Changed from `uploadFileToS3()` to `saveFileLocally()`
- ✅ Images saved to `/uploads/` folder

#### Package Controller (`src/controllers/package.controller.js`)
- ✅ Changed from `uploadFileToS3()` to `saveFileLocally()`
- ✅ Images saved to `/uploads/` folder

#### Product Controller (`src/controllers/product.controller.js`)
- ✅ Changed from `uploadFileToS3()` to `saveFileLocally()`
- ✅ Main product images saved locally
- ✅ Variant images saved locally

## How It Works

### File Upload Flow:
1. **Multer Middleware** (`src/middlewares/upload.middleware.js`)
   - Receives uploaded file
   - Saves to `src/uploads/` folder with unique filename
   - Example: `1234567890-image.jpg`

2. **Local File Uploader** (`src/utils/localFileUploader.js`)
   - Extracts filename from multer file object
   - Returns path: `/uploads/1234567890-image.jpg`

3. **Database Storage**
   - Path stored in database: `/uploads/1234567890-image.jpg`

4. **Static File Serving** (`src/app.js` - Line 16)
   - Express serves files from `src/uploads/` folder
   - Files accessible via: `http://localhost:3003/uploads/1234567890-image.jpg`

## File Structure

```
product-service/
├── src/
│   ├── uploads/              ← Images saved here
│   │   ├── 1234567890-image.jpg
│   │   ├── 1234567891-brand.jpg
│   │   └── ...
│   ├── controllers/
│   │   ├── brand.controller.js      ← Uses local storage
│   │   ├── category.controller.js   ← Uses local storage
│   │   ├── package.controller.js    ← Uses local storage
│   │   └── product.controller.js    ← Uses local storage
│   └── utils/
│       └── localFileUploader.js     ← New utility
```

## Database Storage Format

**Before (S3)**:
```json
{
  "image": "https://bucket.s3.amazonaws.com/products/uuid.jpg"
}
```

**After (Local)**:
```json
{
  "image": "/uploads/1234567890-image.jpg"
}
```

## Accessing Images

### Local Development:
```
http://localhost:3003/uploads/1234567890-image.jpg
```

### Production:
Set `BASE_URL` in `.env`:
```env
BASE_URL=https://yourdomain.com
```

Then images will be:
```
https://yourdomain.com/uploads/1234567890-image.jpg
```

## Benefits

✅ **Files Stay Local**: Images remain in uploads folder
✅ **No S3 Dependency**: No need for AWS credentials for local development
✅ **Faster Development**: No network latency
✅ **Easy Access**: Direct file access via HTTP

## Migration Notes

- **Old S3 URLs**: If you have existing records with S3 URLs, they will still work (full URLs are preserved)
- **New Uploads**: All new uploads use local storage
- **File Cleanup**: Old S3 uploads are no longer created/deleted

## Testing

1. **Upload Brand Image**:
   ```bash
   POST /api/brands
   FormData: { image: <file>, title: "Test Brand" }
   ```
   Check: `src/uploads/` folder should contain the image file

2. **Verify Database**:
   ```sql
   SELECT image FROM brands WHERE id = '...';
   -- Should return: /uploads/1234567890-image.jpg
   ```

3. **Access Image**:
   ```
   GET http://localhost:3003/uploads/1234567890-image.jpg
   ```

## Notes

- **File Size Limit**: 5MB per file (configured in `upload.middleware.js`)
- **Allowed Types**: JPEG, JPG, PNG, GIF, WEBP
- **Folder Auto-Create**: `uploads/` folder is created automatically if it doesn't exist




