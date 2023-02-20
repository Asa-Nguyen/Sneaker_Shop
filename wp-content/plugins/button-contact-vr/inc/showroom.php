<!--  * Version: 4.1 -->
<div class="wrap">
<h2>Contact Form Popup (Beta)</h2>
<h2 class="nav-tab-wrapper">
    <a href="admin.php?page=contact_vr" class="nav-tab">Button contact</a>
    <a href="admin.php?page=contact_vr_showroom" class="nav-tab  nav-tab-active">Showroom</a>
    <a href="admin.php?page=contact_vr_contact_form" class="nav-tab">Contact form (beta)</a>
    <a href="admin.php?page=contact_vr_all_in_one" class="nav-tab">All in one</a>
    <a href="admin.php?page=contact_vr_setting" class="nav-tab">Setting</a>
</h2>
<?php if( isset($_GET['settings-updated']) ) { ?>
    <div id="message" class="updated">
        <p><strong><?php _e('Settings saved.') ?></strong></p>
    </div>
<?php } ?>

<form method="post" action="options.php">
    <?php settings_fields( 'pzf_settings_showroom' ); ?>
    <table class="form-table">     
        <tr valign="top">
            <th scope="row">Enable:</th>
            <td><input id="pzf_enable_showroom" name="pzf_enable_showroom" type="checkbox" value="1" <?php echo get_option('pzf_enable_showroom') == '1' ? 'checked="checked"' : '' ?> />
            </td>
        </tr>   
        <tr valign="top">
            <th scope="row">Color icon showroom</th>
            <td>
                <label for="pzf_color_contact_form">
                    <input id="pzf_color_showroom" class="my-color-field" name="pzf_color_showroom" type="text" value="<?php echo get_option('pzf_color_showroom'); ?>" />
                </label>
            </td>
        </tr> 
        <tr valign="top">
            <th scope="row">Style icon </th>
            <td>
                <ul id="style-icon-vr">
                    <li>
                        <label for="pzf_icon_contact_form1">
                            <span style="background: <?php echo get_option('pzf_color_showroom') ? get_option('pzf_color_showroom') : '#2196f3'; ?>"><img src="/wp-content/plugins/button-contact-vr/img/showroom1.png"></span>
                            <input id="pzf_icon_contact_form1" name="pzf_icon_showroom" type="radio" value="1" <?php echo get_option('pzf_icon_showroom') == '1' ? 'checked="checked"' : '' ?> />icon 1
                        </label>
                    </li>
                    <li>
                        <label for="pzf_icon_contact_form2">
                            <span style="background: <?php echo get_option('pzf_color_showroom') ? get_option('pzf_color_showroom') : '#2196f3'; ?>"><img src="/wp-content/plugins/button-contact-vr/img/showroom2.png"></span>
                            <input id="pzf_icon_contact_form2" name="pzf_icon_showroom" type="radio" value="2" <?php echo get_option('pzf_icon_showroom') == '2' ? 'checked="checked"' : '' ?> />icon 2
                        </label>
                    </li>
                    <li>
                        <label for="pzf_icon_contact_form3">
                            <span style="background: <?php echo get_option('pzf_color_showroom') ? get_option('pzf_color_showroom') : '#2196f3'; ?>"><img src="/wp-content/plugins/button-contact-vr/img/showroom3.png"></span>
                            <input id="pzf_icon_contact_form3" name="pzf_icon_showroom" type="radio" value="3" <?php echo get_option('pzf_icon_showroom') == '3' ? 'checked="checked"' : '' ?> />icon 3
                        </label>
                    </li>
                    <li>
                        <label for="pzf_icon_contact_form4">
                            <span style="background: <?php echo get_option('pzf_color_showroom') ? get_option('pzf_color_showroom') : '#2196f3'; ?>"><img src="/wp-content/plugins/button-contact-vr/img/showroom4.png"></span>
                            <input id="pzf_icon_contact_form4" name="pzf_icon_showroom" type="radio" value="4" <?php echo get_option('pzf_icon_showroom') == '4' ? 'checked="checked"' : '' ?> />icon 4
                        </label>
                    </li>
                    <li>
                        <label for="pzf_icon_contact_form5">
                            <span style="background: <?php echo get_option('pzf_color_showroom') ? get_option('pzf_color_showroom') : '#2196f3'; ?>"><img src="/wp-content/plugins/button-contact-vr/img/showroom5.png"></span>
                            <input id="pzf_icon_contact_form5" name="pzf_icon_showroom" type="radio" value="5" <?php echo get_option('pzf_icon_showroom') == '5' ? 'checked="checked"' : '' ?> />icon 5
                        </label>
                    </li>
					</ul>                
				</td>
			</tr>  
            <tr valign="top">
                <th scope="row">Link</th>
                <td>
                    <label for="pzf_link_showroom">
                        <input id="pzf_link_showroom" name="pzf_link_showroom" type="text" value="<?php echo get_option('pzf_link_showroom'); ?>" />
                    </label><br>
                    <i>Link google map, link showroom, location, ...</i>
                </td>
            </tr>    
			<tr valign="top"  style=" border-bottom: 1px dashed #bfbfbf; ">
				<th scope="row">Open link new tab:</th>
				<td><input id="pzf_link_newtab_showroom" name="pzf_link_newtab_showroom" type="checkbox" value="1" <?php echo get_option('pzf_link_newtab_showroom') == '1' ? 'checked="checked"' : '' ?> />
				</td>
			</tr> 
			<tr><th>Use popups instead of links</th></tr>
            <tr valign="top">
                <th scope="row">List showroom</th>
                <td><textarea class="tiny" placeholder="" name="pzf_content_showroom"/><?php echo get_option('pzf_content_showroom'); ?></textarea><br>
                    <i>Shortcode / showroom content</i></td>
            </tr> 	
            <tr valign="top">
                <th scope="row">Max width popup</th>
                <td><input type="number" placeholder="" name="pzf_max_w_showroom" value="<?php echo get_option('pzf_max_w_showroom'); ?>" />px<br>
                    <i>Default: 600</i></td>
            </tr>   
            <tr valign="top">
                <th scope="row">Background popup</th>
                <td>
                    <label for="pzf_bg_contact_form">
                        <input id="pzf_bg_showroom" class="my-color-field" name="pzf_bg_showroom" type="text" value="<?php echo get_option('pzf_bg_showroom'); ?>" />
                    </label><br>
                    <i>Default: #ffffff</i>
                </td>
            </tr> 
        </tr> 
    </table>
    <?php submit_button(); ?>
</form>

<hr />

<h2><?php esc_html_e( 'Support', 'support_pzf' ); ?></h2>
<p>
<?php _e( 'For submitting any support queries, feedback, bug reports or feature requests, please visit <a href="https://wordpress.org/plugins/button-contact-vr/" target="_blank">this link</a>.', 'support_pzf' ); ?>
</p>
<h2><?php esc_html_e( 'Help', 'support_pzf' ); ?></h2>
<p>
<?php _e( 'Please visit <a href="https://webvocuc.com/blog/tag/button-contact-vr" target="_blank">this link</a>.', 'help_pzf' ); ?>
</p>

</div>

<!-- css admin All in one -->
<style type="text/css">
    ul#style-icon-vr {
        margin: 0;
    }
    ul#style-icon-vr li {display: inline-block;margin-bottom: 0;margin-right: 15px;text-align: center;}
    ul#style-icon-vr li label {
        display: block;
    }
    ul#style-icon-vr li span {
        display: block;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        line-height: 50px;
        padding: 10px;
        position: relative;
        margin: 0 auto;
    }
    td input[type="text"] {
        width: 100%;
        max-width: 330px;
    }
    select#pzf_loco_img_contact_form {
        width: 100%;
        max-width: 330px;
    }
    ul#style-icon-vr li span img {
        max-width: 27px;
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%,-50%);
    }
    td textarea{width:100%;max-width: 420px;min-height: 120px}
    .tox-notification.tox-notification--in.tox-notification--warning {
        display: none;
    }
</style>
<script src="https://cdn.tiny.cloud/1/v9sn59nuhjw008xh3qqfys27pzr0z8gi1lexbcxy6mqd6shq/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
<script src="https://cdn.tiny.cloud/1/v9sn59nuhjw008xh3qqfys27pzr0z8gi1lexbcxy6mqd6shq/tinymce/5/jquery.tinymce.min.js" referrerpolicy="origin"></script>
<script>
  jQuery('textarea.tiny').tinymce({
    menubar: false,
    height:300,
    plugins: [
      'advlist autolink lists link image charmap print preview anchor',
      'searchreplace visualblocks code fullscreen',
      'insertdatetime media table paste code help wordcount'
    ],
    toolbar: 'undo redo | formatselect | bold italic | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help'
  });
</script>