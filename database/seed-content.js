const db = require('../api/v3/db');

async function upsertDepartmentInfo(key, content) {
  const rows = await db.query('SELECT id FROM department_info WHERE key_name = ?', [key]);
  if (rows.length > 0) {
    await db.query('UPDATE department_info SET content = ? WHERE key_name = ?', [content, key]);
  } else {
    await db.query('INSERT INTO department_info (key_name, content) VALUES (?, ?)', [key, content]);
  }
}

async function seedExecutives() {
  const list = [
    { full_name: 'Dela Stephen Dunyo', position: 'President', bio: 'Cybersecurity — 0553955493' },
    { full_name: 'Tetteh Reuben', position: 'Vice President', bio: 'BSc ITE — 0556529793' },
    { full_name: 'Poleson Godwin', position: 'Gen. Secretary', bio: '0547151838' },
    { full_name: 'Kitsi Roland', position: 'Fin. Secretary', bio: '0549090433' },
    { full_name: 'Naazir Godfred', position: 'Organizer', bio: '0243478600' },
    { full_name: 'Abdul Razzaq Adama', position: 'Treasurer', bio: '0597666651' },
    { full_name: 'Dosuntey Rose', position: 'Wocom', bio: '0592053083' }
  ];
  for (const e of list) {
    const rows = await db.query('SELECT id FROM executives WHERE full_name = ? AND position = ? LIMIT 1', [e.full_name, e.position]);
    if (rows.length === 0) {
      await db.query('INSERT INTO executives (full_name, position, bio) VALUES (?, ?, ?)', [e.full_name, e.position, e.bio || '']);
    }
  }
  await upsertDepartmentInfo('executives_json', JSON.stringify(list));
}

async function seedActivities() {
  const now = new Date();
  const items = [
    { title: 'Freshers Week Celebration', description: 'Welcome program for new students', date: now },
    { title: 'Community of Practice', description: 'Peer-led knowledge sharing sessions', date: now },
    { title: 'Infotess Cloud 9 Connection: Chocolate + Photoshoot (Valentine)', description: 'Valentine special social-tech event', date: now },
    { title: 'Infotess Assembly Meetings', description: 'Society general assembly', date: now }
  ];
  for (const a of items) {
    const rows = await db.query('SELECT id FROM activities WHERE title = ? LIMIT 1', [a.title]);
    if (rows.length === 0) {
      await db.query('INSERT INTO activities (title, description, activity_date) VALUES (?, ?, ?)', [a.title, a.description, a.date]);
    }
  }
}

async function seedDepartmentInfo() {
  const roles = [
    'Representing students\' interests and concerns to the faculty',
    'Organizing tech-related events and workshops for students',
    'Providing a platform for students to network and share knowledge'
  ];
  const patrons = [
    'Dr Prince Addo — Lecturer for Software Engineering',
    'Dr Oliver Kuffour Boansi — Lecturer for Database course'
  ];
  const alumni = [
    { full_name: 'Kwame Ahin Adu Ezekiel', graduation_year: '2024', position: 'President', company: '', testimonial: '', image_url: null },
    { full_name: 'Koomson Thomas', graduation_year: '2025', position: 'President', company: '', testimonial: '', image_url: null }
  ];
  await upsertDepartmentInfo('roles_markdown', roles.map((r, i) => `${i + 1}. ${r}`).join('\n'));
  await upsertDepartmentInfo('dues_info', 'Yearly dues at a cost of GHS 50.00');
  await upsertDepartmentInfo('patrons_info', patrons.join('\n'));
  await upsertDepartmentInfo('whatsapp_channel', 'https://whatsapp.com/channel/0029VaxYfmTHQbS7T4m9yx0B.');
  await upsertDepartmentInfo('projects_summary', 'Donated 15 P.A systems to all IT lecturers and educational lecturers.');
  await upsertDepartmentInfo('executives_year', '2025/26');
  await upsertDepartmentInfo('alumni_json', JSON.stringify(alumni));
}

async function main() {
  try {
    await seedExecutives();
    await seedActivities();
    await seedDepartmentInfo();
    process.stdout.write('Content seed completed\n');
    process.exit(0);
  } catch (e) {
    process.stderr.write(`Seed error: ${e.message}\n`);
    process.exit(1);
  }
}

main();
