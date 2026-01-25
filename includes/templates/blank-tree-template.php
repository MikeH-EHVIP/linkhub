<?php
/**
 * Blank Template for Link Trees
 * 
 * This template is used when "Hide Site Header/Footer" is enabled.
 * It provides a clean, distraction-free display for link-in-bio pages.
 *
 * @package LinkHub
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Remove theme header/footer actions
remove_all_actions('genesis_header');
remove_all_actions('genesis_footer');
remove_all_actions('et_header_top');
remove_all_actions('et_before_main_content');
remove_all_actions('et_after_main_content');

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* Emergency CSS to hide theme elements */
        body > *:not(script):not(style) { display: none !important; }
        body > .lh-tree-content { display: block !important; }
    </style>
    <?php wp_head(); ?>
</head>
<body <?php body_class('lh-blank-template'); ?>>
<?php wp_body_open(); ?>
<div class="lh-tree-content">
<?php
while (have_posts()) {
    the_post();
    the_content();
}
?>
</div>
<?php wp_footer(); ?>
</body>
</html>
