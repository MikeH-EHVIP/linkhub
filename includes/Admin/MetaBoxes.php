<?php
/**
 * Admin Meta Boxes
 *
 * @package ElyseVIP\LinkHub
 */

namespace ElyseVIP\LinkHub\Admin;

use ElyseVIP\LinkHub\PostTypes\TreePostType;
use ElyseVIP\LinkHub\PostTypes\LinkPostType;

/**
 * Meta Boxes Class
 */
class MetaBoxes {
    
    /**
     * Singleton instance
     *
     * @var MetaBoxes
     */
    private static $instance = null;
    
    /**
     * Meta key for tree links
     */
    const META_TREE_LINKS = '_LH_tree_links';
    
    /**
     * Get singleton instance
     *
     * @return MetaBoxes
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_tree_links'], 10, 2);
        add_action('save_post', [$this, 'save_tree_settings'], 10, 2);
        add_action('save_post_' . LinkPostType::POST_TYPE, [$this, 'save_link_meta'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Tree settings meta box (header, about, social)
        add_meta_box(
            'LH_tree_settings',
            __('Tree Settings', 'linkhub'),
            [$this, 'render_tree_settings_meta_box'],
            TreePostType::POST_TYPE,
            'normal',
            'high'
        );

        // Tree links meta box
        add_meta_box(
            'LH_tree_links',
            __('Link Tree Items', 'linkhub'),
            [$this, 'render_tree_links_meta_box'],
            TreePostType::POST_TYPE,
            'normal',
            'high'
        );
        
        // Link details meta box
        add_meta_box(
            'LH_link_details',
            __('Link Details', 'linkhub'),
            [$this, 'render_link_details_meta_box'],
            LinkPostType::POST_TYPE,
            'normal',
            'high'
        );
        
        // Link statistics meta box
        add_meta_box(
            'LH_link_stats',
            __('Click Statistics', 'linkhub'),
            [$this, 'render_link_stats_meta_box'],
            LinkPostType::POST_TYPE,
            'side',
            'default'
        );
    }
    
    /**
     * Render tree links meta box
     *
     * @param \WP_Post $post Post object
     */
    public function render_tree_links_meta_box($post) {
        wp_nonce_field('LH_tree_links_nonce', 'LH_tree_links_nonce');
        
        $tree_links = get_post_meta($post->ID, TreePostType::META_TREE_LINKS, true);
        if (!is_array($tree_links)) {
            $tree_links = [];
        }
        
        // Get all available links
        $available_links = get_posts([
            'post_type'      => LinkPostType::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
        
        ?>
        <div id="dtol-tree-links-manager">
            <div class="dtol-links-toolbar">
                <select id="dtol-add-link-select" style="min-width: 300px;">
                    <option value=""><?php _e('Select a link to add...', 'linkhub'); ?></option>
                    <?php foreach ($available_links as $link): ?>
                        <option value="<?php echo esc_attr($link->ID); ?>">
                            <?php echo esc_html($link->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="dtol-add-link-btn" class="button">
                    <?php _e('Add Link', 'linkhub'); ?>
                </button>
                <button type="button" id="dtol-add-heading-btn" class="button">
                    <?php _e('Add Heading', 'linkhub'); ?>
                </button>
                <a href="<?php echo admin_url('post-new.php?post_type=' . LinkPostType::POST_TYPE); ?>" 
                   class="button" target="_blank">
                    <?php _e('Create New Link', 'linkhub'); ?>
                </a>
            </div>
            
            <ul id="dtol-selected-links" class="dtol-links-list">
                <?php foreach ($tree_links as $tree_link): 
                    // Check if it's a heading
                    if (is_array($tree_link) && isset($tree_link['type']) && $tree_link['type'] === 'heading'):
                        $heading_text = isset($tree_link['text']) ? $tree_link['text'] : '';
                        $heading_size = isset($tree_link['size']) ? $tree_link['size'] : 'medium';
                ?>
                    <li class="dtol-link-item dtol-heading-item" data-type="heading">
                        <span class="dtol-drag-handle dashicons dashicons-menu"></span>
                        <div class="dtol-link-info">
                            <span class="dashicons dashicons-editor-textcolor" style="color: #999;"></span>
                            <strong><?php _e('Heading:', 'linkhub'); ?> <?php echo esc_html($heading_text); ?></strong>
                            <span class="dtol-link-meta"><?php printf(__('Size: %s', 'linkhub'), $heading_size); ?></span>
                        </div>
                        <div class="dtol-link-actions">
                            <button type="button" class="button button-small dtol-edit-heading">
                                <?php _e('Edit', 'linkhub'); ?>
                            </button>
                            <button type="button" class="button button-small dtol-remove-link">
                                <?php _e('Remove', 'linkhub'); ?>
                            </button>
                        </div>
                        <button type="button" class="dtol-insert-here button button-small" title="<?php _e('Insert item here', 'linkhub'); ?>">
                            <span class="dashicons dashicons-plus-alt"></span>
                        </button>
                        <input type="hidden" class="dtol-item-type" name="LH_tree_items[type][]" value="heading">
                        <input type="hidden" class="dtol-heading-text" name="LH_tree_items[text][]" value="<?php echo esc_attr($heading_text); ?>">
                        <input type="hidden" class="dtol-heading-size" name="LH_tree_items[size][]" value="<?php echo esc_attr($heading_size); ?>">
                    </li>
                <?php else:
                    // It's a link
                    $link_id = isset($tree_link['link_id']) ? $tree_link['link_id'] : $tree_link;
                    
                    $link = get_post($link_id);
                    if (!$link) continue;
                    
                    $url = get_post_meta($link_id, LinkPostType::META_URL, true);
                    $clicks = get_post_meta($link_id, LinkPostType::META_CLICK_COUNT, true);
                    $image_id = get_post_meta($link_id, LinkPostType::META_IMAGE, true);
                    $thumbnail = '';
                    if ($image_id) {
                        $thumbnail = wp_get_attachment_image_url($image_id, 'thumbnail');
                    }
                ?>
                    <li class="dtol-link-item" data-link-id="<?php echo esc_attr($link_id); ?>" data-type="link">
                        <span class="dtol-drag-handle dashicons dashicons-menu"></span>
                        <?php if ($thumbnail): ?>
                            <img src="<?php echo esc_url($thumbnail); ?>" class="dtol-link-thumbnail" alt="">
                        <?php else: ?>
                            <span class="dtol-link-thumbnail-placeholder dashicons dashicons-admin-links"></span>
                        <?php endif; ?>
                        <div class="dtol-link-info">
                            <strong><?php echo esc_html($link->post_title); ?></strong>
                            <span class="dtol-link-url"><?php echo esc_html($url); ?></span>
                            <span class="dtol-link-clicks"><?php printf(__('%d clicks', 'linkhub'), $clicks); ?></span>
                        </div>
                        <div class="dtol-link-actions">
                            <a href="<?php echo get_edit_post_link($link_id); ?>" 
                               class="button button-small" target="_blank">
                                <?php _e('Edit', 'linkhub'); ?>
                            </a>
                            <button type="button" class="button button-small dtol-remove-link">
                                <?php _e('Remove', 'linkhub'); ?>
                            </button>
                        </div>
                        <button type="button" class="dtol-insert-here button button-small" title="<?php _e('Insert item here', 'linkhub'); ?>">
                            <span class="dashicons dashicons-plus-alt"></span>
                        </button>
                        <input type="hidden" class="dtol-item-type" name="LH_tree_items[type][]" value="link">
                        <input type="hidden" class="dtol-link-id-input" name="LH_tree_items[link_id][]" value="<?php echo esc_attr($link_id); ?>">
                    </li>
                <?php endif; endforeach; ?>
            </ul>
            
            <?php if (empty($tree_links)): ?>
                <p class="dtol-empty-message">
                    <?php _e('No links added yet. Select a link above to get started.', 'linkhub'); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <style>
            .dtol-links-toolbar { margin-bottom: 15px; }
            .dtol-links-toolbar select { margin-right: 5px; }
            .dtol-links-list { list-style: none; margin: 0; padding: 0; }
            .dtol-link-item { 
                background: #f9f9f9; 
                border: 1px solid #ddd; 
                padding: 12px; 
                margin-bottom: 8px; 
                display: flex; 
                align-items: center; 
                cursor: move;
                position: relative;
            }
            .dtol-heading-item { background: #fff9e6; border-left: 4px solid #f0b849; }
            .dtol-drag-handle { margin-right: 10px; color: #999; cursor: grab; flex-shrink: 0; }
            .dtol-link-thumbnail { 
                width: 50px; 
                height: 50px; 
                object-fit: cover; 
                border-radius: 4px; 
                margin-right: 12px;
                flex-shrink: 0;
            }
            .dtol-link-thumbnail-placeholder {
                width: 50px;
                height: 50px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #ddd;
                border-radius: 4px;
                margin-right: 12px;
                color: #999;
                font-size: 28px;
                flex-shrink: 0;
            }
            .dtol-link-info { flex: 1; min-width: 0; }
            .dtol-link-info strong { display: block; margin-bottom: 4px; }
            .dtol-link-url, .dtol-link-clicks, .dtol-link-meta { 
                font-size: 12px; 
                color: #666; 
                margin-right: 15px; 
                display: inline-block;
            }
            .dtol-link-actions { display: flex; gap: 5px; flex-shrink: 0; }
            .dtol-insert-here {
                position: absolute;
                top: -12px;
                left: 50%;
                transform: translateX(-50%);
                opacity: 0;
                transition: opacity 0.2s;
                z-index: 10;
                padding: 2px 8px !important;
                height: auto !important;
                line-height: 1 !important;
                background: #2271b1 !important;
                color: white !important;
                border-color: #2271b1 !important;
            }
            .dtol-insert-here .dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
            }
            .dtol-link-item:hover .dtol-insert-here {
                opacity: 1;
            }
            .dtol-empty-message { 
                padding: 20px; 
                text-align: center; 
                background: #f9f9f9; 
                border: 1px dashed #ddd; 
                color: #666;
            }
        </style>
        <?php
    }

    /**
     * Render tree settings meta box (header image, about text, social links)
     *
     * @param \WP_Post $post Post object
     */
    public function render_tree_settings_meta_box($post) {
        wp_nonce_field('LH_tree_settings_nonce', 'LH_tree_settings_nonce');

        $header_image_id = get_post_meta($post->ID, TreePostType::META_HEADER_IMAGE, true);
        $about_text = get_post_meta($post->ID, TreePostType::META_ABOUT_TEXT, true);
        $social_links = get_post_meta($post->ID, TreePostType::META_SOCIAL_LINKS, true);

        // Styling options
        $background_color = get_post_meta($post->ID, TreePostType::META_BACKGROUND_COLOR, true) ?: '#8b8178';
        $tree_background_color = get_post_meta($post->ID, TreePostType::META_TREE_BACKGROUND_COLOR, true) ?: '#f5f5f5';
        $background_image_id = get_post_meta($post->ID, TreePostType::META_BACKGROUND_IMAGE, true);
        $hero_shape = get_post_meta($post->ID, TreePostType::META_HERO_SHAPE, true) ?: 'round';
        $hero_fade = get_post_meta($post->ID, TreePostType::META_HERO_FADE, true);
        $title_color = get_post_meta($post->ID, TreePostType::META_TITLE_COLOR, true) ?: '#1a1a1a';
        $bio_color = get_post_meta($post->ID, TreePostType::META_BIO_COLOR, true) ?: '#555555';
        $social_style = get_post_meta($post->ID, TreePostType::META_SOCIAL_STYLE, true) ?: 'circle';
        $social_color = get_post_meta($post->ID, TreePostType::META_SOCIAL_COLOR, true) ?: '#333333';
        $heading_size = get_post_meta($post->ID, TreePostType::META_HEADING_SIZE, true) ?: 'medium';

        if (!is_array($social_links)) {
            $social_links = [];
        }

        // Available social platforms
        $platforms = [
            'twitter' => ['label' => 'Twitter/X', 'icon' => 'dashicons-twitter'],
            'facebook' => ['label' => 'Facebook', 'icon' => 'dashicons-facebook'],
            'instagram' => ['label' => 'Instagram', 'icon' => 'dashicons-instagram'],
            'linkedin' => ['label' => 'LinkedIn', 'icon' => 'dashicons-linkedin'],
            'youtube' => ['label' => 'YouTube', 'icon' => 'dashicons-youtube'],
            'tiktok' => ['label' => 'TikTok', 'icon' => 'dashicons-video-alt3'],
            'pinterest' => ['label' => 'Pinterest', 'icon' => 'dashicons-pinterest'],
            'github' => ['label' => 'GitHub', 'icon' => 'dashicons-randomize'],
            'email' => ['label' => 'Email', 'icon' => 'dashicons-email'],
            'website' => ['label' => 'Website', 'icon' => 'dashicons-admin-site-alt3'],
        ];

        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="LH_header_image"><?php _e('Header Image', 'linkhub'); ?></label>
                </th>
                <td>
                    <div class="dtol-header-image-upload">
                        <input type="hidden" id="LH_header_image_id" name="LH_header_image_id"
                               value="<?php echo esc_attr($header_image_id); ?>">
                        <div class="dtol-header-image-preview" style="margin-bottom: 10px;">
                            <?php if ($header_image_id):
                                echo wp_get_attachment_image($header_image_id, 'medium');
                            endif; ?>
                        </div>
                        <button type="button" class="button dtol-upload-header-image">
                            <?php echo $header_image_id ? __('Change Image', 'linkhub') : __('Upload Image', 'linkhub'); ?>
                        </button>
                        <?php if ($header_image_id): ?>
                            <button type="button" class="button dtol-remove-header-image">
                                <?php _e('Remove', 'linkhub'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    <p class="description">
                        <?php _e('Profile image or logo displayed at the top of your link tree. Recommended size: 400x400px.', 'linkhub'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="LH_about_text"><?php _e('About Text', 'linkhub'); ?></label>
                </th>
                <td>
                    <textarea id="LH_about_text" name="LH_about_text"
                              rows="4" class="large-text"><?php echo esc_textarea($about_text); ?></textarea>
                    <p class="description">
                        <?php _e('Short bio or description displayed below your profile image.', 'linkhub'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php _e('Social Links', 'linkhub'); ?></label>
                </th>
                <td>
                    <div id="dtol-social-links-container">
                        <?php foreach ($social_links as $index => $social): ?>
                            <div class="dtol-social-link-row" style="margin-bottom: 10px; display: flex; gap: 10px; align-items: center;">
                                <select name="LH_social_platform[]" style="width: 150px;">
                                    <?php foreach ($platforms as $key => $platform): ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($social['platform'], $key); ?>>
                                            <?php echo esc_html($platform['label']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="url" name="LH_social_url[]"
                                       value="<?php echo esc_attr($social['url']); ?>"
                                       placeholder="<?php _e('URL', 'linkhub'); ?>"
                                       class="regular-text">
                                <button type="button" class="button dtol-remove-social-link">
                                    <span class="dashicons dashicons-no-alt"></span>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button dtol-add-social-link" style="margin-top: 10px;">
                        <?php _e('Add Social Link', 'linkhub'); ?>
                    </button>
                    <p class="description">
                        <?php _e('Social media links displayed as icons in a bar.', 'linkhub'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <h3 style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <?php _e('Page Styling', 'linkhub'); ?>
        </h3>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="LH_background_color"><?php _e('Page Background', 'linkhub'); ?></label>
                </th>
                <td>
                    <input type="text" id="LH_background_color" name="LH_background_color"
                           value="<?php echo esc_attr($background_color); ?>"
                           class="dtol-color-picker" data-default-color="#8b8178">
                    <p class="description">
                        <?php _e('Outer page background color (visible on desktop sides).', 'linkhub'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="LH_tree_background_color"><?php _e('Tree Background', 'linkhub'); ?></label>
                </th>
                <td>
                    <input type="text" id="LH_tree_background_color" name="LH_tree_background_color"
                           value="<?php echo esc_attr($tree_background_color); ?>"
                           class="dtol-color-picker" data-default-color="#f5f5f5">
                    <p class="description">
                        <?php _e('Content area background color (also used for hero fade effect).', 'linkhub'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="LH_background_image"><?php _e('Background Image', 'linkhub'); ?></label>
                </th>
                <td>
                    <div class="dtol-bg-image-upload">
                        <input type="hidden" id="LH_background_image_id" name="LH_background_image_id"
                               value="<?php echo esc_attr($background_image_id); ?>">
                        <div class="dtol-bg-image-preview" style="margin-bottom: 10px;">
                            <?php if ($background_image_id):
                                echo wp_get_attachment_image($background_image_id, 'thumbnail');
                            endif; ?>
                        </div>
                        <button type="button" class="button dtol-upload-bg-image">
                            <?php echo $background_image_id ? __('Change Image', 'linkhub') : __('Upload Image', 'linkhub'); ?>
                        </button>
                        <?php if ($background_image_id): ?>
                            <button type="button" class="button dtol-remove-bg-image">
                                <?php _e('Remove', 'linkhub'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    <p class="description">
                        <?php _e('Optional repeating background pattern/image. Will tile across the page.', 'linkhub'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="LH_hero_shape"><?php _e('Hero Image Shape', 'linkhub'); ?></label>
                </th>
                <td>
                    <select id="LH_hero_shape" name="LH_hero_shape">
                        <option value="round" <?php selected($hero_shape, 'round'); ?>>
                            <?php _e('Round (Circle)', 'linkhub'); ?>
                        </option>
                        <option value="rounded" <?php selected($hero_shape, 'rounded'); ?>>
                            <?php _e('Rounded Corners', 'linkhub'); ?>
                        </option>
                        <option value="square" <?php selected($hero_shape, 'square'); ?>>
                            <?php _e('Square', 'linkhub'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Shape style for the header/profile image.', 'linkhub'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="LH_hero_fade"><?php _e('Hero Fade Effect', 'linkhub'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="LH_hero_fade" name="LH_hero_fade" value="1"
                               <?php checked($hero_fade, '1'); ?>>
                        <?php _e('Enable fade-out overlay effect on hero image', 'linkhub'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Adds a gradient overlay that fades the hero image into the background.', 'linkhub'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="LH_title_color"><?php _e('Title Color', 'linkhub'); ?></label>
                </th>
                <td>
                    <input type="text" id="LH_title_color" name="LH_title_color"
                           value="<?php echo esc_attr($title_color); ?>"
                           class="dtol-color-picker" data-default-color="#1a1a1a">
                    <p class="description">
                        <?php _e('Color for the tree name/title text.', 'linkhub'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="LH_bio_color"><?php _e('Bio Text Color', 'linkhub'); ?></label>
                </th>
                <td>
                    <input type="text" id="LH_bio_color" name="LH_bio_color"
                           value="<?php echo esc_attr($bio_color); ?>"
                           class="dtol-color-picker" data-default-color="#555555">
                    <p class="description">
                        <?php _e('Color for the about/bio text.', 'linkhub'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="LH_social_style"><?php _e('Social Icon Style', 'linkhub'); ?></label>
                </th>
                <td>
                    <select id="LH_social_style" name="LH_social_style">
                        <option value="circle" <?php selected($social_style, 'circle'); ?>>
                            <?php _e('Circle', 'linkhub'); ?>
                        </option>
                        <option value="rounded" <?php selected($social_style, 'rounded'); ?>>
                            <?php _e('Rounded Square', 'linkhub'); ?>
                        </option>
                        <option value="square" <?php selected($social_style, 'square'); ?>>
                            <?php _e('Square', 'linkhub'); ?>
                        </option>
                        <option value="minimal" <?php selected($social_style, 'minimal'); ?>>
                            <?php _e('Minimal (No Background)', 'linkhub'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Visual style for social link icons.', 'linkhub'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="LH_social_color"><?php _e('Social Icon Color', 'linkhub'); ?></label>
                </th>
                <td>
                    <input type="text" id="LH_social_color" name="LH_social_color"
                           value="<?php echo esc_attr($social_color); ?>"
                           class="dtol-color-picker" data-default-color="#333333">
                    <p class="description">
                        <?php _e('Default color for social icons (platform colors used on hover).', 'linkhub'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="LH_heading_size"><?php _e('Section Heading Size', 'linkhub'); ?></label>
                </th>
                <td>
                    <select id="LH_heading_size" name="LH_heading_size">
                        <option value="small" <?php selected($heading_size, 'small'); ?>>
                            <?php _e('Small', 'linkhub'); ?>
                        </option>
                        <option value="medium" <?php selected($heading_size, 'medium'); ?>>
                            <?php _e('Medium (Default)', 'linkhub'); ?>
                        </option>
                        <option value="large" <?php selected($heading_size, 'large'); ?>>
                            <?php _e('Large', 'linkhub'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Size of section heading/divider text in the tree.', 'linkhub'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php // Template for new social link rows - moved outside tables ?>

                    <!-- Template for new social link rows -->
                    <script type="text/template" id="dtol-social-link-template">
                        <div class="dtol-social-link-row" style="margin-bottom: 10px; display: flex; gap: 10px; align-items: center;">
                            <select name="LH_social_platform[]" style="width: 150px;">
                                <?php foreach ($platforms as $key => $platform): ?>
                                    <option value="<?php echo esc_attr($key); ?>">
                                        <?php echo esc_html($platform['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="url" name="LH_social_url[]"
                                   placeholder="<?php _e('URL', 'linkhub'); ?>"
                                   class="regular-text">
                            <button type="button" class="button dtol-remove-social-link">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                    </script>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render link details meta box
     *
     * @param \WP_Post $post Post object
     */
    public function render_link_details_meta_box($post) {
        wp_nonce_field('LH_link_details_nonce', 'LH_link_details_nonce');
        
        $url = get_post_meta($post->ID, LinkPostType::META_URL, true);
        $description = get_post_meta($post->ID, LinkPostType::META_DESCRIPTION, true);
        $icon = get_post_meta($post->ID, LinkPostType::META_ICON, true);
        $image_id = get_post_meta($post->ID, LinkPostType::META_IMAGE, true);
        $display_style = get_post_meta($post->ID, LinkPostType::META_DISPLAY_STYLE, true) ?: 'bar';
        $background_color = get_post_meta($post->ID, LinkPostType::META_BACKGROUND_COLOR, true) ?: '#f8a4c8';
        $text_color = get_post_meta($post->ID, LinkPostType::META_TEXT_COLOR, true) ?: '#000000';
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="LH_url"><?php _e('Destination URL', 'linkhub'); ?></label>
                </th>
                <td>
                    <input type="url" id="LH_url" name="LH_url"
                           value="<?php echo esc_attr($url); ?>"
                           class="large-text">
                    <p class="description">
                        <?php _e('The URL users will be redirected to when they click this link.', 'linkhub'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="LH_description"><?php _e('Description', 'linkhub'); ?></label>
                </th>
                <td>
                    <textarea id="LH_description" name="LH_description"
                              rows="3" class="large-text"><?php echo esc_textarea($description); ?></textarea>
                    <p class="description">
                        <?php _e('Optional description or subtitle displayed below the link title.', 'linkhub'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="LH_display_style"><?php _e('Display Style', 'linkhub'); ?></label>
                </th>
                <td>
                    <select id="LH_display_style" name="LH_display_style">
                        <option value="bar" <?php selected($display_style, 'bar'); ?>>
                            <?php _e('Bar (Button/List Style)', 'linkhub'); ?>
                        </option>
                        <option value="card" <?php selected($display_style, 'card'); ?>>
                            <?php _e('Card (Visual Block Style)', 'linkhub'); ?>
                        </option>
                        <option value="heading" <?php selected($display_style, 'heading'); ?>>
                            <?php _e('Heading (Section Divider)', 'linkhub'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php _e('Choose between a button-style bar, a visual card with image, or a heading/divider.', 'linkhub'); ?>
                    </p>
                </td>
            </tr>
            <tr class="dtol-legacy-style-options dtol-bar-card-options" style="<?php echo $display_style === 'heading' ? 'display:none;' : ''; ?>">
                <th scope="row">
                    <label for="LH_background_color"><?php _e('Background Color', 'linkhub'); ?></label>
                </th>
                <td>
                    <input type="text" id="LH_background_color" name="LH_background_color"
                           value="<?php echo esc_attr($background_color); ?>"
                           class="dtol-color-picker" data-default-color="#f8a4c8">
                    <p class="description">
                        <?php _e('Background color for the link button/card (used with legacy display styles).', 'linkhub'); ?>
                    </p>
                </td>
            </tr>
            <tr class="dtol-legacy-style-options">
                <th scope="row">
                    <label for="LH_text_color"><?php _e('Text Color', 'linkhub'); ?></label>
                </th>
                <td>
                    <input type="text" id="LH_text_color" name="LH_text_color"
                           value="<?php echo esc_attr($text_color); ?>"
                           class="dtol-color-picker" data-default-color="#000000">
                    <p class="description">
                        <?php _e('Text color for the link title (used with legacy display styles).', 'linkhub'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="LH_icon"><?php _e('Icon', 'linkhub'); ?></label>
                </th>
                <td>
                    <input type="text" id="LH_icon" name="LH_icon" 
                           value="<?php echo esc_attr($icon); ?>" 
                           class="regular-text">
                    <p class="description">
                        <?php _e('Icon class (e.g., fa-brands fa-twitter) or emoji.', 'linkhub'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="LH_image"><?php _e('Image', 'linkhub'); ?></label>
                </th>
                <td>
                    <div class="dtol-image-upload">
                        <input type="hidden" id="LH_image_id" name="LH_image_id" 
                               value="<?php echo esc_attr($image_id); ?>">
                        <div class="dtol-image-preview">
                            <?php if ($image_id): 
                                echo wp_get_attachment_image($image_id, 'thumbnail');
                            endif; ?>
                        </div>
                        <button type="button" class="button dtol-upload-image">
                            <?php echo $image_id ? __('Change Image', 'linkhub') : __('Upload Image', 'linkhub'); ?>
                        </button>
                        <?php if ($image_id): ?>
                            <button type="button" class="button dtol-remove-image">
                                <?php _e('Remove', 'linkhub'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    <p class="description">
                        <?php _e('Optional image to display with the link (alternative to icon).', 'linkhub'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render link statistics meta box
     *
     * @param \WP_Post $post Post object
     */
    public function render_link_stats_meta_box($post) {
        $click_count = get_post_meta($post->ID, LinkPostType::META_CLICK_COUNT, true);
        $last_clicked = get_post_meta($post->ID, LinkPostType::META_LAST_CLICKED, true);
        
        ?>
        <div class="dtol-stats">
            <p>
                <strong><?php _e('Total Clicks:', 'linkhub'); ?></strong><br>
                <span style="font-size: 24px;"><?php echo absint($click_count); ?></span>
            </p>
            <?php if ($last_clicked): ?>
                <p>
                    <strong><?php _e('Last Clicked:', 'linkhub'); ?></strong><br>
                    <?php echo esc_html(human_time_diff(strtotime($last_clicked), current_time('timestamp')) . ' ago'); ?>
                </p>
            <?php endif; ?>
            
            <?php if ($post->ID): ?>
                <p>
                    <strong><?php _e('Tracking URL:', 'linkhub'); ?></strong><br>
                    <code style="word-break: break-all;">
                        <?php echo esc_html(\ElyseVIP\LinkHub\Tracking\RedirectHandler::get_tracking_url($post->ID)); ?>
                    </code>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Save tree links
     *
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     */
    public function save_tree_links($post_id, $post) {
        if (!isset($_POST['LH_tree_links_nonce']) || 
            !wp_verify_nonce($_POST['LH_tree_links_nonce'], 'LH_tree_links_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save tree items (links and headings)
        $items = [];
        
        if (isset($_POST['LH_tree_items']['type']) && is_array($_POST['LH_tree_items']['type'])) {
            $types = $_POST['LH_tree_items']['type'];
            $link_ids = isset($_POST['LH_tree_items']['link_id']) ? $_POST['LH_tree_items']['link_id'] : [];
            $texts = isset($_POST['LH_tree_items']['text']) ? $_POST['LH_tree_items']['text'] : [];
            $sizes = isset($_POST['LH_tree_items']['size']) ? $_POST['LH_tree_items']['size'] : [];
            
            $link_index = 0;
            $heading_index = 0;
            
            foreach ($types as $index => $type) {
                if ($type === 'link' && isset($link_ids[$link_index])) {
                    $items[] = [
                        'type' => 'link',
                        'link_id' => absint($link_ids[$link_index])
                    ];
                    $link_index++;
                } elseif ($type === 'heading' && isset($texts[$heading_index])) {
                    $items[] = [
                        'type' => 'heading',
                        'text' => sanitize_text_field($texts[$heading_index]),
                        'size' => in_array($sizes[$heading_index], ['small', 'medium', 'large']) ? $sizes[$heading_index] : 'medium'
                    ];
                    $heading_index++;
                }
            }
        }
        
        update_post_meta($post_id, TreePostType::META_TREE_LINKS, $items);
    }

    /**
     * Save tree settings (header image, about text, social links)
     *
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     */
    public function save_tree_settings($post_id, $post) {
        if (!isset($_POST['LH_tree_settings_nonce']) ||
            !wp_verify_nonce($_POST['LH_tree_settings_nonce'], 'LH_tree_settings_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save header image
        if (isset($_POST['LH_header_image_id'])) {
            update_post_meta($post_id, TreePostType::META_HEADER_IMAGE, absint($_POST['LH_header_image_id']));
        }

        // Save about text
        if (isset($_POST['LH_about_text'])) {
            update_post_meta($post_id, TreePostType::META_ABOUT_TEXT, wp_kses_post($_POST['LH_about_text']));
        }

        // Save social links
        $social_links = [];
        if (isset($_POST['LH_social_platform']) && isset($_POST['LH_social_url'])) {
            $platforms = $_POST['LH_social_platform'];
            $urls = $_POST['LH_social_url'];

            foreach ($platforms as $index => $platform) {
                $url = isset($urls[$index]) ? esc_url_raw($urls[$index]) : '';
                if (!empty($url)) {
                    $social_links[] = [
                        'platform' => sanitize_text_field($platform),
                        'url' => $url,
                    ];
                }
            }
        }
        update_post_meta($post_id, TreePostType::META_SOCIAL_LINKS, $social_links);

        // Save page styling options
        if (isset($_POST['LH_background_color'])) {
            update_post_meta($post_id, TreePostType::META_BACKGROUND_COLOR, sanitize_hex_color($_POST['LH_background_color']));
        }

        if (isset($_POST['LH_tree_background_color'])) {
            update_post_meta($post_id, TreePostType::META_TREE_BACKGROUND_COLOR, sanitize_hex_color($_POST['LH_tree_background_color']));
        }

        if (isset($_POST['LH_background_image_id'])) {
            update_post_meta($post_id, TreePostType::META_BACKGROUND_IMAGE, absint($_POST['LH_background_image_id']));
        }

        if (isset($_POST['LH_hero_shape'])) {
            $valid_shapes = ['round', 'rounded', 'square'];
            $shape = sanitize_text_field($_POST['LH_hero_shape']);
            if (in_array($shape, $valid_shapes)) {
                update_post_meta($post_id, TreePostType::META_HERO_SHAPE, $shape);
            }
        }

        // Hero fade is a checkbox
        $hero_fade = isset($_POST['LH_hero_fade']) ? '1' : '0';
        update_post_meta($post_id, TreePostType::META_HERO_FADE, $hero_fade);

        if (isset($_POST['LH_title_color'])) {
            update_post_meta($post_id, TreePostType::META_TITLE_COLOR, sanitize_hex_color($_POST['LH_title_color']));
        }

        if (isset($_POST['LH_bio_color'])) {
            update_post_meta($post_id, TreePostType::META_BIO_COLOR, sanitize_hex_color($_POST['LH_bio_color']));
        }

        if (isset($_POST['LH_social_style'])) {
            $valid_styles = ['circle', 'rounded', 'square', 'minimal'];
            $style = sanitize_text_field($_POST['LH_social_style']);
            if (in_array($style, $valid_styles)) {
                update_post_meta($post_id, TreePostType::META_SOCIAL_STYLE, $style);
            }
        }

        if (isset($_POST['LH_social_color'])) {
            update_post_meta($post_id, TreePostType::META_SOCIAL_COLOR, sanitize_hex_color($_POST['LH_social_color']));
        }

        if (isset($_POST['LH_heading_size'])) {
            $valid_sizes = ['small', 'medium', 'large'];
            $size = sanitize_text_field($_POST['LH_heading_size']);
            if (in_array($size, $valid_sizes)) {
                update_post_meta($post_id, TreePostType::META_HEADING_SIZE, $size);
            }
        }
    }

    /**
     * Save link meta
     *
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     */
    public function save_link_meta($post_id, $post) {
        if (!isset($_POST['LH_link_details_nonce']) || 
            !wp_verify_nonce($_POST['LH_link_details_nonce'], 'LH_link_details_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save URL
        if (isset($_POST['LH_url'])) {
            update_post_meta($post_id, LinkPostType::META_URL, esc_url_raw($_POST['LH_url']));
            // Invalidate cache when URL changes
            \ElyseVIP\LinkHub\Tracking\RedirectHandler::invalidate_cache($post_id);
        }

        // Save description
        if (isset($_POST['LH_description'])) {
            update_post_meta($post_id, LinkPostType::META_DESCRIPTION, wp_kses_post($_POST['LH_description']));
        }
        
        // Save display style
        if (isset($_POST['LH_display_style'])) {
            update_post_meta($post_id, LinkPostType::META_DISPLAY_STYLE, sanitize_text_field($_POST['LH_display_style']));
        }

        // Save background color
        if (isset($_POST['LH_background_color'])) {
            update_post_meta($post_id, LinkPostType::META_BACKGROUND_COLOR, sanitize_hex_color($_POST['LH_background_color']));
        }

        // Save text color
        if (isset($_POST['LH_text_color'])) {
            update_post_meta($post_id, LinkPostType::META_TEXT_COLOR, sanitize_hex_color($_POST['LH_text_color']));
        }

        // Save icon
        if (isset($_POST['LH_icon'])) {
            update_post_meta($post_id, LinkPostType::META_ICON, sanitize_text_field($_POST['LH_icon']));
        }
        
        // Save image ID
        if (isset($_POST['LH_image_id'])) {
            update_post_meta($post_id, LinkPostType::META_IMAGE, absint($_POST['LH_image_id']));
        }
    }
    
    /**
     * Enqueue admin scripts
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_scripts($hook) {
        if (!in_array($hook, ['post.php', 'post-new.php'])) {
            return;
        }
        
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->post_type, [TreePostType::POST_TYPE, LinkPostType::POST_TYPE])) {
            return;
        }
        
        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-sortable');

        // Enqueue color picker for Link and Tree post types
        if (in_array($screen->post_type, [LinkPostType::POST_TYPE, TreePostType::POST_TYPE])) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        }

        wp_add_inline_script('jquery-ui-sortable', "
            jQuery(document).ready(function($) {
                // Sortable links
                $('#dtol-selected-links').sortable({
                    handle: '.dtol-drag-handle',
                    placeholder: 'dtol-link-placeholder',
                    update: function() {
                        $('.dtol-empty-message').hide();
                    }
                });
                
                // Add link to end
                $('#dtol-add-link-btn').on('click', function() {
                    var linkId = $('#dtol-add-link-select').val();
                    var linkTitle = $('#dtol-add-link-select option:selected').text();
                    
                    if (!linkId) return;
                    
                    // Check if already added
                    if ($('.dtol-link-item[data-link-id=\"' + linkId + '\"]').length) {
                        alert('This link is already added.');
                        return;
                    }
                    
                    addLinkItem(linkId, linkTitle);
                    $('#dtol-add-link-select').val('');
                });
                
                // Add heading
                $('#dtol-add-heading-btn').on('click', function() {
                    var headingText = prompt('Enter heading text:');
                    if (!headingText) return;
                    
                    var headingSize = prompt('Enter heading size (small, medium, or large):', 'medium');
                    if (!headingSize || !['small', 'medium', 'large'].includes(headingSize)) {
                        headingSize = 'medium';
                    }
                    
                    addHeadingItem(headingText, headingSize);
                });
                
                // Edit heading
                $(document).on('click', '.dtol-edit-heading', function() {
                    var item = $(this).closest('.dtol-link-item');
                    var currentText = item.find('.dtol-heading-text').val();
                    var currentSize = item.find('.dtol-heading-size').val();
                    
                    var newText = prompt('Edit heading text:', currentText);
                    if (newText === null) return;
                    
                    var newSize = prompt('Edit heading size (small, medium, or large):', currentSize);
                    if (!newSize || !['small', 'medium', 'large'].includes(newSize)) {
                        newSize = currentSize;
                    }
                    
                    item.find('.dtol-link-info strong').html('Heading: ' + $('<div>').text(newText).html());
                    item.find('.dtol-link-meta').text('Size: ' + newSize);
                    item.find('.dtol-heading-text').val(newText);
                    item.find('.dtol-heading-size').val(newSize);
                });
                
                // Insert here button
                $(document).on('click', '.dtol-insert-here', function() {
                    var targetItem = $(this).closest('.dtol-link-item');
                    var choice = prompt('Insert: 1 = Link, 2 = Heading', '1');
                    
                    if (choice === '1') {
                        var linkId = prompt('Enter Link ID (or use the dropdown above)');
                        if (!linkId) return;
                        
                        // Check if already added
                        if ($('.dtol-link-item[data-link-id=\"' + linkId + '\"]').length) {
                            alert('This link is already added.');
                            return;
                        }
                        
                        addLinkItemBefore(linkId, 'Link ' + linkId, targetItem);
                    } else if (choice === '2') {
                        var headingText = prompt('Enter heading text:');
                        if (!headingText) return;
                        
                        var headingSize = prompt('Enter heading size (small, medium, or large):', 'medium');
                        if (!headingSize || !['small', 'medium', 'large'].includes(headingSize)) {
                            headingSize = 'medium';
                        }
                        
                        addHeadingItemBefore(headingText, headingSize, targetItem);
                    }
                });
                
                // Remove link/heading
                $(document).on('click', '.dtol-remove-link', function() {
                    $(this).closest('.dtol-link-item').remove();
                    if ($('#dtol-selected-links .dtol-link-item').length === 0) {
                        $('.dtol-empty-message').show();
                    }
                });
                
                // Helper function to add link item
                function addLinkItem(linkId, linkTitle) {
                    var item = $('<li class=\"dtol-link-item\" data-link-id=\"' + linkId + '\" data-type=\"link\">' +
                        '<span class=\"dtol-drag-handle dashicons dashicons-menu\"></span>' +
                        '<span class=\"dtol-link-thumbnail-placeholder dashicons dashicons-admin-links\"></span>' +
                        '<div class=\"dtol-link-info\">' +
                            '<strong>' + linkTitle + '</strong>' +
                        '</div>' +
                        '<div class=\"dtol-link-actions\">' +
                            '<button type=\"button\" class=\"button button-small dtol-remove-link\">Remove</button>' +
                        '</div>' +
                        '<button type=\"button\" class=\"dtol-insert-here button button-small\" title=\"Insert item here\">' +
                            '<span class=\"dashicons dashicons-plus-alt\"></span>' +
                        '</button>' +
                        '<input type=\"hidden\" class=\"dtol-item-type\" name=\"LH_tree_items[type][]\" value=\"link\">' +
                        '<input type=\"hidden\" name=\"LH_tree_items[link_id][]\" value=\"' + linkId + '\">' +
                    '</li>');
                    
                    $('#dtol-selected-links').append(item);
                    $('.dtol-empty-message').hide();
                }
                
                function addLinkItemBefore(linkId, linkTitle, targetItem) {
                    var item = $('<li class=\"dtol-link-item\" data-link-id=\"' + linkId + '\" data-type=\"link\">' +
                        '<span class=\"dtol-drag-handle dashicons dashicons-menu\"></span>' +
                        '<span class=\"dtol-link-thumbnail-placeholder dashicons dashicons-admin-links\"></span>' +
                        '<div class=\"dtol-link-info\">' +
                            '<strong>' + linkTitle + '</strong>' +
                        '</div>' +
                        '<div class=\"dtol-link-actions\">' +
                            '<button type=\"button\" class=\"button button-small dtol-remove-link\">Remove</button>' +
                        '</div>' +
                        '<button type=\"button\" class=\"dtol-insert-here button button-small\" title=\"Insert item here\">' +
                            '<span class=\"dashicons dashicons-plus-alt\"></span>' +
                        '</button>' +
                        '<input type=\"hidden\" class=\"dtol-item-type\" name=\"LH_tree_items[type][]\" value=\"link\">' +
                        '<input type=\"hidden\" name=\"LH_tree_items[link_id][]\" value=\"' + linkId + '\">' +
                    '</li>');
                    
                    targetItem.before(item);
                    $('.dtol-empty-message').hide();
                }
                
                // Helper function to add heading item
                function addHeadingItem(text, size) {
                    var item = $('<li class=\"dtol-link-item dtol-heading-item\" data-type=\"heading\">' +
                        '<span class=\"dtol-drag-handle dashicons dashicons-menu\"></span>' +
                        '<div class=\"dtol-link-info\">' +
                            '<span class=\"dashicons dashicons-editor-textcolor\" style=\"color: #999;\"></span>' +
                            '<strong>Heading: ' + $('<div>').text(text).html() + '</strong>' +
                            '<span class=\"dtol-link-meta\">Size: ' + size + '</span>' +
                        '</div>' +
                        '<div class=\"dtol-link-actions\">' +
                            '<button type=\"button\" class=\"button button-small dtol-edit-heading\">Edit</button>' +
                            '<button type=\"button\" class=\"button button-small dtol-remove-link\">Remove</button>' +
                        '</div>' +
                        '<button type=\"button\" class=\"dtol-insert-here button button-small\" title=\"Insert item here\">' +
                            '<span class=\"dashicons dashicons-plus-alt\"></span>' +
                        '</button>' +
                        '<input type=\"hidden\" class=\"dtol-item-type\" name=\"LH_tree_items[type][]\" value=\"heading\">' +
                        '<input type=\"hidden\" class=\"dtol-heading-text\" name=\"LH_tree_items[text][]\" value=\"' + $('<div>').text(text).html() + '\">' +
                        '<input type=\"hidden\" class=\"dtol-heading-size\" name=\"LH_tree_items[size][]\" value=\"' + size + '\">' +
                    '</li>');
                    
                    $('#dtol-selected-links').append(item);
                    $('.dtol-empty-message').hide();
                }
                
                function addHeadingItemBefore(text, size, targetItem) {
                    var item = $('<li class=\"dtol-link-item dtol-heading-item\" data-type=\"heading\">' +
                        '<span class=\"dtol-drag-handle dashicons dashicons-menu\"></span>' +
                        '<div class=\"dtol-link-info\">' +
                            '<span class=\"dashicons dashicons-editor-textcolor\" style=\"color: #999;\"></span>' +
                            '<strong>Heading: ' + $('<div>').text(text).html() + '</strong>' +
                            '<span class=\"dtol-link-meta\">Size: ' + size + '</span>' +
                        '</div>' +
                        '<div class=\"dtol-link-actions\">' +
                            '<button type=\"button\" class=\"button button-small dtol-edit-heading\">Edit</button>' +
                            '<button type=\"button\" class=\"button button-small dtol-remove-link\">Remove</button>' +
                        '</div>' +
                        '<button type=\"button\" class=\"dtol-insert-here button button-small\" title=\"Insert item here\">' +
                            '<span class=\"dashicons dashicons-plus-alt\"></span>' +
                        '</button>' +
                        '<input type=\"hidden\" class=\"dtol-item-type\" name=\"LH_tree_items[type][]\" value=\"heading\">' +
                        '<input type=\"hidden\" class=\"dtol-heading-text\" name=\"LH_tree_items[text][]\" value=\"' + $('<div>').text(text).html() + '\">' +
                        '<input type=\"hidden\" class=\"dtol-heading-size\" name=\"LH_tree_items[size][]\" value=\"' + size + '\">' +
                    '</li>');
                    
                    targetItem.before(item);
                    $('.dtol-empty-message').hide();
                }
                
                // Image upload
                var mediaFrame;
                $('.lh-upload-image').on('click', function(e) {
                    e.preventDefault();
                    
                    if (mediaFrame) {
                        mediaFrame.open();
                        return;
                    }
                    
                    mediaFrame = wp.media({
                        title: 'Select Link Image',
                        button: { text: 'Use this image' },
                        multiple: false
                    });
                    
                    mediaFrame.on('select', function() {
                        var attachment = mediaFrame.state().get('selection').first().toJSON();
                        $('#LH_image_id').val(attachment.id);
                        $('.lh-image-preview').html('<img src=\"' + attachment.url + '\" style=\"max-width: 150px;\">');
                    });
                    
                    mediaFrame.open();
                });
                
                // Remove image
                $('.lh-remove-image').on('click', function(e) {
                    e.preventDefault();
                    $('#LH_image_id').val('');
                    $('.lh-image-preview').empty();
                    $(this).hide();
                });

                // Header image upload (for Tree post type)
                var headerMediaFrame;
                $('.lh-upload-header-image').on('click', function(e) {
                    e.preventDefault();

                    if (headerMediaFrame) {
                        headerMediaFrame.open();
                        return;
                    }

                    headerMediaFrame = wp.media({
                        title: 'Select Header Image',
                        button: { text: 'Use this image' },
                        multiple: false
                    });

                    headerMediaFrame.on('select', function() {
                        var attachment = headerMediaFrame.state().get('selection').first().toJSON();
                        $('#LH_header_image_id').val(attachment.id);
                        var thumbUrl = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                        $('.lh-header-image-preview').html('<img src=\"' + thumbUrl + '\" style=\"max-width: 300px;\">');
                        $('.lh-remove-header-image').show();
                    });

                    headerMediaFrame.open();
                });

                // Remove header image
                $('.lh-remove-header-image').on('click', function(e) {
                    e.preventDefault();
                    $('#LH_header_image_id').val('');
                    $('.lh-header-image-preview').empty();
                    $(this).hide();
                });

                // Background image upload (for Tree post type)
                var bgMediaFrame;
                $('.lh-upload-bg-image').on('click', function(e) {
                    e.preventDefault();

                    if (bgMediaFrame) {
                        bgMediaFrame.open();
                        return;
                    }

                    bgMediaFrame = wp.media({
                        title: 'Select Background Image',
                        button: { text: 'Use this image' },
                        multiple: false
                    });

                    bgMediaFrame.on('select', function() {
                        var attachment = bgMediaFrame.state().get('selection').first().toJSON();
                        $('#LH_background_image_id').val(attachment.id);
                        var thumbUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                        $('.lh-bg-image-preview').html('<img src=\"' + thumbUrl + '\" style=\"max-width: 150px;\">');
                        $('.lh-remove-bg-image').show();
                    });

                    bgMediaFrame.open();
                });

                // Remove background image
                $('.lh-remove-bg-image').on('click', function(e) {
                    e.preventDefault();
                    $('#LH_background_image_id').val('');
                    $('.lh-bg-image-preview').empty();
                    $(this).hide();
                });

                // Add social link
                $('.lh-add-social-link').on('click', function(e) {
                    e.preventDefault();
                    var template = $('#dtol-social-link-template').html();
                    $('#dtol-social-links-container').append(template);
                });

                // Remove social link
                $(document).on('click', '.lh-remove-social-link', function(e) {
                    e.preventDefault();
                    $(this).closest('.lh-social-link-row').remove();
                });

                // Initialize color pickers
                if ($.fn.wpColorPicker) {
                    $('.lh-color-picker').wpColorPicker();
                }

                // Show/hide bar/card specific options (hide for headings which don't need background color)
                function toggleBarCardOptions() {
                    var displayStyle = $('#LH_display_style').val();
                    if (displayStyle === 'heading') {
                        $('.lh-bar-card-options').hide();
                    } else {
                        $('.lh-bar-card-options').show();
                    }
                }

                toggleBarCardOptions();
                $('#LH_display_style').on('change', toggleBarCardOptions);
            });
        ");
    }
    
    /**
     * Get tree links
     *
     * @param int $tree_id Tree post ID
     * @return array Array of link post IDs
     */
    public static function get_tree_links($tree_id) {
        $link_ids = get_post_meta($tree_id, self::META_TREE_LINKS, true);
        return is_array($link_ids) ? $link_ids : [];
    }
}
