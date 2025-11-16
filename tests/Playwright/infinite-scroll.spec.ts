import { test, expect, Page, Route, Request } from '@playwright/test';

async function ensureContainerOrSkip(page: Page) {
  const container = page.locator('.infinite-scroll-container');
  if ((await container.count()) === 0) {
    test.skip(true, 'Infinite scroll container not present on this page');
  }
  return container;
}

async function setInfiniteScrollState(page: Page, state: Partial<{ currentPage: number; lastPage: number; finished: boolean; loading: boolean }>) {
  await page.evaluate((updates) => {
    const el = document.querySelector('.infinite-scroll-container') as any;
    if (!el || !('__x' in el)) return;
    const data = (el as any).__x.$data;
    Object.assign(data, updates);
  }, state);
}

test.describe('Infinite Scroll', () => {
  test('loads next page on scroll, updates URL, and appends items', async ({ page }) => {
    // Intercept page=2 to return fake HTML payload
    await page.route(/\?[^#]*page=2(&|$)|\/?\?page=2$/, async (route: Route, request: Request) => {
      // Simulate network delay to observe loading spinner
      await new Promise((r) => setTimeout(r, 400));
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          html: '<div data-post-item class="p-4 border rounded">Injected Item Page 2</div>',
          currentPage: 2,
          lastPage: 3,
          hasMorePages: true,
        }),
      });
    });

    await page.goto('/');

    const container = await ensureContainerOrSkip(page);
    const postsContainer = container.locator('[x-ref="postsContainer"]');
    const sentinel = container.locator('[x-ref="sentinel"]');

    // Ensure component will attempt to load another page
    await setInfiniteScrollState(page, { currentPage: 1, lastPage: 3, finished: false, loading: false });

    const initialCount = await postsContainer.locator('[data-post-item]').count();

    // Scroll to trigger IntersectionObserver
    await sentinel.scrollIntoViewIfNeeded();

    // Spinner should appear while loading
    await expect(container.locator('.animate-spin')).toBeVisible();

    // Wait for network request and DOM update
    await page.waitForRequest((req) => req.url().includes('page=2'));
    await expect(postsContainer.locator('text=Injected Item Page 2')).toBeVisible();

    const afterCount = await postsContainer.locator('[data-post-item]').count();
    expect(afterCount).toBeGreaterThan(initialCount);

    // URL updated via pushState to page=2
    await expect(async () => {
      const url = page.url();
      expect(url).toContain('page=2');
    }).toPass();
  });

  test('shows end of content after final page', async ({ page }) => {
    // Intercept page=2 and page=3; final returns hasMorePages=false
    await page.route(/\?[^#]*page=2(&|$)|\/?\?page=2$/, async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          html: '<div data-post-item class="p-4 border rounded">Injected Item Page 2</div>',
          currentPage: 2,
          lastPage: 3,
          hasMorePages: true,
        }),
      });
    });
    await page.route(/\?[^#]*page=3(&|$)|\/?\?page=3$/, async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          html: '<div data-post-item class="p-4 border rounded">Injected Item Page 3</div>',
          currentPage: 3,
          lastPage: 3,
          hasMorePages: false,
        }),
      });
    });

    await page.goto('/');

    const container = await ensureContainerOrSkip(page);
    const sentinel = container.locator('[x-ref="sentinel"]');

    // Force state to allow two more loads
    await setInfiniteScrollState(page, { currentPage: 1, lastPage: 3, finished: false, loading: false });

    // Trigger page=2
    await sentinel.scrollIntoViewIfNeeded();
    await page.waitForRequest((req) => req.url().includes('page=2'));

    // Trigger page=3 (final)
    await sentinel.scrollIntoViewIfNeeded();
    await page.waitForRequest((req) => req.url().includes('page=3'));

    // URL should show page=3 now
    await expect(async () => {
      const url = page.url();
      expect(url).toContain('page=3');
    }).toPass();

    // End-of-content message should be visible
    await expect(page.getByText("You've reached the end of the content")).toBeVisible();
  });
});

