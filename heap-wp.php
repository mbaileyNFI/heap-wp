<?php
/**
 * Plugin Name: Heap WordPress Integration
 * Plugin URI:  https://heapanalytics.com
 * Description: This plugin adds the Heap snippet to your WordPress site.
 * Version:     0.1.0
 * Author:      Zao
 * Author URI:  http://zao.is
 * License:     GPL
 */

/**
 * Adds the Heap analytics snippet to the site's <head>
 *
 * @since  0.1.0
 *
 * @return void
 */
function heap_add_snippet_to_head() {
	if ( ! defined( 'HEAP_APP_ID' ) ) {
		return trigger_error( 'You need to define your HEAP_APP_ID in your wp-config.php file, `define( \'HEAP_APP_ID\', \'YOUR_APP_ID\' )`' );
	}
	?>
	<script type="text/javascript">
	  window.heap=window.heap||[],heap.load=function(e,t){window.heap.appid=e,window.heap.config=t=t||{};var r=t.forceSSL||"https:"===document.location.protocol,a=document.createElement("script");a.type="text/javascript",a.async=!0,a.src=(r?"https:":"http:")+"//cdn.heapanalytics.com/js/heap-"+e+".js";var n=document.getElementsByTagName("script")[0];n.parentNode.insertBefore(a,n);for(var o=function(e){return function(){heap.push([e].concat(Array.prototype.slice.call(arguments,0)))}},p=["addEventProperties","addUserProperties","clearEventProperties","identify","removeEventProperty","setEventProperties","track","unsetEventProperty"],c=0;c<p.length;c++)heap[p[c]]=o(p[c])};
	  heap.load("<?php echo esc_attr( HEAP_APP_ID ); ?>");
	</script>
	<?php
}
add_action( 'wp_head', 'heap_add_snippet_to_head', 9999 );
