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

  const { method, query } = req;

  try {
    if (method === 'GET') {
      const { type, export: exportType } = query;
      if (!type) {
        res.statusCode = 400;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ success: false, error: 'Report type is required' }));
        return;
      }

      let data = [];
      let headers = [];
      let queryStr = '';

      switch (type) {
        case 'payments_per_dept':
          queryStr = `
            SELECT s.department, COUNT(p.id) as payment_count, SUM(p.amount) as total_amount 
            FROM payments p 
            JOIN students s ON p.student_id = s.id 
            GROUP BY s.department
          `;
          headers = ['Department', 'Payment Count', 'Total Amount'];
          break;
        case 'payments_per_year':
          queryStr = `
            SELECT academic_year, COUNT(id) as payment_count, SUM(amount) as total_amount 
            FROM payments 
            GROUP BY academic_year
          `;
          headers = ['Academic Year', 'Payment Count', 'Total Amount'];
          break;
        case 'payments_per_semester':
          queryStr = `
            SELECT semester, COUNT(id) as payment_count, SUM(amount) as total_amount 
            FROM payments 
            GROUP BY semester
          `;
          headers = ['Semester', 'Payment Count', 'Total Amount'];
          break;
        case 'audit_logs':
          queryStr = `
            SELECT a.*, u.email 
            FROM audit_logs a 
            JOIN users u ON a.user_id = u.id 
            ORDER BY a.created_at DESC 
            LIMIT 100
          `;
          headers = ['ID', 'User', 'Action', 'Details', 'IP Address', 'Created At'];
          break;
        default:
          res.statusCode = 400;
          res.setHeader('Content-Type', 'application/json');
          res.end(JSON.stringify({ success: false, error: 'Invalid report type' }));
          return;
      }

      data = await db.query(queryStr);

      if (exportType === 'csv') {
        res.statusCode = 200;
        res.setHeader('Content-Type', 'text/csv');
        res.setHeader('Content-Disposition', `attachment; filename="report_${type}_${new Date().toISOString().split('T')[0]}.csv"`);
        
        // Convert to CSV string
        let csvContent = headers.join(',') + '\n';
        data.forEach(row => {
          const rowData = Object.values(row).map(val => {
            const str = String(val === null ? '' : val);
            return str.includes(',') ? `"${str}"` : str;
          });
          csvContent += rowData.join(',') + '\n';
        });
        
        res.end(csvContent);
        return;
      }

      res.statusCode = 200;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ ok: true, success: true, type, headers, data }));

    } else {
      res.statusCode = 405;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ success: false, error: 'Method not allowed' }));
    }

  } catch (error) {
    console.error('Admin reports API error:', error);
    res.statusCode = 500;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ success: false, error: 'Internal server error' }));
  }
};
