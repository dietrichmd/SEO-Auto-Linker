<?php
/*
Plugin Name: SEO Auto Linker Reborn
Plugin URI: 
Description: Allows you to automatically link terms with in your post, page or custom post type content.  Based on SEO Auto Linker by Christopher Davis (http://christopherdavis.me)
Version: 0.1.0
Author: Vestra Interactive
Author URI: http://vestrainteractive.com
Text Domain: seoal
Domain Path: /lang

    Copyright 2024 Vestra Interactive

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('SEOAL_PATH', plugin_dir_path(__FILE__));
define('SEOAL_URL', plugin_dir_url(__FILE__));

require_once SEOAL_PATH . 'inc/base.php';
require_once SEOAL_PATH . 'inc/post-type.php';

if (is_admin()) {
  require_once SEOAL_PATH . 'inc/admin.php';
} else {
  require_once SEOAL_PATH . 'inc/front.php';
}

add_action('init', 'seoal_load_textdomain');

function seoal_load_textdomain() {
  load_plugin_textdomain('seoal', false, dirname(plugin_basename(__FILE__)) . '/lang/');
}

add_action('plugins_loaded', 'seoal_loaded');

function seoal_loaded() {
  do_action('seoal_loaded');
}
