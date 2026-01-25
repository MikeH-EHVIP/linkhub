<?php
/**
 * Link Type Renderer
 *
 * @package LinkHub
 */

namespace LinkHub\Rendering;

use LinkHub\PostTypes\LinkPostType;
use LinkHub\Tracking\RedirectHandler;

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
     * Render a link
     *
     * @param int $link_id Link post ID
     * @param int $design_id Design post ID (0 for no design override)
     * @param string $heading_size Heading size from tree settings
     * @param string $link_bg_color Link button background color from tree
     * @param string $link_text_color Link button text color from tree
     * @return string Rendered HTML
     */
    public static function render($link_id, $design_id = 0, $heading_size = 'medium', $link_bg_color = '#eeeeee', $link_text_color = '#000000') {
        $link = get_post($link_id);
        if (!$link) {
            return '';
        }

        // Use legacy rendering
        return self::render_legacy($link_id, $heading_size, $link_bg_color, $link_text_color);
    }

    /**
     * Render a link using legacy display style (bar/card/heading)
     *
     * @param int $link_id Link post ID
     * @param string $heading_size Heading size from tree settings
     * @param string $link_bg_color Link button background color from tree
     * @param string $link_text_color Link button text color from tree
     * @return string Rendered HTML
     */
    private static function render_legacy($link_id, $heading_size = 'medium', $link_bg_color = '#eeeeee', $link_text_color = '#000000') {
        $link = get_post($link_id);
        $url = get_post_meta($link_id, LinkPostType::META_URL, true);
        $icon = get_post_meta($link_id, LinkPostType::META_ICON, true);
        $image_id = get_post_meta($link_id, LinkPostType::META_IMAGE, true);
        $display_style = get_post_meta($link_id, LinkPostType::META_DISPLAY_STYLE, true) ?: 'bar';
        $tracking_url = RedirectHandler::get_tracking_url($link_id);

        // Use tree-level colors (free version)
        $background_color = $link_bg_color;
        $text_color = $link_text_color;

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
        <div class="lh-link-item lh-bar-style">
            <a href="<?php echo esc_url($final_url); ?>"
               class="lh-bar-link"
               target="_blank"
               rel="noopener noreferrer">
                <?php if ($image_id): ?>
                    <div class="lh-bar-thumbnail">
                        <?php echo wp_get_attachment_image($image_id, 'thumbnail', false, [
                            'alt' => esc_attr($link->post_title),
                        ]); ?>
                    </div>
                <?php elseif ($icon): ?>
                    <div class="lh-bar-icon">
                        <span><?php echo wp_kses_post($icon); ?></span>
                    </div>
                <?php endif; ?>
                <span class="lh-bar-title"><?php echo esc_html($link->post_title); ?></span>
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
        $size_class = 'lh-heading-' . esc_attr($heading_size);
        ?>
        <div class="lh-link-item lh-heading-style <?php echo $size_class; ?>">
            <span class="lh-heading-text" style="color: <?php echo esc_attr($text_color); ?>;">
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
        <div class="lh-link-item lh-card-style">
            <a href="<?php echo esc_url($final_url); ?>"
               class="lh-card-link"
               target="_blank"
               rel="noopener noreferrer">
                <?php if ($image_id): ?>
                    <div class="lh-card-image">
                        <?php echo wp_get_attachment_image($image_id, 'large', false, [
                            'alt' => esc_attr($link->post_title),
                        ]); ?>
                    </div>
                <?php endif; ?>
                <div class="lh-card-banner">
                    <span class="lh-card-title"><?php echo esc_html($link->post_title); ?></span>
                </div>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }
}

