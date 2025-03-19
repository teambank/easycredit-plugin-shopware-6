import {
  PlaywrightTestConfig,
  Project,
  defineConfig,
  devices,
} from "@playwright/test";
import { seconds } from "./utils";

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
  globalSetup: require.resolve("./global.setup"),
};

let projects: Project[] = [
  { name: `backend-auth`, testMatch: /.*\.setup\.ts/ },
];

["Desktop Chrome"].forEach((device) => {
  projects.push({
    name: `checkout @${device}`,
    use: {
      ...devices[device],
    },
    testMatch: "checkout.spec.ts",
  });
  projects.push({
    name: `frontend @${device}`,
    use: {
      ...devices[device],
    },
    testMatch: "frontend.spec.ts",
  });
});

/* test backend only desktop */
["Desktop Chrome"].forEach((device) => {
  let name = projects.find((p) => p.name?.match("checkout"))?.name; // checkout required, so that we have at least one order in the backend
  projects.push({
    name: `backend @${device}`,
    use: {
      ...devices[device],
      storageState: "playwright/.auth/user.json",
    },
    dependencies: [`backend-auth`, name as string],
    testMatch: "backend.spec.ts",
  });
});

config.projects = projects

export default defineConfig(config);
