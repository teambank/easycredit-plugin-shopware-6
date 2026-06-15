import { type FullConfig } from "@playwright/test";
import { ShopwareAdminApi } from "../api/shopware-api";
import { getTestProducts } from "../api/test-products";

async function globalSetup(config: FullConfig) {
  console.log("[prepareData] preparing test data in store");

  const baseURL = config.projects[0].use.baseURL ?? "http://localhost";
  const api = await ShopwareAdminApi.createContext(baseURL);

  const salesChannel = await api.getStorefrontSalesChannel();
  await api.assignAllPaymentMethods(salesChannel.id);
  console.log("[prepareData] added payment methods to sales channel", salesChannel);

  const homeCategoryId = await api.ensureHomeCategory(salesChannel.id);
  console.log("[prepareData] using home category", homeCategoryId);

  const taxId = await api.getTaxIdByRate(19);
  console.log("[prepareData] using tax", taxId);

  const baseProductData = {
    stock: 99999,
    taxId,
    visibilities: [
      {
        salesChannelId: salesChannel.id,
        visibility: 30,
      },
    ],
    ...(homeCategoryId ? { categories: [{ id: homeCategoryId }] } : {}),
  };

  for (const productData of getTestProducts(salesChannel.currencyId)) {
    const result = await api.createProduct({
      ...baseProductData,
      ...productData,
    });
    console.log(result);
    console.log(`[prepareData] added product ${productData.productNumber}`);
  }

  console.log(await api.clearCache());
}

export default globalSetup;
