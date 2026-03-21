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
      // Fetch system settings
      const settingsRows = await db.query('SELECT setting_key, setting_value FROM system_settings');
      const settings = {};
      settingsRows.forEach(row => {
        settings[row.setting_key] = row.setting_value;
      });

      // Provide defaults for UI
      const defaults = {
        current_academic_year: '2025/2026',
        current_semester: '1',
        annual_dues_amount: '100.00',
        payment_modes: 'Cash,Mobile Money,Bank Transfer',
        department_name: 'Information Technology Education',
        institution_name: 'AAMUSTED'
      };

      res.statusCode = 200;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ ok: true, success: true, settings: { ...defaults, ...settings } }));

    } else if (method === 'POST') {
      // Update system settings
      const body = typeof req.body === 'string' ? JSON.parse(req.body || '{}') : (req.body || {});
      const { settings_update } = body;

      if (!settings_update) {
        res.statusCode = 400;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ success: false, error: 'Settings update data is required' }));
        return;
      }

      const pool = db.getPool();
      const connection = await pool.getConnection();
      await connection.beginTransaction();

      try {
        const stmt = 'INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)';
        
        for (const [key, value] of Object.entries(settings_update)) {
          await connection.execute(stmt, [key, String(value)]);
        }

        await connection.commit();
        res.statusCode = 200;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ ok: true, success: true, message: 'System settings updated successfully' }));
      } catch (txError) {
        await connection.rollback();
        throw txError;
      } finally {
        connection.release();
      }

    } else {
      res.statusCode = 405;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ success: false, error: 'Method not allowed' }));
    }

  } catch (error) {
    console.error('Admin settings API error:', error);
    res.statusCode = 500;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ success: false, error: 'Internal server error' }));
  }
};
