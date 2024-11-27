# easyCredit-Rechnung & Ratenkauf Plugin for Shopware 6 - Installment & Bill Payment Plugin

[![Quality Tests (e2e, static analysis, code style)](https://github.com/teambank/easycredit-plugin-shopware-6/actions/workflows/test.yml/badge.svg)](https://github.com/teambank/easycredit-plugin-shopware-6/actions/workflows/test.yml)


easyCredit-Ratenkauf is the easiest and fastest installment payment solution of Germany. Join today to get the simplest way of partial payment for your POS and E-Commerce. easyCredit-Ratenkauf gives you the opportunity to offer installments as an additional payment method in your German WooCommerce store.

Traditional financing solutions are often connected with complicated application processes for customers. With easyCredit-Ratenkauf you can offer a simple, fully online and fast financing solution. Customers can use ‚easyCredit-Ratenkauf‘ in just a few steps: choose their purchases, calculate their preferred installments, enter their personal data, and pay. No paperwork, immediate approval, and complete flexibility throughout. Simple. Fair. Paying in installments with ‚easyCredit-Ratenkauf‘.

# Getting started
Are you interested in using easyCredit-Ratenkauf? Contact us now:
* [sales.ratenkauf@easycredit.de](https://store.shopware.com/en/easyc36021249341f/ratenkauf-by-easycredit.html#)
* +49 (0)911 5390 2726

or register at [easycredit-ratenkauf.de](https://www.easycredit-ratenkauf.de/registrierung.htm) and we will contact you.

**Please note that a valid contract is required to use the plug-in.**

# Installation

The plugin can be installed from the Shopware plugin directory. If you want to install it directly from Github the following approach will work:

```
cd custom/plugins
git clone git@github.com:teambank/easycredit-plugin-shopware-6.git EasyCreditRatenkauf

./bin/console plugin:refresh
./bin/console plugin:install EasyCreditRatenkauf
./bin/console plugin:activate EasyCreditRatenkauf
./bin/console system:config:set EasyCreditRatenkauf.config.webshopId 1.de.1234.1
./bin/console system:config:set EasyCreditRatenkauf.config.apiPassword abcdefxyz
```

# Compatibility

This extension aims to be as compatible as possible with current, future versions of Shopware 6. This version is tested with:

* 6.6.x
* 6.5.x
* 6.4.x

Earlier versions of Shopware 6 may work, but are not actively tested anymore.

If you still have any problems, please open a ticket or contact [ratenkauf@easycredit.de](mailto:ratenkauf@easycredit.de).

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

# Security
If you have discovered a security vulnerability, please email [opensource@teambank.de](mailto:opensource@teambank.de).
