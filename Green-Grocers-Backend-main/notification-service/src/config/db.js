const prisma = require('../utils/prisma');
require("dotenv").config();

const connectDatabase = async () => {
  try {
    await prisma.$connect();
    console.log('MySQL database connected via Prisma');
    await prisma.$queryRaw`SELECT 1`;
    console.log('Database connection verified');
    
    process.on('SIGINT', async () => {
      await prisma.$disconnect();
      console.log('Database connection closed through app termination');
      process.exit(0);
    });
    
    process.on('SIGTERM', async () => {
      await prisma.$disconnect();
      console.log('Database connection closed through app termination');
      process.exit(0);
    });
  } catch (error) {
    console.error("Database connection error:", error.message);
    process.exit(1);
  }
};

module.exports = {
  connectDatabase,
};
