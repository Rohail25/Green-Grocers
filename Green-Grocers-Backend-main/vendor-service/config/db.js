const prisma = require('../src/utils/prisma');

const connectDB = async () => {
  try {
    // Test database connection
    await prisma.$connect();
    console.log('MySQL database connected via Prisma');
    
    // Test query to ensure connection works
    await prisma.$queryRaw`SELECT 1`;
    console.log('Database connection verified');

    // Handle app termination
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

  } catch (err) {
    console.error('Failed to connect to MySQL database:', err.message);
    console.error('Please ensure MySQL is running and DATABASE_URL is correct');
    process.exit(1);
  }
};

module.exports = connectDB;