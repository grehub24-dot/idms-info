const bcrypt = require('bcryptjs');
const db = require('../db');
const { verifyToken } = require('./jwt');

module.exports = async function handler(req, res) {
  if (req.method === 'OPTIONS') {
    res.statusCode = 204;
    res.setHeader('Allow', 'POST, OPTIONS');
    res.end();
    return;
  }

  if (req.method !== 'POST') {
    res.statusCode = 405;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ success: false, error: 'Method not allowed' }));
    return;
  }

  try {
    let body = req.body || {};
    if (typeof req.body === 'string') {
      try {
        body = JSON.parse(req.body || '{}');
      } catch (parseError) {
        res.statusCode = 400;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ success: false, error: 'Invalid JSON payload.' }));
        return;
      }
    }
    const { new_password, confirm_password } = body;

    if (!new_password || !confirm_password) {
      res.statusCode = 400;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ success: false, error: 'All fields are required.' }));
      return;
    }

    if (new_password !== confirm_password) {
      res.statusCode = 400;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ success: false, error: 'Passwords do not match.' }));
      return;
    }

    if (new_password.length < 6) {
      res.statusCode = 400;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ success: false, error: 'Password must be at least 6 characters long.' }));
      return;
    }

    // Auth check - simplified for now, assuming frontend sends Bearer token
    const authHeader = req.headers.authorization;
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      res.statusCode = 401;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ success: false, error: 'Unauthorized' }));
      return;
    }

    const token = authHeader.split(' ')[1];
    const decoded = verifyToken(token);

    if (!decoded) {
      res.statusCode = 401;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ success: false, error: 'Invalid or expired token' }));
      return;
    }

    const userId = decoded.user_id;
    const hashedPassword = await bcrypt.hash(new_password, 10);

    await db.query(
      'UPDATE users SET password = ?, is_password_reset = 1 WHERE id = ?',
      [hashedPassword, userId]
    );

    res.statusCode = 200;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ 
      success: true, 
      ok: true, // Compatibility with some frontend checks
      message: 'Password reset successfully!' 
    }));

  } catch (error) {
    console.error('Password reset error:', error);
    res.statusCode = 500;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ success: false, error: 'Internal server error' }));
  }
};
