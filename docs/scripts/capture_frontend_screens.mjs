/**
 * Capture all GYMFINDER frontend screens (viewport) then compose one PDF
 * with a browser chrome / address bar for each screen.
 */
import { chromium } from 'playwright';
import { createRequire } from 'module';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '../..');
const OUT_DIR = path.join(ROOT, 'DOCS', 'screenshots_raw');
const FINAL_PDF = path.join(ROOT, 'DOCS', 'GymFinder_Frontend_Screenshots.pdf');
const BASE = process.env.GF_BASE || 'http://localhost:8000';

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
  await page.goto(`${BASE}/pages/auth/login.php`, { waitUntil: 'networkidle' });
  await page.fill('input[name="login"]', email);
  await page.fill('input[name="password"]', password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }),
    page.click('button[type="submit"], .auth-btn-submit'),
  ]);
}

async function logout(page) {
  await page.goto(`${BASE}/pages/auth/logout.php`, { waitUntil: 'networkidle' }).catch(() => {});
}

async function capture() {
  fs.mkdirSync(OUT_DIR, { recursive: true });
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
    deviceScaleFactor: 1,
  });
  const page = await context.newPage();

  let currentRole = null;

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
    await page.goto(fullUrl, { waitUntil: 'networkidle' });
    await page.waitForTimeout(400);
    const file = path.join(OUT_DIR, `${screen.id}.png`);
    await page.screenshot({ path: file, fullPage: false });
    console.log('captured', screen.id, fullUrl);
  }

  await browser.close();
  return SCREENS.map((s) => ({
    ...s,
    file: path.join(OUT_DIR, `${s.id}.png`),
    fullUrl: `${BASE}${s.url}`,
  }));
}

async function composePdf(items) {
  const require = createRequire(import.meta.url);
  // Prefer python pillow compose via child process for reliability
  const metaPath = path.join(OUT_DIR, 'manifest.json');
  fs.writeFileSync(metaPath, JSON.stringify(items, null, 2));
  const { spawnSync } = await import('child_process');
  const py = path.join(__dirname, 'compose_screenshots_pdf.py');
  const r = spawnSync('python3', [py, metaPath, FINAL_PDF], { encoding: 'utf8' });
  if (r.stdout) process.stdout.write(r.stdout);
  if (r.stderr) process.stderr.write(r.stderr);
  if (r.status !== 0) throw new Error('compose failed');
  console.log('Wrote', FINAL_PDF);
}

const items = await capture();
await composePdf(items);
