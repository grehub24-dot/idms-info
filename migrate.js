const db = require('./api/v3/db');

async function migrate() {
  console.log('Starting database migration...');
  
  try {
    // Helper to check if column exists
    const columnExists = async (table, column) => {
      const rows = await db.query(`
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = ? AND COLUMN_NAME = ? AND TABLE_SCHEMA = DATABASE()
      `, [table, column]);
      return rows.length > 0;
    };

    // Add is_password_reset to users
    if (!(await columnExists('users', 'is_password_reset'))) {
      console.log('Adding is_password_reset to users table...');
      await db.query('ALTER TABLE users ADD COLUMN is_password_reset TINYINT(1) DEFAULT 0 AFTER status');
    } else {
      console.log('is_password_reset already exists in users table.');
    }

    // Add is_profile_complete to students
    if (!(await columnExists('students', 'is_profile_complete'))) {
      console.log('Adding is_profile_complete to students table...');
      await db.query('ALTER TABLE students ADD COLUMN is_profile_complete TINYINT(1) DEFAULT 0 AFTER phone_number');
    } else {
      console.log('is_profile_complete already exists in students table.');
    }

    console.log('Migration completed successfully.');
    process.exit(0);
  } catch (error) {
    console.error('Migration failed:', error);
    process.exit(1);
  }
}

migrate();
