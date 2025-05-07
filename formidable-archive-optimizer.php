<?php
/**
 * Plugin Name: Formidable Archive Optimizer
 * Description: Moves old Formidable Forms entries to archive tables and still allows access by entry ID.
 * Version: 1.0.0
 * Author: Stan Matrosov
 */

// Variables
define('FRM_OPT_URL', __DIR__);
define('FRM_OPT_ASSETS', plugins_url('formidable-optimizer-wp/assets/'));
define('FRM_ARCHIVE_PERIOD', 4); // Archive entries older than this many months

// Include classes
require_once FRM_OPT_URL . '/classes/frm-optimizer-settings.php';
require_once FRM_OPT_URL . '/classes/frm-entry-helper.php';
require_once FRM_OPT_URL . '/classes/frm-entry-replacer.php';
require_once FRM_OPT_URL . '/classes/frm-optimizer-archive.php';
require_once FRM_OPT_URL . '/classes/frm-optimizer-admin.php';

// Shortcodes
require_once FRM_OPT_URL . '/shortcodes/frm-entry-archived-list.php';



// Activate plugin hook
register_activation_hook(__FILE__, 'createArchiveTablesActivate');
function createArchiveTablesActivate()
{
    $archiver = new Frm_optimizer_archive();
    $archiver->createArchiveTables();
}





