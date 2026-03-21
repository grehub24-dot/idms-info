const db = require('../db');
const { verifyToken } = require('../auth/jwt');

module.exports = async function handler(req, res) {
  if (req.method === 'OPTIONS') {
    res.statusCode = 204;
    res.setHeader('Allow', 'GET, OPTIONS');
    res.end();
    return;
  }

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

  if (req.method !== 'GET') {
    res.statusCode = 405;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ ok: false, error: 'Method not allowed' }));
    return;
  }

  try {
    // 1. Fetch Student Info
    let sRows = [];
    let hasProfileFlagColumn = true;
    try {
      sRows = await db.query(
        `SELECT s.id, s.full_name, s.index_number, s.profile_picture, s.is_profile_complete, u.is_password_reset
         FROM students s
         JOIN users u ON u.id = s.user_id
         WHERE s.user_id = ?`,
        [userId]
      );
    } catch (error) {
      if (error && error.code === 'ER_BAD_FIELD_ERROR') {
        hasProfileFlagColumn = false;
        sRows = await db.query(
          `SELECT s.id, s.full_name, s.index_number, s.profile_picture, u.is_password_reset
           FROM students s
           JOIN users u ON u.id = s.user_id
           WHERE s.user_id = ?`,
          [userId]
        );
      } else {
        throw error;
      }
    }
    const student = sRows[0];

    if (!student) {
      res.statusCode = 404;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ ok: false, error: 'Student record not found' }));
      return;
    }

    if (Number(student.is_password_reset) === 0) {
      res.statusCode = 403;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({
        ok: false,
        requires_password_reset: true,
        redirect: 'password-reset.html',
        error: 'Password reset required before dashboard access.'
      }));
      return;
    }

    const hasProfilePicture = typeof student.profile_picture === 'string' && student.profile_picture.trim() !== '';
    const isProfileComplete = hasProfileFlagColumn
      ? Number(student.is_profile_complete) === 1 && hasProfilePicture
      : hasProfilePicture;
    if (!isProfileComplete) {
      res.statusCode = 403;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({
        ok: false,
        requires_profile_completion: true,
        redirect: 'profile.html',
        error: 'Profile picture is required before dashboard access.'
      }));
      return;
    }

    const studentId = student.id;

    // 2. Fetch Payments
    const payments = await db.query(
      'SELECT * FROM payments WHERE student_id = ? ORDER BY payment_date DESC',
      [studentId]
    );

    // 3. Calculate Total Paid
    const totalPaid = payments.reduce((sum, p) => sum + parseFloat(p.amount || 0), 0);

    // 4. Fetch system settings
    const settingsRows = await db.query(
      "SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('current_academic_year', 'annual_dues_amount')"
    );
    const settings = {};
    settingsRows.forEach(row => {
      settings[row.setting_key] = row.setting_value;
    });

    const currentYear = settings.current_academic_year || '2025/2026';
    const requiredDues = parseFloat(settings.annual_dues_amount || 100.00);

    // 5. Paid this year
    const paidThisYearRows = await db.query(
      'SELECT SUM(amount) as paid FROM payments WHERE student_id = ? AND academic_year = ?',
      [studentId, currentYear]
    );
    const paidThisYear = parseFloat(paidThisYearRows[0].paid || 0);
    const outstanding = Math.max(0, requiredDues - paidThisYear);
    const statusText = outstanding <= 0 ? 'Fully Paid' : 'Unpaid';
    const statusColor = outstanding <= 0 ? 'green' : 'red';

    // 6. Recent Messages
    const recentMsgs = await db.query(
      `SELECT id, title, content, is_broadcast, created_at FROM messages 
       WHERE is_broadcast = 1 OR receiver_id = ? 
       ORDER BY created_at DESC LIMIT 3`,
      [userId]
    );

    // 7. Unread Count
    const unreadCountRows = await db.query(
      `SELECT COUNT(*) as count FROM messages m 
       WHERE (m.is_broadcast = 1 OR m.receiver_id = ?) 
       AND NOT EXISTS (
           SELECT 1 FROM message_reads mr 
           WHERE mr.message_id = m.id AND mr.user_id = ?
       )`,
      [userId, userId]
    );
    const unreadCount = parseInt(unreadCountRows[0].count || 0);

    // Response
    res.statusCode = 200;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({
      ok: true,
      student,
      stats: {
        total_paid: totalPaid,
        receipt_count: payments.length,
        outstanding: outstanding,
        current_year: currentYear,
        status_text: statusText,
        status_color: statusColor
      },
      recent_payments: payments.slice(0, 5),
      recent_messages: recentMsgs,
      unread_count: unreadCount
    }));

  } catch (error) {
    console.error('Dashboard data error:', error);
    res.statusCode = 500;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ ok: false, error: 'Internal server error' }));
  }
};
