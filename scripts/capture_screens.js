const puppeteer = require('puppeteer');
const path = require('path');
const fs = require('fs');

async function captureScreens() {
    const screensDir = path.join(__dirname, '../screenshots');
    if (!fs.existsSync(screensDir)) {
        fs.mkdirSync(screensDir);
    }

    const browser = await puppeteer.launch({ 
        headless: 'new',
        executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'
    });
    const page = await browser.newPage();
    
    // Set a standard desktop viewport
    await page.setViewport({ width: 1280, height: 800 });

    const baseUrl = 'http://localhost:3000';
    
    const targets = [
        { name: '1_trang_chu.png', url: `${baseUrl}/index.html` },
        { name: '2_phong_tap.png', url: `${baseUrl}/pages/gyms/search.html` },
        { name: '3_yeu_thich.png', url: `${baseUrl}/pages/user/favorites.html` },
        { name: '4_gym_chi_tiet.png', url: `${baseUrl}/pages/gyms/detail.html?id=1` },
        { name: '5_huan_luyen_vien.png', url: `${baseUrl}/pages/trainers/index.html` }
    ];

    for (const target of targets) {
        console.log(`Capturing ${target.name}...`);
        try {
            await page.goto(target.url, { waitUntil: 'networkidle2', timeout: 10000 });
            await page.screenshot({ path: path.join(screensDir, target.name), fullPage: true });
            console.log(`Saved ${target.name}`);
        } catch (error) {
            console.error(`Failed to capture ${target.name}:`, error.message);
        }
    }

    await browser.close();
    console.log('All screenshots captured successfully.');
}

captureScreens();
