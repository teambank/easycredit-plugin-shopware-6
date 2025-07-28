# easyCredit-Rechnung & Ratenkauf Plugin for Shopware 6 - Business Services von easyCredit

[![Quality Tests (e2e, static analysis, code style)](https://github.com/teambank/easycredit-plugin-shopware-6/actions/workflows/test.yml/badge.svg)](https://github.com/teambank/easycredit-plugin-shopware-6/actions/workflows/test.yml)

Wir bieten Ihren Kunden flexible, transparente und sichere Zahlungsmöglichkeiten.  
Sie erhalten Zugang zu einer führenden Raten- und Rechnungsoptionen, die Ihren Umsatz ankurbeln und die Kundenzufriedenheit steigern.  
🏆 Ausgezeichnet als **“Leader Payment” von OMR Reviews im Q1/25**.

🚀 Erweitern Sie mit **easyCredit-Ratenkauf** und **easyCredit-Rechnung** Ihr Paymentangebot.  
Vertrauen Sie auf unsere langjährige Erfahrung im Liquiditätsmanagement und die bewiesene hohe Kundenzufriedenheit mit dem easyCredit.

## 🔍 Unsere Lösungen im Detail:

### 🛍️ easyCredit-Ratenkauf:

• Für Warenkörbe von **200 Euro bis 10.000 Euro**  
• Frei wählbare Laufzeiten von **2 bis 60 Monate**

### 🧾 easyCredit-Rechnung:

• Für Warenkörbe von **50 Euro bis 5.000 Euro**  
• **Schnelle Auszahlung** für Sie trotz **30 Tage Zahlpause** für Ihre Kunden

## Ihre Vorteile:

✅ **Einfach** – Ein Plugin für beide Zahlarten  
⚖️ **Fair** – Einfaches, transparentes Preismodell  
🔐 **Sicher** – Wir übernehmen das volle Ausfallrisiko

## Vorteile für den Endkunden:

⚡ **Sofort** – Sofortige Entscheidung im Zahlungsvorgang. Ganz bequem ohne PostIdent-Verfahren  
🧩 **Einfach** – Direkter Abschluss ohne App und Login  
🔁 **Flexibel** – Vorzeitige Rückzahlung des Ratenkaufkunden möglich

# Getting started

Are you interested in using easyCredit? Contact us now:
* [sales.ratenkauf@easycredit.de](mailto:sales.ratenkauf@easycredit.de)
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
