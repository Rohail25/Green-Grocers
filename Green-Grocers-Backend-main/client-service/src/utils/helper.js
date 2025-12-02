const getPlatformPath = (platform) => {
  const map = {
    trivimart: 'mart',
    triveexpress: 'express',
    trivestore: 'store'
  };
  return map[platform] || '';
};

module.exports = {
  getPlatformPath,
};