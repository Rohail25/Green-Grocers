const logger = (req, res, next) => {
  const start = Date.now();
  
  // Log request
  console.log(`${new Date().toISOString()} - ${req.method} ${req.originalUrl} - IP: ${req.ip}`);
  
  // Log response when finished
  res.on('finish', () => {
    const duration = Date.now() - start;
    const status = res.statusCode;
    const statusClass = Math.floor(status / 100);
    
    let statusIcon = '';
    switch (statusClass) {
      case 2: statusIcon = '✓'; break;
      case 3: statusIcon = '→'; break;
      case 4: statusIcon = '⚠'; break;
      case 5: statusIcon = '✗'; break;
      default: statusIcon = '?'; break;
    }
    
    console.log(`${statusIcon} ${req.method} ${req.originalUrl} - ${status} - ${duration}ms`);
  });
  
  next();
};

module.exports = logger;