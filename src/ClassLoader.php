<?php
/**
 * Template loader.
 *
 * @package Omni
 */

namespace Procoders\Omni;
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use Procoders\Omni\Includes\TemplateLoader;
/**
 * Template loader for Meal Planner.
 *
 * Only need to specify class properties here.
 *
 * @package Meal_Planner
 * @author  Gary Jones
 */
class ClassLoader extends TemplateLoader {
	/**
	 * Prefix for filter names.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $filter_prefix = 'omnimind';

	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $theme_template_directory = 'omnimind';

	/**
	 * Reference to the root directory path of this plugin.
	 *
	 * Can either be a defined constant, or a relative reference from where the subclass lives.
	 *
	 * In this case, `PLUGIN_DIR` would be defined in the root plugin file as:
	 *
	 * ~~~
	 * define( 'PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	 * ~~~
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $plugin_directory = OMNI_PLUGIN_DIR . 'src';

	/**
	 * Directory name where templates are found in this plugin.
	 *
	 * Can either be a defined constant, or a relative reference from where the subclass lives.
	 *
	 * e.g. 'templates' or 'includes/templates', etc.
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected $plugin_template_directory = 'Templates';
}
