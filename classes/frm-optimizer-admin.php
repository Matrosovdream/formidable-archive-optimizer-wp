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
        add_action('wp_ajax_frm_save_enabled_forms', array($this, 'ajax_save_enabled_forms'));
        add_action('wp_ajax_frm_save_statuses', array($this, 'ajax_save_statuses'));
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
            FRM_OPT_ASSETS . 'frm-optimizer.js?t=' . time(),
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
            FRM_OPT_ASSETS . 'frm-optimizer.css?t=' . time()
        );
    }

    public function frm_display_optimizer_page() {
        $total_entries = $this->get_total_entries();
        $archived_entries = $this->get_archived_entries();
        $forms = FrmForm::getAll();
        $saved_settings = get_option('frm_optimizer_form_fields', []);
        $enabled_forms = get_option('frm_optimizer_enabled_forms', []);
        $statuses = get_option('frm_optimizer_statuses', []);

        $status_display = $statuses && is_array($statuses) && count($statuses) > 0
                    ? implode(', ', array_map('esc_html', $statuses))
                    : 'None';
        ?>
        <div class="wrap">
            <h1>Formidable Optimizer</h1>

            <div class="fo-section">
                <h2>Archive Entries</h2>
                <p>
                    Total Entries: <strong id="fo-total"><?php echo $total_entries; ?></strong>
                    (Statuses: <?php echo $status_display; ?>)
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
                <h2>Enabled Forms</h2>
                <form id="fo-enabled-forms-form">
                    <p>Select which forms should be available in the archive interface:</p>
                    <select name="enabled_forms[]" multiple size="8" style="width: 100%;">
                        <?php foreach ($forms as $form): ?>
                            <option value="<?php echo $form->id; ?>" <?php selected(in_array($form->id, $enabled_forms)); ?>>
                                <?php echo esc_html($form->name); ?> (ID: <?php echo $form->id; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p>
                        <button type="submit" class="button button-primary">Save Enabled Forms</button>
                        <div id="fo-enabled-msg" class="fo-msg"></div>
                    </p>
                </form>
            </div>

            <div class="fo-section">
                <h2>Statuses</h2>
                <form id="fo-statuses-form">
                    <p>Enter comma-separated status values for archiving:</p>
                    <textarea id="fo-statuses" rows="3" style="width: 100%;"><?php echo esc_textarea(implode(',', $statuses)); ?></textarea>
                    <p>
                        <button type="submit" class="button button-primary">Save Statuses</button>
                        <div id="fo-statuses-msg" class="fo-msg"></div>
                    </p>
                </form>
            </div>

            <?php if (!empty($enabled_forms)): ?>
            <div class="fo-section" style="max-width: 100%;">
                <h2>Form Field Settings</h2>
                <form id="fo-form-settings">
                    <table class="widefat fixed" id="fo-forms-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Form Name</th>
                                <th>Fields IDs</th>
                                <th>Status</th>
                                <th>Dot Number</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($forms as $form):
                                if (!in_array($form->id, $enabled_forms)) continue;
                                $data = $saved_settings[$form->id] ?? [];
                                ?>
                                <tr data-form-id="<?php echo esc_attr($form->id); ?>">
                                    <td><?php echo esc_html($form->name); ?> (ID: <?php echo $form->id; ?>)</td>
                                    <td><textarea class="field-ids" rows="3" style="width: 100%;"><?php echo esc_textarea(implode(',', $data['field_ids'] ?? [])); ?></textarea></td>
                                    <td><input type="number" class="field-status" style="width: 100%;" value="<?php echo esc_attr($data['status'] ?? ''); ?>"></td>
                                    <td><input type="number" class="field-dot" style="width: 100%;" value="<?php echo esc_attr($data['dot'] ?? ''); ?>"></td>
                                    <td><input type="number" class="field-email" style="width: 100%;" value="<?php echo esc_attr($data['email'] ?? ''); ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p>
                        <button type="submit" class="button button-primary">Save Settings</button>
                        <div id="fo-settings-msg" class="fo-msg"></div>
                    </p>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <script>
        jQuery(document).ready(function ($) {
            $('#fo-form-settings').on('submit', function (e) {
                e.preventDefault();
                const formData = {};
                $('#fo-forms-table tbody tr').each(function () {
                    const formId = $(this).data('form-id');
                    const field_ids = $(this).find('.field-ids').val().trim().split(',').map(s => s.trim()).filter(Boolean);
                    const status = $(this).find('.field-status').val();
                    const dot = $(this).find('.field-dot').val();
                    const email = $(this).find('.field-email').val();
                    formData[formId] = { field_ids, status, dot, email };
                });

                $.post(frm_optimizer.ajax_url, {
                    action: 'frm_save_form_field_settings',
                    nonce: frm_optimizer.nonce,
                    data: formData
                }, function (res) {
                    $('#fo-settings-msg').text(res.data.message).css('color', res.success ? 'green' : 'red');
                    if (res.success) location.reload();
                });
            });

            $('#fo-enabled-forms-form').on('submit', function (e) {
                e.preventDefault();
                const selectedForms = [];
                $('#fo-enabled-forms-form select[name="enabled_forms[]"] option:selected').each(function () {
                    selectedForms.push($(this).val());
                });

                $.post(frm_optimizer.ajax_url, {
                    action: 'frm_save_enabled_forms',
                    nonce: frm_optimizer.nonce,
                    forms: selectedForms
                }, function (res) {
                    $('#fo-enabled-msg').text(res.data.message).css('color', res.success ? 'green' : 'red');
                    if (res.success) location.reload();
                });
            });

            $('#fo-statuses-form').on('submit', function (e) {
                e.preventDefault();
                const statuses = $('#fo-statuses').val().trim().split(',').map(s => s.trim()).filter(Boolean);
                $.post(frm_optimizer.ajax_url, {
                    action: 'frm_save_statuses',
                    nonce: frm_optimizer.nonce,
                    statuses: statuses
                }, function (res) {
                    $('#fo-statuses-msg').text(res.data.message).css('color', res.success ? 'green' : 'red');
                    if (res.success) location.reload();
                });
            });
        });
        </script>
        <?php
    }

    private function get_total_entries() {
        $statuses = get_option('frm_optimizer_statuses', ['Failed', 'Complete', 'Refunded']);
        $old_ids = $this->helper->getEntriesForArchive(['status' => $statuses]);
        return count($old_ids);
    }

    private function get_archived_entries() {
        return $this->helper->getEntryCount()['archive'] ?? 0;
    }

    public function ajax_archive_entries() {
        check_ajax_referer('frm_optimizer_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'You do not have permission to perform this action.']);
        }

        $this->optimizerArchive->archiveEntries($_POST['archivePeriod']);
        wp_send_json_success(['message' => 'Entries have been archived.']);
    }

    public function ajax_restore_entries() {
        check_ajax_referer('frm_optimizer_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'You do not have permission to perform this action.']);
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

        foreach ($raw_data as $form_id => $data) {
            $form_id = (int)$form_id;
            $sanitized[$form_id] = [
                'field_ids' => array_filter(array_map('sanitize_text_field', (array)($data['field_ids'] ?? []))),
                'status' => sanitize_text_field($data['status'] ?? ''),
                'dot' => sanitize_text_field($data['dot'] ?? ''),
                'email' => sanitize_text_field($data['email'] ?? '')
            ];
        }

        update_option('frm_optimizer_form_fields', $sanitized);
        wp_send_json_success(['message' => 'Settings saved']);
    }

    public function ajax_save_enabled_forms() {
        check_ajax_referer('frm_optimizer_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $form_ids = isset($_POST['forms']) ? array_map('intval', (array) $_POST['forms']) : [];
        update_option('frm_optimizer_enabled_forms', $form_ids);
        wp_send_json_success(['message' => 'Enabled forms saved.']);
    }

    public function ajax_save_statuses() {
        check_ajax_referer('frm_optimizer_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $statuses = isset($_POST['statuses']) ? array_map('sanitize_text_field', (array) $_POST['statuses']) : [];
        update_option('frm_optimizer_statuses', $statuses);
        wp_send_json_success(['message' => 'Statuses saved.']);
    }
}

new Frm_optimizer_admin();
