<?php
/**
 * Plugin Name: TikTok Video Downloader
 * Plugin URI: https://github.com/kuromifanboi/Tiktok-Video-Downloader
 * Description: Allows users to download TikTok videos from your WordPress website and save them on their local computer.
 * Version: 2.0.0
 * Author: Peter Missick
 * Author URI: https://petermissick.com
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Add a submenu under the "Settings" menu in the WordPress admin dashboard
function tiktok_video_downloader_submenu() {
    add_submenu_page(
        'options-general.php',
        'TikTok Video Downloader',
        'Video Downloader',
        'manage_options',
        'tiktok-video-downloader',
        'tiktok_video_downloader_page'
    );
}
add_action('admin_menu', 'tiktok_video_downloader_submenu');

// Function to render the plugin settings page
function tiktok_video_downloader_page() {
    ?>
    <div class="wrap">
        <h1>TikTok Video Downloader</h1>
        <p>Manage the settings and options for the TikTok Video Downloader plugin.</p>
        <form method="post" action="options.php">
            <?php settings_fields('tiktok_video_downloader_settings'); ?>
            <?php do_settings_sections('tiktok_video_downloader'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register plugin settings
function tiktok_video_downloader_register_settings() {
    register_setting('tiktok_video_downloader_settings', 'tiktok_video_downloader_option');
    add_settings_section('tiktok_video_downloader_section', 'Settings', 'tiktok_video_downloader_section_callback', 'tiktok_video_downloader');
    add_settings_field('tiktok_video_downloader_field', 'Option', 'tiktok_video_downloader_field_callback', 'tiktok_video_downloader', 'tiktok_video_downloader_section');
}
add_action('admin_init', 'tiktok_video_downloader_register_settings');

// Callback function for the settings section
function tiktok_video_downloader_section_callback() {
    echo 'This is the section description.';
}

// Callback function for the settings field
function tiktok_video_downloader_field_callback() {
    $option = get_option('tiktok_video_downloader_option');
    echo "<input type='text' name='tiktok_video_downloader_option' value='" . esc_attr($option) . "' />";
}

// Shortcode to display the TikTok Video Downloader form
function tiktok_video_downloader_shortcode() {
    ob_start();
    ?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="tiktok_video_download">
        <input type="url" name="video_url" placeholder="Enter TikTok video URL" required>
        <button type="submit">Download</button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('tiktok_video_downloader', 'tiktok_video_downloader_shortcode');

// Action to handle the video download
function tiktok_video_download_action() {
    if (isset($_POST['action']) && $_POST['action'] === 'tiktok_video_download') {
        if (isset($_POST['video_url'])) {
            $video_url = esc_url_raw($_POST['video_url']);

            // Validate the video URL
            if (!filter_var($video_url, FILTER_VALIDATE_URL)) {
                wp_die('Invalid video URL');
            }

            // Generate a unique filename for the downloaded video
            $filename = 'tiktok_video_' . uniqid() . '.mp4';

            // Download the video file using cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $video_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.93 Safari/537.36');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: */*',
                'Accept-Language: en-US,en;q=0.5',
                'Connection: keep-alive',
                'DNT: 1',
                'Referer: ' . $video_url,
            ]);

            $video_data = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($video_data) {
                file_put_contents($filename, $video_data);

                // Set appropriate headers for file download
                header("Content-Disposition: attachment; filename=" . $filename);
                header("Content-Type: application/octet-stream");
                header("Content-Length: " . filesize($filename));

                // Read and output the file contents
                readfile($filename);

                // Delete the temporary file
                unlink($filename);

                // Stop further execution
                exit;
            } else {
                wp_die('Failed to download the video: ' . $error);
            }
        }
    }
}
add_action('admin_post_nopriv_tiktok_video_download', 'tiktok_video_download_action');
add_action('admin_post_tiktok_video_download', 'tiktok_video_download_action');
