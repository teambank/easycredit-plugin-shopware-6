export function delay(time): Promise<void> {
  return new Promise(function (resolve) {
    setTimeout(resolve, time);
  });
}

export const randomize = (name, num = 3): string => {
  for (let i = 0; i < num; i++) {
    name += String.fromCharCode(97 + Math.floor(Math.random() * 26));
  }
  return name;
};

export const takeScreenshot = async ({ page }, testInfo): Promise<void> => {
  if (testInfo.status !== testInfo.expectedStatus) {
    // Get a unique place for the screenshot.
    const screenshotPath = testInfo.outputPath(`failure.png`);
    // Add it to the report.
    testInfo.attachments.push({
      name: "screenshot",
      path: screenshotPath,
      contentType: "image/png",
    });
    // Take the screenshot itself.
    await page.screenshot({ path: screenshotPath, timeout: 5000 });
  }
};

export const scaleDown = async ({ page }, testInfo): Promise<void> => {
  await page.evaluate(() => {
    document.body.style.transform = "scale(0.75)";
  });
};

export const minutes = (min: number) => {
  return min * 60 * 1000;
};

export const seconds = (sec: number) => {
  return sec * 1000;
};

export const greaterOrEqualsThan = (v) => {
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
