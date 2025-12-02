const prisma = require("../utils/prisma");

// 🟢 CREATE — Add a new vehicle
const addVehicle = async (req, res) => {
  try {
    const { userId, vehicleType, vehicleModel, vehicleColor, plateNumber, workHours } = req.body;

    if (!userId || !vehicleType || !vehicleModel || !vehicleColor || !plateNumber || !workHours) {
      return res.status(400).json({ message: "All fields are required" });
    }

    const existing = await prisma.vehicle.findUnique({
      where: { plateNumber }
    });
    if (existing) {
      return res.status(400).json({ message: "Vehicle with this plate number already exists" });
    }

    const vehicle = await prisma.vehicle.create({
      data: {
        userId: String(userId),
        vehicleType,
        vehicleModel,
        vehicleColor,
        plateNumber,
        workHours,
      }
    });

    res.status(201).json({
      message: "Vehicle added successfully",
      vehicle,
    });
  } catch (error) {
    console.log(error);
    
    res.status(500).json({ message: "Server error", error: error.message });
  }
};

// 🟡 READ — Get all vehicles (optionally by userId)
const getVehicles = async (req, res) => {
  try {
    const { userId } = req.query;
    const where = userId ? { userId: String(userId) } : {};
    const vehicles = await prisma.vehicle.findMany({
      where,
      orderBy: { createdAt: 'desc' }
    });

    res.status(200).json({
      message: "Vehicles fetched successfully",
      vehicles,
    });
  } catch (error) {
    res.status(500).json({ message: "Server error", error: error.message });
  }
};

// 🟠 READ — Get single vehicle by ID
const getVehicleById = async (req, res) => {
  try {
    const vehicle = await prisma.vehicle.findUnique({
      where: { id: req.params.id }
    });
    if (!vehicle) return res.status(404).json({ message: "Vehicle not found" });

    res.status(200).json({
      message: "Vehicle fetched successfully",
      vehicle,
    });
  } catch (error) {
    res.status(500).json({ message: "Server error", error: error.message });
  }
};

// 🔵 UPDATE — Update vehicle by ID
const updateVehicle = async (req, res) => {
  try {
    const { id } = req.params;
    const updates = req.body;

    // Check if vehicle exists first
    const existing = await prisma.vehicle.findUnique({
      where: { id }
    });

    if (!existing) {
      return res.status(404).json({ message: "Vehicle not found" });
    }

    const vehicle = await prisma.vehicle.update({
      where: { id },
      data: updates
    });

    res.status(200).json({
      message: "Vehicle updated successfully",
      vehicle,
    });
  } catch (error) {
    if (error.code === 'P2025') {
      return res.status(404).json({ message: "Vehicle not found" });
    }
    res.status(500).json({ message: "Server error", error: error.message });
  }
};

// 🔴 DELETE — Delete vehicle by ID
const deleteVehicle = async (req, res) => {
  try {
    const { id } = req.params;

    // Check if vehicle exists first
    const vehicle = await prisma.vehicle.findUnique({
      where: { id }
    });

    if (!vehicle) {
      return res.status(404).json({ message: "Vehicle not found" });
    }

    await prisma.vehicle.delete({
      where: { id }
    });

    res.status(200).json({
      message: "Vehicle deleted successfully",
    });
  } catch (error) {
    if (error.code === 'P2025') {
      return res.status(404).json({ message: "Vehicle not found" });
    }
    res.status(500).json({ message: "Server error", error: error.message });
  }
};

module.exports = {
  addVehicle,
  getVehicles,
  getVehicleById,
  updateVehicle,
  deleteVehicle,
};
