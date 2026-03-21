const db = require('../db');
const { verifyToken } = require('../auth/jwt');

module.exports = async function handler(req, res) {
  // Admin Auth Check
  const authHeader = req.headers.authorization;
  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    res.statusCode = 401;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ success: false, error: 'Unauthorized' }));
    return;
  }

  const token = authHeader.split(' ')[1];
  const decoded = verifyToken(token);

  if (!decoded || decoded.role !== 'admin') {
    res.statusCode = 403;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ success: false, error: 'Access denied: Admin only' }));
    return;
  }

  const { method } = req;

  try {
    if (method === 'GET') {
      // List/Search Users
      const limit = Math.max(1, min(100, parseInt(req.query.limit || '10')));
      const page = Math.max(1, parseInt(req.query.page || '1'));
      const offset = (page - 1) * limit;

      const rows = await db.query(
        'SELECT id, email, role, status, created_at FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?',
        [limit, offset]
      );
      
      const totalRowsResult = await db.query('SELECT COUNT(*) as count FROM users');
      const totalRows = parseInt(totalRowsResult[0].count || 0);

      res.statusCode = 200;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ 
        ok: true, 
        success: true, 
        users: rows,
        pagination: {
          total: totalRows,
          page: page,
          limit: limit,
          pages: Math.ceil(totalRows / limit)
        }
      }));

    } else if (method === 'DELETE') {
      // Delete user
      const { user_id } = req.query;
      if (!user_id) {
        res.statusCode = 400;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ success: false, error: 'User ID is required' }));
        return;
      }

      if (parseInt(user_id) === decoded.user_id) {
        res.statusCode = 400;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ success: false, error: 'You cannot delete your own account' }));
        return;
      }

      await db.query("DELETE FROM users WHERE id = ?", [user_id]);
      res.statusCode = 200;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ ok: true, success: true, message: 'User deleted successfully' }));

    } else {
      res.statusCode = 405;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ success: false, error: 'Method not allowed' }));
    }

  } catch (error) {
    console.error('Admin users API error:', error);
    res.statusCode = 500;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ success: false, error: 'Internal server error' }));
  }
};

function min(a, b) { return a < b ? a : b; }
