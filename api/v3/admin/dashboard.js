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

  if (req.method !== 'GET') {
    res.statusCode = 405;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ success: false, error: 'Method not allowed' }));
    return;
  }

  try {
    // 1. Fetch System Settings
    const settingsRows = await db.query('SELECT setting_key, setting_value FROM system_settings');
    const settings = {};
    settingsRows.forEach(row => {
      settings[row.setting_key] = row.setting_value;
    });

    const currentYear = settings.current_academic_year || '2025/2026';
    const requiredDues = parseFloat(settings.annual_dues_amount || 100.00);

    // 2. Total Students
    const totalStudentsRows = await db.query('SELECT COUNT(*) as count FROM students');
    const totalStudents = parseInt(totalStudentsRows[0].count || 0);

    // 3. Total Revenue
    const totalRevenueRows = await db.query('SELECT SUM(amount) as sum FROM payments');
    const totalRevenue = parseFloat(totalRevenueRows[0].sum || 0);

    // 4. Payments Today
    const today = new Date().toISOString().split('T')[0];
    const paymentsTodayRows = await db.query('SELECT COUNT(*) as count FROM payments WHERE payment_date = ?', [today]);
    const paymentsToday = parseInt(paymentsTodayRows[0].count || 0);

    // 5. Compliance Rate
    const studentsPaidRows = await db.query(
      `SELECT COUNT(*) as count FROM (
        SELECT student_id, SUM(amount) as total
        FROM payments
        WHERE academic_year = ?
        GROUP BY student_id
        HAVING total >= ?
      ) t`,
      [currentYear, requiredDues]
    );
    const studentsPaid = parseInt(studentsPaidRows[0].count || 0);
    const complianceRate = totalStudents > 0 ? Math.round((studentsPaid / totalStudents) * 1000) / 10 : 0;
    const outstandingStudents = Math.max(0, totalStudents - studentsPaid);

    // 6. Recent Payments
    const recentPayments = await db.query(
      `SELECT p.*, s.full_name, s.index_number,
             (SELECT SUM(amount) FROM payments WHERE student_id = s.id AND academic_year = ?) as total_paid
       FROM payments p 
       JOIN students s ON p.student_id = s.id 
       ORDER BY p.created_at DESC 
       LIMIT 5`,
      [currentYear]
    );

    recentPayments.forEach(p => {
      p.balance = Math.max(0, requiredDues - parseFloat(p.total_paid || 0));
    });

    // 7. Monthly Revenue for Chart
    const monthlyRevenueRows = await db.query(
      `SELECT 
          DATE_FORMAT(payment_date, '%b %Y') as month_label,
          SUM(amount) as monthly_total,
          DATE_FORMAT(payment_date, '%Y-%m') as sort_order
       FROM payments 
       GROUP BY sort_order, month_label
       ORDER BY sort_order ASC
       LIMIT 12`
    );

    const chartLabels = monthlyRevenueRows.map(row => row.month_label);
    const chartData = monthlyRevenueRows.map(row => parseFloat(row.monthly_total || 0));

    // Fallback if no data
    if (chartLabels.length === 0) {
      chartLabels.push(new Date().toLocaleDateString('en-US', { month: 'short', year: 'numeric' }));
      chartData.push(0);
    }

    // Response
    res.statusCode = 200;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({
      ok: true,
      success: true,
      stats: {
        total_students: totalStudents,
        total_revenue: totalRevenue,
        payments_today: paymentsToday,
        compliance_rate: complianceRate,
        outstanding_students: outstandingStudents,
        current_year: currentYear,
        required_dues: requiredDues
      },
      recent_payments: recentPayments,
      chart: {
        labels: chartLabels,
        data: chartData
      }
    }));

  } catch (error) {
    console.error('Admin dashboard API error:', error);
    res.statusCode = 500;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ success: false, error: 'Internal server error' }));
  }
};
