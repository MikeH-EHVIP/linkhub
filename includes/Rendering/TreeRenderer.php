<?php
/**
 * Tree Renderer
 *
 * @package LinkHub
 */

namespace LinkHub\Rendering;

use LinkHub\PostTypes\TreePostType;

/**
 * Tree Renderer Class
 *
 * Handles rendering of Link Trees on the frontend
 */
class TreeRenderer {

    /**
     * Initialize renderer
     */
    public static function init() {
        add_filter('the_content', [self::class, 'render_tree_content'], 20);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_frontend_styles']);

        // Hide admin bar in preview mode (Tree Builder preview)
        add_filter('show_admin_bar', [self::class, 'maybe_hide_admin_bar']);
    }

    /**
     * Hide admin bar when in preview mode (Tree Builder preview)
     */
    public static function maybe_hide_admin_bar($show) {
        if (isset($_GET['lh_preview']) && $_GET['lh_preview'] === '1') {
            return false;
        }
        return $show;
    }

    /**
     * Enqueue frontend styles for tree pages
     */
    public static function enqueue_frontend_styles() {
        if (is_singular(TreePostType::POST_TYPE)) {
            wp_enqueue_style(
                'lh-frontend',
                LH_PLUGIN_URL . 'assets/css/modules.css',
                [],
                LH_VERSION
            );
        }
    }

    /**
     * Render tree content when viewing a tree post
     *
     * @param string $content Post content
     * @return string Modified content
     */
    public static function render_tree_content($content) {
        // Only modify content for tree posts on singular views
        if (!is_singular(TreePostType::POST_TYPE)) {
            return $content;
        }

        global $post;
        if (!$post || $post->post_type !== TreePostType::POST_TYPE) {
            return $content;
        }

        // Get tree settings
        $header_image_id = get_post_meta($post->ID, TreePostType::META_HEADER_IMAGE, true);
        $header_image_url = get_post_meta($post->ID, TreePostType::META_HEADER_IMAGE . '_url', true);
        $about_text = get_post_meta($post->ID, TreePostType::META_ABOUT_TEXT, true);
        $social_links = get_post_meta($post->ID, TreePostType::META_SOCIAL_LINKS, true);

        // Get styling options
        $background_color = get_post_meta($post->ID, TreePostType::META_BACKGROUND_COLOR, true) ?: '#8b8178';
        $tree_background_color = get_post_meta($post->ID, TreePostType::META_TREE_BACKGROUND_COLOR, true) ?: '#f5f5f5';
        $background_image_id = get_post_meta($post->ID, TreePostType::META_BACKGROUND_IMAGE, true);
        $background_image_url = get_post_meta($post->ID, TreePostType::META_BACKGROUND_IMAGE . '_url', true);
        $hero_shape = get_post_meta($post->ID, TreePostType::META_HERO_SHAPE, true) ?: 'round';
        $hero_fade = get_post_meta($post->ID, TreePostType::META_HERO_FADE, true);
        $title_color = get_post_meta($post->ID, TreePostType::META_TITLE_COLOR, true) ?: '#1a1a1a';
        $bio_color = get_post_meta($post->ID, TreePostType::META_BIO_COLOR, true) ?: '#555555';
        $social_style = get_post_meta($post->ID, TreePostType::META_SOCIAL_STYLE, true) ?: 'circle';
        $social_color = get_post_meta($post->ID, TreePostType::META_SOCIAL_COLOR, true) ?: '#333333';
        $link_background_color = get_post_meta($post->ID, TreePostType::META_LINK_BACKGROUND_COLOR, true) ?: '#eeeeee';
        $link_text_color = get_post_meta($post->ID, TreePostType::META_LINK_TEXT_COLOR, true) ?: '#000000';
        $title_font = get_post_meta($post->ID, TreePostType::META_TITLE_FONT, true) ?: 'system';
        $body_font = get_post_meta($post->ID, TreePostType::META_BODY_FONT, true) ?: 'system';
        $heading_size = get_post_meta($post->ID, TreePostType::META_HEADING_SIZE, true) ?: 'medium';

        // Get the tree's links
        $tree_links = get_post_meta($post->ID, TreePostType::META_TREE_LINKS, true);

        // Build inline styles for page
        $page_styles = self::build_page_styles($background_color, $tree_background_color, $background_image_id, $title_color, $bio_color, $social_color, $link_background_color, $link_text_color, $title_font, $body_font);

        // Start output with inline styles
        $output = $page_styles;
        $output .= '<div class="lh-tree lh-hero-' . esc_attr($hero_shape) . ' lh-social-' . esc_attr($social_style) . '">';

        // Render header section (image + title + about)
        $output .= self::render_tree_header($post, $header_image_id, $header_image_url, $about_text, $hero_fade);

        // Render social links bar
        if (!empty($social_links) && is_array($social_links)) {
            $output .= self::render_social_links($social_links);
        }

        // Check if there are links to display
        if (!is_array($tree_links) || empty($tree_links)) {
            $output .= '<div class="lh-tree-links lh-tree-empty">';
            $output .= '<p>' . __('No links have been added to this tree yet.', 'linkhub') . '</p>';
            $output .= '</div>';
            $output .= '</div>';
            return $output;
        }

        // Render links section
        $output .= '<div class="lh-tree-links">';

        foreach ($tree_links as $tree_link) {
            // Check if it's a heading
            if (is_array($tree_link) && isset($tree_link['type']) && $tree_link['type'] === 'heading') {
                $heading_text = isset($tree_link['text']) ? $tree_link['text'] : '';
                $heading_size = isset($tree_link['size']) ? $tree_link['size'] : 'medium';
                
                $size_classes = [
                    'small' => 'lh-heading-small',
                    'medium' => 'lh-heading-medium',
                    'large' => 'lh-heading-large'
                ];
                $size_class = isset($size_classes[$heading_size]) ? $size_classes[$heading_size] : $size_classes['medium'];
                
                $output .= sprintf(
                    '<div class="lh-heading %s">%s</div>',
                    esc_attr($size_class),
                    esc_html($heading_text)
                );
                continue;
            }
            
            // Handle links - both old format (just ID) and new format (array with link_id)
            if (is_array($tree_link)) {
                $link_id = $tree_link['link_id'] ?? 0;
                $design_override = $tree_link['design_id'] ?? 0;
            } else {
                $link_id = $tree_link;
                $design_override = 0;
            }

            if (!$link_id) {
                continue;
            }

            // Render this link using the LinkTypeRenderer
            $output .= LinkTypeRenderer::render($link_id, $design_override, $heading_size);
        }

        $output .= '</div>'; // Close lh-tree-links
        $output .= '</div>'; // Close lh-tree

        return $output;
    }

    /**
     * Build inline styles for page customization
     *
     * @param string $background_color Page background color (outer area)
     * @param string $tree_background_color Tree/content area background color
     * @param int $background_image_id Background image attachment ID
     * @param string $title_color Title text color
     * @param string $bio_color Bio text color
     * @param string $social_color Social icon color
     * @param string $link_bg_color Link button background color
     * @param string $link_text_color Link button text color
     * @param string $title_font Title font family
     * @param string $body_font Body/content font family
     * @return string Style tag with CSS
     */
    private static function build_page_styles($background_color, $tree_background_color, $background_image_id, $title_color, $bio_color, $social_color, $link_bg_color, $link_text_color, $title_font, $body_font) {
        // Enqueue Google Fonts if needed
        $google_fonts = [];
        if (in_array($title_font, ['poppins', 'montserrat', 'playfair', 'raleway'])) {
            $google_fonts[] = $title_font;
        }
        if (in_array($body_font, ['opensans', 'roboto', 'lato', 'merriweather'])) {
            $google_fonts[] = $body_font;
        }
        
        if (!empty($google_fonts)) {
            $font_map = [
                'poppins' => 'Poppins:400,600,700',
                'montserrat' => 'Montserrat:400,600,700',
                'playfair' => 'Playfair+Display:400,600,700',
                'raleway' => 'Raleway:400,600,700',
                'opensans' => 'Open+Sans:400,600,700',
                'roboto' => 'Roboto:400,500,700',
                'lato' => 'Lato:400,700',
                'merriweather' => 'Merriweather:400,700'
            ];
            
            $fonts_query = [];
            foreach ($google_fonts as $font) {
                if (isset($font_map[$font])) {
                    $fonts_query[] = $font_map[$font];
                }
            }
            
            if (!empty($fonts_query)) {
                $google_fonts_url = 'https://fonts.googleapis.com/css2?family=' . implode('&family=', $fonts_query) . '&display=swap';
                wp_enqueue_style('lh-google-fonts', $google_fonts_url, [], null);
            }
        }
        
        // Get font stacks
        $title_font_stack = self::get_font_stack($title_font);
        $body_font_stack = self::get_font_stack($body_font);
        
        $styles = '<style>';

        // CSS variable for tree background color (used by fade effect)
        $styles .= ':root { --lh-bg-color: ' . esc_attr($tree_background_color) . '; }';

        // Page background (outer area - visible on desktop sides)
        $styles .= 'body.single-lh_tree { background-color: ' . esc_attr($background_color) . ' !important;';
        if ($background_image_id) {
            $bg_url = wp_get_attachment_url($background_image_id);
            if ($bg_url) {
                $styles .= ' background-image: url(' . esc_url($bg_url) . '); background-repeat: repeat;';
            }
        }
        $styles .= ' }';

        // Tree/content area background
        $styles .= '.lh-tree { background-color: ' . esc_attr($tree_background_color) . '; }';
        
        // Avatar container background (to match tree for rounded corners)
        $styles .= '.lh-tree-avatar { background-color: ' . esc_attr($tree_background_color) . ' !important; }';

        // Title color and font
        $styles .= '.lh-tree-title { color: ' . esc_attr($title_color) . ' !important; font-family: ' . $title_font_stack . '; }';

        // Bio text color and font
        $styles .= '.lh-tree-about, .lh-tree-about p { color: ' . esc_attr($bio_color) . ' !important; font-family: ' . $body_font_stack . '; }';

        // Social icon color
        $styles .= '.lh-social-item a { color: ' . esc_attr($social_color) . ' !important; }';

        // Link button colors and font (bar style)
        $styles .= '.lh-bar-link { background-color: ' . esc_attr($link_bg_color) . ' !important; color: ' . esc_attr($link_text_color) . ' !important; font-family: ' . $body_font_stack . '; border-color: ' . esc_attr($link_text_color) . ' !important; }';
        
        // Link button hover effect - darken background by 10%
        $styles .= '.lh-bar-link:hover { filter: brightness(0.9); }';
        
        // Link button colors and font (card style)
        $styles .= '.lh-card-banner { background-color: ' . esc_attr($link_bg_color) . ' !important; color: ' . esc_attr($link_text_color) . ' !important; font-family: ' . $body_font_stack . '; }';
        $styles .= '.lh-card-link { border-color: ' . esc_attr($link_text_color) . ' !important; }';

        $styles .= '</style>';

        return $styles;
    }

    /**
     * Get font stack for a given font choice
     *
     * @param string $font Font choice
     * @return string CSS font-family value
     */
    private static function get_font_stack($font) {
        $stacks = [
            'system' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            'serif' => 'Georgia, "Times New Roman", Times, serif',
            'sans' => 'Arial, Helvetica, sans-serif',
            'mono' => '"Courier New", Courier, monospace',
            'poppins' => '"Poppins", -apple-system, BlinkMacSystemFont, sans-serif',
            'montserrat' => '"Montserrat", -apple-system, BlinkMacSystemFont, sans-serif',
            'playfair' => '"Playfair Display", Georgia, serif',
            'raleway' => '"Raleway", -apple-system, BlinkMacSystemFont, sans-serif',
            'opensans' => '"Open Sans", -apple-system, BlinkMacSystemFont, sans-serif',
            'roboto' => '"Roboto", -apple-system, BlinkMacSystemFont, sans-serif',
            'lato' => '"Lato", -apple-system, BlinkMacSystemFont, sans-serif',
            'merriweather' => '"Merriweather", Georgia, serif'
        ];
        
        return isset($stacks[$font]) ? $stacks[$font] : $stacks['system'];
    }

    /**
     * Render tree header (profile image, title, about text)
     *
     * @param \WP_Post $post Tree post object
     * @param int $header_image_id Header image attachment ID
     * @param string $about_text About/bio text
     * @param string $hero_fade Whether to show fade effect
     * @return string HTML output
     */
    private static function render_tree_header($post, $header_image_id, $header_image_url, $about_text, $hero_fade = '0') {
        $output = '<div class="lh-tree-header">';

        // Header/profile image
        if ($header_image_id || $header_image_url) {
            $fade_class = ($hero_fade === '1') ? ' lh-avatar-fade' : '';
            $output .= '<div class="lh-tree-avatar' . $fade_class . '">';
            
            if ($header_image_id) {
                // Remove width/height attributes for responsive images
                add_filter('wp_get_attachment_image_attributes', function($attr) {
                    unset($attr['width']);
                    unset($attr['height']);
                    return $attr;
                }, 10);
                
                $output .= wp_get_attachment_image($header_image_id, 'large', false, [
                    'class' => 'lh-avatar-image',
                    'alt' => esc_attr($post->post_title),
                ]);
                
                // Remove the filter after use
                remove_all_filters('wp_get_attachment_image_attributes', 10);
            } elseif ($header_image_url) {
                $output .= '<img src="' . esc_url($header_image_url) . '" class="lh-avatar-image" alt="' . esc_attr($post->post_title) . '">';
            }
            
            $output .= '</div>';
        }

        // Tree title
        $output .= '<h1 class="lh-tree-title">' . esc_html($post->post_title) . '</h1>';

        // About text
        if (!empty($about_text)) {
            $output .= '<div class="lh-tree-about">';
            $output .= wp_kses_post(wpautop($about_text));
            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Render social links bar
     *
     * @param array $social_links Array of social link data
     * @return string HTML output
     */
    private static function render_social_links($social_links) {
        if (empty($social_links)) {
            return '';
        }

        // Platform icons mapping (using dashicons or simple text fallbacks)
        $platform_icons = [
            'twitter' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
            'facebook' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
            'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>',
            'linkedin' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
            'youtube' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
            'tiktok' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>',
            'pinterest' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.372 0 12c0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.937 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738.098.119.112.224.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.631-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12 24c6.627 0 12-5.373 12-12 0-6.628-5.373-12-12-12z"/></svg>',
            'github' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>',
            'email' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M0 3v18h24v-18h-24zm6.623 7.929l-4.623 5.712v-9.458l4.623 3.746zm-4.141-5.929h19.035l-9.517 7.713-9.518-7.713zm5.694 7.188l3.824 3.099 3.83-3.104 5.612 6.817h-18.779l5.513-6.812zm9.208-1.264l4.616-3.741v9.348l-4.616-5.607z"/></svg>',
            'website' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm1 16.057v-3.057h2.994c-.059 1.143-.212 2.24-.456 3.279-.823-.12-1.674-.188-2.538-.222zm1.957 2.162c-.499 1.33-1.159 2.497-1.957 3.456v-3.62c.666.028 1.319.081 1.957.164zm-1.957-7.219v-3.015c.868-.034 1.721-.103 2.548-.224.238 1.027.389 2.111.446 3.239h-2.994zm0-5.014v-3.661c.806.969 1.471 2.15 1.971 3.496-.642.084-1.3.137-1.971.165zm2.703-3.267c1.237.496 2.354 1.228 3.29 2.146-.642.234-1.311.442-2.019.607-.344-.992-.775-1.91-1.271-2.753zm-7.241 13.56c-.244-1.039-.398-2.136-.456-3.279h2.994v3.057c-.865.034-1.714.102-2.538.222zm2.538 1.776v3.62c-.798-.959-1.458-2.126-1.957-3.456.638-.083 1.291-.136 1.957-.164zm-2.994-7.055c.057-1.128.207-2.212.446-3.239.827.121 1.68.19 2.548.224v3.015h-2.994zm1.024-5.179c.5-1.346 1.165-2.527 1.97-3.496v3.661c-.671-.028-1.329-.081-1.97-.165zm-2.005-.35c-.708-.165-1.377-.373-2.018-.607.937-.918 2.053-1.65 3.29-2.146-.496.844-.927 1.762-1.272 2.753zm-.549 1.918c-.264 1.151-.434 2.36-.492 3.611h-3.933c.165-1.658.739-3.197 1.617-4.518.88.361 1.816.67 2.808.907zm.009 9.262c-.988.236-1.92.542-2.797.9-.89-1.328-1.471-2.879-1.637-4.551h3.934c.058 1.265.231 2.488.5 3.651zm.553 1.917c.342.976.768 1.881 1.257 2.712-1.223-.49-2.326-1.211-3.256-2.115.636-.229 1.299-.435 1.999-.597zm9.924 0c.7.163 1.362.367 1.999.597-.931.903-2.034 1.625-3.257 2.116.489-.832.915-1.737 1.258-2.713zm.553-1.917c.27-1.163.442-2.386.501-3.651h3.934c-.167 1.672-.748 3.223-1.638 4.551-.877-.358-1.81-.664-2.797-.9zm.501-5.651c-.058-1.251-.229-2.46-.492-3.611.992-.237 1.929-.546 2.809-.907.877 1.321 1.451 2.86 1.616 4.518h-3.933z"/></svg>',
        ];

        $output = '<div class="lh-social-links">';
        $output .= '<ul class="lh-social-list">';

        foreach ($social_links as $social) {
            $platform = $social['platform'] ?? '';
            $url = $social['url'] ?? '';

            if (empty($url)) {
                continue;
            }

            $icon = isset($platform_icons[$platform]) ? $platform_icons[$platform] : $platform_icons['website'];
            $label = ucfirst($platform);

            $output .= sprintf(
                '<li class="lh-social-item lh-social-%s"><a href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s">%s</a></li>',
                esc_attr($platform),
                esc_url($url),
                esc_attr($label),
                $icon
            );
        }

        $output .= '</ul>';
        $output .= '</div>';

        return $output;
    }
}

