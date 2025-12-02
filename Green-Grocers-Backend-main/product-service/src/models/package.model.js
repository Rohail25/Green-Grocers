const mongoose = require('mongoose');

const packageItemSchema = new mongoose.Schema({
  productId: { type: mongoose.Schema.Types.ObjectId, ref: 'Product' },
  productName: { type: String, required: true },
  quantity: { type: String, required: true }, // e.g., "1kg", "2L", "1pc"
  price: { type: Number, required: true }
}, { _id: false });

const packageSchema = new mongoose.Schema({
  name: { type: String, required: true },
  description: { type: String },
  image: { type: String },
  packageDay: { 
    type: String, 
    enum: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
    required: true 
  },
  items: [packageItemSchema],
  retailPrice: { type: Number, required: true },
  discount: {
    type: { type: String, enum: ['percentage', 'flat'], default: 'percentage' },
    value: { type: Number, default: 0 }
  },
  status: { type: String, enum: ['active', 'inactive'], default: 'active' },
  isFeatured: { type: Boolean, default: false },
  tags: [String],
  category: { type: String }, // e.g., "Healthy", "Fresh", "Family"
  rating: { type: Number, default: 0 },
  totalOrders: { type: Number, default: 0 }
}, { timestamps: true });

module.exports = mongoose.model('Package', packageSchema);
