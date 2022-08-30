.. role:: latex(raw)
   :format: latex

.. _configuration:

Konfiguration
=============

Nachdem Sie die Installation erfolgreich abgeschlossen haben, konfigurieren Sie das Plugin. Damit das Plugin als Zahlungsmethode angezeigt wird aktivieren Sie easyCredit-Ratenkauf als Zahlungsmethode für den deutschen Store.

Konfigurations Menü öffnen
--------------------------

Zur Konfiguration öffnen Sie in der Administration den Bereich *Plugins*. In der Liste der installierten Plugins sollte nun **easyCredit-Ratenkauf** enthalten sein.
In dieser Zeile klicken Sie einfach auf das Plugin um die Konfiguration zu öffnen.

.. image:: ./_static/config-open.png

Plugin konfigurieren
--------------------

Die Konfigurationsmöglichkeiten sind im Folgenden gezeigt. Als Mindestkonfiguration geben Sie hier Ihre Webshop-Id und Ihr API-Passwort an.

API-Zugangsdaten
~~~~~~~~~~~~~~~~~

Hier geben Sie die API-Zugangsdaten ein. Diese bestehen aus der Webshop-ID (1.de... / 2.de...) und Ihrem Passwort, dass Sie im Partner-Portal festlegen können.

.. image:: ./_static/config-api.png

.. note:: Nach einem erfolgreichen Test der API-Zugangsdaten, vergessen Sie bitte nicht auf **Speichern** zu klicken.

API-Signatur (optional, ab v2.0.0)
**********************************

Ab v2.0.0 des Plugins besteht die Möglichkeit die API-Kommunikation mit einer Signatur zu versehen. Die API-Kommunikation wird dann signiert und kann im Shop gegen die Signatur geprüft werden. Erstellen Sie hierzu im Partner-Portal eine API-Signatur und aktivieren Sie diese ebenfalls im Partner-Portal. Tragen Sie die API-Signatur im Backend Ihres Shops-Systems ein und prüfen Sie die Zugangsdaten. Die Prüfung der Zugangsdaten berücksichtigt die API-Signatur und ist bei aktivierter Signatur nur erfolgreich, wenn der Signatur-Schlüssel übereinstimmt.

Verhalten
~~~~~~~~~~~

Im Bereich *Verhalten* kann das Verhalten des Plugins beinflusst werden. Die einzelnen Optionen sind bei Hover über das Info-Icon näher erläutert oder im Folgenden konkretisiert. 

.. image:: ./_static/config-behavior.png

Zinsen nach Bestellabschluss entfernen
***************************************

Der Ratenkauf erfordert die Anzeige der Zinsen während des Bezahlvorganges sowie die Einbeziehung der Zinsen in die Gesamtsumme der Bestellung in der Kundenkommunikation. Darum fügt das Plugin automatisch die Position *Zinsen für Ratenzahlung* im Bestellvorgang ein. Möglicherweise ist diese Position im weiteren Verarbeitungsprozess aber nicht mehr erwünscht. Mit dieser Option werden die Zinsen aus der Bestellung entfernt und in der Administration nicht mehr angezeigt, was zu einer Vereinfachung des Prozesses u.a. mit Warenwirtschaftssystemen führt.

Debug-Logging 
****************************

Um das Verhalten des Plugins und die API-Kommunikation zu analysieren, kann diese Einstellung aktiviert werden. Das Plugin loggt bei aktivierter Option die Kommunikation in *var/log/netzkollektiv_easycredit_prod-[Y]-[m]-[d].log*

Zahlungs- und Bestellstatus
****************************

Der Zahlungs- und Bestellstatus, den eine mit easyCredit-Ratenkauf getätigte Bestellung initial erhält, kann mit dieser Option eingestellt werden. Es werden hier nur die Status angezeigt, die für eine offene Bestellung entsprechend des Statusüberganges (*State Machine Transitions*) zur Verfügung stehen. Wird hier keine Auswahl getroffen oder "Offen" gewählt erfolgt keine Veränderung des Status.

Click & Collect
~~~~~~~~~~~~~~~~~~

Um *Click & Collect* für eine Versandart zu aktivieren, kann diese als *Click & Collect*-Versandart ausgewählt werden. Wählt der Kunde diese Versandart im Bezahlvorgang aus, wird dies bei der Finanzierungsanfrage entsprechend übertragen. Weitere Informationen finden Sie unter `Click & Collect <https://www.easycredit-ratenkauf.de/click-und-collect/>`_

.. image:: ./_static/config-clickandcollect.png
           :scale: 50%

Marketing: Modellrechner-Widget
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Um easyCredit-Ratenkauf bei Ihren Kunden zu bewerben, blendet die Extension ein Widget auf der Produktdetailseite und unterhalb des Warenkorbs ein. Das Widget kann über die CSS-Selektoren entsprechend des verwendeten Templates positioniert werden oder deaktiviert werden.

.. note:: Das Widget wird nur für Artikel angezeigt, deren Preis innerhalb der Betragsgrenzen von easyCredit-Ratenkauf liegen.

.. image:: ./_static/config-marketing.png

Zahlungsart Einstellungen
-------------------------

Um die Zahlungsart **easyCredit-Ratenkauf** im Frontend anzuzeigen, muss die Zahlungsart aktiviert sein, und dem Land *Deutschland* zugewiesen werden. Navigieren Sie hierzu zu den Zahlungsart Einstellungen: :menuselection:`Shop -> Zahlungsarten -> easyCredit-Ratenkauf`
Dort stellen Sie sicher, dass **easyCredit-Ratenkauf** aktiviert ist.

.. image:: ./_static/config-payment-active.png

.. raw:: latex

    \clearpage

Verkaufskanal Einstellungen
------------------------------

Achten Sie weiterhin darauf, dass die Zahlungsart "easyCredit-Ratenkauf" auch im Verkaufskanal als Zahlungsart zugewiesen ist.

.. image:: ./_static/config-payment-country.png
