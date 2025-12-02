const prisma = require("../utils/prisma");
const { getOrderById } = require("../utils/communicateOrderService");
const { getAgents } = require("../utils/communicateUserService");
// ➤ Create earning for an order
exports.createEarning = async (req, res) => {
  try {
    const { orderId } = req.body;

    if (!orderId) {
      return res
        .status(400)
        .json({ success: false, message: "Order ID is required" });
    }

    // ✅ Get order details from order service
    const { data: orderRes } = await getOrderById(
      orderId,
      req.headers.authorization
    );

    const order = orderRes.data;
    if (!order) {
      return res
        .status(404)
        .json({ success: false, message: "Order not found" });
    }

    // ✅ Only create earning if delivered
    if (order.status !== "delivered") {
      return res
        .status(400)
        .json({ success: false, message: "Order not delivered yet" });
    }

    // ✅ Calculate agent earning (example: 10% of totalAmount)
    const amount = Number(order.totalAmount) * 0.1;

    // ✅ Create earning record
    const earning = await prisma.earning.create({
      data: {
        logisticsId: String(order.logisticsId),
        orderId: String(order.id || order._id),
        amount: amount,
        transactionId: order.transactionId || null,
        paymentMethod: "Wallet",
        status: "PAID",
        paidAt: new Date(),
      }
    });

    res.status(201).json({ success: true, data: earning });
  } catch (error) {
    res.status(500).json({ success: false, message: error.message });
  }
};
// GET /earnings/manager/:managerId
exports.getEarningsByManager = async (req, res) => {
  try {
    // ✅ First, get all agents under this manager
    const members = await getAgents(req.headers.authorization);
    const agents = members.data;
    if (!agents.length) {
      return res
        .status(404)
        .json({ success: false, message: "No agents found for this manager" });
    }

    // ✅ Get all earnings where logisticsId is in the manager's agents
    const agentIds = agents.map((a) => String(a.id || a._id));
    const earnings = await prisma.earning.findMany({
      where: {
        logisticsId: { in: agentIds }
      },
      orderBy: {
        createdAt: 'desc'
      }
    });

    res.status(200).json({ success: true, data: earnings });
  } catch (error) {
    res.status(500).json({ success: false, message: error.message });
  }
};
// GET /earnings/manager/:managerId/agent/:agentId
exports.getEarningsAgent = async (req, res) => {
  try {
    const { agentId } = req.params;
    // ✅ Get earnings for this agent
    const earnings = await prisma.earning.findMany({
      where: {
        logisticsId: String(agentId)
      },
      orderBy: {
        createdAt: 'desc'
      }
    });

    res.status(200).json({ success: true, data: earnings });
  } catch (error) {
    res.status(500).json({ success: false, message: error.message });
  }
};
