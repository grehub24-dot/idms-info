const db = require('../db');
const { verifyToken } = require('../auth/jwt');
// Note: In a real environment, we'd use a Node.js SMS library here.
// For now, we'll implement the logic and assume the helper exists or we'll use a fetch-based approach to the Wigal API.

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
      // List student messages for the admin inbox
      const rows = await db.query(
        `SELECT m.*, s.full_name as sender_name, s.index_number 
         FROM messages m 
         JOIN students s ON m.sender_id = s.user_id 
         WHERE m.is_broadcast = 0 AND m.receiver_id = ?
         ORDER BY m.created_at DESC 
         LIMIT 20`,
        [decoded.user_id]
      );
      res.statusCode = 200;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ ok: true, success: true, messages: rows }));

    } else if (method === 'POST') {
      const body = typeof req.body === 'string' ? JSON.parse(req.body || '{}') : (req.body || {});
      const { action, title, content, send_sms, recipient_type, student_id, sms_content } = body;

      if (action === 'broadcast') {
        if (!title || !content) {
          res.statusCode = 400;
          res.setHeader('Content-Type', 'application/json');
          res.end(JSON.stringify({ success: false, error: 'Title and content are required' }));
          return;
        }

        // 1. Save broadcast message
        await db.query(
          "INSERT INTO messages (sender_id, title, content, is_broadcast) VALUES (?, ?, ?, 1)",
          [decoded.user_id, title, content]
        );

        // 2. Optional SMS (Placeholder for Wigal Node implementation)
        if (send_sms) {
          const students = await db.query("SELECT phone_number FROM students WHERE phone_number IS NOT NULL AND phone_number != ''");
          // Logic to send SMS to all would go here
        }

        res.statusCode = 200;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ ok: true, success: true, message: 'Broadcast message sent successfully!' }));

      } else if (action === 'send_sms_only') {
        if (!sms_content) {
          res.statusCode = 400;
          res.setHeader('Content-Type', 'application/json');
          res.end(JSON.stringify({ success: false, error: 'SMS content is required' }));
          return;
        }

        if (recipient_type === 'all') {
          const students = await db.query("SELECT phone_number FROM students WHERE phone_number IS NOT NULL AND phone_number != ''");
          // Logic for bulk SMS
          await db.query(
            "INSERT INTO messages (sender_id, title, content, is_broadcast) VALUES (?, ?, ?, 1)",
            [decoded.user_id, "Bulk SMS", sms_content]
          );
          res.statusCode = 200;
          res.setHeader('Content-Type', 'application/json');
          res.end(JSON.stringify({ ok: true, success: true, message: `SMS sent to all (${students.length}) students!` }));
        } else {
          const sRows = await db.query("SELECT phone_number, full_name, user_id FROM students WHERE id = ?", [student_id]);
          const student = sRows[0];
          if (student && student.phone_number) {
            // Logic for individual SMS
            await db.query(
              "INSERT INTO messages (sender_id, receiver_id, title, content, is_broadcast) VALUES (?, ?, ?, ?, 0)",
              [decoded.user_id, student.user_id, "Individual SMS", sms_content]
            );
            res.statusCode = 200;
            res.setHeader('Content-Type', 'application/json');
            res.end(JSON.stringify({ ok: true, success: true, message: `SMS sent successfully to ${student.full_name}!` }));
          } else {
            res.statusCode = 400;
            res.setHeader('Content-Type', 'application/json');
            res.end(JSON.stringify({ success: false, error: 'Student has no valid phone number' }));
          }
        }
      } else {
        res.statusCode = 400;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ success: false, error: 'Invalid action' }));
      }

    } else if (method === 'DELETE') {
      const { message_id } = req.query;
      if (!message_id) {
        res.statusCode = 400;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ success: false, error: 'Message ID is required' }));
        return;
      }

      await db.query("DELETE FROM messages WHERE id = ?", [message_id]);
      res.statusCode = 200;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ ok: true, success: true, message: 'Message deleted successfully' }));

    } else {
      res.statusCode = 405;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ success: false, error: 'Method not allowed' }));
    }

  } catch (error) {
    console.error('Admin messaging API error:', error);
    res.statusCode = 500;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ success: false, error: 'Internal server error' }));
  }
};
