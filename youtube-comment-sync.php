<?php
/**
 * Plugin Name:       YouTube Comment Sync
 * Description:       Fetches and displays YouTube comments on a WordPress post.
 * Version:           1.0.0
 * Author:            Jamieson Rothwell
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       yt-comment-sync
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

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
            '1.0.0'
        );

        wp_enqueue_script(
            'yt-comments-script',
            plugin_dir_url( __FILE__ ) . 'yt-comments-script.js',
            [],
            '1.0.0',
            true // Load in footer
        );
    }
}
add_action( 'wp_enqueue_scripts', 'yt_comment_sync_enqueue_assets' );

/**
 * Add the shortcode for displaying comments.
 * Usage: [youtube_comments video_url="YOUR_VIDEO_URL" api_key="YOUR_API_KEY"]
 */
function yt_comment_sync_shortcode( $atts ) {
    // Extract shortcode attributes
    $attributes = shortcode_atts(
        [
            'video_url' => '',
            'api_key'   => '',
        ],
        $atts
    );

    if ( empty( $attributes['video_url'] ) || empty( $attributes['api_key'] ) ) {
        return '<p>Error: Please provide a video_url and api_key in the shortcode.</p>';
    }

    // Prepare data to be passed to JavaScript
    $video_id = yt_comment_sync_extract_video_id( $attributes['video_url'] );

    if ( ! $video_id ) {
        return '<p>Error: Invalid YouTube URL provided.</p>';
    }

    // The HTML structure that our JavaScript will target.
    ob_start();
    ?>
    <div
        id="yt-comments-wrapper"
        data-video-id="<?php echo esc_attr( $video_id ); ?>"
        data-api-key="<?php echo esc_attr( $attributes['api_key'] ); ?>"
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
