import { test as setup, expect } from "@playwright/test";

const authFile = "playwright/.auth/user.json";

setup("authenticate", async ({ page }) => {
  await page.goto("/admin/");
  await page.getByLabel("Benutzername oder E-Mail-Adresse").fill("admin");
  await page.getByLabel("Passwort", { exact: true }).fill("shopware");
  await page.getByRole("button", { name: "Anmelden" }).click();

  await expect(page.getByRole("heading", { name: "Dashboard" })).toBeVisible();

  await page.context().storageState({ path: authFile });
});
