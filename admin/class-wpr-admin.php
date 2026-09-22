<?php
/**
 * Admin UI and AJAX.
 *
 * @package WordPairReplacer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPR_Admin {

	private string $page_hook = '';
	private array $page_hooks = array();

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_head', array( $this, 'print_early_admin_theme_script' ), 1 );

		add_action( 'wp_ajax_wpr_get_pairs', array( $this, 'ajax_get_pairs' ) );
		add_action( 'wp_ajax_wpr_save_pair', array( $this, 'ajax_save_pair' ) );
		add_action( 'wp_ajax_wpr_delete_pair', array( $this, 'ajax_delete_pair' ) );
		add_action( 'wp_ajax_wpr_toggle_pair', array( $this, 'ajax_toggle_pair' ) );
		add_action( 'wp_ajax_wpr_save_pair_style', array( $this, 'ajax_save_pair_style' ) );
		add_action( 'wp_ajax_wpr_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_wpr_save_language', array( $this, 'ajax_save_language' ) );
		add_action( 'wp_ajax_wpr_save_google_fonts', array( $this, 'ajax_save_google_fonts' ) );
		add_action( 'wp_ajax_wpr_run_security_scan', array( $this, 'ajax_run_security_scan' ) );
		add_action( 'wp_ajax_wpr_regenerate_css', array( $this, 'ajax_regenerate_css' ) );
		add_action( 'wp_ajax_wpr_submit_support_ticket', array( $this, 'ajax_submit_support_ticket' ) );
		add_action( 'wp_ajax_wpr_update_support_ticket_status', array( $this, 'ajax_update_support_ticket_status' ) );
		add_action( 'wp_ajax_wpr_get_presets', array( $this, 'ajax_get_presets' ) );
		add_action( 'wp_ajax_wpr_save_preset', array( $this, 'ajax_save_preset' ) );
		add_action( 'wp_ajax_wpr_import_preset', array( $this, 'ajax_import_preset' ) );
		add_action( 'wp_ajax_wpr_delete_preset', array( $this, 'ajax_delete_preset' ) );
	}

	public function register_menu(): void {
		$this->page_hook = add_menu_page(
			wpr_t( 'WordPair Replacer' ),
			wpr_t( 'WordPair Replacer' ),
			'manage_options',
			'wordpair-replacer',
			array( $this, 'render_page' ),
			'dashicons-editor-spellcheck',
			80
		);

		$this->page_hooks[] = $this->page_hook;
		$this->page_hooks[] = add_submenu_page(
			'wordpair-replacer',
			wpr_t( 'Dashboard' ),
			wpr_t( 'Dashboard' ),
			'manage_options',
			'wordpair-replacer',
			array( $this, 'render_page' )
		);
		$this->page_hooks[] = add_submenu_page(
			'wordpair-replacer',
			wpr_t( 'Einstellungen' ),
			wpr_t( 'Einstellungen' ),
			'manage_options',
			'wordpair-replacer-settings',
			array( $this, 'render_page' )
		);
		$this->page_hooks[] = add_submenu_page(
			'wordpair-replacer',
			wpr_t( 'Import / Export' ),
			wpr_t( 'Import / Export' ),
			'manage_options',
			'wordpair-replacer-import-export',
			array( $this, 'render_page' )
		);
		$this->page_hooks[] = add_submenu_page(
			'wordpair-replacer',
			wpr_t( 'Documentation' ),
			wpr_t( 'Documentation' ),
			'manage_options',
			'wordpair-replacer-documentation',
			array( $this, 'render_page' )
		);
		$this->page_hooks[] = add_submenu_page(
			'wordpair-replacer',
			wpr_t( 'Support' ),
			wpr_t( 'Support' ),
			'manage_options',
			'wordpair-replacer-support',
			array( $this, 'render_page' )
		);
		$this->page_hooks[] = add_submenu_page(
			'wordpair-replacer',
			wpr_t( 'Changelog' ),
			wpr_t( 'Changelog' ),
			'manage_options',
			'wordpair-replacer-changelog',
			array( $this, 'render_page' )
		);
		$this->page_hooks[] = add_submenu_page(
			'wordpair-replacer',
			wpr_t( 'Lizenz' ),
			wpr_t( 'Lizenz' ),
			'manage_options',
			'wordpair-replacer-license',
			array( $this, 'render_page' )
		);
	}

	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, $this->page_hooks, true ) ) {
			return;
		}

		$settings = WPR_Settings::get();

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_style(
			'wpr-admin',
			WPR_PLUGIN_URL . 'assets/admin/css/wpr-admin.css',
			array( 'wp-color-picker' ),
			WPR_VERSION
		);

		wp_enqueue_script(
			'wpr-admin',
			WPR_PLUGIN_URL . 'assets/admin/js/wpr-admin.js',
			array( 'jquery', 'wp-color-picker' ),
			WPR_VERSION,
			true
		);

		wp_localize_script(
			'wpr-admin',
			'wprAdmin',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'wpr_admin_nonce' ),
				'pluginLanguage' => $settings['plugin_language'] ?? 'en_US',
				'version' => WPR_VERSION,
				'frontendUrl' => home_url( '/' ),
				'i18n'    => array(
					'loading'       => wpr_t( 'Lade Daten...' ),
					'saved'         => wpr_t( 'Gespeichert.' ),
					'deleted'       => wpr_t( 'Gelöscht.' ),
					'confirmDelete' => wpr_t( 'Diesen Eintrag wirklich löschen?' ),
					'error'         => wpr_t( 'Es ist ein Fehler aufgetreten.' ),
					'empty'         => wpr_t( 'Noch keine Wortpaare vorhanden.' ),
					'reset'         => wpr_t( 'Zurücksetzen' ),
					'saveStyle'     => wpr_t( 'Style speichern' ),
					'lastChange'    => wpr_t( 'Letzte Änderung:' ),
					'neverSaved'    => wpr_t( 'Noch nicht gespeichert' ),
					'selectedPair'  => wpr_t( 'Ausgewähltes Wortpaar' ),
					'livePreview'   => wpr_t( 'Live Vorschau' ),
					'frontendPreviewHelp' => wpr_t( 'So wird das Wortpaar im Frontend dargestellt.' ),
					'automaticClass' => wpr_t( 'Automatische Klasse' ),
					'customClass'   => wpr_t( 'Eigene Klasse' ),
					'customId'      => wpr_t( 'Eigene ID' ),
					'supportSent'   => wpr_t( 'Support request sent.' ),
					'supportSending'=> wpr_t( 'Sending support request...' ),
					'applyPreset'   => wpr_t( 'Apply preset' ),
					'loadPreset'    => wpr_t( 'Load preset' ),
					'saveAsPreset'  => wpr_t( 'Save as preset' ),
					'presetName'    => wpr_t( 'Preset name' ),
					'presetSaved'   => wpr_t( 'Preset saved.' ),
					'presetApplied' => wpr_t( 'Preset applied.' ),
					'noPreset'      => wpr_t( 'No preset selected.' ),
					'googleFontsSaved' => wpr_t( 'Google Fonts setting saved.' ),
					'googleFontsEnabled' => wpr_t( 'Google Fonts enabled.' ),
					'googleFontsDisabled' => wpr_t( 'Google Fonts disabled.' ),
					'saving' => wpr_t( 'Saving...' ),
					'scanning' => wpr_t( 'Scanning...' ),
				),
			)
		);
	}


	/**
	 * Prints a tiny early theme bootstrap to prevent a light-mode flash before the admin JS loads.
	 */
	public function print_early_admin_theme_script(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || empty( $screen->id ) || false === strpos( (string) $screen->id, 'wordpair-replacer' ) ) {
			return;
		}
		?>
		<script>
			(function(){
				try {
					var theme = window.localStorage ? window.localStorage.getItem('wprAdminTheme') : '';
					if (theme === 'dark') {
						document.documentElement.classList.add('wpr-admin-dark');
						document.documentElement.style.backgroundColor = '#06111f';
					}
				} catch (e) {}
			}());
		</script>
		<?php
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html( wpr_t( 'Keine Berechtigung.' ) ) );
		}

		$settings = WPR_Settings::get();
		$page     = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'wordpair-replacer';

		if ( 'wordpair-replacer' !== $page ) {
			$this->render_secondary_page( $page, $settings );
			return;
		}
		?>
		<div class="wrap wpr-wrap">
			<div class="wpr-brand-header">
				<div class="wpr-brand-left">
					<div class="wpr-brand-logo" aria-hidden="true">
						<img src="<?php echo esc_url( WPR_PLUGIN_URL . 'assets/icon-128x128.png' ); ?>" alt="">
					</div>
					<div>
						<h1>WordPair <span>Replacer</span> <mark class="wpr-version-badge"><?php echo esc_html( WPR_VERSION ); ?></mark></h1>
						<p><?php echo esc_html( wpr_t( 'Ersetze Wörter oder Wortpaare automatisch im gesamten Frontend – mit individuellem Styling und Live-Vorschau.' ) ); ?></p>
					</div>
				</div>
				<div class="wpr-top-actions">
					<label class="wpr-theme-switch" title="Light / Dark Mode">
						<span>Light</span>
						<input type="checkbox" id="wpr-admin-theme-toggle">
						<i aria-hidden="true"></i>
						<span>Dark</span>
					</label>
					<label class="wpr-language-switch" title="English / Deutsch">
						<span>EN</span>
						<input type="checkbox" class="wpr-language-toggle" <?php checked( $settings['plugin_language'], 'de_DE' ); ?>>
						<i aria-hidden="true"></i>
						<span>DE</span>
					</label>
					<a class="button wpr-frontend-open" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( wpr_t( 'Frontend öffnen' ) ); ?> <span class="dashicons dashicons-external" aria-hidden="true"></span></a>
				</div>
			</div>

			<nav class="wpr-plugin-nav" aria-label="WordPair Replacer">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wordpair-replacer' ) ); ?>" class="is-active"><span class="dashicons dashicons-dashboard" aria-hidden="true"></span><?php echo esc_html( wpr_t( 'Dashboard' ) ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wordpair-replacer-settings' ) ); ?>"><span class="dashicons dashicons-admin-generic" aria-hidden="true"></span><?php echo esc_html( wpr_t( 'Einstellungen' ) ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wordpair-replacer-import-export' ) ); ?>"><span class="dashicons dashicons-database-export" aria-hidden="true"></span><?php echo esc_html( wpr_t( 'Import / Export' ) ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wordpair-replacer-documentation' ) ); ?>"><span class="dashicons dashicons-media-document" aria-hidden="true"></span><?php echo esc_html( wpr_t( 'Documentation' ) ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wordpair-replacer-support' ) ); ?>"><span class="dashicons dashicons-sos" aria-hidden="true"></span><?php echo esc_html( wpr_t( 'Support' ) ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wordpair-replacer-changelog' ) ); ?>"><span class="dashicons dashicons-list-view" aria-hidden="true"></span><?php echo esc_html( wpr_t( 'Changelog' ) ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wordpair-replacer-license' ) ); ?>"><span class="dashicons dashicons-shield" aria-hidden="true"></span><?php echo esc_html( wpr_t( 'Lizenz' ) ); ?></a>
			</nav>

			<div class="wpr-layout" id="wpr-dashboard">
				<main class="wpr-main">
					<section class="wpr-card">
						<div class="wpr-card-header">
							<h2><span class="wpr-heading-icon">＋</span><?php echo esc_html( wpr_t( 'Wortpaar hinzufügen' ) ); ?></h2>
							<p><?php echo esc_html( wpr_t( 'Individuelles Styling wird direkt beim jeweiligen gespeicherten Wortpaar bearbeitet.' ) ); ?></p>
						</div>

						<form id="wpr-form" class="wpr-form">
							<input type="hidden" id="wpr-id" value="0">

							<div class="wpr-control-grid two">
								<label class="wpr-control" for="wpr-original-word">
									<span><?php echo esc_html( wpr_t( 'Originalwort' ) ); ?></span>
									<input type="text" id="wpr-original-word" maxlength="255" required>
								</label>

								<label class="wpr-control" for="wpr-replacement-word">
									<span><?php echo esc_html( wpr_t( 'Ersatzwort' ) ); ?></span>
									<input type="text" id="wpr-replacement-word" maxlength="255" required>
								</label>
							</div>

							<div class="wpr-switch-grid">
								<label><input type="checkbox" id="wpr-is-active" checked> <span><?php echo esc_html( wpr_t( 'Aktiv' ) ); ?></span></label>
								<label><input type="checkbox" id="wpr-case-sensitive"> <span><?php echo esc_html( wpr_t( 'Groß-/Kleinschreibung beachten' ) ); ?></span></label>
								<label><input type="checkbox" id="wpr-whole-word" checked> <span><?php echo esc_html( wpr_t( 'Nur ganze Wörter ersetzen' ) ); ?></span></label>
							</div>

							<div class="wpr-actions">
								<button type="submit" class="button button-primary wpr-primary"><?php echo esc_html( wpr_t( 'Wortpaar speichern' ) ); ?></button>
								<button type="button" class="button" id="wpr-reset"><?php echo esc_html( wpr_t( 'Zurücksetzen' ) ); ?></button>
							</div>
						</form>
					</section>

					<section class="wpr-card wpr-table-card wpr-workbench-card">
						<div class="wpr-card-header wpr-workbench-header">
							<div>
								<h2><span class="wpr-heading-icon">☷</span><?php echo esc_html( wpr_t( 'Wortpaare' ) ); ?></h2>
								<p><?php echo esc_html( wpr_t( 'Wähle links ein Wortpaar und bearbeite rechts das Styling wie in einem Inspector.' ) ); ?></p>
							</div>
							<button type="button" class="button button-primary wpr-primary" id="wpr-scroll-add">＋ <?php echo esc_html( wpr_t( 'Neu hinzufügen' ) ); ?></button>
						</div>

						<div id="wpr-message" class="wpr-message" aria-live="polite"></div>

						<div class="wpr-workbench">
							<div class="wpr-wordpair-browser">
								<div class="wpr-search-row">
									<input type="search" id="wpr-pair-search" placeholder="<?php echo esc_attr( wpr_t( 'Wortpaar suchen...' ) ); ?>">
								</div>
								<div class="wpr-browser-tabs" aria-label="Wortpaare filtern">
									<button type="button" class="wpr-browser-filter is-active" data-filter="all"><?php echo esc_html( wpr_t( 'Alle' ) ); ?></button>
									<button type="button" class="wpr-browser-filter" data-filter="active"><?php echo esc_html( wpr_t( 'Aktiv' ) ); ?></button>
									<button type="button" class="wpr-browser-filter" data-filter="inactive"><?php echo esc_html( wpr_t( 'Inaktiv' ) ); ?></button>
									<button type="button" class="wpr-browser-filter" data-filter="favorites"><?php echo esc_html( wpr_t( 'Favoriten' ) ); ?></button>
								</div>
								<div class="wpr-pairs-list" id="wpr-pairs-list"><?php echo wp_kses_post( $this->render_pairs_fallback_html() ); ?></div><div class="wpr-pair-count" id="wpr-pair-total-count">0 Einträge</div>
							</div>

							<div class="wpr-editor-dock" id="wpr-editor-dock">
								<div class="wpr-editor-empty">
									<span class="wpr-editor-empty-icon">✦</span>
									<h3><?php echo esc_html( wpr_t( 'Wortpaar auswählen' ) ); ?></h3>
									<p><?php echo esc_html( wpr_t( 'Klicke links auf Styling, um Typografie, Farben, Effekte, Abstände und Rahmen hier zentral zu bearbeiten.' ) ); ?></p>
								</div>
							</div>
						</div>
					</section>
				</main>

				<aside class="wpr-sidebar">

					<section class="wpr-card wpr-support-card" id="wpr-support-card">
						<button type="button" class="wpr-support-close" aria-label="<?php echo esc_attr( wpr_t( 'Hinweis schließen' ) ); ?>">×</button>
						<a
							href="https://buymeacoffee.com/nickdesignz"
							target="_blank"
							rel="noopener noreferrer"
							class="wpr-support-link"
							aria-label="Buy me a coffee"
						>
							<span class="wpr-support-image-wrap">
								<img
									src="<?php echo esc_url( set_url_scheme( 'http://beta.nickdesignz.de/wp-content/uploads/2026/05/buymyacoffee-nickdesignz.png', 'https' ) ); ?>"
									alt="Buy me a coffee - NickDesignz"
									loading="lazy"
								>
							</span>
						</a>
						<div class="wpr-support-body">
							<strong><?php echo esc_html( wpr_t( 'Support the developer' ) ); ?></strong>
							<p><?php echo esc_html( wpr_t( 'If this plugin helps you, you can support future development with a coffee.' ) ); ?></p>
							<div class="wpr-support-quicklinks" aria-label="<?php echo esc_attr( wpr_t( 'Schnellzugriff' ) ); ?>">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wordpair-replacer-support' ) ); ?>">↗ <?php echo esc_html( wpr_t( 'Support' ) ); ?></a>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wordpair-replacer-settings' ) ); ?>">⚙ <?php echo esc_html( wpr_t( 'Einstellungen' ) ); ?></a>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wordpair-replacer-changelog' ) ); ?>">☷ <?php echo esc_html( wpr_t( 'Changelog' ) ); ?></a>
							</div>
						</div>
					</section>

					<section class="wpr-card wpr-info-card">
						<h2><span class="wpr-heading-icon">ⓘ</span><?php echo esc_html( wpr_t( 'Hinweis' ) ); ?></h2>

						<div class="wpr-language-form">
							<strong><?php echo esc_html( wpr_t( 'Plugin-Sprache' ) ); ?></strong>
							<div class="wpr-language-options">
								<?php foreach ( WPR_Settings::allowed_plugin_languages() as $locale => $label ) : ?>
									<label>
										<input type="radio" name="plugin_language" value="<?php echo esc_attr( $locale ); ?>" <?php checked( $settings['plugin_language'], $locale ); ?>>
										<span><?php echo esc_html( $label ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
							<p class="description"><?php echo esc_html( wpr_t( 'Englisch ist standardmäßig aktiv. Die Sprache wird nach dem Speichern neu geladen.' ) ); ?></p>
						</div>

						<p><?php echo esc_html( wpr_t( 'Aktive Wortpaare werden automatisch im Frontend ersetzt. Script-, Style-, Code-, SVG- und Textarea-Bereiche werden ausgelassen.' ) ); ?></p>
						<p><?php echo esc_html( wpr_t( 'Google Fonts sind aus Datenschutzgründen standardmäßig deaktiviert und werden im Frontend nur geladen, wenn sie aktiv eingeschaltet wurden.' ) ); ?></p>
					</section>

					<section class="wpr-card wpr-global-style-card">
						<details class="wpr-panel">
							<summary>
								<span class="wpr-sidebar-summary-title"><span class="wpr-heading-icon">⚙</span><span><?php echo esc_html( wpr_t( 'Allgemeines Styling' ) ); ?><small><?php echo esc_html( wpr_t( 'Für alle ersetzten Texte' ) ); ?></small></span></span>
								<span class="wpr-panel-chevron dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
							</summary>

							<form id="wpr-settings-form">
								<label class="wpr-switch-row">
									<input type="checkbox" id="wpr-enable-google-fonts" <?php checked( (int) $settings['enable_google_fonts'], 1 ); ?>>
									<span><?php echo esc_html( wpr_t( 'Google Fonts aktivieren' ) ); ?></span>
								</label>

								<p class="description"><?php echo esc_html( wpr_t( 'Wenn deaktiviert, werden nur Systemschriften angezeigt und im Frontend keine externen Google-Font-Dateien geladen.' ) ); ?></p>

								<?php $this->render_style_controls( 'global', $settings['global_style'] ); ?>

								<label class="wpr-control wpr-css-control" for="wpr-custom-css">
									<span><?php echo esc_html( wpr_t( 'Eigenes CSS' ) ); ?></span>
									<textarea id="wpr-custom-css" rows="8" spellcheck="false" placeholder=".wpr-replaced { text-transform: uppercase; }"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
								</label>

								<div class="wpr-actions">
									<button type="submit" class="button button-primary wpr-primary"><?php echo esc_html( wpr_t( 'Einstellungen speichern' ) ); ?></button>
								</div>
							</form>
						</details>
					</section>

					<section class="wpr-card wpr-preview-card" id="wpr-preview-card">
						<div class="wpr-preview-head">
							<div>
								<h2><span class="wpr-heading-icon">◉</span><?php echo esc_html( wpr_t( 'Live-Vorschau' ) ); ?></h2>
								<p><?php echo esc_html( wpr_t( 'Zeigt das aktuell ausgewählte Wortpaar oder das globale Styling.' ) ); ?></p>
							</div>
							<div class="wpr-preview-actions">
								<button type="button" class="button wpr-preview-theme is-active" data-preview-theme="light"><?php echo esc_html( wpr_t( 'Hell' ) ); ?></button>
								<button type="button" class="button wpr-preview-theme" data-preview-theme="dark"><?php echo esc_html( wpr_t( 'Dunkel' ) ); ?></button>
							</div>
						</div>

						<div class="wpr-preview-body is-light" id="wpr-live-preview">
							<div class="wpr-preview-status">
								<span class="wpr-preview-dot"></span>
								<strong id="wpr-preview-mode"><?php echo esc_html( wpr_t( 'Global Styling' ) ); ?></strong>
							</div>

							<div class="wpr-preview-canvas">
								<p class="wpr-preview-label"><?php echo esc_html( wpr_t( 'Vorschau' ) ); ?></p>
								<p class="wpr-preview-text" id="wpr-preview-text">
									<?php echo esc_html( wpr_t( 'Dies ist ein Beispieltext mit' ) ); ?>
									<span id="wpr-preview-word" class="wpr-preview-word">Keyword</span>
									<?php echo esc_html( wpr_t( 'für deine Webseite.' ) ); ?>
								</p>
							</div>

							<div class="wpr-preview-meta">
								<span id="wpr-preview-pair">Keyword → Replacement</span>
								<button type="button" class="button" id="wpr-preview-replay"><?php echo esc_html( wpr_t( 'Animation neu starten' ) ); ?></button>
							</div>
						</div>
					</section>

					<section class="wpr-card wpr-maintenance-card" id="wpr-css-maintenance-card">
						<details class="wpr-panel">
							<summary>
								<span class="wpr-sidebar-summary-title">
									<span class="wpr-heading-icon">⚠</span>
									<span>
										<?php echo esc_html( wpr_t( 'CSS Maintenance' ) ); ?>
										<small><?php echo esc_html( wpr_t( 'Frontend-CSS neu erstellen' ) ); ?></small>
									</span>
								</span>

								<span class="wpr-panel-chevron dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
							</summary>

							<div class="wpr-maintenance-body">
								<p><?php echo esc_html( wpr_t( 'Nutze diese Funktion nur, wenn die dynamische Frontend-CSS fehlt, veraltet ist oder nach Änderungen nicht korrekt geladen wird.' ) ); ?></p>

								<button type="button" class="button" id="wpr-regenerate-css">
									<?php echo esc_html( wpr_t( 'CSS neu generieren' ) ); ?>
								</button>

								<div id="wpr-css-regenerate-result" class="wpr-css-regenerate-result" aria-live="polite"></div>
							</div>
						</details>
					</section>				

					<section class="wpr-card wpr-changelog-card" id="wpr-changelog-card">
						<details class="wpr-panel">
								<summary>
									<span class="wpr-sidebar-summary-title">
										<span class="wpr-heading-icon">☰</span>

										<span>
											<?php echo esc_html( wpr_t( 'Changelog' ) ); ?>

											<small>1.0.0 – 2.0.6</small>
										</span>
									</span>

									<span class="wpr-panel-chevron dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
								</summary>
							<div class="wpr-changelog">
								<?php echo wp_kses_post( $this->render_changelog_html() ); ?>
							</div>
						</details>
					</section>
					<section class="wpr-card wpr-license-card" id="wpr-license-card">
						<h2><span class="wpr-heading-icon">♢</span><?php echo esc_html( wpr_t( 'Lizenz' ) ); ?></h2>
						<p><?php echo esc_html( wpr_t( 'Kostenlose Version aktiv. Premium-Funktionen können später freigeschaltet werden.' ) ); ?></p>
					</section>
				</aside>
			</div>

			<script type="text/html" id="tmpl-wpr-style-controls">
				<?php $this->render_style_controls( '__SCOPE__' ); ?>
			</script>
		</div>
		<?php
	}



	private function render_secondary_hero( string $kicker, string $title, string $description, string $button_label = '', string $button_url = '' ): string {
		$button = '';
		if ( '' !== $button_label && '' !== $button_url ) {
			$button = '<a class="button button-primary wpr-primary" href="' . esc_url( $button_url ) . '">' . esc_html( $button_label ) . '</a>';
		}

		return '<section class="wpr-card wpr-secondary-hero"><div><p class="wpr-kicker">' . esc_html( $kicker ) . '</p><h2>' . esc_html( $title ) . '</h2><p>' . esc_html( $description ) . '</p></div>' . $button . '</section>';
	}

	private function render_secondary_page( string $page, array $settings ): void {
		$title = wpr_t( 'Dashboard' );
		$body  = '';

		if ( 'wordpair-replacer-css-maintenance' === $page ) {
			$title = wpr_t( 'CSS Maintenance' );
			$paths = class_exists( 'WPR_CSS' ) ? WPR_CSS::upload_dir() : array( 'href' => '', 'file' => '' );
			$body  = '<section class="wpr-card wpr-secondary-card"><h2>' . esc_html( $title ) . '</h2>';
			$body .= '<p>' . esc_html( wpr_t( 'Nutze diese Funktion nur, wenn die dynamische Frontend-CSS fehlt, veraltet ist oder nach Änderungen nicht korrekt geladen wird.' ) ) . '</p>';
			$body .= '<button type="button" class="button button-primary wpr-primary" id="wpr-regenerate-css">' . esc_html( wpr_t( 'CSS neu generieren' ) ) . '</button>';
			$body .= '<div id="wpr-css-regenerate-result" class="wpr-css-regenerate-result" aria-live="polite"></div>';
			if ( ! empty( $paths['href'] ) ) {
				$body .= '<p class="description"><a href="' . esc_url( $paths['href'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( wpr_t( 'CSS-Datei ansehen' ) ) . '</a></p>';
			}
			$body .= '</section>';
		} elseif ( 'wordpair-replacer-import-export' === $page ) {
			$title = wpr_t( 'Import / Export' );
			$body  = $this->render_secondary_hero(
				'WORDPAIR REPLACER',
				$title,
				wpr_t( 'Export word pairs, transfer configurations and reuse visual presets across multiple websites.' )
			);
			$body .= '<section class="wpr-card wpr-secondary-card"><h2>' . esc_html( wpr_t( 'Word pair transfer' ) ) . '</h2>';
			$body .= '<p>' . esc_html( wpr_t( 'Export all word pairs as JSON or import previously exported data.' ) ) . '</p>';
			$body .= '<div class="wpr-secondary-actions"><button type="button" class="button button-primary wpr-primary" id="wpr-export-all-pairs">' . esc_html( wpr_t( 'Export all word pairs' ) ) . '</button></div>';
			$body .= '<label class="wpr-control wpr-code-control"><span>' . esc_html( wpr_t( 'Import JSON' ) ) . '</span><textarea id="wpr-import-json" rows="10" spellcheck="false" placeholder="{&quot;items&quot;:[...]}" ></textarea></label>';
			$body .= '<div class="wpr-secondary-actions"><button type="button" class="button wpr-primary" id="wpr-import-json-button">' . esc_html( wpr_t( 'Start import' ) ) . '</button></div><div id="wpr-message" class="wpr-message" aria-live="polite"></div>';
			$body .= '</section>';
						$body .= '<section class="wpr-card wpr-secondary-card wpr-preset-manager-card"><h2>' . esc_html( wpr_t( 'Preset Library' ) ) . '</h2><p>' . esc_html( wpr_t( 'Save favorite visual setups as reusable presets. Presets can later be shared with the .wprpreset format.' ) ) . '</p><div id="wpr-preset-library-dynamic" class="wpr-preset-library"><div class="wpr-empty">' . esc_html( wpr_t( 'Loading presets...' ) ) . '</div></div><div class="wpr-preset-import-box"><label class="wpr-control wpr-code-control"><span>' . esc_html( wpr_t( 'Import preset JSON' ) ) . '</span><textarea id="wpr-preset-import-json" rows="8" spellcheck="false" placeholder="{&quot;preset&quot;:{...}}"></textarea></label><div class="wpr-secondary-actions"><button type="button" class="button button-primary wpr-primary" id="wpr-import-preset-button">' . esc_html( wpr_t( 'Import preset' ) ) . '</button></div><div id="wpr-preset-import-result" class="wpr-css-regenerate-result" aria-live="polite"></div></div></section>';
		} elseif ( 'wordpair-replacer-documentation' === $page ) {
			$title = wpr_t( 'Documentation' );
			$body  = $this->render_secondary_hero( 'WORDPAIR REPLACER', wpr_t( 'Documentation & FAQ' ), wpr_t( 'Learn how to create word pairs, style keywords, use presets, configure SEO links and troubleshoot common issues.' ) );
			$body .= '<section class="wpr-card wpr-secondary-card wpr-doc-page"><h2>' . esc_html( wpr_t( 'Documentation' ) ) . '</h2><p>' . esc_html( wpr_t( 'Open a topic to read the full explanation.' ) ) . '</p><div class="wpr-doc-accordion-list"><details class="wpr-doc-accordion"><summary><strong>Getting Started</strong><span>+</span></summary><div><p>Create your first word pair, choose the replacement text, save it and select it in the list to open the visual editor. Active word pairs are replaced on the frontend automatically.</p></div></details><details class="wpr-doc-accordion"><summary><strong>Dashboard & Workbench</strong><span>+</span></summary><div><p>The dashboard is a master-detail editor: select a word pair on the left, edit typography, colors, spacing, border, effects, CSS and Link & SEO options in the center, then preview the result on the right.</p></div></details><details class="wpr-doc-accordion"><summary><strong>Typography</strong><span>+</span></summary><div><p>Control font family, size, line height, weight, style, decoration, transform, letter spacing, word spacing and line wrapping.</p></div></details><details class="wpr-doc-accordion"><summary><strong>Colors & Gradients</strong><span>+</span></summary><div><p>Set text color, background color, text gradients, background gradients and shadows. Use the compact color swatches to keep the editor layout stable.</p></div></details><details class="wpr-doc-accordion"><summary><strong>Effects</strong><span>+</span></summary><div><p>Use animations such as pulse, glow, shimmer, blur or gradient motion. Looping is only applied to effects that are suitable for continuous animation.</p></div></details><details class="wpr-doc-accordion"><summary><strong>Link & SEO</strong><span>+</span></summary><div><p>Add internal links, title attributes, ARIA labels and rel options such as nofollow, sponsored and noopener. Use internal links carefully to avoid over-linking.</p></div></details><details class="wpr-doc-accordion"><summary><strong>Custom CSS</strong><span>+</span></summary><div><p>Each word pair has an automatic CSS class. You can add your own class, ID and scoped CSS for advanced styling without touching your theme files.</p></div></details><details class="wpr-doc-accordion"><summary><strong>Presets</strong><span>+</span></summary><div><p>Save visual styles as presets, reuse them for new word pairs and later transfer them between websites using the .wprpreset format.</p></div></details><details class="wpr-doc-accordion"><summary><strong>Import / Export</strong><span>+</span></summary><div><p>Export your word pairs before large changes. Import JSON on another site to transfer settings, or use presets for reusable visual designs.</p></div></details><details class="wpr-doc-accordion"><summary><strong>CSS Maintenance</strong><span>+</span></summary><div><p>Regenerate the dynamic frontend CSS if changes are not visible, the generated CSS file is missing or a cache/minification plugin serves an old version.</p></div></details><details class="wpr-doc-accordion"><summary><strong>Support Tickets</strong><span>+</span></summary><div><p>Support tickets are stored locally in your WordPress installation. Replies are sent by email, so the local history only shows requests created from this website.</p></div></details><details class="wpr-doc-accordion"><summary><strong>Troubleshooting</strong><span>+</span></summary><div><p>If output looks wrong, clear cache plugins, regenerate CSS, check Elementor widgets, use ignore classes for protected areas and verify that your theme does not override the generated CSS.</p></div></details></div></section>';
		} elseif ( 'wordpair-replacer-changelog' === $page ) {
			$title = wpr_t( 'Changelog' );
			$body  = $this->render_secondary_hero( 'WORDPAIR REPLACER', $title, wpr_t( 'Track product improvements, UI refinements, stability fixes and new features.' ) );
			$body .= '<section class="wpr-card wpr-secondary-card"><h2>' . esc_html( $title ) . '</h2><div class="wpr-changelog">' . wp_kses_post( $this->render_changelog_html() ) . '</div></section>';
		} elseif ( 'wordpair-replacer-support' === $page ) {
			$title = wpr_t( 'Support & Ressourcen' );
			$diagnostics = $this->collect_support_diagnostics();
			$conflicts   = $this->detect_plugin_conflicts();
			ob_start();
			?>
			<section class="wpr-support-center">
				<div class="wpr-support-hero wpr-card">
					<div>
						<p class="wpr-kicker"><?php echo esc_html( wpr_t( 'Support Center' ) ); ?></p>
						<h2><?php echo esc_html( wpr_t( 'Support & Resources' ) ); ?></h2>
						<p><?php echo esc_html( wpr_t( 'Create a support ticket, optionally include diagnostics, or open helpful troubleshooting resources.' ) ); ?></p>
					</div>
					<a class="button button-primary wpr-primary" href="https://buymeacoffee.com/nickdesignz" target="_blank" rel="noopener noreferrer">Buy me a coffee</a>
				</div>

				<div class="wpr-support-grid">
					<div class="wpr-support-maincol">
					<section class="wpr-card wpr-support-ticket-card">
						<div class="wpr-support-card-header is-ticket">
							<span class="wpr-support-card-icon" aria-hidden="true">✉</span>
							<div>
								<p class="wpr-kicker"><?php echo esc_html( wpr_t( 'Support Request' ) ); ?></p>
								<h2><?php echo esc_html( wpr_t( 'Create a new ticket' ) ); ?></h2>
								<p class="description"><?php echo esc_html( wpr_t( 'Describe the issue as clearly as possible. Diagnostic data is only included if you enable it.' ) ); ?></p>
							</div>
						</div>

						<form id="wpr-support-ticket-form" class="wpr-support-ticket-form">
							<div class="wpr-control-grid two">
								<label class="wpr-control" for="wpr-support-name"><span><?php echo esc_html( wpr_t( 'Name' ) ); ?></span><input type="text" id="wpr-support-name" name="name" maxlength="120" autocomplete="name"></label>
								<label class="wpr-control" for="wpr-support-email"><span><?php echo esc_html( wpr_t( 'Email' ) ); ?></span><input type="email" id="wpr-support-email" name="email" maxlength="190" required autocomplete="email"></label>
							</div>

							<label class="wpr-control" for="wpr-support-website"><span><?php echo esc_html( wpr_t( 'Website' ) ); ?></span><input type="url" id="wpr-support-website" name="website" value="<?php echo esc_attr( home_url( '/' ) ); ?>" maxlength="255"></label>

							<div class="wpr-control-grid two">
								<label class="wpr-control" for="wpr-support-type"><span><?php echo esc_html( wpr_t( 'Issue type' ) ); ?></span><select id="wpr-support-type" name="type"><option value="bug"><?php echo esc_html( wpr_t( 'Bug / Error' ) ); ?></option><option value="ui"><?php echo esc_html( wpr_t( 'Display / UI' ) ); ?></option><option value="feature"><?php echo esc_html( wpr_t( 'Feature request' ) ); ?></option><option value="license"><?php echo esc_html( wpr_t( 'License / Premium' ) ); ?></option><option value="other"><?php echo esc_html( wpr_t( 'Other' ) ); ?></option></select></label>
								<label class="wpr-control" for="wpr-support-priority"><span><?php echo esc_html( wpr_t( 'Priority' ) ); ?></span><select id="wpr-support-priority" name="priority"><option value="normal"><?php echo esc_html( wpr_t( 'Normal' ) ); ?></option><option value="high"><?php echo esc_html( wpr_t( 'High' ) ); ?></option><option value="critical"><?php echo esc_html( wpr_t( 'Critical' ) ); ?></option></select></label>
							</div>

							<label class="wpr-control" for="wpr-support-message"><span><?php echo esc_html( wpr_t( 'Message' ) ); ?></span><textarea id="wpr-support-message" name="message" rows="8" required placeholder="<?php echo esc_attr( wpr_t( 'What happened? What did you expect? Which steps reproduce the issue?' ) ); ?>"></textarea></label>

							<label class="wpr-switch-row"><input type="checkbox" id="wpr-support-include-diagnostics" name="include_diagnostics" value="1"><span><?php echo esc_html( wpr_t( 'Include diagnostic information' ) ); ?></span></label>
							<input type="text" id="wpr-support-company" name="company" value="" autocomplete="off" tabindex="-1" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;">
							<p class="description"><?php echo esc_html( wpr_t( 'Only technical system data is included. Word pairs, passwords and secret credentials are never sent.' ) ); ?></p>
							<p class="description wpr-privacy-support-note"><?php echo esc_html( wpr_t( 'Privacy note: Your support request is sent by email to NickDesignz. A copy is sent to your email address. Diagnostic data is only included if you enable it.' ) ); ?></p>

							<div class="wpr-actions"><button type="submit" class="button button-primary wpr-primary wpr-support-submit"><?php echo esc_html( wpr_t( 'Send ticket' ) ); ?></button></div>
						</form>
						<div id="wpr-support-ticket-result" class="wpr-css-regenerate-result" aria-live="polite"></div>
					</section>

					<?php echo $this->render_support_ticket_history_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>

					<aside class="wpr-support-side">
						<section class="wpr-card wpr-support-resource-card">
							<div class="wpr-support-card-header is-resources">
								<span class="wpr-support-card-icon" aria-hidden="true">↗</span>
								<div>
									<p class="wpr-kicker"><?php echo esc_html( wpr_t( 'Helpful Links' ) ); ?></p>
									<h2><?php echo esc_html( wpr_t( 'Resources' ) ); ?></h2>
								</div>
							</div>
							<div class="wpr-resource-list">
								<a href="https://buymeacoffee.com/nickdesignz" target="_blank" rel="noopener noreferrer"><span>☕</span><strong>Buy me a coffee</strong><small><?php echo esc_html( wpr_t( 'Support development' ) ); ?></small></a>
								<a href="https://nickdesignz.de/wpr" target="_blank" rel="noopener noreferrer" class="wpr-resource-button"><span>↗</span><strong><?php echo esc_html( wpr_t( 'Open plugin page' ) ); ?></strong><small><?php echo esc_html( wpr_t( 'Documentation, updates and product information' ) ); ?></small></a>
							</div>
						</section>

						<details class="wpr-card wpr-system-card wpr-system-accordion">
							<summary class="wpr-system-summary wpr-panel-summary">
								<span class="wpr-system-title wpr-panel-title"><i aria-hidden="true">ℹ</i><span><?php echo esc_html( wpr_t( 'System status' ) ); ?></span></span>
								<span class="wpr-system-meta wpr-panel-meta"><small><?php echo esc_html( sprintf( wpr_t( '%d item(s)' ), count( $diagnostics ) ) ); ?></small><b aria-hidden="true"></b></span>
							</summary>
							<div class="wpr-system-body">
								<ul class="wpr-system-list">
									<?php foreach ( $diagnostics as $label => $value ) : ?>
										<li><span><?php echo esc_html( $label ); ?></span><strong><?php echo esc_html( is_scalar( $value ) ? (string) $value : wp_json_encode( $value ) ); ?></strong></li>
									<?php endforeach; ?>
								</ul>
							</div>
						</details>

						<details class="wpr-card wpr-conflict-card wpr-compatibility-accordion">
							<summary class="wpr-compatibility-summary wpr-panel-summary">
								<span class="wpr-compatibility-title wpr-panel-title"><i aria-hidden="true">🛡</i><span><?php echo esc_html( wpr_t( 'Compatibility & Security Check' ) ); ?></span></span>
								<span class="wpr-compatibility-meta wpr-panel-meta"><small><?php echo esc_html( sprintf( wpr_t( '%d check(s)' ), count( $conflicts ) ) ); ?></small><b aria-hidden="true"></b></span>
							</summary>
							<div class="wpr-compatibility-body">
								<?php if ( empty( $conflicts ) ) : ?>
									<p class="wpr-ok">✓ <?php echo esc_html( wpr_t( 'No known conflicts detected.' ) ); ?></p>
								<?php else : ?>
									<ul class="wpr-conflict-list">
										<?php foreach ( $conflicts as $conflict ) : ?>
											<?php
											$status       = isset( $conflict['status'] ) ? (string) $conflict['status'] : 'notice';
											$status_label = isset( $conflict['status_label'] ) ? (string) $conflict['status_label'] : wpr_t( 'Notice' );
											?>
											<li class="is-<?php echo esc_attr( sanitize_html_class( $status ) ); ?>">
												<strong><?php echo esc_html( $conflict['name'] ); ?>:</strong>
												<span class="wpr-conflict-status is-<?php echo esc_attr( sanitize_html_class( $status ) ); ?>"><?php echo esc_html( $status_label ); ?></span><br>
												<small><?php echo esc_html( $conflict['hint'] ); ?></small>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</div>
						</details>
					</aside>
				</div>
			</section>
			<?php
			$body = ob_get_clean();
		} elseif ( 'wordpair-replacer-settings' === $page ) {
			$title = wpr_t( 'Settings' );
			$paths = class_exists( 'WPR_CSS' ) ? WPR_CSS::upload_dir() : array( 'href' => '', 'file' => '' );
			ob_start();
			?>
			<form id="wpr-settings-form" class="wpr-settings-page-form">
				<section class="wpr-card wpr-secondary-card wpr-settings-card wpr-settings-general-card">
					<div class="wpr-settings-card-header is-general">
						<span class="wpr-settings-card-icon" aria-hidden="true">⚙</span>
						<div>
							<p class="wpr-kicker"><?php echo esc_html( wpr_t( 'WordPair Replacer' ) ); ?></p>
							<h2><?php echo esc_html( wpr_t( 'Settings' ) ); ?></h2>
							<p class="description"><?php echo esc_html( wpr_t( 'Configure language, privacy-related font loading and plugin behavior.' ) ); ?></p>
						</div>
					</div>

					<div class="wpr-control-grid two wpr-settings-box-grid">
						<div class="wpr-setting-box">
							<strong><?php echo esc_html( wpr_t( 'Plugin language' ) ); ?></strong>
							<div class="wpr-language-options">
								<?php foreach ( WPR_Settings::allowed_plugin_languages() as $locale => $label ) : ?>
									<label>
										<input type="radio" name="plugin_language" value="<?php echo esc_attr( $locale ); ?>" <?php checked( $settings['plugin_language'], $locale ); ?>>
										<span><?php echo esc_html( $label ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="wpr-setting-box wpr-google-fonts-setting">
							<div>
								<strong><?php echo esc_html( wpr_t( 'Enable Google Fonts' ) ); ?></strong>
								<small><?php echo esc_html( wpr_t( 'Loads selected Google Fonts on the frontend only when enabled.' ) ); ?></small>
							</div>
							<label class="wpr-binary-switch wpr-google-fonts-switch" title="<?php echo esc_attr( wpr_t( 'Enable Google Fonts' ) ); ?>">
								<span><?php echo esc_html( wpr_t( 'Off' ) ); ?></span>
								<input type="checkbox" id="wpr-enable-google-fonts" <?php checked( (int) $settings['enable_google_fonts'], 1 ); ?>>
								<i aria-hidden="true"></i>
								<span><?php echo esc_html( wpr_t( 'On' ) ); ?></span>
							</label>
						</div>
					</div>
				</section>

				<details class="wpr-card wpr-secondary-card wpr-settings-card wpr-settings-accordion wpr-security-monitor-settings" open>
					<summary class="wpr-settings-card-header is-security">
						<span class="wpr-settings-card-icon" aria-hidden="true">🛡</span>
						<div>
							<p class="wpr-kicker"><?php echo esc_html( wpr_t( 'Security' ) ); ?></p>
							<h2><?php echo esc_html( wpr_t( 'Security Monitor' ) ); ?></h2>
							<p class="description"><?php echo esc_html( wpr_t( 'Enable optional vulnerability checks and configure your own provider API keys.' ) ); ?></p>
						</div>
						<label class="wpr-binary-switch" title="<?php echo esc_attr( wpr_t( 'Security Monitor' ) ); ?>">
							<span><?php echo esc_html( wpr_t( 'Off' ) ); ?></span>
							<input type="checkbox" id="wpr-security-monitor-enabled" <?php checked( (int) ( $settings['security_monitor_enabled'] ?? 0 ), 1 ); ?>>
							<i aria-hidden="true"></i>
							<span><?php echo esc_html( wpr_t( 'On' ) ); ?></span>
						</label>
						<span class="wpr-settings-accordion-toggle" aria-hidden="true"></span>
					</summary>

					<div class="wpr-settings-accordion-body">
					<div class="wpr-security-privacy-note">
						<strong><?php echo esc_html( wpr_t( 'Privacy notice for EU users' ) ); ?></strong>
						<p><?php echo esc_html( wpr_t( 'External vulnerability checks may send technical component data such as plugin slugs, theme slugs and version numbers to the selected provider. No email addresses, ticket contents, custom CSS, presets or WordPair Replacer settings are sent.' ) ); ?></p>
					</div>

					<div class="wpr-security-provider-grid">
						<div class="wpr-security-provider">
							<div class="wpr-provider-head">
								<strong><?php echo esc_html( wpr_t( 'Local WordPress Update Check' ) ); ?></strong>
								<label class="wpr-binary-switch" title="<?php echo esc_attr( wpr_t( 'Always enabled' ) ); ?>"><span><?php echo esc_html( wpr_t( 'Off' ) ); ?></span><input type="checkbox" disabled checked><i aria-hidden="true"></i><span><?php echo esc_html( wpr_t( 'On' ) ); ?></span></label>
							</div>
							<p><?php echo esc_html( wpr_t( 'Always enabled. Uses WordPress update data to detect outdated core, plugins and themes without sending data to additional services.' ) ); ?></p>
						</div>
						<div class="wpr-security-provider is-coming-soon">
							<div class="wpr-provider-head">
								<strong>WPVulnerability</strong>
								<span class="wpr-badge-soon"><?php echo esc_html( wpr_t( 'Coming soon' ) ); ?></span>
								<label class="wpr-binary-switch"><span><?php echo esc_html( wpr_t( 'Off' ) ); ?></span><input type="checkbox" id="wpr-security-wpvulnerability-enabled" disabled><i aria-hidden="true"></i><span><?php echo esc_html( wpr_t( 'On' ) ); ?></span></label>
							</div>
							<p><?php echo esc_html( wpr_t( 'Open vulnerability database for WordPress components. Usually no API key required. Enable only if this fits your privacy policy.' ) ); ?></p>
							<p class="description"><?php echo esc_html( wpr_t( 'This provider is planned but not connected yet. No data is sent and no key is stored.' ) ); ?></p>
						</div>
						<div class="wpr-security-provider is-coming-soon">
							<div class="wpr-provider-head">
								<strong>Wordfence Intelligence</strong>
								<span class="wpr-badge-soon"><?php echo esc_html( wpr_t( 'Coming soon' ) ); ?></span>
								<label class="wpr-binary-switch"><span><?php echo esc_html( wpr_t( 'Off' ) ); ?></span><input type="checkbox" id="wpr-security-wordfence-enabled" disabled><i aria-hidden="true"></i><span><?php echo esc_html( wpr_t( 'On' ) ); ?></span></label>
							</div>
							<p><?php echo esc_html( wpr_t( 'Free vulnerability data feed from Wordfence. Review the provider terms before enabling it.' ) ); ?></p>
							<p class="description"><?php echo esc_html( wpr_t( 'This provider is planned but not connected yet. No data is sent and no key is stored.' ) ); ?></p>
						</div>
						<div class="wpr-security-provider is-coming-soon">
							<div class="wpr-provider-head">
								<strong>WPScan</strong>
								<span class="wpr-badge-soon"><?php echo esc_html( wpr_t( 'Coming soon' ) ); ?></span>
								<label class="wpr-binary-switch"><span><?php echo esc_html( wpr_t( 'Off' ) ); ?></span><input type="checkbox" id="wpr-security-wpscan-enabled" disabled><i aria-hidden="true"></i><span><?php echo esc_html( wpr_t( 'On' ) ); ?></span></label>
							</div>
							<p><?php echo esc_html( wpr_t( 'Requires your own WPScan API token from your WPScan account.' ) ); ?></p>
							<input type="password" id="wpr-security-wpscan-api-token" value="" autocomplete="off" placeholder="WPScan API Token" disabled>
							<p class="description"><?php echo esc_html( wpr_t( 'This provider is planned but not connected yet. No data is sent and no key is stored.' ) ); ?></p>
						</div>
						<div class="wpr-security-provider is-coming-soon">
							<div class="wpr-provider-head">
								<strong>Patchstack</strong>
								<span class="wpr-badge-soon"><?php echo esc_html( wpr_t( 'Coming soon' ) ); ?></span>
								<label class="wpr-binary-switch"><span><?php echo esc_html( wpr_t( 'Off' ) ); ?></span><input type="checkbox" id="wpr-security-patchstack-enabled" disabled><i aria-hidden="true"></i><span><?php echo esc_html( wpr_t( 'On' ) ); ?></span></label>
							</div>
							<p><?php echo esc_html( wpr_t( 'Requires your own Patchstack API key. Availability depends on your Patchstack plan and API access.' ) ); ?></p>
							<input type="password" id="wpr-security-patchstack-api-key" value="" autocomplete="off" placeholder="Patchstack API Key" disabled>
							<p class="description"><?php echo esc_html( wpr_t( 'This provider is planned but not connected yet. No data is sent and no key is stored.' ) ); ?></p>
						</div>
					</div>
					<div class="wpr-security-scan-actions">
						<button type="button" class="button wpr-secondary" id="wpr-run-security-scan"><?php echo esc_html( wpr_t( 'Run security scan now' ) ); ?></button>
						<div id="wpr-security-scan-result" class="wpr-settings-save-result" aria-live="polite"><?php echo esc_html( $this->security_scan_status_text() ); ?></div>
					</div>
					<div id="wpr-security-scan-details" class="wpr-security-scan-details">
						<?php echo $this->render_security_scan_details_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					</div>
				</details>

				<details class="wpr-card wpr-secondary-card wpr-settings-card wpr-settings-accordion wpr-settings-css-card">
					<summary class="wpr-settings-card-header is-css">
						<span class="wpr-settings-card-icon" aria-hidden="true">{}</span>
						<div>
							<p class="wpr-kicker"><?php echo esc_html( wpr_t( 'Frontend CSS' ) ); ?></p>
							<h2><?php echo esc_html( wpr_t( 'Custom CSS & CSS Maintenance' ) ); ?></h2>
							<p class="description"><?php echo esc_html( wpr_t( 'Add scoped custom CSS and regenerate the generated frontend stylesheet when needed.' ) ); ?></p>
						</div>
						<span class="wpr-settings-accordion-toggle" aria-hidden="true"></span>
					</summary>
					<div class="wpr-settings-accordion-body">
					<div class="wpr-settings-css-layout">
						<label class="wpr-control wpr-css-control" for="wpr-custom-css">
							<span><?php echo esc_html( wpr_t( 'Custom CSS' ) ); ?></span>
							<textarea id="wpr-custom-css" rows="8" spellcheck="false" placeholder=".wpr-replaced { text-transform: uppercase; }"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
						</label>
						<div class="wpr-css-maintenance-compact">
							<h3><?php echo esc_html( wpr_t( 'CSS Maintenance' ) ); ?></h3>
							<p><?php echo esc_html( wpr_t( 'Use this only if the dynamic frontend CSS is missing, outdated or not loaded correctly after changes.' ) ); ?></p>
							<button type="button" class="button button-primary wpr-primary" id="wpr-regenerate-css"><?php echo esc_html( wpr_t( 'Regenerate CSS' ) ); ?></button>
							<div id="wpr-css-regenerate-result" class="wpr-css-regenerate-result" aria-live="polite"></div>
							<?php if ( ! empty( $paths['href'] ) ) : ?>
								<p class="description"><a href="<?php echo esc_url( $paths['href'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( wpr_t( 'View CSS file' ) ); ?></a></p>
							<?php endif; ?>
						</div>
					</div>
					<div class="wpr-actions wpr-settings-actions"><button type="submit" class="button button-primary wpr-primary"><?php echo esc_html( wpr_t( 'Save settings' ) ); ?></button><span id="wpr-settings-save-result" class="wpr-settings-save-result" aria-live="polite"></span></div>
					</div>
				</details>
			</form>
			<?php
			$body = ob_get_clean();
		} elseif ( 'wordpair-replacer-license' === $page ) {
			$title = wpr_t( 'License' );
			$body  = $this->render_secondary_hero( 'WORDPAIR REPLACER', $title, wpr_t( 'Manage your license status and future premium unlocks.' ) );
			$body .= '<section class="wpr-card wpr-secondary-card"><h2>' . esc_html( $title ) . '</h2><p>' . esc_html( wpr_t( 'Free version active. Premium features can be unlocked later.' ) ) . '</p></section>';
		}
		?>
		<div class="wrap wpr-wrap">
			<div class="wpr-brand-header">
				<div class="wpr-brand-left">
					<div class="wpr-brand-logo" aria-hidden="true"><img src="<?php echo esc_url( WPR_PLUGIN_URL . 'assets/icon-128x128.png' ); ?>" alt=""></div>
					<div><h1>WordPair <span>Replacer</span> <mark class="wpr-version-badge"><?php echo esc_html( WPR_VERSION ); ?></mark></h1><p><?php echo esc_html( wpr_t( 'Ersetze Wörter oder Wortpaare automatisch im gesamten Frontend – mit individuellem Styling und Live-Vorschau.' ) ); ?></p></div>
				</div>
				<div class="wpr-top-actions">
					<label class="wpr-theme-switch" title="Light / Dark Mode"><span>Light</span><input type="checkbox" id="wpr-admin-theme-toggle"><i aria-hidden="true"></i><span>Dark</span></label>
					<label class="wpr-language-switch" title="English / Deutsch">
						<span>EN</span>
						<input type="checkbox" class="wpr-language-toggle" <?php checked( $settings['plugin_language'], 'de_DE' ); ?>>
						<i aria-hidden="true"></i>
						<span>DE</span>
					</label>
					<a class="button wpr-frontend-open" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( wpr_t( 'Frontend öffnen' ) ); ?> <span class="dashicons dashicons-external" aria-hidden="true"></span></a>
				</div>
			</div>
			<nav class="wpr-plugin-nav" aria-label="WordPair Replacer">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wordpair-replacer' ) ); ?>" class="<?php echo 'wordpair-replacer' === $page ? 'is-active' : ''; ?>"><span class="dashicons dashicons-dashboard" aria-hidden="true"></span><?php echo esc_html( wpr_t( 'Dashboard' ) ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wordpair-replacer-settings' ) ); ?>" class="<?php echo 'wordpair-replacer-settings' === $page ? 'is-active' : ''; ?>"><span class="dashicons dashicons-admin-generic" aria-hidden="true"></span><?php echo esc_html( wpr_t( 'Einstellungen' ) ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wordpair-replacer-import-export' ) ); ?>" class="<?php echo 'wordpair-replacer-import-export' === $page ? 'is-active' : ''; ?>"><span class="dashicons dashicons-database-export" aria-hidden="true"></span><?php echo esc_html( wpr_t( 'Import / Export' ) ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wordpair-replacer-documentation' ) ); ?>" class="<?php echo 'wordpair-replacer-documentation' === $page ? 'is-active' : ''; ?>"><span class="dashicons dashicons-media-document" aria-hidden="true"></span><?php echo esc_html( wpr_t( 'Documentation' ) ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wordpair-replacer-support' ) ); ?>" class="<?php echo 'wordpair-replacer-support' === $page ? 'is-active' : ''; ?>"><span class="dashicons dashicons-sos" aria-hidden="true"></span><?php echo esc_html( wpr_t( 'Support' ) ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wordpair-replacer-changelog' ) ); ?>" class="<?php echo 'wordpair-replacer-changelog' === $page ? 'is-active' : ''; ?>"><span class="dashicons dashicons-list-view" aria-hidden="true"></span><?php echo esc_html( wpr_t( 'Changelog' ) ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wordpair-replacer-license' ) ); ?>" class="<?php echo 'wordpair-replacer-license' === $page ? 'is-active' : ''; ?>"><span class="dashicons dashicons-shield" aria-hidden="true"></span><?php echo esc_html( wpr_t( 'Lizenz' ) ); ?></a>
			</nav>
			<div class="wpr-secondary-wrap"><?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		</div>
		<?php
	}


	private function collect_support_diagnostics(): array {
		global $wpdb;

		$theme = wp_get_theme();
		$paths = class_exists( 'WPR_CSS' ) ? WPR_CSS::upload_dir() : array( 'file' => '' );
		$css_file = ! empty( $paths['file'] ) && file_exists( $paths['file'] ) ? size_format( filesize( $paths['file'] ) ) : wpr_t( 'Nicht gefunden' );

		return array(
			wpr_t( 'Plugin-Version' ) => WPR_VERSION,
			wpr_t( 'WordPress-Version' ) => get_bloginfo( 'version' ),
			wpr_t( 'PHP-Version' ) => PHP_VERSION,
			wpr_t( 'Theme' ) => $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ),
			wpr_t( 'Multisite' ) => is_multisite() ? wpr_t( 'Ja' ) : wpr_t( 'Nein' ),
			wpr_t( 'Frontend-CSS' ) => $css_file,
			wpr_t( 'Datenbank' ) => $wpdb->db_version(),
			wpr_t( 'Sprache' ) => WPR_I18n::current_language(),
		);
	}

	private function detect_plugin_conflicts(): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$settings       = WPR_Settings::get();
		$active_plugins = (array) get_option( 'active_plugins', array() );
		$plugins        = get_plugins();
		$checks         = array();
		$seen           = array();
		$has_elementor  = false;
		$has_elementor_pro = false;

		$optimization_plugins = array(
			'wp-rocket'              => array( 'WP Rocket', wpr_t( 'Exclude WordPair Replacer assets from CSS/JS minify, combine, defer and delay if gradients, animations or generated styles do not appear correctly.' ) ),
			'litespeed-cache'        => array( 'LiteSpeed Cache', wpr_t( 'Exclude WordPair Replacer assets from CSS/JS minify, combine, defer and delay if gradients, animations or generated styles do not appear correctly.' ) ),
			'autoptimize'            => array( 'Autoptimize', wpr_t( 'Exclude WordPair Replacer assets from CSS/JS minify, combine, defer and delay if gradients, animations or generated styles do not appear correctly.' ) ),
			'w3-total-cache'         => array( 'W3 Total Cache', wpr_t( 'Clear cache after regenerating frontend CSS and exclude the generated WordPair Replacer CSS file if needed.' ) ),
			'flyingpress'            => array( 'FlyingPress', wpr_t( 'If JavaScript actions or frontend styles behave unexpectedly, exclude WordPair Replacer assets from delay/defer rules.' ) ),
			'perfmatters'            => array( 'Perfmatters', wpr_t( 'If JavaScript actions or frontend styles behave unexpectedly, exclude WordPair Replacer assets from delay/defer rules.' ) ),
			'wp-asset-clean-up'      => array( 'Asset CleanUp', wpr_t( 'Make sure WordPair Replacer admin and frontend assets are not unloaded on pages where replacements are used.' ) ),
			'sg-cachepress'          => array( 'SG Optimizer', wpr_t( 'Clear cache after regenerating frontend CSS and exclude the generated WordPair Replacer CSS file if needed.' ) ),
			'hummingbird-performance' => array( 'Hummingbird', wpr_t( 'Exclude WordPair Replacer assets from CSS/JS minify, combine, defer and delay if gradients, animations or generated styles do not appear correctly.' ) ),
			'fast-velocity-minify'   => array( 'Fast Velocity Minify', wpr_t( 'Exclude WordPair Replacer assets from CSS/JS minify, combine, defer and delay if gradients, animations or generated styles do not appear correctly.' ) ),
		);

		foreach ( $active_plugins as $plugin_file ) {
			$slug = strtolower( dirname( $plugin_file ) );
			$name = isset( $plugins[ $plugin_file ]['Name'] ) ? strtolower( $plugins[ $plugin_file ]['Name'] ) : strtolower( $plugin_file );

			if ( 'elementor' === $slug || false !== strpos( $name, 'elementor' ) ) {
				$has_elementor = true;
			}

			if ( 'elementor-pro' === $slug || false !== strpos( $name, 'elementor pro' ) ) {
				$has_elementor_pro = true;
			}

			foreach ( $optimization_plugins as $needle => $data ) {
				if ( false !== strpos( $slug, $needle ) || false !== strpos( $name, $needle ) ) {
					if ( isset( $seen[ $needle ] ) ) {
						continue 2;
					}

					$seen[ $needle ] = true;
					$checks[] = array(
						'name'         => $data[0],
						'hint'         => $data[1] . ' ' . wpr_t( 'Suggested exclusions:' ) . ' wordpair-replacer, wpr-frontend, /wp-content/uploads/wordpair-replacer/',
						'status'       => 'warning',
						'status_label' => wpr_t( 'Optimization plugin detected' ),
					);
					continue 2;
				}
			}
		}

		if ( $has_elementor || $has_elementor_pro ) {
			$checks[] = array(
				'name'         => $has_elementor_pro ? 'Elementor / Elementor Pro' : 'Elementor',
				'status'       => 'compatible',
				'status_label' => wpr_t( 'Compatible' ),
				'hint'         => wpr_t( 'For complex widgets, use ignore classes if a section should not be processed.' ),
			);
		}

		$checks = array_merge( $checks, $this->collect_local_security_checks( $plugins ) );
		$checks = array_merge( $checks, $this->collect_external_security_provider_checks() );

		return $checks;
	}

	private function collect_local_security_checks( array $plugins ): array {
		$checks = array();

		if ( ! function_exists( 'get_core_updates' ) ) {
			require_once ABSPATH . 'wp-admin/includes/update.php';
		}


		$core_updates   = function_exists( 'get_core_updates' ) ? get_core_updates() : array();
		$plugin_updates = get_site_transient( 'update_plugins' );
		$theme_updates  = get_site_transient( 'update_themes' );

		$core_has_update = false;
		if ( is_array( $core_updates ) ) {
			foreach ( $core_updates as $update ) {
				if ( is_object( $update ) && ! empty( $update->response ) && 'upgrade' === $update->response ) {
					$core_has_update = true;
					break;
				}
			}
		}

		$checks[] = array(
			'name'         => wpr_t( 'WordPress Core' ),
			'status'       => $core_has_update ? 'warning' : 'compatible',
			'status_label' => $core_has_update ? wpr_t( 'Update available' ) : wpr_t( 'Current' ),
			'hint'         => $core_has_update ? wpr_t( 'Update WordPress before debugging frontend rendering or security-related issues.' ) : wpr_t( 'No local WordPress core update is currently reported.' ),
		);

		$plugin_update_count = is_object( $plugin_updates ) && ! empty( $plugin_updates->response ) ? count( (array) $plugin_updates->response ) : 0;
		$theme_update_count  = is_object( $theme_updates ) && ! empty( $theme_updates->response ) ? count( (array) $theme_updates->response ) : 0;

		$checks[] = array(
			'name'         => wpr_t( 'Plugin updates' ),
			'status'       => $plugin_update_count > 0 ? 'warning' : 'compatible',
			'status_label' => $plugin_update_count > 0 ? sprintf( wpr_t( '%d update(s) available' ), $plugin_update_count ) : wpr_t( 'Current' ),
			'hint'         => $plugin_update_count > 0 ? wpr_t( 'Outdated plugins can affect Elementor, optimization plugins and frontend rendering. Review updates before reporting conflicts.' ) : wpr_t( 'No local plugin updates are currently reported.' ),
		);

		$checks[] = array(
			'name'         => wpr_t( 'Theme updates' ),
			'status'       => $theme_update_count > 0 ? 'warning' : 'compatible',
			'status_label' => $theme_update_count > 0 ? sprintf( wpr_t( '%d update(s) available' ), $theme_update_count ) : wpr_t( 'Current' ),
			'hint'         => $theme_update_count > 0 ? wpr_t( 'Theme updates may affect frontend markup and CSS specificity.' ) : wpr_t( 'No local theme updates are currently reported.' ),
		);

		$paths = class_exists( 'WPR_CSS' ) ? WPR_CSS::upload_dir() : array( 'file' => '' );
		$css_file = (string) ( $paths['file'] ?? '' );
		$css_ok   = '' !== $css_file && file_exists( $css_file ) && is_readable( $css_file ) && filesize( $css_file ) > 0;
		$css_hint = $css_ok ? sprintf( wpr_t( 'Generated CSS exists and is readable. Size: %s.' ), size_format( filesize( $css_file ) ) ) : wpr_t( 'Generated CSS file is missing, unreadable or empty. Regenerate CSS and clear cache.' );

		$checks[] = array(
			'name'         => wpr_t( 'Frontend CSS' ),
			'status'       => $css_ok ? 'compatible' : 'error',
			'status_label' => $css_ok ? wpr_t( 'OK' ) : wpr_t( 'Needs attention' ),
			'hint'         => $css_hint,
		);

		global $wpdb;
		$preset_table = $wpdb->prefix . 'wordpair_replacer_presets';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$preset_exists = $preset_table === $wpdb->get_var( "SHOW TABLES LIKE '{$preset_table}'" );

		$checks[] = array(
			'name'         => wpr_t( 'Preset database' ),
			'status'       => $preset_exists ? 'compatible' : 'warning',
			'status_label' => $preset_exists ? wpr_t( 'OK' ) : wpr_t( 'Missing' ),
			'hint'         => $preset_exists ? wpr_t( 'Preset table is available.' ) : wpr_t( 'Preset table was not found. Reactivate the plugin or run database repair.' ),
		);

		return $checks;
	}

	private function collect_external_security_provider_checks(): array {
		// WPVulnerability, Wordfence, WPScan and Patchstack are not connected to
		// any external service yet ("coming soon" in the UI) and can no longer
		// be enabled or configured with an API key. Report them as such instead
		// of a misleading "disabled"/"configured" status.
		$checks = array();

		foreach ( array( 'WPVulnerability', 'Wordfence Intelligence', 'WPScan', 'Patchstack' ) as $provider ) {
			$checks[] = array(
				'name'         => $provider,
				'status'       => 'notice',
				'status_label' => wpr_t( 'Coming soon' ),
				'hint'         => wpr_t( 'This provider is planned but not connected yet. No data is sent and no key is stored.' ),
			);
		}

		return $checks;
	}


	private function render_security_scan_details_html( bool $animate = false ): string {
		$scan = $this->get_security_scan_result();
		$checks = isset( $scan['checks'] ) && is_array( $scan['checks'] ) ? $scan['checks'] : array();

		if ( ! empty( $scan['timestamp'] ) && empty( $checks ) ) {
			$checks = $this->detect_plugin_conflicts();
		}

		$summary = isset( $scan['summary'] ) && is_array( $scan['summary'] ) ? $scan['summary'] : array( 'passed' => 0, 'warnings' => 0, 'critical' => 0 );

		if ( ! empty( $checks ) ) {
			$summary = array( 'passed' => 0, 'warnings' => 0, 'critical' => 0 );
			foreach ( $checks as $summary_check ) {
				$summary_status = isset( $summary_check['status'] ) ? (string) $summary_check['status'] : 'notice';
				if ( 'error' === $summary_status || 'critical' === $summary_status ) {
					$summary['critical']++;
				} elseif ( 'warning' === $summary_status ) {
					$summary['warnings']++;
				} else {
					$summary['passed']++;
				}
			}
		}

		$groups = array(
			'critical' => array( 'label' => wpr_t( 'Critical issues' ), 'items' => array() ),
			'warning'  => array( 'label' => wpr_t( 'Warnings' ), 'items' => array() ),
			'passed'   => array( 'label' => wpr_t( 'Passed checks' ), 'items' => array() ),
		);

		foreach ( $checks as $check ) {
			$status = isset( $check['status'] ) ? (string) $check['status'] : 'notice';
			if ( 'error' === $status || 'critical' === $status ) {
				$groups['critical']['items'][] = $check;
			} elseif ( 'warning' === $status ) {
				$groups['warning']['items'][] = $check;
			} else {
				$groups['passed']['items'][] = $check;
			}
		}

		ob_start();
		?>
		<div class="wpr-security-scan-report">
			<div class="wpr-security-scan-report-head">
				<strong><?php echo esc_html( wpr_t( 'Scan details' ) ); ?></strong>
				<span><?php echo esc_html( empty( $scan['timestamp'] ) ? wpr_t( 'Not run yet' ) : wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $scan['timestamp'] ) ); ?></span>
			</div>
			<ul>
				<li><span><?php echo esc_html( wpr_t( 'Last scan' ) ); ?></span><strong><?php echo esc_html( empty( $scan['timestamp'] ) ? wpr_t( 'No security scan has been run yet.' ) : wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $scan['timestamp'] ) ); ?></strong></li>
				<li><span><?php echo esc_html( wpr_t( 'Result' ) ); ?></span><strong><?php echo esc_html( empty( $scan['timestamp'] ) ? wpr_t( 'Pending' ) : ( (int) ( $summary['critical'] ?? 0 ) > 0 ? wpr_t( 'Critical issues found' ) : ( (int) ( $summary['warnings'] ?? 0 ) > 0 ? wpr_t( 'Warnings found' ) : wpr_t( 'No issues found' ) ) ) ); ?></strong></li>
				<li><span><?php echo esc_html( wpr_t( 'Summary' ) ); ?></span><strong><?php echo esc_html( sprintf( wpr_t( '%1$d passed, %2$d warning(s), %3$d critical' ), (int) ( $summary['passed'] ?? 0 ), (int) ( $summary['warnings'] ?? 0 ), (int) ( $summary['critical'] ?? 0 ) ) ); ?></strong></li>
				<li><span><?php echo esc_html( wpr_t( 'Active providers' ) ); ?></span><strong><?php echo esc_html( ! empty( $scan['providers'] ) && is_array( $scan['providers'] ) ? implode( ', ', array_map( 'sanitize_text_field', $scan['providers'] ) ) : wpr_t( 'No external providers enabled' ) ); ?></strong></li>
			</ul>

			<?php if ( empty( $scan['timestamp'] ) ) : ?>
				<p><?php echo esc_html( wpr_t( 'No scan result is available yet. Run a security scan to see passed checks, warnings and critical issues.' ) ); ?></p>
			<?php else : ?>
				<div class="wpr-security-result-groups<?php echo $animate ? ' is-animated' : ''; ?>">
					<?php foreach ( $groups as $group_key => $group ) : ?>
						<div class="wpr-security-result-group is-<?php echo esc_attr( $group_key ); ?>">
							<h4>
								<span class="wpr-security-group-title"><?php echo esc_html( $group['label'] ); ?></span>
								<span class="wpr-security-group-count"><?php echo esc_html( (string) count( $group['items'] ) ); ?></span>
							</h4>
							<?php if ( empty( $group['items'] ) ) : ?>
								<p class="wpr-security-empty"><?php echo esc_html( wpr_t( 'No entries in this group.' ) ); ?></p>
							<?php else : ?>
								<ul class="wpr-security-check-list">
									<?php foreach ( $group['items'] as $item_index => $item ) : ?>
										<li style="--wpr-scan-delay: <?php echo esc_attr( (string) min( 1400, (int) $item_index * 140 ) ); ?>ms;">
											<span class="wpr-security-check-icon" aria-hidden="true"></span>
											<span class="wpr-security-check-text">
												<strong><?php echo esc_html( (string) ( $item['name'] ?? '' ) ); ?>: <?php echo esc_html( (string) ( $item['status_label'] ?? '' ) ); ?></strong>
												<?php if ( 'passed' !== $group_key && ! empty( $item['hint'] ) ) : ?>
													<small><?php echo esc_html( (string) $item['hint'] ); ?></small>
												<?php endif; ?>
											</span>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<p><?php echo esc_html( wpr_t( 'External providers are only contacted when you enable them and run a security scan.' ) ); ?></p>
		</div>
		<?php
		return (string) ob_get_clean();
	}


	private function security_scan_status_text(): string {
		$scan = $this->get_security_scan_result();

		if ( empty( $scan['timestamp'] ) ) {
			return wpr_t( 'No security scan has been run yet.' );
		}

		return sprintf(
			wpr_t( 'Last security scan: %s' ),
			wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $scan['timestamp'] )
		);
	}

	private function get_security_scan_result(): array {
		$result = get_option( 'wpr_security_scan_result', array() );
		return is_array( $result ) ? $result : array();
	}

	public function ajax_run_security_scan(): void {
		$this->verify_ajax_request();

		$settings = WPR_Settings::get();
		$providers = array(
			'WPVulnerability' => ! empty( $settings['security_monitor_enabled'] ) && ! empty( $settings['security_wpvulnerability_enabled'] ),
			'Wordfence Intelligence' => ! empty( $settings['security_monitor_enabled'] ) && ! empty( $settings['security_wordfence_enabled'] ),
			'WPScan' => ! empty( $settings['security_monitor_enabled'] ) && ! empty( $settings['security_wpscan_enabled'] ) && ! empty( $settings['security_wpscan_api_token'] ),
			'Patchstack' => ! empty( $settings['security_monitor_enabled'] ) && ! empty( $settings['security_patchstack_enabled'] ) && ! empty( $settings['security_patchstack_api_key'] ),
		);

		$checks = $this->detect_plugin_conflicts();
		$summary = array( 'passed' => 0, 'warnings' => 0, 'critical' => 0 );

		foreach ( $checks as $check ) {
			$status = isset( $check['status'] ) ? (string) $check['status'] : 'notice';
			if ( 'error' === $status || 'critical' === $status ) {
				$summary['critical']++;
			} elseif ( 'warning' === $status ) {
				$summary['warnings']++;
			} else {
				$summary['passed']++;
			}
		}

		$active_providers = array_keys( array_filter( $providers ) );
		$result = array(
			'timestamp' => time(),
			'providers' => $active_providers,
			'local_check' => true,
			'checks' => $checks,
			'summary' => $summary,
		);

		update_option( 'wpr_security_scan_result', $result, false );

		if ( $summary['critical'] > 0 ) {
			$message = sprintf( wpr_t( 'Security scan completed. %1$d critical issue(s) and %2$d warning(s) found.' ), $summary['critical'], $summary['warnings'] );
		} elseif ( $summary['warnings'] > 0 ) {
			$message = sprintf( wpr_t( 'Security scan completed. %d warning(s) found.' ), $summary['warnings'] );
		} else {
			$message = wpr_t( 'Security scan completed. No issues found.' );
		}

		wp_send_json_success(
			array(
				'message' => $message,
				'timestamp' => $result['timestamp'],
				'details_html' => $this->render_security_scan_details_html( true ),
			)
		);
	}


	private function support_ticket_recipient(): string {
		return 'plugin@nickdesignz.de';
	}


	private function generate_support_ticket_id(): array {
		$counter = (int) get_option( 'wpr_support_ticket_counter', 0 );
		$counter++;
		update_option( 'wpr_support_ticket_counter', $counter, false );

		$domain_hash = strtoupper( substr( hash( 'sha256', home_url( '/' ) ), 0, 4 ) );
		$random_code = strtoupper( wp_generate_password( 6, false, false ) );

		return array(
			'id'          => sprintf( 'WPR-%s-%s-%s-%04d', wp_date( 'ymd' ), $domain_hash, $random_code, $counter ),
			'domain_hash' => $domain_hash,
			'counter'     => $counter,
		);
	}

	private function normalize_support_label( string $value, array $map ): string {
		return $map[ $value ] ?? ucfirst( str_replace( array( '_', '-' ), ' ', $value ) );
	}

	private function support_rate_limit_key( string $email ): string {
		return 'wpr_support_rate_' . md5( strtolower( $email ) . '|' . home_url( '/' ) );
	}

	private function build_internal_support_email( array $ticket ): string {
		$lines = array(
			'New support request submitted via WordPair Replacer',
			'',
			'Ticket ID: ' . $ticket['reference'],
			'Priority: ' . $ticket['priority_label'],
			'Issue Type: ' . $ticket['type_label'],
			'',
			'From:',
			trim( $ticket['name'] ) !== '' ? $ticket['name'] : 'Not provided',
			$ticket['email'],
			$ticket['website'] ?: home_url( '/' ),
			'',
			'Message:',
			$ticket['message'],
		);

		if ( ! empty( $ticket['diagnostics'] ) ) {
			$lines[] = '';
			$lines[] = '━━━━━━━━━━━━━━━━━━';
			$lines[] = 'System:';
			foreach ( $ticket['diagnostics'] as $label => $value ) {
				$lines[] = wp_strip_all_tags( (string) $label ) . ': ' . ( is_scalar( $value ) ? (string) $value : wp_json_encode( $value ) );
			}
		}

		if ( ! empty( $ticket['conflicts'] ) ) {
			$lines[] = '';
			$lines[] = 'Compatibility & Security Check:';
			foreach ( $ticket['conflicts'] as $conflict ) {
				$lines[] = $conflict['name'] . ': ' . $conflict['hint'];
			}
		}

		$lines[] = '';
		$lines[] = '━━━━━━━━━━━━━━━━━━';
		$lines[] = 'Domain ID: ' . $ticket['domain_hash'];
		$lines[] = 'Local Ticket Counter: ' . sprintf( '%04d', (int) $ticket['counter'] );
		$lines[] = '';
		$lines[] = 'WordPair Replacer';
		$lines[] = 'https://plugins.nickdesignz.de';

		return implode( "\n", $lines );
	}

	private function build_user_support_email( array $ticket ): string {
		$name = trim( (string) $ticket['name'] );
		$hello_name = '' !== $name ? $name : 'there';
		$local_time = ! empty( $ticket['client_local_time'] ) ? $ticket['client_local_time'] : 'Not available';
		$client_tz  = ! empty( $ticket['client_timezone'] ) ? $ticket['client_timezone'] : 'Your browser timezone';

		$lines = array(
			'Hello ' . $hello_name . ',',
			'',
			'your support request has been successfully submitted.',
			'',
			'Ticket ID:',
			$ticket['reference'],
			'',
			'Issue Type:',
			$ticket['type_label'],
			'',
			'Priority:',
			$ticket['priority_label'],
			'',
			'Your Message:',
			'“' . $ticket['message'] . '”',
			'',
			'━━━━━━━━━━━━━━━━━━',
			'',
			'Your Local Time:',
			$local_time . ' (' . $client_tz . ')',
			'',
			'Current Time in Berlin:',
			wp_date( 'm/d/Y – h:i A', null, new DateTimeZone( 'Europe/Berlin' ) ) . ' (Europe/Berlin)',
			'',
			'Please keep possible timezone differences in mind. Support replies are typically handled during regular support hours.',
			'',
			'━━━━━━━━━━━━━━━━━━',
			'',
			'Best regards,',
			'',
			'NickDesignz Plugins',
			'Visual WordPress Solutions',
			'https://plugins.nickdesignz.de',
			'',
			'Support:',
			'plugin@nickdesignz.de',
			'',
			'Languages:',
			'German / English',
			'',
			'Support Hours:',
			'Mon–Fri • 10:00 AM – 06:00 PM (Europe/Berlin)',
			'',
			'Please note:',
			'I manage multiple projects simultaneously, so response times may vary depending on workload.',
			'',
			'━━━━━━━━━━━━━━━━━━',
			'',
			'If WordPair Replacer saves you time and you enjoy using it, feel free to support the project:',
			'https://buymeacoffee.com/nickdesignz',
			'',
			'Fueled by caffeine, RGB lights and 2 AM debugging sessions.',
		);

		return implode( "\n", $lines );
	}


	private function render_pairs_fallback_html(): string {
		return '<div class="wpr-empty">Wortpaare werden geladen...</div>';
	}

	private function render_changelog_html(): string {
		return '<ul class="wpr-changelog-list">'
			. '<li><strong>2.3.2</strong> <span class="wpr-changelog-date">2026-05-23</span><br>Refined Security Monitor scan result output with compact checklist rows, progressive completion animation and cleaner result cards.</li><li><strong>2.3.1</strong> <span class="wpr-changelog-date">2026-05-23</span><br>Fixed security scan result summaries so passed checks are shown correctly, added dashboard action spacing and refined settings accordion toggle direction and background.</li><li><strong>2.3.0</strong> <span class="wpr-changelog-date">2026-05-23</span><br>Cleaned up admin CSS overrides, unified accordion toggles, preserved refined Settings spacing and removed remaining plain text toggle markers.</li><li><strong>2.2.9</strong> <span class="wpr-changelog-date">2026-05-23</span><br>Added detailed scan results with passed, warning and critical groups, made Settings secondary panels collapsible, refined CSS maintenance layout and improved Security Monitor result visibility.</li><li><strong>2.2.8</strong> <span class="wpr-changelog-date">2026-05-23</span><br>Refined Settings layout with unified section headers, merged Custom CSS and CSS Maintenance, preserved ticket history spacing polish and removed the redundant Settings hero banner.</li><li><strong>2.2.7</strong> <span class="wpr-changelog-date">2026-05-23</span><br>Polished support card headers, refined support icons and harmonized ticket, resources and diagnostics panels.</li><li><strong>2.2.5</strong> <span class="wpr-changelog-date">2026-05-23</span><br>Unified support accordion headers, improved System Status and ticket history panels, added support privacy note, refined Security Monitor provider behavior and restored provider states when the monitor is re-enabled.</li><li><strong>2.2.4</strong> <span class="wpr-changelog-date">2026-05-23</span><br>Added collapsible System Status, simplified Support resources, preserved refined security UI spacing and fixed dark-mode flash during admin page changes.</li><li><strong>2.2.3</strong> <span class="wpr-changelog-date">2026-05-23</span><br>Improved security scan result visibility, polished the compatibility accordion header and unified Security Monitor switch states.</li><li><strong>2.2.2</strong> <span class="wpr-changelog-date">2026-05-23</span><br>Added modern ON/OFF provider switches, manual security scan state, clearer provider messaging and settings save feedback.</li><li><strong>2.2.1</strong> <span class="wpr-changelog-date">2026-05-23</span><br>Fixed Security Monitor settings persistence and made the compatibility/security check collapsible.</li><li><strong>2.2.0</strong> <span class="wpr-changelog-date">2026-05-23</span><br>Added Compatibility & Security Check with local update diagnostics, optimization-plugin detection, generated CSS checks, preset database checks and opt-in vulnerability provider settings.</li>'
			. '<li><strong>2.1.9</strong> <span class="wpr-changelog-date">2026-05-23</span><br>Separated box background gradients from text gradients by rendering gradient text on an inner text layer. Improved text shadow rendering for gradient text so shadows stay behind the visible text layer.</li>'
			. '<li><strong>2.1.8</strong> <span class="wpr-changelog-date">2026-05-22</span><br>Google Fonts switch now saves instantly via AJAX, matching the language switch behavior while keeping Save settings for custom CSS and future advanced settings.</li>'
			. '<li><strong>2.1.7</strong> <span class="wpr-changelog-date">2026-05-22</span><br>Moved preset controls into the preview inspector, removed redundant preview copy, polished Google Fonts switch styling and completed preset translations. </li><li><strong>2.1.6</strong> <span class="wpr-changelog-date">2026-05-22</span><br>Added a real preset database, 12 bundled default presets, dashboard preset apply/save controls and secure preset import/export.</li>'
			. '<li><strong>2.1.5</strong> <span class="wpr-changelog-date">2026-05-22</span><br>Fixed support ticket history layout, refined conflict check formatting and improved Elementor compatibility status output.</li>'
			. '<li><strong>2.1.4</strong> <span class="wpr-changelog-date">2026-05-22</span><br>Improved Support layout, moved local ticket history below the ticket form, rebuilt conflict detection and replaced the language dropdown with a compact language switch.</li>'
			. '<li><strong>2.1.3</strong> <span class="wpr-changelog-date">2026-05-22</span><br>Added full page headers, expanded documentation accordions, cleaner support ticket history, improved settings layout, preset UI hooks and conflict check deduplication.</li>'
			. '<li><strong>2.1.2</strong> <span class="wpr-changelog-date">2026-05-22</span><br>Added preset library foundation, local ticket history and documentation page foundation.</li>'
			. '<li><strong>2.1.1</strong> <span class="wpr-changelog-date">2026-05-22</span><br>Finalized support ticket IDs, English support emails, sender copies, rate limiting and privacy-safe diagnostics.</li>'
			. '<li><strong>2.0.9</strong> <span class="wpr-changelog-date">2026-05-22</span><br>Moved custom CSS controls into a dedicated CSS panel, added internal link helpers, expanded Link &amp; SEO tooltips and refined save/header styling.</li>'
			. '<li><strong>2.0.8</strong> <span class="wpr-changelog-date">2026-05-22</span><br>Replaced the WordPress color picker with a modern lightweight swatch popover to keep editor rows stable.</li>'
			. '<li><strong>2.0.7</strong> <span class="wpr-changelog-date">2026-05-22</span><br>Improved color row alignment, removed redundant horizontal editor tabs, added localStorage fallbacks and introduced an Import / Export foundation.</li>'
			. '<li><strong>2.0.6</strong> <span class="wpr-changelog-date">2026-05-22</span><br>Improved compact round color pickers, localized field tooltips, language selector styling and support sidebar shortcuts.</li>'
			. '<li><strong>2.0.5</strong> <span class="wpr-changelog-date">2026-05-22</span><br>Polished dark-mode controls, compact color pickers, language selector styling, dashboard navigation order and contextual field tooltips.</li>'
			. '<li><strong>2.0.4</strong> <span class="wpr-changelog-date">2026-05-22</span><br>Added top language switcher, refined dark-mode switch fields, improved pair list spacing and polished navigation icons.</li>'
			. '<li><strong>2.0.3</strong> <span class="wpr-changelog-date">2026-05-21</span><br>Refined V2 workbench with real top tabs, vertical inspector navigation, removed duplicate sidebar widgets, moved language/CSS maintenance to settings and improved preview/detail layout.</li>'
			. '<li><strong>2.0.2</strong> <span class="wpr-changelog-date">2026-05-21</span><br>Fixed workbench tabs, added large dock preview, visible CSS class/ID fields and improved save action styling.</li>'
			. '<li><strong>2.0.1</strong> <span class="wpr-changelog-date">2026-05-21</span><br>Rebuilt the premium workbench visual system with real editor tabs, improved light/dark contrast, green save actions, cleaner pair list and menu integration.</li>'
			. '<li><strong>2.0.0</strong> <span class="wpr-changelog-date">2026-05-21</span><br>Added V2 plugin navigation, Link &amp; SEO controls, per-word-pair CSS class/ID fields and improved premium workbench details.</li>'
			. '<li><strong>1.8.0</strong> <span class="wpr-changelog-date">2026-05-21</span><br>Polished contrast, button states, dashboard spacing and light/dark theme behavior.</li>'
			. '<li><strong>1.7.9</strong> <span class="wpr-changelog-date">2026-05-21</span><br>Improved premium admin UI contrast, vertical tabs, pair browser filters, light/dark preview and simplified pair list.</li>'
			. '<li><strong>1.7.8</strong> <span class="wpr-changelog-date">2026-05-21</span><br>UI fix release with wider wordpair browser, real vertical inspector tabs, improved light/dark contrast and cleaner preview styling.</li>'
			. '<li><strong>1.7.7</strong> <span class="wpr-changelog-date">2026-05-21</span><br>Added premium master-detail UI refinements, light/dark admin themes, header actions, stronger contrast and improved wordpair browser controls.</li>'
			. '<li><strong>1.7.6</strong> <span class="wpr-changelog-date">2026-05-21</span><br>Added premium dark workbench UI with word-pair browser, docked inspector editor and improved CSS maintenance layout.</li>'
			. '<li><strong>1.7.5</strong> <span class="wpr-changelog-date">2026-05-21</span><br>Added advanced typography controls, background gradients, per-side border widths, animation timing controls and compact styling refinements.</li>'
			. '<li><strong>1.7.4</strong> <span class="wpr-changelog-date">2026-05-18</span><br>Improved color controls, fixed preview background behavior, added gradient type/direction settings and enhanced accordion affordances.</li>'
			. '<li><strong>1.7.3</strong> <span class="wpr-changelog-date">2026-05-18</span><br>Improved backend branding, added gradient text, text shadow, reset controls, linked padding/radius fields and dismissible support card.</li>'
			. '<li><strong>1.7.2</strong> <span class="wpr-changelog-date">2026-05-18</span><br>Added live backend preview with light/dark mode and changed typography defaults to inherit theme styles.</li>'
			. '<li><strong>1.7.1</strong> <span class="wpr-changelog-date">2026-05-18</span><br>Added first live preview foundation and improved individual style editing.</li>'
			. '<li><strong>1.7.0</strong> <span class="wpr-changelog-date">2026-05-18</span><br>Restored stable frontend replacement after output-buffer improvements.</li>'
			. '<li><strong>1.6.x</strong> <span class="wpr-changelog-date">2026-05-17</span><br>WordPress.org preparation, plugin-check hardening, safe activation, Buy me a coffee card and frontend stability refinements.</li>'
			. '<li><strong>1.5.x</strong> <span class="wpr-changelog-date">2026-05-17</span><br>AJAX style saving, action stability, border/color controls and effect-color UI improvements.</li>'
			. '<li><strong>1.4.x</strong> <span class="wpr-changelog-date">2026-05-17</span><br>Dynamic frontend CSS generation, individual CSS fixes, activation safety and UI refinements.</li>'
			. '<li><strong>1.1.0 – 1.3.x</strong> <span class="wpr-changelog-date">2026-05-17</span><br>Multilingual base, frontend replacement controls, active state handling and early styling options.</li>'
			. '<li><strong>1.0.0</strong> <span class="wpr-changelog-date">2026-05-17</span><br>Initial WordPair Replacer release with basic word-pair replacement, activation checks and admin management.</li>'
			. '</ul>';
	}



	private function get_internal_link_options(): array {
		$items = array();
		$post_types = get_post_types( array( 'public' => true ), 'names' );

		if ( empty( $post_types ) ) {
			return $items;
		}

		$posts = get_posts(
			array(
				'post_type'      => array_values( $post_types ),
				'post_status'    => 'publish',
				'posts_per_page' => 80,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		foreach ( $posts as $post ) {
			$items[] = array(
				'title' => get_the_title( $post ),
				'url'   => get_permalink( $post ),
				'type'  => get_post_type_object( $post->post_type )->labels->singular_name ?? $post->post_type,
			);
		}

		return $items;
	}

	private function render_style_controls( string $scope, array $values = array() ): void {
		$values = array_replace( WPR_Settings::style_defaults(), $values );
		?>
		<div class="wpr-style-editor">
			<?php $this->render_editor_panel_start( 'Tt', wpr_t( 'Typografie' ), wpr_t( 'Schrift, Größe, Stärke und Zeilenhöhe' ), 'typography' ); ?>
				<label class="wpr-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-font-family' ); ?>">
					<span><?php echo esc_html( wpr_t( 'Schriftart' ) ); ?></span>
					<select id="<?php echo esc_attr( 'wpr-' . $scope . '-font-family' ); ?>" data-style-field="font_family">
						<?php foreach ( WPR_Settings::allowed_system_fonts() as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" data-type="system" <?php selected( $values['font_family'], $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
						<?php foreach ( WPR_Settings::allowed_google_fonts() as $font ) : ?>
							<option value="<?php echo esc_attr( $font ); ?>" data-type="google" <?php selected( $values['font_family'], $font ); ?>><?php echo esc_html( $font ); ?> (Google Font)</option>
						<?php endforeach; ?>
					</select>
				</label>
				<div class="wpr-control-grid two">
					<?php $this->render_value_unit( $scope, 'font_size', wpr_t( 'Schriftgröße' ), $values['font_size'], $values['font_size_unit'] ); ?>
					<?php $this->render_number( $scope, 'line_height', wpr_t( 'Zeilenhöhe' ), $values['line_height'], '0.1' ); ?>
				</div>
				<label class="wpr-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-font-weight' ); ?>">
					<span><?php echo esc_html( wpr_t( 'Schriftstärke' ) ); ?></span>
					<select id="<?php echo esc_attr( 'wpr-' . $scope . '-font-weight' ); ?>" data-style-field="font_weight">
						<option value=""><?php echo esc_html( wpr_t( 'Theme übernehmen' ) ); ?></option>
						<option value="100" <?php selected( $values['font_weight'], '100' ); ?>>100</option>
						<option value="200" <?php selected( $values['font_weight'], '200' ); ?>>200</option>
						<option value="300" <?php selected( $values['font_weight'], '300' ); ?>>300</option>
						<option value="400" <?php selected( $values['font_weight'], '400' ); ?>>400</option>
						<option value="500" <?php selected( $values['font_weight'], '500' ); ?>>500</option>
						<option value="600" <?php selected( $values['font_weight'], '600' ); ?>>600</option>
						<option value="700" <?php selected( $values['font_weight'], '700' ); ?>>700</option>
						<option value="800" <?php selected( $values['font_weight'], '800' ); ?>>800</option>
						<option value="900" <?php selected( $values['font_weight'], '900' ); ?>>900</option>
					</select>
				</label>

				<div class="wpr-control-grid three">
					<label class="wpr-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-font-style' ); ?>">
						<span><?php echo esc_html( wpr_t( 'Schriftstil' ) ); ?></span>
						<select id="<?php echo esc_attr( 'wpr-' . $scope . '-font-style' ); ?>" data-style-field="font_style">
							<option value="normal" <?php selected( $values['font_style'] ?? 'normal', 'normal' ); ?>>Normal</option>
							<option value="italic" <?php selected( $values['font_style'] ?? 'normal', 'italic' ); ?>>Italic</option>
							<option value="oblique" <?php selected( $values['font_style'] ?? 'normal', 'oblique' ); ?>>Oblique</option>
						</select>
					</label>

					<label class="wpr-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-text-decoration' ); ?>">
						<span><?php echo esc_html( wpr_t( 'Textdekoration' ) ); ?></span>
						<select id="<?php echo esc_attr( 'wpr-' . $scope . '-text-decoration' ); ?>" data-style-field="text_decoration">
							<option value="none" <?php selected( $values['text_decoration'] ?? 'none', 'none' ); ?>>None</option>
							<option value="underline" <?php selected( $values['text_decoration'] ?? 'none', 'underline' ); ?>>Underline</option>
							<option value="overline" <?php selected( $values['text_decoration'] ?? 'none', 'overline' ); ?>>Overline</option>
							<option value="line-through" <?php selected( $values['text_decoration'] ?? 'none', 'line-through' ); ?>>Line-through</option>
						</select>
					</label>

					<label class="wpr-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-text-transform' ); ?>">
						<span><?php echo esc_html( wpr_t( 'Texttransform' ) ); ?></span>
						<select id="<?php echo esc_attr( 'wpr-' . $scope . '-text-transform' ); ?>" data-style-field="text_transform">
							<option value="none" <?php selected( $values['text_transform'] ?? 'none', 'none' ); ?>>None</option>
							<option value="uppercase" <?php selected( $values['text_transform'] ?? 'none', 'uppercase' ); ?>>Uppercase</option>
							<option value="lowercase" <?php selected( $values['text_transform'] ?? 'none', 'lowercase' ); ?>>Lowercase</option>
							<option value="capitalize" <?php selected( $values['text_transform'] ?? 'none', 'capitalize' ); ?>>Capitalize</option>
						</select>
					</label>
				</div>

				<div class="wpr-control-grid three">
					<?php $this->render_value_unit( $scope, 'letter_spacing', wpr_t( 'Zeichenabstand' ), $values['letter_spacing'] ?? '', $values['letter_spacing_unit'] ?? 'px' ); ?>
					<?php $this->render_value_unit( $scope, 'word_spacing', wpr_t( 'Wortabstand' ), $values['word_spacing'] ?? '', $values['word_spacing_unit'] ?? 'px' ); ?>
					<label class="wpr-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-white-space' ); ?>">
						<span><?php echo esc_html( wpr_t( 'Umbruch' ) ); ?></span>
						<select id="<?php echo esc_attr( 'wpr-' . $scope . '-white-space' ); ?>" data-style-field="white_space">
							<option value="" <?php selected( $values['white_space'] ?? '', '' ); ?>>Theme übernehmen</option>
							<option value="normal" <?php selected( $values['white_space'] ?? '', 'normal' ); ?>>Normal</option>
							<option value="nowrap" <?php selected( $values['white_space'] ?? '', 'nowrap' ); ?>>No wrap</option>
						</select>
					</label>
				</div>
			<?php $this->render_editor_panel_end(); ?>

			<?php $this->render_editor_panel_start( '🎨', wpr_t( 'Farben' ), wpr_t( 'Textfarbe, Hintergrundfarbe, Verlauf und Schatten' ), 'colors' ); ?>
				<div class="wpr-control-grid two wpr-color-two">
					<?php $this->render_color( $scope, 'color', wpr_t( 'Textfarbe' ), $values['color'] ); ?>
					<?php $this->render_color( $scope, 'background_color', wpr_t( 'Hintergrundfarbe' ), $values['background_color'] ); ?>
				</div>

				<div class="wpr-option-box wpr-bg-gradient-box">
					<div class="wpr-option-box-head">
						<label>
							<input type="checkbox" id="<?php echo esc_attr( 'wpr-' . $scope . '-bg-gradient-enabled' ); ?>" data-style-field="bg_gradient_enabled" value="1" <?php checked( (string) ( $values['bg_gradient_enabled'] ?? '0' ), '1' ); ?>>
							<span><?php echo esc_html( wpr_t( 'Hintergrund-Verlauf' ) ); ?></span>
						</label>
						<small><?php echo esc_html( wpr_t( 'Optional statt einfarbiger Hintergrundfarbe' ) ); ?></small>
					</div>

					<div class="wpr-bg-gradient-fields">
						<div class="wpr-control-grid two">
							<?php $this->render_color( $scope, 'bg_gradient_color_1', wpr_t( 'Hintergrund Farbe 1' ), $values['bg_gradient_color_1'] ?? '#24afab' ); ?>
							<?php $this->render_color( $scope, 'bg_gradient_color_2', wpr_t( 'Hintergrund Farbe 2' ), $values['bg_gradient_color_2'] ?? '#6c5ce7' ); ?>
						</div>

						<div class="wpr-control-grid three">
							<label class="wpr-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-bg-gradient-type' ); ?>">
								<span><?php echo esc_html( wpr_t( 'Verlaufsart' ) ); ?></span>
								<select id="<?php echo esc_attr( 'wpr-' . $scope . '-bg-gradient-type' ); ?>" data-style-field="bg_gradient_type">
									<option value="linear" <?php selected( $values['bg_gradient_type'] ?? 'linear', 'linear' ); ?>>Linear</option>
									<option value="radial" <?php selected( $values['bg_gradient_type'] ?? 'linear', 'radial' ); ?>>Radial</option>
								</select>
							</label>

							<?php $this->render_number( $scope, 'bg_gradient_angle', wpr_t( 'Winkel' ), $values['bg_gradient_angle'] ?? '90', '1' ); ?>

							<label class="wpr-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-bg-gradient-position' ); ?>">
								<span><?php echo esc_html( wpr_t( 'Position' ) ); ?></span>
								<select id="<?php echo esc_attr( 'wpr-' . $scope . '-bg-gradient-position' ); ?>" data-style-field="bg_gradient_position">
									<option value="center" <?php selected( $values['bg_gradient_position'] ?? 'center', 'center' ); ?>>Center</option>
									<option value="top" <?php selected( $values['bg_gradient_position'] ?? 'center', 'top' ); ?>>Top</option>
									<option value="right" <?php selected( $values['bg_gradient_position'] ?? 'center', 'right' ); ?>>Right</option>
									<option value="bottom" <?php selected( $values['bg_gradient_position'] ?? 'center', 'bottom' ); ?>>Bottom</option>
									<option value="left" <?php selected( $values['bg_gradient_position'] ?? 'center', 'left' ); ?>>Left</option>
								</select>
							</label>
						</div>
					</div>
				</div>

				<div class="wpr-option-box wpr-gradient-box">
					<div class="wpr-option-box-head">
						<label>
							<input type="checkbox" id="<?php echo esc_attr( 'wpr-' . $scope . '-gradient-enabled' ); ?>" data-style-field="gradient_enabled" value="1" <?php checked( (string) ( $values['gradient_enabled'] ?? '0' ), '1' ); ?>>
							<span><?php echo esc_html( wpr_t( 'Text-Verlauf' ) ); ?></span>
						</label>
						<small><?php echo esc_html( wpr_t( 'Optional statt normaler Textfarbe' ) ); ?></small>
					</div>

					<div class="wpr-gradient-fields">
						<div class="wpr-control-grid two">
							<?php $this->render_color( $scope, 'gradient_color_1', wpr_t( 'Farbe 1' ), $values['gradient_color_1'] ?? '#24afab' ); ?>
							<?php $this->render_color( $scope, 'gradient_color_2', wpr_t( 'Farbe 2' ), $values['gradient_color_2'] ?? '#6c5ce7' ); ?>
						</div>

						<div class="wpr-control-grid three">
							<label class="wpr-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-gradient-type' ); ?>">
								<span><?php echo esc_html( wpr_t( 'Verlaufsart' ) ); ?></span>
								<select id="<?php echo esc_attr( 'wpr-' . $scope . '-gradient-type' ); ?>" data-style-field="gradient_type">
									<option value="linear" <?php selected( $values['gradient_type'] ?? 'linear', 'linear' ); ?>>Linear</option>
									<option value="radial" <?php selected( $values['gradient_type'] ?? 'linear', 'radial' ); ?>>Radial</option>
								</select>
							</label>

							<?php $this->render_number( $scope, 'gradient_angle', wpr_t( 'Winkel' ), $values['gradient_angle'] ?? '90', '1' ); ?>

							<label class="wpr-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-gradient-position' ); ?>">
								<span><?php echo esc_html( wpr_t( 'Position' ) ); ?></span>
								<select id="<?php echo esc_attr( 'wpr-' . $scope . '-gradient-position' ); ?>" data-style-field="gradient_position">
									<option value="center" <?php selected( $values['gradient_position'] ?? 'center', 'center' ); ?>>Center</option>
									<option value="top" <?php selected( $values['gradient_position'] ?? 'center', 'top' ); ?>>Top</option>
									<option value="right" <?php selected( $values['gradient_position'] ?? 'center', 'right' ); ?>>Right</option>
									<option value="bottom" <?php selected( $values['gradient_position'] ?? 'center', 'bottom' ); ?>>Bottom</option>
									<option value="left" <?php selected( $values['gradient_position'] ?? 'center', 'left' ); ?>>Left</option>
								</select>
							</label>
						</div>
					</div>
				</div>

				<div class="wpr-option-box wpr-shadow-box">
					<div class="wpr-option-box-head">
						<label>
							<input type="checkbox" id="<?php echo esc_attr( 'wpr-' . $scope . '-text-shadow-enabled' ); ?>" data-style-field="text_shadow_enabled" value="1" <?php checked( (string) ( $values['text_shadow_enabled'] ?? '0' ), '1' ); ?>>
							<span><?php echo esc_html( wpr_t( 'Text Shadow' ) ); ?></span>
						</label>
						<small><?php echo esc_html( wpr_t( 'Optionaler Schatten für mehr Tiefe' ) ); ?></small>
					</div>

					<div class="wpr-shadow-fields">
						<?php $this->render_color( $scope, 'text_shadow_color', wpr_t( 'Schattenfarbe' ), $values['text_shadow_color'] ?? '#000000' ); ?>
						<div class="wpr-control-grid three">
							<?php $this->render_number( $scope, 'text_shadow_x', wpr_t( 'X in px' ), $values['text_shadow_x'] ?? '0', '1' ); ?>
							<?php $this->render_number( $scope, 'text_shadow_y', wpr_t( 'Y in px' ), $values['text_shadow_y'] ?? '2', '1' ); ?>
							<?php $this->render_number( $scope, 'text_shadow_blur', wpr_t( 'Blur in px' ), $values['text_shadow_blur'] ?? '8', '1' ); ?>
						</div>
					</div>
				</div>
			<?php $this->render_editor_panel_end(); ?>

			<?php $this->render_editor_panel_start( '↔', wpr_t( 'Abstände' ), wpr_t( 'Padding mit eigener Einheit' ), 'spacing' ); ?>
				<?php $this->render_box_control( $scope, 'padding', wpr_t( 'Padding' ), $values ); ?>
			<?php $this->render_editor_panel_end(); ?>

			<?php $this->render_editor_panel_start( '▢', wpr_t( 'Rahmen' ), wpr_t( 'Breite, Stil, Farbe und Radius' ), 'border' ); ?>
				<?php $this->render_box_control( $scope, 'border_width', wpr_t( 'Rahmenstärke' ), $values ); ?>

				<div class="wpr-control-grid two">
					<label class="wpr-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-border-style' ); ?>">
						<span><?php echo esc_html( wpr_t( 'Rahmenstil' ) ); ?></span>
						<select id="<?php echo esc_attr( 'wpr-' . $scope . '-border-style' ); ?>" data-style-field="border_style">
							<?php foreach ( WPR_Settings::allowed_border_styles() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $values['border_style'], $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</div>

				<div class="wpr-color-stack wpr-border-color-row">
					<?php $this->render_color( $scope, 'border_color', wpr_t( 'Rahmenfarbe' ), $values['border_color'] ); ?>
				</div>

				<?php $this->render_box_control( $scope, 'border_radius', wpr_t( 'Border Radius' ), $values ); ?>
			<?php $this->render_editor_panel_end(); ?>

			<?php $this->render_editor_panel_start( '✦', wpr_t( 'Text-Effekt' ), wpr_t( 'Animationen und moderne Texteffekte' ), 'effects' ); ?>
				<div class="wpr-control-grid four">
					<label class="wpr-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-text-effect' ); ?>">
						<span><?php echo esc_html( wpr_t( 'Effekt' ) ); ?></span>
						<select id="<?php echo esc_attr( 'wpr-' . $scope . '-text-effect' ); ?>" data-style-field="text_effect">
							<?php foreach ( WPR_Settings::allowed_effects() as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $values['text_effect'], $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>

					<?php $this->render_number( $scope, 'animation_duration', wpr_t( 'Dauer in ms' ), $values['animation_duration'], '50' ); ?>
					<?php $this->render_number( $scope, 'animation_delay', wpr_t( 'Verzögerung in ms' ), $values['animation_delay'], '50' ); ?>

					<label class="wpr-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-animation-timing' ); ?>">
						<span><?php echo esc_html( wpr_t( 'Timing' ) ); ?></span>
						<select id="<?php echo esc_attr( 'wpr-' . $scope . '-animation-timing' ); ?>" data-style-field="animation_timing">
							<option value="ease" <?php selected( $values['animation_timing'] ?? 'ease-in-out', 'ease' ); ?>>ease</option>
							<option value="linear" <?php selected( $values['animation_timing'] ?? 'ease-in-out', 'linear' ); ?>>linear</option>
							<option value="ease-in" <?php selected( $values['animation_timing'] ?? 'ease-in-out', 'ease-in' ); ?>>ease-in</option>
							<option value="ease-out" <?php selected( $values['animation_timing'] ?? 'ease-in-out', 'ease-out' ); ?>>ease-out</option>
							<option value="ease-in-out" <?php selected( $values['animation_timing'] ?? 'ease-in-out', 'ease-in-out' ); ?>>ease-in-out</option>
						</select>
					</label>
				</div>
					<label class="wpr-switch-row wpr-compact-switch">
						<input type="checkbox"
							id="<?php echo esc_attr( 'wpr-' . $scope . '-animation-loop' ); ?>"
							data-style-field="animation_loop"
							value="1"
							<?php checked( (string) ( $values['animation_loop'] ?? '0' ), '1' ); ?>
						>

						<span><?php echo esc_html( wpr_t( 'Animation in Schleife abspielen' ) ); ?></span>
					</label>
					<p class="description">
						<?php echo esc_html( wpr_t( 'Loop wird nur bei dauerhaft geeigneten Effekten wie Pulse, Glow, Shimmer, Wave oder Glitch angewendet.' ) ); ?>
					</p>			

				<div class="wpr-effect-extra-controls" data-effect-options-for="<?php echo esc_attr( $scope ); ?>">
					<div class="wpr-effect-option wpr-effect-option-color">
						<div class="wpr-color-stack">
							<?php $this->render_color( $scope, 'effect_color', wpr_t( 'Effektfarbe' ), $values['effect_color'] ?? '#24afab' ); ?>
						</div>
					</div>

					<div class="wpr-effect-option wpr-effect-option-color-2">
						<div class="wpr-color-stack">
							<?php $this->render_color( $scope, 'effect_color_2', wpr_t( 'Zweite Effektfarbe' ), $values['effect_color_2'] ?? '#6c5ce7' ); ?>
						</div>
					</div>

					<div class="wpr-effect-option wpr-effect-option-strength">
						<?php $this->render_range( $scope, 'effect_strength', wpr_t( 'Effektstärke in px' ), 0, 40, 1, $values['effect_strength'] ?? '12', 'px' ); ?>
					</div>

					<div class="wpr-effect-option wpr-effect-option-blur">
						<?php $this->render_range( $scope, 'effect_blur', wpr_t( 'Glow/Blur in px' ), 0, 60, 1, $values['effect_blur'] ?? '10', 'px' ); ?>
					</div>

					<p class="description wpr-effect-hint"><?php echo esc_html( wpr_t( 'Wähle zuerst einen Texteffekt. Passende Zusatzoptionen werden automatisch angezeigt.' ) ); ?></p>
				</div>
			<?php $this->render_editor_panel_end(); ?>



			<?php $this->render_editor_panel_start( '{}', wpr_t( 'CSS' ), wpr_t( 'Eigene Klassen, IDs und scoped CSS für dieses Wortpaar' ), 'customcss' ); ?>
				<div class="wpr-control-grid two">
					<label class="wpr-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-custom-class' ); ?>"><span><?php echo esc_html( wpr_t( 'Eigene CSS-Klasse' ) ); ?></span><input type="text" id="<?php echo esc_attr( 'wpr-' . $scope . '-custom-class' ); ?>" value="<?php echo esc_attr( $values['custom_class'] ?? '' ); ?>" data-style-field="custom_class" placeholder="keyword-badge"></label>
					<label class="wpr-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-custom-id' ); ?>"><span><?php echo esc_html( wpr_t( 'Eigene CSS-ID' ) ); ?></span><input type="text" id="<?php echo esc_attr( 'wpr-' . $scope . '-custom-id' ); ?>" value="<?php echo esc_attr( $values['custom_id'] ?? '' ); ?>" data-style-field="custom_id" placeholder="keyword-special"></label>
				</div>

				<p class="description"><strong><?php echo esc_html( wpr_t( 'Automatische Klasse:' ) ); ?></strong> <code><?php echo esc_html( str_replace( 'pair-', 'wpr-replaced-', $scope ) ); ?></code></p>

				<label class="wpr-control wpr-code-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-pair-custom-css' ); ?>">
					<span><?php echo esc_html( wpr_t( 'Eigenes CSS' ) ); ?></span>
					<textarea id="<?php echo esc_attr( 'wpr-' . $scope . '-pair-custom-css' ); ?>" rows="7" spellcheck="false" data-style-field="pair_custom_css" placeholder="color:#ff3366;&#10;text-shadow:0 0 12px rgba(255,51,102,.35);"><?php echo esc_textarea( $values['pair_custom_css'] ?? '' ); ?></textarea>
					<small><?php echo esc_html( wpr_t( 'Dieses CSS wird automatisch auf die Klasse dieses Wortpaares begrenzt. Nutze nur CSS-Deklarationen ohne Selektor.' ) ); ?></small>
				</label>
			<?php $this->render_editor_panel_end(); ?>

			<?php $this->render_editor_panel_start( '🔗', wpr_t( 'Link & SEO' ), wpr_t( 'Interne Verlinkung, Linkattribute und SEO-Optionen' ), 'linkseo' ); ?>
				<label class="wpr-switch-row wpr-compact-switch">
					<input type="checkbox" id="<?php echo esc_attr( 'wpr-' . $scope . '-link-enabled' ); ?>" data-style-field="link_enabled" value="1" <?php checked( (string) ( $values['link_enabled'] ?? '0' ), '1' ); ?>>
					<span><?php echo esc_html( wpr_t( 'Wortpaar verlinken' ) ); ?></span>
				</label>

				<div class="wpr-control-grid two">
					<label class="wpr-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-link-url' ); ?>">
						<span><?php echo esc_html( wpr_t( 'Link URL' ) ); ?></span>
						<input type="url" id="<?php echo esc_attr( 'wpr-' . $scope . '-link-url' ); ?>" value="<?php echo esc_attr( $values['link_url'] ?? '' ); ?>" data-style-field="link_url" placeholder="/seo/ oder https://example.com">
					</label>

					<label class="wpr-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-internal-link' ); ?>">
						<span><?php echo esc_html( wpr_t( 'Interne Seite suchen' ) ); ?></span>
						<select id="<?php echo esc_attr( 'wpr-' . $scope . '-internal-link' ); ?>" class="wpr-internal-link-select">
							<option value=""><?php echo esc_html( wpr_t( 'Seite oder Beitrag auswählen' ) ); ?></option>
							<?php foreach ( $this->get_internal_link_options() as $link_option ) : ?>
								<option value="<?php echo esc_url( $link_option['url'] ); ?>"><?php echo esc_html( $link_option['title'] . ' · ' . $link_option['type'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</div>

				<div class="wpr-control-grid two">
					<label class="wpr-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-link-target' ); ?>">
						<span><?php echo esc_html( wpr_t( 'Öffnen' ) ); ?></span>
						<select id="<?php echo esc_attr( 'wpr-' . $scope . '-link-target' ); ?>" data-style-field="link_target">
							<option value="_self" <?php selected( $values['link_target'] ?? '_self', '_self' ); ?>><?php echo esc_html( wpr_t( 'Gleiches Fenster' ) ); ?></option>
							<option value="_blank" <?php selected( $values['link_target'] ?? '_self', '_blank' ); ?>><?php echo esc_html( wpr_t( 'Neues Fenster' ) ); ?></option>
						</select>
					</label>
				</div>

				<div class="wpr-control-grid two">
					<label class="wpr-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-link-title' ); ?>"><span><?php echo esc_html( wpr_t( 'Link-Titel' ) ); ?></span><input type="text" id="<?php echo esc_attr( 'wpr-' . $scope . '-link-title' ); ?>" value="<?php echo esc_attr( $values['link_title'] ?? '' ); ?>" data-style-field="link_title"></label>
					<label class="wpr-control" for="<?php echo esc_attr( 'wpr-' . $scope . '-link-aria-label' ); ?>"><span><?php echo esc_html( wpr_t( 'ARIA Label' ) ); ?></span><input type="text" id="<?php echo esc_attr( 'wpr-' . $scope . '-link-aria-label' ); ?>" value="<?php echo esc_attr( $values['link_aria_label'] ?? '' ); ?>" data-style-field="link_aria_label"></label>
				</div>

				<div class="wpr-switch-grid">
					<label><input type="checkbox" id="<?php echo esc_attr( 'wpr-' . $scope . '-link-rel-nofollow' ); ?>" data-style-field="link_rel_nofollow" value="1" <?php checked( (string) ( $values['link_rel_nofollow'] ?? '0' ), '1' ); ?>> <span>nofollow</span></label>
					<label><input type="checkbox" id="<?php echo esc_attr( 'wpr-' . $scope . '-link-rel-sponsored' ); ?>" data-style-field="link_rel_sponsored" value="1" <?php checked( (string) ( $values['link_rel_sponsored'] ?? '0' ), '1' ); ?>> <span>sponsored</span></label>
					<label><input type="checkbox" id="<?php echo esc_attr( 'wpr-' . $scope . '-link-rel-noopener' ); ?>" data-style-field="link_rel_noopener" value="1" <?php checked( (string) ( $values['link_rel_noopener'] ?? '1' ), '1' ); ?>> <span>noopener</span></label>
				</div>

				<div class="wpr-control-grid two">
					<?php $this->render_color( $scope, 'link_color', wpr_t( 'Linkfarbe' ), $values['link_color'] ?? '' ); ?>
					<?php $this->render_color( $scope, 'link_hover_color', wpr_t( 'Hover-Farbe' ), $values['link_hover_color'] ?? '' ); ?>
				</div>

			<?php $this->render_editor_panel_end(); ?>

		</div>
		<?php
	}

	private function render_editor_panel_start( string $icon, string $title, string $description, string $reset_group ): void {
		?>
		<details class="wpr-editor-panel" data-reset-group="<?php echo esc_attr( $reset_group ); ?>">
			<summary>
				<span class="wpr-panel-title">
					<span class="wpr-panel-icon" aria-hidden="true"><?php echo esc_html( $icon ); ?></span>
					<span>
						<strong><?php echo esc_html( $title ); ?></strong>
						<small><?php echo esc_html( $description ); ?></small>
					</span>
				</span>
				<span class="wpr-panel-actions">
					<button type="button" class="button wpr-reset-section" data-reset-group="<?php echo esc_attr( $reset_group ); ?>"><?php echo esc_html( wpr_t( 'Zurücksetzen' ) ); ?></button>
					<span class="wpr-edit-icon" aria-hidden="true">✎</span>
				</span>
			</summary>
			<div class="wpr-editor-panel-body">
		<?php
	}

	private function render_editor_panel_end(): void {
		?>
			</div>
		</details>
		<?php
	}

	private function render_value_unit( string $scope, string $key, string $label, string $value, string $unit ): void {
		$id = 'wpr-' . $scope . '-' . str_replace( '_', '-', $key );
		?>
		<label class="wpr-control wpr-unit-control" for="<?php echo esc_attr( $id ); ?>">
			<span><?php echo esc_html( $label ); ?></span>
			<div>
				<input type="number" id="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $value ); ?>" min="0" step="0.1" data-style-field="<?php echo esc_attr( $key ); ?>">
				<select id="<?php echo esc_attr( $id . '-unit' ); ?>" data-style-field="<?php echo esc_attr( $key . '_unit' ); ?>">
					<?php foreach ( WPR_Settings::allowed_units() as $allowed_unit ) : ?>
						<option value="<?php echo esc_attr( $allowed_unit ); ?>" <?php selected( $unit, $allowed_unit ); ?>><?php echo esc_html( $allowed_unit ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</label>
		<?php
	}

	private function render_number( string $scope, string $key, string $label, string $value, string $step ): void {
		$id = 'wpr-' . $scope . '-' . str_replace( '_', '-', $key );
		?>
		<label class="wpr-control" for="<?php echo esc_attr( $id ); ?>">
			<span><?php echo esc_html( $label ); ?></span>
			<input type="number" id="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $value ); ?>" min="0" step="<?php echo esc_attr( $step ); ?>" data-style-field="<?php echo esc_attr( $key ); ?>">
		</label>
		<?php
	}

	private function render_range( string $scope, string $key, string $label, float $min, float $max, float $step, string $value, string $unit ): void {
		$id = 'wpr-' . $scope . '-' . str_replace( '_', '-', $key );
		?>
		<label for="<?php echo esc_attr( $id ); ?>" class="wpr-range-label">
			<span><?php echo esc_html( $label ); ?></span>
			<strong data-output-for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $value . $unit ); ?></strong>
			<input type="range" id="<?php echo esc_attr( $id ); ?>" min="<?php echo esc_attr( (string) $min ); ?>" max="<?php echo esc_attr( (string) $max ); ?>" step="<?php echo esc_attr( (string) $step ); ?>" value="<?php echo esc_attr( $value ); ?>" data-unit="<?php echo esc_attr( $unit ); ?>" data-style-field="<?php echo esc_attr( $key ); ?>">
		</label>
		<?php
	}

	private function render_color( string $scope, string $key, string $label, string $value ): void {
		$id          = 'wpr-' . $scope . '-' . str_replace( '_', '-', $key );
		$color_value = sanitize_hex_color( $value ) ?: '';
		$swatch      = '' !== $color_value ? $color_value : '#ffffff';
		?>
		<div class="wpr-color-row<?php echo '' === $color_value ? ' is-empty' : ''; ?>">
			<div class="wpr-color-row-label">
				<strong><?php echo esc_html( $label ); ?></strong>
				<small><?php echo esc_html( $key ); ?></small>
			</div>
			<div class="wpr-color-row-picker">
				<input type="hidden" id="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $color_value ); ?>" class="wpr-color-field" data-default-color="<?php echo esc_attr( $color_value ); ?>" data-style-field="<?php echo esc_attr( $key ); ?>">
				<button type="button" class="wpr-color-swatch" style="--wpr-current-color: <?php echo esc_attr( $swatch ); ?>; background-color: <?php echo esc_attr( $swatch ); ?>;" aria-label="<?php echo esc_attr( sprintf( wpr_t( 'Farbe für %s wählen' ), $label ) ); ?>"></button>
				<div class="wpr-modern-color-popover" aria-hidden="true">
					<input type="color" class="wpr-color-native" value="<?php echo esc_attr( $swatch ); ?>" aria-label="<?php echo esc_attr( sprintf( wpr_t( 'Farbwert für %s' ), $label ) ); ?>">
					<div class="wpr-color-popover-row">
						<input type="text" class="wpr-color-hex" value="<?php echo esc_attr( $color_value ); ?>" placeholder="#24afab" inputmode="text">
						<button type="button" class="button wpr-color-copy" title="<?php echo esc_attr( wpr_t( 'HEX-Wert kopieren' ) ); ?>">⧉</button>
					</div>
					<button type="button" class="button wpr-color-clear"><?php echo esc_html( wpr_t( 'Leeren' ) ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}

	private function render_box_control( string $scope, string $base_key, string $label, array $values ): void {
		$unit_key = $base_key . '_unit';
		?>
		<div class="wpr-box-control" data-box="<?php echo esc_attr( $base_key ); ?>">
			<div class="wpr-box-head">
				<strong><?php echo esc_html( $label ); ?></strong>
				<div class="wpr-box-tools">
					<button type="button" class="button wpr-link-box is-linked" data-box-target="<?php echo esc_attr( $base_key ); ?>" aria-pressed="true" title="<?php echo esc_attr( wpr_t( 'Werte koppeln' ) ); ?>">🔒</button>
					<select id="<?php echo esc_attr( 'wpr-' . $scope . '-' . str_replace( '_', '-', $unit_key ) ); ?>" data-style-field="<?php echo esc_attr( $unit_key ); ?>">
						<?php foreach ( WPR_Settings::allowed_units() as $unit ) : ?>
							<option value="<?php echo esc_attr( $unit ); ?>" <?php selected( $values[ $unit_key ], $unit ); ?>><?php echo esc_html( $unit ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="wpr-box-grid">
				<?php
				$parts = array(
					't' => wpr_t( 'Oben' ),
					'r' => wpr_t( 'Rechts' ),
					'b' => wpr_t( 'Unten' ),
					'l' => wpr_t( 'Links' ),
				);

				foreach ( $parts as $part => $part_label ) :
					$key = $base_key . '_' . $part;
					$id  = 'wpr-' . $scope . '-' . str_replace( '_', '-', $key );
					?>
					<label for="<?php echo esc_attr( $id ); ?>">
						<span><?php echo esc_html( $part_label ); ?></span>
						<input type="number" id="<?php echo esc_attr( $id ); ?>" value="<?php echo esc_attr( $values[ $key ] ?? '0' ); ?>" min="0" step="0.1" data-style-field="<?php echo esc_attr( $key ); ?>" data-box-field="<?php echo esc_attr( $base_key ); ?>">
					</label>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	private function regenerate_css_safely(): void {
		if ( ! class_exists( 'WPR_CSS' ) ) {
			return;
		}

		try {
			WPR_CSS::generate();
		} catch ( Throwable $e ) {
			// CSS generation errors are intentionally ignored to avoid interrupting responses.
}
	}

	public function ajax_regenerate_css(): void {
		$this->verify_ajax_request();

		if ( ! class_exists( 'WPR_CSS' ) ) {
			wp_send_json_error(
				array(
					'message' => wpr_t( 'CSS-Klasse wurde nicht gefunden.' ),
				),
				500
			);
		}

		$result = WPR_CSS::generate();
		$paths  = WPR_CSS::upload_dir();

		if ( ! $result || empty( $paths['file'] ) || ! file_exists( $paths['file'] ) ) {
			wp_send_json_error(
				array(
					'message' => wpr_t( 'CSS-Datei konnte nicht erstellt werden. Bitte Upload-Verzeichnis und Dateirechte prüfen.' ),
				),
				500
			);
		}

		wp_send_json_success(
			array(
				'message' => wpr_t( 'CSS-Datei wurde erfolgreich neu generiert.' ),
				'url'     => esc_url_raw( $paths['href'] ),
			)
		);
	}	
	

	public function ajax_submit_support_ticket(): void {
		$this->verify_ajax_request();

		$post_data = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
		$post_data = is_array( $post_data ) ? wp_unslash( $post_data ) : array();

		if ( ! empty( $post_data['company'] ) ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Support request could not be processed.' ) ), 400 );
		}

		$name     = isset( $post_data['name'] ) ? sanitize_text_field( $post_data['name'] ) : '';
		$email    = isset( $post_data['email'] ) ? sanitize_email( $post_data['email'] ) : '';
		$website  = isset( $post_data['website'] ) ? esc_url_raw( $post_data['website'] ) : '';
		$type     = isset( $post_data['type'] ) ? sanitize_key( $post_data['type'] ) : 'other';
		$priority = isset( $post_data['priority'] ) ? sanitize_key( $post_data['priority'] ) : 'normal';
		$message  = isset( $post_data['message'] ) ? sanitize_textarea_field( $post_data['message'] ) : '';
		$include_diagnostics = ! empty( $post_data['include_diagnostics'] );
		$client_timezone = isset( $post_data['client_timezone'] ) ? sanitize_text_field( $post_data['client_timezone'] ) : '';
		$client_local_time = isset( $post_data['client_local_time'] ) ? sanitize_text_field( $post_data['client_local_time'] ) : '';

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Please enter a valid email address.' ) ), 400 );
		}

		if ( '' === trim( $message ) ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Please describe your request.' ) ), 400 );
		}

		if ( strlen( $message ) > 5000 ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Please keep your message below 5000 characters.' ) ), 400 );
		}

		$rate_key = $this->support_rate_limit_key( $email );
		$rate_count = (int) get_transient( $rate_key );
		if ( $rate_count >= 3 ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Too many support requests. Please try again later.' ) ), 429 );
		}
		set_transient( $rate_key, $rate_count + 1, HOUR_IN_SECONDS );

		$type_labels = array(
			'bug'     => 'Bug / Error',
			'ui'      => 'Display / UI',
			'feature' => 'Feature request',
			'license' => 'License / Premium',
			'other'   => 'Other',
		);
		$priority_labels = array(
			'normal'   => 'Normal',
			'high'     => 'High',
			'critical' => 'Critical',
		);

		$ticket_meta = $this->generate_support_ticket_id();
		$diagnostics = $include_diagnostics ? $this->collect_support_diagnostics() : array();
		$conflicts   = $include_diagnostics ? $this->detect_plugin_conflicts() : array();

		$ticket = array(
			'reference'          => $ticket_meta['id'],
			'domain_hash'        => $ticket_meta['domain_hash'],
			'counter'            => $ticket_meta['counter'],
			'name'               => $name,
			'email'              => $email,
			'website'            => $website,
			'type'               => $type,
			'type_label'         => $this->normalize_support_label( $type, $type_labels ),
			'priority'           => $priority,
			'priority_label'     => $this->normalize_support_label( $priority, $priority_labels ),
			'message'            => $message,
			'diagnostics'        => $diagnostics,
			'conflicts'          => $conflicts,
			'client_timezone'    => $client_timezone,
			'client_local_time'  => $client_local_time,
		);

		$recipient = $this->support_ticket_recipient();
		$subject   = '[' . $ticket['reference'] . '] ' . $ticket['type_label'];
		$headers   = array(
			'Content-Type: text/plain; charset=UTF-8',
			'Reply-To: ' . ( '' !== $name ? $name : 'WordPair Replacer User' ) . ' <' . $email . '>',
		);

		$sent_internal = wp_mail( $recipient, $subject, $this->build_internal_support_email( $ticket ), $headers );

		$user_headers = array(
			'Content-Type: text/plain; charset=UTF-8',
			'Reply-To: NickDesignz Plugins <' . $recipient . '>',
		);
		$sent_copy = wp_mail(
			$email,
			'[' . $ticket['reference'] . '] Your support request has been received',
			$this->build_user_support_email( $ticket ),
			$user_headers
		);

		$tickets = get_option( 'wpr_support_tickets', array() );
		$tickets = is_array( $tickets ) ? $tickets : array();
		array_unshift(
			$tickets,
			array(
				'reference'        => $ticket['reference'],
				'created_at'       => current_time( 'mysql' ),
				'name'             => $name,
				'email'            => $email,
				'website'          => $website,
				'type'             => $type,
				'type_label'       => $ticket['type_label'],
				'priority'         => $priority,
				'priority_label'   => $ticket['priority_label'],
				'message'          => $message,
				'diagnostics_sent' => $include_diagnostics ? 1 : 0,
				'status'           => 'open',
				'sent'             => $sent_internal ? 1 : 0,
				'copy_sent'        => $sent_copy ? 1 : 0,
			)
		);
		$tickets = array_slice( $tickets, 0, 50 );
		update_option( 'wpr_support_tickets', $tickets, false );

		if ( ! $sent_internal ) {
			wp_send_json_error(
				array(
					'message'   => wpr_t( 'The ticket was saved locally, but the email could not be sent. Please check your WordPress mail configuration.' ),
					'reference' => $ticket['reference'],
				),
				500
			);
		}

		wp_send_json_success(
			array(
				'message'   => wpr_t( 'Support request sent.' ),
				'reference' => $ticket['reference'],
				'history'   => $this->render_support_ticket_history_html(),
			)
		);
	}



	private function render_support_ticket_history_html(): string {
		$tickets = get_option( 'wpr_support_tickets', array() );
		$tickets = is_array( $tickets ) ? $tickets : array();

		usort(
			$tickets,
			static function ( array $a, array $b ): int {
				$status_a = $a['status'] ?? 'open';
				$status_b = $b['status'] ?? 'open';
				if ( $status_a !== $status_b ) {
					return 'open' === $status_a ? -1 : 1;
				}
				return strcmp( (string) ( $b['created_at'] ?? '' ), (string) ( $a['created_at'] ?? '' ) );
			}
		);

		$open     = 0;
		$resolved = 0;
		foreach ( $tickets as $ticket ) {
			if ( 'resolved' === ( $ticket['status'] ?? 'open' ) ) {
				$resolved++;
			} else {
				$open++;
			}
		}

		ob_start();
		?>
		<details class="wpr-card wpr-ticket-history-card wpr-panel-accordion wpr-ticket-history-accordion" open>
			<summary class="wpr-ticket-history-summary wpr-panel-summary">
				<span class="wpr-ticket-history-title wpr-panel-title"><i aria-hidden="true">☰</i><span><small><?php echo esc_html( wpr_t( 'Local History' ) ); ?></small><?php echo esc_html( wpr_t( 'Your Tickets' ) ); ?></span></span>
				<span class="wpr-ticket-history-meta wpr-panel-meta"><small><?php echo esc_html( sprintf( wpr_t( '%d ticket(s)' ), count( $tickets ) ) ); ?></small><b aria-hidden="true"></b></span>
			</summary>
			<div class="wpr-ticket-history-body">
				<div class="wpr-ticket-filter-pills" aria-label="Ticket filters">
					<button type="button" class="is-active" data-ticket-filter="open"><?php echo esc_html( wpr_t( 'Open' ) ); ?> (<?php echo esc_html( (string) $open ); ?>)</button>
					<button type="button" data-ticket-filter="resolved"><?php echo esc_html( wpr_t( 'Resolved' ) ); ?> (<?php echo esc_html( (string) $resolved ); ?>)</button>
					<button type="button" data-ticket-filter="all"><?php echo esc_html( wpr_t( 'All' ) ); ?> (<?php echo esc_html( (string) count( $tickets ) ); ?>)</button>
				</div>

			<?php if ( empty( $tickets ) ) : ?>
				<p class="description"><?php echo esc_html( wpr_t( 'No local support tickets have been created from this website yet.' ) ); ?></p>
			<?php else : ?>
				<div class="wpr-ticket-list">
					<?php foreach ( $tickets as $ticket ) :
						$status = $ticket['status'] ?? 'open';
						$type_label = $ticket['type_label'] ?? ucfirst( (string) ( $ticket['type'] ?? 'Other' ) );
					?>
						<details class="wpr-ticket-item" data-ticket-status="<?php echo esc_attr( $status ); ?>">
							<summary>
								<span class="wpr-ticket-main"><strong><?php echo esc_html( (string) ( $ticket['reference'] ?? '' ) ); ?></strong><small><?php echo esc_html( $type_label ); ?> • <?php echo esc_html( (string) ( $ticket['created_at'] ?? '' ) ); ?></small></span>
								<span class="wpr-ticket-status is-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span>
							</summary>
							<div class="wpr-ticket-details">
								<p><?php echo nl2br( esc_html( (string) ( $ticket['message'] ?? '' ) ) ); ?></p>
								<div class="wpr-ticket-actions">
									<button type="button" class="button wpr-copy-ticket-id" data-ticket-id="<?php echo esc_attr( (string) ( $ticket['reference'] ?? '' ) ); ?>"><?php echo esc_html( wpr_t( 'Copy Ticket ID' ) ); ?></button>
									<?php if ( 'resolved' === $status ) : ?>
										<button type="button" class="button wpr-ticket-status-toggle" data-ticket-id="<?php echo esc_attr( (string) ( $ticket['reference'] ?? '' ) ); ?>" data-status="open"><?php echo esc_html( wpr_t( 'Reopen' ) ); ?></button>
									<?php else : ?>
										<button type="button" class="button wpr-ticket-status-toggle" data-ticket-id="<?php echo esc_attr( (string) ( $ticket['reference'] ?? '' ) ); ?>" data-status="resolved"><?php echo esc_html( wpr_t( 'Mark as resolved' ) ); ?></button>
									<?php endif; ?>
								</div>
							</div>
						</details>
					<?php endforeach; ?>
				</div>
				<p class="description"><?php echo esc_html( wpr_t( 'This local ticket history only shows requests submitted from this website. Support replies are sent by email.' ) ); ?></p>
			<?php endif; ?>
			</div>
		</details>
		<?php
		return (string) ob_get_clean();
	}

	public function ajax_update_support_ticket_status(): void {
		$this->verify_ajax_request();

		$reference = filter_input( INPUT_POST, 'reference', FILTER_UNSAFE_RAW );
		$reference = null !== $reference && false !== $reference ? sanitize_text_field( wp_unslash( $reference ) ) : '';
		$status    = filter_input( INPUT_POST, 'status', FILTER_UNSAFE_RAW );
		$status    = null !== $status && false !== $status ? sanitize_key( wp_unslash( $status ) ) : 'open';
		$status    = in_array( $status, array( 'open', 'resolved' ), true ) ? $status : 'open';

		if ( '' === $reference ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Invalid ticket ID.' ) ), 400 );
		}

		$tickets = get_option( 'wpr_support_tickets', array() );
		$tickets = is_array( $tickets ) ? $tickets : array();
		$updated = false;

		foreach ( $tickets as &$ticket ) {
			if ( isset( $ticket['reference'] ) && $reference === $ticket['reference'] ) {
				$ticket['status'] = $status;
				$ticket['status_changed_at'] = current_time( 'mysql' );
				$updated = true;
				break;
			}
		}
		unset( $ticket );

		if ( ! $updated ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Ticket not found.' ) ), 404 );
		}

		update_option( 'wpr_support_tickets', $tickets, false );

		wp_send_json_success(
			array(
				'message' => wpr_t( 'Ticket status updated.' ),
				'html'    => $this->render_support_ticket_history_html(),
			)
		);
	}


	private function preset_table(): string {
		return WPR_Activator::presets_table_name();
	}

	private function sanitize_preset_payload( array $data ): array {
		$defaults = WPR_Settings::style_defaults();
		$style    = isset( $data['style'] ) && is_array( $data['style'] ) ? $data['style'] : $data;
		$style    = array_intersect_key( $style, $defaults );

		return WPR_Settings::sanitize_pair_style( array_merge( array( 'use_custom_style' => 1 ), $style ) );
	}

	private function preset_badges( array $style ): array {
		$badges = array();
		$checks = array(
			'Typography' => array( 'font_family', 'font_size', 'font_weight', 'line_height', 'font_style', 'text_decoration', 'text_transform', 'letter_spacing', 'word_spacing', 'white_space' ),
			'Colors'     => array( 'color', 'background_color', 'gradient_enabled', 'bg_gradient_enabled', 'text_shadow_enabled' ),
			'Spacing'    => array( 'padding_t', 'padding_r', 'padding_b', 'padding_l' ),
			'Border'     => array( 'border_width', 'border_width_t', 'border_width_r', 'border_width_b', 'border_width_l', 'border_color', 'border_radius_t', 'border_radius_r', 'border_radius_b', 'border_radius_l' ),
			'Effects'    => array( 'text_effect' ),
			'CSS'        => array( 'custom_class', 'custom_id', 'pair_custom_css' ),
			'Link & SEO' => array( 'link_enabled', 'link_url', 'link_title', 'link_aria_label', 'link_rel_nofollow', 'link_rel_sponsored', 'link_rel_noopener' ),
		);
		$defaults = WPR_Settings::style_defaults();

		foreach ( $checks as $label => $keys ) {
			foreach ( $keys as $key ) {
				$value   = isset( $style[ $key ] ) ? (string) $style[ $key ] : '';
				$default = isset( $defaults[ $key ] ) ? (string) $defaults[ $key ] : '';
				if ( '' !== trim( $value ) && $value !== $default && 'none' !== $value && '0' !== $value ) {
					$badges[] = $label;
					break;
				}
			}
		}

		return empty( $badges ) ? array( 'Clean' ) : array_values( array_unique( $badges ) );
	}

	private function get_presets_for_response(): array {
		global $wpdb;

		$table = $this->preset_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY readonly DESC, name ASC", ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return array();
		}

		$presets = array();
		foreach ( $rows as $row ) {
			$decoded = json_decode( (string) ( $row['preset_json'] ?? '' ), true );
			$style   = is_array( $decoded ) && isset( $decoded['style'] ) && is_array( $decoded['style'] ) ? $decoded['style'] : array();
			$style   = array_replace( WPR_Settings::style_defaults(), $style );
			$presets[] = array(
				'id'              => absint( $row['id'] ),
				'slug'            => sanitize_title( $row['slug'] ?? '' ),
				'name'            => sanitize_text_field( $row['name'] ?? '' ),
				'description'     => sanitize_textarea_field( $row['description'] ?? '' ),
				'preview_keyword' => sanitize_text_field( $row['preview_keyword'] ?? 'WordPress' ),
				'readonly'        => ! empty( $row['readonly'] ) ? 1 : 0,
				'style'           => $style,
				'badges'          => $this->preset_badges( $style ),
			);
		}

		return $presets;
	}

	public function ajax_get_presets(): void {
		$this->verify_ajax_request();
		wp_send_json_success( array( 'presets' => $this->get_presets_for_response() ) );
	}

	public function ajax_save_preset(): void {
		$this->verify_ajax_request();
		global $wpdb;

		$name = filter_input( INPUT_POST, 'name', FILTER_UNSAFE_RAW );
		$name = null !== $name && false !== $name ? sanitize_text_field( wp_unslash( $name ) ) : '';
		if ( '' === $name ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Preset name is required.' ) ), 400 );
		}

		$description = filter_input( INPUT_POST, 'description', FILTER_UNSAFE_RAW );
		$description = null !== $description && false !== $description ? sanitize_textarea_field( wp_unslash( $description ) ) : '';
		$preview = filter_input( INPUT_POST, 'preview_keyword', FILTER_UNSAFE_RAW );
		$preview = null !== $preview && false !== $preview ? sanitize_text_field( wp_unslash( $preview ) ) : 'WordPress';
		$json = filter_input( INPUT_POST, 'preset_data', FILTER_UNSAFE_RAW );
		$json = null !== $json && false !== $json ? wp_unslash( $json ) : '';
		$data = json_decode( (string) $json, true );
		if ( ! is_array( $data ) ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Invalid preset data.' ) ), 400 );
		}

		$style = $this->sanitize_preset_payload( $data );
		$slug  = sanitize_title( $name . '-' . substr( wp_hash( microtime( true ) . wp_rand() ), 0, 6 ) );
		$table = $this->preset_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->insert(
			$table,
			array(
				'slug'            => $slug,
				'name'            => $name,
				'description'     => $description,
				'preview_keyword' => $preview,
				'preset_json'     => wp_json_encode( array( 'style' => $style ), JSON_UNESCAPED_SLASHES ),
				'readonly'        => 0,
				'created_at'      => current_time( 'mysql' ),
				'updated_at'      => current_time( 'mysql' ),
			)
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Preset could not be saved.' ) ), 500 );
		}

		wp_send_json_success( array( 'message' => wpr_t( 'Preset saved.' ), 'presets' => $this->get_presets_for_response() ) );
	}

	public function ajax_import_preset(): void {
		$this->verify_ajax_request();
		global $wpdb;

		$json = filter_input( INPUT_POST, 'preset_json', FILTER_UNSAFE_RAW );
		$json = null !== $json && false !== $json ? wp_unslash( $json ) : '';
		$data = json_decode( (string) $json, true );
		if ( ! is_array( $data ) ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Invalid preset JSON.' ) ), 400 );
		}

		$preset = isset( $data['preset'] ) && is_array( $data['preset'] ) ? $data['preset'] : $data;
		$name = sanitize_text_field( $preset['name'] ?? 'Imported Preset' );
		if ( '' === $name ) {
			$name = 'Imported Preset';
		}
		$description = sanitize_textarea_field( $preset['description'] ?? '' );
		$preview = sanitize_text_field( $preset['preview_keyword'] ?? 'WordPress' );
		$style = $this->sanitize_preset_payload( array( 'style' => ( $preset['style'] ?? $preset ) ) );
		$slug  = sanitize_title( $name . '-' . substr( wp_hash( microtime( true ) . wp_rand() ), 0, 6 ) );
		$table = $this->preset_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->insert(
			$table,
			array(
				'slug'            => $slug,
				'name'            => $name,
				'description'     => $description,
				'preview_keyword' => '' !== $preview ? $preview : 'WordPress',
				'preset_json'     => wp_json_encode( array( 'style' => $style ), JSON_UNESCAPED_SLASHES ),
				'readonly'        => 0,
				'created_at'      => current_time( 'mysql' ),
				'updated_at'      => current_time( 'mysql' ),
			)
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Preset could not be imported.' ) ), 500 );
		}

		wp_send_json_success( array( 'message' => wpr_t( 'Preset imported.' ), 'presets' => $this->get_presets_for_response() ) );
	}

	public function ajax_delete_preset(): void {
		$this->verify_ajax_request();
		global $wpdb;
		$id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		$id = false !== $id && null !== $id ? absint( $id ) : 0;
		if ( $id <= 0 ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Invalid preset ID.' ) ), 400 );
		}
		$table = $this->preset_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete( $table, array( 'id' => $id, 'readonly' => 0 ), array( '%d', '%d' ) );
		if ( false === $deleted ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Preset could not be deleted.' ) ), 500 );
		}
		wp_send_json_success( array( 'message' => wpr_t( 'Preset deleted.' ), 'presets' => $this->get_presets_for_response() ) );
	}

	private function verify_ajax_request(): void {
		check_ajax_referer( 'wpr_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Keine Berechtigung.' ) ), 403 );
		}
	}

	public function ajax_get_pairs(): void {
		$this->verify_ajax_request();

		global $wpdb;

		$table = WPR_Activator::table_name();

		$query = 'SELECT *
			FROM ' . $table . '
			ORDER BY id DESC';

		$items = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! is_array( $items ) ) {
			wp_send_json_success( array( 'items' => array() ) );
		}

		foreach ( $items as &$item ) {
			if ( empty( $item['original_word'] ) && ! empty( $item['original_en'] ) ) {
				$item['original_word'] = $item['original_en'];
			}

			if ( empty( $item['replacement_word'] ) && ! empty( $item['replacement_en'] ) ) {
				$item['replacement_word'] = $item['replacement_en'];
			}

			if ( empty( $item['original_word'] ) && ! empty( $item['original_de'] ) ) {
				$item['original_word'] = $item['original_de'];
			}

			if ( empty( $item['replacement_word'] ) && ! empty( $item['replacement_de'] ) ) {
				$item['replacement_word'] = $item['replacement_de'];
			}
		}
		unset( $item );

		wp_send_json_success( array( 'items' => $items ) );
	}

	public function ajax_save_pair(): void {
		$this->verify_ajax_request();

		global $wpdb;

		$table_name       = WPR_Activator::table_name();
		$id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		$id = false !== $id && null !== $id ? absint( $id ) : 0;
		$original_word = filter_input( INPUT_POST, 'original_word', FILTER_UNSAFE_RAW );
		$original_word = null !== $original_word && false !== $original_word
			? sanitize_text_field( wp_unslash( $original_word ) )
			: '';

		$replacement_word = filter_input( INPUT_POST, 'replacement_word', FILTER_UNSAFE_RAW );
		$replacement_word = null !== $replacement_word && false !== $replacement_word
			? sanitize_text_field( wp_unslash( $replacement_word ) )
			: '';

		if ( '' === $original_word || '' === $replacement_word ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Originalwort und Ersatzwort sind Pflichtfelder.' ) ), 400 );
		}

		$post_data = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
		$post_data = is_array( $post_data ) ? wp_unslash( $post_data ) : array();

		$style = WPR_Settings::sanitize_pair_style( $post_data );

		$data = array_merge(
			array(
				'original_word'    => $original_word,
				'replacement_word' => $replacement_word,
				'is_active'        => isset( $post_data['is_active'] ) ? min( 1, absint( $post_data['is_active'] ) ) : 0,
				'case_sensitive'   => isset( $post_data['case_sensitive'] ) ? min( 1, absint( $post_data['case_sensitive'] ) ) : 0,
				'whole_word'       => isset( $post_data['whole_word'] ) ? min( 1, absint( $post_data['whole_word'] ) ) : 0,
				'updated_at'       => current_time( 'mysql' ),
			),
			$style
		);

		if ( $id > 0 ) {
			$result = $wpdb->update( $table_name, $data, array( 'id' => $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		} else {
			$data['created_at'] = current_time( 'mysql' );
			$result = $wpdb->insert( $table_name, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$id                 = (int) $wpdb->insert_id;
		}

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Speichern fehlgeschlagen.' ) ), 500 );
		}

		$this->regenerate_css_safely();

		wp_send_json_success( array( 'message' => wpr_t( 'Gespeichert.' ), 'id' => $id ) );
	}

	public function ajax_delete_pair(): void {
		$this->verify_ajax_request();

		global $wpdb;

		$id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		$id = false !== $id && null !== $id ? absint( $id ) : 0;

		if ( $id <= 0 ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Ungültige ID.' ) ), 400 );
		}

		$result = $wpdb->delete( WPR_Activator::table_name(), array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Löschen fehlgeschlagen.' ) ), 500 );
		}

		$this->regenerate_css_safely();

		wp_send_json_success( array( 'message' => wpr_t( 'Gelöscht.' ) ) );
	}

	public function ajax_save_language(): void {
		$this->verify_ajax_request();

		$language = filter_input( INPUT_POST, 'plugin_language', FILTER_UNSAFE_RAW );
		$language = null !== $language && false !== $language
			? sanitize_text_field( wp_unslash( $language ) )
			: 'en_US';

		WPR_Settings::update_language( $language );

		wp_send_json_success( array( 'message' => wpr_t( 'Sprache gespeichert.' ) ) );
	}

	public function ajax_toggle_pair(): void {
		$this->verify_ajax_request();

		global $wpdb;

		$id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		$id = false !== $id && null !== $id ? absint( $id ) : 0;

		$is_active = filter_input( INPUT_POST, 'is_active', FILTER_VALIDATE_INT );
		$is_active = false !== $is_active && null !== $is_active ? min( 1, absint( $is_active ) ) : 0;

		if ( $id <= 0 ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Ungültige ID.' ) ), 400 );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			WPR_Activator::table_name(),
			array(
				'is_active'  => $is_active,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Speichern fehlgeschlagen.' ) ), 500 );
		}

		$this->regenerate_css_safely();

		wp_send_json_success( array( 'message' => wpr_t( 'Gespeichert.' ), 'is_active' => $is_active ) );
	}

	public function ajax_save_pair_style(): void {
		$this->verify_ajax_request();

		global $wpdb;

		$id = filter_input( INPUT_POST, 'id', FILTER_VALIDATE_INT );
		$id = false !== $id && null !== $id ? absint( $id ) : 0;

		if ( $id <= 0 ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Ungültige ID.' ) ), 400 );
		}

		$post_data = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
		$post_data = is_array( $post_data ) ? wp_unslash( $post_data ) : array();

		$style = WPR_Settings::sanitize_pair_style( $post_data );
		$style['use_custom_style'] = 1;
		$style['updated_at']       = current_time( 'mysql' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			WPR_Activator::table_name(),
			$style,
			array( 'id' => $id )
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => wpr_t( 'Speichern fehlgeschlagen.' ) ), 500 );
		}

		$this->regenerate_css_safely();

		wp_send_json_success( array( 'message' => wpr_t( 'Gespeichert.' ), 'id' => $id ) );
	}


	public function ajax_save_google_fonts(): void {
		$this->verify_ajax_request();

		$current = WPR_Settings::get();
		$post_data = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
		$post_data = is_array( $post_data ) ? wp_unslash( $post_data ) : array();

		$current['enable_google_fonts'] = ! empty( $post_data['enable_google_fonts'] ) ? 1 : 0;

		WPR_Settings::update( $current );
		$this->regenerate_css_safely();

		$message = ! empty( $current['enable_google_fonts'] )
			? wpr_t( 'Google Fonts enabled.' )
			: wpr_t( 'Google Fonts disabled.' );

		wp_send_json_success(
			array(
				'message' => $message,
				'enable_google_fonts' => (int) $current['enable_google_fonts'],
			)
		);
	}

	public function ajax_save_settings(): void {
		$this->verify_ajax_request();

		$current = WPR_Settings::get();
		$post_data = filter_input_array( INPUT_POST, FILTER_UNSAFE_RAW );
		$post_data = is_array( $post_data ) ? wp_unslash( $post_data ) : array();

		$input = array(
			'plugin_language'                  => $current['plugin_language'] ?? 'en_US',
			'enable_google_fonts'              => isset( $post_data['enable_google_fonts'] ) ? absint( $post_data['enable_google_fonts'] ) : 0,
			'security_monitor_enabled'         => isset( $post_data['security_monitor_enabled'] ) ? absint( $post_data['security_monitor_enabled'] ) : 0,
			'global_style'                     => array(),
			'custom_css'                       => isset( $post_data['custom_css'] ) ? $post_data['custom_css'] : '',
		);

		foreach ( array_keys( WPR_Settings::style_defaults() ) as $field ) {
			$key = 'global_' . $field;
			$input['global_style'][ $field ] = isset( $post_data[ $key ] ) ? $post_data[ $key ] : WPR_Settings::style_defaults()[ $field ];
		}

		WPR_Settings::update( $input );

		$this->regenerate_css_safely();

		wp_send_json_success( array( 'message' => wpr_t( 'Einstellungen gespeichert.' ) ) );
	}
}
