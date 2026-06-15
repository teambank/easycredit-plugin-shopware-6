import {
  PlaywrightTestConfig,
  Project,
  defineConfig,
  devices,
} from "@playwright/test";
import { seconds } from "./helpers/utils";

let config: PlaywrightTestConfig = {
  outputDir: "../test-results/" + process.env.VERSION + "/",
  use: {
    baseURL: process.env.BASE_URL ?? "http://localhost",
    trace: "retain-on-failure",
    locale: "de-DE",
  },
  retries: process.env.CI ? 2 : 0,
  timeout: seconds(60),
  expect: {
    timeout: 10 * 1000,
  },
  reporter: [["list", { printSteps: true }], ["html"]],
  globalSetup: require.resolve("./setup/global.setup"),
};

let projects: Project[] = [
  // Disabled: admin auth setup + backend.spec.ts need Shopware admin UI maintenance.
  // { name: `backend-auth`, testMatch: "specs/backend-auth.spec.ts" },
];

["Desktop Chrome"].forEach((device) => {
  projects.push({
    name: `checkout @${device}`,
    use: {
      ...devices[device],
    },
    testMatch: "specs/checkout.spec.ts",
  });
  projects.push({
    name: `frontend @${device}`,
    use: {
      ...devices[device],
    },
    testMatch: "specs/frontend.spec.ts",
  });
});

/* test backend only desktop — disabled together with backend-auth
["Desktop Chrome"].forEach((device) => {
  let name = projects.find((p) => p.name?.match("checkout"))?.name; // checkout required, so that we have at least one order in the backend
  projects.push({
    name: `backend @${device}`,
    use: {
      ...devices[device],
      storageState: "playwright/.auth/user.json",
    },
    dependencies: [`backend-auth`, name as string],
    testMatch: "specs/backend.spec.ts",
  });
});
*/

if (!process.env.BASE_URL) {
    config = {
        ...config,
        ...{
            webServer: {
                command: '~/.symfony5/bin/symfony server:start --dir=/opt/shopware/public --port=80',
                url: 'http://localhost/',
                reuseExistingServer: !process.env.CI,
                stdout: 'ignore',
                stderr: 'pipe',
                timeout: 10 * 1000,
            },
        },
    };
}

config.projects = projects

export default defineConfig(config);
