# Vorlagen: Datenschutzangaben für die Amazon-Registrierung als privater Entwickler

Diese Vorlagen helfen Ihnen beim Ausfüllen des Entwicklerprofils im Solution
Provider Portal, wenn Sie die geschützten Rollen „Direct-to-Consumer Shipping"
und „Tax Invoicing" beantragen. Amazon stellt die Fragen auf Englisch, daher
sind auch die Antworten auf Englisch.

**Bitte vor dem Absenden anpassen:**

- Ersetzen Sie alles in `[eckigen Klammern]` durch Ihre eigenen Angaben.
- Prüfen Sie jede Aussage: Amazon kann die Angaben überprüfen, und sie müssen
  auf Ihr Unternehmen zutreffen. Lieber eine bescheidene, wahre Antwort als
  eine übertriebene.
- Jedes Antwortfeld fasst etwa 500 Zeichen. Die Vorlagen bleiben darunter.
- Die Angaben zu Hosting, Backups und Firewall erfragen Sie am besten kurz bei
  Ihrem Hosting-Anbieter.
- Antwort 4 setzt voraus, dass im Modul unter „Löschung der Käuferdaten" die
  automatische Löschung eingeschaltet bleibt (Standard: 30 Tage nach Lieferung).

Die Vorlagen gehen von diesem Aufbau aus: Das Modul Amazon Marketplace Pro
läuft in Ihrem eigenen PrestaShop-Shop. Amazon-Daten werden nur zwischen Ihrem
Server und Amazon übertragen und nur in Ihrer Shop-Datenbank gespeichert.

---

## 1. Data usage 4.1 - why restricted roles are required

> Describe why your organization requires Restricted roles containing
> Personally Identifiable Information to build your application or feature.

```
We are a seller and use the app only for our own Amazon account. Direct-to-Consumer Shipping: our PrestaShop shop imports Amazon orders so we can ship them; buyer name and address are needed to create the order and the shipping label. Tax Invoicing: the buyer's VAT number is needed for a correct VAT invoice (EU B2B), which we upload to Amazon. The data is used only for fulfilment and invoicing, never for marketing.
```

## 2. Network Protection 1.1

> Describe the network protection controls used by your organization to
> restrict public access to databases, file servers, and desktop/developer
> endpoints.

```
The shop runs at [hosting provider]. The database accepts connections only from the shop server, not from the internet. Server access is limited to [SFTP/SSH with key] and the hosting control panel with two-factor authentication. The host runs a firewall and [web application firewall/malware scanning]. Office computers use a firewall, antivirus and automatic updates; only [number] staff have access.
```

## 3. Encryption at Rest 2.4

> Describe how your organization stores Amazon information at Rest including:
> (a) encryption methods, and (b) key management systems.

```
(a) Amazon information is stored only in our shop's MySQL database at [hosting provider], on [encrypted disks/storage, AES-256]. Backups are encrypted with [AES-256]. Data in transit uses TLS. (b) Encryption keys are managed by [the hosting provider / us]; they are not stored with the data, and access is limited to [named roles]. API credentials are kept in a password manager with two-factor authentication.
```

## 4. Data Retention 2.1 - backups

> Describe how your organization stores encrypted backups/archives of Amazon
> Information including: (a) geographically separated backup location, and
> (b) restore procedures (RTO/RPO).

```
(a) The shop and its database are backed up [daily] by [hosting provider/backup tool], encrypted, to a separate location ([other data centre/region]); [number] days are kept. (b) RPO: [24 hours]. RTO: [4 hours]: the shop is restored from the latest backup by [us/the host]. A restore was last tested on [date]. Buyer data in orders is deleted [30] days after delivery by the module, unless tax law requires us to keep it.
```

## 5. Logging and Monitoring 2.6

> Describe your organization's security logging and monitoring system,
> including monitoring mechanisms for suspicious activities, and incident
> investigation procedures.

```
No PII is stored in any log. The module's activity log records order IDs, counts and statuses only, and removes e-mail addresses before saving. Server and access logs are kept [12 months] by [the host]. We review logins and logs [every two weeks] for failed logins, unusual access and errors; the host alerts us to attacks. Suspicious findings start our incident response plan.
```

## 6. Risk Management and Incident Response 1.6

> Summarize the steps taken within your organization's incident response plan
> to handle database hacks, unauthorized access, and data leaks.

```
1. Contain: block the access, change passwords, rotate the API credentials and revoke the app's authorisation. 2. Assess what data was affected, using our logs and Amazon's. 3. Notify Amazon at security@amazon.com within 24 hours of detection, and the authorities and buyers as GDPR requires. 4. Fix the cause and restore from backup. 5. Record the incident. The plan is reviewed [every six months].
```

## 7. Credential Management 1.4

> How does your organization enforce password management practices for all
> the systems handling Amazon information as it relates to required length,
> complexity and expiration period?

```
All accounts with access (Seller Central, Solution Provider Portal, hosting, shop back office, database) use passwords of at least 12 characters with upper and lower case, numbers and symbols, generated by a password manager, no personal data, no reuse of the last 10, minimum age 1 day, changed at least every 365 days, lockout after 10 failed attempts, and two-factor authentication.
```

## 8. Vulnerability Management 2.7 - tracking remediation

> How does your organization track remediation progress of findings
> identified from vulnerability scans and penetration tests?

```
The shop is scanned [monthly] for vulnerabilities with [tool/host's scanner], and tested [yearly]. Each finding is recorded in [our ticket list] with a deadline: critical within 7 days, high within 30, medium within 90. A finding is closed only after the fix is verified by a new scan. Accepted risks are recorded with their reason.
```

## 9. Vulnerability Management 2.7 - code vulnerabilities

> How does your organization remediate code vulnerabilities identified in the
> development lifecycle and during runtime?

```
We do not write our own code; we use PrestaShop and its modules, including Amazon Marketplace Pro. We install security updates for PrestaShop, PHP and modules [within 7 days] of release; the host patches the server. Module vendors fix reported vulnerabilities and send updates. Updates are tested on a staging copy of the shop before going live.
```
