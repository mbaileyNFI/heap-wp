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
		trigger_error( 'You need to define your HEAP_APP_ID in your wp-config.php file, `define( \'HEAP_APP_ID\', \'YOUR_APP_ID\' )`' );
		return;
	}
	?>
	<script id="heap-analytics" type="text/javascript">
		window.heap=window.heap||[],heap.load=function(e,t){window.heap.appid=e,window.heap.config=t=t||{};var r=t.forceSSL||"https:"===document.location.protocol,a=document.createElement("script");a.type="text/javascript",a.async=!0,a.src=(r?"https:":"http:")+"//cdn.heapanalytics.com/js/heap-"+e+".js";var n=document.getElementsByTagName("script")[0];n.parentNode.insertBefore(a,n);for(var o=function(e){return function(){heap.push([e].concat(Array.prototype.slice.call(arguments,0)))}},p=["addEventProperties","addUserProperties","clearEventProperties","identify","removeEventProperty","setEventProperties","track","unsetEventProperty"],c=0;c<p.length;c++)heap[p[c]]=o(p[c])};
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
	?>
	<script id="heap-analytics-identify" type="text/javascript">
		var url = '<?php echo esc_url( site_url( '?heap_get_user_id=' . hash_hmac( 'sha256', HEAP_APP_ID, 'Heap_WordPress_Integration' ) ) ); ?>';
		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', url, true );
		xhr.onload = function() {
			if ( 4 === xhr.readyState && 200 === xhr.status && xhr.responseText ) {
				console.warn('xhr.responseText', xhr.responseText);
				heap.identify( xhr.responseText );
			}
		};
		xhr.send(null);
	</script>
	<?php
}

/**
 * Listen for requests for the User ID from the Heap JS logged-in snippet.
 *
 * @since  0.1.0
 *
 * @return void
 */
function heap_check_for_user_id_request() {
	if ( defined( 'HEAP_APP_ID' ) && isset( $_POST['heap_get_user_id'] ) && $_POST['heap_get_user_id'] === hash_hmac( 'sha256', HEAP_APP_ID, 'Heap_WordPress_Integration' ) ) {
		$user_id = get_current_user_id();
		wp_send_json( $user_id, $user_id ? 200 : 400 );
	}
}
add_action( 'init', 'heap_check_for_user_id_request' );

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
	$body['properties']['email']        = $current_user->user_email;
	$body['properties']['first_name']   = isset( $current_user->first_name ) ? $current_user->first_name : '';
	$body['properties']['last_name']    = isset( $current_user->last_name ) ? $current_user->last_name : '';
	$body['properties']['display_name'] = isset( $current_user->display_name ) ? $current_user->display_name : '';
	$body['properties']['roles']        = isset( $user->roles ) ? implode( ', ', $user->roles ) : '';

	$args         = $base_args;
	$args['body'] = wp_json_encode( $body );

	wp_remote_post( 'https://heapanalytics.com/api/add_user_properties', $args );

}
add_action( 'wp_login', 'heap_track_login_and_user', 10, 2 );
