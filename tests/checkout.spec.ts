import { test, expect } from '@playwright/test';
import { scaleDown, takeScreenshot } from "./utils";
import {
  goToProduct,
  goThroughPaymentPage,
  checkAddressInvalidation,
  checkAmountInvalidation,
  confirmOrder,
  fillCheckout
} from "./common";

test.beforeEach(scaleDown);
test.afterEach(takeScreenshot);

test.beforeAll(async ({ request}, testInfo) => {
  var headers = {
    'Content-Type': 'application/json',
    Accept: 'application/json'
  }

  var response = await request.post('/api/oauth/token', {
    headers: headers,
    data: {
      "client_id": "administration",
      "grant_type": "password",
      "scopes": "write",
      "username": "admin",
      "password": "shopware"
    }
  });
  const authorization = await response.json()
  headers['Authorization'] = 'Bearer ' + authorization.access_token;

  response = await request.get('/api/sales-channel', {
    headers: headers
  })
  const salesChannel = await response.json().then((data) => {
    return data.data.find(e => e.name === 'Storefront')
  })

  response = await request.get('/api/tax', {
    headers: headers
  })
  const taxId = await response.json().then((data) => {
    return data.data.find(e => e.taxRate === 19).id
  })

  var response = await request.post('/api/product', {
    headers: headers,
    data: {
      "name": "Product",
      "productNumber": "123456",
      "stock": 99999,
      "taxId": taxId,
      "price": [
        {
          "currencyId": salesChannel.currencyId,
          "gross": 201,
          "net": 200,
          "linked": false
        }
      ],
      "visibilities": [{
        "salesChannelId": salesChannel.id,
        "visibility": 30
      }],
      "categories": [
        {
        "displayNestedProducts": true,
        "type": "page",
        "productAssignmentType": "product",
        "name": "Home",
        "navigationSalesChannels": [{
          "id": salesChannel.id
        }]
        }
      ]
    }
  })

  response = await request.get('/api/product', {
    headers: headers
  })
})

test.describe("go through standard checkout @checkout", () => {
  test("checkout", async ({ page }) => {
    await goToProduct(page);

    await page
      .getByRole("button", { name: "Add to shopping cart" })
      .first()
      .click();
    await page.locator(".offcanvas .begin-checkout");
    await expect(page.locator(".offcanvas").getByRole("link", { name: /Product/ }).first()).toBeVisible();

    await fillCheckout(page);

    await goThroughPaymentPage(page);
    await confirmOrder(page);
  });
});

test.describe("address change should invalidate payment @checkout", () => {
  test("checkoutAddressChange", async ({ page }) => {
    await goToProduct(page);

    await page
      .getByRole("button", { name: "Add to shopping cart" })
      .first()
      .click();
    await page.locator(".offcanvas .begin-checkout");
    await expect(page.locator(".offcanvas").getByRole("link", { name: /Product/ }).first()).toBeVisible();

    await fillCheckout(page);

    await goThroughPaymentPage(page);

    await checkAddressInvalidation(page);
  });
});

test.describe("amount change should invalidate payment @checkout", () => {
  test("checkoutAmountChange", async ({ page }) => {
    await goToProduct(page);

    await page
      .getByRole("button", { name: "Add to shopping cart" })
      .first()
      .click();
    await page.locator(".offcanvas .begin-checkout");
    await expect(page.locator(".offcanvas").getByRole("link", { name: /Product/ }).first()).toBeVisible();

    await fillCheckout(page);

    await goThroughPaymentPage(page);

    await checkAmountInvalidation(page);
  });
});

test.describe("go through express checkout @express", () => {
  test("expressCheckout", async ({ page }) => {
    await goToProduct(page);

    await page
      .locator("a")
      .filter({ hasText: "Jetzt direkt in Raten zahlen" })
      .click();
    await page.getByText("Akzeptieren", { exact: true }).click();

    await goThroughPaymentPage(page, true);
    await confirmOrder(page);
  });
});

test.describe("address change should invalidate payment @express", () => {
  test("expressCheckoutAddressChange", async ({ page }) => {
    await goToProduct(page);

    await test.step("Start express checkout", async () => {
      await page
        .locator("a")
        .filter({ hasText: "Jetzt direkt in Raten zahlen" })
        .click();
      await page.getByText("Akzeptieren", { exact: true }).click();
    });

    await goThroughPaymentPage(page, true);

    await checkAddressInvalidation(page);
  });
});

test.describe("amount change should invalidate payment @express", () => {
  test("expressCheckoutAmountChange", async ({ page }) => {
    await goToProduct(page);

    await test.step("Start express checkout", async () => {
      await page
        .locator("a")
        .filter({ hasText: "Jetzt direkt in Raten zahlen" })
        .click();
      await page.getByText("Akzeptieren", { exact: true }).click();
    });

    await goThroughPaymentPage(page, true);

    await checkAmountInvalidation(page);
  });
});