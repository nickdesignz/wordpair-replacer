=== WordPair Replacer ===
Contributors: nickdesignz
Donate link: https://buymeacoffee.com/nickdesignz
Tags: text replace, word replace, typography, css effects, animation
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 2.3.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Replace words or phrases dynamically in the frontend and style replacements globally or individually with generated CSS.

== Description ==

WordPair Replacer allows site administrators to define word or phrase pairs and automatically replace matching frontend text output. Replacements can use a global styling configuration or individual styling per word pair.

The plugin is designed for content highlighting, typography enhancements, visual emphasis, landing pages, marketing text and lightweight frontend text adjustments without manually editing every post or page.

Frontend CSS is generated as a separate file in the uploads directory. Google Fonts are disabled by default and are only loaded when explicitly enabled in the plugin settings.

= Main features =

* Add, edit, activate, deactivate and delete word pairs.
* Replace words and phrases automatically in frontend content.
* Optional case-sensitive replacement.
* Optional whole-word-only replacement.
* Global styling for all replaced words.
* Individual styling for each saved word pair.
* Generated frontend CSS file.
* Modern text effects and animations.
* WordPress color picker integration.
* Optional Google Fonts integration.
* Google Fonts are disabled by default.
* Backend language switcher for English and German.
* AJAX-based backend interface.
* Custom CSS field for advanced styling.

= Privacy =

WordPair Replacer does not track visitors and does not send plugin usage data to external services.

Google Fonts are disabled by default. If enabled by the administrator, Google Fonts may be loaded from Google servers in the frontend.

The Buy Me a Coffee support link is displayed only in the WordPress admin area and does not load external tracking scripts.

== Installation ==

1. Upload the `wordpair-replacer` folder to the `/wp-content/plugins/` directory or install the plugin ZIP file through the WordPress plugin screen.
2. Activate the plugin through the `Plugins` screen in WordPress.
3. Open `WordPair Replacer` in the WordPress admin menu.
4. Add your first word pair.
5. Adjust global styling or individual styling as needed.

== Frequently Asked Questions ==

= Does the plugin modify my database content? =

No. Replacements are performed dynamically during frontend output. The original post or page content is not changed.

= Can I style each replacement differently? =

Yes. Each saved word pair can use individual styling that overrides the global styling.

= Does the plugin load Google Fonts? =

Google Fonts are disabled by default. They are only loaded when explicitly enabled in the plugin settings.

== Screenshots ==

1. Word pair management screen.
2. Individual styling controls for a saved word pair.
3. Global styling settings.
4. Text effect controls.
5. Frontend replacement example.

== Changelog ==

= 2.3.5 =
* Content accuracy: the in-admin Changelog page had not been updated since 2.3.2 even though the plugin was already on a newer version. Added the missing 2.3.3/2.3.4 entries so the admin changelog and readme.txt changelog stay in sync.
* Corrected the Security Monitor privacy notice, which still implied external vulnerability data could be sent right now; the WPVulnerability/Wordfence/WPScan/Patchstack providers are "Coming soon" and never contacted, so the notice now says so explicitly.

= 2.3.4 =
* Fixed a layout bug where the word-pair style editor's tab rail, form fields and live preview could visually overlap and become unreadable at common desktop widths (up to ~1920px), caused by responsive rules whose selectors no longer matched after an earlier layout refactor. The tab rail now compacts to icons and the live preview stacks below the form on narrower windows.
* Fixed "Save word pair" and "Send ticket" (and other primary action buttons) losing their purple styling due to conflicting CSS resets accumulated across several plugin versions.
* Fixed the "Dark Luxury" preset preview swatch rendering invisible (dark-on-dark) text.
* Completed remaining German/English admin UI strings that were hardcoded in JavaScript and did not follow the plugin language switch (word-pair filter counts, editor action buttons, import/duplicate messages).

= 2.3.3 =
* Security hardening: the WPVulnerability, Wordfence, Patchstack and WPScan security-scan providers are not yet connected to any external service. Their toggles and API key fields are now locked as "Coming soon" in the UI, and the backend no longer accepts or stores values for them, so no unused API key can end up in the database. Existing installs are cleaned up automatically on upgrade.
* Completed missing German/English admin UI translations.

= 2.1.2 =
* Finalized support ticket ID format, English notification emails, sender copy, rate limiting and privacy-safe diagnostics.


= 2.0.0 - 2026-05-21 =
* Introduced the V2 premium workbench with plugin navigation, cleaner word-pair browser and Link & SEO controls.
* Added per-word-pair linking, SEO rel attributes, target handling, link colors, custom CSS class and custom CSS ID fields.
* Added CSS maintenance, support, changelog and license anchors in the plugin navigation.

= 1.8.0 =
* Polished premium admin UI spacing, wider word-pair browser, improved light/dark contrast, better editor padding and cleaner button states.

= 1.7.9 - 2026-05-21 =
* UI fix release: wider wordpair browser, real vertical inspector tabs, improved light/dark contrast, cleaner previews and full-width admin layout.

= 1.7.7 - 2026-05-21 =
* Added premium dark workbench UI with left word-pair browser and right inspector editor.
* Added search, selected pair state, docked editor and improved CSS maintenance layout.

= 1.7.5 - 2026-05-21 =
* Added typography controls for font style, text decoration, text transform, letter spacing, word spacing and white-space.
* Added background gradients and per-side border width controls.
* Improved animation timing, loop handling and compact admin UI spacing.

= 1.7.4 - 2026-05-18 =
Improved the color controls, fixed background color behavior in preview/frontend styling, added linear/radial gradient options with angle/position controls and enhanced accordion indicators.

= 1.7.3 - 2026-05-18 =
Improved backend branding, added gradient text controls, text shadow controls, section reset buttons, linked padding/radius fields and a dismissible support card.

= 1.7.2 - 2026-05-18 =
Added true live backend preview with light/dark preview mode. Typography values now inherit theme styles by default and only output font-size, font-weight and line-height when explicitly configured.

= 1.7.0 - 2026-05-17 =
Restored the last known stable frontend code path and kept WordPress.org metadata and English readme.

= 1.6.2 - 2026-05-17 =
Improved Buy Me a Coffee support card image sizing.

= 1.6.1 - 2026-05-17 =
Added a backend Buy Me a Coffee support card.

= 1.6.0 - 2026-05-17 =
Fixed global styling color picker layout in the right sidebar.

= 1.5.9 - 2026-05-17 =
Limited the backend changelog to the latest three updates while keeping the full changelog in the readme file.

= 1.0.0 - 2026-05-17 =
Initial release with custom database table, backend management, AJAX handling and frontend replacement.

== Upgrade Notice ==

= 1.7.0 =
Restored stable frontend behavior.
