import puppeteer from 'puppeteer-core';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { spawnSync } from 'child_process';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '../..');
const OUT_DIR = path.join(ROOT, 'DOCS', 'screenshots_raw');
const FINAL_PDF = path.join(ROOT, 'DOCS', 'GymFinder_Frontend_Screenshots.pdf');
const FINAL_PNG = path.join(ROOT, 'DOCS', 'GymFinder_Frontend_Screenshots.png');
const BASE = process.env.GF_BASE || 'http://localhost:8000';
const CHROME =
  process.env.CHROME_PATH ||
  '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';

const SCREENS = [
  { id: '01-home', title: 'Trang chủ', url: '/', guest: true },
  { id: '02-gyms-search', title: 'Tìm kiếm phòng tập', url: '/pages/gyms/search.php', guest: true },
  { id: '03-gym-detail', title: 'Chi tiết phòng tập', url: '/pages/gyms/detail.php?id=1', guest: true },
  { id: '04-trainers', title: 'Danh sách HLV', url: '/pages/trainers/index.php', guest: true },
  { id: '05-trainer-detail', title: 'Chi tiết HLV', url: '/pages/trainers/detail.php?id=1', guest: true },
  { id: '06-login', title: 'Đăng nhập', url: '/pages/auth/login.php', guest: true },
  { id: '07-register', title: 'Đăng ký', url: '/pages/auth/register.php', guest: true },
  { id: '08-forgot-password', title: 'Quên mật khẩu', url: '/pages/auth/forgot_password.php', guest: true },
  { id: '09-terms', title: 'Điều khoản dịch vụ', url: '/pages/legal/terms.php', guest: true },
  { id: '10-privacy', title: 'Chính sách bảo mật', url: '/pages/legal/privacy.php', guest: true },
  { id: '11-favorites-guest', title: 'Yêu thích (Guest)', url: '/pages/user/favorites.php', guest: true },
  { id: '12-favorites-user', title: 'Yêu thích (User)', url: '/pages/user/favorites.php', role: 'user' },
  { id: '13-profile-user', title: 'Hồ sơ cá nhân (User)', url: '/pages/user/profile.php', role: 'user' },
  { id: '14-admin-dashboard', title: 'Admin — Dashboard', url: '/admin/pages/dashboard.php', role: 'admin' },
  { id: '15-admin-gyms', title: 'Admin — Phòng tập', url: '/admin/pages/gyms.php', role: 'admin' },
  { id: '16-admin-trainers', title: 'Admin — HLV', url: '/admin/pages/trainers.php', role: 'admin' },
  { id: '17-admin-users', title: 'Admin — Người dùng', url: '/admin/pages/users.php', role: 'admin' },
  { id: '18-admin-catalogs', title: 'Admin — Danh mục', url: '/admin/pages/catalogs.php', role: 'admin' },
  { id: '19-admin-reviews', title: 'Admin — Đánh giá', url: '/admin/pages/reviews.php', role: 'admin' },
  { id: '20-admin-profile', title: 'Admin — Hồ sơ', url: '/admin/pages/profile.php', role: 'admin' },
];

async function login(page, email, password) {
  await page.goto(`${BASE}/pages/auth/login.php`, { waitUntil: 'networkidle2', timeout: 30000 });
  await page.type('input[name="login"]', email, { delay: 10 });
  await page.type('input[name="password"]', password, { delay: 10 });
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 30000 }),
    page.click('button[type="submit"]'),
  ]);
}

async function logout(page) {
  try {
    await page.goto(`${BASE}/pages/auth/logout.php`, { waitUntil: 'networkidle2', timeout: 20000 });
  } catch {
    /* ignore */
  }
}

const browser = await puppeteer.launch({
  executablePath: CHROME,
  headless: true,
  defaultViewport: { width: 1440, height: 900 },
  args: ['--hide-scrollbars', '--disable-gpu', '--no-first-run'],
});

fs.mkdirSync(OUT_DIR, { recursive: true });
const page = await browser.newPage();
let currentRole = null;
const items = [];

for (const screen of SCREENS) {
  const need = screen.role || (screen.guest ? 'guest' : null);
  if (need === 'guest' && currentRole !== 'guest') {
    await logout(page);
    currentRole = 'guest';
  } else if (need === 'user' && currentRole !== 'user') {
    await logout(page);
    await login(page, 'u02@gymfinder.test', 'password');
    currentRole = 'user';
  } else if (need === 'admin' && currentRole !== 'admin') {
    await logout(page);
    await login(page, 'u01@gymfinder.test', 'password');
    currentRole = 'admin';
  }

  const fullUrl = `${BASE}${screen.url}`;
  await page.goto(fullUrl, { waitUntil: 'networkidle2', timeout: 30000 });
  await new Promise((r) => setTimeout(r, 350));
  const file = path.join(OUT_DIR, `${screen.id}.png`);
  await page.screenshot({ path: file, type: 'png' });
  items.push({ ...screen, file, fullUrl });
  console.log('ok', screen.id);
}

await browser.close();

const metaPath = path.join(OUT_DIR, 'manifest.json');
fs.writeFileSync(metaPath, JSON.stringify(items, null, 2));
const py = path.join(__dirname, 'compose_screenshots_pdf.py');
const r = spawnSync('python3', [py, metaPath, FINAL_PDF, FINAL_PNG], { encoding: 'utf8' });
if (r.stdout) process.stdout.write(r.stdout);
if (r.stderr) process.stderr.write(r.stderr);
if (r.status !== 0) process.exit(r.status || 1);
console.log('DONE', FINAL_PDF, FINAL_PNG);
