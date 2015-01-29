<?php /*
Plugin Name: RV sitemap
Version: 2.7.a
Description: Creates a sitemap for your gallery. Sitemaps are used to inform search engines about pages that are available for crawling.
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=78
Author: rvelices
Author URI: http://www.modusoptimus.com/
*/

add_event_handler('get_admin_plugin_menu_links', 'sitemap_plugin_admin_menu' );

function sitemap_plugin_admin_menu($menu)
{
  $menu[] = array(
      'NAME' => 'Sitemap',
      'URL' => get_admin_plugin_menu_link(dirname(__FILE__).'/sitemap.php')
    );
  return $menu;
}

?>
