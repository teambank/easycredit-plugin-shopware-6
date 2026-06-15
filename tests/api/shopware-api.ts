import { APIRequestContext, request as playwrightRequest } from "@playwright/test";

const JSON_HEADERS = {
  "Content-Type": "application/json",
  Accept: "application/json",
};

type SalesChannel = {
  id: string;
  currencyId: string;
  name: string;
};

export class ShopwareAdminApi {
  private constructor(
    private readonly request: APIRequestContext,
    private readonly headers: Record<string, string>
  ) {}

  static async createContext(baseURL: string): Promise<ShopwareAdminApi> {
    const request = await playwrightRequest.newContext({ baseURL });
    return ShopwareAdminApi.fromRequest(request);
  }

  static async fromRequest(request: APIRequestContext): Promise<ShopwareAdminApi> {
    const response = await request.post("/api/oauth/token", {
      headers: JSON_HEADERS,
      data: {
        client_id: "administration",
        grant_type: "password",
        scopes: "write",
        username: "admin",
        password: "shopware",
      },
    });
    const { access_token } = await response.json();
    return new ShopwareAdminApi(request, {
      ...JSON_HEADERS,
      Authorization: `Bearer ${access_token}`,
    });
  }

  async getStorefrontSalesChannel(): Promise<SalesChannel> {
    const response = await this.request.get("/api/sales-channel", {
      headers: this.headers,
    });
    const salesChannel = (await response.json()).data.find(
      (channel: SalesChannel) => channel.name === "Storefront"
    );
    if (!salesChannel) {
      throw new Error('Sales channel "Storefront" not found');
    }
    return salesChannel;
  }

  async assignAllPaymentMethods(salesChannelId: string): Promise<void> {
    const paymentMethodsResponse = await this.request.get("/api/payment-method", {
      headers: this.headers,
    });
    const paymentMethodIds = (await paymentMethodsResponse.json()).data.map(
      (paymentMethod: { id: string }) => ({ id: paymentMethod.id })
    );

    await this.request.patch(`/api/sales-channel/${salesChannelId}`, {
      headers: this.headers,
      data: {
        id: salesChannelId,
        paymentMethods: paymentMethodIds,
      },
    });
  }

  async ensureHomeCategory(salesChannelId: string): Promise<string | undefined> {
    const response = await this.request.get("/api/category", {
      headers: this.headers,
    });
    const existing = (await response.json()).data.find(
      (category: { name: string; id: string }) => category.name === "Home"
    );
    if (existing) {
      return existing.id;
    }

    const createResponse = await this.request.post("/api/category", {
      headers: this.headers,
      data: {
        name: "Home",
        type: "page",
        productAssignmentType: "product",
        displayNestedProducts: true,
        navigationSalesChannels: [{ id: salesChannelId }],
      },
    });
    const created = await createResponse.json();
    return created.data ? created.data.id : created.id;
  }

  async getTaxIdByRate(rate: number): Promise<string> {
    const response = await this.request.get("/api/tax", {
      headers: this.headers,
    });
    const tax = (await response.json()).data.find(
      (entry: { taxRate: number; id: string }) => entry.taxRate === rate
    );
    if (!tax) {
      throw new Error(`Tax rate ${rate} not found`);
    }
    return tax.id;
  }

  async createProduct(data: Record<string, unknown>): Promise<string> {
    const response = await this.request.post("/api/product", {
      headers: this.headers,
      data,
    });
    return response.text();
  }

  async clearCache(): Promise<string> {
    const response = await this.request.delete("/api/_action/cache", {
      headers: this.headers,
      data: {},
    });
    return response.text();
  }

  async findProductIdByNumber(productNumber: string): Promise<string> {
    const response = await this.request.post("/api/search/product", {
      headers: this.headers,
      data: {
        filter: [{ type: "equals", field: "productNumber", value: productNumber }],
        limit: 1,
      },
    });
    const productId = (await response.json()).data[0]?.id;
    if (!productId) {
      throw new Error(`Product not found: ${productNumber}`);
    }
    return productId;
  }

  async setProductStock(productNumber: string, stock: number): Promise<void> {
    const productId = await this.findProductIdByNumber(productNumber);
    await this.request.patch(`/api/product/${productId}`, {
      headers: this.headers,
      data: { id: productId, stock },
    });
    await this.clearCache();
  }
}

export async function setProductStock(
  request: APIRequestContext,
  productNumber: string,
  stock: number
): Promise<void> {
  const api = await ShopwareAdminApi.fromRequest(request);
  await api.setProductStock(productNumber, stock);
}
