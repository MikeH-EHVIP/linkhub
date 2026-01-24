<?php
/**
 * Link Type Renderer
 *
 * @package ElyseVIP\LinkHub
 */

namespace ElyseVIP\LinkHub\Rendering;

use ElyseVIP\LinkHub\PostTypes\LinkPostType;
use ElyseVIP\LinkHub\Tracking\RedirectHandler;

/**
 * Link Type Renderer Class
 * 
 * Handles rendering of links with legacy styles (bar, card, heading)
 */
class LinkTypeRenderer {
    
    /**
     * Initialize renderer
     */
    public static function init() {
        // No filters needed for legacy rendering
    }

    /**
     * Render a link using legacy display style (bar/card/heading)
     *
     * @param int $link_id Link post ID
     * @param string $heading_size Heading size from tree settings
     * @return string Rendered HTML
     */
    public static function render($link_id, $heading_size = 'medium') {
        $link = get_post($link_id);
        if (!$link) {
            return '';
        }

        // Use legacy rendering
        return self::render_legacy($link_id, $heading_size);
    }

    /**
     * Render a link using legacy display style (bar/card/heading)
     *
     * @param int $link_id Link post ID
     * @param string $heading_size Heading size from tree settings
     * @return string Rendered HTML
     */
    private static function render_legacy($link_id, $heading_size = 'medium') {
        $link = get_post($link_id);
        $url = get_post_meta($link_id, LinkPostType::META_URL, true);
        $icon = get_post_meta($link_id, LinkPostType::META_ICON, true);
        $image_id = get_post_meta($link_id, LinkPostType::META_IMAGE, true);
        $display_style = get_post_meta($link_id, LinkPostType::META_DISPLAY_STYLE, true) ?: 'bar';
        $tracking_url = RedirectHandler::get_tracking_url($link_id);
        $background_color = get_post_meta($link_id, LinkPostType::META_BACKGROUND_COLOR, true) ?: '#f8a4c8';
        $text_color = get_post_meta($link_id, LinkPostType::META_TEXT_COLOR, true) ?: '#000000';

        if ($display_style === 'heading') {
            return self::render_legacy_heading($link, $text_color, $heading_size);
        } elseif ($display_style === 'card') {
            return self::render_legacy_card($link, $url, $tracking_url, $icon, $image_id, $background_color, $text_color);
        } else {
            return self::render_legacy_bar($link, $url, $tracking_url, $icon, $image_id, $background_color, $text_color);
        }
    }

    /**
     * Render legacy bar style (Linktree-like button with optional thumbnail)
     */
    private static function render_legacy_bar($link, $url, $tracking_url, $icon, $image_id, $background_color, $text_color) {
        ob_start();
        $final_url = $tracking_url ?: $url;
        ?>
        <div class="dtol-link-item dtol-bar-style">
            <a href="<?php echo esc_url($final_url); ?>"
               class="dtol-bar-link"
               target="_blank"
               rel="noopener noreferrer"
               style="background-color: <?php echo esc_attr($background_color); ?>; color: <?php echo esc_attr($text_color); ?>;">
                <?php if ($image_id): ?>
                    <div class="dtol-bar-thumbnail">
                        <?php echo wp_get_attachment_image($image_id, 'thumbnail', false, [
                            'alt' => esc_attr($link->post_title),
                        ]); ?>
                    </div>
                <?php elseif ($icon): ?>
                    <div class="dtol-bar-icon">
                        <span><?php echo wp_kses_post($icon); ?></span>
                    </div>
                <?php endif; ?>
                <span class="dtol-bar-title"><?php echo esc_html($link->post_title); ?></span>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render legacy heading style (section divider text, not clickable)
     */
    private static function render_legacy_heading($link, $text_color, $heading_size = 'medium') {
        ob_start();
        $size_class = 'dtol-heading-' . esc_attr($heading_size);
        ?>
        <div class="dtol-link-item dtol-heading-style <?php echo $size_class; ?>">
            <span class="dtol-heading-text" style="color: <?php echo esc_attr($text_color); ?>;">
                <?php echo esc_html($link->post_title); ?>
            </span>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render legacy card style (Linktree-like card with image and title banner)
     */
    private static function render_legacy_card($link, $url, $tracking_url, $icon, $image_id, $background_color, $text_color) {
        ob_start();
        $final_url = $tracking_url ?: $url;
        ?>
        <div class="dtol-link-item dtol-card-style">
            <a href="<?php echo esc_url($final_url); ?>"
               class="dtol-card-link"
               target="_blank"
               rel="noopener noreferrer">
                <?php if ($image_id): ?>
                    <div class="dtol-card-image">
                        <?php echo wp_get_attachment_image($image_id, 'large', false, [
                            'alt' => esc_attr($link->post_title),
                        ]); ?>
                    </div>
                <?php endif; ?>
                <div class="dtol-card-banner" style="background-color: <?php echo esc_attr($background_color); ?>; color: <?php echo esc_attr($text_color); ?>;">
                    <span class="dtol-card-title"><?php echo esc_html($link->post_title); ?></span>
                </div>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }
}
