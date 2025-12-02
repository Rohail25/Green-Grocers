const mongoose = require('mongoose');

const couponSchema = new mongoose.Schema({
  couponType: { type: String, enum: ['Discount', 'FreeShipping', 'BuyXGetY'], },
  name: { type: String, },
  code: { type: String, },
  appliesTo: { type: String, enum: ['AllProducts', 'SelectedProducts'], },
  productIds: [{ type: mongoose.Schema.Types.ObjectId, ref: 'Product' }], // for selected products
  buyXProductIds: [{ type: mongoose.Schema.Types.ObjectId, ref: 'Product' }],
  getYProductIds: [{ type: mongoose.Schema.Types.ObjectId, ref: 'Product' }],
  discountValue: { type: Number }, // For Discount
  startDate: Date,
  endDate: Date,
  maxRedemptions: Number,
  perCustomerLimit: Number
}, { timestamps: true });
 
const vendorSchema = new mongoose.Schema({
  vendorId: { type: String, required: true, unique: true },
  userId: { type: String, required: true },
  storeName: { type: String, required: true },
  phone: String,
  email: String,
  address: String,
  vendorProfileImage: { type: String },
  vendorBannerImage: { type: String },
  inventoryCount: { type: Number, default: 0 },
  categories: [{ 
    categoryId: { type: mongoose.Schema.Types.ObjectId, ref: 'Category' },
    title: { type: String },
    image: { type: String },
  }],
  description: String,
  status: { type: String, enum: ['pending', 'approved', 'rejected'], default: 'pending' },
  storeEnabled: { type: Boolean, default: true },
  coupons: [couponSchema],

  storeCurrency: { type: String, },
  timezone: { type: String, },
  workHours: { type: String }, // e.g., '8am - 9pm'
  state: { type: String },
  city: { type: String },
  localGovernment: { type: String },
  country: { type: String },
  storeIndustries: [{ type: String }], // Clothing, Beauty care, etc.
  storeAddress: { type: String } // Optional
}, { timestamps: true });

module.exports = mongoose.model('Vendor', vendorSchema);
