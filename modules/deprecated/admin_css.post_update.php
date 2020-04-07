<?php

/**
 * @file
 * Post update functions for the legacy Admin CSS module.
 */

/**
 * Uninstall the admin_css module.
 */
function admin_css_post_update_uninstall_module() {
  /**
   * @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   */
  $module_installer = \Drupal::service('module_installer');

  // Uninstall the deprecated module.
  $module_installer->uninstall(['admin_css']);
}
