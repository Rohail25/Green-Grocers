const prisma = require('../utils/prisma');
const jwt = require('jsonwebtoken');
const bcrypt = require('bcryptjs');
const { v4: uuidv4 } = require('uuid');
const { uploadFileToS3 } = require('../middlewares/s3Uploader.middleware');

const crypto = require('crypto');
const { sendEmail } = require('../utils/ses');
const { getPlatformPath } = require('../utils/helper');
const { registerClient } = require('../utils/communicateClientService');


const registerUser = async (req, res) => {
  try {
    const { email, phone, password, confirmPassword, platform, googleId, facebookId } = req.body;
    if (!platform) return res.status(400).json({ message: 'Platform is required' });

    // Check for existing user
    const existingUser = await prisma.user.findFirst({
      where: { email: email.toLowerCase().trim(), platform }
    });

    if (existingUser) {
      if (googleId && existingUser.googleId) {
        return res.status(409).json({ message: 'Google account already registered' });
      }
      if (facebookId && existingUser.facebookId) {
        return res.status(409).json({ message: 'Facebook account already registered' });
      }

      if (!googleId && !facebookId) {
        return res.status(409).json({ message: 'Email already registered for this platform' });
      }

      if (googleId && !existingUser.googleId) {
        existingUser.googleId = googleId;
      }
      if (facebookId && !existingUser.facebookId) {
        existingUser.facebookId = facebookId;
      }

      if (password) {
        if (password !== confirmPassword) {
          return res.status(400).json({ message: 'Passwords do not match' });
        }
        existingUser.password = await bcrypt.hash(password, 10);
      }

      const updateData = {};
      if (googleId && !existingUser.googleId) updateData.googleId = googleId;
      if (facebookId && !existingUser.facebookId) updateData.facebookId = facebookId;
      if (password) updateData.password = await bcrypt.hash(password, 10);
      
      await prisma.user.update({
        where: { id: existingUser.id },
        data: updateData
      });
      
      const updatedUser = await prisma.user.findUnique({
        where: { id: existingUser.id }
      });

      // Resend email if not confirmed
      if (!updatedUser.isEmailConfirmed) {
        try {
          const token = jwt.sign(
            { id: updatedUser.id, platform: updatedUser.platform },
            process.env.JWT_SECRET,
            { expiresIn: '24h' }
          );
          const confirmLink = `http://51.21.203.90:3000/#/${getPlatformPath(platform)}/verify?key=${token}`;
          await sendEmail(email, 'Confirm your account', `Click here to confirm your email: ${confirmLink}`);
        } catch (emailError) {
          console.error('[Email Sending Error]', emailError);
        }
      }

      return res.status(200).json({
        message: 'Account updated successfully.' + (!updatedUser.isEmailConfirmed ? ' Please check your email to confirm.' : ''),
        requiresConfirmation: !updatedUser.isEmailConfirmed
      });
    }

    // New user registration
    if ((!googleId && !facebookId) && password !== confirmPassword) {
      return res.status(400).json({ message: 'Passwords do not match' });
    }

    const hashed = password ? await bcrypt.hash(password, 10) : undefined;
    const vendorId = platform === 'trivestore' ? `VEND-${uuidv4().slice(0, 8)}` : null;
    const clientId = platform === 'trivemart' ? `MART-${uuidv4().slice(0, 8)}` : null;
    
    // Parse addresses and extraDetails if they come as strings
    const addresses = typeof req.body.addresses === 'string' 
      ? JSON.parse(req.body.addresses) 
      : (req.body.addresses || []);
    const extraDetails = typeof req.body.extraDetails === 'string' 
      ? JSON.parse(req.body.extraDetails) 
      : (req.body.extraDetails || []);

    // Save user to DB first
    let user;
    try {
      user = await prisma.user.create({
        data: {
          email: email.toLowerCase().trim(),
          phone: phone || null,
          password: hashed || null,
          platform,
          vendorId,
          clientId,
          googleId: googleId || null,
          facebookId: facebookId || null,
          addresses: addresses,
          extraDetails: extraDetails,
          isEmailConfirmed: false
        }
      });
    } catch (saveError) {
      console.error('[User Save Error]', saveError);
      return res.status(500).json({ message: 'Failed to save user information. Please try again.' });
    }

    // Generate confirmation token AFTER user is saved
    let token;
    try {
      token = jwt.sign(
        { id: user.id, platform: user.platform },
        process.env.JWT_SECRET,
        { expiresIn: '24h' }
      );
    } catch (tokenError) {
      console.error('[Token Generation Error]', tokenError);
      return res.status(500).json({ message: 'Error generating verification token' });
    }

    // Send email confirmation
    const confirmLink = `http://51.21.203.90:3000/#/${getPlatformPath(platform)}/verify?key=${token}`;
    try {
      await sendEmail(email, 'Confirm your account', `Click here to confirm your email: ${confirmLink}`);
    } catch (emailError) {
      console.error('[Email Sending Error]', emailError);
      // Don't fail registration if email fails
    }

    // 👉 Create client profile in Client Service if trivimart
    if (platform === 'trivemart') {
      try {
        await registerClient(user, token);
      } catch (clientError) {
        console.error('[Client Service Error]', clientError.message);
        // Don't block registration if client profile fails
      }
    }

    return res.status(201).json({
      message: 'User registered. Please check your email to confirm.',
      requiresConfirmation: true
    });
  } catch (err) {
    console.error('[Register Error]', err);
    return res.status(500).json({ message: 'Internal server error' });
  }
};



const loginUser = async (req, res) => {
  try {
    const { email, phone, password, platform, googleId, facebookId } = req.body;

    if (!platform) return res.status(400).json({ message: 'Platform is required' });

    const user = email 
      ? await prisma.user.findFirst({
          where: { email: email.toLowerCase().trim(), platform }
        })
      : await prisma.user.findFirst({
          where: { phone, platform }
        });
    if (!user) return res.status(404).json({ message: 'User not found for this platform' });

    // Third-party login flow
    if (googleId || facebookId) {
      if ((googleId && user.googleId === googleId) || (facebookId && user.facebookId === facebookId)) {
        // Check if email is confirmed
        if (!user.isEmailConfirmed) {
          return res.status(403).json({ message: 'Please confirm your email before logging in' });
        }

        const token = jwt.sign({ id: user.id, platform: user.platform }, process.env.JWT_SECRET);
        const addresses = typeof user.addresses === 'string' 
          ? JSON.parse(user.addresses) 
          : (user.addresses || []);
        
        return res.status(200).json({
          token,
          user: {
            id: user.id,
            email: user.email,
            phone: user.phone,
            platform: user.platform,
            vendorId: user.vendorId,
            addresses: addresses,
          }
        });
      } else {
        return res.status(401).json({ message: 'Social login failed: ID mismatch' });
      }
    }

    // Normal password login flow
    if (!user.password) {
      return res.status(400).json({
        message: 'No password set for this account. Please use social login or reset password'
      });
    }

    if (!user.isEmailConfirmed) {
      return res.status(403).json({ message: 'Please confirm your email before logging in' });
    }

    const isMatch = await bcrypt.compare(password, user.password);
    if (!isMatch) return res.status(401).json({ message: 'Invalid password' });

    const token = jwt.sign({ id: user.id, platform: user.platform }, process.env.JWT_SECRET);
    res.status(200).json({
      token,
      user: {
        id: user.id,
        email: user.email,
        phone: user.phone,
        platform: user.platform,
        vendorId: user.vendorId,
      }
    });
  } catch (err) {
    console.error('[Login Error]', err);
    res.status(500).json({ message: 'Internal server error' });
  }
};


const getProfile = async (req, res) => {
  try {
    const user = await prisma.user.findUnique({
      where: { id: req.user.id }
    });
    if (!user) return res.status(404).json({ message: 'User not found' });

    // Remove password from response
    const { password, ...userWithoutPassword } = user;
    res.json({ user: userWithoutPassword });
  } catch (err) {
    console.error('[User Profile Error]', err);
    res.status(500).json({ message: 'Failed to fetch user profile' });
  }
};

const updateProfile = async (req, res) => {
  try {
    const userId = req.user.id;
    const updates = { ...req.body };

    // Upload new profile image if exists
    if (req.file) {
      const imageUrl = await uploadFileToS3(req.file);
      updates.profileImage = imageUrl;
    }

    // Parse JSON fields if they come as strings
    if (updates.addresses && typeof updates.addresses === 'string') {
      try {
        updates.addresses = JSON.parse(updates.addresses);
      } catch (err) {
        console.warn('Failed to parse addresses:', err);
      }
    }
    if (updates.extraDetails && typeof updates.extraDetails === 'string') {
      try {
        updates.extraDetails = JSON.parse(updates.extraDetails);
      } catch (err) {
        console.warn('Failed to parse extraDetails:', err);
      }
    }

    // Never allow password/role/platform edits via profile update
    delete updates.password;
    delete updates.role;
    delete updates.platform;

    const user = await prisma.user.update({
      where: { id: userId },
      data: updates
    });

    // Remove password from response
    const { password, ...userWithoutPassword } = user;

    res.json({ message: 'Profile updated successfully', user: userWithoutPassword });
  } catch (err) {
    console.error('[Update Profile Error]', err);
    if (err.code === 'P2025') {
      return res.status(404).json({ message: 'User not found' });
    }
    res.status(500).json({ message: 'Failed to update profile', error: err.message });
  }

};



const confirmEmail = async (req, res) => {
  try {
    const { token } = req.body;

    if (!token) {
      return res.status(400).json({ message: 'Confirmation token is required' });
    }

    // Decode the token
    let payload;
    try {
      payload = jwt.verify(token, process.env.JWT_SECRET);
    } catch (err) {
      return res.status(400).json({ message: 'Invalid or expired confirmation token' });
    }

    const user = await prisma.user.findUnique({
      where: { id: payload.id }
    });
    if (!user) return res.status(404).json({ message: 'User not found' });

    if (user.isEmailConfirmed) {
      return res.status(400).json({ message: 'Email is already confirmed' });
    }

    await prisma.user.update({
      where: { id: user.id },
      data: { isEmailConfirmed: true }
    });

    res.status(200).json({ message: 'Email confirmed successfully' });
  } catch (err) {
    console.error('[Confirm Email Error]', err);
    res.status(500).json({ message: 'Failed to confirm email' });
  }
};



const resendConfirmationEmail = async (req, res) => {
  try {
    const { email, platform } = req.body;
    if (!email || !platform) {
      return res.status(400).json({ message: 'Email and platform are required' });
    }

    const user = await prisma.user.findFirst({
      where: { email: email.toLowerCase().trim(), platform }
    });
    if (!user) {
      return res.status(404).json({ message: 'User not found' });
    }

    if (user.isEmailConfirmed) {
      return res.status(400).json({ message: 'Email already confirmed' });
    }

    const confirmationToken = jwt.sign(
      { id: user.id, platform },
      process.env.JWT_SECRET,
      { expiresIn: '1h' }
    );

    const platformPath = getPlatformPath(platform);


    const confirmLink = `http://51.21.203.90:3000/#/${platformPath}/verify?key=${confirmationToken}`;

    await sendEmail(email, 'Confirm your account', `Click here to confirm your email: ${confirmLink}`);

    res.status(200).json({ message: 'Confirmation email resent successfully' });
  } catch (err) {
    console.error('[Resend Confirmation Error]', err.message);
    res.status(500).json({ message: 'Failed to resend confirmation email' });
  }
};

const addAddress = async (req, res) => {
  try {
    const userId = req.user.id;
    const address = req.body;
    const user = await prisma.user.findUnique({
      where: { id: userId }
    });

    if (!user) return res.status(404).json({ message: 'User not found' });
    
    const addresses = typeof user.addresses === 'string' 
      ? JSON.parse(user.addresses) 
      : (user.addresses || []);
    addresses.push(address);
    
    await prisma.user.update({
      where: { id: userId },
      data: { addresses: addresses }
    });
    
    res.status(200).json({ message: 'Address added successfully', address: addresses[addresses.length - 1] });
  } catch (err) {
    console.error('[Add Address Error]', err.message);
    res.status(500).json({ message: 'Failed to add address', error: err.message });
  }
};

const updateAddress = async (req, res) => {
  try {
    const userId = req.user.id;
    const { addressId } = req.params;
    const address = req.body;
    const user = await prisma.user.findUnique({
      where: { id: userId }
    });
    if (!user) return res.status(404).json({ message: 'User not found' });
    
    const addresses = typeof user.addresses === 'string' 
      ? JSON.parse(user.addresses) 
      : (user.addresses || []);
    
    // Since addresses don't have _id in Prisma (stored as JSON), we'll update by index
    // If addressId is provided as index
    const index = parseInt(addressId);
    if (isNaN(index) || index < 0 || index >= addresses.length) {
      return res.status(404).json({ message: 'Address not found' });
    }
    
    addresses[index] = { ...addresses[index], ...address };
    
    await prisma.user.update({
      where: { id: userId },
      data: { addresses: addresses }
    });
    
    res.status(200).json({ message: 'Address updated successfully', address: addresses[index] });
  } catch (err) {
    console.error('[Update Address Error]', err.message);
    res.status(500).json({ message: 'Failed to update address', error: err.message });
  }
};

const deleteAddress = async (req, res) => {
  try {
    const userId = req.user.id;
    const { addressId } = req.params;
    const user = await prisma.user.findUnique({
      where: { id: userId }
    });
    if (!user) return res.status(404).json({ message: 'User not found' });
    
    const addresses = typeof user.addresses === 'string' 
      ? JSON.parse(user.addresses) 
      : (user.addresses || []);
    
    // Since addresses don't have _id in Prisma (stored as JSON), we'll delete by index
    const index = parseInt(addressId);
    if (isNaN(index) || index < 0 || index >= addresses.length) {
      return res.status(404).json({ message: 'Address not found' });
    }
    
    addresses.splice(index, 1);
    
    await prisma.user.update({
      where: { id: userId },
      data: { addresses: addresses }
    });
    
    res.status(200).json({ message: 'Address deleted successfully' });
  } catch (err) {
    console.error('[Delete Address Error]', err.message);
    res.status(500).json({ message: 'Failed to delete address', error: err.message });
  }
};






module.exports = { registerUser, loginUser, getProfile, updateProfile, confirmEmail, resendConfirmationEmail, addAddress, updateAddress, deleteAddress };


