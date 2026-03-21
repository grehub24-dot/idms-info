const https = require('https');
const { URL } = require('url');

const toBool = (value) => {
  if (typeof value !== 'string') return false;
  const v = value.trim().toLowerCase();
  return v === '1' || v === 'true' || v === 'yes' || v === 'on';
};

const sendRequest = (endpoint, headers, payload, rejectUnauthorized) => {
  return new Promise((resolve, reject) => {
    const url = new URL(endpoint);
    const data = JSON.stringify(payload);
    const req = https.request(
      {
        protocol: url.protocol,
        hostname: url.hostname,
        port: url.port || 443,
        path: `${url.pathname}${url.search}`,
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Content-Length': Buffer.byteLength(data),
          ...headers
        },
        rejectUnauthorized
      },
      (res) => {
        let raw = '';
        res.on('data', (chunk) => {
          raw += chunk;
        });
        res.on('end', () => {
          resolve({ statusCode: res.statusCode || 0, body: raw });
        });
      }
    );

    req.on('error', reject);
    req.write(data);
    req.end();
  });
};

/**
 * Utility function to send SMS via Wigal API
 */
const sendSMS = async (to, message) => {
  const apiKey = process.env.WIGAL_API_KEY || process.env.SMS_API_KEY || '$2y$10$6oYYcjc6Ge3/W.P.1Yqk6eHBs0ERVFR6IaBQ2qpYGBnMYp28B3uPe';
  const username = process.env.WIGAL_USERNAME || process.env.SMS_USERNAME || 'amanvid';
  const senderId = process.env.WIGAL_SENDER_ID || process.env.SMS_SENDER_ID || 'INFOTESS';
  const endpoint = process.env.WIGAL_SMS_ENDPOINT || process.env.SMS_API_URL || 'https://frogapi.wigal.com.gh/api/v3/sms/send';
  const sslDisabled = toBool(process.env.SMS_DISABLE_SSL_VERIFY || '');

  if (!apiKey || !username || !senderId) {
    console.error('SMS credentials are not configured');
    return { success: false, error: 'SMS credentials are not configured' };
  }

  const payload = {
    senderid: senderId,
    destinations: [
      {
        destination: String(to).trim(),
        message: String(message).trim(),
        msgid: `MSG${Date.now()}`,
        smstype: 'text'
      }
    ]
  };

  try {
    const result = await sendRequest(
      endpoint,
      { 'API-KEY': apiKey, USERNAME: username },
      payload,
      !sslDisabled
    );

    let parsed = null;
    try {
      parsed = result.body ? JSON.parse(result.body) : null;
    } catch (e) {
      console.error('Error parsing Wigal response:', e);
    }

    if (result.statusCode >= 200 && result.statusCode < 300) {
      return { success: true, body: parsed };
    } else {
      console.error('Wigal API error:', result.body);
      return { success: false, error: `Wigal API returned status ${result.statusCode}`, body: parsed };
    }
  } catch (error) {
    console.error('Error sending SMS via Wigal:', error);
    return { success: false, error: error.message };
  }
};

/**
 * Vercel Serverless Function Handler
 */
const handler = async function (req, res) {
  if (req.method !== 'POST') {
    res.statusCode = 405;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ ok: false, error: 'Method not allowed' }));
    return;
  }

  const body = typeof req.body === 'string' ? JSON.parse(req.body || '{}') : (req.body || {});
  const to = String(body.to || '').trim();
  const message = String(body.message || '').trim();

  if (!to || !message) {
    res.statusCode = 400;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ ok: false, error: 'to and message are required' }));
    return;
  }

  const result = await sendSMS(to, message);

  res.statusCode = result.success ? 200 : 500;
  res.setHeader('Content-Type', 'application/json');
  res.end(JSON.stringify({ ok: result.success, ...result }));
};

module.exports = handler;
module.exports.sendSMS = sendSMS;
