=== WordPair Replacer ===
Contributors: nickdesignz
Donate link: https://buymeacoffee.com/nickdesignz
Tags: text replace, word replace, typography, css effects, animation
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 2.3.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Replace words or phrases dynamically in the frontend and style replacements globally or individually with generated CSS.

== Description ==

WordPair Replacer allows site administrators to define word or phrase pairs and automatically replace matching frontend text output — without editing every post or page by hand. Replacements can use a global styling configuration or individual styling per word pair: typography, colors, gradients, borders, spacing and 25+ modern text effects and animations.

Typical use cases: consistently highlighting or linking recurring keywords, brand names or product names across a site; visually enhancing landing pages and marketing copy without touching page content; automating internal SEO linking for specific terms; and lightweight, performant frontend text adjustments without shortcodes or theme edits.

Frontend CSS is generated as a separate file in the uploads directory instead of inline styles, for better performance. Google Fonts are disabled by default and are only loaded when explicitly enabled in the plugin settings.

= Main features =

**Word replacement**

* Add, edit, activate, deactivate and delete an unlimited number of word or phrase pairs.
* Automatic replacement across the entire frontend output — posts, pages, widgets, theme output.
* Optional case-sensitive replacement.
* Optional whole-word-only replacement.
* Script, style, code, pre, SVG and textarea blocks are automatically skipped; specific areas can also be excluded via a `data-wpr-ignore` attribute or `wpr-ignore` CSS class.

**Styling & design**

* Global styling for all replaced words, plus individual per-word-pair styling that overrides the global style.
* Typography: font family (system fonts or optional Google Fonts), size, weight, line height, style, decoration, text transform, letter/word spacing, line wrapping.
* Colors: text color, background color, text gradient, background gradient (linear/radial, with angle/position), text shadow/glow.
* Border width (per side), style, color and radius (per corner); padding per side.
* Live preview directly in the admin, with a light/dark preview mode.
* WordPress color picker integration.

**Effects & animations**

* 25+ text effects: fade in, slide in (4 directions), zoom, bounce, pulse, shake, blur in, typing effect, glitch, neon glow, text shadow glow, gradient text, gradient animation, shimmer, stroke text, 3D text, hover highlight, reveal variants and more.
* Configurable animation duration, delay, infinite loop (for suitable effects) and easing curve.

**Link & SEO**

* Automatically link the replaced word to an internal page/post (built-in search) or a free-form URL.
* Target, rel attributes (nofollow, sponsored, noopener), title, ARIA label, custom link color and hover color.
* Automatic CSS class per word pair, plus an optional custom CSS class/ID and scoped custom CSS per word pair, and an additional global custom CSS field.

**Presets & import/export**

* 12 built-in visual presets as a starting point; save your own presets and apply them from the preset library.
* Export/import presets in the `.wprpreset` format to share styles between websites.
* Export all word pairs as JSON and import them on another site.

**Admin experience**

* Bilingual admin interface (English/German), switchable independently of the WordPress site language.
* Light/dark admin theme, AJAX-based interface with no page reloads.
* Built-in support ticket system (email delivery, local history, rate limiting, spam protection).
* Local compatibility check for outdated WordPress core/plugin/theme versions and common optimization plugins (no external data transmission).

= Compatibility =

Tested and confirmed working with Elementor, and tested and confirmed working without Elementor (classic editor / Gutenberg) — replacement operates on the rendered frontend output and is not tied to a specific page builder. Other page builders (e.g. Divi, Beaver Builder, Bricks, Oxygen) have not been tested yet; compatibility is likely given the plugin's approach, but not yet verified.

= Roadmap =

* External vulnerability checks: integration with WPVulnerability, Wordfence Intelligence, WPScan and Patchstack for optional external security scans. Currently marked "Coming soon" in the UI — no data is sent to these providers yet.
* Premium features: the free version is fully functional; additional features may follow as a premium extension later.
* Verified compatibility with more page builders beyond Elementor.

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

= Does WordPair Replacer work with Elementor? =

Yes. It has been tested and confirmed working with Elementor. It has also been tested and confirmed working without Elementor (classic editor / Gutenberg), since replacement happens on the rendered frontend output rather than inside a specific builder. Other page builders have not been tested yet.

= Is there a premium version? =

Not yet. The free version is fully functional. Additional features, along with optional external vulnerability-scan provider integrations (currently marked "Coming soon" in the Security Monitor settings), may be added as a premium extension in the future.

== Screenshots ==

1. Word pair management screen.
2. Individual styling controls for a saved word pair.
3. Global styling settings.
4. Text effect controls.
5. Frontend replacement example.

== Changelog ==

= 2.3.6 =
* Expanded the readme with a full feature overview, intended use cases, Elementor/page-builder compatibility notes and a roadmap section.
* The GitHub-facing README.md is now bilingual (German/English) with a project banner image.

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
