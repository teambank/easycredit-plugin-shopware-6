import { test, expect } from "@playwright/test";
import { delay, randomize } from "./utils";

const greaterOrEqualsThan = (v) => {
  return (
    v.localeCompare(process.env.VERSION.replace(/^v/, ""), undefined, {
      numeric: true,
      sensitivity: "base",
    }) <= 0
  );
}

export async function clickWithRetry(locator, maxRetries = 3) {
  let attempt = 0;
  while (attempt < maxRetries) {
    try {
      await locator.click();
      return;
    } catch (e) {
      console.error(`Click failed on attempt ${attempt + 1}: ${e.message}`);
      attempt++;
      if (attempt === maxRetries) {
          throw new Error(`Max retries reached, click failed: ${e.message}`);
      }
    }
  }
}

export const goThroughPaymentPage = async (page, express: boolean = false) => {
  await test.step(`easyCredit-Ratenkauf Payment`, async () => {
    await page.getByTestId("uc-deny-all-button").click();
    await page.getByRole("button", { name: "Weiter zur Dateneingabe" }).click();

    if (express) {
      await page.locator("#vorname").fill(randomize("Ralf"));
      await page.locator("#nachname").fill("Ratenkauf");
    }

    await page.locator("#geburtsdatum").fill("05.04.1972");

    if (express) {
      await page.locator("#email").fill("ralf.ratenkauf@teambank.de");
    }
    await page.locator("#mobilfunknummer").fill("015112345678");
    await page.locator("#iban").fill("DE12500105170648489890");

    if (express) {
      await page.locator("#strasseHausNr").fill("Beuthener Str. 25");
      await page.locator("#plz").fill("90402");
      await page.locator("#ort").fill("Nürnberg");
    }

    await page.getByText("Allen zustimmen").click();

    await delay(500);
    await clickWithRetry(page.getByRole("button", { name: "Ratenwunsch prüfen" }))

    await delay(500);
    await page.getByRole("button", { name: "Ratenwunsch übernehmen" }).click();
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

    if (greaterOrEqualsThan('6.4.7')) {
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
        .getByRole("button", { text: "Weiter zum Ratenkauf" })
    ).toBeVisible();
  });
};

export const checkAmountInvalidation = async (page) => {
  await test.step("Check if an amount change invalidates payment", async () => {
    await page.waitForURL("**/checkout/confirm");

    if (greaterOrEqualsThan('6.5.0')) {
      await page.locator(".btn-plus").first().click();
    } else {
      await page.locator(".cart-item-quantity-container").getByRole("combobox").selectOption({ index: 2 });
    }

    await expect(
      page
        .locator(".confirm-payment")
        .getByRole("button", { text: "Weiter zum Ratenkauf" })
    ).toBeVisible();
  });
};

export const confirmOrder = async (page) => {
  await test.step(`Confirm order`, async () => {
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

export const goToProduct = async (page, num = 0) => {
  await test.step(`Go to product (num: ${num}}`, async () => {
    await page.goto("/search?search=123456");
  });
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
    await personalForm.getByRole("textbox", { name: "First name*" }).fill(randomize("Ralf"));
    await personalForm.getByRole("textbox", { name: "Last name*" }).fill("Ratenkauf");

    // SW 6.4
    // workaround: checking checkboxes results in "Target closed" on CI
    if (process.env.VERSION.match("v6.4")) {
      await page.click("text=Do not create a customer account");
    }

    await personalForm.getByLabel("Email address*").fill("test@email.com");

    const billingForm = page.locator(".register-billing");
    await billingForm.getByRole("textbox", { name: "Street address*" }).fill("Beuthener Str. 25");
    await billingForm.getByRole("textbox", { name: "Postal code" }).fill("90402");
    await billingForm.getByRole("textbox", { name: "City*" }).fill("Nürnberg");
    await billingForm.getByLabel("Country*").selectOption({ label: "Germany" });

    await page.getByRole("button", { name: "Continue" }).click();

    /* Confirm Page */
    await page.locator("easycredit-checkout-label").click();
    await page.getByRole("button", { name: "Weiter zu easyCredit-Ratenkauf" }).click();
  });
};
