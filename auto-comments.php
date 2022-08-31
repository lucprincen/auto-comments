<?php
/**
 * Plugin Name: Auto updating Comments
 * Plugin URI: http://www.chefduweb.nl
 * Description: Auto-updating comments using the Heartbeat API
 * Version: 1.1
 * Author: Luc Princen
 * Author URI: http://www.chefduweb.nl
 * Requires at least: 3.6
 * Tested up to: 3.6.1
 *
 */



	/**
	 * Updating the comment-timestamp so the Heartbeat can check it
	 *
	 * @access public
	 * @return void
	 */

	//Handles comments that go from hold to approved:
	add_action( 'comment_hold_to_approved', 'wpc_update_comment_time', 100, 2 );

	//Handles comments that are immediately approved (like admin-comments)
	add_action( 'comment_post', 'wpc_pre_update_comment_time', 100, 2 );


	//comment_post just sends along the comment id, we need the whole comment:
	function wpc_pre_update_comment_time( $comment_id, $status ){

		//comment_post sends along a status, if it's "succes", $status == 1
		if( $status == 1 ){

			$comment = get_comment( $comment_id );
			wpc_update_comment_time( $comment );

		}
	}


	function wpc_update_comment_time( $comment ){

		//get the post_id from the comment object:
		$post_id = $comment->comment_post_ID;
	
		//update the meta:
		update_post_meta( $post_id, '_wpc_comment_timestamp', time() );
		
	}



	/**
	 * Our Heartbeat's response on the server-side
	 *
	 * @access public
	 * @param array $response
	 * @param array $data
	 * @param string $screen_id (optional)
	 * @return array
	 */


	// Logged in users:
	add_filter( 'heartbeat_received', 'wpc_heartbeat_response', 10, 2 );
	 
	// Logged out users
	add_filter( 'heartbeat_nopriv_received', 'wpc_heartbeat_response', 10, 2 );
	

	function wpc_heartbeat_response( $response, $data ){

		if( isset( $data['wpc_comment_update'] ) ){
			
			global $wpdb;

			//get the newest comment-timestamp:
			// (we don't have to go to the DB for this everytime, post_meta gets cached and updated only when the records get updated)
			$last_commented = get_post_meta( $data['wpc_comment_update']['post_id'], '_wpc_comment_timestamp', true );
	
	 		//check the timestamp of our last known version versus the one in the heartbeat:
	 		if( $data['wpc_comment_update']['timestamp'] != $last_commented ){

	        	// We have data with our handle and a new comment! lets respond with something...
	        	// Get comments from the old timestamp up and post_id = $data['post_id'];
				$time = date( 'Y-m-d H:i:s', $last_commented );
				$post_id = $data['wpc_comment_update']['post_id'];
				

				//Query the new comments for this post that have been posted since:
	        	$_comments = $wpdb->get_results( $wpdb->prepare( "
	 					SELECT * 
	 					FROM $wpdb->comments 
	 					WHERE comment_post_ID = $post_id 
	 					AND comment_date >= '$time' 

	 				 "));


	        	//Now, output the newest comments in html:
				ob_start();
				
				wp_list_comments( array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 74,
				), $_comments );

				//get the output buffer and clean it:
				$html = ob_get_clean();

				//then add it to the response object we're sending back
				//including the updated timestamp.
				$response['wpc_comment_update'] = array(
			
	                'timestamp'	=> $last_commented,
	                'html' 		=> $html
			
	        	);

	 		}
		}
		
		return $response;

	}


	
	/**
	 * Load the scripts in the footer.
	 *
	 * @access public
	 * @return void
	 */
	add_action( 'wp_footer', 'wpc_enqueue_scripts' );

	function wpc_enqueue_scripts(){
	    
	    //only embed on a single page where comments are allowed:    
	    if( is_single() && comments_open() ){
	
	        //enqueue the js:
	        wp_enqueue_script( 'heartbeat' );
	        wp_enqueue_script( 'wpc_js', wpc_plugin_url().'/script.js' );
	    
	    	//Add some js variables like the current post id and the last comment timestamp:
	        global $post;
	        $args = array( 
	        	'post_id'   => $post->ID,
				'time'      => get_post_meta( $post->ID, '_wpc_comment_timestamp', true )
			);
	    
	        wp_localize_script( 'wpc_js', 'wpc', $args );

	    }
	
	}
	

	/**
	 * WPC_PLUGIN_URL returns the url of this plugin
	 *
	 * @access public
	 * @return string
	 */
	function wpc_plugin_url(){
	
		$full_path = plugin_dir_url(__FILE__);
		return substr( $full_path, 0 , -1 ); //strip the trailing slash
	
	}




	
?>