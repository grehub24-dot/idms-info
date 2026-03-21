const jwt = require('jsonwebtoken');

const SECRET = process.env.JWT_SECRET || 'your-super-secret-jwt-key-change-this-in-production';

const generateToken = (payload) => {
  return jwt.sign(payload, SECRET, { expiresIn: '24h' });
};

const verifyToken = (token) => {
  try {
    return jwt.verify(token, SECRET);
  } catch (err) {
    return null;
  }
};

module.exports = {
  generateToken,
  verifyToken
};
