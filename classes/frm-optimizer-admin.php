<?php

class Frm_optimizer_admin {

    private $helper;
    private $optimizerArchive;

    public function __construct() {
        $this->helper = new Frm_optimize_helper();
        $this->addHooks();
        $this->optimizerArchive = new Frm_optimizer_archive();
    }

    public function addHooks() {
        add_action('admin_menu', array($this, 'frm_register_optimizer_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

        add_action('wp_ajax_frm_archive_entries', array($this, 'ajax_archive_entries'));
        add_action('wp_ajax_frm_restore_entries', array($this, 'ajax_restore_entries'));
        add_action('wp_ajax_frm_save_form_field_settings', array($this, 'ajax_save_form_field_settings'));
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
            FRM_OPT_ASSETS . 'frm-optimizer.js',
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
            FRM_OPT_ASSETS . 'frm-optimizer.css'
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
                <p>
                    Total Entries: <strong id="fo-total"><?php echo $total_entries; ?></strong>
                    (Completed, Failed)
                </p>

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

            <div class="fo-section">
                <h2>Form Field Settings</h2>
                <form id="fo-form-settings">
                    <table class="widefat fixed" id="fo-forms-table">
                        <thead>
                            <tr>
                                <th>Form Name</th>
                                <th>Field IDs (comma-separated)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $forms = FrmForm::getAll();
                            $saved_settings = get_option('frm_optimizer_form_fields', []);
                            foreach ($forms as $form):
                                $field_ids = isset($saved_settings[$form->id]) ? implode(',', $saved_settings[$form->id]) : '';
                                ?>
                                <tr data-form-id="<?php echo esc_attr($form->id); ?>">
                                    <td><?php echo esc_html($form->name); ?> (ID: <?php echo $form->id; ?>)</td>
                                    <td>
                                        <input type="text" name="forms[<?php echo $form->id; ?>]" value="<?php echo esc_attr($field_ids); ?>" style="width: 100%;" />
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p>
                        <button type="submit" class="button button-primary">Save Settings</button>
                        <span id="fo-settings-msg" class="fo-msg"></span>
                    </p>
                </form>
            </div>
        </div>

        <script>
        jQuery(document).ready(function ($) {
            $('#fo-form-settings').on('submit', function (e) {
                e.preventDefault();

                const formData = {};
                $('#fo-forms-table tbody tr').each(function () {
                    const formId = $(this).data('form-id');
                    const fieldInput = $(this).find('input[type="text"]').val().trim();
                    const fieldArray = fieldInput !== '' ? fieldInput.split(',').map(s => s.trim()) : [];
                    formData[formId] = fieldArray;
                });

                $.post(frm_optimizer.ajax_url, {
                    action: 'frm_save_form_field_settings',
                    nonce: frm_optimizer.nonce,
                    data: formData
                }, function (res) {
                    $('#fo-settings-msg').text(res.data.message).css('color', res.success ? 'green' : 'red');
                });
            });
        });
        </script>
        <?php
    }

    private function get_total_entries() {
        $old_ids = $this->helper->getEntriesForArchive([
            'status' => ['Failed', 'Complete'],
        ]);
        return count($old_ids);
    }

    private function get_archived_entries() {
        return $this->helper->getEntryCount()['archive'] ?? 0;
    }

    public function ajax_archive_entries() {
        check_ajax_referer('frm_optimizer_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'You do not have permission to perform this action.']);
            return;
        }

        $this->optimizerArchive->archiveEntries($_POST['archivePeriod']);
        wp_send_json_success(['message' => 'Entries have been archived.']);
    }

    public function ajax_restore_entries() {
        check_ajax_referer('frm_optimizer_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'You do not have permission to perform this action.']);
            return;
        }

        $this->optimizerArchive->restoreAllEntries();
        wp_send_json_success(['message' => 'Entries have been restored.']);
    }

    public function ajax_save_form_field_settings() {
        check_ajax_referer('frm_optimizer_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $raw_data = $_POST['data'] ?? [];
        $sanitized = [];

        foreach ($raw_data as $form_id => $fields) {
            $sanitized[(int) $form_id] = array_filter(array_map('sanitize_text_field', (array)$fields));
        }

        update_option('frm_optimizer_form_fields', $sanitized);

        wp_send_json_success(['message' => 'Settings saved']);
    }
}

new Frm_optimizer_admin();
