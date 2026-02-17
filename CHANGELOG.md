# 3.1.10

* die Timeouts für API-Anfragen wurden zur Verbesserung der Zuverlässigkeit optimiert
* die Händler-Informationen werden jetzt asynchron geladen, um Ladezeiten zu reduzieren.
* der Warenkorb wird bei Initialisierung des Express-Checkouts von der Produktdetailseite wieder zuverlässig geleert

# 3.1.9

* im Widget wird die Zins-Flexibilisierung nun auch im Warenkorb und im Offcanvas-Warenkorb berücksichtigt
* der Betrag für den Express-Button im Offcanvas-Warenkorb wird nun korrekt übergeben
* der Express-Checkout wurde überarbeitet, um bereits angemeldete Kunden korrekt zu behandeln

# 3.1.8

* das Widget in der Listenansicht wird nun auch auf Seiten angezeigt, die per Ajax nachgeladen werden (Pagination)
* behebt einen Fehler in der Regelauswertung, wenn keine Verfügbarkeitsregel gesetzt ist
* die package.json wird nicht mehr mitgeliefert, weil nicht benötigt (fehlende package-lock.json verhinderte JS-Build)

# 3.1.7

* die Zahlart-Verfügbarkeitsregeln werden nun vollständig bei Installation / Neuinstallation angelegt und  nicht mehr per Migration angepasst
* die Widget-Konfiguration wird nun als JSON-Objekt angegeben (vorher: meta-Tags)  
* das Widget wird nun wieder im Warenkorb angezeigt (`/checkout/cart`) & in älteren Version auch wieder im OffCanvasCart (< 6.5)
* Anpassungen zur Kompatibilität mit Shopware 6.7.2.x

# 3.1.6

* die Initialisierung mit Bundle-Produkten ist nun möglich
* der Hinweis-Text bei Bestellabschluss ohne Transaktion wurde angepasst
* behebt einen Fehler bei der Transaktionsvalidierung (<= SW 6.5)
* der Express-Checkout funktioniert nun auch bei Konfiguration von Shopware in Unterverzeichnis 

# 3.1.5

* das Storefront-Skript wurde neu gebaut (enthält nun die Änderung aus v3.1.3 bzgl. Duplizierung bei Express-Checkout)

# 3.1.4

* behebt einen Fehler im Update-Prozess, bei dem die Zahlart-Einstellungen überschrieben wurden und Rule-Builder Regeln u.U. doppelt angelegt wurden
* der `StorageInitializer` lädt nur noch bei vorhandenem Token und verhindert einen Fehler bei SalesChannelContext mit leerem Token 
* einige externe Links wurden aktualisiert

# 3.1.3

* Zinsflex ist im Plugin nun für alle Händler verfügbar
* beim Start des Express-Checkouts aus dem Offcanvas-Warenkorb auf der Produktseite werden Artikel nicht mehr dupliziert
* die Webshop-Daten werden nicht mehr in der Konfiguration gespeichert, sondern erst bei Bedarf abgerufen
* die Express-Buttons werden auf der Warenkorb-Seite nun entsprechend der Konfiguration dargestellt
* kleinere Darstellungsfehler in der Konfiguration wurden behoben (SW 6.7)

# 3.1.2

* Anpassungen zur Kompatibilität mit Shopware 6.7

# 3.1.1

* wenn Zinsflex aktiviert ist, werden Produkte standardmäßig mit Zinsflex dargestellt
* behebt einen Fehler, der auftrat nachdem Zinsflex aktiviert wurde
* behebt ein Problem, durch das App-Templates nicht gefunden wurden

# 3.1.0

* das Plugin unterstützt nun die Initialisierung über die API (/easycredit/init-payment, /easycredit/return), z.B. für Shopware Frontends.
* der Zahlungsstatus wird jetzt in der Datenbank statt in der Session gespeichert.
* handle_payment ist nun API-kompatibel
* Abwärtskompatibilität bis Shopware 6.4
* die Installation ist nun auch über Composer von Github möglich
* der Widget-Betrag wird nun wieder korrekt ermittelt

# 2.2.6

* doppelte JavaScript-Builds werden während der Theme-Kompilierung (bis einschließlich SW 6.5) automatisch entfernt, anstatt das zusätzliche Build zu löschen.
* die Storefront lässt sich unter SW 6.4 wieder erfolgreich kompilieren.
* der Express-Checkout wird nun mit ungecachtem Warenkorb initialisiert.

# 3.0.2

* das Widget und der Express-Button wird nun über die Zahlungsart-Regel gesteuert (>= SW 6.5)
* digitale Produkte sind standardmäßig über die Regelkonfiguration ausgeschlossen (>= SW 6.5)

# 3.0.1

* bei Rechnungskauf-Vorgängen wird keine Zinsposition mehr angezeigt
* Integration von Änderungen aus v2.2.5

# 2.2.5

* behebt ein Problem bei der Zahlungsintialisierung i.V.m. Rabatten
* der Zahlungsvorgang ist nun immer an einen bestimmten `cartToken` gebunden (behebt ein Problem mit Drittanbieter-Erweiterungen)
* valide Zugangsdaten werden beim Testen automatisch gespeichert, um die Kennung synchronisieren zu können

# 3.0.0

* Integration von easyCredit-Rechnung

# 2.2.4

* Integration von Ausnahmemöglichkeiten von Zins-Flex via RuleBuilder

# 2.2.3

* das JavaScript-Build wird nun bei Aufruf von `./bin/build-storefront.sh` nicht mehr doppelt erstellt
* behebt ein Problem mit dem Express-Checkout, durch das die Adresse nicht verifiziert werden konnte

# 2.2.2

* die asynchrone Authorisierung wurde entfernt, da die Transaktionen synchron autorisiert und geprüft werden
* das Widget und der Express-Button können nun auch im Off-Canvas Warenkorb angezeigt werden
* das Widget kann nun auch in der Produktübersicht angezeigt werden
* die Steuerung des Widgets über CSS-Selektoren wurde erweitert

# 2.2.1

* die Einstellung für das Debug-Logging wurde wieder integriert
* es wird wieder das Webpack-Build für die Storefront-Funktionalität in allen Versionen verwendet
* im CSRF-Mode Ajax wird ein CSRF-Token vor dem Request abgerufen (SW 6.4)

# 2.2.0

* Kompatibilität mit Shopware 6.6
* Known Issue: die "Debug Logging"-Einstellung wurde vorrübergehend entfernt (das Log-Level kann via APP_ENV beeinflusst werden)

# 2.1.11

* die Zinsen werden nun auch aus der `orderTransaction` entfernt
* das Express-Flag wird vor Weiterleitung aus dem Checkout zurücksetzt 

# 2.1.10

* die Preise für Widget und Express-Button werden nun analog zum product:price:amount Meta-Tag ermittelt
* zur Initialisierung der JavaScript-basierten Funktionalitäten wird nun das load-Event statt DOMContentLoaded verwendet

# 2.1.9

* verbessert die Fehlerbehandlung bei Initialisierung des Express-Checkouts
* verhindert, dass eine Bestellung ohne gültige Transaktion aufgegeben werden kann
* die Snippets haben nun eindeutige Prefixes zur Vermeidung von Kollisionen
* für Widget und Express-Button wird nun durchgängig der calculatedCheapestPrice verwendet, um erweiterte Preise korrekt zu behandeln

# 2.1.8

* die Warenkorb-Validierung prüft nun explizit den SalesChannel zur Vermeidung von Seiteneffekten

# 2.1.7

* für die Konfiguration des Transaktionshandlings ("Lieferung melden" & "Rückabwicklung") kann nun der Flow Builder verwendet werden
* behebt einen Fehler, der bei Verwendung der REST API auftrat ("There is currently no session available")

# 2.1.6

* das Ratenrechner-Widget wird auf Seiten mit individuellem CMS-Layout nicht mehr doppelt angezeigt
* es wurde ein Schreibfehler behoben, der eine Ausgabe im Backend-Header verursachte

# 2.1.5

* es erfolgt nun eine explizite Versionsprüfung für das Kompatiblitätsmodul `ContextResolverListenerModifier`, da verwaiste Dateien aus vorherigen Updates zu einem Fehler führten

# 2.1.4

* bei Ratenkauf-Initialisierung wird die Produkt-URL und die URL des Hauptbildes übertragen
* der RouteScope wird nun für ältere Shopware-Versionen trotz entfernter Annotation gesetzt (`ContextResolverListenerModifier`)
* die Installation funktioniert nun auch unter Shopware 6.4 in allen Versionen (`EntityRepositoryInterface`)
* Webpack Build funktioniert nun auch unter Shopware 6.4 (`window.__sw__.assetPath` hinzugefügt)
* der Express Checkout kann nun auch unter SW < 6.4.6.0 initialisiert werden
* des Ratenrechner-Widget verursacht auf leeren Produktübersichtsseiten und im leeren Warenkorb keinen Fehler mehr

# 2.1.3

* Widget & Express Checkout werden nun auch in der Buybox angezeigt (Content Management)
* bei Veränderung des Warenkorbs wird die Ratenzahlung im Hintergrund automatisch geprüft & angepasst (PATCH)
* Peformance-Verbesserungen durch internes Caching
* behebt einen Fehler, der bei erneutem Initialisieren des Express-Checkouts auftrat

# 2.1.2

* Kompatibilität mit Shopware 6.5.1.0
* das Widget berücksichtigt nun wieder die Einstellung im Backend

# 2.1.1

* Kompatibilität mit Shopware 6.5
* behebt einen Fehler bei Durchführung von Produkt-Exports über die CLI

# 2.1.0

* Express-Checkout: der Ratenkauf kann direkt von der Produktdetailseite oder aus dem Warenkorb heraus gestartet werden

# 2.0.10

* behebt ein Problem mit Shopware 6.4.18.1

# 2.0.9

* behebt ein Problem unter PHP 8.1

# 2.0.8

* umfangreiche Marketing-Komponenten wurden eingefügt und sind über das Backend einstellbar
* behebt einen Fehler im Cart-Validator

# 2.0.7

* behebt einen Fehler in der 2-Phasen-Bestätigung

# 2.0.6

* verhindert, dass die Payment Session durch den Aufruf einer nicht existenten Ressource geleert wurde

# 2.0.5

* eine Bestellung kann nur abgeschlossen werden, wenn der Transaktionstatus PREAUTHORIZED ist, andernfalls erhält der Kunde eine Fehlermeldung
* eine Bestellung wird nur als bezahlt markiert, wenn der Transaktionsstatus bei Aufruf des AuthorizationCallback AUTHORIZED ist
* beim automatischen Melden der Lieferung durch Bestellstatusänderung wird der Status nur übertragen, wenn dies nicht bereits geschehen ist
* die package-lock.json im Administrations-Modul wird nun mitgeliefert

# 2.0.4

* bei mehreren Sales Channels werden nun die korrekten Zugangsdaten je Sales Channel verwendet

# 2.0.3

* Änderungen zum Markenrelaunch von easyCredit-Ratenkauf

# 2.0.2

* es sind nun auch Finanzierungen ohne Zinsen möglich
* die Bestellnummer wird bei Bestätigung der Bestellung nun korrekt übergeben
* eine Inkompatibilität mit Doctrine wurde behoben, die dazu führte, dass die Zinsen nicht entfernt wurden
* die doppelte Betrags- und Adressprüfung im PayHandler wurde zur besseren Kompatibilität zwischen den Versionen entfernt (Konflikt mit "Zinsen entfernen") 
* die API-Library wurde auf v1.3.0 aktualisiert

# 2.0.1

* Rule Builder: es wurden die Bedingungen Summe, inkl. Zinsen (kompatibel mit ratenkauf by easyCredit) und Gesamtsumme, inkl. Zinsen (kompatibel mit ratenkauf by easyCredit) hinzugefügt
* bei Plugin-Installation wird eine Standard-Verfügbarkeitsregel für ratenkauf by easyCredit angelegt (DE & EUR)
* es wurde eine DeliveryInfo zur Zins-Position hinzugefügt, die die Zinsen als versandkostenfrei markiert
* das Plugin-Icon wurde durch eine schärfere Version ersetzt und der Menüpunkt ratenkauf by easyCredit wieder unter Einstellungen -> Erweiterungen aufgenommen
* bei interner Neuberechnung des Warenkorb wird die Warenkorb-Validierung nicht mehr angewendet (verhindert Abbruch des Bezahlvorgangs durch Flow Builder)

# 2.0.0

* Migration auf ratenkauf by easyCredit API v3
* Integration von EasyCredit Ratenkauf Web-Komponenten

# 1.1.10

* Änderungen zur Kompatibilität mit v6.4.9.0

# 1.1.9

* der Zahlungs- und Bestellstatus für neue Bestellungen kann nun konfiguriert werden
* die Standard-Einstellungen werden bei Installation wieder korrekt gesetzt

# 1.1.8

* in den Backend-Modulen wird nun das globale Shopware-Objekt verwendet
* obsolete Komponenten wurden entfernt

# 1.1.7

* verwende die Kunden-Anrede als bevorzugten Wert (temporärer Fix für NEXT-17764) 

# 1.1.6

* die automatischen Aktionen "Lieferung melden" & "Rückabwicklung" sind nun über eine Konfigurationsoption steuerbar 

# 1.1.5

* bei Gast-Bestellungen wird zur Initialisierung der Zahlung nun der Vor- und Nachname der Rechnungsadresse verwendet (vorher: Kundendaten)
* die Hinweismeldung im Checkout wurde angepasst und wird nun als WARNING ausgegeben (vorher: ERROR)
* das Händler-Interface wurde aktualisiert und ist nun als WebComponent eingebunden
* die API-Library wurde aktualisiert auf v1.6.0 (Prüfung von Vor- und Nachname)
* die Beträge werden nach Entfernen der Zinsen auf zwei Nachkommastellen gerundet

# 1.1.4

* die Zahlungsartenauswahl ist nun über das _Checkout Widget_ als WebComponent integriert
* die API-Library wurde aktualisiert auf v1.5.0
* Kompatibilität mit Shopware 6.4

# 1.1.3

* eine Versandart kann für „Click & Collect“ definiert werden
* die API-Library wurde aktualisiert auf v.1.4.0

# 1.1.2

* Verbesserung der Multichannel-Kompatibilität (behebt einen Fehler im Checkout bei mehreren SalesChannel mit unterschiedlichen Einstellungen)
* Verbesserung der Fehler-Toleranz bei unerwarteten Rückgabewerten der API
* das Ratenkauf Widget loggt Betragsunter- bzw. Betragsüberschreitungen nicht mehr als Fehler

# 1.1.1

* Version 1.1.0 konnte nicht installiert werden (Composer Version Constraint)

# 1.1.0

* Verbesserung der Kompatibilität mit dem Shopware Rule Builder

# 1.0.0

* Bestellungen werden nach Abschluss statt als "Bezahlt" als "Authorisiert" markiert (erst nach Meldung der Lieferung im Händler-Portal ist die Bestellung bezahlt)
* der Lieferstatus wird nun an das Händler-Portal übermittelt (order_delivery.state.shipped, order_delivery.state.returned)
* die Konfiguration wurde zur Standardisierung auf config.xml migriert 
* API: das Feld Kategorie wird nach 255 Zeichen abgeschnitten, um einen Fehler bei zu langen Kategorienamen zu vermeiden

# 0.9.8

* Integration des Händler-Interface Widgets
* Verbesserung der Validierung (Firma, abweichende Adresse, Betragsgrenzen)
* die Zinsen enthalten nun 0% Steuern (vorher keine Steuerdefinition)
* die Zinsen können nun automatisch entfernt werden (Standardeinstellung: entfernen)
* das Debug-Logging bei Weiterleitung zum Payment Terminal wurde verbessert
* Weiterleitung auf das Payment Terminal erfolgt nur nach erfolgreicher Validierung
* die Adresse kann in der Administration für ratenkauf by easyCredit Bestellungen nicht mehr angepasst werden
* das Widget stellt den Preis über einen meta-Tag zur Verfügung (vorher: Erkennung über itemprop="price")
* Debug-Logging kann über die Plugin-Einstellungen aktiviert werden
* Anpassung von Übersetzungen

# 0.9.7

* Fehlerbehebung in Zahlartenauswahl in Zusammenspiel mit anderen Plugins

# 0.9.6

* Kompatibilität mit Shopware 6.3

# 0.9.5

* die Bestellnummer wird zur einfacheren Bestellbearbeitung an easyCredit übermittelt
* das Plugin verwendet nun v2 der easyCredit API
* behebt einen Fehler in der Zahlartenauswahl

# 0.9.4

* Verbesserung des Error Handlings bei fehlenden oder inkorrekten Zugangsdaten & Server-Fehlern
* Entfernen der Zahlungsmethode easyCredit bei Fehlern aus dem Checkout
* Entfernen von Zahlungsmethode und Widget, wenn im Sales Channel nicht zugeordnet

# 0.9.3

* Anpassungen gemäß Shopware Quality Guide

# 0.9.1

* erstes Release für Shopware 6.1
