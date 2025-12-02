const mongoose = require('mongoose');

const cartItemSchema = new mongoose.Schema({
  productId: { type: String, required: true },
  vendorId: { type: String, required: true },
  productName: String,
  productImage: String,
  price: { type: Number, required: true },
  quantity: { type: Number, default: 1 },
  variantIndex: { type: Number, default: 0 }
});

const cartSchema = new mongoose.Schema({
  userId: { type: String, required: true, unique: true },
  items: [cartItemSchema],
  totalPrice: { type: Number, default: 0 }
}, { timestamps: true });

module.exports = mongoose.model('Cart', cartSchema); 