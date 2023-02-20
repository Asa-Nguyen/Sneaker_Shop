<!--  * Version: 2.0 -->
<div class="wrap">
<h2>Advanced settings</h2>
<h2 class="nav-tab-wrapper">
    <a href="admin.php?page=contact_vr" class="nav-tab">Button contact</a>
    <a href="admin.php?page=contact_vr_showroom" class="nav-tab">Showroom</a>
    <a href="admin.php?page=contact_vr_contact_form" class="nav-tab">Contact form (beta)</a>
    <a href="admin.php?page=contact_vr_all_in_one" class="nav-tab">All in one)</a>
    <a href="admin.php?page=contact_vr_setting" class="nav-tab nav-tab-active">Setting</a>
</h2>
<?php if( isset($_GET['settings-updated']) ) { ?>
    <div id="message" class="updated">
        <p><strong><?php _e('Settings saved.') ?></strong></p>
    </div>
<?php } ?>

<form method="post" action="options.php">
    <?php settings_fields( 'pzf-settings-group-setting' ); ?>
    <table class="form-table">     
        <tr valign="top">
            <th scope="row">Size scale</th>
            <td><input placeholder="1" type="text" name="setting_size" value="<?php echo get_option('setting_size'); ?>" /> <i>Default 1</i><br>
            <i>E.g: 0.8 - 0.9 - 1.1 - 1.2</i></td>
        </tr>       
        <tr valign="top">
            <th rowspan="2" scope="row">Location</th>
            <td style=" padding-bottom: 0; ">
            	<select id="pzf_location" name="pzf_location">
					<option value="left" <?php selected( get_option('pzf_location'), 'left' ); ?>> Left</option>
					<option value="right" <?php selected( get_option('pzf_location'), 'right' ); ?>> Right</option>
				</select>   	
            </td>
        </tr>  
        <tr>        	
            <td>
            	Bottom <input placeholder="0%" type="number" min="0" max="100" name="pzf_location_bottom" value="<?php echo get_option('pzf_location_bottom'); ?>" /> <i>unit %</i>
            </td>
        </tr> 

        <tr valign="top">
            <th rowspan="2" scope="row">Hide on</th>
            <td style=" padding-bottom: 0; ">
            	<input id="pzf_hide_desktop" name="pzf_hide_desktop" type="checkbox" value="1" <?php echo get_option('pzf_hide_desktop') == '1' ? 'checked="checked"' : '' ?> /> Desktop           	
            </td>
        </tr>
        <tr>        	
            <td><input id="pzf_hide_mobile" name="pzf_hide_mobile" type="checkbox" value="1" <?php echo get_option('pzf_hide_mobile') == '1' ? 'checked="checked"' : '' ?> /> Mobile
            </td>
        </tr>    
        <tr valign="top">
            <th scope="row">Custom CSS</th>
            <td><textarea placeholder="Add custom CSS here"  name="pzf_add_css" style="height: 150px;width: 100%;max-width: 700px;" /><?php echo get_option('pzf_add_css'); ?></textarea><br>
            <i>Add custom CSS here</i></td>
        </tr> 
        <tr valign="top">
            <th scope="row">Custom JS</th>
            <td><textarea placeholder="Add custom JS here! You need to have a SCRIPT tag around scripts." name="pzf_add_js" style="height: 150px;width: 100%;max-width: 700px;" /><?php echo get_option('pzf_add_js'); ?></textarea><br>
            <i>You need to have a SCRIPT tag around scripts.</i></td>
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