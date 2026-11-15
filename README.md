# Comment Control

<p align="center">
  <strong>Ultra-fast, zero-bloat comment governance, invisible zero-asset anti-spam shield, and privacy optimization for WordPress.</strong>
</p>

[![WordPress Plugin](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/plugins/comment-control/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](LICENSE.txt)

---

## ⚡ Why Comment Control?

Most comment and anti-spam plugins for WordPress inject heavy JavaScript frameworks, load megabytes of remote CSS, create complex custom database tables, and degrade your Google Core Web Vitals.

**Comment Control** is engineered with a strict performance-first philosophy:
- **0 KB Frontend Assets**: 0 KB JavaScript, 0 KB CSS. Operates 100% via native WordPress PHP hooks executing in under 1 millisecond.
- **Zero Custom Database Tables**: Uses a single serialized option in `wp_options`. Clean activation, zero leftover database debris on uninstall.
- **Invisible Anti-Spam Shield (No CAPTCHA)**: Uses an accessible CSS-masked semantic honeypot and cryptographic time-trap to block automated spam bots before SQL database insertion.

---

## 🌟 Core Feature Modules

### 1. Governance & Lifecycle
- **Global Killswitch**: Turn off comments and discussion templates site-wide with a single toggle.
- **Automatic Page & Attachment Protection**: Force-disable comments on standard Pages and Media Attachments.
- **Custom Post Type Selector**: Selectively disable comments across custom post types.
- **Auto-Close by Publication Age**: Automatically close comments on older posts (e.g. after 60 days).
- **Character Budget Enforcer**: Set minimum character thresholds (stops 1-word "Thanks!" spam) and maximum character limits.

### 2. Invisible Anti-Spam Shield
- **Zero-Latency Honeypot**: Hidden semantic input field traps automated bot scripts with zero server overhead.
- **Cryptographic Speed Trap**: Rejects instant bot submissions (< 2.5 seconds) while allowing genuine human comments.
- **Author URL Stripper**: Completely removes the "Website" URL input field, eliminating ~85% of comment backlink spammers.
- **Link Gatekeeper**: Limit the maximum number of URLs allowed per comment or force multi-link submissions into the moderation queue.

### 3. Privacy, Performance & SEO
- **IP Address Anonymizer**: Anonymizes commenter IP addresses (`127.0.0.1`) to ensure 100% GDPR and CCPA compliance.
- **SEO Crawl Budget Optimizer**: Rewrites `?replytocom` query loops to clean in-page anchors and 301-redirects crawler queries to canonical permalinks.
- **Gravatar Killer**: Option to disable external Gravatar lookups, eliminating 20–100+ external DNS calls on discussion-heavy posts.

---

## 🛠️ System Requirements
- **WordPress:** 6.0 or higher
- **PHP:** 7.4 or higher

---

## 🤝 Contributing
Issues and feature requests are welcome! Feel free to visit the [issues page](https://github.com/ProtoXenTech/comment-control/issues).

---

## 📄 License
Licensed under the [GNU General Public License v2.0 or later](LICENSE.txt).  
Crafted with pride by **[ProtoXen](https://protoxen.com/)**.
