<?php
/**
 * WordPress Block API for External Integration
 * 
 * This comprehensive system extracts WordPress Gutenberg blocks with their complete data,
 * styling, and functionality to allow seamless integration with external platforms like XenForo.
 * 
 * The approach works by:
 * 1. Parsing WordPress content to extract block data and structure
 * 2. Collecting associated CSS, JavaScript, and media assets
 * 3. Processing block-specific attributes and configurations
 * 4. Packaging everything into a consumable API format with fallbacks
 * 5. Providing specialized handlers for default and custom block types
 * 
 * Key Concepts:
 * - Block Registry: Understanding what blocks are available and their schemas
 * - Asset Collection: Gathering all CSS/JS dependencies for proper rendering
 * - Attribute Processing: Handling block configuration and user customizations
 * - Rendering Pipeline: Converting block data to HTML while preserving functionality
 */

// Add to your theme's functions.php or create as a plugin

class WP_Block_External_API {
    
    private $registered_blocks = [];
    private $block_assets = [];
    
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_api_endpoints']);
        add_action('wp_enqueue_scripts', [$this, 'collect_block_assets']);
    }
    
    /**
     * Register our custom REST API endpoints
     * These endpoints will be accessible to external sites
     */
    public function register_api_endpoints() {
        // Main endpoint to fetch block data with styling and functionality
        register_rest_route('wp-blocks/v1', '/content/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_block_content'],
            'permission_callback' => '__return_true', // Adjust permissions as needed
            'args' => [
                'id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ]
            ]
        ]);
        
        // Endpoint to get available block types and their schemas
        register_rest_route('wp-blocks/v1', '/block-types', [
            'methods' => 'GET',
            'callback' => [$this, 'get_block_types'],
            'permission_callback' => '__return_true'
        ]);
        
        // Endpoint specifically for block assets (CSS/JS)
        register_rest_route('wp-blocks/v1', '/assets/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_block_assets'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    /**
     * Main API callback - fetches block content with all associated data
     * This is what external sites will primarily use
     */
    public function get_block_content($request) {
        $post_id = $request['id'];
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('post_not_found', 'Post not found', ['status' => 404]);
        }
        
        // Parse the content to extract blocks
        $blocks = parse_blocks($post->post_content);
        $processed_blocks = [];
        
        foreach ($blocks as $block) {
            if (!empty($block['blockName'])) {
                $processed_blocks[] = $this->process_block($block);
            }
        }
        
        return [
            'post_id' => $post_id,
            'post_title' => $post->post_title,
            'blocks' => $processed_blocks,
            'global_styles' => $this->get_global_block_styles(),
            'scripts' => $this->get_block_scripts()
        ];
    }
    
    /**
     * Process individual blocks to extract their complete data structure
     * This is where we dig into each block to understand its content and configuration
     */
    private function process_block($block) {
        $block_type = WP_Block_Type_Registry::get_instance()->get_registered($block['blockName']);
        
        // Create a block instance to access rendered content
        $block_instance = new WP_Block($block);
        
        $processed_block = [
            'name' => $block['blockName'],
            'attributes' => $block['attrs'] ?? [],
            'inner_blocks' => [],
            'rendered_content' => $block_instance->render(),
            'raw_content' => $block['innerHTML'] ?? '',
            'block_supports' => $block_type ? $block_type->supports : [],
            'block_styles' => $this->extract_block_styles($block['blockName']),
            'block_scripts' => $this->extract_block_scripts($block['blockName'])
        ];
        
        // Process nested blocks recursively
        if (!empty($block['innerBlocks'])) {
            foreach ($block['innerBlocks'] as $inner_block) {
                $processed_block['inner_blocks'][] = $this->process_block($inner_block);
            }
        }
        
        return $processed_block;
    }
    
    /**
     * Extract CSS styles specific to a block type
     * This captures both theme styles and block-specific styles
     */
    private function extract_block_styles($block_name) {
        $styles = [];
        
        // Get registered block styles
        $block_styles = WP_Block_Styles_Registry::get_instance()->get_registered_styles($block_name);
        
        foreach ($block_styles as $style) {
            $styles[] = [
                'name' => $style['name'],
                'label' => $style['label'],
                'inline_style' => $style['inline_style'] ?? null,
                'style_handle' => $style['style_handle'] ?? null
            ];
        }
        
        // Get theme.json styles if available
        if (function_exists('wp_get_global_settings')) {
            $theme_styles = wp_get_global_settings();
            if (isset($theme_styles['blocks'][$block_name])) {
                $styles['theme_styles'] = $theme_styles['blocks'][$block_name];
            }
        }
        
        return $styles;
    }
    
    /**
     * Extract JavaScript functionality for blocks
     * This captures both view scripts and editor scripts
     */
    private function extract_block_scripts($block_name) {
        $scripts = [];
        $block_type = WP_Block_Type_Registry::get_instance()->get_registered($block_name);
        
        if ($block_type) {
            // View script (frontend functionality)
            if ($block_type->view_script) {
                $scripts['view_script'] = $this->get_script_content($block_type->view_script);
            }
            
            // View script module (modern ES modules)
            if ($block_type->view_script_module) {
                $scripts['view_script_module'] = $this->get_script_content($block_type->view_script_module);
            }
        }
        
        return $scripts;
    }
    
    /**
     * Get the actual content of enqueued scripts
     * This allows external sites to include the JavaScript functionality
     */
    private function get_script_content($script_handle) {
        global $wp_scripts;
        
        if (!isset($wp_scripts->registered[$script_handle])) {
            return null;
        }
        
        $script = $wp_scripts->registered[$script_handle];
        $script_path = str_replace(site_url(), ABSPATH, $script->src);
        
        if (file_exists($script_path)) {
            return [
                'handle' => $script_handle,
                'src' => $script->src,
                'content' => file_get_contents($script_path),
                'dependencies' => $script->deps
            ];
        }
        
        return [
            'handle' => $script_handle,
            'src' => $script->src,
            'dependencies' => $script->deps
        ];
    }
    
    /**
     * Get global block styles that apply across all blocks
     * This includes theme.json styles and global CSS variables
     */
    private function get_global_block_styles() {
        $global_styles = [];
        
        // Get theme.json global styles
        if (function_exists('wp_get_global_stylesheet')) {
            $global_styles['theme_json'] = wp_get_global_stylesheet();
        }
        
        // Get CSS custom properties (CSS variables)
        if (function_exists('wp_get_global_settings')) {
            $settings = wp_get_global_settings();
            $global_styles['css_variables'] = $this->generate_css_variables($settings);
        }
        
        return $global_styles;
    }
    
    /**
     * Generate CSS variables from theme.json settings
     * This ensures external sites can replicate the design system
     */
    private function generate_css_variables($settings) {
        $css_vars = [];
        
        // Color palette
        if (isset($settings['color']['palette'])) {
            foreach ($settings['color']['palette'] as $color) {
                $css_vars["--wp--preset--color--{$color['slug']}"] = $color['color'];
            }
        }
        
        // Font sizes
        if (isset($settings['typography']['fontSizes'])) {
            foreach ($settings['typography']['fontSizes'] as $size) {
                $css_vars["--wp--preset--font-size--{$size['slug']}"] = $size['size'];
            }
        }
        
        // Spacing scale
        if (isset($settings['spacing']['spacingSizes'])) {
            foreach ($settings['spacing']['spacingSizes'] as $spacing) {
                $css_vars["--wp--preset--spacing--{$spacing['slug']}"] = $spacing['size'];
            }
        }
        
        return $css_vars;
    }
    
    /**
     * Get all registered block types and their schemas
     * Useful for external sites to understand available block types
     */
    public function get_block_types($request) {
        $registry = WP_Block_Type_Registry::get_instance();
        $block_types = [];
        
        foreach ($registry->get_all_registered() as $name => $block_type) {
            $block_types[$name] = [
                'name' => $name,
                'title' => $block_type->title ?? $name,
                'description' => $block_type->description ?? '',
                'attributes' => $block_type->attributes ?? [],
                'supports' => $block_type->supports ?? [],
                'category' => $block_type->category ?? 'common',
                'keywords' => $block_type->keywords ?? []
            ];
        }
        
        return $block_types;
    }
    
    /**
     * Get block assets endpoint
     * Returns CSS and JavaScript assets for a specific post
     */
    public function get_block_assets($request) {
        $post_id = $request['id'];
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('post_not_found', 'Post not found', ['status' => 404]);
        }
        
        // Get all enqueued styles and scripts for this post
        $assets = [
            'styles' => $this->get_post_block_styles($post_id),
            'scripts' => $this->get_post_block_scripts($post_id)
        ];
        
        return $assets;
    }
    
    /**
     * Get styles specific to blocks used in a post
     */
    private function get_post_block_styles($post_id) {
        // This would collect all CSS needed for the blocks in this specific post
        // Implementation would depend on your specific needs
        return [];
    }
    
    /**
     * Get scripts specific to blocks used in a post
     */
    private function get_post_block_scripts($post_id) {
        // This would collect all JavaScript needed for the blocks in this specific post
        // Implementation would depend on your specific needs
        return [];
    }
    
    /**
     * Collect block assets during normal page load
     * This helps us understand what assets are being used
     */
    public function collect_block_assets() {
        // Hook into the normal WordPress asset loading to collect information
        // about what styles and scripts are being loaded for blocks
    }
}

// Initialize the API
new WP_Block_External_API();

/**
 * XenForo Integration Helper Class
 * 
 * This class provides helper methods for integrating the WordPress block data
 * into XenForo or other external platforms.
 */
class XenForo_Block_Renderer {
    
    private $wp_api_url;
    private $cache_duration = 3600; // 1 hour
    
    public function __construct($wp_site_url) {
        $this->wp_api_url = rtrim($wp_site_url, '/') . '/wp-json/wp-blocks/v1';
    }
    
    /**
     * Fetch block content from WordPress API
     * This is what you'd call from XenForo to get the block data
     */
    public function fetch_block_content($post_id) {
        // Use caching to avoid repeated API calls
        $cache_key = "wp_blocks_{$post_id}";
        $cached_content = $this->get_cached_content($cache_key);
        
        if ($cached_content !== false) {
            return $cached_content;
        }
        
        // Fetch from WordPress API
        $response = wp_remote_get($this->wp_api_url . "/content/{$post_id}");
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Cache the result
        $this->cache_content($cache_key, $data);
        
        return $data;
    }
    
    /**
     * Render blocks for XenForo template system
     * This converts WordPress block data into XenForo-compatible HTML
     */
    public function render_blocks_for_xenforo($blocks, $include_styles = true) {
        $output = '';
        
        if ($include_styles) {
            $output .= $this->generate_block_styles($blocks);
        }
        
        foreach ($blocks as $block) {
            $output .= $this->render_single_block($block);
        }
        
        return $output;
    }
    
    /**
     * Render a single block with XenForo-compatible markup
     */
    private function render_single_block($block) {
        $html = '<div class="wp-block wp-block-' . esc_attr(str_replace('/', '-', $block['name'])) . '">';
        
        // Add block attributes as data attributes for JavaScript functionality
        if (!empty($block['attributes'])) {
            foreach ($block['attributes'] as $key => $value) {
                if (is_scalar($value)) {
                    $html .= ' data-' . esc_attr($key) . '="' . esc_attr($value) . '"';
                }
            }
        }
        
        $html .= '>';
        $html .= $block['rendered_content'];
        
        // Render inner blocks recursively
        if (!empty($block['inner_blocks'])) {
            foreach ($block['inner_blocks'] as $inner_block) {
                $html .= $this->render_single_block($inner_block);
            }
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generate CSS styles for blocks
     */
    private function generate_block_styles($blocks) {
        $styles = '<style type="text/css">';
        
        // Add global WordPress block styles
        $styles .= $this->get_base_block_styles();
        
        // Add specific block styles
        foreach ($blocks as $block) {
            if (!empty($block['block_styles'])) {
                // Process block-specific styles
                foreach ($block['block_styles'] as $style) {
                    if (!empty($style['inline_style'])) {
                        $styles .= $style['inline_style'];
                    }
                }
            }
        }
        
        $styles .= '</style>';
        
        return $styles;
    }
    
    /**
     * Get base block styles that WordPress uses
     */
    private function get_base_block_styles() {
        return '
            .wp-block {
                margin-bottom: 1em;
            }
            .wp-block:last-child {
                margin-bottom: 0;
            }
            /* Add more base styles as needed */
        ';
    }
    
    /**
     * Simple caching mechanism
     * In a real XenForo implementation, you'd use XenForo's caching system
     */
    private function get_cached_content($key) {
        // Implement your caching logic here
        // For XenForo, you might use: \XF::app()->cache()->fetch($key)
        return false;
    }
    
    private function cache_content($key, $data) {
        // Implement your caching logic here
        // For XenForo, you might use: \XF::app()->cache()->set($key, $data, $this->cache_duration)
    }
}

// Example usage in XenForo:
/*
$renderer = new XenForo_Block_Renderer('https://your-wordpress-site.com');
$block_data = $renderer->fetch_block_content(123); // WordPress post ID
$rendered_html = $renderer->render_blocks_for_xenforo($block_data['blocks']);
echo $rendered_html;
*/