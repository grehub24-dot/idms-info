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

  if (req.method !== 'GET') {
    res.statusCode = 405;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ ok: false, error: 'Method not allowed' }));
    return;
  }

  try {
    // 1. Fetch Student Info
    const sRows = await db.query('SELECT id FROM students WHERE user_id = ?', [userId]);
    const student = sRows[0];

    if (!student) {
      res.statusCode = 404;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ ok: false, error: 'Student record not found' }));
      return;
    }

    const studentId = student.id;

    // 2. Fetch Payments
    const payments = await db.query(
      `SELECT id, amount, academic_year, semester, payment_date, payment_method, receipt_number, created_at 
       FROM payments 
       WHERE student_id = ? 
       ORDER BY payment_date DESC`,
      [studentId]
    );

    // Response
    res.statusCode = 200;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({
      ok: true,
      payments
    }));

  } catch (error) {
    console.error('Payments list error:', error);
    res.statusCode = 500;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ ok: false, error: 'Internal server error' }));
  }
};
