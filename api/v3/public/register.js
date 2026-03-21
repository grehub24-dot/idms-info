const db = require('../db');
const bcrypt = require('bcryptjs');
const { sendEmail } = require('../auth/mailer');
const { sendSMS } = require('../sms/send');

module.exports = async function handler(req, res) {
  if (req.method !== 'POST') {
    res.statusCode = 405;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ error: 'Method not allowed' }));
    return;
  }

  try {
    const body = typeof req.body === 'string' ? JSON.parse(req.body || '{}') : (req.body || {});
    const { full_name, index_number, department, level, class: className, stream, email, phone_number } = body;

    if (!full_name || !index_number || !email || !phone_number) {
      res.statusCode = 400;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ error: 'Please fill all required fields.' }));
      return;
    }

    // Check duplicate index
    const indexRows = await db.query('SELECT id FROM students WHERE index_number = ?', [index_number]);
    if (indexRows.length > 0) {
      res.statusCode = 400;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ error: `Student with Index Number ${index_number} already exists.` }));
      return;
    }

    // Check email duplicate
    const emailRows = await db.query('SELECT id FROM users WHERE email = ?', [email]);
    if (emailRows.length > 0) {
      res.statusCode = 400;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({ error: 'Email address already registered.' }));
      return;
    }

    // Generate random 6-character password
    const autoPassword = Math.random().toString(36).slice(-6);
    const passwordHash = await bcrypt.hash(autoPassword, 10);

    const pool = db.getPool();
    const connection = await pool.getConnection();
    await connection.beginTransaction();

    try {
      // 1. Create User Account
      const [uResult] = await connection.execute(
        'INSERT INTO users (email, password, role, status, is_password_reset) VALUES (?, ?, "student", "active", 0)',
        [email, passwordHash]
      );
      const userId = uResult.insertId;

      const baseParams = [userId, index_number, full_name, department, level, phone_number, className || '', stream || ''];
      const attempts = [
        {
          sql: 'INSERT INTO students (user_id, index_number, full_name, department, level, phone_number, class_name, stream, is_profile_complete) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)',
          params: baseParams
        },
        {
          sql: 'INSERT INTO students (user_id, index_number, full_name, department, level, phone_number, class_name, stream) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
          params: baseParams
        },
        {
          sql: 'INSERT INTO students (user_id, index_number, full_name, department, level, phone_number) VALUES (?, ?, ?, ?, ?, ?)',
          params: [userId, index_number, full_name, department, level, phone_number]
        }
      ];

      let inserted = false;
      let lastInsertError = null;
      for (const attempt of attempts) {
        try {
          await connection.execute(attempt.sql, attempt.params);
          inserted = true;
          break;
        } catch (error) {
          lastInsertError = error;
          if (error && error.code !== 'ER_BAD_FIELD_ERROR') {
            throw error;
          }
        }
      }

      if (!inserted && lastInsertError) {
        throw lastInsertError;
      }

      await connection.commit();

      // 3. Send Notifications
      const welcomeMsg = `Welcome to INFOTESS! Reg successful. Index: ${index_number}. Your temporary password is: ${autoPassword}. Please login and reset your password.`;
      
      const [emailResult, smsResult] = await Promise.all([
        sendEmail({
          to: email,
          subject: 'Welcome to AAMUSTED - Infotess!',
          html: `
            <div style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; background-color: #ffffff;">
              <div style="background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); color: white; padding: 40px 20px; text-align: center;">
                <h1 style="margin: 0; font-size: 28px; font-weight: bold;">Welcome to AAMUSTED - Infotess!</h1>
                <p style="margin: 10px 0 0 0; font-size: 18px; opacity: 0.9;">Student Registration Successful</p>
              </div>
              
              <div style="padding: 30px; line-height: 1.6; color: #333;">
                <p style="font-size: 16px;">Dear <strong>${full_name.split(' ')[0]}</strong>,</p>
                <p style="font-size: 15px;">Congratulations! You have been successfully registered in our system. Below are your details:</p>
                
                <div style="margin: 25px 0; border: 1px solid #e0e0e0; border-radius: 4px; overflow: hidden;">
                  <div style="background-color: #f8f9fa; padding: 12px 15px; border-bottom: 1px solid #e0e0e0; color: #2575fc; font-weight: bold; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">
                    Student Information
                  </div>
                  <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <tr>
                      <td style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; width: 40%; color: #666; font-weight: 600;">Full Name:</td>
                      <td style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; color: #333;">${full_name}</td>
                    </tr>
                    <tr>
                      <td style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; color: #666; font-weight: 600;">Index Number:</td>
                      <td style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; color: #333;">${index_number}</td>
                    </tr>
                    <tr>
                      <td style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; color: #666; font-weight: 600;">Level:</td>
                      <td style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; color: #333;">Level ${level}</td>
                    </tr>
                    <tr>
                      <td style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; color: #666; font-weight: 600;">Class:</td>
                      <td style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; color: #333;">Class ${className || 'N/A'}</td>
                    </tr>
                    <tr>
                      <td style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; color: #666; font-weight: 600;">Department:</td>
                      <td style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; color: #333;">${department}</td>
                    </tr>
                    <tr>
                      <td style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; color: #666; font-weight: 600;">Email:</td>
                      <td style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; color: #2575fc;">${email}</td>
                    </tr>
                    <tr>
                      <td style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; color: #666; font-weight: 600;">Phone:</td>
                      <td style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; color: #333;">${phone_number}</td>
                    </tr>
                    <tr>
                      <td style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; color: #666; font-weight: 600;">Registration Date:</td>
                      <td style="padding: 12px 15px; border-bottom: 1px solid #f0f0f0; color: #333;">${new Date().toLocaleDateString()}</td>
                    </tr>
                    <tr>
                      <td style="padding: 12px 15px; color: #666; font-weight: 600;">Temporary Password:</td>
                      <td style="padding: 12px 15px; color: #333; font-weight: bold; background-color: #fff9c4;">${autoPassword}</td>
                    </tr>
                  </table>
                </div>
                
                <div style="margin-top: 30px;">
                  <p style="font-weight: bold; color: #444; margin-bottom: 10px;">Important Information:</p>
                  <ul style="padding-left: 20px; margin: 0; font-size: 14px; color: #555;">
                    <li style="margin-bottom: 8px;">Keep your index number safe - you'll need it for all transactions</li>
                    <li style="margin-bottom: 8px;">Use your temporary password to login, then reset it immediately</li>
                    <li style="margin-bottom: 8px;">All payment receipts will be sent to this email address</li>
                    <li style="margin-bottom: 8px;">Contact the finance office for any payment-related queries</li>
                  </ul>
                </div>
                
                <p style="margin-top: 25px; font-size: 14px; color: #666;">If you have any questions or notice any incorrect information, please contact the administration office immediately.</p>
                
                <div style="text-align: center; margin-top: 35px;">
                  <a href="${process.env.APP_URL || 'http://localhost:3000'}/login.html" style="background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); color: white; padding: 14px 30px; text-decoration: none; font-weight: bold; border-radius: 5px; display: inline-block; box-shadow: 0 4px 10px rgba(37, 117, 252, 0.3);">Login to Your Account</a>
                </div>
              </div>
              
              <div style="background-color: #f8f9fa; color: #999; padding: 20px; text-align: center; font-size: 12px; border-top: 1px solid #eeeeee;">
                &copy; ${new Date().getFullYear()} INFOTESS SDMS - AAMUSTED. All rights reserved.
              </div>
            </div>
          `
        }),
        sendSMS(phone_number, welcomeMsg)
      ]);

      res.statusCode = 200;
      res.setHeader('Content-Type', 'application/json');
      res.end(JSON.stringify({
        ok: true,
        success: true,
        message: 'Registration successful! Your temporary password has been sent via Email and SMS.',
        delivery: {
          email_sent: emailResult.success,
          sms_sent: smsResult.success
        }
      }));

    } catch (txError) {
      await connection.rollback();
      throw txError;
    } finally {
      connection.release();
    }

  } catch (error) {
    console.error('Registration error:', error);
    res.statusCode = 500;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ error: 'Internal server error: ' + error.message }));
  }
};
