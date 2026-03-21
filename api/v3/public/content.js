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
        try {
          const rows = await db.query('SELECT * FROM executives ORDER BY id ASC');
          if (rows && rows.length > 0) {
            result.executives = rows;
          } else {
            const info = await db.query('SELECT content FROM department_info WHERE key_name = ?', ['executives_json']);
            if (info.length > 0 && info[0].content) {
              const parsed = JSON.parse(info[0].content);
              if (Array.isArray(parsed) && parsed.length > 0) {
                result.executives = parsed;
              }
            }
            if (!result.executives || result.executives.length === 0) {
              result.executives = [
                { full_name: 'Dela Stephen Dunyo', position: 'President', bio: 'Cybersecurity — 0553955493' },
                { full_name: 'Tetteh Reuben', position: 'Vice President', bio: 'BSc ITE — 0556529793' },
                { full_name: 'Poleson Godwin', position: 'Gen. Secretary', bio: '0547151838' },
                { full_name: 'Kitsi Roland', position: 'Fin. Secretary', bio: '0549090433' },
                { full_name: 'Naazir Godfred', position: 'Organizer', bio: '0243478600' },
                { full_name: 'Abdul Razzaq Adama', position: 'Treasurer', bio: '0597666651' },
                { full_name: 'Dosuntey Rose', position: 'Wocom', bio: '0592053083' }
              ];
            }
          }
        } catch (e) {
          try {
            const info = await db.query('SELECT content FROM department_info WHERE key_name = ?', ['executives_json']);
            if (info.length > 0 && info[0].content) {
              const parsed = JSON.parse(info[0].content);
              result.executives = Array.isArray(parsed) ? parsed : [];
            }
            if (!result.executives || result.executives.length === 0) {
              result.executives = [
                { full_name: 'Dela Stephen Dunyo', position: 'President', bio: 'Cybersecurity — 0553955493' },
                { full_name: 'Tetteh Reuben', position: 'Vice President', bio: 'BSc ITE — 0556529793' },
                { full_name: 'Poleson Godwin', position: 'Gen. Secretary', bio: '0547151838' },
                { full_name: 'Kitsi Roland', position: 'Fin. Secretary', bio: '0549090433' },
                { full_name: 'Naazir Godfred', position: 'Organizer', bio: '0243478600' },
                { full_name: 'Abdul Razzaq Adama', position: 'Treasurer', bio: '0597666651' },
                { full_name: 'Dosuntey Rose', position: 'Wocom', bio: '0592053083' }
              ];
              }
          } catch (_) {
            result.executives = [
              { full_name: 'Dela Stephen Dunyo', position: 'President', bio: 'Cybersecurity — 0553955493' },
              { full_name: 'Tetteh Reuben', position: 'Vice President', bio: 'BSc ITE — 0556529793' },
              { full_name: 'Poleson Godwin', position: 'Gen. Secretary', bio: '0547151838' },
              { full_name: 'Kitsi Roland', position: 'Fin. Secretary', bio: '0549090433' },
              { full_name: 'Naazir Godfred', position: 'Organizer', bio: '0243478600' },
              { full_name: 'Abdul Razzaq Adama', position: 'Treasurer', bio: '0597666651' },
              { full_name: 'Dosuntey Rose', position: 'Wocom', bio: '0592053083' }
            ];
          }
          } else {
            result.executives = [];
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
