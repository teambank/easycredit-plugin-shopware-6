import { test, expect } from '@playwright/test';
import { scaleDown, takeScreenshot, delay, greaterOrEqualsThan } from "./utils";
import {
  goToProduct,
  addCurrentProductToCart,
  fillCheckout,
  selectAndProceed,
  startExpress,
  goThroughPaymentPage,
  confirmOrder,
  checkAddressInvalidation,
  checkAmountInvalidation,
  openEditShippingAddressModal,
  saveShippingAddressModal
} from "./common";
import { PaymentTypes } from "./types";

test.beforeEach(scaleDown);
test.afterEach(takeScreenshot);

test.describe("go through standard @installment", () => {
  test("standardCheckoutInstallment", async ({ page }) => {
    await goToProduct(page);

    await page
      .getByRole("button", { name: "Add to shopping cart" })
      .first()
      .click();
    await page.locator(".offcanvas .begin-checkout");
    await expect(page.locator(".offcanvas").getByRole("link", { name: /Product/ }).first()).toBeVisible();

    await fillCheckout(page);

    /* Confirm Page */
    await selectAndProceed({ page, paymentType: PaymentTypes.INSTALLMENT });

    await goThroughPaymentPage({
      page: page,
      paymentType: PaymentTypes.INSTALLMENT,
    });
    await confirmOrder({
      page: page,
      paymentType: PaymentTypes.INSTALLMENT,
    });
  });
});

test.describe("go through standard @bill", () => {
  test("standardCheckoutBill", async ({ page }) => {
    await goToProduct(page);

    await page
      .getByRole("button", { name: "Add to shopping cart" })
      .first()
      .click();
    await page.locator(".offcanvas .begin-checkout");
    await expect(
      page
        .locator(".offcanvas")
        .getByRole("link", { name: /Product/ })
        .first()
    ).toBeVisible();

    await fillCheckout(page);

    /* Confirm Page */
    await selectAndProceed({ page, paymentType: PaymentTypes.BILL });

    await goThroughPaymentPage({
      page: page,
      paymentType: PaymentTypes.BILL,
    });
    await confirmOrder({
      page: page,
      paymentType: PaymentTypes.BILL,
    });
  });
});

test.describe("go through @express @installment", () => {
  test("expressCheckout", async ({ page }) => {
    await goToProduct(page);

    await startExpress({ page, paymentType: PaymentTypes.INSTALLMENT });

    await goThroughPaymentPage({
      page: page,
      paymentType: PaymentTypes.INSTALLMENT,
      express: true,
    });
    await confirmOrder({
      page: page,
      paymentType: PaymentTypes.INSTALLMENT,
    });
  });
});

test.describe("go through @express @bill", () => {
  test("expressCheckout", async ({ page }) => {
    await goToProduct(page);

    await startExpress({page, paymentType: PaymentTypes.BILL});

    await goThroughPaymentPage({
      page: page,
      paymentType: PaymentTypes.BILL,
      express: true,
    });
    await confirmOrder({
      page: page,
      paymentType: PaymentTypes.BILL,
    });
  });
});

test.describe("go through standard @installment and switch to @bill", () => {
  test("standardCheckoutInstallmentSwitchToBill", async ({ page }) => {
    await goToProduct(page);

    await page
      .getByRole("button", { name: "Add to shopping cart" })
      .first()
      .click();
    await page.locator(".offcanvas .begin-checkout");
    await expect(
      page
        .locator(".offcanvas")
        .getByRole("link", { name: /Product/ })
        .first()
    ).toBeVisible();

    await fillCheckout(page);

    /* Confirm Page */
    await selectAndProceed({ page, paymentType: PaymentTypes.INSTALLMENT });

    await goThroughPaymentPage({
      page: page,
      paymentType: PaymentTypes.INSTALLMENT,
      switchPaymentType: true
    });
    await confirmOrder({
      page: page,
      paymentType: PaymentTypes.BILL,
    });
  });
});

test.describe("company should not be able to pay @bill @installment", () => {
  test("companyBlocked", async ({ page }) => {
    await goToProduct(page);
    await addCurrentProductToCart(page);

    await page.goto("checkout/confirm");
    await fillCheckout(page);    

    await page.waitForURL("**/checkout/confirm");

    await openEditShippingAddressModal(page);

    await page.getByRole("textbox", { name: "Company" }).fill("Testfirma");

    await delay(1000);

    await saveShippingAddressModal(page);

    /* Confirm Page */
    for (let paymentType of [PaymentTypes.BILL, PaymentTypes.INSTALLMENT]) {
      await selectAndProceed({page, paymentType})
      await expect(
        await page.locator(`easycredit-checkout[payment-type=${paymentType}]`)
      ).toContainText(
        "Die Zahlung mit easyCredit ist nur für Privatpersonen möglich."
      );
    }
  });
});

test.describe("amount change should invalidate payment @installment", () => {
  test("checkoutAmountChange", async ({ page }) => {
    await goToProduct(page);

    await page
      .getByRole("button", { name: "Add to shopping cart" })
      .first()
      .click();
    await page.locator(".offcanvas .begin-checkout");
    await expect(
      page
        .locator(".offcanvas")
        .getByRole("link", { name: /Product/ })
        .first()
    ).toBeVisible();

    await fillCheckout(page);

    /* Confirm Page */
    await selectAndProceed({ page, paymentType: PaymentTypes.INSTALLMENT });

    await goThroughPaymentPage({
      page: page,
      paymentType: PaymentTypes.INSTALLMENT,
    });

    await checkAmountInvalidation(page);
  });
});

test.describe("address change should invalidate payment @installment", () => {
  test("checkoutAddressChange", async ({ page }) => {
    await goToProduct(page);

    await page
      .getByRole("button", { name: "Add to shopping cart" })
      .first()
      .click();
    await page.locator(".offcanvas .begin-checkout");
    await expect(
      page
        .locator(".offcanvas")
        .getByRole("link", { name: /Product/ })
        .first()
    ).toBeVisible();

    await fillCheckout(page);

    await selectAndProceed({ page, paymentType: PaymentTypes.INSTALLMENT });

    await goThroughPaymentPage({
      page: page,
      paymentType: PaymentTypes.INSTALLMENT,
    });

    await checkAddressInvalidation(page);
  });
});

test.describe("address change should invalidate payment @express", () => {
  test("expressCheckoutAddressChange", async ({ page }) => {
    await goToProduct(page);

    await startExpress({ page, paymentType: PaymentTypes.INSTALLMENT });

    await goThroughPaymentPage({
      page: page,
      paymentType: PaymentTypes.INSTALLMENT,
      express: true
    });

    await checkAddressInvalidation(page);
  });
});

test.describe("amount change should invalidate payment @express", () => {
  test("expressCheckoutAmountChange", async ({ page }) => {
    await goToProduct(page);

    await startExpress({ page, paymentType: PaymentTypes.INSTALLMENT });

    await goThroughPaymentPage({
      page: page,
      paymentType: PaymentTypes.INSTALLMENT,
      express: true,
    });

    await checkAmountInvalidation(page);
  });
});

test.describe("product below amount constraint should not be buyable @bill @installment", () => {
  test("productBelowAmountConstraints", async ({ page }) => {
    await test.step(`Go to product (sku: below50)`, async () => {
      await page.goto('/Below-50/below50');
    });
    await addCurrentProductToCart(page);

    await page.goto("checkout/confirm");
    await fillCheckout(page);

    /* Confirm Page */
    for (let paymentType of [PaymentTypes.BILL, PaymentTypes.INSTALLMENT]) {
      await page
        .locator(`easycredit-checkout-label[payment-type=${paymentType}]`)
        .click();
      await expect(
        await page.locator(`easycredit-checkout[payment-type=${paymentType}]`)
      ).toContainText("liegt außerhalb der zulässigen Beträge");
    }
  });
});

test.describe("product above amount constraint should not be buyable @bill @installment", () => {
  test("productAboveAmountConstraints", async ({ page }) => {
    await goToProduct(page, "above10000");
    await addCurrentProductToCart(page);

    await page.goto("checkout/confirm");
    await fillCheckout(page);

    /* Confirm Page */
    for (let paymentType of [PaymentTypes.BILL, PaymentTypes.INSTALLMENT]) {
      await page
        .locator(`easycredit-checkout-label[payment-type=${paymentType}]`)
        .click();
      await expect(
        await page.locator(`easycredit-checkout[payment-type=${paymentType}]`)
      ).toContainText("liegt außerhalb der zulässigen Beträge");
    }
  });
});
