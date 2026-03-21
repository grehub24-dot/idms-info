const bcrypt = require('bcryptjs');
const db = require('../db');
const { generateToken } = require('./jwt');

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
    const identifier = String(body.identifier || '').trim();
    const password = body.password;

    if (!identifier || !password) {
      res.statusCode = 400;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ success: false, error: 'Please enter both identifier and password.' }));
      return;
    }

    let user;
    // Check if it's an email or index number
    if (identifier.includes('@')) {
      const rows = await db.query('SELECT * FROM users WHERE email = ?', [identifier]);
      user = rows[0];
    } else {
      const rows = await db.query(
        'SELECT u.*, s.index_number FROM users u JOIN students s ON u.id = s.user_id WHERE s.index_number = ?',
        [identifier]
      );
      user = rows[0];
    }

    if (!user || !(await bcrypt.compare(password, user.password))) {
      res.statusCode = 401;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ success: false, error: 'Invalid credentials. Please try again.' }));
      return;
    }

    if (user.status !== 'active') {
      res.statusCode = 401;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ success: false, error: 'Your account is inactive or banned. Please contact support.' }));
      return;
    }

    const requiresReset = Number(user.is_password_reset) === 0;
    
    // Check if profile is complete (students only)
    let isProfileComplete = true;
    let profilePicture = null;
    let indexNumber = null;
    if (user.role === 'student') {
      let studentRows = [];
      let hasProfileFlagColumn = true;
      try {
        studentRows = await db.query(
          'SELECT index_number, is_profile_complete, profile_picture FROM students WHERE user_id = ?',
          [user.id]
        );
      } catch (error) {
        if (error && error.code === 'ER_BAD_FIELD_ERROR') {
          hasProfileFlagColumn = false;
          studentRows = await db.query(
            'SELECT index_number, profile_picture FROM students WHERE user_id = ?',
            [user.id]
          );
        } else {
          throw error;
        }
      }
      if (studentRows.length > 0) {
        const student = studentRows[0];
        indexNumber = student.index_number || null;
        profilePicture = student.profile_picture || null;
        const hasProfilePicture = typeof student.profile_picture === 'string' && student.profile_picture.trim() !== '';
        if (hasProfileFlagColumn) {
          isProfileComplete = Number(student.is_profile_complete) === 1 && hasProfilePicture;
        } else {
          isProfileComplete = hasProfilePicture;
        }
      }
    }

    // Generate JWT
    const token = generateToken({
      user_id: user.id,
      email: user.email,
      role: user.role
    });

    // Determine redirect target
    let redirect = 'admin/dashboard.html';
    if (user.role === 'student') {
      if (requiresReset) {
        redirect = 'student/password-reset.html';
      } else if (!isProfileComplete) {
        redirect = 'student/profile.html';
      } else {
        redirect = 'student/dashboard.html';
      }
    }

    // Response contract matching existing logic
    const response = {
      success: true,
      token,
      user: {
        id: user.id,
        email: user.email,
        role: user.role,
        status: user.status,
        index_number: indexNumber,
        profile_picture: profilePicture
      },
      requires_password_reset: requiresReset,
      is_profile_complete: isProfileComplete,
      redirect: redirect
    };

    res.statusCode = 200;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify(response));

  } catch (error) {
    console.error('Login error:', error);
    res.statusCode = 500;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ success: false, error: 'Internal server error' }));
  }
};
