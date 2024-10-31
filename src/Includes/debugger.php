<?php

namespace Procoders\Omni\Includes;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class debugger
{

    /**
     * @param string $message The error message to be logged
     *
     * @return void
     */
    public function omni_error_log(string $message): void
    {
        global $wp_filesystem;

        // Include the WP_Filesystem class.
        require_once(ABSPATH . 'wp-admin/includes/file.php');

        // Initialize the WP_Filesystem.
        WP_Filesystem();

        // Check if directory and file exist, if not create them
        $log_path = plugin_dir_path(dirname(__FILE__)) . 'logs';
        $log_file = $log_path . '/omni.log';

        if (!$wp_filesystem->is_dir($log_path)) {
            $wp_filesystem->mkdir($log_path);
        }

        if (!$wp_filesystem->exists($log_file)) {
            $wp_filesystem->put_contents($log_file, '', FS_CHMOD_FILE); // empty file
        }

        $log = $wp_filesystem->get_contents($log_file);
        $log .= 'Message: ' . $message . "\n";
        $log .= 'Date: ' . gmdate('Y-m-d H:i:s') . "\n\n";

        $wp_filesystem->put_contents(plugin_dir_path(dirname(__FILE__)) . 'Logs/omni.log', $log, FS_CHMOD_FILE);
    }
}