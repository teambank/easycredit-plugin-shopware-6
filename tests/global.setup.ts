import { request, type FullConfig } from "@playwright/test";

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

  response = await req.get("/api/tax", {
    headers: headers,
  });
  const taxId = await response.json().then((data) => {
    return data.data.find((e) => e.taxRate === 19).id;
  });

  const baseProductData = {
    stock: 99999,
    taxId: taxId,
    visibilities: [
      {
        salesChannelId: salesChannel.id,
        visibility: 30,
      },
    ],
    categories: [
      {
        displayNestedProducts: true,
        type: "page",
        productAssignmentType: "product",
        name: "Home",
        navigationSalesChannels: [
          {
            id: salesChannel.id,
          },
        ],
      },
    ],
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
  ];

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
