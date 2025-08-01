import Plugin from "src/plugin-system/plugin.class";
import { getCsrfToken } from "../util.js";

export default class EasyCreditRatenkaufExpressCheckout extends Plugin {
  init() {
    document.addEventListener("easycredit-submit", async (e) => {
      if (!e.target.matches('easycredit-express-button')) {
        return;
      }
      const component = e.target;
      const easyCreditParams = this.buildAdditionalParams(e.detail);

      if (
        document.querySelector(".is-ctl-checkout.is-act-cartpage") ||
        component.closest(".cart-offcanvas")
      ) {
        const params = new URLSearchParams(easyCreditParams).toString();
        const baseUrl = window.router['frontend.easycredit.express'] ?? '/easycredit/express';
        window.location.href = [baseUrl, params].join('?');
        return;
      }

      const buyForm = document.getElementById(
        "productDetailPageBuyProductForm"
      );

      if (buyForm) {
        let additional = {};

        const token = await getCsrfToken();
        if (token) {
          additional["_csrf_token"] = token;
        }

        additional["redirectTo"] = "frontend.easycredit.express";
        additional["redirectParameters"] = JSON.stringify(easyCreditParams);

        var replicatedForm;
        if ((replicatedForm = await this.replicateForm(buyForm, additional))) {
          replicatedForm.submit();
          return;
        }
      }

      window.alert(
        "Die Express-Zahlung mit easyCredit konnte nicht gestartet werden."
      );
      console.error(
        "easyCredit payment could not be started. Please check the integration."
      );
    });
  }

  buildAdditionalParams = (detail) => {
    let additional = {};
    detail.express = "1";
    for (let [key, value] of Object.entries(detail)) {
      additional["easycredit[" + key + "]"] = value;
    }
    return additional;
  };

  replicateForm(buyForm, additionalData) {
    if (!(buyForm instanceof HTMLFormElement)) {
      return false;
    }

    const action = buyForm.getAttribute("action");
    const method = buyForm.getAttribute("method");

    if (!action || !method) {
      return false;
    }

    const form = document.createElement("form");
    form.setAttribute("action", action);
    form.setAttribute("method", method);
    form.style.display = "none";

    const formData = new FormData(buyForm);
    for (const [key, value] of Object.entries(additionalData)) {
      formData.set(key, value);
    }

    for (const key of formData.keys()) {
      const field = document.createElement("input");
      field.setAttribute("type", "hidden");
      field.setAttribute("name", key);
      field.setAttribute("value", formData.get(key));
      form.appendChild(field);
    }

    document.body.appendChild(form);

    return form;
  }
}
