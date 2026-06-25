# WC Child Subscription Manager

A WooCommerce plugin that lets parents register their children and link them to the subscriptions they buy. Parents manage their own children from a front-end page and pick which child a subscription is for during checkout; admins can manage children on behalf of any parent.

Built for academies and clubs where a guardian buys training subscriptions for one or more children.

**License:** GPL-2.0-or-later · **Version:** 1.0.2 · **Requires:** WordPress 5.8+, WooCommerce, PHP 7.4+

---

## Live demo

In production on a US client site — **Futbol Elite Training** (Denver, CO):
[Children page](https://futbolelitetraining.com/children/) *(login required — parents only see their own children)*

---

## What it does

The plugin registers a **Children** custom post type, each child owned by the parent's user account. Parents add and edit their own children from a front-end Children page, and at checkout they choose which of their children the subscription applies to. Every view and selection is scoped to the logged-in parent, so one parent can never see or bind another parent's children.

## Features

- **Parent-managed children** — parents add, edit, and view their own children from a front-end page.
- **Checkout binding** — a dropdown on the WooCommerce checkout lets the parent link the subscription being purchased to one of their children.
- **Per-user ownership & isolation** — each child is tied to a parent account; parents only ever see and select their own children.
- **Admin management** — admins can add children on behalf of any parent and assign them to the correct parent account.

## Installation

1. Copy this plugin into `wp-content/plugins/wc-child-subscription-manager`.
2. In **Plugins**, activate **WC Child Subscription Manager**.
3. Make sure **WooCommerce** & **WooCommerce Subscriptions** are installed and active.
4. A **Children** section appears in the admin, and the front-end Children page and checkout dropdown become available.

## Usage

**As a parent:** open the Children page → add or edit your children → at checkout, select the child the subscription is for.

**As an admin:** WP admin → **Children** → add a child and assign it to the parent's account.


## How it's built

- `includes/class-cpt.php` — registers the Children custom post type and admin fields, including parent assignment.
- `includes/class-frontend.php` — the front-end Children management page and the checkout child-selection dropdown, scoped to the current user.
- Standard WordPress plugin structure with an `ABSPATH` guard and `plugin_dir_*` constants; text domain ready for translation.

## Author

**Muhammad Hamad** — Full-Stack & WordPress Developer
[mrhammad.com](https://mrhammad.com) · [github.com/Hamad-mirza](https://github.com/Hamad-mirza)
Developed at [Innovatek Solutions](https://innovateksol.com).

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
