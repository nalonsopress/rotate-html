<?php

$rotate_html_admin_url = admin_url().'options-general.php?page=rotatehtml';

add_action('admin_menu', 'rotate_html_menu');
function rotate_html_menu() {
	add_options_page('Rotate HTML', 'Rotate HTML', 'update_plugins', 'rotatehtml', 'rotate_html_options');
}

// Add settings link on plugin page
function rotate_html_settings_link( $links ) { 
  $settings_link = '<a href="options-general.php?page=rotatehtml">Settings</a>'; 
  array_unshift( $links, $settings_link ); 
  return $links; 
}
add_filter( "plugin_action_links_$plugin_basename", 'rotate_html_settings_link' );


function rotate_html_options() {
	if ( $_POST ) {
		// process the posted data and display summary page - not pretty :(
		rotate_html_save( $_POST );
	}

	$action = isset( $_GET['action'] ) ? $_GET['action'] : false;
	switch( $action ){
		case 'new' :
			rotate_html_edit();
			break;
		case 'edit' :
			$id = intval( $_GET['id'] );
			rotate_html_edit( $id );
			break;
		case 'delete' :
			$id = intval( $_GET['id'] );
			check_admin_referer( 'rotate_html_delete' . $id );
			rotate_html_delete( $id );
			// now display summary page
			rotate_html_list();
			break;
		default:
			rotate_html_list();
	}
}

function rotate_html_page_title( $suffix='' ) {
 return '
 <div id="icon-options-general" class="icon32"><br/></div><h2>Rotate HTML '.$suffix.'</h2>
 ';
}

function rotate_html_error( $text='An undefined error has occured.' ) {
	echo '<div class="wrap">' . rotate_html_page_title( ' - ERROR!' ) . '<h3>' . $text . '</h3></div>';
}
 
function rotate_html_list() {
	global $wpdb, $user_ID, $rotate_html_admin_url;
	$table_name = $wpdb->prefix . 'rotate_html';
	$pageURL = $rotate_html_admin_url;
	$cat = isset( $_GET['cat'] ) ? $_GET['cat'] : false;
	$author_id = isset( $_GET['author_id'] ) ? intval( $_GET['author_id'] ) : 0;
	$where = $page_params = '';

	if( $cat ) {
		$where = " WHERE category = '$cat'";
		$page_params = '&cat='.urlencode( $cat );
	}
	if( $author_id ) {
		$where = " WHERE user_id = $author_id";
		$page_params .= '&author_id='.$author_id;
	}
	
	// pagination related

	$item_count = $wpdb->get_row( "Select count(*) items FROM $table_name $where" );
	if( isset( $item_count->items ) ) {
		$totalrows = 	$item_count->items;
	} else {
		echo '<h3>The expected database table "<i>' . $table_name . '</i>" does not appear to exist.</h3>';
	}
	
	$perpage = 20;
	$paged = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 0;
	$paged = $paged ? $paged : 1;

	$num_pages = 1 + floor( $totalrows / $perpage );

	if($paged > $num_pages) { $paged = $num_pages; }
	
	$del_paged = ( $paged > 1 ) ? '&paged='.$paged : ''; // so we stay on the current page if we delete an item
	
	$paging = paginate_links( array(
		'base' => $pageURL . $page_params . '%_%', // add_query_arg( 'paged', '%#%' ),
		'format' => '&paged=%#%',
		'prev_text' => __( '&laquo;' ),
		'next_text' => __( '&raquo;' ),
		'total' => $num_pages,
		'current' => $paged
		) );
	
	// now load the data to display

	$startrow = ( $paged - 1 ) * $perpage;	
	$rows = $wpdb->get_results( "SELECT * FROM $table_name $where ORDER BY rotate_html_id LIMIT $startrow, $perpage" );
	$item_range = count( $rows );
	if( $item_range > 1 ) {
		$item_range = ( $startrow + 1 ) . ' - ' . ( $startrow + $item_range );
	}
	
	$author = array();

	?>
<div class="wrap">
	<?php echo rotate_html_page_title(); ?>
	<div class="tablenav">
		<div class="alignleft actions">
			<input type="submit" class="button-secondary action" id="rotate_html_add" name="rotate_html_add" value="Add New" onclick="location.href='options-general.php?page=rotatehtml&action=new'"/>
			Category: <select id="rotate_html_category" name="rotate_html_category" onchange="javascript:window.location='<?php echo $pageURL . '&cat='; ?>'+(this.options[this.selectedIndex].value);">
			<option value="">View all categories </option>
			<?php echo rotate_html_get_category_options( $cat ); ?>
			</select>
		</div>
		<div class="tablenav-pages">
			<span class="displaying-num">Displaying <?php echo $item_range . ' of ' . $totalrows; ?></span>
			<?php echo $paging; ?>
		</div>
	</div>

	<table class="widefat">
	<thead><tr>
		<th>ID</th>
		<th>Text</th>
		<th width="10%">Category</th>
		<th width="10%">Author</th>
		<th width="10%">Action</th>
	</tr></thead>
	<tbody>
<?php		
	$alt = '';
	foreach ( $rows as $row ) {
		$alt = ( $alt ) ? '' : ' class="alternate"'; // stripey :)
		if( !isset( $author[$row->user_id] ) ){
			$user_info = get_userdata( $row->user_id );
			$author[$row->user_id] = $user_info->display_name;
		}
		// $status = ( $row->visible=='yes' ) ? 'visible' : 'hidden';
		$bytes = strlen( $row->text );
		if( strlen( $row->text ) > 200 ) {
			$row->text = trim(mb_substr( $row->text, 0, 350, 'UTF-8' ) ) . '...';
		}
		echo '<tr' . $alt . '>
		<td>' . $row->rotate_html_id . '</td>
		<td>' . esc_html($row->text) . '</td>
		<td><a href="' . $pageURL . '&cat=' . $row->category . '">' . $row->category . '</a><br />' . $status . '</td>
		<td class="author column-author"><a href="' . $pageURL . '&author_id=' . $row->user_id . '">' . $author[ $row->user_id ] . '</a><br />' . $bytes . ' bytes</td>
		<td><a href="' . $pageURL . '&action=edit&id=' . $row->rotate_html_id . '">Edit</a><br />';
		$del_link = wp_nonce_url( $pageURL . $del_paged . '&action=delete&id=' . $row->rotate_html_id, 'rotate_html_delete'  .  $row->rotate_html_id );
		echo '<a onclick="if ( confirm(\'You are about to delete post #' . $row->rotate_html_id . '\n Cancel to stop, OK to delete.\') ) { return true; }return false;" href="' . $del_link . '" title="Delete this post" class="submitdelete">Delete</a>';
		echo '</td></tr>';		
	}
	echo '</tbody></table>';

  echo '</div>';
}

function rotate_html_edit( $rotate_html_id = 0 ) {
	
	echo '<div class="wrap">';
	$title = '- Add New';
	if ( $rotate_html_id ) {
		$title = '- Edit';
		
		global $wpdb;
		$table_name = $wpdb->prefix . 'rotate_html';
		$sql = "SELECT * from $table_name where rotate_html_id=$rotate_html_id";
		$row = $wpdb->get_row( $sql );
		if ( !$row ) {
			$error_text = '<h2>The requested entry was not found.</h2>';
		}
	} else {
		$row = new stdClass();
		$row->text = '';
		$row->visible = 'yes';
	}
	echo rotate_html_page_title( $title ); 
	
	if ( $rotate_html_id && !$row ) {
		echo '<h3>The requested entry was not found.</h3>';
	} else {
	// display the add/edit form 
	global $rotate_html_admin_url;
	
		echo '<form method="post" action="' . $rotate_html_admin_url . '">
			' . wp_nonce_field( 'rotate_html_edit' . $rotate_html_id ) . '
			<input type="hidden" id="rotate_html_id" name="rotate_html_id" value="' . $rotate_html_id . '">
			<h3>Text To Display</h3>
			<textarea name="rotate_html_text" style="width: 80%; height: 100px;">' . apply_filters( 'format_to_edit', $row->text ) . '</textarea>
			<h3>Category</h3>
			<p>Select a category from the list or enter a new one.</p>
			<label for="rotate_html_category">Category: </label><select id="rotate_html_category" name="rotate_html_category">'; 
		echo rotate_html_get_category_options( $row->category );
		echo '</select></p>
			<p><label for="rotate_html_category_new">New Category: </label><input type="text" id="rotate_html_category_new" name="rotate_html_category_new"></p>';
		echo '<div class="submit">
			<input class="button-primary" type="submit" name="rotate_html_save" value="Save Changes" />
			</div>
			</form>
			
			<p>Return to <a href="' . $rotate_html_admin_url . '">Rotate HTML summary page</a>.</p>';
	}
  echo '</div>';	
}

function rotate_html_save( $data ) {
	global $wpdb, $user_ID;
	$table_name = $wpdb->prefix . 'rotate_html';
	
	$rotate_html_id = intval( $data['rotate_html_id'] );
	check_admin_referer( 'rotate_html_edit' . $rotate_html_id );
	
	$sqldata = array();
	$category_new = trim( $data['rotate_html_category_new'] );
	$sqldata['category'] = ( $category_new ) ? $category_new : $data['rotate_html_category'];
	$sqldata['user_id'] = $user_ID;
	$sqldata['visible'] = 'yes';
	
	$sqldata['text'] = trim( stripslashes( $data['rotate_html_text'] ) );
	if ( $rotate_html_id ) {
		$wpdb->update( $table_name, $sqldata, array( 'rotate_html_id'=>$rotate_html_id ) );
	} else {
		$wpdb->insert( $table_name, $sqldata );
	}
}

function rotate_html_delete( $id ) {

	global $wpdb;

	$table_name = $wpdb->prefix . 'rotate_html';
	$id = intval( $id );
	$sql = "DELETE FROM $table_name WHERE rotate_html_id = $id";
	$wpdb->query( $sql );
}

?>