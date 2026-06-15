type ProductPrice = {
  currencyId: string;
  gross: number;
  net: number;
  linked: boolean;
};

export type TestProduct = {
  name: string;
  productNumber: string;
  price: ProductPrice[];
  stock?: number;
  isCloseout?: boolean;
  downloadable?: boolean;
  states?: string[];
};

function price(currencyId: string, amount: number): ProductPrice[] {
  return [{ currencyId, gross: amount, net: amount, linked: false }];
}

export function getTestProducts(currencyId: string): TestProduct[] {
  const products: TestProduct[] = [
    {
      name: "Regular Product",
      productNumber: "regular",
      price: price(currencyId, 200),
    },
    {
      name: "Below 50",
      productNumber: "below50",
      price: price(currencyId, 20),
    },
    {
      name: "Below 200",
      productNumber: "below200",
      price: price(currencyId, 199),
    },
    {
      name: "Above 5000",
      productNumber: "above5000",
      price: price(currencyId, 6000),
    },
    {
      name: "Above 10000",
      productNumber: "above10000",
      price: price(currencyId, 11000),
    },
    {
      name: "Last Stock Product",
      productNumber: "laststock",
      stock: 1,
      isCloseout: true,
      price: price(currencyId, 200),
    },
    {
      name: "Digital",
      productNumber: "digital",
      downloadable: true,
      states: ["is-download"],
      price: price(currencyId, 11000),
    },
  ];

  for (let i = 1; i <= 60; i++) {
    products.push({
      name: `Scroll Product ${i}`,
      productNumber: `scroll-${i}`,
      price: price(currencyId, 100 + i),
    });
  }

  return products;
}
