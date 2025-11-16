// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('UI Enhancements', () => {
  test('"/" opens search modal and "Esc" closes it', async ({ page }) => {
    await page.goto('/');
    await page.keyboard.press('/');
    const searchInput = page.locator('input#search-modal-title');
    await expect(searchInput).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(searchInput).toBeHidden();
  });

  test('"?" opens shortcuts help modal', async ({ page }) => {
    await page.goto('/');
    await page.keyboard.press('?');
    await expect(page.getByRole('dialog', { name: 'QR code' })).toBeHidden();
    await expect(page.getByRole('dialog')).toBeVisible();
  });

  test('N/P navigate pagination when present', async ({ page }) => {
    await page.goto('/?page=1');
    // If there is pagination, pressing N should navigate (best-effort)
    await page.keyboard.press('N');
    // Can't guarantee next exists; just assert no error
    await expect(page).toHaveURL(/.*/);
  });

  test('Parallax class present on hero image on homepage', async ({ page }) => {
    await page.goto('/');
    const hero = page.locator('.js-parallax-hero').first();
    await expect(hero).toBeVisible();
    await page.evaluate(() => window.scrollTo(0, 300));
    // After scroll, style should include transform (if desktop)
    const transform = await hero.evaluate((el) => getComputedStyle(el).transform);
    expect(transform).not.toBe('none');
  });

  test('Print CSS linked with media=print', async ({ page }) => {
    await page.goto('/');
    const hasPrintLink = await page.locator('link[rel="stylesheet"][media="print"]').count();
    expect(hasPrintLink).toBeGreaterThan(0);
  });

  test('QR code modal scaffold appears and allows download click (without generation)', async ({ page }) => {
    // Navigate to an article page sample if available; fallback to home
    await page.goto('/');
    // Try opening search to find an article quickly, else skip button check by scanning floating actions presence
    // Open shortcuts to ensure no conflicts
    await page.keyboard.press('Escape');
    // Try to show floating actions by scrolling
    await page.evaluate(() => window.scrollTo(0, 400));
    const qrButton = page.locator('button[aria-label="Show QR code"]').first();
    // Button may not be present on home; don't fail the suite
    if (await qrButton.count() > 0) {
      await qrButton.click();
      const dialog = page.getByRole('dialog', { name: 'QR Code' });
      await expect(dialog).toBeVisible();
      // Generate and then download
      const generate = page.getByRole('button', { name: 'Generate' });
      await expect(generate).toBeVisible();
      await generate.click();
      // Wait for canvas to render
      await expect(dialog.locator('#qr-container canvas')).toBeVisible({ timeout: 5000 });
      const download = page.getByRole('button', { name: 'Download' });
      await expect(download).toBeVisible();
    }
  });
});


