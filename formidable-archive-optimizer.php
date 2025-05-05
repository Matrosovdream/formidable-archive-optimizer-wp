<?php
/**
 * Plugin Name: Formidable Archive Optimizer
 * Description: Moves old Formidable Forms entries to archive tables and still allows access by entry ID.
 * Version: 1.0
 * Author: Your Name
 */

// Variables
define('FRM_OPT_URL', __DIR__);
define('FRM_OPT_ASSETS', plugins_url('formidable-optimizer-wp/assets/'));

// Include classes
require_once FRM_OPT_URL . '/classes/frm-optimizer-settings.php';
require_once FRM_OPT_URL . '/classes/frm-entry-helper.php';
require_once FRM_OPT_URL . '/classes/frm-entry-replacer.php';
require_once FRM_OPT_URL . '/classes/frm-optimizer-admin.php';
require_once FRM_OPT_URL . '/classes/frm-optimizer-archive.php';

// Shortcodes
require_once FRM_OPT_URL . '/shortcodes/frm-entry-archived-list.php';


add_action('init', 'frm_work');
function frm_work()
{

    if (isset($_GET['create_tables'])) {

        ffao_create_archive_tables();
        die();

    }

    if (isset($_GET['migrate'])) {
        ffao_archive_old_entries();
        die();
    }

    if (isset($_GET['restore'])) {
        ffao_restore_all_archived_entries();
        die();
    }

}

//register_activation_hook(__FILE__, 'ffao_create_archive_tables');












// Hook into Formidable to retrieve archived entries

/*
DROP TABLE wp_frm_items_archive;
DROP TABLE wp_frm_item_metas_archive;
DROP TABLE wp_frm_payments_archive;
*/
