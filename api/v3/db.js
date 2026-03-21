const mysql = require('mysql2/promise');

let pool;

const getPool = () => {
  if (!pool) {
    pool = mysql.createPool({
      host: process.env.DB_HOST || 'localhost',
      user: process.env.DB_USER || 'root',
      password: process.env.DB_PASS || '',
      database: process.env.DB_NAME || 'infotess_sdms',
      waitForConnections: true,
      connectionLimit: 10,
      queueLimit: 0
    });
  }
  return pool;
};

const query = async (sql, params) => {
  const [rows] = await getPool().execute(sql, params);
  return rows;
};

module.exports = {
  query,
  getPool
};
