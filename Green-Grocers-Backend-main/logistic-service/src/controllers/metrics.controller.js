const { getOrderLogisticStat } = require("../utils/communicateOrderService");
const {
  getUserRateLogistics,
  getAgents,
} = require("../utils/communicateUserService");
const getLogisticsPerformanceMetrics = async (req, res) => {
  try {
    const { logisticId } = req.params;

    // Call Order Service
    const ordersResp = await getOrderLogisticStat(
      logisticId,
      req.headers.authorization
    );

    const {
      totalOrders,
      fulfilled: fulfilledCount,
      pending: pendingCount,
      completionRate,
      orders = [],
    } = ordersResp || {};

    const fulfilled =
      fulfilledCount ?? orders.filter((o) => o.status === "delivered").length;
    const pending =
      pendingCount ??
      orders.filter((o) => ["inprogress", "assigned"].includes(o.status))
        .length;

    // Call User Service (for ratings)
    const ratingsResp = await getUserRateLogistics(
      logisticId,
      req.headers.authorization
    );

    const { totalReviews, avgRating, ratingDistribution } = ratingsResp;

    // Call Team Member Service
    const memberResp = await getAgents(req.headers.authorization);

    const members = memberResp.data;

    res.json({
      logisticId,
      agents: members.map((member) => ({
        firstName: member.firstName,
        lastName: member.lastName,
        email: member.email,
      })),
      metrics: {
        fulfilledDelivery: fulfilled,
        pendingFulfillment: pending,
        deliveryCompletionRate: completionRate,
        totalReviews,
        averageRating: avgRating,
        ratingDistribution,
      },
    });
  } catch (err) {
    console.error("[Metrics Error]", err);
    res.status(500).json({ message: "Failed to fetch metrics" });
  }
};

module.exports = { getLogisticsPerformanceMetrics };
