=== Comment Control ===
Contributors: protoxen
Donate link: https://protoxen.com/
Tags: comments, disable comments, antispam, spam, comment privacy
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ultra-fast, zero-bloat comment governance, invisible zero-asset anti-spam shield, privacy protection, and SEO crawl optimization.

== Description ==

**Comment Control** is an ultra-fast, zero-bloat comment management and protection plugin engineered specifically for WordPress performance purists, agencies, and publishers.

Unlike bulky third-party discussion platforms and heavyweight anti-spam plugins that inject megabytes of JavaScript, remote trackers, and foreign database tables, Comment Control operates at **sub-millisecond execution speeds** with **zero frontend assets (0 KB JavaScript, 0 KB CSS)**.

### ⚡ Key Features

* **Zero Frontend Weight**: 0 KB JS, 0 KB CSS on your frontend. Completely native and bloat-free.
* **Invisible Anti-Spam Shield (No CAPTCHA)**: Uses an accessible CSS-masked honeypot and cryptographic time-trap to block automated spam bots before SQL database insertion.
* **Granular Lifecycle & Rules**:
  * Global killswitch to disable comments everywhere with one click.
  * Turn off comments on Pages and Media / Attachments automatically.
  * Selectively disable comments across custom post types.
  * Auto-close comments on older posts by publication age.
* **Character Budget Enforcer**: Set minimum and maximum comment lengths to prevent low-effort spam (e.g., "Thanks!", "Nice post!") and massive buffer dumps.
* **Link Gatekeeper**: Strip author "Website / URL" fields and limit hyperlinks inside comments to eliminate backlink spammers.
* **Privacy & GDPR Compliance**: Automatically anonymize commenter IP addresses (`127.0.0.1`) so no personally identifiable information (PII) is retained.
* **SEO Crawl Budget Optimizer**: Clean `?replytocom` parameters to eliminate duplicate query strings and 301-redirect crawlers to canonical permalinks.
* **Gravatar Killer**: Option to disable external Gravatar lookups to eliminate 20–100+ third-party DNS requests.

== Installation ==

1. Upload the `comment-control` folder to the `/wp-content/plugins/` directory, or install directly through the WordPress Plugins dashboard.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to **Settings > Comment Control** to configure your rules, shield, and privacy options.

== Frequently Asked Questions ==

= Does Comment Control slow down my website? =
No! Comment Control does not load any CSS or JavaScript on your frontend. It utilizes lightweight native WordPress PHP hooks executing in under 1 millisecond.

= Does it create custom database tables? =
No. Comment Control stores its configuration in a single standard `wp_options` row and does not mutate your database schema.

= How does the invisible anti-spam shield work? =
Comment Control injects a semantic honeypot field masked from humans. Automated bots crawl and fill it, triggering immediate rejection. It also uses a cryptographic time-trap to block instant submissions.

== Screenshots ==

1. Lifecycle & Post Type Governance Studio.
2. Invisible Zero-Asset Anti-Spam Shield.
3. Privacy & SEO Crawl Optimization Settings.

== Changelog ==

= 1.0.0 =
* Initial public release.
