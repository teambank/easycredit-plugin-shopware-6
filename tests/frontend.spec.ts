import { test, expect } from "@playwright/test";
import { takeScreenshot, scaleDown, greaterOrEqualsThan } from "./utils";
import { goToProduct, addCurrentProductToCart, goToCart } from "./common.ts";

test.beforeEach(scaleDown);
test.afterEach(takeScreenshot);

test.describe("Widget should be visible @product", () => {
  test("widgetProduct", async ({ page }) => {
    await goToProduct(page);
    await expect(
      await page
        .locator('[itemprop="offers"]')
        .getByText(/Finanzieren ab.+?Monat/)
    ).toBeVisible();
  });
});

test.describe("Widget should be visible outside amount constraint @product", () => {
  test("widgetProductOutsideAmount", async ({ page }) => {
    await test.step(`Go to product (sku: below50)`, async () => {
      await page.goto('/Below-50/below50');
    });
    await expect(
      await page
        .locator('[itemprop="offers"]')
        .getByText(/Finanzieren ab.+?Bestellwert/)
    ).toBeVisible();
  });
});

test.describe("Widget should not be visible for digital products @product", () => {
  test("widgetProductDigital", async ({ page }) => {
    await test.step(`Go to product (sku: digital)`, async () => {
      await page.goto('/search?search=digital');
    });
    await expect(
      await page
        .locator('[itemprop="offers"]')
        .getByText(/Finanzieren ab.+?Bestellwert/)
    ).not.toBeVisible();
  });
});

test.describe("Widget should be visible @cart", () => {
  test("widgetProduct", async ({ page }) => {
    await goToProduct(page);
    await addCurrentProductToCart(page);

    await expect(
      await page
        .locator('.offcanvas')
        .getByText(/Finanzieren ab.+?Monat/)
    ).toBeVisible();

    await goToCart(page);

    await expect(
      await page
        .locator('.checkout-aside')
          .getByText(/Finanzieren ab.+?Monat/)
      ).toBeVisible();
  });
});
