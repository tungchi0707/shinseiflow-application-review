=== ShinseiFlow – Application Review & Approval Workflow ===
Contributors: casperyeh
Tags: application form, approval workflow, review, notifications, form management
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.4.3.29
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

= 0.4.3.25 =

* Fixed AI field translation being blocked by optional empty source fields.
* Empty optional source values are now skipped while valid sources continue translating.
* Preserved existing translations and AI provider behavior.

= 0.4.3.24 =

* Improved spacing between frontend form fields.
* Kept help text visually grouped with its control.
* Stacked radio and checkbox group choices vertically.
* Preserved all existing form behavior.

= 0.4.3.23 =

* Refined field editor proportions and spacing.
* Standardized labels above controls.
* Expanded placeholder and description fields to full width.
* Preserved all existing settings behavior.

= 0.4.3.22 =

* Added missing translator comments for placeholder strings.
* Normalized PHP file line endings.
* Clarified nonce verification context for safe request reads.
* Documented required direct database access for custom tables.
* Preserved all existing behavior and data structures.

= 0.4.3.21 =

* Refined the field editor column proportions for better use of space.
* Standardized field labels above their controls.
* Expanded placeholder and description controls to full width.
* Preserved all existing field settings and behavior.

= 0.4.3.20 =

* Unified the field editor layout for saved and newly added fields.
* Improved field settings spacing and required-control alignment.
* Limited the form settings editor to a responsive 1000px content width.
* Improved usability on laptops and narrow admin screens.
* Preserved all existing field settings and saving behavior.

= 0.4.3.19 =

* Fixed fields being reassigned when new sections and fields were saved in the same request.
* Added a final section and field order synchronization before saving form settings.
* Ensured frontend section order follows the configured section order.
* Preserved the existing sections, fields, and database structure.

= 0.4.3.18 =

* Removed the legacy Single Checkbox field type.
* Renamed Checkbox Group to Checkbox in the field type interface.
* Preserved checkbox choices, array storage, validation, and the independent consent system.
* Updated Japanese and Traditional Chinese language resources.

= 0.4.3.17 =

* Added updated Japanese and Traditional Chinese language resources for the new field types and validation messages.
* Renamed field type labels to distinguish Single Checkbox, Checkbox Group, and Radio Button Group.
* Localized frontend validation messages and Yes/No display values.
* Added missing JavaScript localization strings for Radio Button Group and Checkbox Group.
* Preserved existing field type keys, stored data, and database schema.

= 0.4.3.15 =

* Added configurable checkbox group fields.
* Checkbox groups reuse the existing choices structure and store selected values as arrays.
* Added unique choice value validation for dropdown, radio, and checkbox group fields.
* Added server-side array allowlist validation, confirmation handling, display formatting, and edit/resubmission restoration.
* Preserved the existing single checkbox behavior.

= 0.4.3.14 =
* Added configurable radio button fields.
* Radio fields reuse the existing dropdown choices structure and scalar value storage.
* Added server-side allowlist validation and edit/resubmission state restoration.
* Fixed duplicate choice indexes after deleting and adding option rows.

= 0.4.3.13 =
* Added a configurable base language for AI translation.
* New installations select the base language from the WordPress locale, with English fallback.
* Fixed translation tools being permanently tied to Japanese.
* Preserved all existing translation and form data when changing the base language.

= 0.4.3.12 =
* Added complete Traditional Chinese, Simplified Chinese, and Korean default translation strings.
* New installations now initialize all five supported language defaults.
* Existing empty or missing values for the three newly supported defaults are safely populated without overwriting customized translations.

= 0.4.3.11 =
* Added correct Japanese default translations for frontend translation strings.
* Prevented English source defaults from filling Japanese translation fields.
* Added a one-time migration that preserves custom Japanese translations while replacing unchanged English defaults.

= 0.4.3.10 =
* Cleaned up request-created attachments when a rejected application update fails.
* Avoided a shared upload email rate-limit bucket when no valid contact email is available.
* Stopped pending upload cleanup when the storage root is a symbolic link.

= 0.4.3.9 =
* Completed field, consent, Turnstile, and upload rate-limit checks before storing pending uploads.
* Revalidated required consent items before initial and edited applications are saved.
* Added precise failed-request upload cleanup and scheduled cleanup for pending files older than 24 hours.

= 0.4.3.8 =
* Moved application history parsing, writing, and timeline rendering to a dedicated PHP trait.
* Preserved the existing history schema, event keys, compatibility fallbacks, and output behavior.
* No functional or UI behavior was changed.

= 0.4.3.7 =
* Moved the Privacy and Data Retention admin page rendering to a dedicated PHP trait.
* Preserved the existing Settings API, option, lifecycle, and page output behavior.
* No functional or UI behavior was changed.

= 0.4.3.6 =
* Moved application number generation and rule processing to a dedicated PHP trait.
* Preserved all existing methods, settings, and application number behavior.
* No functional or UI behavior was changed.

= 0.4.3.5 =
* Loaded admin JavaScript only on the ShinseiFlow pages where it is required.
* Loaded the WordPress Media Library and sortable dependency only on pages that use them.
* Preserved existing functionality and UI behavior.

= 0.4.3.4 =
* Moved the About ShinseiFlow admin page styles to a dedicated stylesheet.
* Loaded the About stylesheet only on the About page.
* No functional or visual behavior was changed.

= 0.4.3.3 =
* Removed a no-op resource hints callback and an orphaned category color rules script.
* Removed an unused category color option constant, dashboard count query, and admin table CSS selector.
* Preserved legacy application statuses, saved data, and uninstall cleanup behavior.

= 0.4.3.2 =
* Removed the unused legacy inline frontend CSS method.
* Disabled new additional information request workflow actions and related email settings.
* Preserved existing needs-more-review statuses and request notes for backward-compatible display.

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
