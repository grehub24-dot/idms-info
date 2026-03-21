const db = require('../db');
const { verifyToken } = require('../auth/jwt');

module.exports = async function handler(req, res) {
  // Auth check
  const authHeader = req.headers.authorization;
  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    res.statusCode = 401;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ ok: false, error: 'Unauthorized' }));
    return;
  }

  const token = authHeader.split(' ')[1];
  const decoded = verifyToken(token);

  if (!decoded || decoded.role !== 'student') {
    res.statusCode = 401;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ ok: false, error: 'Unauthorized student access only' }));
    return;
  }

  const userId = decoded.user_id;

  if (req.method === 'GET') {
    try {
      // 1. Fetch Broadcast Messages
      const broadcasts = await db.query(
        'SELECT id, title, content, is_broadcast, created_at FROM messages WHERE is_broadcast = 1 ORDER BY created_at DESC LIMIT 20'
      );

      // 2. Fetch Direct Messages
      const directMessages = await db.query(
        'SELECT id, title, content, is_broadcast, created_at FROM messages WHERE receiver_id = ? AND is_broadcast = 0 ORDER BY created_at DESC',
        [userId]
      );

      // 3. Fetch read ids
      const readRows = await db.query('SELECT message_id FROM message_reads WHERE user_id = ?', [userId]);
      const readIds = readRows.map(row => row.message_id);

      // Response
      res.statusCode = 200;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({
        ok: true,
        broadcasts,
        direct_messages: directMessages,
        read_ids: readIds
      }));

    } catch (error) {
      console.error('Messages list error:', error);
      res.statusCode = 500;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ ok: false, error: 'Internal server error' }));
    }
  } else if (req.method === 'POST') {
    try {
      const body = typeof req.body === 'string' ? JSON.parse(req.body || '{}') : (req.body || {});
      const action = body.action || '';

      if (action === 'send_to_admin') {
        const { subject, content } = body;
        if (!subject || !content) {
          res.statusCode = 400;
          res.setHeader('Content-Type', 'application/json');
          res.end(JSON.stringify({ ok: false, error: 'Subject and content are required' }));
          return;
        }

        // Find an admin
        const admins = await db.query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        const adminId = admins[0] ? admins[0].id : null;

        if (!adminId) {
          res.statusCode = 404;
          res.setHeader('Content-Type', 'application/json');
          res.end(JSON.stringify({ ok: false, error: 'No administrator found' }));
          return;
        }

        await db.query(
          "INSERT INTO messages (sender_id, receiver_id, title, content, is_broadcast) VALUES (?, ?, ?, ?, 0)",
          [userId, adminId, subject, content]
        );

        res.statusCode = 200;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ ok: true, message: 'Message sent to administrator successfully!' }));
      } else if (action === 'mark_read') {
        const { message_id } = body;
        if (!message_id) {
          res.statusCode = 400;
          res.setHeader('Content-Type', 'application/json');
          res.end(JSON.stringify({ ok: false, error: 'Message ID required' }));
          return;
        }

        await db.query(
          "INSERT IGNORE INTO message_reads (message_id, user_id) VALUES (?, ?)",
          [message_id, userId]
        );

        res.statusCode = 200;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ ok: true }));
      } else {
        res.statusCode = 400;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ ok: false, error: 'Invalid action' }));
      }
    } catch (error) {
      console.error('Messages action error:', error);
      res.statusCode = 500;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ ok: false, error: 'Internal server error' }));
    }
  } else {
    res.statusCode = 405;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ ok: false, error: 'Method not allowed' }));
  }
};
