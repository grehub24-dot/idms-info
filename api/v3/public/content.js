const db = require('../db');

module.exports = async function handler(req, res) {
  if (req.method !== 'GET') {
    res.statusCode = 405;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ error: 'Method not allowed' }));
    return;
  }

  // Use URL to determine which content to fetch
  const url = new URL(req.url, `http://${req.headers.host}`);
  const pathParts = url.pathname.split('/');
  const resource = pathParts[pathParts.length - 1].replace('.php', '');

  try {
    let result = { ok: true };
    
    switch (resource) {
      case 'news':
        result.news = await db.query('SELECT * FROM news ORDER BY published_at DESC LIMIT 20');
        break;
      case 'events':
        result.events = await db.query('SELECT * FROM events ORDER BY event_date DESC LIMIT 20');
        break;
      case 'executives':
        result.executives = await db.query('SELECT * FROM executives ORDER BY id ASC');
        break;
      case 'activities':
        result.activities = await db.query('SELECT * FROM activities ORDER BY activity_date DESC LIMIT 20');
        break;
      case 'gallery':
        result.gallery = await db.query('SELECT * FROM activities WHERE image_url IS NOT NULL ORDER BY activity_date DESC LIMIT 30');
        break;
      case 'projects':
        result.projects = await db.query('SELECT * FROM activities WHERE registration_link IS NOT NULL ORDER BY activity_date DESC LIMIT 20');
        break;
      case 'department':
        const deptRows = await db.query('SELECT * FROM department_info');
        result.department = {};
        deptRows.forEach(row => { result.department[row.key_name] = row.content; });
        break;
      case 'settings':
        const settingsRows = await db.query('SELECT setting_key, setting_value FROM system_settings');
        result.settings = {};
        settingsRows.forEach(row => { result.settings[row.setting_key] = row.setting_value; });
        break;
      default:
        res.statusCode = 404;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ ok: false, error: 'Resource not found' }));
        return;
    }

    res.statusCode = 200;
    res.setHeader('Content-Type', 'application/json');
    res.setHeader('Cache-Control', 'public, s-maxage=900, stale-while-revalidate=3600');
    res.end(JSON.stringify(result));

  } catch (error) {
    console.error(`Public content API error (${resource}):`, error);
    res.statusCode = 500;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ ok: false, error: 'Internal server error' }));
  }
};
