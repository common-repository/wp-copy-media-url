<?php
/**
 * Plugin Name: WP Copy Media URL
 * Plugin URI: http://wordpress.org/plugins/wp-copy-media-url/
 * Description: WordPress plugin for copy media url on a single click.
 * Author: Ashish Ajani
 * Version: 2.1
 * Author: Ashish Ajani
 * Author URI: http://freelancer-coder.com/
 * License: GPLv2 or later
 * 
 * 
 */
/*  FOR THE RESTRICTION OF DIRECTLY ACCESS OF THE CLASS */
if (!class_exists('WP')) {
    die();
}

/**
 * Plugin Activation
 */
register_activation_hook(__FILE__, 'wp_cmu_install');

function wp_cmu_install() {
    add_option("wp_cmu_button_text", "Copy Media URL");
    add_option("wp_cmu_button_text_copied", "COPIED");
    add_option("wp_cmu_button_color", "#0073AA");
    add_option("wp_cmu_button_text_color", "#FFFFFF");
    add_option("wp_cmu_link_text", "Copy Media URL");
    add_option("wp_cmu_link_text_copied", "COPIED");
    add_option("wp_cmu_link_text_color", "#0073AA");
}

/**
 * Plugin deactivation
 */
register_deactivation_hook(__FILE__, 'wp_cmu_uninstall');

function wp_cmu_uninstall() {
    delete_option("wp_cmu_button_text");
    delete_option("wp_cmu_button_text_copied");
    delete_option("wp_cmu_button_color");
    delete_option("wp_cmu_button_text_color");
    delete_option("wp_cmu_link_text");
    delete_option("wp_cmu_link_text_copied");
    delete_option("wp_cmu_link_text_color");
}

class WP_Copy_Media_URL {

    /**
     * Stores the class instance.
     *
     * @var WP_Copy_Media_URL
     */
    private static $instance = null;

    /**
     * Returns the instance of this class.
     *
     * @return WP_Copy_Media_URL The instance
     */
    public static function get_instance() {
        if (!self::$instance)
            self::$instance = new self;
        return self::$instance;
    }

    /**
     * Initialize the plugin.
     */
    public function init_plugin() {
        $this->init_hooks();
    }

    /**
     * Initialises the WP actions.
     */
    private function init_hooks() {
        $this->wp_cmu_enqueue_assets();
        add_action('admin_head', array($this, 'wp_cmu_load_css'));
        add_filter('plugin_row_meta', array($this, 'wp_cmu_add_meta_links'), 10, 2);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'wp_cmu_add_action_links'));
        add_filter('plugin_action_links', array($this, 'wp_cmu_details_link'), 10, 3);
        add_filter('wp_prepare_attachment_for_js', array($this, 'wp_cmu_filter_wp_prepare_attachment_for_js'), 10, 3);
        add_action('print_media_templates', array($this, 'wp_cmu_print_media_templates'));
        $this->wp_cmu_display_in_listing();
        add_filter('media_row_actions', array($this, 'wp_cmu_library_listing'), 10, 2);
        add_action('attachment_submitbox_misc_actions', array($this, 'wp_cmu_on_edit_media_screen'));
        add_action('print_media_templates', array($this, 'wp_cmu_print_media_templates_two_column'));
    }

    /**
     * Load required media files
     */
    public function wp_cmu_enqueue_assets() {
        wp_enqueue_script(
                'wp-cmu-settings', plugins_url('js/wp-copy-media-url.js', __FILE__), array('media-views')
        );
        wp_enqueue_style(
                'wp-cmu-settings', plugins_url('css/wp-copy-media-url.css', __FILE__), array('media-views')
        );
    }

    /**
     * Load css with setting
     */
    function wp_cmu_load_css() {
        echo '<style type="text/css">
                a.wp-cmu-copy-btn {color:' . get_option('wp_cmu_link_text_color') . ';}
                button.button-primary.wp-cmu-copy-btn,.button.button-primary.wp-cmu-copy-btn-list {background:' . get_option("wp_cmu_button_color") . ';color:' . get_option('wp_cmu_button_text_color') . ';box-shadow:none;text-shadow:none;border-color:' . get_option("wp_cmu_button_color") . ';margin:0 auto;margin-top:3px;}
                button.button-primary.wp-cmu-copy-btn:hover,.button.button-primary.wp-cmu-copy-btn-list:hover {background:' . get_option("wp_cmu_button_color") . ';color:' . get_option('wp_cmu_button_text_color') . ';box-shadow:none;text-shadow:none;border-color:' . get_option("wp_cmu_button_color") . ';}
                button.button-primary.wp-cmu-copy-btn:focus,.button.button-primary.wp-cmu-copy-btn-list:focus {background:' . get_option("wp_cmu_button_color") . ';color:' . get_option('wp_cmu_button_text_color') . ';box-shadow:none;text-shadow:none;border-color:' . get_option("wp_cmu_button_color") . ';}
                </style>';
    }

    /**
     * Add plugin links
     * 
     * @param type $links
     * @param type $file
     * @return string
     */
    function wp_cmu_add_meta_links($links, $file) {
        if ($file == "wp-copy-media-url/wp-copy-media-url.php") {
            $plugin_url = 'http://wordpress.org/plugins/wp-copy-media-url/';

            $links[] = '<a href="' . $plugin_url . '" target="_blank" title="' . __(
                            'Click here to visit the plugin on WordPress.org', 'wp-copy-media-url'
                    ) . '">' . __('Visit WordPress.org page', 'wp-copy-media-url') . '</a>';

            $rate_url = 'https://wordpress.org/support/plugin/wp-copy-media-url/reviews/?rate=5#new-post';
            $links[] = '<a href="' . $rate_url . '" target="_blank" title="' . __(
                            'Click here to rate and review this plugin on WordPress.org', 'wp-copy-media-url'
                    ) . '">' . __('Rate this plugin', 'wp-copy-media-url') . '</a>';
        }

        return $links;
    }

    /**
     * Add Settings link
     * @param type $links
     * @return type
     */
    function wp_cmu_add_action_links($links) {
        $mylinks = array(
            '<a href="' . admin_url('options-general.php?page=wp-cmu-options') . '">Settings</a>',
        );
        return array_merge($links, $mylinks);
    }

    /**
     * Add Plugin Details link
     * 
     * @param type $links
     * @param type $plugin_file
     * @param type $plugin_data
     * @return type
     */
    function wp_cmu_details_link($links, $plugin_file, $plugin_data) {
        if (isset($plugin_data['PluginURI']) && false !== strpos($plugin_data['PluginURI'], 'http://wordpress.org/extend/plugins/')) {
            $slug = basename($plugin_data['PluginURI']);
            $links[] = sprintf('<a href="%s" class="thickbox" title="%s">%s</a>', self_admin_url('plugin-install.php?tab=plugin-information&amp;plugin=' . $slug . '&amp;TB_iframe=true&amp;width=600&amp;height=550'), esc_attr(sprintf(__('More information about %s'), $plugin_data['Name'])), __('Details')
            );
        }
        return $links;
    }

    /**
     * This function is used to add custom class in the grid view
     * 
     * @param array $response
     * @param type $attachment
     * @param type $meta
     * @return string
     */
    public function wp_cmu_filter_wp_prepare_attachment_for_js($response, $attachment, $meta) {
        $response['customClass'] = "copy-media-url";
        return $response;
    }

    /**
     * This function is used to add Copy URL link in the Media grid view
     */
    public function wp_cmu_print_media_templates() {
        ?>
        <script type="text/html" id="tmpl-attachment-copy-media-url">
            <div class="attachment-preview js--select-attachment type-{{ data.type }} subtype-{{ data.subtype }} {{ data.orientation }}">
                <div class="thumbnail-wp-cmu-copy-btn">
                    <button type="button" class="button button-primary button-large wp-cmu-copy-btn" data-copied-text="<?php echo get_option("wp_cmu_button_text_copied"); ?>" url="{{ data.url }}"><?php echo get_option("wp_cmu_button_text"); ?></button>
                </div>
                
                <div class="thumbnail">
                    <# if ( data.uploading ) { #>
                    <div class="media-progress-bar"><div style="width: {{ data.percent }}%"></div></div>
                    <# } else if ( 'image' === data.type && data.sizes ) { #>
                    <div class="centered">
                        <img src="{{ data.size.url }}" draggable="false" alt="" />
                    </div>
                    <# } else { #>
                    <div class="centered">
                        <# if ( data.image && data.image.src && data.image.src !== data.icon ) { #>
                        <img src="{{ data.image.src }}" class="thumbnail" draggable="false" alt="" />
                        <# } else if ( data.sizes && data.sizes.medium ) { #>
                        <img src="{{ data.sizes.medium.url }}" class="thumbnail" draggable="false" alt="" />
                        <# } else { #>
                        <img src="{{ data.icon }}" class="icon" draggable="false" alt="" />
                        <# } #>
                    </div>
                    <div class="filename">
                        <div>{{ data.filename }}</div>
                    </div>
                    <# } #>
                </div>
                <# if ( data.buttons.close ) { #>
                <button type="button" class="button-link attachment-close media-modal-icon"><span class="screen-reader-text"><?php _e('Remove'); ?></span></button>
                <# } #>
            </div>       
            <# if ( data.buttons.check ) { #>
            <button type="button" class="check" tabindex="-1"><span class="media-modal-icon"></span><span class="screen-reader-text"><?php _e('Deselect'); ?></span></button>
            <# } #>
            <#
            var maybeReadOnly = data.can.save || data.allowLocalEdits ? '' : 'readonly';
            if ( data.describe ) {
            if ( 'image' === data.type ) { #>
            <input type="text" value="{{ data.caption }}" class="describe" data-setting="caption"
                   placeholder="<?php esc_attr_e('Caption this image&hellip;'); ?>" {{ maybeReadOnly }} />
                   <# } else { #>
                   <input type="text" value="{{ data.title }}" class="describe" data-setting="title"
                   <# if ( 'video' === data.type ) { #>
                   placeholder="<?php esc_attr_e('Describe this video&hellip;'); ?>"
                   <# } else if ( 'audio' === data.type ) { #>
                   placeholder="<?php esc_attr_e('Describe this audio file&hellip;'); ?>"
                   <# } else { #>
                   placeholder="<?php esc_attr_e('Describe this media file&hellip;'); ?>"
                   <# } #> {{ maybeReadOnly }} />
                   <# }
                   } #>
        </script>
        <?php
    }

    /**
     * This function is used to add Copy URL link in the Add Media listing view
     */
    public function wp_cmu_display_in_listing() {
        if (isset($_REQUEST['attachment_id']) && ($id = intval($_REQUEST['attachment_id'])) && $_REQUEST['fetch']) {
            $post = get_post($id);
            if ('attachment' != $post->post_type)
                wp_die(__('Invalid post type.'));
            if (!current_user_can('edit_post', $id))
                wp_die(__('Sorry, you are not allowed to edit this item.'));

            switch ($_REQUEST['fetch']) {
                case 3 :
                    $url = wp_get_attachment_url($id);
                    echo "<button type='button' class='button button-primary button-large wp-cmu-copy-btn-list' data-copied-text='" . get_option("wp_cmu_button_text_copied") . "' url='" . $url . "'>" . get_option("wp_cmu_button_text") . "</button>";
                    break;
            }
        }
    }

    /**
     * This function is used to add Copy URL link in the Media listing view
     * 
     * @param type $actions
     * @param type $post
     * @return type
     */
    public function wp_cmu_library_listing($actions, $post) {
        $link = '<a type="button" class="wp-cmu-copy-btn" data-copied-text="' . get_option("wp_cmu_link_text_copied") . '" url="' . $post->guid . '">' . get_option("wp_cmu_link_text") . '</a>';
        return array_merge($actions, array("wp-cmu-media" => $link));
    }

    /**
     * This function is use to add Copy Button when edit media
     * 
     * @global type $post
     */
    public function wp_cmu_on_edit_media_screen() {
        global $post;
        ?>
        <div class="misc-pub-section misc-pub-filename">
            <button type="button" class="button button-primary button-large wp-cmu-copy-btn" data-copied-text="<?php echo get_option("wp_cmu_button_text_copied"); ?>" url="<?php echo wp_get_attachment_url($post->ID); ?>"><?php echo get_option("wp_cmu_button_text"); ?></button>
        </div>
        <?php
    }

    /**
     * This function is use in the media details popup
     */
    public function wp_cmu_print_media_templates_two_column() {
        ?> 
        <script type="text/html" id="tmpl-attachment-details-two-column-copy-media-url">
            <div class="attachment-media-view  {{ data.orientation }}">
                <div class="thumbnail  thumbnail-{{ data.type }}">
                    <# if ( data.uploading ) { #>
                    <div class="media-progress-bar"><div></div></div>
                    <# } else if ( data.sizes && data.sizes.large ) { #>
                    <img class="details-image" src="{{ data.sizes.large.url }}" draggable="false" alt="" />
                    <# } else if ( data.sizes && data.sizes.full ) { #>
                    <img class="details-image" src="{{ data.sizes.full.url }}" draggable="false" alt="" />
                    <# } else if ( -1 === jQuery.inArray( data.type, [ 'audio', 'video' ] ) ) { #>
                    <img class="details-image icon" src="{{ data.icon }}" draggable="false" alt="" />
                    <# } #>

                    <# if ( 'audio' === data.type ) { #>
                    <div class="wp-media-wrapper">
                        <audio style="visibility: hidden" controls class="wp-audio-shortcode" width="100%" preload="none">
                            <source type="{{ data.mime }}" src="{{ data.url }}"/>
                        </audio>
                    </div>
                    <# } else if ( 'video' === data.type ) {
                    var w_rule = '';
                    if ( data.width ) {
                    w_rule = 'width: ' + data.width + 'px;';
                    } else if ( wp.media.view.settings.contentWidth ) {
                    w_rule = 'width: ' + wp.media.view.settings.contentWidth + 'px;';
                    }
                    #>
                    <div style="{{ w_rule }}" class="wp-media-wrapper wp-video">
                        <video controls="controls" class="wp-video-shortcode" preload="metadata"
                               <# if ( data.width ) { #>width="{{ data.width }}"<# } #>
                               <# if ( data.height ) { #>height="{{ data.height }}"<# } #>
                               <# if ( data.image && data.image.src !== data.icon ) { #>poster="{{ data.image.src }}"<# } #>>
                               <source type="{{ data.mime }}" src="{{ data.url }}"/>
                        </video>
                    </div>
                    <# } #>

                    <div class="attachment-actions">
                        <# if ( 'image' === data.type && ! data.uploading && data.sizes && data.can.save ) { #>
                        <button type="button" class="button edit-attachment"><?php _e('Edit Image'); ?></button>
                        <# } else if ( 'pdf' === data.subtype && data.sizes ) { #>
                        <?php _e('Document Preview'); ?>
                        <# } #>
                    </div>
                </div>
            </div>
            <div class="attachment-info">
                <span class="settings-save-status">
                    <span class="spinner"></span>
                    <span class="saved"><?php esc_html_e('Saved.'); ?></span>
                </span>
                <div class="details">
                    <div class="filename"><strong><?php _e('File name:'); ?></strong> {{ data.filename }}</div>
                    <div class="filename"><strong><?php _e('File type:'); ?></strong> {{ data.mime }}</div>
                    <div class="uploaded"><strong><?php _e('Uploaded on:'); ?></strong> {{ data.dateFormatted }}</div>

                    <div class="file-size"><strong><?php _e('File size:'); ?></strong> {{ data.filesizeHumanReadable }}</div>
                    <# if ( 'image' === data.type && ! data.uploading ) { #>
                    <# if ( data.width && data.height ) { #>
                    <div class="dimensions"><strong><?php _e('Dimensions:'); ?></strong> {{ data.width }} &times; {{ data.height }}</div>
                    <# } #>
                    <# } #>

                    <# if ( data.fileLength ) { #>
                    <div class="file-length"><strong><?php _e('Length:'); ?></strong> {{ data.fileLength }}</div>
                    <# } #>

                    <# if ( 'audio' === data.type && data.meta.bitrate ) { #>
                    <div class="bitrate">
                        <strong><?php _e('Bitrate:'); ?></strong> {{ Math.round( data.meta.bitrate / 1000 ) }}kb/s
                        <# if ( data.meta.bitrate_mode ) { #>
                        {{ ' ' + data.meta.bitrate_mode.toUpperCase() }}
                        <# } #>
                    </div>
                    <# } #>

                    <div class="compat-meta">
                        <# if ( data.compat && data.compat.meta ) { #>
                        {{{ data.compat.meta }}}
                        <# } #>
                    </div>
                    <div class="uploaded "><button type="button" class="button button-primary button-large wp-cmu-copy-btn wp-cmu-copy-btn-right" data-copied-text="<?php echo get_option("wp_cmu_button_text_copied"); ?>" url="{{ data.url }}"><?php echo get_option("wp_cmu_button_text"); ?></button></div>
                </div>

                <div class="settings">
                    <label class="setting" data-setting="url">
                        <span class="name"><?php _e('URL'); ?></span>
                        <input type="text" value="{{ data.url }}" readonly />
                    </label>
                    <# var maybeReadOnly = data.can.save || data.allowLocalEdits ? '' : 'readonly'; #>
                    <?php if (post_type_supports('attachment', 'title')) : ?>
                        <label class="setting" data-setting="title">
                            <span class="name"><?php _e('Title'); ?></span>
                            <input type="text" value="{{ data.title }}" {{ maybeReadOnly }} />
                        </label>
                    <?php endif; ?>
                    <# if ( 'audio' === data.type ) { #>
                    <?php
                    foreach (array(
                'artist' => __('Artist'),
                'album' => __('Album'),
                    ) as $key => $label) :
                        ?>
                        <label class="setting" data-setting="<?php echo esc_attr($key) ?>">
                            <span class="name"><?php echo $label ?></span>
                            <input type="text" value="{{ data.<?php echo $key ?> || data.meta.<?php echo $key ?> || '' }}" />
                        </label>
                    <?php endforeach; ?>
                    <# } #>
                    <label class="setting" data-setting="caption">
                        <span class="name"><?php _e('Caption'); ?></span>
                        <textarea {{ maybeReadOnly }}>{{ data.caption }}</textarea>
                    </label>
                    <# if ( 'image' === data.type ) { #>
                    <label class="setting" data-setting="alt">
                        <span class="name"><?php _e('Alt Text'); ?></span>
                        <input type="text" value="{{ data.alt }}" {{ maybeReadOnly }} />
                    </label>
                    <# } #>
                    <label class="setting" data-setting="description">
                        <span class="name"><?php _e('Description'); ?></span>
                        <textarea {{ maybeReadOnly }}>{{ data.description }}</textarea>
                    </label>
                    <label class="setting">
                        <span class="name"><?php _e('Uploaded By'); ?></span>
                        <span class="value">{{ data.authorName }}</span>
                    </label>
                    <# if ( data.uploadedToTitle ) { #>
                    <label class="setting">
                        <span class="name"><?php _e('Uploaded To'); ?></span>
                        <# if ( data.uploadedToLink ) { #>
                        <span class="value"><a href="{{ data.uploadedToLink }}">{{ data.uploadedToTitle }}</a></span>
                        <# } else { #>
                        <span class="value">{{ data.uploadedToTitle }}</span>
                        <# } #>
                    </label>
                    <# } #>
                    <div class="attachment-compat"></div>
                </div>

                <div class="actions">
                    <a class="view-attachment" href="{{ data.link }}"><?php _e('View attachment page'); ?></a>
                    <# if ( data.can.save ) { #> |
                    <a href="post.php?post={{ data.id }}&action=edit"><?php _e('Edit more details'); ?></a>
                    <# } #>
                    <# if ( ! data.uploading && data.can.remove ) { #> |
                    <?php if (MEDIA_TRASH): ?>
                        <# if ( 'trash' === data.status ) { #>
                        <button type="button" class="button-link untrash-attachment"><?php _e('Untrash'); ?></button>
                        <# } else { #>
                        <button type="button" class="button-link trash-attachment"><?php _ex('Trash', 'verb'); ?></button>
                        <# } #>
                    <?php else: ?>
                        <button type="button" class="button-link delete-attachment"><?php _e('Delete Permanently'); ?></button>
                    <?php endif; ?>
                    <# } #>
                </div>
            </div>
        </script>
        <?php
    }

}

/* INITIALIZE MAIN CLASS */
add_action('admin_init', array(WP_Copy_Media_URL::get_instance(), 'init_plugin'), 20);


add_action('admin_menu', 'wp_cmu_option_menu');

/*
 * Add option in the settings menu
 */

function wp_cmu_option_menu() {
    add_options_page('WP Copy Media URL', 'WordPress Copy Media URL', 'manage_options', 'wp-cmu-options', 'wp_cmu_options');
}

/**
 * Settings Page
 */
function wp_cmu_options() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    if (isset($_POST['submit']) && isset($_POST['wp_cmu_button_text'])) {
        $required_fields = array(
            "wp_cmu_button_text" => "Please enter button text",
            "wp_cmu_button_text_copied" => "Please enter copied text",
            "wp_cmu_button_color" => "Please select button color",
            "wp_cmu_button_text_color" => "Please select text color",
            "wp_cmu_link_text" => "Please enter text",
            "wp_cmu_link_text_copied" => "Please enter text copied",
            "wp_cmu_link_text_color" => "Please select text coor",
        );
        $error_msg = array();
        foreach ($required_fields as $key => $value) {
            if (isset($_POST[$key]) && strlen(trim($_POST[$key])) <= 0) {
                $error_msg[$key] = $value;
            }
        }
        if (empty($error_msg)) {
            foreach ($required_fields as $key => $value) {
                update_option($key, $_POST[$key]);
            }
            ?>
            <div class="updated notice">
                <p><?php _e('Changes applied successfully'); ?></p>
            </div> 
            <?php
        } else {
            ?>
            <div class="error notice">
                <p><?php _e('Please enter all the fields'); ?></p>
            </div> 
            <?php
        }
    }
    ?>
    <div class="wrap">
        <h1>WordPress Copy Media URL</h1>
        <div class="wp-cmu-sec">
            <h2 class="nav-tab-wrapper">
                <a href="?page=wp-cmu-options" class="nav-tab nav-tab-active">Settings</a>
            </h2>
            <div class="wp-cmu-row">
                <div class="wp-cmu-setting">
                    <form action="" name="wp-cmu-settings" method="POST">
                        <table>
                            <tbody>
                                <tr>
                                    <th colspan="2" scope="row" align="left"><label><h2>Button settings:</h2></label></th>
                                </tr>
                                <tr>
                                    <th scope="row" align="left"><label>Button text:</label></th>
                                    <td><input name="wp_cmu_button_text" id="wp_cmu_button_text" value="<?php echo get_option("wp_cmu_button_text"); ?>" class="regular-text" type="text"></td>
                                </tr>
                                <tr>
                                    <th scope="row" align="left"><label>Button text after copy:</label></th>
                                    <td><input name="wp_cmu_button_text_copied" id="wp_cmu_button_text_copied" value="<?php echo get_option("wp_cmu_button_text_copied"); ?>" class="regular-text" type="text"></td>
                                </tr>
                                <tr>
                                    <th scope="row" align="left"><label>Button color:</label></th>
                                    <td><input type="color" name="wp_cmu_button_color" value="<?php echo get_option("wp_cmu_button_color"); ?>"></td>
                                </tr>
                                <tr>
                                    <th scope="row" align="left"><label>Button text color:</label></th>
                                    <td><input type="color" name="wp_cmu_button_text_color" value="<?php echo get_option("wp_cmu_button_text_color"); ?>"></td>
                                </tr>
                                <tr>
                                    <th colspan="2" scope="row" align="left"><label><h2>Link settings:</h2></label></th>
                                </tr>
                                <tr>
                                    <th scope="row" align="left"><label>Link text:</label></th>
                                    <td><input name="wp_cmu_link_text" id="wp_cmu_link_text" value="<?php echo get_option("wp_cmu_link_text"); ?>" class="regular-text" type="text"></td>
                                </tr>
                                <tr>
                                    <th scope="row" align="left"><label>Link text after copy:</label></th>
                                    <td><input name="wp_cmu_link_text_copied" id="wp_cmu_link_text_copied" value="<?php echo get_option("wp_cmu_link_text_copied"); ?>" class="regular-text" type="text"></td>
                                </tr>
                                <tr>
                                    <th scope="row" align="left"><label>Link text color:</label></th>
                                    <td><input name="wp_cmu_link_text_color" id="wp_cmu_link_text_color" value="<?php echo get_option("wp_cmu_link_text_color"); ?>" type="color"></td>
                                </tr>
                                <tr>
                                    <td colspan="2"><?php submit_button(); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                </div>
                <div class="wp-cmu-sidebar-boxes-main">
                    <div class="wp-cmu-sidebar-box">
                        <h3 class="hndle">Need Help?</h3>
                        <div class="inside">
                            <ul>
                                <li><a href="https://wordpress.org/plugins/wp-copy-media-url/installation/" target="_blank">Installation Help</a></li>
                                <li><a href="https://wordpress.org/plugins/wp-copy-media-url/faq/" target="_blank">Frequently Asked Questions</a></li>
                                <li><a href="https://wordpress.org/support/plugin/wp-copy-media-url" target="_blank">Support Forum</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="wp-cmu-sidebar-box">
                        <h3 class="hndle">Support WordPress Copy Media URL</h3>
                        <p>Please rate this plugin if it worked for you. You can also share your feedback or suggestions with me if you find anything which did not worked for you or it may improve the plugin features.</p>
                        <div class="wp-cmu-rate-sec">
                            <a href="https://wordpress.org/support/plugin/wp-copy-media-url/reviews/#new-post" target="_blank" class="rate-link">Rate this plugin</a>
                            <a href="https://wordpress.org/support/plugin/wp-copy-media-url/reviews/#new-post" target="_blank" class="rate-star"><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span></a>
                        </div>
                    </div>

                    <div class="wp-cmu-sidebar-box">
                        <h3 class="hndle">Any Suggestions?</h3>
                        <p>Please feel free to share your suggestions with me, it will be really helpful.</p>
                        <div class="inside">
                            <ul>
                                <li>Email: <a href="mailto:info@freelancer-coder.com">info@freelancer-coder.com</a></li>
                                <li>Web: <a href="https://freelancer-coder.com/" target="_blank">https://freelancer-coder.com</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}