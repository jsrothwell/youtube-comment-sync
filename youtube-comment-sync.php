<?php
/**
 * Plugin Name:       YouTube Comment Sync
 * Description:       Fetches and displays YouTube comments on a WordPress post via a shortcode.
 * Version:           1.1.0
 * Author:            Jamieson Rothwell
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       yt-comment-sync
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// =============================================================================
// Settings Page
// =============================================================================

/**
 * Adds a new options page under the "Settings" menu in the admin.
 */
function yt_comment_sync_add_admin_menu() {
    add_options_page(
        'YouTube Comment Sync Settings',
        'YouTube Comment Sync',
        'manage_options',
        'yt_comment_sync',
        'yt_comment_sync_options_page_html'
    );
}
add_action( 'admin_menu', 'yt_comment_sync_add_admin_menu' );

/**
 * Registers the settings for the plugin.
 */
function yt_comment_sync_settings_init() {
    register_setting( 'ytCommentSync', 'yt_comment_sync_settings' );

    add_settings_section(
        'yt_comment_sync_api_section',
        'API Settings',
        'yt_comment_sync_settings_section_callback',
        'ytCommentSync'
    );

    add_settings_field(
        'yt_comment_sync_api_key',
        'YouTube Data API v3 Key',
        'yt_comment_sync_api_key_render',
        'ytCommentSync',
        'yt_comment_sync_api_section'
    );
}
add_action( 'admin_init', 'yt_comment_sync_settings_init' );

/**
 * Renders the input field for the API key.
 */
function yt_comment_sync_api_key_render() {
    $options = get_option( 'yt_comment_sync_settings' );
    ?>
    <input type='text' name='yt_comment_sync_settings[yt_comment_sync_api_key]' value='<?php echo isset($options['yt_comment_sync_api_key']) ? esc_attr($options['yt_comment_sync_api_key']) : ''; ?>' class="regular-text">
    <p class="description">You can get an API key from the <a href="https://console.cloud.google.com/apis/library/youtube.googleapis.com" target="_blank">Google Cloud Console</a>.</p>
    <?php
}

/**
 * Callback for the settings section description.
 */
function yt_comment_sync_settings_section_callback() {
    echo '<p>Enter your YouTube API Key below. This will be used for all shortcodes unless a key is specified directly in the shortcode.</p>';
}

/**
 * Renders the HTML for the settings page.
 */
function yt_comment_sync_options_page_html() {
    ?>
    <div class="wrap">
        <h1>YouTube Comment Sync Settings</h1>
        <form action='options.php' method='post'>
            <?php
            settings_fields( 'ytCommentSync' );
            do_settings_sections( 'ytCommentSync' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// =============================================================================
// Shortcode and Frontend Logic
// =============================================================================

/**
 * Enqueue scripts and styles.
 */
function yt_comment_sync_enqueue_assets() {
    // Only load these scripts on single post pages.
    if ( is_single() ) {
        wp_enqueue_style(
            'yt-comments-style',
            plugin_dir_url( __FILE__ ) . 'yt-comments-style.css',
            [],
            '1.1.0'
        );

        wp_enqueue_script(
            'yt-comments-script',
            plugin_dir_url( __FILE__ ) . 'yt-comments-script.js',
            [],
            '1.1.0',
            true // Load in footer
        );
    }
}
add_action( 'wp_enqueue_scripts', 'yt_comment_sync_enqueue_assets' );

/**
 * Add the shortcode for displaying comments.
 * Usage: [youtube_comments video_url="YOUR_VIDEO_URL"]
 * The 'api_key' attribute is now optional.
 */
function yt_comment_sync_shortcode( $atts ) {
    // Get saved options
    $saved_options = get_option( 'yt_comment_sync_settings' );
    $saved_api_key = isset( $saved_options['yt_comment_sync_api_key'] ) ? $saved_options['yt_comment_sync_api_key'] : '';

    // Extract shortcode attributes
    $attributes = shortcode_atts(
        [
            'video_url' => '',
            'api_key'   => $saved_api_key, // Default to the saved API key
        ],
        $atts
    );

    // An API key in the shortcode will override the saved one.
    $api_key = ! empty( $attributes['api_key'] ) ? $attributes['api_key'] : $saved_api_key;

    if ( empty( $attributes['video_url'] ) ) {
        return '<p>Error: Please provide a video_url in the shortcode.</p>';
    }

    if ( empty( $api_key ) ) {
        return '<p>Error: API key is missing. Please add it in the plugin settings or the shortcode.</p>';
    }

    $video_id = yt_comment_sync_extract_video_id( $attributes['video_url'] );

    if ( ! $video_id ) {
        return '<p>Error: Invalid YouTube URL provided.</p>';
    }

    ob_start();
    ?>
    <div
        id="yt-comments-wrapper"
        data-video-id="<?php echo esc_attr( $video_id ); ?>"
        data-api-key="<?php echo esc_attr( $api_key ); ?>"
    >
        <h3 class="yt-comments-title">Comments from YouTube</h3>
        <div id="youtube-comments-container">
            <div class="yt-comments-loader">
                <div class="yt-spinner"></div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'youtube_comments', 'yt_comment_sync_shortcode' );

/**
 * Helper function to extract Video ID from URL.
 */
function yt_comment_sync_extract_video_id( $url ) {
    preg_match( '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches );
    return isset( $matches[1] ) ? $matches[1] : null;
}
