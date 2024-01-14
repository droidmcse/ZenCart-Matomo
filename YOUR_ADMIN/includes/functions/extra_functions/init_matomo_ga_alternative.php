<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

//----
// If the installation supports admin-page registration (i.e. v1.5.0 and later), then
// register the New Tools tool into the admin menu structure.
//
// NOTES:  
// 1) Once this file has run once and you see the Tools->New Tool link in the admin
// menu structure, it is safe to delete this file (unless you have other functions that
// are initialized in the file).
// 2) If you have multiple items to add to the admin-level menus, then you should register each
// of the pages here, just make sure that the "page key" is unique or a debug-log will be
// generated!
//
if (function_exists('zen_register_admin_page')) {
    if (!zen_page_key_exists('matomo_ga_alternative')) {
        zen_register_admin_page('matomo_ga_alternative', 'BOX_CONFIGURATION_MATOMO_GA_ALTERNATIVE', 'FILENAME_CONFIGURATION', '', 'configuration', 'Y', 99);
        matomo_ga_alternative_install();
    }

    // This item is new so might want to add it to an existing installation if upgrading
    if( !defined('MATOMO_GA_ALTERNATIVE_MODEL_SKU') ) {
        $sql = 'select configuration_group_id from ' . TABLE_CONFIGURATION_GROUP . ' where configuration_group_title = "Matomo GA Alternative";';
        $results = $db->Execute($sql);
	$group_id = $results->fields['configuration_group_id'];

        $sql_data_array = array(
            'configuration_title' => 'Use Model as SKU',
            'configuration_key' => 'MATOMO_GA_ALTERNATIVE_MODEL_SKU',
            'configuration_value' => 'false',
            'configuration_description' => 'Report the product model as the SKU, otherwise use the product ID number.',
            'configuration_group_id' => $group_id,
            'sort_order' => 17,
            'date_added' => 'now()',
            'set_function' => "zen_cfg_select_option(array('true', 'false'),"
        );
        zen_db_perform(TABLE_CONFIGURATION, $sql_data_array);
    }
}

function matomo_ga_alternative_install()
{
    global $db;

    /*
      delete from admin_pages where page_key = 'matomo_ga_alternative';
      delete from configuration_group where configuration_group_title = 'Matomo GA Alternative';
      delete from configuration where configuration_key like 'MATOMO_GA_ALTERNATIVE_%';
      delete from configuration where configuration_key like 'MATOMO_%';

     */

    $sql = "select (max(sort_order) + 1 ) as m_sort_order from " . TABLE_CONFIGURATION_GROUP;
    $results = $db->Execute($sql);
    $sql = "insert ignore into " . TABLE_CONFIGURATION_GROUP . " set configuration_group_title = 'Matomo GA Alternative', configuration_group_description = 'Matomo GA Alternative', visible=1";
    $db->Execute($sql);
    $insert_id = $db->insert_ID();
    $sql = "update " . TABLE_ADMIN_PAGES . " set page_params = 'gID=" . $insert_id . "' where page_key = 'matomo_ga_alternative'";
    $db->Execute($sql);
    $sql = "update " . TABLE_CONFIGURATION_GROUP . " set sort_order = '" . $insert_id . "' where configuration_group_title = 'Matomo GA Alternative'";
    $db->Execute($sql);

    $sql_data_array = array(
        'configuration_title' => 'Matomo GA Alternative',
        'configuration_key' => 'MATOMO_GA_ALTERNATIVE_VERSION',
        'configuration_value' => '0.0.1',
        'configuration_description' => 'The <em>Matomo GA Alternative</em> installed version.',
        'configuration_group_id' => $insert_id,
        'date_added' => 'now()',
        'sort_order' => 1,
        'use_function' => NULL,
        'set_function' => 'zen_cfg_read_only()'
    );
    zen_db_perform(TABLE_CONFIGURATION, $sql_data_array);
    $sql_data_array = array(
        'configuration_title' => 'Enable Matomo GA Alternative ?',
        'configuration_key' => 'MATOMO_GA_ALTERNATIVE_ENABLED',
        'configuration_value' => 'false',
        'configuration_description' => 'Enable <b>Matomo GA Alternative</b> Default: <b>false</b>.',
        'configuration_group_id' => $insert_id,
        'date_added' => 'now()',
        'sort_order' => 4,
        'use_function' => NULL,
        'set_function' => "zen_cfg_select_option(array('true', 'false'),"
    );
    zen_db_perform(TABLE_CONFIGURATION, $sql_data_array);

    $sql_data_array = array(
        'configuration_title' => 'Matomo URL',
        'configuration_key' => 'MATOMO_GA_ALTERNATIVE_URL',
        'configuration_value' => 'https://www.yourmatomo.com/', // set this later
        'configuration_description' => 'URL where to point for matomo',
        'configuration_group_id' => $insert_id,
        'sort_order' => 5,
        'date_added' => 'now()'
    );
    zen_db_perform(TABLE_CONFIGURATION, $sql_data_array);

    $sql_data_array = array(
        'configuration_title' => 'Matomo Site ID',
        'configuration_key' => 'MATOMO_GA_ALTERNATIVE_SITE_ID',
        'configuration_value' => '10',
        'configuration_description' => 'Site id for matomo',
        'configuration_group_id' => $insert_id,
        'sort_order' => 6,
        'date_added' => 'now()'
    );
    zen_db_perform(TABLE_CONFIGURATION, $sql_data_array);

    $sql_data_array = array(
        'configuration_title' => 'Enable JavaScript Tracking Code',
        'configuration_key' => 'MATOMO_GA_ALTERNATIVE_ENABLE_JAVASCRIPT',
        'configuration_value' => 'false',
        'configuration_description' => 'This will enable the javascript tracking code',
        'configuration_group_id' => $insert_id,
        'sort_order' => 10,
        'date_added' => 'now()',
        'set_function' => "zen_cfg_select_option(array('true', 'false'),"
    );
    zen_db_perform(TABLE_CONFIGURATION, $sql_data_array);

    $sql_data_array = array(
        'configuration_title' => 'Enable eCommerce Tracking',
        'configuration_key' => 'MATOMO_GA_ALTERNATIVE_ENABLE_ECOMMERCE',
        'configuration_value' => 'false',
        'configuration_description' => 'This will enable the ecommerce modules<br>This is an ecommerce site? Seems like a no brainer to have this on. Your choice though.',
        'configuration_group_id' => $insert_id,
        'sort_order' => 15,
        'date_added' => 'now()',
        'set_function' => "zen_cfg_select_option(array('true', 'false'),"
    );
    zen_db_perform(TABLE_CONFIGURATION, $sql_data_array);

    $sql_data_array = array(
        'configuration_title' => 'Image Tracking Link Enabled',
        'configuration_key' => 'MATOMO_GA_ALTERNATIVE_ENABLE_IMAGE_TRACKING',
        'configuration_value' => 'true',
        'configuration_description' => 'This will enable the image pixel to track users with javascript disabled',
        'configuration_group_id' => $insert_id,
        'sort_order' => 20,
        'date_added' => 'now()',
        'set_function' => "zen_cfg_select_option(array('true', 'false'),"
    );
    zen_db_perform(TABLE_CONFIGURATION, $sql_data_array);

    $sql_data_array = array(
        'configuration_title' => 'Enable client side DoNotTrack detection',
        'configuration_key' => 'MATOMO_GA_ALTERNATIVE_ENABLE_CLIENT_SIDE_DONOTTRACK_DETECTION',
        'configuration_value' => 'false',
        'configuration_description' => 'So tracking requests will not be sent if visitors do not wish to be tracked.
Note: Server side DoNotTrack support has been enabled, so this option will have no effect.',
        'configuration_group_id' => $insert_id,
        'sort_order' => 30,
        'date_added' => 'now()',
        'set_function' => "zen_cfg_select_option(array('true', 'false'),"
    );
    zen_db_perform(TABLE_CONFIGURATION, $sql_data_array);

    $sql_data_array = array(
        'configuration_title' => 'Enable debugging',
        'configuration_key' => 'MATOMO_GA_ALTERNATIVE_ENABLE_DEBUG',
        'configuration_value' => 'false',
        'configuration_description' => 'Will write a log file of what the matomo output would be into your DIR_FS_LOGS folder',
        'configuration_group_id' => $insert_id,
        'sort_order' => 40,
        'date_added' => 'now()',
        'set_function' => "zen_cfg_select_option(array('true', 'false'),"
    );
    zen_db_perform(TABLE_CONFIGURATION, $sql_data_array);

//  $sql = "insert ignore into " . TABLE_CONFIGURATION . " set configuration_title = 'Send tracking information via SMS enabled', configuration_key = 'MATOMO_GA_ALTERNATIVE_ENABLED', configuration_description = 'This is the global option to turn this feature', configuration_value = 'false', configuration_group_id = '$insert_id', date_added = now(), set_function = 'zen_cfg_select_option(array(\'true\', \'false\'),'";
//  $db->Execute($sql);
//  $sql = "insert ignore into " . TABLE_CONFIGURATION . " set configuration_title = 'Send tracking information via SMS test mode', configuration_key = 'MATOMO_GA_ALTERNATIVE_MODE', configuration_description = 'Production or Test mode', configuration_value = 'test', configuration_group_id = '$insert_id', date_added = now(), set_function = 'zen_cfg_select_option(array(\'production\', \'test\'),'";
//  $db->Execute($sql);
//  $sql = "insert ignore into " . TABLE_CONFIGURATION . " set configuration_title = 'sid', configuration_key = 'MATOMO_GA_ALTERNATIVE_SID', configuration_description = 'This is the sid', configuration_value = 'AC6fb9293d649c136dbe7922404f040802', configuration_group_id = '$insert_id', date_added = now()";
//  $db->Execute($sql);
//  $sql = "insert ignore into " . TABLE_CONFIGURATION . " set configuration_title = 'token', configuration_key = 'MATOMO_GA_ALTERNATIVE_TOKEN', configuration_description = 'This is the token', configuration_value = '2478093fee07a4bf4316f9a79a590826', configuration_group_id = '$insert_id', date_added = now()";
//  $db->Execute($sql);
//
}
