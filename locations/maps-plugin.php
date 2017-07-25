<?php
/**
 * Plugin Name: LocationsPlugin
 * Version: 1.1
 * Author: Halyna Kondratiuk
 * Description: A simple plugin
 */
/** Step 2 (from text above). */
//include 'function.php';

add_action('init', 'register_custom_post_type');
add_action('add_meta_boxes', 'add_location_meta_boxes');
add_action("save_post", "save_location", 10, 1);
add_action('admin_menu', 'wpdocs_register_my_custom_submenu_page');
add_action('admin_init', 'wporg_settings_init');
add_action('admin_footer', 'media_selector_print_scripts');
add_shortcode('locations', 'locations_function');


function locations_function($atts)
{

//    return [ 'coordinates' => $var, 'center_lat' => $center_lat, 'center_lng' => $center_lng ];
//
//
//    $result = locations_fu( $id );
//
//    $result['center_lng'];

    //var_dump($atts['id']);


    if (isset($atts['id'])) {
        $post_ids = explode(",", $atts['id']);
    }

    $center_lat = $center_lng = 0;


    if (is_array($post_ids)) {
        $var = [];
        foreach ($post_ids as $id) {
            $coord = get_post_meta($id, 'location_coordinates', true);

            if (is_array($coord)) {

                $coord['lat'] = (float)$coord['lat'];
                $coord['lng'] = (float)$coord['lng'];

                $var[] = $coord;
                $center_lat += $coord['lat'];
                $center_lng += $coord['lng'];
            }

        }
    } else if ( isset($atts['id']) ){
        $var = [];
        $coord = get_post_meta($atts['id'], 'location_coordinates', true);

        if (is_array($coord)) {

            $coord['lat'] = (float)$coord['lat'];
            $coord['lng'] = (float)$coord['lng'];

            $var[] = $coord;
            $center_lat = $coord['lat'];
            $center_lng = $coord['lng'];
        }
    } else {
        $var = [];
        $coord = get_post_meta(get_the_ID(), 'location_coordinates', true);

        if (is_array($coord)) {

            $coord['lat'] = (float)$coord['lat'];
            $coord['lng'] = (float)$coord['lng'];

            $var[] = $coord;
            $center_lat = $coord['lat'];
            $center_lng = $coord['lng'];
        }
    }


    $center_lat = $center_lat / count($var);
    $center_lng = $center_lng / count($var);

    //var_dump($atts);
    //exit();
    $js_array = json_encode($var);
    // var_dump($var);
    //exit();


    global $post;
    $location_coordinates = get_post_meta($post->ID, 'location_coordinates', true);
    $image_marker = wp_get_attachment_url(get_option('media_selector_attachment_id'));


    //$my_saved_attachment_post_id = get_option( 'media_selector_attachment_id', 0 );
    // print_r($post);
    //exit();

    $API_KEY = get_option('plugin_options');
    //$API_KEY = "AIzaSyAzXoaC9OV09c-sTdIWWR1hWzUcJppx_g8";
    //print_r($API_KEY['text_string']);
    //exit();
    //print_r($post);
    //exit();

    ?>

    <div class='map' style='height:300px; margin-bottom: 1.6842em' id='map'></div>

    <script type='text/javascript'>
        var lat = "<?php echo $center_lat ?>";
        var lng = "<?php echo $center_lng ?>";
        var image_marker = "<?php echo $image_marker ?>";
        var beaches = <?php echo $js_array ?>;
        //console.log(beaches);

        var map;
        function initMap() {
            var latlng = new google.maps.LatLng(lat, lng);
            map = new google.maps.Map(document.getElementById('map'), {
                center: latlng,
                zoom: 5
            });
            //var image = image_marker;
            var image = {
                url: image_marker,
                scaledSize: new google.maps.Size(20, 32),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(0, 32)
            };

            jQuery.each(beaches, function (key, item) {

                var marker = new google.maps.Marker({
                    position: {lat: item.lat, lng: item.lng},
                    map: map,
                    icon: image
                });
            });
//       var beachMarker = new google.maps.Marker({
//          position: latlng,
//          map: map,
//          icon: image
//       });
        }
        var key = "<?php echo $API_KEY['text_string'] ?>";
        var script = document.createElement('script');
        script.src = "https://maps.googleapis.com/maps/api/js?key=" + key + "&callback=initMap";
        document.body.appendChild(script);
    </script>
    <!--<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAzXoaC9OV09c-sTdIWWR1hWzUcJppx_g8&ibraries=places&callback=initMap&key=" async defer></script>-->

    <?php
    $output = ob_get_clean();
    return $output;
}


function media_selector_print_scripts()
{

    $my_saved_attachment_post_id = get_option('media_selector_attachment_id', 0);
    //print_r($my_saved_attachment_post_id);
    //exit();

    ?>
    <script type='text/javascript'>
        jQuery(document).ready(function ($) {
            // Uploading files
            var file_frame;
            var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id
            var set_to_post_id = <?php echo $my_saved_attachment_post_id; ?>; // Set this
            jQuery('#upload_image_button').on('click', function (event) {
                event.preventDefault();
                // If the media frame already exists, reopen it.
                if (file_frame) {
                    // Set the post ID to what we want
                    file_frame.uploader.uploader.param('post_id', set_to_post_id);
                    // Open frame
                    file_frame.open();
                    return;
                } else {
                    // Set the wp.media post id so the uploader grabs the ID we want when initialised
                    wp.media.model.settings.post.id = set_to_post_id;
                }
                // Create the media frame.
                file_frame = wp.media.frames.file_frame = wp.media({
                    title: 'Select a image to upload',
                    button: {
                        text: 'Use this image',
                    },
                    multiple: false	// Set to true to allow multiple files to be selected
                });
                // When an image is selected, run a callback.
                file_frame.on('select', function () {
                    // We set multiple to false so only get one image from the uploader
                    attachment = file_frame.state().get('selection').first().toJSON();
                    // Do something with attachment.id and/or attachment.url here
                    $('#image-preview').attr('src', attachment.url).css('width', 'auto');
                    $('#image_attachment_id').val(attachment.id);
                    // Restore the main post ID
                    wp.media.model.settings.post.id = wp_media_post_id;
                });
                // Finally, open the modal
                file_frame.open();
            });
            // Restore the main ID when the add media button is pressed
            jQuery('a.add_media').on('click', function () {
                wp.media.model.settings.post.id = wp_media_post_id;
            });
        });
    </script>
    <?php
}


function wporg_settings_init()
{
    // register a new setting for "wporg" page
    register_setting('plugin_options', 'plugin_options');
    // register a new section in the "wporg" page
    add_settings_section('plugin_key', 'Main Settings', 'plugin_section_text', 'settings');
    // register a new field in the "wporg_section_developers" section, inside the "wporg" page
    add_settings_field('plugin_text_string', 'API key', 'plugin_setting_string', 'settings', 'plugin_key');
    //add_settings_section('plugin_image', 'Submain Settings', 'plugin_section_text', 'settings');
    //add_settings_field('plugin_text_string', 'Image', 'plugin_setting_string_second', 'settings', 'plugin_image');
}

function plugin_section_text()
{
    echo '<p>Please, input API key.</p>';
}

function plugin_setting_string()
{
    $options = get_option('plugin_options');
    ?>
    <input required name='plugin_options[text_string]' size='40' type='text'
           value='<?php echo $options['text_string'] ?>'>
    <?php
}

function wpdocs_register_my_custom_submenu_page()
{
    add_submenu_page(
        'edit.php?post_type=location',
        'Settings',
        'Settings',
        'manage_options',
        'settings',
        'wpdocs_my_custom_submenu_page_callback');
}

function wpdocs_my_custom_submenu_page_callback()
{
    // check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    //upload images from media
    if (isset($_POST['submit_image_selector']) && isset($_POST['image_attachment_id'])) :
        update_option('media_selector_attachment_id', absint($_POST['image_attachment_id']));
    endif;

    wp_enqueue_media();

    ?>
    <div class="wrap">
        <h1><?= esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            // output security fields for the registered setting "wporg_options"
            settings_fields('plugin_options');
            // output setting sections and their fields
            // (sections are registered for "wporg", each field is registered to a specific section)
            do_settings_sections('settings');
            // output save settings button
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php


    ?>
    <div class="wrap">
        <form method='post'>
            <div class='image-preview-wrapper'>
                <img id='image-preview'
                     src='<?php echo wp_get_attachment_url(get_option('media_selector_attachment_id')); ?>'
                     height='100'>
            </div>
            <input id="upload_image_button" type="button" class="button" value="<?php _e('Upload image'); ?>"/>
            <input type='hidden' name='image_attachment_id' id='image_attachment_id'
                   value='<?php echo get_option('media_selector_attachment_id'); ?>'>
            <input type="submit" name="submit_image_selector" value="Save" class="button-primary">
        </form>
    </div>
    <?php
}

function save_submenu_page()
{
    if (isset($_POST['plugin_options'])) {

        update_option('plugin_options', $_POST['plugin_options']);
    }
}


function register_custom_post_type()
{
    $labels = array(
        'name' => _x('Locations', 'post type general name'),
        'singular_name' => _x('Location', 'post type singular name'),
        'menu_name' => _x('Locations', 'admin menu'),
        'name_admin_bar' => _x('Location', 'add new on admin bar'),
        'add_new' => _x('Add New', 'location'),
        'add_new_item' => __('Add New Location'),
        'new_item' => __('New Location'),
        'edit_item' => __('Edit Location'),
        'view_item' => __('View Location'),
        'all_items' => __('All Locations'),
        'search_items' => __('Search Locations'),
        'parent_item_colon' => __('Parent Locations:'),
        'not_found' => __('No Locations found.'),
        'not_found_in_trash' => __('No Locations found in Trash.'),
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'show_in_nav_menus' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_admin_bar' => true,
        'menu_position' => 10,
        'menu_icon' => 'dashicons-location-alt',
        'can_export' => true,
        'delete_with_user' => false,
        'hierarchical' => false,
        'has_archive' => false,
        'query_var' => true,
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'rewrite' => array(
            'slug' => 'location',
            'with_front' => true,
            'pages' => true,
            'feeds' => true,
        ),
        'supports' => array('title', 'editor')
    );

    register_post_type('location', $args);
}


function add_location_meta_boxes()
{
    add_meta_box(
        'wp_location_meta_box', //id
        'Location Information', //name
        'location_meta_box_display', //display function
        'location', //post type
        'normal', //location
        'high' //priority
    );
}

function location_meta_box_display($post)
{

    global $post;
    //set nonce field
    wp_nonce_field('wp_location_nonce', 'wp_location_nonce_field');

    //collect variables
    $location_address = get_post_meta($post->ID, 'location_address', true);
    $location_coordinates = get_post_meta($post->ID, 'location_coordinates', true);

    $API_KEY = get_option('plugin_options');

    ?>
    <div class="container">
        <h2>Welcome To Locations</h2>
        <form>
            <div class="form-group">
                <label for="example-search-input">Address: </label>
                <input class="form-control controls" id='pac-input' placeholder="Search Box" type="text"
                       name="location_address" value="<?php echo $location_address; ?>">
            </div>
            <div class="form-group" id="coordinates" style="display: none;">
                <label for="example-search-input">Coordinates</label>
                <input type='text' name='attachment_lat' id='attachment_lat'
                       value='<?php echo isset($location_coordinates['lat']) ? $location_coordinates['lat'] : '' ?>'>
                <input type='text' name='attachment_lng' id='attachment_lng'
                       value='<?php echo isset($location_coordinates['lng']) ? $location_coordinates['lng'] : '' ?>'>
            </div>
        </form>
    </div>

    <script type='text/javascript'>
        //
        //        var key = "";
        //        var script = document.createElement('script');
        //        script.src = "https://maps.googleapis.com/maps/api/js?key=" + key + "&ibraries=places&callback=initAutocomplete";
        //        document.body.appendChild(script);

        function initAutocomplete() {
            // Create the autocomplete object, restricting the search to geographical
            // location types.
            autocomplete = new google.maps.places.Autocomplete(
                (document.getElementById('pac-input')),
                {types: ['geocode']});

            // When the user selects an address
            // fields in the form.
            autocomplete.addListener('place_changed', function () {
                var lat = autocomplete.getPlace().geometry.location.lat();
                var lng = autocomplete.getPlace().geometry.location.lng();
                jQuery('#coordinates').fadeIn();
                jQuery('#attachment_lat').val(lat);
                jQuery('#attachment_lng').val(lng);

            });
        }

    </script>

    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $API_KEY['text_string'] ?>&libraries=places&callback=initAutocomplete"
            async defer></script>

    <?php
}

function save_location($post_id)
{
    /*
     * In production code, $slug should be set only once in the plugin,
     * preferably as a class property, rather than in each function that needs it.
     */
    $post_type = get_post_type($post_id);

    // If this isn't a 'book' post, don't update it.
    if ("location" != $post_type) return $post_id;

    //check for nonce
    if (!isset($_POST['wp_location_nonce_field'])) {
        return $post_id;
    }

    //verify nonce
    if (!wp_verify_nonce($_POST['wp_location_nonce_field'], 'wp_location_nonce')) {
        return $post_id;
    }

    if (!current_user_can("edit_post", $post_id))
        return $post_id;

    //check for autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    $attachment_lat = isset($_POST['attachment_lat']) ? sanitize_text_field($_POST['attachment_lat']) : '';
    $attachment_lng = isset($_POST['attachment_lng']) ? sanitize_text_field($_POST['attachment_lng']) : '';
    $location_address = isset($_POST['location_address']) ? sanitize_text_field($_POST['location_address']) : '';


    $location_coordinates = ['lat' => $attachment_lat, 'lng' => $attachment_lng];
    update_post_meta($post_id, 'location_coordinates', $location_coordinates);
    update_post_meta($post_id, 'location_address', $location_address);

}


