<?php

class Frm_optimizer_admin {

    private $helper;
    private $optimizerArchive;

    public function __construct() {

        $this->helper = new Frm_optimize_helper();

        $this->addHooks();

        // Include optimizer archive
        $this->optimizerArchive = new Frm_optimizer_archive();
    }

    public function addHooks() {
        add_action('admin_menu', array($this, 'frm_register_optimizer_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

        add_action('wp_ajax_frm_archive_entries', array($this, 'ajax_archive_entries'));
        add_action('wp_ajax_frm_restore_entries', array($this, 'ajax_restore_entries'));
    }

    public function frm_register_optimizer_page() {
        add_submenu_page(
            'formidable',
            'Optimizer',
            'Optimizer',
            'manage_options',
            'formidable-optimizer',
            array($this, 'frm_display_optimizer_page')
        );
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'formidable-optimizer') === false) {
            return;
        }

        wp_enqueue_script(
            'frm-optimizer-js',
            FRM_OPT_ASSETS.'frm-optimizer.js',
            array('jquery'),
            null,
            true
        );

        wp_localize_script('frm-optimizer-js', 'frm_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('frm_optimizer_nonce')
        ));

        wp_enqueue_style(
            'frm-optimizer-css', 
            FRM_OPT_ASSETS.'frm-optimizer.css'
        );
    }

    public function frm_display_optimizer_page() {
        $total_entries = $this->get_total_entries();
        $archived_entries = $this->get_archived_entries();
        ?>
        <div class="wrap">
            <h1>Formidable Optimizer</h1>

            <div class="fo-section">
                <h2>Archive Entries</h2>
                <p>Total Entries: <strong id="fo-total"><?php echo $total_entries; ?></strong></p>

                <!-- Archive period -->
                <p>Archive entries older than: 
                    <input type="number" id="archive-period" value="<?php echo FRM_ARCHIVE_PERIOD; ?>" min="1" style="width: 60px;"> months
                </p>

                <button id="fo-archive-btn" class="button button-primary">Archive Entries</button>
                <div id="fo-archive-msg" class="fo-msg"></div>
            </div>

            <div class="fo-section">
                <h2>Restore Entries</h2>
                <p>Archived Entries: <strong id="fo-archived"><?php echo $archived_entries; ?></strong></p>
                <button id="fo-restore-btn" class="button button-secondary">Restore Entries</button>
                <div id="fo-restore-msg" class="fo-msg"></div>
            </div>
        </div>
        <?php
    }

    private function get_total_entries() {
        return $this->helper->getEntryCount()['default'] ?? 0;
    }

    private function get_archived_entries() {
        return $this->helper->getEntryCount()['archive'] ?? 0;
    }

    public function ajax_archive_entries() {
        check_ajax_referer('frm_optimizer_nonce', 'nonce');

        // Check if the user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'You do not have permission to perform this action.']);
            return;
        }

        // Archive all entries
        $this->optimizerArchive->archiveEntries( $period=$_POST['archivePeriod'] );

        // TODO: real logic to archive entries
        wp_send_json_success(['message' => 'Entries have been archived.']);
    }

    public function ajax_restore_entries() {
        check_ajax_referer('frm_optimizer_nonce', 'nonce');

        // Check if the user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'You do not have permission to perform this action.']);
            return;
        }

        // Restore all entries
        $this->optimizerArchive->restoreAllEntries();

        // TODO: real logic to restore entries
        wp_send_json_success(['message' => 'Entries have been restored.']);
    }
}

new Frm_optimizer_admin();
