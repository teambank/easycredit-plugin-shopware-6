import { request, type FullConfig } from "@playwright/test";

async function ensureHomeCategory(req: any, headers: Record<string, string>, salesChannelId: string): Promise<string | undefined> {
  // Try to find existing "Home" category
  let response = await req.get("/api/category", {
    headers: headers,
  });
  let homeCategoryId = await response.json().then((data: any) => {
    const found = data.data.find((e: any) => e.name === "Home");
    return found ? found.id : undefined;
  });

  if (homeCategoryId) {
    return homeCategoryId;
  }

  // Create the Home category if it does not exist yet
  const createCategoryResponse = await req.post("/api/category", {
    headers: headers,
    data: {
      name: "Home",
      type: "page",
      productAssignmentType: "product",
      displayNestedProducts: true,
      navigationSalesChannels: [
        {
          id: salesChannelId,
        },
      ],
    },
  });
  const created = await createCategoryResponse.json();
  return created.data ? created.data.id : created.id;
}

async function globalSetup(config: FullConfig) {
  console.log("[prepareData] preparing test data in store");

  var headers = {
    "Content-Type": "application/json",
    Accept: "application/json",
  };

  const req = await request.newContext({
    baseURL: config.projects[0].use.baseURL,
  });

  var response = await req.post("/api/oauth/token", {
    headers: headers,
    data: {
      client_id: "administration",
      grant_type: "password",
      scopes: "write",
      username: "admin",
      password: "shopware",
    },
  });
  
  const authorization = await response.json();
  headers["Authorization"] = "Bearer " + authorization.access_token;

  response = await req.get("/api/sales-channel", {
    headers: headers,
  });
  const salesChannel = await response.json().then((data) => {
    return data.data.find((e) => e.name === "Storefront");
  });

  response = await req.get('/api/payment-method', { headers });
  const allPaymentMethods = (await response.json()).data;
  const allPaymentMethodIds = allPaymentMethods.map((pm: any) => ({ id: pm.id }));

  response = await req.patch(`/api/sales-channel/${salesChannel.id}`, {
      headers,
      data: {
          id: salesChannel.id,
          paymentMethods: allPaymentMethodIds,
      },
  });
  console.log('[prepareData] added payment methods to sales channel', salesChannel, allPaymentMethodIds);

  // Resolve a single existing category (or create it) to avoid duplicates
  const homeCategoryId = await ensureHomeCategory(req, headers, salesChannel.id);
  console.log('[prepareData] using home category', homeCategoryId);

  response = await req.get("/api/tax", {
    headers: headers,
  });
  const taxId = await response.json().then((data) => {
    return data.data.find((e) => e.taxRate === 19).id;
  });
  console.log('[prepareData] using tax', taxId);

  const baseProductData = {
    stock: 99999,
    taxId: taxId,
    visibilities: [
      {
        salesChannelId: salesChannel.id,
        visibility: 30,
      },
    ],
    // Assign all products to the same existing category (if found)
    ...(homeCategoryId ? { categories: [{ id: homeCategoryId }] } : {}),
  };

  const productsData = [
    {
      name: "Regular Product",
      productNumber: "regular",
      price: [{
        currencyId: salesChannel.currencyId,
        gross: 200,
        net: 200,
        linked: false,
      }],
    },
    {
      name: "Below 50",
      productNumber: "below50",
      price: [{
        currencyId: salesChannel.currencyId,
        gross: 20,
        net: 20,
        linked: false,
      }],
    },
    {
      name: "Below 200",
      productNumber: "below200",
      price: [{
        currencyId: salesChannel.currencyId,
        gross: 199,
        net: 199,
        linked: false,
      }],
    },
    {
      name: "Above 5000",
      productNumber: "above5000",
      price: [{
        currencyId: salesChannel.currencyId,
        gross: 6000,
        net: 6000,
        linked: false,
      }],
    },
    {
      name: "Above 10000",
      productNumber: "above10000",
      price: [{
        currencyId: salesChannel.currencyId,
        gross: 11000,
        net: 11000,
        linked: false,
      }],
    },
    {
      name: "Digital",
      productNumber: "digital",
      downloadable: true,
      states: ['is-download'],
      price: [{
        currencyId: salesChannel.currencyId,
        gross: 11000,
        net: 11000,
        linked: false,
      }],
    },
  ];

  // Add many more products to enable scrolling tests
  for (let i = 1; i <= 60; i++) {
    productsData.push({
      name: `Scroll Product ${i}`,
      productNumber: `scroll-${i}`,
      price: [{
        currencyId: salesChannel.currencyId,
        gross: 100 + i,
        net: 100 + i,
        linked: false,
      }],
    });
  }

  for (const productData of productsData) {
    var response = await req.post("/api/product", {
      headers: headers,
        data: {
          ...baseProductData,
          ...productData
        }
    });
    console.log(await response.text());
    console.log(`[prepareData] added product ${productData.productNumber}`);
  }
}

export default globalSetup;
