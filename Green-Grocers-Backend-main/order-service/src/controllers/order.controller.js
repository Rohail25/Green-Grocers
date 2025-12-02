const prisma = require("../utils/prisma");
const {
  updateProductQuantityService,
} = require("../utils/comunicateProductService");
const {
  creditUserWallet,
  debitWallet,
} = require("../utils/communicateClientService");
const { CreateAgentEarning } = require("../utils/communicateLogisticService");
const stripe = require("../utils/stripe");

const initiatePayment = async (req, res) => {
  try {
    const { method } = req.body; // WALLET, CARD, BANK, USSD, COD
    const { orderId } = req.params;
    const order = await prisma.order.findUnique({
      where: { id: orderId }
    });

    if (!order) return res.status(404).json({ message: "Order not found" });

    switch (method) {
      case "WALLET":
        // Check wallet balance
        const success = await debitWallet(
          order.userId,
          order.totalAmount,
          req.headers.authorization
        );
        if (!success)
          return res.status(400).json({ message: "Insufficient balance" });

        await prisma.order.update({
          where: { id: orderId },
          data: {
            paymentMethod: "WALLET",
            paymentStatus: "PAID",
            transactionId: `WALLET-${Date.now()}`
          }
        });
        
        const updatedOrder = await prisma.order.findUnique({
          where: { id: orderId }
        });
        return res.json({ message: "Paid via Wallet", order: updatedOrder });

      case "CARD":
        // Stripe example
        const session = await stripe.checkout.sessions.create({
          payment_method_types: ["card"],
          line_items: [
            {
              price_data: {
                currency: "usd", // or ngn if using Paystack
                product_data: {
                  name: `Order ${order.id}`,
                },
                unit_amount: Math.round(Number(order.totalAmount) * 100),
              },
              quantity: 1,
            },
          ],
          mode: "payment",
          success_url: `${process.env.CLIENT_URL}/success?orderId=${order.id}`,
          cancel_url: `${process.env.CLIENT_URL}/cancel?orderId=${order.id}`,
        });

        await prisma.order.update({
          where: { id: orderId },
          data: {
            paymentMethod: "CARD",
            paymentStatus: "PENDING"
          }
        });

        return res.json({ url: session.url });

      case "BANK":
      case "TRANSFER":
      case "USSD":
        // For these, mark pending until manual confirmation or webhook
        await prisma.order.update({
          where: { id: orderId },
          data: {
            paymentMethod: method,
            paymentStatus: "PENDING",
            transactionId: `MANUAL-${Date.now()}`
          }
        });
        
        const updatedOrderManual = await prisma.order.findUnique({
          where: { id: orderId }
        });
        return res.json({
          message: `${method} selected. Awaiting confirmation.`,
          order: updatedOrderManual,
        });

      case "COD":
        await prisma.order.update({
          where: { id: orderId },
          data: {
            paymentMethod: "COD",
            paymentStatus: "PENDING" // marked PAID only after delivery
          }
        });
        
        const updatedOrderCOD = await prisma.order.findUnique({
          where: { id: orderId }
        });
        return res.json({ message: "Cash on Delivery selected", order: updatedOrderCOD });

      default:
        return res.status(400).json({ message: "Invalid payment method" });
    }
  } catch (err) {
    console.error("[Payment Error]", err.message);
    res.status(500).json({ message: "Payment failed", error: err.message });
  }
};

// Stripe webhook
const handleWebhook = async (req, res) => {
  let event;
  try {
    event = stripe.webhooks.constructEvent(
      req.body,
      req.headers["stripe-signature"],
      process.env.STRIPE_WEBHOOK_SECRET
    );
  } catch (err) {
    console.error("⚠️ Webhook signature verification failed:", err.message);
    return res.sendStatus(400);
  }

  if (event.type === "checkout.session.completed") {
    const session = event.data.object;
    const orderId = session.success_url.split("orderId=")[1]; // extract from redirect URL

    const order = await prisma.order.findUnique({
      where: { id: orderId }
    });
    if (order) {
      await prisma.order.update({
        where: { id: orderId },
        data: {
          paymentStatus: "PAID",
          transactionId: session.payment_intent
        }
      });
    }
  }

  res.json({ received: true });
};
const createOrder = async (req, res) => {
  try {
    const {
      userId,
      items,
      vendorId,
      totalAmount,
      customerName,
      paymentStatus,
      shippingAddress,
      discount,
      couponCode,
    } = req.body;

    // Parse JSON fields if needed
    const itemsData = typeof items === 'string' ? JSON.parse(items) : items;
    const shippingAddressData = typeof shippingAddress === 'string' 
      ? JSON.parse(shippingAddress) 
      : shippingAddress;

    const order = await prisma.order.create({
      data: {
        userId,
        vendorId,
        items: itemsData || [],
        totalAmount: parseFloat(totalAmount),
        customerName: customerName || null,
        paymentStatus: paymentStatus || "PENDING",
        purchaseDate: new Date(),
        status: "inprogress",
        orderProgress: "Awaiting Confirmation",
        shippingAddress: shippingAddressData || null,
        deliveryStatus: "Pending",
        discountAmount: discount ? parseFloat(discount) : 0,
        couponCode: couponCode || null,
      }
    });

    for (const item of itemsData || []) {
      try {
        await updateProductQuantityService(
          item.productId,
          item.quantity,
          item.variantIndex
        );
      } catch (productError) {
        console.error("[Product Quantity Update Error]", productError.message);
      }
    }
    
    await prisma.cart.deleteMany({
      where: { userId }
    });
    
    res.status(201).json({ message: "Order created", order });
  } catch (err) {
    console.log("err", err);
    res
      .status(500)
      .json({ message: "Failed to create order", error: err.message });
  }
};

const getVendorOrders = async (req, res) => {
  try {
    const orders = await prisma.order.findMany({
      where: { vendorId: req.params.vendorId }
    });
    res.json(orders);
  } catch (err) {
    res.status(500).json({ message: "Failed to fetch orders" });
  }
};

const updateOrderStatus = async (req, res) => {
  const { status, orderProgress } = req.body;
  const orderId = req.params.orderId;

  const allowedStatuses = [
    "inprogress",
    "assigned",
    "dispatched",
    "delivered",
    "canceled",
  ];
  if (!allowedStatuses.includes(status)) {
    return res.status(400).json({ message: "Invalid status" });
  }

  try {
    const order = await prisma.order.findUnique({
      where: { id: orderId }
    });

    if (!order) return res.status(404).json({ message: "Order not found" });

    // Add null checks for req.user
    if (!req.user) {
      return res.status(401).json({ message: "Unauthorized" });
    }

    // AUTHORIZATION LAYER
    if (["assigned", "dispatched"].includes(status)) {
      if (req.user.platform !== "trivestore") {
        return res
          .status(403)
          .json({ message: "Unauthorized: vendor mismatch" });
      }
    }

    if (["delivered", "canceled"].includes(status)) {
      if (!req.user.logisticsId || req.user.logisticsId !== order.logisticsId) {
        return res
          .status(403)
          .json({ message: "Unauthorized: logistics mismatch" });
      }
    }
    const statusToDeliveryStatus = {
      assigned: "Out for Delivery",
      dispatched: "Out for Delivery",
      inprogress: "Pending",
      delivered: "Delivered",
      canceled: "Failed",
    };
    
    // Parse status history if it exists
    const statusHistory = typeof order.statusHistory === 'string' 
      ? JSON.parse(order.statusHistory) 
      : (order.statusHistory || []);
    
    // Add new status to history
    statusHistory.push({
      status,
      updatedAt: new Date().toISOString(),
      updatedBy: req.user.id || req.user._id || 'system'
    });
    
    // Generate 4-digit code if confirming
    const updates = {
      status,
      orderProgress: orderProgress || `Order marked as ${status}`,
      deliveryStatus: statusToDeliveryStatus[status] || "Pending",
      statusHistory: statusHistory
    };

    if (status === "assigned") {
      updates.authenticationCode = Math.floor(
        1000 + Math.random() * 9000
      ).toString();
      updates.deliveryTimeline = "Tuesday 24/04/24"; // or dynamic
    }

    const updated = await prisma.order.update({
      where: { id: orderId },
      data: updates
    });
    
    if (status === "delivered") {
      try {
        await CreateAgentEarning(updated.id, req.headers.authorization);
      } catch (earnErr) {
        console.error("⚠️ Failed to create earning:", earnErr.message);
        // NOTE: we don't fail order update if earning call fails
      }
    }
    res.status(200).json({ message: "Order status updated", order: updated });
  } catch (err) {
    console.error("[Secure Status Update Error]", err.message);
    res.status(500).json({ message: "Internal server error" });
  }
};

const getOrdersByUser = async (req, res) => {
  const orders = await prisma.order.findMany({
    where: { userId: req.params.userId }
  });
  res.json({ orders });
};

const getOrderById = async (req, res) => {
  const order = await prisma.order.findUnique({
    where: { id: req.params.orderId }
  });
  if (!order) return res.status(404).json({ message: "Order not found" });
  res.json({ order });
};

const requestReturn = async (req, res) => {
  const { orderId } = req.params;
  const { reason } = req.body;

  try {
    const order = await prisma.order.findUnique({
      where: { id: orderId }
    });
    if (!order) return res.status(404).json({ message: "Order not found" });

    // Only the user who placed the order can request a return
    if (req.user.id !== order.userId) {
      return res.status(403).json({ message: "Unauthorized" });
    }

    if (order.isReturnRequested) {
      return res.status(400).json({ message: "Return already requested" });
    }

    await prisma.order.update({
      where: { id: orderId },
      data: {
        isReturnRequested: true,
        returnRequest: {
          isRequested: true,
          reason,
          requestedAt: new Date().toISOString(),
          status: "Pending",
        }
      }
    });

    const updatedOrder = await prisma.order.findUnique({
      where: { id: orderId }
    });

    res.status(200).json({ message: "Return requested successfully", order: updatedOrder });
  } catch (err) {
    console.error(err);
    res.status(500).json({ message: "Internal server error" });
  }
};
const processReturn = async (req, res) => {
  const { orderId } = req.params;
  const { status, refundAmount, processedBy } = req.body;

  const allowedStatuses = ["Approved", "Rejected", "Refunded"];
  if (!allowedStatuses.includes(status)) {
    return res.status(400).json({ message: "Invalid status" });
  }

  try {
    const order = await prisma.order.findUnique({
      where: { id: orderId }
    });
    if (!order) return res.status(404).json({ message: "Order not found" });

    const returnRequest = typeof order.returnRequest === 'string' 
      ? JSON.parse(order.returnRequest) 
      : (order.returnRequest || {});
    
    returnRequest.status = status;
    returnRequest.refundAmount = refundAmount ? parseFloat(refundAmount) : Number(order.totalAmount);
    returnRequest.processedBy = processedBy;
    
    const updateData = {
      returnRequest: returnRequest
    };
    
    if (status === "Refunded") {
      updateData.paymentStatus = "UNPAID"; // Or handle logic based on your payment gateway
      updateData.status = "canceled";
    }
    
    await prisma.order.update({
      where: { id: orderId },
      data: updateData
    });
    
    if (status === "Refunded") {
      try {
        await creditUserWallet(
          order.userId,
          returnRequest.refundAmount,
          `Refund for Order ${order.id}`,
          req.headers.authorization
        );
      } catch (walletError) {
        console.error("[Wallet Credit Error]", walletError.message);
      }
    }
    
    const updatedOrder = await prisma.order.findUnique({
      where: { id: orderId }
    });

    res
      .status(200)
      .json({ message: `Return ${status.toLowerCase()} successfully`, order: updatedOrder });
  } catch (err) {
    console.error(err);
    res.status(500).json({ message: "Internal server error" });
  }
};
const getOrdersByLogistic = async (req, res) => {
  try {
    const { logisticId } = req.params;

    const orders = await prisma.order.findMany({
      where: { logisticsId: logisticId }
    });

    const fulfilled = orders.filter((o) => o.status === "delivered").length;
    const pending = orders.filter((o) =>
      ["inprogress", "assigned"].includes(o.status)
    ).length;
    const total = orders.length;
    const completionRate =
      total > 0 ? Math.round((fulfilled / total) * 100) : 0;

    res.json({
      logisticId,
      totalOrders: total,
      fulfilled,
      pending,
      completionRate,
      orders,
    });
  } catch (err) {
    console.error("[Get Orders By Logistic Error]", err.message);
    res.status(500).json({ message: "Failed to fetch orders" });
  }
};

module.exports = {
  createOrder,
  getVendorOrders,
  updateOrderStatus,
  getOrdersByUser,
  getOrderById,
  requestReturn,
  processReturn,
  getOrdersByLogistic,
  initiatePayment,
  handleWebhook,
};
