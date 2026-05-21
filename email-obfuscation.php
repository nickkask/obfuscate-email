<?php
/**
 * Plugin Name: Email Obfuscation
 * Plugin URI: https://kaskcreative.com
 * Description: Protects email addresses from spam bots using base64 encoding and JavaScript decoding. Use the [email] shortcode or obfuscate_email() function.
 * Version: 1.0
 * Author: Nick Kask
 * Author URI: https://kaskcreative.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: email-obfuscation
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add JavaScript for decoding obfuscated emails
 */
function email_obfuscation_scripts() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Decode all obfuscated emails
        var obfuscatedEmails = document.querySelectorAll('.obfuscated-email');
        obfuscatedEmails.forEach(function(element) {
            var encoded = element.getAttribute('data-email');
            if (encoded) {
                var decoded = atob(encoded);
                element.href = 'mailto:' + decoded;
                if (element.textContent === '') {
                    element.textContent = decoded;
                }
            }
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'email_obfuscation_scripts');

/**
 * Shortcode: [email]yourname@example.com[/email]
 * 
 * Usage examples:
 * [email]contact@example.com[/email]
 * [email text="Contact Us"]contact@example.com[/email]
 */
function obfuscate_email_shortcode($atts, $content = null) {
    if (empty($content)) {
        return '';
    }
    
    $email = sanitize_email($content);
    $encoded = base64_encode($email);
    
    // Extract display text if provided
    $display_text = isset($atts['text']) ? esc_html($atts['text']) : '';
    
    return sprintf(
        '<a href="#" class="obfuscated-email" data-email="%s">%s</a>',
        esc_attr($encoded),
        $display_text
    );
}
add_shortcode('email', 'obfuscate_email_shortcode');

/**
 * PHP function for use in theme files
 * 
 * Usage examples:
 * <?php echo obfuscate_email('contact@example.com'); ?>
 * <?php echo obfuscate_email('contact@example.com', 'Email Us'); ?>
 * 
 * @param string $email The email address to obfuscate
 * @param string $display_text Optional text to display instead of email
 * @return string HTML link with obfuscated email
 */
function obfuscate_email($email, $display_text = '') {
    $encoded = base64_encode($email);
    $display = !empty($display_text) ? esc_html($display_text) : '';
    
    return sprintf(
        '<a href="#" class="obfuscated-email" data-email="%s">%s</a>',
        esc_attr($encoded),
        $display
    );
}

/**
 * Automatically obfuscate email addresses in content
 * 
 * @param string $content Post/page content
 * @return string Content with obfuscated emails
 */
function auto_obfuscate_emails($content) {
    // Email regex pattern
    $pattern = '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/';
    
    // Find all emails in content
    preg_match_all($pattern, $content, $matches);
    
    if (!empty($matches[0])) {
        foreach ($matches[0] as $email) {
            // Skip if email is already inside an obfuscated-email link
            if (strpos($content, 'data-email="' . base64_encode($email) . '"') !== false) {
                continue;
            }
            
            // Skip if email is already in a mailto link
            if (strpos($content, 'mailto:' . $email) !== false) {
                continue;
            }
            
            // Create obfuscated version
            $encoded = base64_encode($email);
            $obfuscated = sprintf(
                '<a href="#" class="obfuscated-email" data-email="%s">%s</a>',
                esc_attr($encoded),
                esc_html($email)
            );
            
            // Replace plain text email with obfuscated version
            $content = str_replace($email, $obfuscated, $content);
        }
    }
    
    return $content;
}
add_filter('the_content', 'auto_obfuscate_emails', 20);
add_filter('widget_text', 'auto_obfuscate_emails', 20);
add_filter('the_excerpt', 'auto_obfuscate_emails', 20);
