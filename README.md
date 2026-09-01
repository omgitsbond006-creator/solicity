# Solicity Bank

A full-fledged demo banking platform — real backend, real MySQL database,
premium frontend. No React, no build step: plain PHP + HTML/CSS/JS that
runs anywhere PHP + MySQL runs.

## Run it

Requires PHP 8.1+ with `pdo_mysql` (bundled with most PHP installs,
including XAMPP/MAMP/WAMP) and a MySQL or MariaDB server.

**1. Create the database.** Easiest via phpMyAdmin: create a database
named `solicity_bank`, open its **Import** tab, and import
`db/schema.sql`. (Or just skip this step entirely — see below.)

**2. Point the app at your database.** Open `db/connect.php` and edit the
five constants at the top (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`,
`DB_PASS`) to match your setup. The defaults (`127.0.0.1`, `3306`,
`solicity_bank`, `root`, empty password) are the standard XAMPP/MAMP
values, so most local setups need no changes at all.

**3. Serve it.**

```
cd solicity
php -S localhost:8000 router.php
```

— or drop the folder into your Apache `htdocs`/`www` directory and open
it there instead; `.htaccess` files already block direct access to
`/db/` and `/lib/` for that setup (`router.php` does the same job for
PHP's built-in server).

Open **http://localhost:8000**. If you skipped step 1, the app notices
the tables don't exist yet and creates + seeds them itself on first
request — no manual import required either way. To reset the demo data,
just drop and recreate the `solicity_bank` database (or truncate its
tables) and reload the site.

## Demo logins

| Role | Email / Username | Password |
|---|---|---|
| Customer | ada@demo.test | demo123 |
| Customer | marcus@demo.test | demo123 |
| Admin | admin | admin123 |

Or open a brand-new account from the landing page — registration
provisions a real Checking + Savings account and a virtual card in the
database, plus a $250 welcome bonus.

## What's in it

**Public site** — a full marketing landing page: animated hero with a
tilting 3D card mockup, live stat counters, a trusted-by marquee, a
product screenshot, a how-it-works walkthrough, a comparison table,
testimonials, and an FAQ.

**Customer app** (`/app`) — dashboard with a real balance-trend chart,
spending-by-category breakdown, and account sparklines (all driven by
actual transaction history), a searchable/filterable/paginated
transaction ledger, transfers (between your own accounts, to another
Solicity customer, or bill pay to a preset list of billers), a
flip-to-reveal virtual card with real freeze/unfreeze, and account
settings (profile + password).

**Admin panel** (`/admin`) — bank-wide KPIs and a 14-day transaction
volume chart, and full customer management: create a customer (opens
their accounts and issues a card automatically), edit their profile,
reset their password, freeze the customer or any individual account,
issue additional cards, and permanently delete a customer. Every
transaction — a customer's or anyone's, from the customer page or the
bank-wide transaction monitor — can be edited, added, or deleted; the
account's balance and every later transaction's running total
recalculate automatically whenever you do.

## How it's built

- **Database**: MySQL/MariaDB via PDO, schema in `db/schema.sql` (import
  it in phpMyAdmin, or let the app create it automatically — see
  above), seeded with two demo customers plus ~60 days of realistic
  transaction history on first run. Real tables: `users`, `admins`,
  `accounts`, `cards`, `transactions`, with foreign keys and cascading
  deletes — not a flat file.
- **Backend**: one API dispatcher (`api/api.php`) handling every
  mutating action (registration, transfers, bill pay, card freeze, admin
  edits) with prepared statements and `PDO` transactions, so a transfer
  either fully applies on both accounts or not at all. Editing, adding,
  or deleting a transaction replays that account's full history to keep
  every running balance internally consistent.
- **Frontend**: a from-scratch design system (`assets/css/style.css`) —
  dark editorial palette, glassmorphism panels, a 3D tilting/flipping
  card component, scroll-reveal and animated-counter micro-interactions
  — plus Chart.js (vendored locally, no CDN dependency) for the real
  data visualizations. No framework, no bundler.
- **Routing**: all links and asset paths resolve through a small
  `base_url()` helper keyed off `SCRIPT_NAME`, so the app works whether
  it's hosted at a web root or under a subdirectory.

This is a simulation built for demonstration purposes — no real funds,
accounts, or financial data are involved.
