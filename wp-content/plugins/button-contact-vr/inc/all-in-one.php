<!--  * Version: 3.0 -->
<div class="wrap">
<h2>All in one</h2>
<h2 class="nav-tab-wrapper">
    <a href="admin.php?page=contact_vr" class="nav-tab">Button contact</a>
    <a href="admin.php?page=contact_vr_showroom" class="nav-tab">Showroom</a>
    <a href="admin.php?page=contact_vr_contact_form" class="nav-tab">Contact form (beta)</a>
    <a href="admin.php?page=contact_vr_all_in_one" class="nav-tab nav-tab-active">All in one</a>
    <a href="admin.php?page=contact_vr_setting" class="nav-tab">Setting</a>
</h2>
<?php if( isset($_GET['settings-updated']) ) { ?>
    <div id="message" class="updated">
        <p><strong><?php _e('Settings saved.') ?></strong></p>
    </div>
<?php } ?>

<form method="post" action="options.php">
    <?php settings_fields( 'pzf_settings_all_in_one' ); ?>
    <table class="form-table">     
        <tr valign="top">
            <th scope="row">Enable:</th>
            <td><input id="pzf_enable_all_in_one" name="pzf_enable_all_in_one" type="checkbox" value="1" <?php echo get_option('pzf_enable_all_in_one') == '1' ? 'checked="checked"' : '' ?> />
            </td>
        </tr>   
        <tr valign="top">
            <th scope="row">Note:</th>
            <td><input placeholder="contact" type="text" name="pzf_note_all_in_one" value="<?php echo get_option('pzf_note_all_in_one'); ?>" /><br>
            <i>Show on hover</i></td>
        </tr>        
        <tr valign="top">
            <th scope="row" valign="top">Note (show/hide)</th>
            <td>
                <label for="pzf_note_bar_all_in_one">
                    <input id="pzf_note_bar_all_in_one" name="pzf_note_bar_all_in_one" type="checkbox" value="1" <?php echo get_option('pzf_note_bar_all_in_one') == '1' ? 'checked="checked"' : '' ?> />
                </label>
            </td>
        </tr> 
        <tr valign="top">
            <th scope="row">Color all in one</th>
            <td>
                <label for="pzf_color_all_in_one">
                    <input id="pzf_color_all_in_one" class="my-color-field" name="pzf_color_all_in_one" type="text" value="<?php echo get_option('pzf_color_all_in_one'); ?>" />
                </label>
            </td>
        </tr> 
        <tr valign="top">
            <th scope="row">Style icon </th>
            <td>
                <ul id="style-icon-vr">
                    <li>
                        <label for="pzf_icon_all_in_one1">
                            <span style="background: <?php echo get_option('pzf_color_all_in_one') ? get_option('pzf_color_all_in_one') : '#2196f3'; ?>"><img src="/wp-content/plugins/button-contact-vr/img/icon1.png"></span>
                            <input id="pzf_icon_all_in_one1" name="pzf_icon_all_in_one" type="radio" value="1" <?php echo get_option('pzf_icon_all_in_one') == '1' ? 'checked="checked"' : '' ?> />icon 1
                        </label>
                    </li>
                    <li>
                        <label for="pzf_icon_all_in_one2">
                            <span style="background: <?php echo get_option('pzf_color_all_in_one') ? get_option('pzf_color_all_in_one') : '#2196f3'; ?>"><img src="/wp-content/plugins/button-contact-vr/img/icon2.png"></span>
                            <input id="pzf_icon_all_in_one2" name="pzf_icon_all_in_one" type="radio" value="2" <?php echo get_option('pzf_icon_all_in_one') == '2' ? 'checked="checked"' : '' ?> />icon 2
                        </label>
                    </li>
                    <li>
                        <label for="pzf_icon_all_in_one3">
                            <span style="background: <?php echo get_option('pzf_color_all_in_one') ? get_option('pzf_color_all_in_one') : '#2196f3'; ?>"><img src="/wp-content/plugins/button-contact-vr/img/icon3.png"></span>
                            <input id="pzf_icon_all_in_one3" name="pzf_icon_all_in_one" type="radio" value="3" <?php echo get_option('pzf_icon_all_in_one') == '3' ? 'checked="checked"' : '' ?> />icon 3
                        </label>
                    </li>
                    <li>
                        <label for="pzf_icon_all_in_one4">
                            <span style="background: <?php echo get_option('pzf_color_all_in_one') ? get_option('pzf_color_all_in_one') : '#2196f3'; ?>"><img src="/wp-content/plugins/button-contact-vr/img/icon4.png"></span>
                            <input id="pzf_icon_all_in_one4" name="pzf_icon_all_in_one" type="radio" value="4" <?php echo get_option('pzf_icon_all_in_one') == '4' ? 'checked="checked"' : '' ?> />icon 4
                        </label>
                    </li>
                    <li>
                        <label for="pzf_icon_all_in_one5">
                            <span style="background: <?php echo get_option('pzf_color_all_in_one') ? get_option('pzf_color_all_in_one') : '#2196f3'; ?>"><img src="/wp-content/plugins/button-contact-vr/img/icon5.png"></span>
                            <input id="pzf_icon_all_in_one5" name="pzf_icon_all_in_one" type="radio" value="5" <?php echo get_option('pzf_icon_all_in_one') == '5' ? 'checked="checked"' : '' ?> />icon 5
                        </label>
                    </li>
                </ul>                
            </td>
        </tr>  
        <tr valign="top">
            <th scope="row">Item show / hidden:</th>
            <td><input id="pzf_hide_default_all_in_one" name="pzf_hide_default_all_in_one" type="checkbox" value="1" <?php echo get_option('pzf_hide_default_all_in_one') == '1' ? 'checked="checked"' : '' ?> /><i> Show</i></br>
                <i>Default is hidden </i>
            </td>
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
    ul#style-icon-vr li span img {
        max-width: 27px;
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%,-50%);
    }
</style>