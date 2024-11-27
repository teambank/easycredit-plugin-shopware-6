import { test, expect } from "@playwright/test";
import { delay, randomize, greaterOrEqualsThan, clickWithRetry } from "./utils";
import { PaymentTypes } from "./types";

export const goToProduct = async (page, sku = "regular") => {
  await test.step(`Go to product (sku: ${sku}}`, async () => {
    await page.goto(`/search?search=${sku}`);
  });
};

export const addCurrentProductToCart = async (page) => {
  await page.getByRole("button", { name: "Add to shopping cart" }).first().click();
  await page.waitForResponse(/checkout\/line-item\/add/);

  await expect(page.locator(".flashbags .alert")).toContainText(
    /added .+? cart/
  );
};

export const fillCheckout = async (page) => {
  await test.step("Fill out standard checkout", async () => {
    await page.goto("/checkout/confirm");

    const personalForm = await page.locator(".register-personal");
    await personalForm.getByLabel(/Salutation/).selectOption({ index: 1 });

    var randomLetters = "";
    for (let i = 0; i < 3; i++) {
      randomLetters += String.fromCharCode(97 + Math.floor(Math.random() * 26));
    }
    await personalForm
      .getByRole("textbox", { name: "First name*" })
      .fill(randomize("Ralf"));
    await personalForm
      .getByRole("textbox", { name: "Last name*" })
      .fill("Ratenkauf");

    // SW 6.4
    // workaround: checking checkboxes results in "Target closed" on CI
    if (process.env.VERSION && process.env.VERSION.match("v6.4")) {
      await page.click("text=Do not create a customer account");
    }

    await personalForm.getByLabel("Email address*").fill("test@email.com");

    const billingForm = page.locator(".register-billing");
    await billingForm
      .getByRole("textbox", { name: "Street address*" })
      .fill("Beuthener Str. 25");
    await billingForm
      .getByRole("textbox", { name: "Postal code" })
      .fill("90402");
    await billingForm.getByRole("textbox", { name: "City*" }).fill("Nürnberg");
    await billingForm.getByLabel("Country*").selectOption({ label: "Germany" });

    await page.getByRole("button", { name: "Continue" }).click();
  });
};

export const selectAndProceed = async ({
  page,
  paymentType,
}: {
  page: any;
  paymentType: PaymentTypes;
}) => {
  await test.step(`Start standard checkout (${paymentType})`, async () => {
    if (paymentType === PaymentTypes.INSTALLMENT) {
      await page
        .locator("easycredit-checkout-label[payment-type=INSTALLMENT]")
        .click();
      await page.getByRole("button", { name: "Weiter zu easyCredit-Ratenkauf" }).click();
      return;
    }
    if (paymentType === PaymentTypes.BILL) {
      await page
        .locator("easycredit-checkout-label[payment-type=BILL]")
        .click();
      await page
        .getByRole("button", { name: "Weiter zu easyCredit-Rechnung" })
        .click();
      return;
    }
  });
}

export const startExpress = async ({
  page,
  paymentType,
}: {
  page: any;
  paymentType: PaymentTypes;
}) => {
  await test.step(`Start express checkout (${paymentType})`, async () => {
    if (paymentType === PaymentTypes.INSTALLMENT) {
      await page.locator("a").filter({ hasText: "Jetzt direkt in Raten zahlen" }).click();
      await page.getByText("Akzeptieren", { exact: true }).click();
    }
    if (paymentType === PaymentTypes.BILL) {
      await page.locator("a").filter({ hasText: "In 30 Tagen" }).click();
      await page.getByText("Akzeptieren", { exact: true }).click();
    }
  });
}

export const goThroughPaymentPage = async ({
  page,
  paymentType,
  express = false,
}: {
  page: any;
  paymentType: PaymentTypes;
  express?: boolean;
}) => {
  await test.step(`easyCredit-Ratenkauf Payment`, async () => {
    await page.getByTestId("uc-deny-all-button").click();

    await expect(
      page.getByText(
        paymentType === PaymentTypes.INSTALLMENT
          ? "Monatliche Wunschrate"
          : "Ihre Bezahloptionen"
      )
    ).toBeVisible();

    await page.getByRole("button", { name: "Weiter zur Dateneingabe" }).click();

    if (express) {
      await page.locator("#firstName").fill(randomize("Ralf"));
      await page.locator("#lastName").fill("Ratenkauf");
    }

    await page.locator("#dateOfBirth").fill("05.04.1972");

    if (express) {
      await page.locator("#email").getByRole('textbox').fill("ralf.ratenkauf@teambank.de");
    }

    await page.locator("tbk-vorwahldropdown .tel-wrapper").click();
    await page.locator('tbk-vorwahldropdown').locator('li').filter({ hasText: '+49' }).getByRole('paragraph').click();
    await page.locator('#mobilfunknummer').getByRole('textbox').fill('1703404848');
    await page.locator('app-ratenkauf-iban-input-dumb').getByRole('textbox').fill("DE12500105170648489890");

    if (express) {
      await page.locator("#streetAndNumber").fill("Beuthener Str. 25");
      await page.locator("#postalCode").fill("90402");
      await page.locator("#city").fill("Nürnberg");
    }

    await page.locator("#agreeAll").click();

    await delay(500);

    await clickWithRetry(
      page.getByRole("button", { name: "Zahlungswunsch prüfen" })
    );

    await delay(500);
    await page.getByRole("button", { name: "Zahlungswunsch übernehmen" }).click();
  });
};

export const confirmOrder = async ({
  page,
  paymentType,
}: {
  page: any;
  paymentType: PaymentTypes;
}) => {
  await test.step(`Confirm order`, async () => {

    if (paymentType === PaymentTypes.INSTALLMENT) {
      await expect(
        page.locator("easycredit-checkout[payment-type=INSTALLMENT]")
      ).toContainText(
        "Ihre Auswahl"
      );
    } else {
      await expect(
        page.locator("easycredit-checkout[payment-type=BILL]")
      ).toContainText(
        "Heutebestellen"
      );      
    }

    if (paymentType === PaymentTypes.INSTALLMENT) {
      await expect
        .soft(page.locator(".confirm-product"))
        .toContainText("Interest for installments payment");
    } else {
      await expect
        .soft(page.locator(".confirm-product"))
        .not.toContainText("Interest for installments payment");
    }

    /* Confirm Page */
    await expect(page.getByText("I have read")).toBeVisible({ timeout: 10000 });
    await page.evaluate(async () => {
      // workaround: checking checkboxes results in "Target closed" on CI
      document.getElementById("tos").checked = true;
    });

    await page.getByRole("button", { name: "Submit order" }).click();

    /* Success Page */
    await expect(page.getByText("Thank you for your order")).toBeVisible();
  });
};

export const checkAddressInvalidation = async (page) => {
  await test.step("Check if an address change invalidates payment", async () => {
    await page.waitForURL("**/checkout/confirm");

    await page.getByText("Change shipping address").click();
    await page
      .locator(".address-editor-modal")
      .getByText("Edit address")
      .first()
      .click();

    await page
      .getByRole("textbox", { name: "Street address*" })
      .fill("Beuthener Str. 24");

    await delay(1000);

    if (greaterOrEqualsThan("6.4.7")) {
      await page
        .locator("#shipping-address-create-edit")
        .getByText("Save address")
        .click();
    } else {
      await page
        .locator("#address-create-edit")
        .getByText("Save address")
        .click();
    }

    await expect(
      page
        .locator(".confirm-payment")
        .getByRole("button", { text: "Weiter zu easyCredit-Ratenkauf" })
    ).toBeVisible();
  });
};

export const checkAmountInvalidation = async (page) => {
  await test.step("Check if an amount change invalidates payment", async () => {
    await page.waitForURL("**/checkout/confirm");

    if (greaterOrEqualsThan("6.5.0")) {
      await page.locator(".btn-plus").first().click();
    } else {
      await page
        .locator(".cart-item-quantity-container")
        .getByRole("combobox")
        .selectOption({ index: 2 });
    }

    await expect(
      page
        .locator(".confirm-payment")
        .getByRole("button", { text: "Weiter zu easyCredit-Ratenkauf" })
    ).toBeVisible();
  });
};