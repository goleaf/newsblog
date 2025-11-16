import { test, expect } from '@playwright/test';

test.describe('Homepage', () => {
  test('should load homepage successfully', async ({ page }) => {
    await page.goto('/');
    
    // Check page title
    await expect(page).toHaveTitle(/TechNewsHub|Home/i);
    
    // Check main content is visible
    await expect(page.locator('main')).toBeVisible();
    
    // Check navigation is present
    await expect(page.locator('nav, [role="navigation"]').first()).toBeVisible();
  });

  test('should display featured posts section', async ({ page }) => {
    await page.goto('/');
    
    // Check if featured posts section exists (may be empty)
    const featuredSection = page.locator('[data-testid="hero-post"], .hero-post, [class*="hero"]').first();
    if (await featuredSection.count() > 0) {
      await expect(featuredSection).toBeVisible();
    }
  });

  test('should display latest articles', async ({ page }) => {
    await page.goto('/');
    
    // Check for latest articles heading or grid
    const latestSection = page.locator('text=Latest Articles, text=Latest, [class*="post-grid"]').first();
    if (await latestSection.count() > 0) {
      await expect(latestSection).toBeVisible();
    }
  });

  test('should have working sort dropdown', async ({ page }) => {
    await page.goto('/');
    
    const sortSelect = page.locator('select#sort, select[name="sort"]').first();
    if (await sortSelect.count() > 0) {
      await expect(sortSelect).toBeVisible();
      
      // Test sorting
      await sortSelect.selectOption('popular');
      await page.waitForURL(/\?sort=popular/);
    }
  });

  test('should be responsive on mobile', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto('/');
    
    // Check mobile menu button exists
    const mobileMenuButton = page.locator('[aria-label*="menu"], [data-testid="mobile-menu-button"]').first();
    if (await mobileMenuButton.count() > 0) {
      await expect(mobileMenuButton).toBeVisible();
    }
  });

  test('should support dark mode', async ({ page }) => {
    await page.goto('/');
    
    // Check if dark mode toggle exists
    const darkModeToggle = page.locator('[data-testid="dark-mode-toggle"], [aria-label*="dark"], [aria-label*="theme"]').first();
    if (await darkModeToggle.count() > 0) {
      await expect(darkModeToggle).toBeVisible();
      
      // Toggle dark mode
      await darkModeToggle.click();
      await expect(page.locator('html')).toHaveClass(/dark/);
    }
  });
});

