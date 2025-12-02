const prisma = require("../utils/prisma");
const { v4: uuidv4 } = require("uuid");

// Get Wallet Balance
const getWallet = async (req, res) => {
  try {
    const wallet = await prisma.wallet.findUnique({
      where: { userId: String(req.user.id) }
    });
    
    if (!wallet) {
      return res.json({ balance: 0, transactions: [] });
    }
    
    // Parse JSON transactions array
    const transactions = typeof wallet.transactions === 'string' 
      ? JSON.parse(wallet.transactions) 
      : wallet.transactions || [];
    
    // Sort by createdAt (descending)
    transactions.sort((a, b) => {
      const dateA = new Date(a.createdAt || 0);
      const dateB = new Date(b.createdAt || 0);
      return dateB - dateA;
    });
    
    res.json({
      userId: wallet.userId,
      balance: Number(wallet.balance),
      transactions: transactions
    });
  } catch (err) {
    console.error("[Get Wallet Error]", err.message);
    res.status(500).json({ message: "Failed to fetch wallet" });
  }
};

// Withdraw
const withdrawFromWallet = async (req, res) => {
  try {
    const { amount, bankName, accountNumber } = req.body;

    if (!amount || !bankName || !accountNumber) {
      return res
        .status(400)
        .json({ message: "Bank, account number, and amount are required" });
    }

    const userId = String(req.user.id);
    
    // Find or create wallet
    let wallet = await prisma.wallet.findUnique({
      where: { userId }
    });

    if (!wallet) {
      wallet = await prisma.wallet.create({
        data: {
          userId,
          balance: 0,
          transactions: []
        }
      });
    }

    const currentBalance = Number(wallet.balance);
    
    if (currentBalance < amount) {
      return res.status(400).json({ message: "Insufficient balance" });
    }

    // Parse transactions JSON
    const transactions = typeof wallet.transactions === 'string' 
      ? JSON.parse(wallet.transactions) 
      : wallet.transactions || [];

    // Add new transaction to array
    transactions.push({
      transactionId: uuidv4(),
      type: "DEBIT",
      amount: amount,
      description: "Wallet withdrawal",
      bankName,
      accountNumber,
      status: "PENDING",
      createdAt: new Date().toISOString()
    });

    // Update wallet
    const updated = await prisma.wallet.update({
      where: { userId },
      data: {
        balance: { decrement: amount },
        transactions: transactions
      }
    });

    // Parse updated transactions for response
    const updatedTransactions = typeof updated.transactions === 'string' 
      ? JSON.parse(updated.transactions) 
      : updated.transactions || [];

    res.json({ 
      message: "Withdrawal request submitted", 
      wallet: {
        userId: updated.userId,
        balance: Number(updated.balance),
        transactions: updatedTransactions.sort((a, b) => {
          return new Date(b.createdAt || 0) - new Date(a.createdAt || 0);
        }).slice(0, 10)
      }
    });
  } catch (err) {
    console.error("[Withdraw Error]", err.message);
    res.status(500).json({ message: "Withdrawal failed", error: err.message });
  }
};

// Credit wallet (e.g., refunds, deposits, order cancellation refunds)
const creditWallet = async (req, res) => {
  try {
    const { amount, description } = req.body;
    const userId = String(req.user.id);
    
    // Find or create wallet
    let wallet = await prisma.wallet.findUnique({
      where: { userId }
    });

    if (!wallet) {
      wallet = await prisma.wallet.create({
        data: {
          userId,
          balance: 0,
          transactions: []
        }
      });
    }

    // Parse transactions JSON
    const transactions = typeof wallet.transactions === 'string' 
      ? JSON.parse(wallet.transactions) 
      : wallet.transactions || [];

    // Add new transaction to array
    transactions.push({
      transactionId: uuidv4(),
      type: "CREDIT",
      amount: amount,
      description: description || "Wallet credit",
      status: "SUCCESS",
      createdAt: new Date().toISOString()
    });

    // Update wallet
    const updated = await prisma.wallet.update({
      where: { userId },
      data: {
        balance: { increment: amount },
        transactions: transactions
      }
    });

    // Parse updated transactions for response
    const updatedTransactions = typeof updated.transactions === 'string' 
      ? JSON.parse(updated.transactions) 
      : updated.transactions || [];

    res.json({ 
      message: "Wallet credited", 
      wallet: {
        userId: updated.userId,
        balance: Number(updated.balance),
        transactions: updatedTransactions.sort((a, b) => {
          return new Date(b.createdAt || 0) - new Date(a.createdAt || 0);
        }).slice(0, 10)
      }
    });
  } catch (err) {
    console.error("[Credit Wallet Error]", err.message);
    res.status(500).json({ message: "Failed to credit wallet" });
  }
};
const debitWallet = async (userId, amount, description = "Order payment") => {
  try {
    const userIdStr = String(userId);
    
    const wallet = await prisma.wallet.findUnique({
      where: { userId: userIdStr }
    });

    if (!wallet) {
      return { success: false, message: "Wallet not found" };
    }

    const currentBalance = Number(wallet.balance);
    
    if (currentBalance < amount) {
      return { success: false, message: "Insufficient balance" };
    }

    // Parse transactions JSON
    const transactions = typeof wallet.transactions === 'string' 
      ? JSON.parse(wallet.transactions) 
      : wallet.transactions || [];

    // Add new transaction to array
    transactions.push({
      transactionId: uuidv4(),
      type: "DEBIT",
      amount: amount,
      description,
      status: "SUCCESS",
      createdAt: new Date().toISOString()
    });

    // Update wallet
    const updated = await prisma.wallet.update({
      where: { userId: userIdStr },
      data: {
        balance: { decrement: amount },
        transactions: transactions
      }
    });

    return { 
      success: true, 
      wallet: {
        userId: updated.userId,
        balance: Number(updated.balance)
      }
    };
  } catch (err) {
    console.error("[Debit Wallet Error]", err.message);
    return { success: false, message: "Failed to debit wallet" };
  }
};
module.exports = {
  getWallet,
  withdrawFromWallet,
  creditWallet,
  debitWallet,
};
