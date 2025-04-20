<?php 

class Frm_optimizer_admin {


    public function __construct()
    {

        // Include filters/hooks
        $this->addHooks();

    }

    public function addHooks()
    {

        // Menu point
        add_action('admin_menu', array($this, 'frm_register_optimizer_page'));

    }

    public function frm_register_optimizer_page() {
        add_submenu_page(
            'formidable',                 // Parent slug (Formidable plugin menu)
            'Optimizer',                  // Page title
            'Optimizer',                  // Menu title
            'manage_options',             // Capability
            'formidable-optimizer',      // Menu slug
            array($this, 'frm_display_optimizer_page')  // Callback
        );
    }

    public function frm_display_optimizer_page() {
        ?>
        <div class="wrap">
            <h1>Formidable Optimizer</h1>
            <form method="post">
                <?php wp_nonce_field('frm_optimizer_action', 'frm_optimizer_nonce'); ?>
                <p>
                    <input type="submit" name="frm_archive_entries" class="button button-primary" value="Archive Entries" />
                    <input type="submit" name="frm_restore_entries" class="button button-secondary" value="Restore Entries" />
                </p>
            </form>
            <?php call_user_func(array($this, 'frm_handle_optimizer_actions')); ?>
        </div>
        <?php
    }

    public function frm_handle_optimizer_actions() {
        if (!isset($_POST['frm_optimizer_nonce']) || !wp_verify_nonce($_POST['frm_optimizer_nonce'], 'frm_optimizer_action')) {
            return;
        }

        if (isset($_POST['frm_archive_entries'])) {
            // Archive logic here (example message)
            echo '<div class="notice notice-success"><p>Entries have been archived.</p></div>';
            // Implement your logic here to move old Formidable entries to another table
        }

        if (isset($_POST['frm_restore_entries'])) {
            // Restore logic here (example message)
            echo '<div class="notice notice-success"><p>Entries have been restored.</p></div>';
            // Implement your logic here to bring them back from archive
        }
    }

}

new Frm_optimizer_admin();