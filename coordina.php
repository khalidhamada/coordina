<?php
/**
 * Plugin Name: Coordina
 * Plugin URI: https://example.com/coordina
 * Description: WordPress-native work management foundations for operational teams.
 * Version: 1.2.1
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Author: Khalid Hamada
 * Author URI: https://example.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: coordina
 * Domain Path: /languages
 *
 * @package Coordina
 *
 * Copyright 2026 Khalid Hamada
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1335 USA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'COORDINA_VERSION', '1.2.1' );
define( 'COORDINA_FILE', __FILE__ );
define( 'COORDINA_PATH', plugin_dir_path( __FILE__ ) );
define( 'COORDINA_URL', plugin_dir_url( __FILE__ ) );
define( 'COORDINA_BASENAME', plugin_basename( __FILE__ ) );

$coordina_autoload = COORDINA_PATH . 'vendor/autoload.php';

if ( file_exists( $coordina_autoload ) ) {
	require_once $coordina_autoload;
} else {
	require_once COORDINA_PATH . 'src/Support/Autoloader.php';
	Coordina\Support\Autoloader::register( COORDINA_PATH . 'src/' );
}

register_activation_hook( COORDINA_FILE, array( Coordina\Core\Activator::class, 'activate' ) );
register_deactivation_hook( COORDINA_FILE, array( Coordina\Core\Deactivator::class, 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		$plugin = new Coordina\Core\Plugin();
		$plugin->boot();
	}
);


