<?php
/*
Plugin name: Rotate HTML
Description: This plugin makes an AJAX call to display evenly rotated text/HTML
Version: 1.0.0
Author: nalonsopress
License: GPL2
*/


class Athletics_Rotate_HTML {

	function get_next( $category = '' ) {

		global $wpdb;

		$table_name = $wpdb->prefix . 'rotate_html';
	  	$sql = 'SELECT rotate_html_id, text FROM '. $table_name . " WHERE visible='yes' ";
		$sql .= ( $category!='' ) ? " AND category = '$category'" : '' ;
		$sql .= ' ORDER BY timestamp, rotate_html_id LIMIT 1 ';
		$row = $wpdb->get_row( $sql );
		
		// update the timestamp of the row we just seleted (used by rotator, not by random)
		if( intval( $row->rotate_html_id ) ) {
			$sql = 'UPDATE ' . $table_name . ' SET timestamp = Now() WHERE rotate_html_id = ' . intval( $row->rotate_html_id );
			$wpdb->query( $sql );
		}
		
		// now we can safely render shortcodes without self recursion (unless there is only one item containing [randomtext] shortcode - don't do that, it's just silly!)
		$snippet = do_shortcode( $row->text );
		
		return $snippet;
	}
	
	function update( $new_instance, $old_instance ) {

	  	$instance = $old_instance;
	  	$instance['title'] = strip_tags( stripslashes( $new_instance['title'] ) );
		$instance['category'] = strip_tags( strip_tags( stripslashes( $new_instance['category'] ) ) );
		$instance['pretext'] = $new_instance['pretext'];
		$instance['posttext'] = $new_instance['posttext'];
		$instance['random'] = intval( $new_instance['random'] );

	  	return $instance;
	}
	
	function form( $instance ) {
		
		$instance = wp_parse_args( (array)$instance, array( 'title' => 'Random Text', 'category' => '', 'pretext' => '', 'posttext' => '' ) );
		
		$title = htmlspecialchars( $instance['title'] );
		$category = htmlspecialchars( $instance['category'] ) ;
		$pretext = htmlspecialchars( $instance['pretext'] );
		$posttext = htmlspecialchars( $instance['posttext'] );
		if( !isset( $instance['random'] ) ) { $instance['random'] = 0; }
  
		echo '<p>
				<label for="' . $this->get_field_name( 'title' ) . '">Title: </label> 
				<input type="text" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" value="' . $title . '"/>
			</p><p>
				<label for="' . $this->get_field_name( 'pretext' ) . '">Pre-Text: </label> 
				<input type="text" id="' . $this->get_field_id( 'pretext' ) . '" name="' . $this->get_field_name( 'pretext' ) . '" value="' . $pretext . '"/>
			</p><p>
				<label for="' . $this->get_field_name( 'category' ) . '">Category: </label>
				<select id="' . $this->get_field_id( 'category' ) . '" name="' . $this->get_field_name( 'category' ) . '">
				<option value="">All Categories </option>';
		echo rotate_html_get_category_options( $instance['category'] );
		echo '</select></p>
			<p>
				<label for="' . $this->get_field_name( 'posttext' ) . '">Post-Text: </label> 
				<input type="text" id="' . $this->get_field_id( 'posttext' ) . '" name="' . $this->get_field_name( 'posttext' ) . '" value="' . $posttext . '"/>
			</p>
			<p>
				<label for="' . $this->get_field_name( 'random' ) . '">Selection: </label> 
				<select id="' . $this->get_field_id( 'random' ).'" name="'.$this->get_field_name( 'random' ).'">
				<option value="1" '.selected( intval( $instance['random']), 1 ).'>Random</option>
				<option value="0" '.selected( intval( $instance['random']), 0 ).'>Rotation</option>
				</select><br/>
				<span class="description">Note: Random can be more intensive with large record sets, and some items may never appear.</span>
			</p>'; 
	}

	function enqueue_scripts() {
		wp_enqueue_script(
			'rotate_html'//$handle
			,plugins_url() . "/athletics-rotate-html/js/rotate_html.js" //$src
			,array( 'jquery' ) //$deps (dependencies)
			,'1.0' //$ver
			,false //$in_footer
		);
		wp_localize_script( 'rotate_html', 'arh_ajax', array( 'url' => home_url( 'wp-admin/admin-ajax.php' ), 'nonce' => wp_create_nonce( 'arh_ajax_nonce' ) ) );
	}
}

function rotate_html( $category ) {
	$rotate_html = new Athletics_Rotate_HTML;
	echo $rotate_html->get_next( $category );
}

function rotate_html_get_category_options( $category='' ) {

	global $wpdb;

	$table_name = $wpdb->prefix . 'rotate_html';
	$sql = 'SELECT category FROM ' . $table_name . ' GROUP BY category ORDER BY category';
	$rows = $wpdb->get_results( $sql );
	
	$option_nocategory = false;
	$nocategory_name = 'No Category';
	
	foreach ( $rows as $row ) {
		$selected = ( $category==$row->category ) ? 'SELECTED' : '';
		$categoryname = $row->category;		
		if ( trim( $categoryname ) == '' ) {
			$categoryname = $nocategory_name;
			$option_nocategory = true;
		}
		$result .= '<option value="' . $row->category . '" ' . $selected . '>' . $categoryname . ' </option>';
	}
	if( !$option_nocategory )
		$result = '<option value="">' . $nocategory_name . ' </option>' . $result;
	return $result;
}

register_activation_hook( __FILE__, 'rotate_html_install' );
function rotate_html_install() {

	global $wpdb, $user_ID;

	$table_name = $wpdb->prefix . 'rotate_html';
	// create the table if it doesn't exist 
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
		$sql = "CREATE TABLE `$table_name` (
			`rotate_html_id` int(10) unsigned NOT NULL auto_increment,
			`category` varchar(32) character set utf8 NOT NULL,
			`text` text character set utf8 NOT NULL,
			`visible` enum('yes','no') NOT NULL default 'yes',
			`user_id` int(10) unsigned NOT NULL,
			`timestamp` timestamp NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY  (`rotate_html_id`),
			KEY `visible` (`visible`),
			KEY `category` (`category`),
			KEY `timestamp` (`timestamp`) 
		)";
		$results = $wpdb->query( $sql );
	}
}

if ( is_admin() ) {
	$plugin_basename = plugin_basename( __FILE__ ); 
	include 'rotate_html_admin.php';
}

add_action( 'wp_enqueue_scripts', array( 'Athletics_Rotate_HTML', 'enqueue_scripts' ) );

add_action( 'wp_ajax_arh_rotate_html', 'arh_rotate_html' );
add_action( 'wp_ajax_nopriv_arh_rotate_html', 'arh_rotate_html' );
	function arh_rotate_html( ) {
		rotate_html( $_GET['category'] );
		die;
	}	

?>
