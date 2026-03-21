const db = require('../db');
const { verifyToken } = require('../auth/jwt');
const bcrypt = require('bcryptjs');
const formidable = require('formidable');
const fs = require('fs');

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
      // List/Search Students OR Get by Index
      const idParamRaw = req.query.id;
      if (idParamRaw !== undefined && String(idParamRaw).trim() !== '') {
        const idParam = parseInt(String(idParamRaw), 10);
        if (!Number.isFinite(idParam) || idParam <= 0) {
          res.statusCode = 400;
          res.setHeader('Content-Type', 'application/json');
          res.end(JSON.stringify({ success: false, error: 'Invalid id parameter' }));
          return;
        }
        let rows = [];
        try {
          rows = await db.query(
            `SELECT s.id as student_id, s.full_name, s.index_number, s.department, s.level,
                    s.class_name, s.stream, s.phone_number as phone, s.profile_picture, s.created_at, s.updated_at,
                    u.email
             FROM students s
             LEFT JOIN users u ON s.user_id = u.id
             WHERE s.id = ? LIMIT 1`,
            [idParam]
          );
        } catch (e) {
          if (e && e.code === 'ER_BAD_FIELD_ERROR') {
            rows = await db.query(
              `SELECT s.id as student_id, s.full_name, s.index_number, s.department, s.level,
                      s.phone_number as phone, s.profile_picture, s.created_at, s.updated_at,
                      u.email
               FROM students s
               LEFT JOIN users u ON s.user_id = u.id
               WHERE s.id = ? LIMIT 1`,
              [idParam]
            );
          } else {
            throw e;
          }
        }
        res.statusCode = 200;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ ok: true, success: true, student: rows[0] || null }));
        return;
      }
      const indexNumberParam = String(req.query.index_number || '').trim();
      if (indexNumberParam !== '') {
        const rows = await db.query(
          `SELECT s.id as student_id, s.full_name, s.index_number, s.department, s.level, 
                  u.email, s.phone_number as phone, s.created_at, s.updated_at
           FROM students s
           LEFT JOIN users u ON s.user_id = u.id
           WHERE s.index_number = ? LIMIT 1`,
          [indexNumberParam]
        );
        res.statusCode = 200;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ ok: true, success: true, student: rows[0] || null }));
        return;
      }

      const limitRaw = Number(req.query.limit);
      const limit = Number.isFinite(limitRaw) && limitRaw > 0 ? Math.min(200, Math.max(1, Math.floor(limitRaw))) : 50;
      const q = String(req.query.q || '').trim();
      
      let rows = [];
      const params = [];
      const qParams = [];
      let where = '';

      if (q !== '') {
        where = ` WHERE s.full_name LIKE ? OR s.index_number LIKE ? OR s.department LIKE ?`;
        const like = `%${q}%`;
        qParams.push(like, like, like);
      }

      try {
        const queryWithClassStream = `
          SELECT s.id as student_id, s.full_name, s.index_number, s.department, s.level,
                 s.class_name, s.stream, s.profile_picture,
                 u.email, s.phone_number as phone, s.created_at, s.updated_at
          FROM students s
          LEFT JOIN users u ON s.user_id = u.id
          ${where}
          ORDER BY s.id DESC
          LIMIT ${limit}
        `;
        rows = await db.query(queryWithClassStream, qParams);
      } catch (e) {
        if (e && e.code === 'ER_BAD_FIELD_ERROR') {
          const queryFallback = `
            SELECT s.id as student_id, s.full_name, s.index_number, s.department, s.level,
                   s.profile_picture,
                   u.email, s.phone_number as phone, s.created_at, s.updated_at
            FROM students s
            LEFT JOIN users u ON s.user_id = u.id
            ${where}
            ORDER BY s.id DESC
            LIMIT ${limit}
          `;
          rows = await db.query(queryFallback, qParams);
        } else {
          throw e;
        }
      }

      res.statusCode = 200;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ ok: true, success: true, students: rows }));

    } else if (method === 'POST') {
      // Create Student OR Bulk Upload
      if (req.headers['content-type'] && req.headers['content-type'].includes('multipart/form-data')) {
        // Bulk Upload
        const form = new formidable.IncomingForm();
        form.parse(req, async (err, fields, files) => {
          if (err || !files.file) {
            res.statusCode = 400;
            res.setHeader('Content-Type', 'application/json');
            res.end(JSON.stringify({ success: false, error: 'CSV file is required' }));
            return;
          }

          const csvData = fs.readFileSync(files.file.filepath, 'utf8');
          const lines = csvData.split(/\r?\n/);
          if (lines.length < 2) {
            res.statusCode = 400;
            res.setHeader('Content-Type', 'application/json');
            res.end(JSON.stringify({ success: false, error: 'CSV is empty' }));
            return;
          }

          const header = lines[0].split(',').map(h => h.trim().toLowerCase());
          const map = {};
          header.forEach((h, i) => { map[h] = i; });

          const required = ['full_name', 'index_number', 'department', 'level', 'password'];
          for (const r of required) {
            if (map[r] === undefined) {
              res.statusCode = 400;
              res.setHeader('Content-Type', 'application/json');
              res.end(JSON.stringify({ success: false, error: `Missing required column: ${r}` }));
              return;
            }
          }

          let created = 0;
          let errors = [];
          const pool = db.getPool();

          for (let i = 1; i < lines.length; i++) {
            const line = lines[i].split(',').map(c => c.trim());
            if (line.length < header.length) continue;

            const fullName = line[map['full_name']];
            const indexNumber = line[map['index_number']];
            const department = line[map['department']];
            const level = line[map['level']];
            const password = line[map['password']];
            const email = map['email'] !== undefined ? line[map['email']] : null;
            const phone = map['phone'] !== undefined ? line[map['phone']] : null;

            if (!fullName || !indexNumber || !department || !level || !password) {
              errors.push({ row: i + 1, error: 'Missing required fields' });
              continue;
            }

            const connection = await pool.getConnection();
            await connection.beginTransaction();
            try {
              const hash = await bcrypt.hash(password, 10);
              const [uResult] = await connection.execute(
                'INSERT INTO users (email, password, role, status) VALUES (?, ?, "student", "active")',
                [email || null, hash]
              );
              const userId = uResult.insertId;

              await connection.execute(
                'INSERT INTO students (user_id, index_number, full_name, department, level, phone_number) VALUES (?, ?, ?, ?, ?, ?)',
                [userId, indexNumber, fullName, department, level, phone || null]
              );

              await connection.commit();
              created++;
            } catch (txError) {
              await connection.rollback();
              errors.push({ row: i + 1, error: txError.message });
            } finally {
              connection.release();
            }
          }

          res.statusCode = 200;
          res.setHeader('Content-Type', 'application/json');
          res.end(JSON.stringify({ ok: true, success: true, created, errors }));
        });
        return;
      }

      // Single Create Student
      const body = typeof req.body === 'string' ? JSON.parse(req.body || '{}') : (req.body || {});
      const { full_name, index_number, department, level, email, phone, password, class_name, stream } = body;

      if (!full_name || !index_number || !department || !level || !password) {
        res.statusCode = 400;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ success: false, error: 'Missing required fields' }));
        return;
      }

      const pool = db.getPool();
      const connection = await pool.getConnection();
      await connection.beginTransaction();

      try {
        // 1. Create User
        const hash = await bcrypt.hash(password, 10);
        const [uResult] = await connection.execute(
          'INSERT INTO users (email, password, role, status) VALUES (?, ?, "student", "active")',
          [email || null, hash]
        );
        const userId = uResult.insertId;

        // 2. Create Student (with fallback if class_name/stream columns missing)
        let sResult;
        try {
          const [ins] = await connection.execute(
            'INSERT INTO students (user_id, index_number, full_name, department, level, phone_number, class_name, stream) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [userId, index_number, full_name, department, level, phone || null, class_name || '', stream || '']
          );
          sResult = ins;
        } catch (e) {
          if (e && e.code === 'ER_BAD_FIELD_ERROR') {
            const [ins2] = await connection.execute(
              'INSERT INTO students (user_id, index_number, full_name, department, level, phone_number) VALUES (?, ?, ?, ?, ?, ?)',
              [userId, index_number, full_name, department, level, phone || null]
            );
            sResult = ins2;
          } else {
            throw e;
          }
        }

        await connection.commit();
        res.statusCode = 201;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ ok: true, success: true, student_id: sResult.insertId }));
      } catch (txError) {
        await connection.rollback();
        throw txError;
      } finally {
        connection.release();
      }

    } else if (method === 'PUT') {
      // Update Student (supports JSON or multipart for profile picture)
      const contentType = String(req.headers['content-type'] || '');
      if (contentType.includes('multipart/form-data')) {
        const formidable = require('formidable');
        const path = require('path');
        const fs = require('fs');
        const form = new formidable.IncomingForm();
        const uploadDir = path.join(process.cwd(), 'images', 'profiles');
        if (!fs.existsSync(uploadDir)) {
          fs.mkdirSync(uploadDir, { recursive: true });
        }
        form.uploadDir = uploadDir;
        form.keepExtensions = true;
        form.parse(req, async (err, fields, files) => {
          if (err) {
            res.statusCode = 400;
            res.setHeader('Content-Type', 'application/json');
            res.end(JSON.stringify({ success: false, error: 'Invalid form data' }));
            return;
          }
          const student_id = parseInt(Array.isArray(fields.student_id) ? fields.student_id[0] : fields.student_id, 10);
          const full_name = Array.isArray(fields.full_name) ? fields.full_name[0] : fields.full_name;
          const index_number = Array.isArray(fields.index_number) ? fields.index_number[0] : fields.index_number;
          const department = Array.isArray(fields.department) ? fields.department[0] : fields.department;
          const level = Array.isArray(fields.level) ? fields.level[0] : fields.level;
          const email = Array.isArray(fields.email) ? fields.email[0] : fields.email;
          const phone = Array.isArray(fields.phone_number) ? fields.phone_number[0] : fields.phone_number;
          const class_name = Array.isArray(fields.class_name) ? fields.class_name[0] : fields.class_name;
          const stream = Array.isArray(fields.stream) ? fields.stream[0] : fields.stream;

          if (!student_id) {
            res.statusCode = 400;
            res.setHeader('Content-Type', 'application/json');
            res.end(JSON.stringify({ success: false, error: 'Student ID is required' }));
            return;
          }

          let profilePicture = null;
          const incomingFile = Array.isArray(files.profile_picture) ? files.profile_picture[0] : files.profile_picture;
          if (incomingFile && Number(incomingFile.size || 0) > 0) {
            const ext = path.extname(incomingFile.originalFilename || incomingFile.filepath || '');
            const newFileName = `${index_number || 'student'}_${Date.now()}${ext}`;
            const newPath = path.join(uploadDir, newFileName);
            fs.renameSync(incomingFile.filepath, newPath);
            profilePicture = `images/profiles/${newFileName}`;
          }

          const pool = db.getPool();
          const connection = await pool.getConnection();
          await connection.beginTransaction();
          try {
            const [rows] = await connection.execute('SELECT user_id FROM students WHERE id = ?', [student_id]);
            if (rows.length === 0) {
              res.statusCode = 404;
              res.setHeader('Content-Type', 'application/json');
              res.end(JSON.stringify({ success: false, error: 'Student not found' }));
              return;
            }
            const userId = rows[0].user_id;

            // Build update with fallbacks for class_name/stream and profile_picture
            try {
              await connection.execute(
                'UPDATE students SET full_name = ?, index_number = ?, department = ?, level = ?, class_name = ?, stream = ?, phone_number = ?, ' +
                (profilePicture ? 'profile_picture = ?, ' : '') +
                'updated_at = NOW() WHERE id = ?',
                profilePicture
                  ? [full_name, index_number, department, level, class_name || '', stream || '', phone || null, profilePicture, student_id]
                  : [full_name, index_number, department, level, class_name || '', stream || '', phone || null, student_id]
              );
            } catch (e) {
              if (e && e.code === 'ER_BAD_FIELD_ERROR') {
                // Fallback without class_name/stream
                await connection.execute(
                  'UPDATE students SET full_name = ?, index_number = ?, department = ?, level = ?, phone_number = ?, ' +
                  (profilePicture ? 'profile_picture = ?, ' : '') +
                  'updated_at = NOW() WHERE id = ?',
                  profilePicture
                    ? [full_name, index_number, department, level, phone || null, profilePicture, student_id]
                    : [full_name, index_number, department, level, phone || null, student_id]
                );
              } else {
                throw e;
              }
            }

            if (userId && email) {
              await connection.execute('UPDATE users SET email = ? WHERE id = ?', [email, userId]);
            }

            await connection.commit();
            res.statusCode = 200;
            res.setHeader('Content-Type', 'application/json');
            res.end(JSON.stringify({ ok: true, success: true, message: 'Student updated successfully' }));
          } catch (txError) {
            await connection.rollback();
            throw txError;
          } finally {
            connection.release();
          }
        });
        return;
      }

      // JSON body update
      const body = typeof req.body === 'string' ? JSON.parse(req.body || '{}') : (req.body || {});
      const { student_id, full_name, index_number, department, level, email, phone, class_name, stream } = body;

      if (!student_id) {
        res.statusCode = 400;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ success: false, error: 'Student ID is required' }));
        return;
      }

      const pool = db.getPool();
      const connection = await pool.getConnection();
      await connection.beginTransaction();
      try {
        const [rows] = await connection.execute('SELECT user_id FROM students WHERE id = ?', [student_id]);
        if (rows.length === 0) {
          res.statusCode = 404;
          res.setHeader('Content-Type', 'application/json');
          res.end(JSON.stringify({ success: false, error: 'Student not found' }));
          return;
        }
        const userId = rows[0].user_id;

        try {
          await connection.execute(
            'UPDATE students SET full_name = ?, index_number = ?, department = ?, level = ?, class_name = ?, stream = ?, phone_number = ?, updated_at = NOW() WHERE id = ?',
            [full_name, index_number, department, level, class_name || '', stream || '', phone || null, student_id]
          );
        } catch (e) {
          if (e && e.code === 'ER_BAD_FIELD_ERROR') {
            await connection.execute(
              'UPDATE students SET full_name = ?, index_number = ?, department = ?, level = ?, phone_number = ?, updated_at = NOW() WHERE id = ?',
              [full_name, index_number, department, level, phone || null, student_id]
            );
          } else {
            throw e;
          }
        }

        if (userId && email) {
          await connection.execute('UPDATE users SET email = ? WHERE id = ?', [email, userId]);
        }

        await connection.commit();
        res.statusCode = 200;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ ok: true, success: true, message: 'Student updated successfully' }));
      } catch (txError) {
        await connection.rollback();
        throw txError;
      } finally {
        connection.release();
      }

    } else if (method === 'DELETE') {
      // Delete Student
      const student_id = req.query.id;
      if (!student_id) {
        res.statusCode = 400;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ success: false, error: 'Student ID is required' }));
        return;
      }

      const pool = db.getPool();
      const connection = await pool.getConnection();
      await connection.beginTransaction();

      try {
        // Find user_id
        const [rows] = await connection.execute('SELECT user_id FROM students WHERE id = ?', [student_id]);
        const userId = rows[0] ? rows[0].user_id : null;

        // Delete student
        await connection.execute('DELETE FROM students WHERE id = ?', [student_id]);
        
        // Delete user
        if (userId) {
          await connection.execute('DELETE FROM users WHERE id = ?', [userId]);
        }

        await connection.commit();
        res.statusCode = 200;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ ok: true, success: true, message: 'Student deleted successfully' }));
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
    console.error('Admin students API error:', error);
    res.statusCode = 500;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ success: false, error: 'Internal server error' }));
  }
};

function min(a, b) { return a < b ? a : b; }
