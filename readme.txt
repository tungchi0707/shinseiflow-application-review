=== ShinseiFlow – Application Review & Approval Workflow ===
Contributors: tungchi07
Tags: application form, approval workflow, review, notifications, form management
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.4.3.34
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create application forms, review submissions, manage approval workflows, and notify applicants from WordPress.

== Description ==

ShinseiFlow is a WordPress plugin for receiving, reviewing, approving, and managing applications submitted from frontend forms.

Features:

* Frontend application form.
* Application status lookup.
* Submitted content view.
* Revision / resubmission flow.
* Admin review workflow.
* Approval / rejection notifications.
* Protected download file settings.
* Cloudflare Turnstile spam protection.
* Optional AI translation helper.
* Role-based plugin access settings.
* Privacy and data retention guidance.
* Configurable application number rules for new applications.
* Multilingual display settings for admin translation inputs.

== External Services ==

This plugin can connect to external services only when related features are enabled or used by an administrator.

= Cloudflare Turnstile =

Cloudflare Turnstile is disabled by default. This plugin loads the Cloudflare Turnstile JavaScript API only when a site administrator explicitly enables Turnstile in the plugin settings and configures the required site key and secret key.

When enabled, Turnstile is used to verify whether a form submission is made by a human and to help protect application forms from automated spam submissions.

When Turnstile is enabled and configured, this plugin loads the Turnstile JavaScript API from:

https://challenges.cloudflare.com/turnstile/v0/api.js

When a protected form is submitted, this plugin sends the Turnstile response token to Cloudflare for server-side verification using:

https://challenges.cloudflare.com/turnstile/v0/siteverify

The data sent to Cloudflare may include the Turnstile response token and the visitor's IP address, depending on the verification request. If Turnstile is disabled or not fully configured, this plugin does not load the Turnstile script and does not send Turnstile verification requests.

Cloudflare Turnstile is provided by Cloudflare, Inc.

Terms of Service:
https://www.cloudflare.com/website-terms/

Privacy Policy:
https://www.cloudflare.com/privacypolicy/

= OpenAI API =

This plugin can connect to the OpenAI API when an administrator manually uses the AI translation feature.

The AI translation feature may send form labels, descriptions, placeholders, and other translation target text configured in the plugin to OpenAI for translation.

The plugin does not automatically send application submissions to OpenAI as part of the normal application workflow.

OpenAI API is provided by OpenAI, L.L.C.

Terms of Use:
https://openai.com/policies/terms-of-use/

Privacy Policy:
https://openai.com/policies/privacy-policy/

= Google Gemini API =

This plugin can connect to the Google Gemini API when an administrator manually uses the AI translation feature and selects Gemini as the AI provider.

The AI translation feature may send form labels, descriptions, placeholders, and other translation target text configured in the plugin to Google for translation.

The plugin does not automatically send application submissions to Google Gemini as part of the normal application workflow.

Google Gemini API is provided by Google LLC.

Terms of Service:
https://policies.google.com/terms

Privacy Policy:
https://policies.google.com/privacy

== Changelog ==

= 0.4.3.34 =

* Updated the WordPress.org contributor username.

= 0.4.3.33 =

* Hardened inline-script JSON encoding to prevent script-breakout risks while preserving existing admin behavior.

= 0.4.3.32 =

* Improved recipient, CC, and BCC email sanitization while preserving multi-address support.

= 0.4.3.31 =

* Moved application detail admin inline CSS to the enqueued admin stylesheet for WordPress.org compliance.

= 0.4.3.30 =

* Removed the built-in arbitrary Custom CSS settings for WordPress.org compliance.

= 0.4.3.29 =

* Unified About-page resource and support card structure.
* Replaced resource external-link icon with a clear action button.
* Refined About-page card height and spacing.

= 0.4.3.28 =

* Unified About-page resource and support card heights.
* Refined card spacing and vertical rhythm.

= 0.4.3.27 =

* Simplified About-page resource links.
* Updated project support URL.
* Refined resource card spacing.

= 0.4.3.26 =

* Added multilingual Consent item fields.
* Added AI translation support for Consent content.
* Added language-aware Consent rendering with base-language fallback.
* Preserved existing Consent behavior and data compatibility.
