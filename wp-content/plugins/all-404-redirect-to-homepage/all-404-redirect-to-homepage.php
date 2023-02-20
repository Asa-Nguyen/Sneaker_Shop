<?php
/*
Plugin Name: All 404 Redirect to Homepage
Plugin URI: https://www.wp-buy.com
Description: a plugin to redirect 404 pages to home page or any custom page
Author: wp-buy
Version: 3.7
Author URI: https://www.wp-buy.com
*/
register_activation_hook( __FILE__, 'p404_modify_htaccess' );
register_deactivation_hook( __FILE__, 'p404_clear_htaccess' );

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! defined( 'WP_CONTENT_DIR' ) ) exit;

define( 'OPTIONS404', 'options-404-redirect-group' );
require_once ('functions.php');
add_action('admin_menu', 'p404_admin_menu');
add_action('admin_head', 'p404_header_code');
add_action('wp', 'p404_redirect');
add_action( 'admin_enqueue_scripts', 'p404_enqueue_styles_scripts' );
add_action('wp_ajax_P404REDIRECT_HideMsg', 'P404REDIRECT_HideMsg'); 
add_action('wp_ajax_P404REDIRECT_HideAlert', 'P404REDIRECT_HideAlert'); 


function P404REDIRECT__filter_action_links( $links ) { 
	$links['settings'] = sprintf('<a href="%s">Settings</a>', admin_url( 'admin.php?page=all-404-redirect-to-homepage.php' )); 
	$network_dir_append = "";
	If (is_multisite()) $network_dir_append = "network/";
	$links['MorePlugins'] = sprintf('<a href="%s"><b style="color:#f18500">More Plugins</b></a>', admin_url( $network_dir_append . 'plugin-install.php?s=wp-buy&tab=search&type=author' )); 
	return $links;
}
add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), 'P404REDIRECT__filter_action_links', 10, 1 );



function p404_redirect()
{
	if(is_404()) 
	{
        $options= P404REDIRECT_get_my_options();
	    $link=P404REDIRECT_get_current_URL();
	    if($link == $options['p404_redirect_to'])
	    {
	        echo "<b>All 404 Redirect to Homepage</b> has detected that the target URL is invalid, this will cause an infinite loop redirection, please go to the plugin settings and correct the traget link! ";
	        exit(); 
	    }
	    
	 	if($options['p404_status']=='1' & $options['p404_redirect_to']!=''){
			$links = P404REDIRECT_read_option_value('links',0);
			P404REDIRECT_save_option_value('links', $links + 1);
			P404REDIRECT_add_redirected_link(P404REDIRECT_get_current_URL());
		 	header ('HTTP/1.1 301 Moved Permanently');
			header ("Location: " . $options['p404_redirect_to']);
			exit(); 
		}
	}
}

//---------------------------------------------------------------

function p404_get_site_404_page_path()
{
   $url= str_ireplace("://", "", site_url());
   $site_404_page = substr($url, stripos($url, "/"));  
    if (stripos($url, "/")=== FALSE || $site_404_page == "/")
       $site_404_page = "/index.php?error=404";
    else
       $site_404_page = $site_404_page . "/index.php?error=404";   
   return  $site_404_page;
}
//---------------------------------------------------------------

function p404_check_default_permalink() 
 {           
    $file= get_home_path() . "/.htaccess";

    $content="ErrorDocument 404 " . p404_get_site_404_page_path();

    $marker_name="FRedirect_ErrorDocument";
    $filestr ="";

    if(file_exists($file)){            
        $f = @fopen( $file, 'r+' );
        $filestr = @fread($f , filesize($file));            
        if (strpos($filestr , $marker_name) === false)
         {
             insert_with_markers( $file,  $marker_name,  $content ); 
         }
    }else
    {
       insert_with_markers( $file,  $marker_name,  $content ); 
    }

 }
 

//---------------------------------------------------------------

function p404_header_code()
{
	p404_check_default_permalink();
	      
}


function p404_enqueue_styles_scripts()
{
    if( is_admin() ) {              
        $css= plugins_url() . '/'.  basename(dirname(__FILE__)) . "/stylesheet.css";               
        wp_enqueue_style( 'main-404-css', $css );
    }
}
        

//---------------------------------------------------------------

function p404_admin_menu() {
	add_options_page('All 404 Redirect to Homepage', 'All 404 Redirect to Homepage', 'manage_options', basename(__FILE__), 'p404_options_menu'  );
}

//---------------------------------------------------------------
function p404_options_menu() {
	
	if (!current_user_can('manage_options'))  {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		
	include "option_page.php";
}

//---------------------------------------------------------------
$path = plugin_basename( __FILE__ );
add_action("after_plugin_row_{$path}", 'P404REDIRECT_after_plugin_row', 10, 3);




add_action( 'admin_enqueue_scripts', 'p404_include_js' );

function p404_include_js()
{

	$mypage = isset($_GET['page']) ? $_GET['page'] : '';
	
    if ($mypage == 'all-404-redirect-to-homepage.php') {
        if (!did_action('wp_enqueue_media')) {
            wp_enqueue_media();
        }
		
		
        wp_enqueue_script('myuploadscript', plugin_dir_url(__FILE__) . '/js/custom.js', array('jquery'));
    }
}



function p404_modify_htaccess()
{
    $options= P404REDIRECT_get_my_options();
    if(isset($options['img_p404_status']) && $options['img_p404_status'] == 1) {
        $image_id = isset($options['image_id_p404_redirect_to']) ? absint($options['image_id_p404_redirect_to']) : '';
        if ($image_id != '') {
            $image = wp_get_attachment_image_src($image_id);
            $image = $image[0];

            $ruls[] = <<<EOT
RewriteOptions inherit
<IfModule mod_rewrite.c>
RewriteEngine on
RewriteCond %{DOCUMENT_ROOT}%{REQUEST_URI} !-f
RewriteRule \.(gif|jpe?g|png|bmp) $image [NC,L]
</IfModule>

EOT;
//NC (no case, case insensitive, useless in this context) and L (last rule if applied)
            return p404_add_htaccess($ruls);
        }
    }
}


//echo WP_CONTENT_DIR;echo"<br>";
//echo ABSPATH;exit;
function p404_add_htaccess($insertion) {
    //Clear the old htaccess file located inside the main website directory
    $htaccess_file = WP_CONTENT_DIR.'/.htaccess';
    $filename = $htaccess_file;
    if (!file_exists($filename)) {
        touch($filename);
    }
    if (is_writable($filename)) {
        return array('status'=>true,'massage'=>p404_insert_with_markers_htaccess($htaccess_file, 'All_404_marker_comment_image', (array) $insertion));
    }else{
        return array('status'=>false,'massage'=>$insertion);
    }
}

function p404_clear_htaccess()
{
    $htaccess_file = WP_CONTENT_DIR.'/.htaccess';

    p404_insert_with_markers_htaccess($htaccess_file, 'All_404_marker_comment_image', "");
}

function p404_insert_with_markers_htaccess( $filename, $marker, $insertion ) {
    if (!file_exists( $filename ) || is_writeable( $filename ) ) {
        if (!file_exists( $filename ) ) {
            $markerdata = '';
        } else {
            $markerdata = explode( "\n", implode( '', file( $filename ) ) );
        }

        if ( !$f = @fopen( $filename, 'w' ) )
            return false;

        $foundit = false;
        if ( $markerdata ) {
            $state = true;
            foreach ( $markerdata as $n => $markerline ) {
                if (strpos($markerline, '# BEGIN ' . $marker) !== false)
                    $state = false;
                if ( $state ) {
                    if ( $n + 1 < count( $markerdata ) )
                        fwrite( $f, "{$markerline}\n" );
                    else
                        fwrite( $f, "{$markerline}" );
                }
                if (strpos($markerline, '# END ' . $marker) !== false) {
                    fwrite( $f, "# BEGIN {$marker}\n" );
                    if ( is_array( $insertion ))
                        foreach ( $insertion as $insertline )
                            fwrite( $f, "{$insertline}\n" );
                    fwrite( $f, "# END {$marker}\n" );
                    $state = true;
                    $foundit = true;
                }
            }
        }
        if (!$foundit) {
            fwrite( $f, "\n# BEGIN {$marker}\n" );
            if ( is_array( $insertion ))
                foreach ( $insertion as $insertline )
                    fwrite( $f, "{$insertline}\n" );
            fwrite( $f, "# END {$marker}\n" );
        }
        fclose( $f );
        return true;
    } else {
        return false;
    }
}
