<?php
/**
 * LinkHub - Module Server Renderers
 *
 * @package LinkHub
 */

namespace LinkHub\Modules;

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Module;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;
use LinkHub\PostTypes\LinkPostType;
use LinkHub\Tracking\RedirectHandler;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Divi 5 Modules for Tree of Links
 */
class TreeOfLinksModules implements DependencyInterface {
    
    /**
     * Load and register all modules
     */
    public function load() {
        add_action('init', [$this, 'register_modules']);
    }
    
    /**
     * Register all Tree of Links modules
     */
    public function register_modules() {
        $log_file = LH_PLUGIN_DIR . 'lh-debug.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($log_file, "[{$timestamp}] register_modules() called\n", FILE_APPEND);
        
        $module_base_path = LH_PLUGIN_DIR . 'visual-builder/src';
        file_put_contents($log_file, "[{$timestamp}] module_base_path = {$module_base_path}\n", FILE_APPEND);
        
        // Register each module (design-only modules for Link Type Design templates)
        $modules = [
            'link-title' => [self::class, 'render_link_title'],
            'link-description' => [self::class, 'render_link_description'],
            'link-image' => [self::class, 'render_link_image'],
            'link-icon' => [self::class, 'render_link_icon'],
        ];
        
        foreach ($modules as $module_dir => $callback) {
            $path = $module_base_path . '/' . $module_dir;
            file_put_contents($log_file, "[{$timestamp}] Registering module: {$path}\n", FILE_APPEND);
            
            ModuleRegistration::register_module(
                $path,
                ['render_callback' => $callback]
            );
            
            file_put_contents($log_file, "[{$timestamp}] Module registered: {$module_dir}\n", FILE_APPEND);
        }
        
        file_put_contents($log_file, "[{$timestamp}] All modules registration complete\n", FILE_APPEND);
    }
    
    /**
     * Get current link ID from global context
     */
    private static function get_link_id() {
        return $GLOBALS['LH_current_link_id'] ?? 0;
    }
    
    /**
     * Get link data
     */
    private static function get_link_data($link_id) {
        if (!$link_id) {
            return null;
        }

        $link = get_post($link_id);
        if (!$link) {
            return null;
        }

        // Get icon value - stored as single META_ICON field
        $icon = get_post_meta($link_id, LinkPostType::META_ICON, true);

        return [
            'post' => $link,
            'url' => get_post_meta($link_id, LinkPostType::META_URL, true),
            'description' => get_post_meta($link_id, LinkPostType::META_DESCRIPTION, true),
            'icon' => $icon,
            'image_id' => get_post_meta($link_id, LinkPostType::META_IMAGE, true),
            'click_count' => get_post_meta($link_id, LinkPostType::META_CLICK_COUNT, true),
            'display_style' => get_post_meta($link_id, LinkPostType::META_DISPLAY_STYLE, true),
            'tracking_url' => RedirectHandler::get_tracking_url($link_id),
        ];
    }
    
    /**
     * Render module styles
     */
    private static function render_module_styles($args) {
        $attrs = $args['attrs'] ?? [];
        $elements = $args['elements'];
        
        Style::add([
            'id' => $args['id'],
            'name' => $args['name'],
            'orderIndex' => $args['orderIndex'],
            'storeInstance' => $args['storeInstance'],
            'styles' => [
                $elements->style([
                    'attrName' => 'module',
                    'styleProps' => [
                        'disabledOn' => [
                            'disabledModuleVisibility' => $args['settings']['disabledModuleVisibility'] ?? null,
                        ],
                    ],
                ]),
            ],
        ]);
    }
    
    /**
     * Render module script data
     */
    private static function render_script_data($args) {
        $args['elements']->script_data(['attrName' => 'module']);
    }
    
    /**
     * Render module classnames
     */
    private static function render_classnames($args, $additional_classes = []) {
        $classnames_instance = $args['classnamesInstance'];
        $attrs = $args['attrs'];
        
        $classnames_instance->add(
            ElementClassnames::classnames([
                'attrs' => $attrs['module']['decoration'] ?? [],
            ])
        );
        
        foreach ($additional_classes as $class) {
            $classnames_instance->add($class);
        }
    }
    
    // ===== LINK TITLE =====

    public static function render_link_title($attrs, $content, $block, $elements) {
        $link_id = self::get_link_id();
        $data = self::get_link_data($link_id);
        $html_tag = $attrs['htmlTag'] ?? 'h2';

        $title = $data ? esc_html($data['post']->post_title) : 'Link Title';

        $title_html = HTMLUtility::render([
            'tag' => $html_tag,
            'attributes' => ['class' => 'lh-link-title'],
            'childrenSanitizer' => 'esc_html',
            'children' => $title,
        ]);

        $module_inner = HTMLUtility::render([
            'tag' => 'div',
            'attributes' => ['class' => 'et_pb_module_inner'],
            'childrenSanitizer' => 'et_core_esc_previously',
            'children' => $title_html,
        ]);

        $module_elements = $elements->style_components(['attrName' => 'module']);

        return Module::render([
            'orderIndex' => $block->parsed_block['orderIndex'],
            'storeInstance' => $block->parsed_block['storeInstance'],
            'attrs' => $attrs,
            'elements' => $elements,
            'id' => $block->parsed_block['id'],
            'moduleClassName' => 'LH_link_title',
            'name' => $block->block_type->name,
            'classnamesFunction' => function($args) {
                self::render_classnames($args);
            },
            'moduleCategory' => $block->block_type->category,
            'stylesComponent' => function($args) {
                self::render_link_title_styles($args);
            },
            'scriptDataComponent' => function($args) {
                self::render_script_data($args);
            },
            'children' => $module_elements . $module_inner,
        ]);
    }

    /**
     * Render Link Title styles (module + title element)
     */
    private static function render_link_title_styles($args) {
        $elements = $args['elements'];

        Style::add([
            'id' => $args['id'],
            'name' => $args['name'],
            'orderIndex' => $args['orderIndex'],
            'storeInstance' => $args['storeInstance'],
            'styles' => [
                // Module styles
                $elements->style([
                    'attrName' => 'module',
                    'styleProps' => [
                        'disabledOn' => [
                            'disabledModuleVisibility' => $args['settings']['disabledModuleVisibility'] ?? null,
                        ],
                    ],
                ]),
                // Title element styles (font, size, color, etc.)
                $elements->style([
                    'attrName' => 'title',
                ]),
            ],
        ]);
    }

    // ===== LINK DESCRIPTION =====

    public static function render_link_description($attrs, $content, $block, $elements) {
        $link_id = self::get_link_id();
        $data = self::get_link_data($link_id);

        $description = $data && !empty($data['description'])
            ? wp_kses_post($data['description'])
            : 'Link description text';

        $description_html = HTMLUtility::render([
            'tag' => 'div',
            'attributes' => ['class' => 'lh-link-description'],
            'childrenSanitizer' => 'et_core_esc_previously',
            'children' => $description,
        ]);

        $module_inner = HTMLUtility::render([
            'tag' => 'div',
            'attributes' => ['class' => 'et_pb_module_inner'],
            'childrenSanitizer' => 'et_core_esc_previously',
            'children' => $description_html,
        ]);

        $module_elements = $elements->style_components(['attrName' => 'module']);

        return Module::render([
            'orderIndex' => $block->parsed_block['orderIndex'],
            'storeInstance' => $block->parsed_block['storeInstance'],
            'attrs' => $attrs,
            'elements' => $elements,
            'id' => $block->parsed_block['id'],
            'moduleClassName' => 'LH_link_description',
            'name' => $block->block_type->name,
            'classnamesFunction' => function($args) {
                self::render_classnames($args);
            },
            'moduleCategory' => $block->block_type->category,
            'stylesComponent' => function($args) {
                self::render_link_description_styles($args);
            },
            'scriptDataComponent' => function($args) {
                self::render_script_data($args);
            },
            'children' => $module_elements . $module_inner,
        ]);
    }

    /**
     * Render Link Description styles (module + description element)
     */
    private static function render_link_description_styles($args) {
        $elements = $args['elements'];

        Style::add([
            'id' => $args['id'],
            'name' => $args['name'],
            'orderIndex' => $args['orderIndex'],
            'storeInstance' => $args['storeInstance'],
            'styles' => [
                // Module styles
                $elements->style([
                    'attrName' => 'module',
                    'styleProps' => [
                        'disabledOn' => [
                            'disabledModuleVisibility' => $args['settings']['disabledModuleVisibility'] ?? null,
                        ],
                    ],
                ]),
                // Description element styles (font, size, color, etc.)
                $elements->style([
                    'attrName' => 'description',
                ]),
            ],
        ]);
    }

    // ===== LINK IMAGE =====

    public static function render_link_image($attrs, $content, $block, $elements) {
        $link_id = self::get_link_id();
        $data = self::get_link_data($link_id);
        $image_size = $attrs['imageSize'] ?? 'large';

        if ($data && $data['image_id']) {
            $img_tag = wp_get_attachment_image($data['image_id'], $image_size, false, [
                'alt' => esc_attr($data['post']->post_title),
            ]);
        } else {
            $img_tag = '<img src="https://via.placeholder.com/400x300?text=Link+Image" alt="Link Image" />';
        }

        // Wrap image in a container div for proper border-radius clipping
        $image_wrapper = HTMLUtility::render([
            'tag' => 'div',
            'attributes' => ['class' => 'lh-link-image'],
            'childrenSanitizer' => 'et_core_esc_previously',
            'children' => $img_tag,
        ]);

        $module_inner = HTMLUtility::render([
            'tag' => 'div',
            'attributes' => ['class' => 'et_pb_module_inner'],
            'childrenSanitizer' => 'et_core_esc_previously',
            'children' => $image_wrapper,
        ]);

        $module_elements = $elements->style_components(['attrName' => 'module']);

        return Module::render([
            'orderIndex' => $block->parsed_block['orderIndex'],
            'storeInstance' => $block->parsed_block['storeInstance'],
            'attrs' => $attrs,
            'elements' => $elements,
            'id' => $block->parsed_block['id'],
            'moduleClassName' => 'LH_link_image',
            'name' => $block->block_type->name,
            'classnamesFunction' => function($args) {
                self::render_classnames($args);
            },
            'moduleCategory' => $block->block_type->category,
            'stylesComponent' => function($args) {
                self::render_link_image_styles($args);
            },
            'scriptDataComponent' => function($args) {
                self::render_script_data($args);
            },
            'children' => $module_elements . $module_inner,
        ]);
    }

    /**
     * Render Link Image styles (module + image element)
     */
    private static function render_link_image_styles($args) {
        $elements = $args['elements'];

        Style::add([
            'id' => $args['id'],
            'name' => $args['name'],
            'orderIndex' => $args['orderIndex'],
            'storeInstance' => $args['storeInstance'],
            'styles' => [
                // Module styles
                $elements->style([
                    'attrName' => 'module',
                    'styleProps' => [
                        'disabledOn' => [
                            'disabledModuleVisibility' => $args['settings']['disabledModuleVisibility'] ?? null,
                        ],
                    ],
                ]),
                // Image element styles (border-radius, sizing, etc.)
                $elements->style([
                    'attrName' => 'image',
                ]),
            ],
        ]);
    }
    
    // ===== LINK ICON =====

    public static function render_link_icon($attrs, $content, $block, $elements) {
        $link_id = self::get_link_id();
        $data = self::get_link_data($link_id);

        $icon_html = '??';
        if ($data && !empty($data['icon'])) {
            // Icon is stored as a simple string (emoji or icon class)
            $icon_html = esc_html($data['icon']);
        }

        $icon_wrapped = HTMLUtility::render([
            'tag' => 'span',
            'attributes' => ['class' => 'lh-link-icon'],
            'childrenSanitizer' => 'et_core_esc_previously',
            'children' => $icon_html,
        ]);

        $module_inner = HTMLUtility::render([
            'tag' => 'div',
            'attributes' => ['class' => 'et_pb_module_inner'],
            'childrenSanitizer' => 'et_core_esc_previously',
            'children' => $icon_wrapped,
        ]);

        $module_elements = $elements->style_components(['attrName' => 'module']);

        return Module::render([
            'orderIndex' => $block->parsed_block['orderIndex'],
            'storeInstance' => $block->parsed_block['storeInstance'],
            'attrs' => $attrs,
            'elements' => $elements,
            'id' => $block->parsed_block['id'],
            'moduleClassName' => 'LH_link_icon',
            'name' => $block->block_type->name,
            'classnamesFunction' => function($args) {
                self::render_classnames($args);
            },
            'moduleCategory' => $block->block_type->category,
            'stylesComponent' => function($args) {
                self::render_link_icon_styles($args);
            },
            'scriptDataComponent' => function($args) {
                self::render_script_data($args);
            },
            'children' => $module_elements . $module_inner,
        ]);
    }

    /**
     * Render Link Icon styles (module + icon element)
     */
    private static function render_link_icon_styles($args) {
        $elements = $args['elements'];

        Style::add([
            'id' => $args['id'],
            'name' => $args['name'],
            'orderIndex' => $args['orderIndex'],
            'storeInstance' => $args['storeInstance'],
            'styles' => [
                // Module styles
                $elements->style([
                    'attrName' => 'module',
                    'styleProps' => [
                        'disabledOn' => [
                            'disabledModuleVisibility' => $args['settings']['disabledModuleVisibility'] ?? null,
                        ],
                    ],
                ]),
                // Icon element styles
                $elements->style([
                    'attrName' => 'icon',
                ]),
            ],
        ]);
    }
}

