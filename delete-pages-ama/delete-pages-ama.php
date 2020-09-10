<?php
/**
 * @package DeletePagesAMA
 **/

/*
Plugin Name: Delete Pages
Plugin URI: https://github.com/abdelrahmanMA/delete-specific-pages
description: Deletes Specific Pages
Version: 1.0.0
Author: Abdelrahman Muhammad
Author URI: http://abdelrahmanma.com
*/

defined( 'ABSPATH' ) or die( 'You can\'t access this file.' );

class DeletePagesAMA
{
    function __construct(){
        add_action( 'admin_menu', array( $this, 'register_custom_menu_page') );
    }
    function activate(){
        $this->register_custom_menu_page();
        flush_rewrite_rules();
    }
    function deactivate(){
        flush_rewrite_rules();
    }
    function uninstall(){
        
    }

    function register_custom_menu_page() {
        add_menu_page('Delete Pages', 'Delete Pages', 'manage_options', 'deletepages', array( $this, '_custom_menu_page'), null, 6); 
    }
    
    function _custom_menu_page(){
        add_action('admin_enqueue_scripts', array( $this, 'admin_style' ) );
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        $css = "
        <style> 
            .wrap{
                width: 50%;
                margin: 10px auto;
            }        
        </style>";
        // echo $css;
		$count_posts = wp_count_posts();
		$count_posts = $count_posts->publish;
		if($count_posts > 8000)
			$disabled = "<script>var post = 'disabled'</script>";
		else
			$disabled = "";
        $page = "
        <div class='wrap'>
            <h1 class='wp-heading-inline'>Delete Specific Pages</h1>
            <form method='POST'>
                <textarea cols=50 rows=15 required name=purls></textarea>
                <div>
                    <h3>Select Type</h3>
                    <input type='radio' name='type' value='page' onchange='post_checked(this);' checked> Pages<br>
                    <input type='radio' name='type' value='post' onchange='post_checked(this);'> Posts
                </div>
                <div>
                    <br>
                    <h3>Should I delete pages in this list or pages that are NOT in this list?</h3>
                    <input type='radio' name='dmode' value='same' id='others' checked> Delete pages in this list<br>
                    <input type='radio' name='dmode' value='other' id='otherp'> Delete pages NOT in this list
                </div>
                <div>
                    <h3>Skip Trash</h3>
                    <input type='checkbox' name='directd'> Skip trash and permanently delete pages 
                </div>
                <br>
                <div>
                    <input type='submit' class='button action' value='Submit'>
                </div>
            </form>
        </div>
		<script>
			function post_checked(rpost){
				var otherp = document.getElementById('otherp');
				var others = document.getElementById('others');
				if(post == 'disabled' && rpost.checked && rpost.value == 'post'){
					otherp.disabled = true;
					others.checked = true;
					}
				else if(rpost.checked && rpost.value == 'page')
					otherp.disabled = false;
			}
		</script>";
       echo $disabled . $page;
    }
    function admin_style() {
        wp_enqueue_style('admin-styles', get_template_directory_uri().'/admin.css');
      }
      
}

if ( class_exists( 'DeletePagesAMA' ) ){
    $deletePagesAMA = new DeletePagesAMA();
}

register_activation_hook( __FILE__, array( $deletePagesAMA, 'activate' ) );
register_deactivation_hook( __FILE__, array( $deletePagesAMA, 'deactivate' ) );

function validatesAsInt($number)
{
    $number = filter_var($number, FILTER_VALIDATE_INT);
    return ($number !== FALSE);
}

function handle_POST(){
    if ( current_user_can( 'manage_options' ) )  {
        if( isset( $_POST['purls'] ) ){
            $dmode = $_POST['dmode'];
            $purls = $_POST['purls'];
            $type = $_POST['type'];
            if ( isset( $_POST['directd'] ) ){
                $direct = true;
            }
            else{
                $direct = false;
            }
            $purls = preg_split( "/[;,\n\s]/", $purls );
            if( is_numeric( $purls[0] ) ){
                if( validatesAsInt( $purls[0] ) ){
                    $mode = 'id';
                }
            }
            else{
                $mode = 'url';
            }
            
            $purls = array_flip($purls);
            
            if($type == 'page'){
                $websitePages = get_pages();
            }
            else{
				$count_posts = wp_count_posts();
				$count_posts = $count_posts->publish;
				if($count_posts < 8000)
                	$websitePages = get_posts( array('posts_per_page' => 8000) );
				else{
					if( $mode === 'id'){
						foreach ( $purls as $pid => $inde){
							if ( get_post_status ( $pid ) ) {
								wp_delete_post($pid, $direct);
							}
						}
					}
					elseif( $mode === 'url' ){
						foreach ( $purls as $url => $inde){
							$pid = url_to_postid( $url );
							if ( get_post_status ( $pid ) ) {
								wp_delete_post($pid, $direct);
							}
						}
					}
				}
            }
            if( $mode === 'id' ){
                foreach ( $websitePages as $page){
                    if(!isset($purls[$page->ID]) && $dmode == 'other'){
                        wp_delete_post($page->ID, $direct);
                    }
                    elseif( isset($purls[$page->ID]) && $dmode == 'same'){
                        wp_delete_post($page->ID, $direct);
                    }
                }
            }
            elseif( $mode === 'url' ){
                foreach ( $websitePages as $page){
                    if(!isset($purls[get_permalink($page->ID)]) && $dmode == 'other'){
                        wp_delete_post($page->ID, $direct);
                    }
                    elseif( isset($purls[get_permalink($page->ID)]) && $dmode == 'same'){
                        wp_delete_post($page->ID, $direct);
                    }
                }
            }
        }
    }
}
add_action('wp_loaded', 'handle_POST');