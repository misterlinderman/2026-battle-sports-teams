<?php

/**
 * Fired when the plugin is uninstalled (deleted) from WordPress.
 *
 * Removes custom roles and options created by the Battle Sports Platform plugin.
 *
 * @package BattleSports
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

/**
 * Removes custom user roles created by the plugin.
 *
 * @return void
 */
function bsp_uninstall_remove_roles(): void {
    $roles = ['bsp_coach', 'bsp_designer'];
    foreach ($roles as $role) {
        remove_role($role);
    }
}

/**
 * Removes plugin options from the database.
 *
 * @return void
 */
function bsp_uninstall_remove_options(): void {
    $options = [
        'bsp_roles_registered',
        'bsp_monday_config',
        'bsp_submission_fee_product_id',
    ];
    foreach ($options as $option) {
        delete_option($option);
    }
}

bsp_uninstall_remove_roles();
bsp_uninstall_remove_options();
