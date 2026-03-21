module.exports = async function handler(req, res) {
  // For JWT, logout is primarily a frontend action (deleting the token).
  // This endpoint can be used to signal the end of a session if needed.
  
  res.statusCode = 200;
  res.setHeader('Content-Type', 'application/json');
  res.end(JSON.stringify({
    success: true,
    ok: true,
    message: 'Logged out successfully'
  }));
};
