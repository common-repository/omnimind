<?php
/**
 * Initialize the admin menu.
 *
 * @package Omni
 */

namespace Procoders\Omni\Admin;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use Procoders\Omni\Admin\ClassAdmin as AdminInit;

/**
 * Create the admin menu.
 */
class ClassNav
{
    /**
     * Main class runner.
     */
    public static function run(): void
    {
        add_action('admin_menu', array(static::class, 'init_menu'));
    }

    /**
     * Register the plugin menu.
     */
    public static function init_menu(): void
    {
        $slug = dirname(plugin_basename(OMNI_FILE));
        $admin = new AdminInit();
        add_menu_page(
            esc_html__('Omnimind', 'omnimind'),
            esc_html__('Omnimind', 'omnimind'),
            'manage_options',
            $slug,
            array($admin, 'omni_settings_page'),
            'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none"><path fill="#a7aaad" fill-rule="evenodd" d="m11.602.232 3.468 2.224 1.732 4.164-1.4 4.04-1.294 1.992-1.32 2.963-5.998.143-1.183-3.413L3.214 7.74l.861-4.419L7 .508l4.603-.276Zm-4.315 12.38.562 1.62 3.955-.094.982-2.204.333-.513-5.832 1.19Zm6.953-3.179.819-2.362-4.166-1.696-3.73 1.662-.878.392 7.955 2.004Zm-9.228-3.08 1.54-.686 2.987-1.33-2.21-2.064-1.872 1.8-.445 2.28ZM9.11 1.885l1.918 1.79 1.866-.832-1.69-1.083-2.094.125ZM14.07 3.96l.546 1.313-1.825-.743 1.28-.57ZM5.435 8.761l1.268 2.44 4.646-.95-5.914-1.49ZM6.994 16.748a.75.75 0 0 1 .75-.75h4.164a.75.75 0 1 1 0 1.5H7.744a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd"/><path fill="#a7aaad" d="M8.076 17.892a1.75 1.75 0 1 0 3.501 0h-3.5Z"/></svg>')
        );
    }
}
