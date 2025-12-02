const mongoose = require('mongoose');

const variantSchema = new mongoose.Schema({
  images: [String], 
  size: String,
  color: String,
  quantity: { type: Number, default: 0 },
  inStock: { type: Boolean, default: true }
}, { _id: false });


 
const productSchema = new mongoose.Schema({
  vendorId: { type: String },
  brandId: { type: mongoose.Schema.Types.ObjectId, ref: 'Brand' },
  categoryId: { type: mongoose.Schema.Types.ObjectId, ref: 'Category' },
  
  // Product Information
  name: { type: String, required: true },
  description: { type: String },
  itemSize: String,
  totalQuantityInStock: { type: Number, default: 0 },

  // Product Images
  images: [String],

  // Inventory Variants
  variants: [variantSchema],

  // Other Information
  brand: String,
  category: String,
  gender: { type: String, enum: ['Male', 'Female', 'Unisex'] },
  collection: String,
  tags: [String],

  // Price & Discount
  retailPrice: Number,
  wholesalePrice: Number,
  minWholesaleQty: Number,
  preSalePrice: Number,
  preSalePeriod: {
    start: Date,
    end: Date
  },
  discount: {
    type: {
      type: String, // e.g., 'percentage', 'flat'
      enum: ['percentage', 'flat'],
    },
    value: Number
  },

  appliedCoupons: [{
    couponId: String,
    couponType: String,
    code: String,
    discountValue: Number,
    startDate: Date,
    endDate: Date
  }],

  // Product status
  status: { type: String, enum: ['active', 'inactive'], default: 'active' },
  isFeatured: { type: Boolean, default: false }
  
}, { timestamps: true });

productSchema.pre(/^find/, function (next) {
  this.populate("brandId");
  this.populate("categoryId");
  next();
});


module.exports = mongoose.model('Product', productSchema);
