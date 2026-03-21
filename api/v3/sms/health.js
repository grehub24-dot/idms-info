const toBool = (value) => {
  if (typeof value !== 'string') return false;
  const v = value.trim().toLowerCase();
  return v === '1' || v === 'true' || v === 'yes' || v === 'on';
};

module.exports = async function handler(req, res) {
  if (req.method !== 'GET') {
    res.statusCode = 405;
    res.setHeader('Content-Type', 'application/json');
    res.end(JSON.stringify({ ok: false, error: 'Method not allowed' }));
    return;
  }

  const envApiKey = process.env.WIGAL_API_KEY || process.env.SMS_API_KEY || '';
  const envUsername = process.env.WIGAL_USERNAME || process.env.SMS_USERNAME || '';
  const envSenderId = process.env.WIGAL_SENDER_ID || process.env.SMS_SENDER_ID || '';
  const endpoint = process.env.WIGAL_SMS_ENDPOINT || process.env.SMS_API_URL || 'https://frogapi.wigal.com.gh/api/v3/sms/send';
  const sslDisabled = toBool(process.env.SMS_DISABLE_SSL_VERIFY || '');

  const checks = {
    api_key: envApiKey !== '',
    username: envUsername !== '',
    sender_id: envSenderId !== '',
    endpoint: endpoint !== ''
  };

  const warnings = [];
  if (!checks.api_key || !checks.username || !checks.sender_id) {
    warnings.push('Set WIGAL_API_KEY, WIGAL_USERNAME and WIGAL_SENDER_ID in Vercel environment variables.');
  }
  if (sslDisabled) {
    warnings.push('SMS_DISABLE_SSL_VERIFY is enabled. Use only for local development.');
  }

  res.statusCode = 200;
  res.setHeader('Content-Type', 'application/json');
  res.end(JSON.stringify({
    ok: checks.api_key && checks.username && checks.sender_id && checks.endpoint,
    checks,
    settings: {
      endpoint,
      ssl_verify_disabled: sslDisabled
    },
    warnings
  }));
};
