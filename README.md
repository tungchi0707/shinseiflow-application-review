# ShinseiFlow

**Application Review & Approval Workflow for WordPress**

[English](README.md) | [日本語](README_ja.md) | [繁體中文](README_zh-Hant.md)

## Overview

ShinseiFlow is an open-source WordPress plugin for receiving applications, reviewing submissions, managing approval decisions, sending notifications, and providing approved downloads within your own WordPress site.

It is designed for organizations that need more than a simple contact form. ShinseiFlow supports the full application lifecycle, from submission and review to approval, rejection, additional information requests, resubmission, and applicant follow-up.

## Who is ShinseiFlow for?

ShinseiFlow is suitable for organizations and teams that want to manage structured application workflows directly in WordPress without relying on an external SaaS platform.

## Typical Use Cases

- Local government applications
- Grant and subsidy applications
- Scholarship applications
- Event registrations
- Volunteer recruitment
- Contest entries and open calls
- Internal approval requests
- Download eligibility reviews
- Any application process that requires human review

## Not Just a Form Builder

Traditional form plugins focus mainly on collecting submissions.

ShinseiFlow focuses on what happens after submission:

- Review applications in the WordPress admin
- Approve or reject applications
- Request additional information when needed
- Send workflow-based notification emails
- Let applicants check their current status and submitted content
- Allow revisions and resubmissions
- Provide downloadable files after approval
- Preserve application history and status changes

## Key Features

- Customizable application forms
- Built-in applicant name, email, and phone system fields
- Multiple custom field types
- Consent fields
- Section-based form organization
- Multi-step application flow
- Application review and approval workflow
- Approval and rejection management
- Additional information request workflow
- Applicant status lookup
- Revision and resubmission support
- Email notification templates
- Approved file downloads
- Cloudflare Turnstile support
- Built-in anti-spam and rate-limiting protections
- Multilingual application forms, Consent items, frontend labels, and messages
- AI-assisted translation for configurable form content
- Display customization options
- Privacy and data retention settings
- Application history and audit timeline

## Requirements

- WordPress 6.5 or later
- Tested up to WordPress 7.1
- PHP 8.0 or later
- License: GPL-2.0-or-later

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`, or install it through the WordPress admin.
2. Activate **ShinseiFlow – Application Review & Approval Workflow**.
3. Configure the application form, email templates, security settings, languages, and frontend pages.
4. Add the required shortcodes to your WordPress pages.

## Shortcodes

- `[tcarm_form]` — Application form
- `[tcarm_status]` — Application status lookup
- `[tcarm_view]` — Application details
- `[tcarm_edit]` — Revision and resubmission form

## Multilingual Support

ShinseiFlow includes its own multilingual configuration system for application forms, frontend labels, messages, Consent items, and other configurable content.

The plugin is also internationalized for the standard WordPress translation system.

Translation files such as `.po` and `.mo` are not bundled with the plugin package. When the plugin is published on WordPress.org, WordPress interface translations can be managed and distributed through [translate.wordpress.org](https://translate.wordpress.org/).

## Optional External Services

ShinseiFlow can integrate with optional external services when explicitly configured by an administrator.

### Cloudflare Turnstile

Cloudflare Turnstile can be enabled to provide bot protection for frontend workflows.

It is disabled by default and is only loaded and used after an administrator configures the required Turnstile settings.

### AI-assisted Translation

Optional AI-assisted translation is available for configurable form and interface content.

Supported integrations include OpenAI and Google Gemini. AI translation is only initiated by an administrator and is not used to process applicant submissions automatically.

For full information about external services, transmitted data, terms, and privacy policies, see the plugin's `readme.txt`.

## Documentation and Support

Project website, documentation, support information, and issue reporting:

https://labs.tungchi.jp/shinseiflow/

## Project Status

- Current version: **0.4.3.48**
- Currently under review for inclusion in the WordPress.org Plugin Directory
- Compatible with WordPress 6.5 and later
- Tested with WordPress 7.1
- Internationalization-ready for WordPress.org community translations
- Multilingual form, Consent, frontend label, and message configuration supported
- Optional Cloudflare Turnstile integration available
- Optional AI-assisted translation available for configurable content

ShinseiFlow is under active development. Until the WordPress.org review process is complete, GitHub represents the latest development and review-candidate source.

## Development Philosophy

ShinseiFlow is designed with long-term maintainability, usability, privacy, security, and WordPress compatibility in mind.

AI is used as a development assistant, while feature planning, architecture, testing, security review, usability decisions, and release quality are reviewed manually.

The goal is not simply to generate code, but to build a stable, maintainable, and practical WordPress plugin.

## Support the Project

ShinseiFlow is independently developed and maintained as an open-source project.

If ShinseiFlow has been helpful to you or your organization, you can support continued maintenance, documentation, WordPress compatibility updates, and future improvements:

https://labs.tungchi.jp/support-the-project/

Support is completely optional. ShinseiFlow remains free and open source.

## License

ShinseiFlow is licensed under the GPL-2.0-or-later license.

Copyright © Casper Yeh
