<!--  * Version: 3.0 -->
<div class="wrap">
<h2>Contact Form</h2>
<h2 class="nav-tab-wrapper">
    <a href="admin.php?page=contact_vr" class="nav-tab">Button contact</a>
    <a href="admin.php?page=contact_vr_showroom" class="nav-tab">Showroom</a>
    <a href="admin.php?page=contact_vr_contact_form" class="nav-tab  nav-tab-active">Contact form (beta)</a>
    <a href="admin.php?page=contact_vr_all_in_one" class="nav-tab">All in one </a>
    <a href="admin.php?page=contact_vr_setting" class="nav-tab">Setting</a>
</h2>
<?php if( isset($_GET['settings-updated']) ) { ?>
    <div id="message" class="updated">
        <p><strong><?php _e('Settings saved.') ?></strong></p>
    </div>
<?php } ?>

<form method="post" action="options.php">
    <?php settings_fields( 'pzf_settings_contact_form' ); ?>
    <table class="form-table">     
        <tr valign="top">
            <th scope="row">Enable:</th>
            <td><input id="pzf_enable_contact_form" name="pzf_enable_contact_form" type="checkbox" value="1" <?php echo get_option('pzf_enable_contact_form') == '1' ? 'checked="checked"' : '' ?> />
            </td>
        </tr>   
        <tr valign="top">
            <th scope="row">Color icon contact</th>
            <td>
                <label for="pzf_color_contact_form">
                    <input id="pzf_color_acontact_form" class="my-color-field" name="pzf_color_contact_form" type="text" value="<?php echo get_option('pzf_color_contact_form'); ?>" />
                </label>
            </td>
        </tr> 
        <tr valign="top" style=" border-bottom: 1px dashed #bfbfbf; ">
            <th scope="row">Style icon </th>
            <td>
                <ul id="style-icon-vr">
                    <li>
                        <label for="pzf_icon_contact_form1">
                            <span style="background: <?php echo get_option('pzf_color_contact_form') ? get_option('pzf_color_contact_form') : '#2196f3'; ?>"><img src="/wp-content/plugins/button-contact-vr/img/icon1.png"></span>
                            <input id="pzf_icon_contact_form1" name="pzf_icon_contact_form" type="radio" value="1" <?php echo get_option('pzf_icon_contact_form') == '1' ? 'checked="checked"' : '' ?> />icon 1
                        </label>
                    </li>
                    <li>
                        <label for="pzf_icon_contact_form2">
                            <span style="background: <?php echo get_option('pzf_color_contact_form') ? get_option('pzf_color_contact_form') : '#2196f3'; ?>"><img src="/wp-content/plugins/button-contact-vr/img/icon2.png"></span>
                            <input id="pzf_icon_contact_form2" name="pzf_icon_contact_form" type="radio" value="2" <?php echo get_option('pzf_icon_contact_form') == '2' ? 'checked="checked"' : '' ?> />icon 2
                        </label>
                    </li>
                    <li>
                        <label for="pzf_icon_contact_form3">
                            <span style="background: <?php echo get_option('pzf_color_contact_form') ? get_option('pzf_color_contact_form') : '#2196f3'; ?>"><img src="/wp-content/plugins/button-contact-vr/img/icon3.png"></span>
                            <input id="pzf_icon_contact_form3" name="pzf_icon_contact_form" type="radio" value="3" <?php echo get_option('pzf_icon_contact_form') == '3' ? 'checked="checked"' : '' ?> />icon 3
                        </label>
                    </li>
                    <li>
                        <label for="pzf_icon_contact_form4">
                            <span style="background: <?php echo get_option('pzf_color_contact_form') ? get_option('pzf_color_contact_form') : '#2196f3'; ?>"><img src="/wp-content/plugins/button-contact-vr/img/icon4.png"></span>
                            <input id="pzf_icon_contact_form4" name="pzf_icon_contact_form" type="radio" value="4" <?php echo get_option('pzf_icon_contact_form') == '4' ? 'checked="checked"' : '' ?> />icon 4
                        </label>
                    </li>
                    <li>
                        <label for="pzf_icon_contact_form5">
                            <span style="background: <?php echo get_option('pzf_color_contact_form') ? get_option('pzf_color_contact_form') : '#2196f3'; ?>"><img src="/wp-content/plugins/button-contact-vr/img/icon5.png"></span>
                            <input id="pzf_icon_contact_form5" name="pzf_icon_contact_form" type="radio" value="5" <?php echo get_option('pzf_icon_contact_form') == '5' ? 'checked="checked"' : '' ?> />icon 5
                        </label>
                    </li>
                </ul>                
            </td>
		<tr><th>Form content:</th></tr>
            <tr valign="top">
                <th scope="row">Title</th>
                <td><textarea class="tiny" placeholder="" name="pzf_title_contact_form" value="" /><?php echo get_option('pzf_title_contact_form'); ?></textarea></td>
            </tr> 
            <tr valign="top" style=" border-bottom: 1px dashed #bfbfbf; ">
                <th scope="row">Form</th>
                <td><textarea class="tiny" placeholder="" name="pzf_content_contact_form"/><?php echo get_option('pzf_content_contact_form'); ?></textarea><br>
                    <i>Shortcode contact form 7, Ninja Forms...</i></td>
            </tr> 
            <tr valign="top">
                <th scope="row">Image</th>
                <td><input type="text" placeholder="Link image" name="pzf_img_contact_form" value="<?php echo get_option('pzf_img_contact_form'); ?>" /></td>
            </tr> 
            <tr valign="top">
                <th scope="row">Image location </th>
                <td style=" padding-bottom: 0; ">
                    <select id="pzf_loco_img_contact_form" name="pzf_loco_img_contact_form">
                        <option value="left" <?php selected( get_option('pzf_loco_img_contact_form'), 'left' ); ?>> Left</option>
                        <option value="right" <?php selected( get_option('pzf_loco_img_contact_form'), 'right' ); ?>> Right</option>
                        <option value="top" <?php selected( get_option('pzf_loco_img_contact_form'), 'top' ); ?>> Top</option>
                        <option value="bottom" <?php selected( get_option('pzf_loco_img_contact_form'), 'bottom' ); ?>> Bottom</option>
                    </select><br>
                    <i>Default: left</i>       
                </td>
            </tr> 
            <tr valign="top">
                <th scope="row">Max width popup</th>
                <td><input type="text" placeholder="" name="pzf_max_w_contact_form" value="<?php echo get_option('pzf_max_w_contact_form'); ?>" />px<br>
                    <i>Default: 600</i></td>
            </tr>   
            <tr valign="top">
                <th scope="row">Background popup</th>
                <td>
                    <label for="pzf_bg_contact_form">
                        <input id="pzf_color_acontact_form" class="my-color-field" name="pzf_bg_contact_form" type="text" value="<?php echo get_option('pzf_bg_contact_form'); ?>" />
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