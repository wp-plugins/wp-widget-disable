<?php
/**
 * Plugin Name: WP Widget Disable
 * Plugin URI:  http://required.ch
 * Description: Disable Sidebar and Dashboard Widgets with an easy to use interface. Simply use the checkboxes provided under <strong>Appearance -> Disable Widgets</strong> and select the Widgets you'd like to hide.
 * Version:     1.2.0
 * Author:      required+
 * Author URI:  http://required.ch
 * License:     GPLv2+
 * Text Domain: wp-widget-disable
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2015 required+ (email : support@required.ch)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

defined( 'WPINC' ) or die;

include( dirname( __FILE__ ) . '/lib/requirements-check.php' );

$wp_widget_disable_requirements_check = new WP_Widget_Disable_Requirements_Check( array(
	'title' => 'WP Widget Disable',
	'php'   => '5.2',
	'wp'    => '3.5.1',
	'file'  => __FILE__,
) );

if ( $wp_widget_disable_requirements_check->passes() ) {
	// Pull in the plugin classes and initialize
	include( dirname( __FILE__ ) . '/lib/wp-stack-plugin.php' );
	include( dirname( __FILE__ ) . '/classes/plugin.php' );
	WP_Widget_Disable_Plugin::start( __FILE__ );
}

unset( $wp_widget_disable_requirements_check );
