<?php

/**
 * Comprehensive WordPress Block Type Handlers
 * 
 * This class provides specialized handling for all default WordPress block types
 * and demonstrates how to create handlers for custom blocks. Each handler understands
 * the specific structure, attributes, and rendering requirements of its block type.
 * 
 * The beauty of this approach is that it maintains the semantic meaning of each block
 * while adapting the presentation for different platforms like XenForo.
 */

class WP_Block_Type_Handlers {
    
    private $block_handlers = [];
    
    public function __construct() {
        $this->register_default_handlers();
    }
    
    /**
     * Register handlers for all default WordPress blocks
     * Each handler knows how to extract, process, and adapt its specific block type
     */
    private function register_default_handlers() {
        
        // Text and Content Blocks
        $this->register_handler('core/paragraph', [$this, 'handle_paragraph']);
        $this->register_handler('core/heading', [$this, 'handle_heading']);
        $this->register_handler('core/list', [$this, 'handle_list']);
        $this->register_handler('core/quote', [$this, 'handle_quote']);
        $this->register_handler('core/code', [$this, 'handle_code']);
        $this->register_handler('core/preformatted', [$this, 'handle_preformatted']);
        $this->register_handler('core/verse', [$this, 'handle_verse']);
        
        // Media Blocks
        $this->register_handler('core/image', [$this, 'handle_image']);
        $this->register_handler('core/gallery', [$this, 'handle_gallery']);
        $this->register_handler('core/audio', [$this, 'handle_audio']);
        $this->register_handler('core/video', [$this, 'handle_video']);
        $this->register_handler('core/file', [$this, 'handle_file']);
        
        // Layout Blocks
        $this->register_handler('core/group', [$this, 'handle_group']);
        $this->register_handler('core/columns', [$this, 'handle_columns']);
        $this->register_handler('core/column', [$this, 'handle_column']);
        $this->register_handler('core/cover', [$this, 'handle_cover']);
        $this->register_handler('core/spacer', [$this, 'handle_spacer']);
        $this->register_handler('core/separator', [$this, 'handle_separator']);
        
        // Interactive Blocks
        $this->register_handler('core/button', [$this, 'handle_button']);
        $this->register_handler('core/buttons', [$this, 'handle_buttons']);
        $this->register_handler('core/social-links', [$this, 'handle_social_links']);
        $this->register_handler('core/navigation', [$this, 'handle_navigation']);
        
        // Embed Blocks
        $this->register_handler('core/embed', [$this, 'handle_embed']);
        $this->register_handler('core-embed/youtube', [$this, 'handle_youtube']);
        $this->register_handler('core-embed/twitter', [$this, 'handle_twitter']);
        
        // Advanced Blocks
        $this->register_handler('core/table', [$this, 'handle_table']);
        $this->register_handler('core/calendar', [$this, 'handle_calendar']);
        $this->register_handler('core/search', [$this, 'handle_search']);
        
        // Custom block example
        $this->register_handler('custom/testimonial', [$this, 'handle_custom_testimonial']);
    }
    
    public function register_handler($block_name, $handler) {
        $this->block_handlers[$block_name] = $handler;
    }
    
    public function process_block($block) {
        $block_name = $block['blockName'];
        
        if (isset($this->block_handlers[$block_name])) {
            return call_user_func($this->block_handlers[$block_name], $block);
        }
        
        // Fallback handler for unknown blocks
        return $this->handle_generic_block($block);
    }
    
    /**
     * PARAGRAPH BLOCK HANDLER
     * Handles core/paragraph blocks with text formatting, colors, and typography
     */
    public function handle_paragraph($block) {
        $attributes = $block['attrs'] ?? [];
        
        // Extract paragraph-specific attributes
        $text_color = $attributes['textColor'] ?? null;
        $background_color = $attributes['backgroundColor'] ?? null;
        $font_size = $attributes['fontSize'] ?? null;
        $align = $attributes['align'] ?? null;
        $drop_cap = $attributes['dropCap'] ?? false;
        
        // Build CSS classes and inline styles
        $classes = ['wp-block-paragraph'];
        $inline_styles = [];
        
        if ($text_color) {
            $classes[] = "has-{$text_color}-color";
            $classes[] = "has-text-color";
        }
        
        if ($background_color) {
            $classes[] = "has-{$background_color}-background-color";
            $classes[] = "has-background";
        }
        
        if ($font_size) {
            $classes[] = "has-{$font_size}-font-size";
        }
        
        if ($align) {
            $classes[] = "has-text-align-{$align}";
        }
        
        if ($drop_cap) {
            $classes[] = "has-drop-cap";
        }
        
        // Handle custom colors and font sizes
        if (isset($attributes['style']['color']['text'])) {
            $inline_styles[] = "color: {$attributes['style']['color']['text']}";
        }
        
        if (isset($attributes['style']['color']['background'])) {
            $inline_styles[] = "background-color: {$attributes['style']['color']['background']}";
        }
        
        if (isset($attributes['style']['typography']['fontSize'])) {
            $inline_styles[] = "font-size: {$attributes['style']['typography']['fontSize']}";
        }
        
        return [
            'type' => 'paragraph',
            'content' => $block['innerHTML'],
            'classes' => $classes,
            'inline_styles' => $inline_styles,
            'xenforo_template' => 'wp_block_paragraph',
            'xenforo_data' => [
                'content' => strip_tags($block['innerHTML'], '<strong><em><a><br><span>'),
                'has_formatting' => $this->has_text_formatting($block['innerHTML']),
                'alignment' => $align,
                'drop_cap' => $drop_cap
            ]
        ];
    }
    
    /**
     * HEADING BLOCK HANDLER
     * Handles core/heading blocks with levels, colors, and typography
     */
    public function handle_heading($block) {
        $attributes = $block['attrs'] ?? [];
        
        $level = $attributes['level'] ?? 2;
        $text_color = $attributes['textColor'] ?? null;
        $background_color = $attributes['backgroundColor'] ?? null;
        $align = $attributes['align'] ?? null;
        $anchor = $attributes['anchor'] ?? null;
        
        $classes = ["wp-block-heading"];
        $inline_styles = [];
        
        if ($text_color) {
            $classes[] = "has-{$text_color}-color";
        }
        
        if ($align) {
            $classes[] = "has-text-align-{$align}";
        }
        
        // Handle custom styling
        if (isset($attributes['style']['color']['text'])) {
            $inline_styles[] = "color: {$attributes['style']['color']['text']}";
        }
        
        if (isset($attributes['style']['typography']['fontSize'])) {
            $inline_styles[] = "font-size: {$attributes['style']['typography']['fontSize']}";
        }
        
        return [
            'type' => 'heading',
            'content' => $block['innerHTML'],
            'level' => $level,
            'anchor' => $anchor,
            'classes' => $classes,
            'inline_styles' => $inline_styles,
            'xenforo_template' => 'wp_block_heading',
            'xenforo_data' => [
                'text' => strip_tags($block['innerHTML']),
                'level' => $level,
                'anchor' => $anchor,
                'alignment' => $align
            ]
        ];
    }
    
    /**
     * IMAGE BLOCK HANDLER
     * Handles core/image blocks with captions, sizing, and alignment
     */
    public function handle_image($block) {
        $attributes = $block['attrs'] ?? [];
        
        // Extract image attributes
        $image_id = $attributes['id'] ?? null;
        $url = $attributes['url'] ?? '';
        $alt = $attributes['alt'] ?? '';
        $caption = $attributes['caption'] ?? '';
        $align = $attributes['align'] ?? null;
        $width = $attributes['width'] ?? null;
        $height = $attributes['height'] ?? null;
        $size_slug = $attributes['sizeSlug'] ?? 'large';
        $link_destination = $attributes['linkDestination'] ?? null;
        $link_target = $attributes['linkTarget'] ?? null;
        $href = $attributes['href'] ?? null;
        
        // Get image metadata if we have an ID
        $image_meta = [];
        if ($image_id) {
            $image_meta = wp_get_attachment_metadata($image_id);
            $image_sizes = $image_meta['sizes'] ?? [];
        }
        
        $classes = ['wp-block-image'];
        $inline_styles = [];
        
        if ($align) {
            $classes[] = "align{$align}";
        }
        
        if ($width) {
            $inline_styles[] = "width: {$width}px";
        }
        
        if ($height) {
            $inline_styles[] = "height: {$height}px";
        }
        
        // Prepare different image sizes for responsive display
        $responsive_sizes = [];
        if ($image_id) {
            $responsive_sizes = [
                'thumbnail' => wp_get_attachment_image_src($image_id, 'thumbnail')[0] ?? '',
                'medium' => wp_get_attachment_image_src($image_id, 'medium')[0] ?? '',
                'large' => wp_get_attachment_image_src($image_id, 'large')[0] ?? '',
                'full' => wp_get_attachment_image_src($image_id, 'full')[0] ?? ''
            ];
        }
        
        return [
            'type' => 'image',
            'content' => $block['innerHTML'],
            'classes' => $classes,
            'inline_styles' => $inline_styles,
            'xenforo_template' => 'wp_block_image',
            'xenforo_data' => [
                'url' => $url,
                'alt' => $alt,
                'caption' => $caption,
                'alignment' => $align,
                'width' => $width,
                'height' => $height,
                'sizes' => $responsive_sizes,
                'has_link' => !empty($href),
                'link_url' => $href,
                'link_target' => $link_target
            ]
        ];
    }
    
    /**
     * GALLERY BLOCK HANDLER
     * Handles core/gallery blocks with multiple images and layout options
     * This is a more complex block that demonstrates nested content handling
     */
    public function handle_gallery($block) {
        $attributes = $block['attrs'] ?? [];
        
        $images = $attributes['images'] ?? [];
        $columns = $attributes['columns'] ?? 3;
        $image_crop = $attributes['imageCrop'] ?? true;
        $size_slug = $attributes['sizeSlug'] ?? 'large';
        $link_to = $attributes['linkTo'] ?? 'none';
        $caption = $attributes['caption'] ?? '';
        
        $classes = ['wp-block-gallery'];
        $classes[] = "columns-{$columns}";
        $classes[] = $image_crop ? 'is-cropped' : '';
        
        // Process each image in the gallery
        $processed_images = [];
        foreach ($images as $image) {
            $image_data = [];
            
            if (isset($image['id'])) {
                $image_data['id'] = $image['id'];
                $image_data['url'] = $image['url'] ?? wp_get_attachment_url($image['id']);
                $image_data['alt'] = $image['alt'] ?? get_post_meta($image['id'], '_wp_attachment_image_alt', true);
                $image_data['caption'] = $image['caption'] ?? wp_get_attachment_caption($image['id']);
                
                // Get different sizes for responsive display
                $image_data['sizes'] = [
                    'thumbnail' => wp_get_attachment_image_src($image['id'], 'thumbnail')[0] ?? '',
                    'medium' => wp_get_attachment_image_src($image['id'], 'medium')[0] ?? '',
                    'large' => wp_get_attachment_image_src($image['id'], 'large')[0] ?? '',
                    'full' => wp_get_attachment_image_src($image['id'], 'full')[0] ?? ''
                ];
            } else {
                // Handle images without IDs (external images)
                $image_data = [
                    'url' => $image['url'] ?? '',
                    'alt' => $image['alt'] ?? '',
                    'caption' => $image['caption'] ?? ''
                ];
            }
            
            $processed_images[] = $image_data;
        }
        
        return [
            'type' => 'gallery',
            'content' => $block['innerHTML'],
            'classes' => $classes,
            'xenforo_template' => 'wp_block_gallery',
            'xenforo_data' => [
                'images' => $processed_images,
                'columns' => $columns,
                'image_crop' => $image_crop,
                'link_to' => $link_to,
                'caption' => $caption,
                'total_images' => count($processed_images)
            ]
        ];
    }
    
    /**
     * BUTTON BLOCK HANDLER
     * Handles core/button blocks with styling, links, and interactions
     */
    public function handle_button($block) {
        $attributes = $block['attrs'] ?? [];
        
        $text = $attributes['text'] ?? '';
        $url = $attributes['url'] ?? '';
        $link_target = $attributes['linkTarget'] ?? null;
        $rel = $attributes['rel'] ?? null;
        $placeholder = $attributes['placeholder'] ?? '';
        $background_color = $attributes['backgroundColor'] ?? null;
        $text_color = $attributes['textColor'] ?? null;
        $border_radius = $attributes['borderRadius'] ?? null;
        $width = $attributes['width'] ?? null;
        
        $classes = ['wp-block-button'];
        $button_classes = ['wp-block-button__link'];
        $inline_styles = [];
        
        if ($background_color) {
            $button_classes[] = "has-{$background_color}-background-color";
            $button_classes[] = "has-background";
        }
        
        if ($text_color) {
            $button_classes[] = "has-{$text_color}-color";
            $button_classes[] = "has-text-color";
        }
        
        if ($width) {
            $classes[] = "is-style-{$width}";
        }
        
        // Handle custom colors
        if (isset($attributes['style']['color']['background'])) {
            $inline_styles[] = "background-color: {$attributes['style']['color']['background']}";
        }
        
        if (isset($attributes['style']['color']['text'])) {
            $inline_styles[] = "color: {$attributes['style']['color']['text']}";
        }
        
        if ($border_radius !== null) {
            $inline_styles[] = "border-radius: {$border_radius}px";
        }
        
        return [
            'type' => 'button',
            'content' => $block['innerHTML'],
            'classes' => $classes,
            'button_classes' => $button_classes,
            'inline_styles' => $inline_styles,
            'xenforo_template' => 'wp_block_button',
            'xenforo_data' => [
                'text' => $text,
                'url' => $url,
                'target' => $link_target,
                'rel' => $rel,
                'is_external' => $this->is_external_link($url),
                'has_custom_styling' => !empty($inline_styles)
            ]
        ];
    }
    
    /**
     * COLUMNS BLOCK HANDLER
     * Handles core/columns blocks for layout (container for column blocks)
     */
    public function handle_columns($block) {
        $attributes = $block['attrs'] ?? [];
        
        $columns_count = count($block['innerBlocks'] ?? []);
        $is_stacked_on_mobile = $attributes['isStackedOnMobile'] ?? true;
        $vertical_alignment = $attributes['verticalAlignment'] ?? null;
        
        $classes = ['wp-block-columns'];
        
        if ($is_stacked_on_mobile) {
            $classes[] = 'is-stacked-on-mobile';
        }
        
        if ($vertical_alignment) {
            $classes[] = "are-vertically-aligned-{$vertical_alignment}";
        }
        
        return [
            'type' => 'columns',
            'content' => $block['innerHTML'],
            'classes' => $classes,
            'xenforo_template' => 'wp_block_columns',
            'xenforo_data' => [
                'columns_count' => $columns_count,
                'is_stacked_on_mobile' => $is_stacked_on_mobile,
                'vertical_alignment' => $vertical_alignment
            ]
        ];
    }
    
    /**
     * CUSTOM TESTIMONIAL BLOCK HANDLER
     * Example of how to handle custom blocks with specialized functionality
     * This demonstrates the pattern for creating handlers for your own custom blocks
     */
    public function handle_custom_testimonial($block) {
        $attributes = $block['attrs'] ?? [];
        
        // Custom block attributes
        $testimonial_text = $attributes['testimonialText'] ?? '';
        $author_name = $attributes['authorName'] ?? '';
        $author_title = $attributes['authorTitle'] ?? '';
        $author_image = $attributes['authorImage'] ?? '';
        $rating = $attributes['rating'] ?? 5;
        $show_rating = $attributes['showRating'] ?? true;
        $background_color = $attributes['backgroundColor'] ?? 'white';
        $text_color = $attributes['textColor'] ?? 'black';
        $border_style = $attributes['borderStyle'] ?? 'solid';
        
        $classes = ['wp-block-testimonial'];
        $classes[] = "has-{$background_color}-background";
        $classes[] = "has-{$text_color}-color";
        $classes[] = "border-{$border_style}";
        
        // Generate star rating display
        $star_display = '';
        if ($show_rating) {
            for ($i = 1; $i <= 5; $i++) {
                $star_display .= $i <= $rating ? '★' : '☆';
            }
        }
        
        // Process author image if provided
        $author_image_data = [];
        if ($author_image) {
            if (is_numeric($author_image)) {
                // It's an attachment ID
                $author_image_data = [
                    'url' => wp_get_attachment_url($author_image),
                    'alt' => get_post_meta($author_image, '_wp_attachment_image_alt', true)
                ];
            } else {
                // It's a URL
                $author_image_data = [
                    'url' => $author_image,
                    'alt' => $author_name
                ];
            }
        }
        
        return [
            'type' => 'custom_testimonial',
            'content' => $block['innerHTML'],
            'classes' => $classes,
            'xenforo_template' => 'wp_block_custom_testimonial',
            'xenforo_data' => [
                'testimonial_text' => $testimonial_text,
                'author_name' => $author_name,
                'author_title' => $author_title,
                'author_image' => $author_image_data,
                'rating' => $rating,
                'star_display' => $star_display,
                'show_rating' => $show_rating,
                'has_author_image' => !empty($author_image_data),
                'css_classes' => implode(' ', $classes)
            ]
        ];
    }
    
    /**
     * GENERIC BLOCK HANDLER
     * Fallback handler for blocks that don't have specific handlers
     * This ensures that even unknown blocks can be processed gracefully
     */
    public function handle_generic_block($block) {
        $attributes = $block['attrs'] ?? [];
        $block_name = $block['blockName'] ?? 'unknown';
        
        // Extract common attributes that most blocks might have
        $align = $attributes['align'] ?? null;
        $anchor = $attributes['anchor'] ?? null;
        $class_name = $attributes['className'] ?? '';
        
        $classes = ['wp-block', str_replace('/', '-', $block_name)];
        
        if ($align) {
            $classes[] = "align{$align}";
        }
        
        if ($class_name) {
            $classes[] = $class_name;
        }
        
        return [
            'type' => 'generic',
            'block_name' => $block_name,
            'content' => $block['innerHTML'],
            'classes' => $classes,
            'xenforo_template' => 'wp_block_generic',
            'xenforo_data' => [
                'block_name' => $block_name,
                'raw_content' => $block['innerHTML'],
                'attributes' => $attributes,
                'has_inner_blocks' => !empty($block['innerBlocks'])
            ]
        ];
    }
    
    /**
     * UTILITY METHODS
     * Helper functions used by multiple block handlers
     */
    
    private function has_text_formatting($content) {
        return strpos($content, '<') !== false;
    }
    
    private function is_external_link($url) {
        if (empty($url)) return false;
        
        $site_url = get_site_url();
        return strpos($url, $site_url) !== 0 && strpos($url, '/') !== 0;
    }
    
    private function extract_inline_styles($style_attribute) {
        $styles = [];
        if (empty($style_attribute)) return $styles;
        
        $style_pairs = explode(';', $style_attribute);
        foreach ($style_pairs as $pair) {
            if (strpos($pair, ':') !== false) {
                [$property, $value] = explode(':', $pair, 2);
                $styles[trim($property)] = trim($value);
            }
        }
        
        return $styles;
    }
    
    /**
     * Get processed block data suitable for external consumption
     */
    public function get_processed_block_data($block) {
        $processed = $this->process_block($block);
        
        // Add common metadata
        $processed['original_block_name'] = $block['blockName'];
        $processed['has_inner_blocks'] = !empty($block['innerBlocks']);
        $processed['inner_blocks_count'] = count($block['innerBlocks'] ?? []);
        
        // Process inner blocks recursively
        if (!empty($block['innerBlocks'])) {
            $processed['inner_blocks'] = [];
            foreach ($block['innerBlocks'] as $inner_block) {
                $processed['inner_blocks'][] = $this->get_processed_block_data($inner_block);
            }
        }
        
        return $processed;
    }
}

/**
 * Enhanced XenForo Integration System
 * 
 * This class provides comprehensive integration capabilities for displaying
 * WordPress blocks within XenForo, including template integration, asset management,
 * and responsive design considerations.
 */

class XenForo_WordPress_Integration {
    
    private $wp_api_url;
    private $block_handlers;
    private $cache_duration = 3600;
    private $asset_cache = [];
    private $template_cache = [];
    
    public function __construct($wp_site_url) {
        $this->wp_api_url = rtrim($wp_site_url, '/') . '/wp-json/wp-blocks/v1';
        $this->block_handlers = new WP_Block_Type_Handlers();
    }
    
    /**
     * Main method to fetch and render WordPress content in XenForo
     * This is the primary entry point for XenForo integration
     */
    public function render_wp_content($post_id, $options = []) {
        // Default options
        $options = array_merge([
            'include_styles' => true,
            'include_scripts' => true,
            'responsive' => true,
            'cache' => true,
            'template_wrapper' => 'wp_content_wrapper'
        ], $options);
        
        // Fetch block data from WordPress
        $block_data = $this->fetch_block_content($post_id, $options['cache']);
        
        if (!$block_data) {
            return $this->render_error_message('Unable to fetch WordPress content');
        }
        
        // Process blocks through our handlers
        $processed_blocks = [];
        foreach ($block_data['blocks'] as $block) {
            $processed_blocks[] = $this->block_handlers->get_processed_block_data($block);
        }
        
        // Generate complete HTML output
        $html_output = '';
        
        // Add CSS if requested
        if ($options['include_styles']) {
            $html_output .= $this->generate_block_styles($processed_blocks, $block_data);
        }
        
        // Add JavaScript if requested
        if ($options['include_scripts']) {
            $html_output .= $this->generate_block_scripts($processed_blocks, $block_data);
        }
        
        // Render the blocks
        $html_output .= $this->render_blocks_html($processed_blocks, $options);
        
        return $html_output;
    }
    
    /**
     * Generate comprehensive CSS for WordPress blocks in XenForo context
     */
    private function generate_block_styles($processed_blocks, $block_data) {
        $css_output = '<style type="text/css" id="wp-block-styles">';
        
        // Add WordPress base styles adapted for XenForo
        $css_output .= $this->get_wp_base_styles_for_xenforo();
        
        // Add global WordPress styles
        if (!empty($block_data['global_styles']['theme_json'])) {
            $css_output .= $this->process_theme_json_styles($block_data['global_styles']['theme_json']);
        }
        
        // Add CSS custom properties
        if (!empty($block_data['global_styles']['css_variables'])) {
            $css_output .= ':root {';
            foreach ($block_data['global_styles']['css_variables'] as $property => $value) {
                $css_output .= "{$property}: {$value};";
            }
            $css_output .= '}';
        }
        
        // Add block-specific styles
        $css_output .= $this->generate_block_specific_styles($processed_blocks);
        
        // Add responsive styles for XenForo integration
        $css_output .= $this->get_responsive_styles();
        
        $css_output .= '</style>';
        
        return $css_output;
    }
    
    /**
     * WordPress base styles adapted for XenForo's CSS * framework
     */

    

    private function get_wp_base_styles_for_xenforo() {
        return '
        /* WordPress Block Base Styles for XenForo */
        .wp-block {
            margin-bottom: 1.5em;
            clear: both;
        }
        
        .wp-block:last-child {
            margin-bottom: 0;
        }
        
        /* Paragraph Blocks */
        .wp-block-paragraph {
            margin-top: 0;
            margin-bottom: 1em;
        }
        
        .wp-block-paragraph.has-drop-cap::first-letter {
            float: left;
            font-size: 3em;
            line-height: 1;
            margin: 0.1em 0.1em 0 0;
            font-weight: bold;
        }
        
        /* Heading Blocks */
        .wp-block-heading {
            margin-top: 1em;
            margin-bottom: 0.75em;
        }
        ';