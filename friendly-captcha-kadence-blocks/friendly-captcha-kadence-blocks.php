<?php

/**
 * Plugin Name: Friendly Captcha – Kadence Blocks Integration
 * Description: Adds Kadence Blocks integration for Friendly Captcha.
 * Version: 1.0.0
 * Author: La Cabane Digitale
 * Author URI: https://www.lacabanedigitale.fr
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', static function () {
    if (! class_exists('FriendlyCaptcha_Plugin')) {
        return;
    }

    if (! method_exists('FriendlyCaptcha_Plugin', 'is_configured')) {
        return;
    }

    // Make the integration selectable in the Friendly Captcha settings screen.
    FriendlyCaptcha_Plugin::$integrations[] = array(
        'name' => 'Kadence Blocks',
        'slug' => 'kadence-blocks',
        'entry' => 'kadence-blocks/kadence-blocks.php',
        'settings_description' => 'Enable Friendly Captcha for <a href="https://wordpress.org/plugins/kadence-blocks/" target="_blank">Kadence Blocks</a>.',
    );

    add_filter('the_content', 'frcaptcha_kadence_inject_widget');
    add_filter('kadence_blocks_advanced_form_submission_reject', 'frcaptcha_kadence_validate_submission', 10, 4);
    add_filter('kadence_blocks_advanced_form_submission_reject_message', 'frcaptcha_kadence_reject_message', 10, 4);
    add_action('wp_footer', 'frcaptcha_kadence_enqueue_assets');
});

/**
 * Append the Friendly Captcha widget to Kadence forms on the front-end.
 */
function frcaptcha_kadence_inject_widget($content)
{
    if (is_admin() || ! is_singular()) {
        return $content;
    }

    if (strpos($content, 'wp-block-kadence-advanced-form') === false) {
        return $content;
    }

    $plugin = FriendlyCaptcha_Plugin::$instance;
    if (! $plugin || ! $plugin->is_configured()) {
        return $content;
    }

    if (! function_exists('frcaptcha_generate_widget_tag_from_plugin')) {
        return $content;
    }

    $widget = frcaptcha_generate_widget_tag_from_plugin($plugin);

    return str_replace('</form>', $widget . '</form>', $content);
}

/**
 * Validate Kadence Blocks submissions with Friendly Captcha.
 */
function frcaptcha_kadence_validate_submission($reject, $form_args, $processed_fields, $post_id)
{
    $plugin = FriendlyCaptcha_Plugin::$instance;

    if (! $plugin || ! $plugin->is_configured()) {
        return $reject;
    }

    if (! function_exists('frcaptcha_get_sanitized_frcaptcha_solution_from_post')) {
        return $reject;
    }

    $solution = frcaptcha_get_sanitized_frcaptcha_solution_from_post();

    if (empty($solution)) {
        return true;
    }

    if (! function_exists('frcaptcha_verify_captcha_solution')) {
        return true;
    }

    $verification = frcaptcha_verify_captcha_solution(
        $solution,
        $plugin->get_sitekey(),
        $plugin->get_api_key(),
        'kadence-blocks'
    );

    if (! $verification['success']) {
        return true;
    }

    return false;
}

/**
 * Provide a consistent Friendly Captcha error message.
 */
function frcaptcha_kadence_reject_message($message, $form_args, $processed_fields, $post_id)
{
    if (is_callable(array('FriendlyCaptcha_Plugin', 'default_error_user_message'))) {
        return FriendlyCaptcha_Plugin::default_error_user_message();
    }

    return $message;
}

/**
 * Load Friendly Captcha assets and the Kadence reset helper script.
 */
function frcaptcha_kadence_enqueue_assets()
{
    if (is_admin() || ! is_singular()) {
        return;
    }

    global $post;

    if (! $post || ! function_exists('has_block')) {
        return;
    }

    if (! has_block('kadence/advanced-form', $post)) {
        return;
    }

    if (function_exists('frcaptcha_enqueue_widget_scripts')) {
        frcaptcha_enqueue_widget_scripts();
    }

    wp_enqueue_script(
        'frcaptcha_kadence_reset',
        plugins_url('assets/script.js', __FILE__),
        array(),
        null,
        true
    );
}
