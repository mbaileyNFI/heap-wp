<?php
/**
 * Plugin Name: Heap WordPress Integration
 * Plugin URI:  https://heapanalytics.com
 * Description: This plugin adds the Heap snippet to your WordPress site.
 * Version:     1.0
 * Author:      M Bailey
 * Author URI:  https://nficorporate.com
 * License:     GPL
 */

/**
 * Adds the Heap analytics snippet to the site's <head>
 *
 * @since  1.0
 *
 * @return void
 */
function heap_add_snippet_to_head() {
	if ( ! defined( 'HEAP_APP_ID' ) ) {
		trigger_error( 'You need to define your HEAP_APP_ID in your wp-config.php file, `define( \'HEAP_APP_ID\', \'YOUR_APP_ID\' )`' );
		return;
	}
	?>
	<script type="text/javascript">
	  window.heap=window.heap||[],heap.load=function(e,t){window.heap.appid=e,window.heap.config=t=t||{};var r=document.createElement("script");r.type="text/javascript",r.async=!0,r.src="https://cdn.heapanalytics.com/js/heap-"+e+".js";var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(r,a);for(var n=function(e){return function(){heap.push([e].concat(Array.prototype.slice.call(arguments,0)))}},p=["addEventProperties","addUserProperties","clearEventProperties","identify","resetIdentity","removeEventProperty","setEventProperties","track","unsetEventProperty"],o=0;o<p.length;o++)heap[p[o]]=n(p[o])};
	  heap.load("<?php echo esc_attr( HEAP_APP_ID ); ?>");
	</script>
	<?php

	if ( is_user_logged_in() ) {
		add_action( 'wp_footer', 'heap_identify_js_snippet', 9999 );
	}
}
add_action( 'wp_head', 'heap_add_snippet_to_head', 9999 );

/**
 * Adds the Heap analytics logged-in snippet to the site's footer.
 *
 * @since  0.1.0
 *
 * @return void
 */
function heap_identify_js_snippet() {
	$userid = wp_get_current_user();
	?>
	<script id="heap-analytics-identify" type="text/javascript">
		heap.identify("<?php echo esc_attr( $userid ); ?>");
	</script>
	<?php
}

/**
 * Tracks a login event and identifies the user and user properties with Heap.
 *
 * @since  0.1.0
 *
 * @param  string  $username The logged-in user's username.
 * @param  WP_User $user     User object
 *
 * @return void
 */
function heap_track_login_and_user( $username, $user ) {
	if ( ! ( $user instanceof WP_User ) || ! defined( 'HEAP_APP_ID' ) ) {
		return;
	}

	$base_args = array(
		'headers' => array(
			'Content-Type' => 'application/json',
		),
	);

	$body_args = array(
		'app_id'     => HEAP_APP_ID,
		'identity'   => $user->ID,
		'properties' => array(
			'user_name'  => $username,
			'user_id'    => $user->ID,
		),
	);


	// Track the user/login: https://heapanalytics.com/docs/server-side#track
	$body          = $body_args;
	$body['event'] = 'WordPress Login';
	$args          = $base_args;
	$args['body']  = wp_json_encode( $body );

	wp_remote_post( 'https://heapanalytics.com/api/track', $args );

	// Define User Properties: https://heapanalytics.com/docs/server-side#add-user-properties
	$body = $body_args;
	$body['properties']['email']        = $user->user_email;
	$body['properties']['first_name']   = isset( $user->first_name ) ? $user->first_name : '';
	$body['properties']['last_name']    = isset( $user->last_name ) ? $user->last_name : '';
	$body['properties']['display_name'] = isset( $user->display_name ) ? $user->display_name : '';
	$body['properties']['roles']        = isset( $user->roles ) ? implode( ', ', $user->roles ) : '';

	$args         = $base_args;
	$args['body'] = wp_json_encode( $body );

	wp_remote_post( 'https://heapanalytics.com/api/add_user_properties', $args );

}
add_action( 'wp_login', 'heap_track_login_and_user', 10, 2 );
