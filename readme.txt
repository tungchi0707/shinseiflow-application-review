=== ShinseiFlow – Application Review & Approval Workflow ===
Contributors: casperyeh
Tags: application form, approval workflow, review, notifications, form management
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.4.3.1
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

Important baseline note:

The current baseline is a standalone generic plugin. Runtime identifiers, documentation, and changelog text are maintained with generic naming for clean same-site testing and distribution preparation.

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

= 0.4.3.1 =
* Fixed missing admin stylesheet loading on the About ShinseiFlow page.

= 0.4.3 =
* Added an About ShinseiFlow admin page with project resources, support information, credits, and acknowledgements.
* Added the About submenu and matching admin tab.
* Updated the POT file for the new About page strings.

= 0.4.2.4 =
* Fixed frontend status badge fallback to use English default strings when language-specific status labels are empty.

= 0.4.2.3 =
* Fixed remaining frontend consent fallback strings to use English defaults.
* Added gettext handling for a small number of admin table labels.
* Updated the POT file.

= 0.4.2.2 =
* Fixed admin application detail status labels to use admin gettext labels instead of frontend translation strings.

= 0.4.2.1 =
* Fixed frontend translation fallback behavior to use English defaults instead of Japanese when a language-specific string is empty.

= 0.4.2 =
* Updated default frontend strings and email templates to use English as the baseline.
* Made additional application detail labels translatable.
* Updated the POT file.
* Preserved existing saved settings and custom templates.

= 0.4.1.1 =
* Made application status labels translatable in admin screens.

= 0.4.1 =
* Aligned the plugin name, slug, text domain, main file, and language file names for WordPress.org submission.
* Updated the visible admin brand name to ShinseiFlow.
* Kept internal prefixes, options, shortcodes, and database data unchanged for compatibility.

= 0.4.0.3 =
* Updated extension hook names to use the plugin prefix for WordPress.org compatibility.

= 0.4.0.2 =
* Changed the Cloudflare Turnstile enable control to a switcher UI.
* Disabled dependent Turnstile settings when Turnstile is not enabled.
* Preserved existing Turnstile settings when the disabled UI state is saved.

= 0.4.0.1 =
* Fixed an activation error caused by a leftover default term initialization call after removing the built-in public publishing feature.

= 0.4.0 =
* Removed built-in public information publishing and ACF/taxonomy mapping features from the core plugin.
* Removed public publishing settings, form field mapping controls, and category color mapping UI from the core admin screens.
* Added application status hooks for future extensions.
* Kept existing saved data and legacy records intact for backward compatibility.

= 0.3.2.1 =
* Removed the external Cloudflare Turnstile frontend script enqueue to clear the Plugin Check enqueued remote script error.

= 0.3.2 =
* Changed Translation String Settings group labels to English source strings.
* Regenerated the POT file.
* Added Japanese language pack baseline.
* Added Traditional Chinese language pack baseline.

= 0.3.1 =
* Converted visible admin page template and admin JavaScript source strings to English for WordPress.org i18n preparation.

= 0.3.0 =
* Converted visible Core PHP source strings to English for WordPress.org i18n preparation.

= 0.2.9.10.1 =
* Documented the remaining file upload request handling warning with a targeted PHPCS ignore after confirming surrounding nonce and capability checks.

= 0.2.9.10 =
* Made Cloudflare Turnstile opt-in behavior explicit and tightened request/input warning cleanup where safe.

= 0.2.9.9 =
* Cleaned up remaining request, nonce, and input sanitization warnings with targeted allowlists, sanitization, and local PHPCS notes where appropriate.

= 0.2.9.8 =
* Prefixed local variables in uninstall.php to satisfy WordPress naming convention checks.

= 0.2.9.7 =
* Cleaned up remaining request handling and input sanitization warnings where safe.

= 0.2.9.6 =
* Audited custom database table queries and caching-related Plugin Check warnings.

= 0.2.9.5 =
* Audited nonce and request handling for admin actions, AJAX handlers, and frontend request flows.

= 0.2.9.4 =
* Hardened filesystem handling around upload finalization and secure download output.

= 0.2.9.3 =
* Hardened prepared SQL usage for application management queries and frontend shortcode lookups.

= 0.2.9.2.3 =
* Updated the readme Tested up to value to WordPress 7.0 based on the current test environment.

= 0.2.9.2.2 =
* Updated plugin metadata and readme external services documentation for review readiness.

= 0.2.9.2.1 =
* Restored frontend input placeholder attributes in the shortcode escaping allowlist.

= 0.2.9.2 =
* Added a scoped escaping pass for frontend shortcode output.

= 0.2.9.1 =
* Added a scoped escaping pass for application class admin output and messages.

= 0.2.9.0.1 =
* Refined settings page select output escaping for Plugin Check follow-up review.

= 0.2.9.0 =
* Added a scoped escaping pass for settings, notifications, and shared admin class output.

= 0.2.8.9 =
* Added the first scoped admin page escaping pass for Plugin Check output review.

= 0.2.8.8 =
* Added WordPress.org metadata, license fields, stable tag, tested version, and low-risk enqueue cleanup for external font loading.

= 0.2.8.7.4 =
* Removed the development-only automatic approval test feature from runtime settings and hooks.

= 0.2.8.7.3 =
* Refined the application number preview UI so it no longer uses the settings note style.

= 0.2.8.7.2 =
* Consolidated ordinary admin settings note boxes into nearby description text.

= 0.2.8.7.1 =
* Fixed the deactivation notice trigger on the WordPress plugins screen.

= 0.2.8.7 =
* Added uninstall data cleanup controlled by the data deletion setting and a deactivation data retention notice.

= 0.2.8.6 =
* Performed final WordPress i18n QA, added translator comments, and adjusted admin i18n script data merging.

= 0.2.8.5 =
* Added WordPress i18n wrappers for low-risk frontend and shared hardcoded system messages.

= 0.2.8.4 =
* Added WordPress i18n wrappers for low-risk server-side and AJAX messages visible to administrators.

= 0.2.8.3 =
* Added WordPress i18n wrappers for low-risk residual admin PHP UI strings in dashboard, settings, and application render views.

= 0.2.8.2 =
* Added WordPress-localized admin JavaScript UI strings while preserving original Japanese fallbacks.

= 0.2.8.1 =
* Added WordPress i18n wrappers for low-risk admin page labels, descriptions, help text, table headers, and section titles.

= 0.2.8.0 =
* Added WordPress i18n wrappers for low-risk admin common labels, notices, modal text, and accessibility labels.

= 0.2.7.9 =
* Added WordPress i18n wrappers for low-risk admin menu, tab, and page title strings.

= 0.2.7.8 =
* Fixed form settings delete interactions and extracted frontend redirect JavaScript into a standalone asset file.

= 0.2.7.7 =
* Extracted remaining executable page inline JavaScript into standalone admin asset files.

= 0.2.7.6 =
* Extracted asset trait JavaScript into standalone asset files and enqueued them through WordPress script APIs.

= 0.2.7.5 =
* Repackaged from the verified v0.2.7.4 baseline with version metadata updated only.

= 0.2.7.4 =
* Removed legacy default form fields, sections, and consent items from new installations.

= 0.2.7.3 =
* Added bulk permanent deletion for applications already moved to deleted status.

= 0.2.7.2 =
* Added permanent deletion for applications already moved to deleted status.

= 0.2.7.1 =
* Refined multilingual settings UI wording and admin menu labels.

= 0.2.7 =
* Added multilingual settings with display language controls and provider-based AI translation support for OpenAI and Gemini.

= 0.2.6.2 =
* Improved the application number rule settings UI with a compact horizontal layout.

= 0.2.6.1 =
* Removed remaining legacy and case-specific keywords from runtime code, documentation, and changelog.

= 0.2.6 =
* Added configurable application number rule settings for new applications.

= 0.2.5 =
* Performed a full legacy identifier cleanup for frontend fields, CSS/HTML namespace, integration identifiers, post meta keys, and default text.

= 0.2.4 =
* Separated frontend query args, hidden fields, and transient keys to standalone generic identifiers.

= 0.2.3 =
* Added an identifier conflict audit document for same-site testing preparation.

= 0.2.2.1 =
* Fixed the remaining test mail notice query key and scheduled hook names after option/table/admin slug separation.

= 0.2.2 =
* Renamed option keys, database table names, settings groups, and admin menu slugs to standalone generic identifiers.
* Started clean generic options and tables without copying or deleting prior data.
* Renamed admin page URLs and admin notice query keys to standalone generic identifiers.

= 0.2.1.1 =
* Fixed the blocked log delete action and nonce names after admin-post action separation.

= 0.2.1 =
* Renamed public shortcodes to [tcarm_form], [tcarm_status], [tcarm_view], and [tcarm_edit].
* Renamed plugin admin-post actions, AJAX action, and nonce action / field names for standalone operation.
* Removed registration of earlier public shortcode aliases.
* Kept options, database tables, settings groups, admin menu slugs, CSS classes, content types, metadata, download token structures, and email placeholders unchanged for that release.

= 0.2.0 =
* Renamed PHP class, trait, and plugin constants to the TCARM namespace.
* Renamed the primary plugin capability to manage_tcarm_applications.
* Renamed include filenames to class-tcarm-*.php.
* Kept public shortcodes, options, database tables, actions, nonce names, and hooks unchanged for that release.

= 0.1.0 =
* Initial standalone baseline.
* Renamed plugin identity to Application Review Manager while preserving behavior for stability.
