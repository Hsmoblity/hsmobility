# WordPress REST Endpoint Implementation – Submit Consultation (Option B)  
**Project:** HSM (Home Service Management) – Consultation Form  
**Version:** 1.0  
**Author:** Inception Team  
**Date:** 2025‑11‑11  

---

## 1. Executive Summary  
The current client‑side form posts directly to Web3Forms, which limits control over data handling, security, and analytics. By moving the submission flow to a **self‑hosted WordPress REST endpoint**, we gain:

| Benefit | Impact |
|---------|--------|
| **Data ownership** | All submissions are stored in WordPress (custom post type) for full auditability. |
| **Security** | Server‑side validation, reCAPTCHA, rate‑limiting, and anti‑spam safeguards protect against abuse. |
| **Notifications** | Immediate email alerts to the admin team. |
| **Admin UI** | Native WordPress list table for reviewing, filtering, and exporting submissions. |
| **Scalability** | The endpoint can be reused by other front‑ends (mobile, headless CMS) without re‑implementing logic. |

---

## 2. Business Value & Success Metrics  

| Metric | Target | Measurement |
|--------|--------|-------------|
| **Submission volume** | 1,000 consultations per month | WP post count |
| **Error rate** | < 2 % of submissions | API response codes |
| **Spam detection** | 0 % of valid submissions flagged as spam | Rate‑limit & reCAPTCHA logs |
| **Email delivery** | 99 % of notification emails sent | SMTP logs / email analytics |
| **Admin review time** | < 5 min per submission | Time‑to‑action in WP |
| **User satisfaction** | NPS ≥ 70 | Post‑consultation survey |

---

## 3. Scope (In‑Scope / Out‑of‑Scope)

| In‑Scope | Out‑of‑Scope |
|----------|--------------|
| Register CPT `consultation_request` | Full CRM integration (outside webhook) |
| Custom REST route `/wp-json/hsm/v1/submit-consultation` | Front‑end UI redesign (only API call) |
| Server‑side validation & sanitization | Multi‑language support |
| reCAPTCHA v3 verification (optional) | Advanced analytics dashboard |
| Rate‑limiting per IP & per user | Mobile app integration (unless using same endpoint) |
| Admin menu & list table for submissions | Email template design (use existing site email) |
| Email notifications to admin | Logging to external SIEM |
| Unit & manual testing | Deployment automation (CI/CD) – optional |

---

## 4. Detailed Requirements  

### 4.1. API Contract  

| Method | URL | Headers | Body | Success Response | Error Response |
|--------|-----|---------|------|------------------|----------------|
| POST | `/wp-json/hsm/v1/submit-consultation` | `Content-Type: application/json` | JSON payload (see §4.2) | `200 OK` – `{ "success": true, "id": 123, "message": "Consultation request received" }` | `400/422/429` – `{ "success": false, "error": "..." }` |

#### 4.2. Request Payload  

```json
{
  "firstName": "John",
  "lastName": "Doe",
  "email": "john@example.com",
  "phone": "123-456-7890",
  "address": "123 Main St",
  "city": "Toronto",
  "province": "ON",
  "postalCode": "M5V 3A8",
  "preferredContact": "email",
  "preferredTime": "morning",
  "urgency": "within_week",
  "additionalNotes": "...",
  "products": [
    { "id":"prod_1","title":"Acorn 180 Stairlift","quantity":1,"price":500 }
  ],
  "orderTotals": { "subtotal": 1000, "tax": 130, "total": 1130 },
  "recaptchaToken": "..."
}
```

#### 4.3. Validation Rules  

| Field | Required | Type | Constraints |
|-------|----------|------|-------------|
| `firstName`, `lastName` | Yes | string | 1‑50 chars |
| `email` | Yes | string | Valid email regex |
| `phone` | Optional | string | Phone number format (US/Canada) |
| `address`, `city`, `province`, `postalCode` | Yes | string | 1‑100 chars |
| `preferredContact` | Yes | enum (`email`, `phone`) |
| `preferredTime` | Yes | enum (`morning`, `afternoon`, `evening`) |
| `urgency` | Yes | enum (`within_week`, `within_month`, `any_time`) |
| `products` | Yes | array | Each item must have `id`, `title`, `quantity`, `price` |
| `orderTotals` | Yes | object | `total` = `subtotal` + `tax` (allow tolerance of ±5 %) |
| `recaptchaToken` | Optional | string | If present, must pass verification |

### 4.4. Security Features  

| Feature | Implementation |
|---------|----------------|
| **reCAPTCHA v3** | Optional; configured via plugin settings. Server verifies token with Google API. |
| **Rate‑limiting** | Transient `hsm_submit_lock_{ip}` counts submissions. Block after 5 per hour. |
| **Honeypot** | Hidden field `company` (or similar) in form; if filled, reject. |
| **Input Sanitization** | `sanitize_text_field`, `sanitize_email`, `wp_strip_all_tags`, `wp_kses_post`. |
| **Permission** | Public (`__return_true`) – only validation & security checks protect the endpoint. |

### 4.5. Data Storage  

| Component | Details |
|-----------|---------|
| **CPT** | `consultation_request` – `public => false`, `show_ui => true`, `supports => ['title','editor']`. |
| **Post Title** | `"Consultation from {firstName} {lastName} - {date}"`. |
| **Post Content** | JSON‑encoded sanitized payload (for quick view). |
| **Meta Fields** | `email`, `phone`, `address`, `city`, `province`, `postalCode`, `preferredContact`, `preferredTime`, `urgency`, `additionalNotes`, `products`, `orderTotals`. |
| **Status** | `private` (default) – not visible on front‑end. |

### 4.6. Notification Email  

* Recipient: Site admin email (configurable).  
* Subject: `"New Consultation Request – {firstName} {lastName}"`.  
* Body: Human‑readable summary + link to WP edit page.  
* Use `wp_mail` (SMTP plugin recommended).  

### 4.7. Admin UI  

| Feature | Description |
|---------|-------------|
| **Menu** | `HSM > Consultation Requests` (under Tools). |
| **List Table** | Columns: Name, Email, Phone, Status, Date. |
| **Columns** | `name`, `email`, `phone`, `status`, `date`. |
| **Row Actions** | View (edit screen), Delete, Export CSV (bulk). |
| **Edit Screen** | Displays submission details in meta boxes; `post_content` shows JSON preview. |

### 4.8. Front‑End Integration (Next.js)  

1. **Endpoint URL** – `https://your-site.com/wp-json/hsm/v1/submit-consultation`.  
2. **Form Submission** – `fetch` POST with `Content-Type: application/json`.  
3. **reCAPTCHA** – If enabled, obtain token via `grecaptcha.execute` and include `recaptchaToken`.  
4. **Error Handling** – Show user‑friendly messages based on API response.  
5. **Optional Proxy** – Next.js API route `/api/submit-consultation` can forward the request server‑side, hiding the WP endpoint from the client.

### 4.9. Testing Strategy  

| Test Type | Scope |
|-----------|-------|
| **Unit Tests** | handler validation, reCAPTCHA verification, rate‑limiting, post creation. |
| **Integration Tests** | End‑to‑end flow: form → WP endpoint → email. |
| **Manual Tests** | UI review, spam/honeypot, invalid data, rate‑limit breach. |
| **Load Tests** | Simulate high traffic to verify rate‑limiting. |
| **Security Tests** | Attempt injection, CSRF, and reCAPTCHA bypass. |

### 4.10. Deployment & Operations  

| Item | Details |
|------|---------|
| **Plugin Settings** | Admin page for reCAPTCHA secret, notification email, rate‑limit thresholds. |
| **Caching** | Exclude POST route from caching (e.g., Cloudflare page rule). |
| **SMTP** | Ensure `wp_mail` works; configure via SMTP plugin. |
| **Logging** | Use `error_log` or WP options for transient logs; consider external log aggregation. |
| **Backup** | CPT data should be included in regular WP backups. |
| **Rollback** | Versioned plugin; disable endpoint if critical bugs appear. |

---

## 5. Risks & Mitigations  

| Risk | Impact | Mitigation |
|------|--------|------------|
| **reCAPTCHA mis‑configuration** | False positives → legitimate users blocked | Provide clear admin UI with test token; fallback to honeypot. |
| **Rate‑limiting too strict** | Users blocked after legitimate submissions | Allow per‑user (email) limit in addition to IP; provide “Contact us” fallback. |
| **Email delivery failure** | Admin misses urgent requests | Integrate with transactional email service (SendGrid, Mailgun) and monitor bounce rates. |
| **Data privacy** | Sensitive info stored in WP | Ensure CPT is `private`; restrict edit capabilities to admins. |
| **Performance impact** | Large payloads slow server | Validate payload size; reject > 10 KB. |
| **Future integration** | Need to push data to CRM | Design CPT meta keys to be easily exported; plan webhook module. |

---

## 6. Assumptions  

* The WordPress site runs on PHP 8.1+ and has REST API enabled.  
* The server has a functional SMTP configuration or an SMTP plugin installed.  
* Front‑end developers can update the Next.js form to include the new endpoint URL.  
* Admin users have sufficient permissions to edit CPTs.  
* The reCAPTCHA secret key will be provided by the marketing team if needed.

---

## 7. Timeline & Milestones  

| Phase | Duration | Deliverable |
|-------|----------|-------------|
| **Planning & Design** | 1 day | Detailed PRD, API spec, CPT schema |
| **CPT & Route Registration** | 1 day | `consultation_request` CPT, `/submit-consultation` route |
| **Handler Implementation** | 2 days | Validation, sanitization, reCAPTCHA, rate‑limiting, post creation |
| **Admin UI** | 1 day | List table, columns, edit screen |
| **Email Notification** | 0.5 day | wp_mail integration, template |
| **Frontend Integration** | 1 day | Next.js form update, error handling |
| **Testing** | 1 day | Unit + manual tests, bug fixes |
| **Documentation** | 0.5 day | README, API guide, admin settings |
| **Deployment** | 0.5 day | Plugin release, CI pipeline update |

**Total:** ~8 days (≈ 1 week) assuming all resources are available.

---

## 8. Resources Required  

| Role | Hours | Notes |
|------|-------|-------|
| **WordPress Developer** | 8 | CPT, route, handler, admin UI |
| **Backend Engineer** | 4 | reCAPTCHA integration, rate‑limiting, email |
| **Frontend Engineer (Next.js)** | 4 | Form update, API calls |
| **QA Engineer** | 4 | Test cases, CI integration |
| **Project Manager** | 2 | Coordination, risk tracking |

---

## 9. Future Enhancements (Post‑MVP)  

1. **Webhook Integration** – Notify external CRM (e.g., HubSpot) on submission.  
2. **CSV Export** – Bulk download of submissions from admin UI.  
3. **Front‑End Preview** – Secure preview token for editors.  
4. **Multi‑Language Support** – Store & display data in locale‑specific fields.  
5. **Analytics Dashboard** – Visualize submission trends, conversion rates.  
6. **Mobile App Integration** – Reuse the same endpoint for native apps.  

---

## 10. Acceptance Criteria  

* The endpoint accepts valid JSON, creates a private CPT, and returns a 200 response with the new post ID.  
* Invalid or missing fields trigger a 422 response with clear error messages.  
* reCAPTCHA verification fails → 403 response.  
* Rate‑limit breaches → 429 response.  
* Admin UI lists all submissions with required columns and allows editing.  
* Notification email is sent to the configured admin address.  
* Front‑end form successfully posts to the endpoint and handles success/error feedback.  

---

