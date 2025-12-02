const getPlatformPath = (platform) => {
  const map = {
    trivemart: "mart",
    triveexpress: "express",
    trivestore: "store",
  };
  return map[platform] || "";
};

module.exports = {
  getPlatformPath,
};
