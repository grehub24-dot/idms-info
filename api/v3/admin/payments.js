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
      // List/Search Payments
      const limitRaw = Number(req.query.limit);
      const limit = Number.isFinite(limitRaw) && limitRaw > 0 ? Math.min(200, Math.max(1, Math.floor(limitRaw))) : 50;
      const indexNumber = String(req.query.index_number || '').trim();
      const academicYear = String(req.query.academic_year || '').trim();
      const semester = String(req.query.semester || '').trim();
      const methodFilter = String(req.query.payment_method || '').trim();

      let rows = [];
      let params = [];
      const whereParts = [];

      if (indexNumber !== '') {
        whereParts.push(`s.index_number = ?`);
        params.push(indexNumber);
      }
      if (academicYear !== '') {
        whereParts.push(`p.academic_year = ?`);
        params.push(academicYear);
      }
      if (semester !== '') {
        whereParts.push(`p.semester = ?`);
        params.push(semester);
      }
      if (methodFilter !== '') {
        whereParts.push(`p.payment_method = ?`);
        params.push(methodFilter);
      }
      const where = whereParts.length ? ` WHERE ${whereParts.join(' AND ')}` : '';

      try {
        const queryWithCLS = `
          SELECT p.id as payment_id, p.amount, p.academic_year, p.semester, p.payment_date, 
                 p.payment_method, p.receipt_number, p.created_at,
                 s.id as student_id, s.full_name, s.index_number, s.department, s.level, s.class_name, s.stream
          FROM payments p
          JOIN students s ON s.id = p.student_id
          ${where}
          ORDER BY p.id DESC LIMIT ${limit}
        `;
        rows = await db.query(queryWithCLS, params);
      } catch (e) {
        if (e && e.code === 'ER_BAD_FIELD_ERROR') {
          const queryFallback = `
            SELECT p.id as payment_id, p.amount, p.academic_year, p.semester, p.payment_date, 
                   p.payment_method, p.receipt_number, p.created_at,
                   s.id as student_id, s.full_name, s.index_number, s.department, s.level
            FROM payments p
            JOIN students s ON s.id = p.student_id
            ${where}
            ORDER BY p.id DESC LIMIT ${limit}
          `;
          rows = await db.query(queryFallback, params);
        } else {
          throw e;
        }
      }

      res.statusCode = 200;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ ok: true, success: true, payments: rows }));

    } else if (method === 'POST') {
      // Create Payment
      const body = typeof req.body === 'string' ? JSON.parse(req.body || '{}') : (req.body || {});
      const { index_number, amount, academic_year, semester, payment_date, payment_method } = body;

      if (!index_number || !amount || !academic_year || !semester || !payment_date || !payment_method) {
        res.statusCode = 400;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ success: false, error: 'Missing required fields' }));
        return;
      }

      // Find student
      const sRows = await db.query('SELECT id, full_name, phone_number FROM students WHERE index_number = ?', [index_number]);
      const student = sRows[0];
      if (!student) {
        res.statusCode = 404;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ success: false, error: 'Student not found' }));
        return;
      }

      const receiptNumber = 'SDMS-' + new Date().getFullYear() + '-' + Math.random().toString(36).substring(2, 10).toUpperCase();

      const [pResult] = await db.getPool().execute(
        'INSERT INTO payments (student_id, amount, academic_year, semester, payment_date, payment_method, receipt_number, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [student.id, amount, academic_year, semester, payment_date, payment_method, receiptNumber, decoded.user_id]
      );

      // Trigger SMS/Email would go here in Node.js
      // ...

      res.statusCode = 201;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ 
        ok: true, 
        success: true, 
        payment_id: pResult.insertId, 
        receipt_number: receiptNumber,
        message: 'Payment recorded successfully' 
      }));

    } else if (method === 'DELETE') {
      // Delete Payment
      const payment_id = req.query.id;
      if (!payment_id) {
        res.statusCode = 400;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ success: false, error: 'Payment ID is required' }));
        return;
      }

      await db.query('DELETE FROM payments WHERE id = ?', [payment_id]);
      res.statusCode = 200;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ ok: true, success: true, message: 'Payment deleted successfully' }));

    } else {
      res.statusCode = 405;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ success: false, error: 'Method not allowed' }));
    }

  } catch (error) {
    console.error('Admin payments API error:', error);
    res.statusCode = 500;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ success: false, error: 'Internal server error' }));
  }
};

function min(a, b) { return a < b ? a : b; }
