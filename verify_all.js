const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();

  try {
    // Go to login page
    await page.goto('http://localhost:8000/index.php');

    // Fill login form
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'password');

    // Click login
    await page.click('button[type="submit"]');

    // Wait for navigation to dashboard
    await page.waitForURL('**/dashboard.php');

    // Take screenshot of dashboard
    await page.screenshot({ path: '/home/jules/verification/dashboard.png', fullPage: true });
    console.log('Dashboard screenshot saved');

    // Go to Lapor Bullying
    await page.goto('http://localhost:8000/views/lapor_bullying.php');
    await page.screenshot({ path: '/home/jules/verification/lapor_bullying.png', fullPage: true });
    console.log('Lapor Bullying screenshot saved');

    // Go to Mood Tracker
    await page.goto('http://localhost:8000/views/mood_tracker.php');
    await page.screenshot({ path: '/home/jules/verification/mood_tracker.png', fullPage: true });
    console.log('Mood Tracker screenshot saved');

    // Go to Admin User Management
    await page.goto('http://localhost:8000/views/admin_users.php');
    await page.screenshot({ path: '/home/jules/verification/admin_users.png', fullPage: true });
    console.log('Admin Users screenshot saved');

  } catch (error) {
    console.error('Error during verification:', error);
    // Take a screenshot of the error state if possible
    await page.screenshot({ path: '/home/jules/verification/error.png' });
  } finally {
    await browser.close();
  }
})();
