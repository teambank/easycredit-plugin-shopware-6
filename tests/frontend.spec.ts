import { test, expect } from '@playwright/test';
import { takeScreenshot, scaleDown } from './utils';
import { goToProduct, addCurrentProductToCart, goToCart } from './common.ts';

test.beforeEach(scaleDown);
test.afterEach(takeScreenshot);

test.describe('Widget should be visible @product', () => {
    test('widgetProduct', async ({ page }) => {
        await goToProduct(page);
        await expect(await page.locator('[itemprop="offers"]').getByText(/Finanzieren ab.+?Monat/)).toBeVisible();
    });
});

test.describe('Widget should be visible outside amount constraint @product', () => {
    test('widgetProductOutsideAmount', async ({ page }) => {
        await test.step(`Go to product (sku: below50)`, async () => {
            await page.goto('/Below-50/below50');
        });
        await expect(await page.locator('[itemprop="offers"]').getByText(/Finanzieren ab.+?Bestellwert/)).toBeVisible();
    });
});

test.describe('Widget should not be visible for digital products @product', () => {
    test('widgetProductDigital', async ({ page }) => {
        await test.step(`Go to product (sku: digital)`, async () => {
            await page.goto('/search?search=digital');
        });
        await expect(
            await page.locator('[itemprop="offers"]').getByText(/Finanzieren ab.+?Bestellwert/)
        ).not.toBeVisible();
    });
});

test.describe('Widget should be visible @cart', () => {
    test('widgetProduct', async ({ page }) => {
        await goToProduct(page);
        await addCurrentProductToCart(page);

        await expect(await page.locator('.offcanvas').getByText(/Finanzieren ab.+?Monat/)).toBeVisible();

        await goToCart(page);

        await expect(await page.locator('.checkout-aside').getByText(/Finanzieren ab.+?Monat/)).toBeVisible();
    });
});

test.describe('Widget should be visible on homepage product listing @listing', () => {
    test('widgetHomepageListing', async ({ page }) => {
        await test.step('Go to homepage', async () => {
            await page.goto('/');
        });

        const listing = page.locator('.cms-element-product-listing');
        await expect(listing).toBeVisible();
        await expect(listing.getByText(/Finanzieren ab/).first()).toBeVisible();
    });
});

test.describe('Widget should be visible on homepage listing next page @listing', () => {
    test('widgetHomepageListingNextPage', async ({ page }) => {
        await test.step('Go to homepage', async () => {
            await page.goto('/');
        });

        const listing = page.locator('.cms-element-product-listing');
        await expect(listing).toBeVisible();

        await test.step('Click next page to trigger AJAX reload', async () => {
            const pagination = page.locator('[data-listing-pagination]');
            await expect(pagination).toBeVisible();

            // Click page 2 in pagination (Shopware updates URL via pushState on AJAX)
            await pagination.locator('a.page-link[data-page="2"]').first().click();
            await page.waitForURL('**/?p=2*');
        });

        // After AJAX reload, the widget should be present in the listing again
        await expect(listing.getByText(/Finanzieren ab/).first()).toBeVisible();
    });
});
