// deliveryAssignment.controller.js

const { UpdateOrderStatus } = require("../utils/communicateOrderService");
const prisma = require("../utils/prisma");
const assignDelivery = async (req, res) => {
  try {
    const { managerId } = req.body; // Manager performing the assignment
    const {
      teamMemberId,
      orderId,
      parcelNo,
      vendorLocation,
      travelDistance,
      estimatedTime,
    } = req.body;

    // Check team member exists and belongs to this manager
    // const teamMember = await TeamMember.findOne({
    //   _id: teamMemberId,
    //   managerId,
    // });
    // if (!teamMember) {
    //   return res
    //     .status(404)
    //     .json({ message: "Team member not found or not under this manager" });
    // }

    // Parse vendorLocation if it's a string
    const vendorLocationData = typeof vendorLocation === 'string' 
      ? JSON.parse(vendorLocation) 
      : vendorLocation;
    
    // Create new delivery assignment
    const assignment = await prisma.deliveryAssignment.create({
      data: {
        parcelNo,
        teamMemberId: String(teamMemberId),
        orderId: String(orderId),
        vendorLocation: vendorLocationData || null,
        travelDistance: travelDistance ? parseFloat(travelDistance) : null,
        estimatedTime: estimatedTime ? parseInt(estimatedTime) : null,
        status: "assigned",
        assignedAt: new Date(),
      }
    });

    // Add assignment to team member's activeAssignments
    // teamMember.activeAssignments.push(assignment._id);
    // await teamMember.save();
    try {
      await UpdateOrderStatus(
        assignment.orderId,
        "assigned",
        undefined,
        req.headers.authorization
      );
    } catch (orderError) {
      console.error("[Order Status Update Error]", orderError.message);
    }

    res
      .status(201)
      .json({ message: "Delivery assigned successfully", assignment });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};
// Get assignments for a team member
const getMyAssignments = async (req, res) => {
  try {
    const { teamMemberId } = req.params;
    const assignments = await prisma.deliveryAssignment.findMany({
      where: {
        teamMemberId: String(teamMemberId),
        status: { in: ["assigned", "in-progress"] }
      }
    });

    res.json(assignments);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

// Update assignment status
const updateAssignmentStatus = async (req, res) => {
  try {
    const { assignmentId } = req.params;
    const { status } = req.body;

    const assignment = await prisma.deliveryAssignment.findUnique({
      where: { id: assignmentId }
    });

    if (!assignment) {
      return res.status(404).json({ message: "Assignment not found" });
    }

    const updateData = { status };

    if (status === "in-progress") {
      updateData.startedAt = new Date();
    } else if (status === "completed") {
      updateData.completedAt = new Date();
      // Update order status as well
      try {
        await UpdateOrderStatus(
          assignment.orderId,
          "delivered",
          undefined,
          req.headers.authorization
        );
      } catch (orderError) {
        console.error("[Order Status Update Error]", orderError.message);
      }
    }

    const updated = await prisma.deliveryAssignment.update({
      where: { id: assignmentId },
      data: updateData
    });
    
    res.json(updated);
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

// Toggle availability
const toggleAvailability = async (req, res) => {
  try {
    const { teamMemberId } = req.params;
    const { isAvailable } = req.body;

    // const teamMember = await TeamMember.findById(teamMemberId);
    // teamMember.isAvailable = isAvailable;
    // await teamMember.save();

    res.json({ status:200 });
  } catch (error) {
    res.status(500).json({ message: error.message });
  }
};

const getDeliveryAnalytics = async (req, res) => {
  try {
    const { teamMemberId, managerId } = req.query;

    const whereClause = {};
    if (teamMemberId) whereClause.teamMemberId = String(teamMemberId);

    // Get all assignments
    const assignments = await prisma.deliveryAssignment.findMany({
      where: whereClause
    });

    // Calculate analytics
    const totalAssigned = assignments.length;
    const totalCompleted = assignments.filter(a => a.status === "completed").length;
    const totalOngoing = assignments.filter(a => a.status === "in-progress").length;
    const totalPending = assignments.filter(a => a.status === "assigned").length;

    res.json({
      totalAssigned,
      totalCompleted,
      totalOngoing,
      totalPending,
    });
  } catch (err) {
    console.error("[Delivery Analytics Error]", err);
    res.status(500).json({ message: "Internal server error" });
  }
};

module.exports = {
  getMyAssignments,
  updateAssignmentStatus,
  toggleAvailability,
  assignDelivery,
  getDeliveryAnalytics,
};
