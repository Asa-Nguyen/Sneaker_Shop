<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include_once "cf_dropdown.php";

global $wpdb,$table_prefix;

$redirect_to = (isset($_POST['redirect_to'])) ? sanitize_text_field($_POST['redirect_to']) : '';
$nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : '';

if($redirect_to !=='')
{

    if(wp_verify_nonce( $nonce, 'p404home_nounce' ))
	{
		P404REDIRECT_save_option_value('p404_redirect_to',$redirect_to);
        P404REDIRECT_save_option_value('p404_status',sanitize_text_field($_POST['p404_status']));
        P404REDIRECT_save_option_value('img_p404_status',sanitize_text_field($_POST['img_p404_status']));
        P404REDIRECT_save_option_value('image_id_p404_redirect_to',sanitize_text_field($_POST['misha-img']));
        if(isset($_POST['img_p404_status']) && $_POST['img_p404_status'] == 1 ) {
            if(isset($_POST['misha-img']) && $_POST['misha-img'] != ''){
                $mod_file = p404_modify_htaccess();

                if($mod_file['status']){
                    P404REDIRECT_option_msg('Options Saved!');
                }else{
                    ?>
                    <div class="no_access">
                        Your .htaccess file is not writable; You need to modify the file manually.<br>
                        Please copy and paste the following code into the first line of the .htaccess file <br>
                        <code>
                        <?php
                        $image = wp_get_attachment_image_src(absint($_POST['misha-img']));
            $image = $image[0];

            ?>
                            <br><br>
RewriteOptions inherit<br>
&#60;IfModule mod_rewrite.c><br>
RewriteEngine on<br>
RewriteCond %{DOCUMENT_ROOT}%{REQUEST_URI} !-f<br>
RewriteRule \.(gif|jpe?g|png|bmp) <?php echo esc_url($image);?> [NC,L]<br>
&#60;/IfModule><br>

                        </code><br><br>
                    </div>
                    <?php
                }
            }else{
                P404REDIRECT_warning_option_msg('Please upload a new default image');
            }

        }else{
            p404_clear_htaccess();
            P404REDIRECT_option_msg('Options Saved!');
        }


	}else
	{
		P404REDIRECT_failure_option_msg('Unable to save data!');
    }
}
$options= P404REDIRECT_get_my_options();
?>

<?php
if(P404REDIRECT_there_is_cache()!='')
P404REDIRECT_info_option_msg("You have a cache plugin installed <b>'" . P404REDIRECT_there_is_cache() . "'</b>, you have to clear cache after any changes to get the changes reflected immediately! ");
?>
<style>
.nav-tab-active, .nav-tab-active:focus, .nav-tab-active:focus:active, .nav-tab-active:hover {
    border-bottom: 1px solid #fff;
    background:#fff;
    color:#000;
}
#tabs_content {
  padding: 20px;
  background-color: #fff;
  border: 1px solid #ccc;
  border-top: 0;
}
a.misha-upl>span {
    text-decoration: underline;

	
}

a.misha-rmv {
    text-decoration: underline;
}

.no_access {
    background-color: #f7dbdb;
    width: 98%;
    border: 2px dashed red;
    padding: 20px;
    font-size: 15px;
    font-weight: bold;
}
.form-input-titles{
width:300px; line-height:40px
}
</style>
<div class="wrap">
<div ><div class='inner'>
<h2>All 404 Redirect to Homepage</h2>

<?php
$mytab = isset($_REQUEST['mytab']) ? sanitize_text_field($_REQUEST['mytab']) : "options";

?>

<nav class="nav-tab-wrapper wp-clearfix" aria-label="Secondary menu">
<a href="?page=all-404-redirect-to-homepage.php&mytab=options" class="nav-tab <?php if($mytab=='options' ) echo 'nav-tab-active';?>">Options</a>
<a href="?page=all-404-redirect-to-homepage.php&mytab=404urls" class="nav-tab <?php if($mytab=='404urls' ) echo 'nav-tab-active';?>">404 URLs</a>
</nav>
<div id="tabs_content">
<?php
// ----   Options Tab ----------------
if($mytab == "options"){
?>
<form method="POST">
    <h3>404 Pages</h3>
	<div class="form-input-titles">404 Redirection Status:</div>
	<?php
		$drop = new p404redirect_dropdown('p404_status');
		$drop->add('Enabled','1');
		$drop->add('Disabled','2');
		$drop->dropdown_print();
		$drop->select($options['p404_status']);
	?>

	<br/><br/>

	<div class="form-input-titles">Redirect all 404 pages to:</div>
	<input type="text" name="redirect_to" id="redirect_to" size="30" value="<?php echo esc_attr($options['p404_redirect_to']);?>">
	<br>
    <br>
    
    <h3>404 Images</h3>
    <div class="form-input-titles">Image 404 Redirection Status:</div>
    <?php
    $drop = new p404redirect_dropdown('img_p404_status');
    $drop->add('Enabled','1');
    $drop->add('Disabled','2');
    $drop->dropdown_print();
    $image_status_val = '';
    if(isset($options['img_p404_status'])){
        $image_status_val = $options['img_p404_status'];
    }
    $drop->select($image_status_val);
    ?>
	<b>Try our <a style="color:red" href="https://wordpress.org/plugins/broken-images-redirection" target="_blank">Broken Images Redirection</a> Plugin for more options</b>

    <br/><br/>
	
	<div id="myimage404_lable">
    Redirect all 404 images to:
	</div>
    <?php
    $image_id = isset($options['image_id_p404_redirect_to'])?absint($options['image_id_p404_redirect_to']):'';
    if( !wp_get_attachment_image_src($image_id) &&  $image_id == '') {
        
    ?>
        <a href="#" class="misha-upl"><span>Upload image</span></a>
          <a href="#" onclick="javascript:document.getElementById('myimage404').style.display='none'; document.getElementById('myimage404_lable').style.display='none'; " class="misha-rmv" style="display:none">Remove image</a>
          <input type="hidden" class="misha-img" name="misha-img" value="" >
    <?php
    }
    ?>
	<br/>
	
	<?php
	if( wp_get_attachment_image_src($image_id) &&  $image_id != '') {
        $image = wp_get_attachment_image_src($image_id);
	    ?>
		
		<br><a href="#" class="misha-upl"><img id="myimage404" src="<?php echo esc_url($image[0]);?>" style="max-width: 80px;"/></a><br>
	      <a href="#" onclick="javascript:document.getElementById('myimage404').style.display='none'; document.getElementById('myimage404_lable').style.display='none'; "  class="misha-rmv">Remove image</a>
	      <input type="hidden" class="misha-img" name="misha-img" value="<?php esc_attr_e($image_id);?>">
    <?php
    }?>
		
<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo $nonce = wp_create_nonce('p404home_nounce'); ?>" />
<br />
<br />
<hr>
<br />
<input  class="button-primary" type="submit" value="  Update Options  " name="Save_Options"></form>
<?php
}else if($mytab == "404urls"){
// ----   404 URLs Tab ----------------
?>
<div>

<hr/>

   <b style="color:red"><?php echo esc_html(P404REDIRECT_read_option_value('links',0));?></b> URLs redirected since the plugin install in <?php echo esc_attr(P404REDIRECT_read_option_value('install_date',date("Y-m-d h:i a")));?>
<hr/>
<b>Latest 20 URLs Redirected: </b><br/><br/>


    <table class="wp-list-table widefat striped">
    <thead>
    <tr>
        <th width="30">#</th>
        <th width="150">Date</th>
        <th>URL</th>
    </tr>
    </thead>
    <tbody>


        <?php
$links = P404REDIRECT_read_option_value('redirected_links',array());
if(count($links)==0){
?>
<tr><td colspan="3">No 404 links redirected yet.</td></tr>
<?php
    } else{
    for($i=0; $i<count($links); $i++){
?>
    <tr>
    <td><?php echo (int) $i+1; ?></td>
    <td><?php echo esc_html($links[$i]['date']);?></td>
    <td><a target="_blank" href="<?php echo esc_url($links[$i]['link']);?>"><?php echo esc_url($links[$i]['link']);?></a></td>
    </tr>
<?php }} ?>


    </tbody>
    </table>
    <br/></div>

<?php
}
// ----   End of Tabs ----------------
?>
    <br/>
	<hr />
    <b style="color:red">Have many broken links?</b> keep track of 404 errors using our powerfull <a target="_blank" href="https://www.wp-buy.com/product/seo-redirection-premium-wordpress-plugin/#404plugin_msg">SEO Redirection Plugin</a> with an advanced <b>404 Manager</b> and set many rules to handle 404 links
	<p>
	<a href="https://www.wp-buy.com/product/seo-redirection-premium-wordpress-plugin/#404plugin_img" target="_blank"><img src="<?php echo esc_url(plugin_dir_url(__FILE__));?>/images/seopro.png" /></a>
	</p>
</div></div></div>
