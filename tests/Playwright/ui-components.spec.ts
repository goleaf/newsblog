import { test, expect } from '@playwright/test';

test.describe('Advanced UI Components', () => {
  test('gallery navigation, thumbnails, counter, and swipe', async ({ page }) => {
    await page.goto('/ui-demo');

    // Wait for gallery main image to be visible
    const mainImg = page.locator('div.aspect-video img');
    await expect(mainImg).toBeVisible();

    const counter = page.locator('text=/^\\d+\\/\\d+$/');
    await expect(counter).toHaveText('1/3');

    // Next button ›
    await page.locator('button:has-text("›")').click();
    await expect(counter).toHaveText('2/3');

    // Prev button ‹
    await page.locator('button:has-text("‹")').click();
    await expect(counter).toHaveText('1/3');

    // Click a thumbnail (use alt text)
    await page.locator('button img[alt="Sample 3"]').click();
    await expect(counter).toHaveText('3/3');

    // Simulate swipe via Alpine component internals (touch events)
    const root = page.locator('[x-data*="galleryComponent"]');
    // Move from 3/3 to 2/3 by swiping right (deltaX > 40)
    await root.evaluate((el: any) => {
      const cmp = (el as any).__x?.$data || (el as any).__x;
      cmp.startX = 100; cmp.deltaX = 0;
      cmp.onTouchStart({ changedTouches: [{ clientX: 100 }] });
      cmp.onTouchMove({ changedTouches: [{ clientX: 160 }] });
      cmp.onTouchEnd();
    });
    await expect(counter).toHaveText('2/3');
  });

  test('pull quotes and social embed fallback render', async ({ page }) => {
    await page.goto('/ui-demo');
    await expect(page.getByText('Steve Jobs')).toBeVisible();

    // Social fallback link visible
    const link = page.locator('a[href*="twitter.com/jack/status/20"]');
    await expect(link).toBeVisible();
    await expect(link).toHaveText(/Open/i);
  });

  test('charts render canvas elements', async ({ page }) => {
    await page.goto('/ui-demo');
    await expect(page.locator('canvas')).toHaveCount(3);
  });
});

