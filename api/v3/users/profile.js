const db = require('../db');
const { verifyToken } = require('../auth/jwt');
const formidable = require('formidable');
const fs = require('fs');
const path = require('path');

module.exports = async function handler(req, res) {
  if (req.method === 'OPTIONS') {
    res.statusCode = 204;
    res.setHeader('Allow', 'GET, POST, OPTIONS');
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

  if (!decoded) {
    res.statusCode = 401;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ ok: false, error: 'Invalid or expired token' }));
    return;
  }

  const userId = decoded.user_id;

  if (req.method === 'GET') {
    try {
      // Fetch user and student data
      const rows = await db.query(
        'SELECT u.email, u.role, s.* FROM users u LEFT JOIN students s ON u.id = s.user_id WHERE u.id = ?',
        [userId]
      );
      const profile = rows[0];

      if (!profile) {
        res.statusCode = 404;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ ok: false, error: 'Profile not found' }));
        return;
      }

      res.statusCode = 200;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ ok: true, student: profile }));
    } catch (error) {
      console.error('Get profile error:', error);
      res.statusCode = 500;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ ok: false, error: 'Internal server error' }));
    }
  } else if (req.method === 'POST') {
    // Handle multipart/form-data for profile update
    const form = new formidable.IncomingForm();
    const uploadDir = path.join(process.cwd(), 'images', 'profiles');
    
    if (!fs.existsSync(uploadDir)) {
      fs.mkdirSync(uploadDir, { recursive: true });
    }

    form.uploadDir = uploadDir;
    form.keepExtensions = true;

    form.parse(req, async (err, fields, files) => {
      if (err) {
        res.statusCode = 500;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ ok: false, error: 'Error parsing form data' }));
        return;
      }

      const email = Array.isArray(fields.email) ? fields.email[0] : fields.email;
      const phone = Array.isArray(fields.phone_number) ? fields.phone_number[0] : fields.phone_number;

      if (!email || !phone) {
        res.statusCode = 400;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ ok: false, error: 'Email and phone number are required' }));
        return;
      }

      try {
        let profilePicture = null;
        const incomingFile = Array.isArray(files.profile_picture) ? files.profile_picture[0] : files.profile_picture;
        if (incomingFile && Number(incomingFile.size || 0) > 0) {
          const file = incomingFile;
          const ext = path.extname(file.originalFilename || file.filepath);
          // Get student index number for filename
          const sRows = await db.query('SELECT index_number, profile_picture FROM students WHERE user_id = ?', [userId]);
          const indexNumber = sRows[0] ? sRows[0].index_number : 'unknown';
          
          const newFileName = `${indexNumber}_${Date.now()}${ext}`;
          const newPath = path.join(uploadDir, newFileName);
          
          fs.renameSync(file.filepath, newPath);
          profilePicture = `images/profiles/${newFileName}`;
        }

        const existingRows = await db.query('SELECT profile_picture FROM students WHERE user_id = ?', [userId]);
        const existingPicture = existingRows[0] ? existingRows[0].profile_picture : null;
        const finalProfilePicture = profilePicture || existingPicture || null;
        const isProfileComplete = finalProfilePicture ? 1 : 0;

        // Transactional update
        const pool = db.getPool();
        const connection = await pool.getConnection();
        await connection.beginTransaction();

        try {
          // Update users table
          await connection.execute('UPDATE users SET email = ? WHERE id = ?', [email, userId]);

          // Update students table
          if (profilePicture) {
            try {
              await connection.execute(
                'UPDATE students SET phone_number = ?, profile_picture = ?, is_profile_complete = 1 WHERE user_id = ?',
                [phone, profilePicture, userId]
              );
            } catch (error) {
              if (error && error.code === 'ER_BAD_FIELD_ERROR') {
                await connection.execute(
                  'UPDATE students SET phone_number = ?, profile_picture = ? WHERE user_id = ?',
                  [phone, profilePicture, userId]
                );
              } else {
                throw error;
              }
            }
          } else {
            try {
              await connection.execute(
                'UPDATE students SET phone_number = ?, is_profile_complete = ? WHERE user_id = ?',
                [phone, isProfileComplete, userId]
              );
            } catch (error) {
              if (error && error.code === 'ER_BAD_FIELD_ERROR') {
                await connection.execute(
                  'UPDATE students SET phone_number = ? WHERE user_id = ?',
                  [phone, userId]
                );
              } else {
                throw error;
              }
            }
          }

          await connection.commit();
          res.statusCode = 200;
          res.setHeader('Content-Type', 'application/json');
          res.end(JSON.stringify({
            ok: true,
            message: isProfileComplete ? 'Profile updated successfully' : 'Profile saved. Please upload your profile picture to complete setup.',
            profile_complete: isProfileComplete === 1
          }));
        } catch (txError) {
          await connection.rollback();
          throw txError;
        } finally {
          connection.release();
        }
      } catch (error) {
        console.error('Update profile error:', error);
        res.statusCode = 500;
        res.setHeader('Content-Type', 'application/json');
        res.end(JSON.stringify({ ok: false, error: 'Database error' }));
      }
    });
  } else {
    res.statusCode = 405;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ ok: false, error: 'Method not allowed' }));
  }
};
