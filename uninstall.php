<?php
/**
 * This file gets triggered on Plugin Uninstall
 */

defined('WP_UNINSTALL_PLUGIN') or die("Unauthorized Access.");

delete_option('gbams123-leonardoai-api-token');