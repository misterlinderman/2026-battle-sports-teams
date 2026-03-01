# Battle Sports Platform — Manual QA Checklist

**Phase:** Final Testing  
**Date:** _________  
**Tester:** _________  
**Environment:** _________ (Local / Staging / Production)

---

## Pre-requisites

- [ ] Run integration tests: `wp eval-file wp-content/test-integration.php` — all pass
- [ ] Test accounts available: `test_coach`, `test_designer` (see cursorrules for credentials)
- [ ] Test devices: iPhone SE (or similar narrow viewport), iPad (or tablet viewport)

---

## 1. Intake Form Submission (6 Products)

Complete a full intake flow for each product. Verify submission creates WooCommerce order, team, roster, and artwork queue entry.

| Product | Form URL/Slug | Step 1 | Step 2 | Step 3 | Step 4 | Order Created | Artwork Queue |
|---------|---------------|--------|--------|--------|--------|---------------|---------------|
| Battle 7v7 | product=7v7 | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ |
| Battle Flag | product=flag | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ |
| Battle Women's Flag | product=womens-flag | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ |
| Battle Charlie Tackle | product=charlie-tackle | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ |
| Battle Alpha Tackle | product=alpha-tackle | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ |
| Battle Bravo Tackle | product=bravo-tackle | ☐ | ☐ | ☐ | ☐ | ☐ | ☐ |

**Per product, verify:**
- [ ] Customer info (name, email, phone, shipping) saves correctly
- [ ] Team info (org, team name, colors, logo upload) saves correctly
- [ ] Design options (jersey style, shorts/pants, numbers, etc.) display per product
- [ ] Roster or quantity step completes without JS errors
- [ ] $50 submission fee is charged via WooCommerce
- [ ] Confirmation/redirect after payment works
- [ ] Artwork queue entry created with correct `order_ref` and `product_type`

---

## 2. Portal Dashboard (Coach Role)

**Login as:** `test_coach`

- [ ] Portal page loads at `/portal/` (or configured URL)
- [ ] Dashboard shows: My Teams, Active Orders, Pending Approvals cards
- [ ] "Start New Order" link/button works
- [ ] "Manage Rosters" link goes to `/portal/rosters/` (or child page)
- [ ] "View Artwork Queue" link visible only for designers (hidden for coaches)
- [ ] Recent Orders table displays (or "No orders yet" when empty)
- [ ] My Artwork section shows pending approvals when applicable
- [ ] No PHP errors or broken layout

---

## 3. Roster Management (Coach Role)

**Login as:** `test_coach`  
**URL:** `/portal/rosters/` (or Manage Rosters page)

- [ ] Roster list/team selector loads
- [ ] **Add:** Create new player (name, number, sizes) — saves successfully
- [ ] **Edit:** Update existing player — changes persist
- [ ] **Delete:** Remove player — removed from roster
- [ ] **Import:** Import roster (paste/list) — bulk players added
- [ ] Team selector works when user has multiple teams
- [ ] "Back to Portal" link works

---

## 4. Artwork Queue (Designer Role)

**Login as:** `test_designer`  
**URL:** `/portal/artwork-queue/` (or Artwork Queue page)

- [ ] Artwork queue table/list loads
- [ ] Filters work: status, designer, unassigned, date range
- [ ] Queue shows submitted artwork items with order ref, product type, status
- [ ] Can assign artwork to designer
- [ ] Status badges display correctly (submitted, in_queue, in_progress, proof_sent, etc.)

---

## 5. Proof Upload Flow (Designer Role)

**Login as:** `test_designer`  
**Prerequisite:** At least one artwork item in `in_progress` or suitable status

- [ ] Proof upload button/form visible for eligible artwork
- [ ] File upload accepts: JPG, PNG, PDF, AI, EPS (max 50MB)
- [ ] Upload succeeds and proof appears
- [ ] Status updates to `proof_sent` (or equivalent)
- [ ] Proof notification email sent to coach/customer
- [ ] Invalid file type rejected
- [ ] Oversized file (>50MB) rejected

---

## 6. Customer Approval Flow (Coach Role)

**Login as:** `test_coach` (owner of order/artwork)  
**Prerequisite:** Artwork with proof uploaded (status `proof_sent`)

- [ ] Pending approval appears in Portal dashboard "My Artwork"
- [ ] "View proof" link opens proof file
- [ ] **Approve:** Approve button — status changes to approved, success message
- [ ] **Request Revision:** Revision button + notes — status resets, designer notified
- [ ] Approval/revision actions work without page reload (AJAX)
- [ ] Approved artwork no longer appears in pending list

---

## 7. Email Delivery

**Verify these emails are sent and delivered:**

| Email | Trigger | Recipient | Check |
|-------|---------|-----------|-------|
| Submission confirmation | After intake + payment | Customer | ☐ |
| Proof notification | After designer uploads proof | Coach/Customer | ☐ |
| Approval notification | After customer approves | Designer / System | ☐ |

**Notes:**
- [ ] WP Mail SMTP (or configured mailer) is active
- [ ] Emails do not land in spam
- [ ] "From" address and branding correct

---

## 8. Mobile Responsiveness

**Test on:** iPhone SE (375×667) and iPad (768×1024) or equivalent

- [ ] **iPhone SE:** Intake form usable (no horizontal scroll, buttons tappable)
- [ ] **iPhone SE:** Portal dashboard readable, cards stack properly
- [ ] **iPhone SE:** Roster manager usable
- [ ] **iPhone SE:** Artwork queue readable
- [ ] **iPad:** Layout adapts (multi-column where appropriate)
- [ ] **iPad:** No overlapping elements or cut-off content
- [ ] Touch targets ≥ 44×44px where applicable

---

## 9. WooCommerce Payment Flow

- [ ] Add submission fee product to cart during intake
- [ ] Checkout page loads
- [ ] Test payment (use WooCommerce test mode / dummy gateway if available)
- [ ] Order created with correct total ($50)
- [ ] Order status updates (Processing/Completed)
- [ ] Order visible in WooCommerce → Orders
- [ ] Order ref and product type stored for artwork queue

---

## 10. Admin Panel

**Login as:** Administrator

- [ ] Battle Sports settings page accessible (e.g. Settings → Battle Sports)
- [ ] General tab: submission fee product, portal page config
- [ ] Integrations tab: Make.com webhook URL, webhook log
- [ ] Webhook log shows recent outbound calls (if any)
- [ ] No PHP errors in admin
- [ ] WooCommerce orders and products manageable

---

## Sign-off

| Role | Name | Date |
|------|------|------|
| QA Lead | | |
| Developer | | |

**Notes:**
