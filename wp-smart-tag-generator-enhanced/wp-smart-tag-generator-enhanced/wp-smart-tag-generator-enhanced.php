<?php
/**
 * Plugin Name: WP Smart Tag Generator Enhanced (Orthodox Edition)
 * Description: AI-powered WordPress content analysis with Orthodox Christian context. Generate tags, extract named entities, and create topical maps with full Arabic language support.
 * Version: 3.1.0
 * Author: Wiz Consults
 * Author URI: https://www.wizconsults.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-smart-tag-generator
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Network: false
 * 
 * @fixed 2025-05-30 18:27:30 UTC by cyberkar
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access forbidden.');
}

// Define plugin constants
define('WSTG_VERSION', '3.1.0');
define('WSTG_PLUGIN_FILE', __FILE__);
define('WSTG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WSTG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WSTG_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class - FIXED SYNTAX ERROR
 */
class WP_Smart_Tag_Generator_Enhanced {
    
    private static $instance = null;
    private $deepseek_api = null;
    private $content_analyzer = null;
    private $taxonomy_manager = null;
    private $bulk_processor = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('plugins_loaded', array($this, 'plugins_loaded'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function plugins_loaded() {
        // Load text domain first
        load_plugin_textdomain(
            'wp-smart-tag-generator',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
        
        // Load all required classes
        $this->load_includes();
        
        // Initialize components
        $this->init_components();
        
        // Setup WordPress hooks
        $this->setup_hooks();
        
        error_log('WSTG: Plugin loaded successfully at ' . current_time('Y-m-d H:i:s') . ' UTC by ' . wp_get_current_user()->user_login);
    }
    
    private function load_includes() {
        $includes = array(
            'class-enhanced-deepseek-api.php',
            'class-enhanced-content-analyzer.php', 
            'class-taxonomy-manager.php',
            'class-bulk-processor.php'
        );
        
        foreach ($includes as $file) {
            $file_path = WSTG_PLUGIN_DIR . 'includes/' . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
                error_log('WSTG: Loaded ' . $file);
            } else {
                error_log('WSTG: Missing ' . $file . ' - some features may not work');
                add_action('admin_notices', function() use ($file) {
                    echo '<div class="notice notice-error"><p><strong>WSTG Error:</strong> Missing file: ' . esc_html($file) . '</p></div>';
                });
            }
        }
    }
    
    private function init_components() {
        // Initialize component classes
        if (class_exists('WSTG_Enhanced_DeepSeek_API')) {
            $this->deepseek_api = new WSTG_Enhanced_DeepSeek_API();
        }
        
        if (class_exists('WSTG_Enhanced_Content_Analyzer')) {
            $this->content_analyzer = new WSTG_Enhanced_Content_Analyzer($this->deepseek_api);
        }
        
        if (class_exists('WSTG_Taxonomy_Manager')) {
            $this->taxonomy_manager = new WSTG_Taxonomy_Manager();
        }
        
        if (class_exists('WSTG_Bulk_Processor')) {
            $this->bulk_processor = new WSTG_Bulk_Processor($this->content_analyzer, $this->taxonomy_manager);
        }
    }
    
    private function setup_hooks() {
        // Core WordPress hooks
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Add custom cron schedules
        add_filter('cron_schedules', array($this, 'add_cron_schedules'));
        
        // AJAX hooks
        add_action('wp_ajax_wstg_analyze_content', array($this, 'ajax_analyze_content'));
        add_action('wp_ajax_wstg_apply_analysis', array($this, 'ajax_apply_analysis'));
        add_action('wp_ajax_wstg_test_api', array($this, 'ajax_test_api'));
        
        // Let taxonomy manager handle its own hooks
        if ($this->taxonomy_manager) {
            $this->taxonomy_manager->init_hooks();
        }
        
        // Let bulk processor handle its own hooks  
        if ($this->bulk_processor) {
            $this->bulk_processor->init_hooks();
        }
    }
    
    public function init() {
        // Register taxonomies through taxonomy manager
        if ($this->taxonomy_manager) {
            $this->taxonomy_manager->register_taxonomies();
        }
    }
    
    public function admin_init() {
        // Register settings
        register_setting('wstg_settings_group', 'wstg_deepseek_api_key');
        register_setting('wstg_settings_group', 'wstg_ai_model', array('default' => 'deepseek-chat'));
        register_setting('wstg_settings_group', 'wstg_max_tags', array('default' => 6));
        register_setting('wstg_settings_group', 'wstg_max_entities', array('default' => 10));
        register_setting('wstg_settings_group', 'wstg_use_ai', array('default' => 1));
        register_setting('wstg_settings_group', 'wstg_arabic_support', array('default' => 1));
        register_setting('wstg_settings_group', 'wstg_orthodox_context', array('default' => 1));
        register_setting('wstg_settings_group', 'wstg_enable_tags', array('default' => 1));
        register_setting('wstg_settings_group', 'wstg_enable_entities', array('default' => 1));
        register_setting('wstg_settings_group', 'wstg_enable_topical_map', array('default' => 1));
        register_setting('wstg_settings_group', 'wstg_excluded_pages', array('default' => array()));
    }
    
    /**
     * FIXED: Add custom cron schedules
     */
    public function add_cron_schedules($schedules) {
        $schedules['wstg_process_interval'] = array(
            'interval' => 30, // 30 seconds between batches
            'display' => __('WSTG Process Interval', 'wp-smart-tag-generator')
        );
        return $schedules;
    }
    
    /**
     * Create independent "Lexonmy" admin menu section
     */
    public function add_admin_menu() {
        // Create independent "Lexonmy" top-level menu
        add_menu_page(
            __('Lexonmy - AI Content Analysis', 'wp-smart-tag-generator'), // Page title
            __('Lexonmy', 'wp-smart-tag-generator'), // Menu title
            'manage_options', // Capability
            'lexonmy-dashboard', // Menu slug
            array($this, 'lexonmy_dashboard_page'), // Callback
            'dashicons-analytics', // Icon
            58 // Position (after Settings which is 80)
        );
        
        // Add submenu pages under Lexonmy
        add_submenu_page(
            'lexonmy-dashboard', // Parent slug
            __('Dashboard', 'wp-smart-tag-generator'), // Page title
            __('Dashboard', 'wp-smart-tag-generator'), // Menu title
            'manage_options', // Capability
            'lexonmy-dashboard' // Menu slug (same as parent)
        );
        
        // Enhanced Bulk Content Analysis
        add_submenu_page(
            'lexonmy-dashboard',
            __('Bulk Content Analysis', 'wp-smart-tag-generator'),
            __('Bulk Analysis', 'wp-smart-tag-generator'),
            'manage_options',
            'lexonmy-bulk-analysis',
            array($this, 'bulk_analysis_page')
        );
        
        // Settings page under Lexonmy
        add_submenu_page(
            'lexonmy-dashboard',
            __('Lexonmy Settings', 'wp-smart-tag-generator'),
            __('Settings', 'wp-smart-tag-generator'),
            'manage_options',
            'lexonmy-settings',
            array($this, 'settings_page')
        );
        
        error_log('WSTG: Lexonmy menu created at ' . current_time('Y-m-d H:i:s') . ' UTC by ' . wp_get_current_user()->user_login);
    }
    
    /**
     * Lexonmy Dashboard Page
     */
    public function lexonmy_dashboard_page() {
        // Get comprehensive statistics
        $stats = $this->get_dashboard_statistics();
        ?>
        <div class="wrap">
            <h1><?php _e('🤖 Lexonmy - AI Content Analysis Dashboard', 'wp-smart-tag-generator'); ?></h1>
            
            <div class="notice notice-info">
                <p><strong>✅ System Status:</strong> Enhanced Edition Active! | 
                <strong>👤 User:</strong> <?php echo wp_get_current_user()->user_login; ?> | 
                <strong>🕐 Time:</strong> <?php echo current_time('Y-m-d H:i:s'); ?> UTC</p>
            </div>
            
            <!-- Quick Stats Overview -->
            <div class="dashboard-stats">
                <h2><?php _e('Content Analysis Overview', 'wp-smart-tag-generator'); ?></h2>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📄</div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $stats['total_content']; ?></div>
                            <div class="stat-label"><?php _e('Total Content', 'wp-smart-tag-generator'); ?></div>
                            <div class="stat-detail"><?php echo $stats['posts']; ?> posts, <?php echo $stats['pages']; ?> pages</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">👤</div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $stats['entities']; ?></div>
                            <div class="stat-label"><?php _e('Named Entities', 'wp-smart-tag-generator'); ?></div>
                            <div class="stat-detail"><?php echo $stats['orthodox_entities']; ?> Orthodox entities</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🗺️</div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $stats['topics']; ?></div>
                            <div class="stat-label"><?php _e('Topic Maps', 'wp-smart-tag-generator'); ?></div>
                            <div class="stat-detail"><?php echo $stats['topic_depth']; ?> levels deep</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🏷️</div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo $stats['tags']; ?></div>
                            <div class="stat-label"><?php _e('SEO Tags', 'wp-smart-tag-generator'); ?></div>
                            <div class="stat-detail"><?php echo $stats['tagged_posts']; ?> posts tagged</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="dashboard-actions">
                <h2><?php _e('Quick Actions', 'wp-smart-tag-generator'); ?></h2>
                
                <div class="action-buttons">
                    <a href="<?php echo admin_url('admin.php?page=lexonmy-bulk-analysis'); ?>" class="button button-primary button-large">
                        🚀 <?php _e('Start Bulk Analysis', 'wp-smart-tag-generator'); ?>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=lexonmy-settings'); ?>" class="button button-secondary">
                        ⚙️ <?php _e('Configure Settings', 'wp-smart-tag-generator'); ?>
                    </a>
                    
                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=wstg_named_entities'); ?>" class="button button-secondary">
                        👤 <?php _e('Manage Entities', 'wp-smart-tag-generator'); ?>
                    </a>
                    
                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=wstg_topics'); ?>" class="button button-secondary">
                        🗺️ <?php _e('Manage Topics', 'wp-smart-tag-generator'); ?>
                    </a>
                </div>
            </div>
            
            <!-- API Status -->
            <div class="api-status">
                <h2><?php _e('API Status', 'wp-smart-tag-generator'); ?></h2>
                
                <?php $api_key = get_option('wstg_deepseek_api_key', ''); ?>
                <?php if (!empty($api_key)): ?>
                    <div class="status-ok">
                        <p>✅ <?php _e('DeepSeek API configured and ready', 'wp-smart-tag-generator'); ?></p>
                        <button type="button" id="test-api-dashboard" class="button button-secondary">
                            <?php _e('Test Connection', 'wp-smart-tag-generator'); ?>
                        </button>
                        <div id="api-test-result-dashboard"></div>
                    </div>
                <?php else: ?>
                    <div class="status-warning">
                        <p>⚠️ <?php _e('DeepSeek API not configured', 'wp-smart-tag-generator'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=lexonmy-settings'); ?>" class="button button-primary">
                            <?php _e('Configure API Key', 'wp-smart-tag-generator'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .dashboard-stats, .dashboard-actions, .api-status { background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ddd; border-radius: 8px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { display: flex; align-items: center; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #0073aa; }
        .stat-icon { font-size: 32px; margin-right: 20px; }
        .stat-number { font-size: 28px; font-weight: bold; color: #333; }
        .stat-label { font-size: 14px; font-weight: 600; color: #666; margin: 5px 0; }
        .stat-detail { font-size: 12px; color: #888; }
        .action-buttons { display: flex; gap: 15px; flex-wrap: wrap; margin: 20px 0; }
        .action-buttons .button { padding: 10px 20px; }
        .status-ok { color: #0f5132; background: #d1e7dd; padding: 15px; border-radius: 6px; }
        .status-warning { color: #664d03; background: #fff3cd; padding: 15px; border-radius: 6px; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-api-dashboard').on('click', function() {
                var button = $(this);
                var result = $('#api-test-result-dashboard');
                
                button.prop('disabled', true).text('Testing...');
                result.html('<p>Testing API connection...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wstg_test_api',
                        nonce: '<?php echo wp_create_nonce('wstg_test_api'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            result.html('<p style="color: green;">✅ ' + response.data + '</p>');
                        } else {
                            result.html('<p style="color: red;">❌ ' + response.data + '</p>');
                        }
                    },
                    error: function() {
                        result.html('<p style="color: red;">❌ Request failed</p>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Test Connection');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Bulk Analysis Page (delegated to bulk processor)
     */
    public function bulk_analysis_page() {
        if ($this->bulk_processor) {
            $this->bulk_processor->bulk_processing_page();
        } else {
            echo '<div class="wrap"><h1>Bulk Analysis</h1><p>Bulk processor not available.</p></div>';
        }
    }
    
    public function add_meta_boxes() {
        $post_types = array('post', 'page');
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'wstg_content_analyzer',
                __('AI Content Analyzer (Orthodox Edition)', 'wp-smart-tag-generator'),
                array($this, 'meta_box_callback'),
                $post_type,
                'side',
                'default'
            );
        }
    }
    
    public function meta_box_callback($post) {
        wp_nonce_field('wstg_meta_box', 'wstg_meta_box_nonce');
        
        $use_ai = get_option('wstg_use_ai', 1);
        $api_key = get_option('wstg_deepseek_api_key', '');
        $orthodox_context = get_option('wstg_orthodox_context', 1);
        $enable_tags = get_option('wstg_enable_tags', 1);
        $enable_entities = get_option('wstg_enable_entities', 1);
        $enable_topical_map = get_option('wstg_enable_topical_map', 1);
        
        // Get existing analysis data from taxonomy manager
        $existing_data = array();
        if ($this->taxonomy_manager) {
            $existing_data = $this->taxonomy_manager->get_post_analysis_summary($post->ID);
        }
        
        include WSTG_PLUGIN_DIR . 'templates/meta-box.php';
    }
    
    public function settings_page() {
        include WSTG_PLUGIN_DIR . 'templates/settings-page.php';
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook && 
            strpos($hook, 'lexonmy') === false) {
            return;
        }
        
        wp_enqueue_style(
            'wstg-admin',
            WSTG_PLUGIN_URL . 'assets/css/admin-enhanced.css',
            array(),
            WSTG_VERSION
        );
        
        wp_enqueue_script(
            'wstg-admin',
            WSTG_PLUGIN_URL . 'assets/js/admin-enhanced.js',
            array('jquery'),
            WSTG_VERSION,
            true
        );
        
        wp_localize_script('wstg-admin', 'wstg_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wstg_ajax_nonce'),
            'messages' => array(
                'error' => __('An error occurred. Please try again.', 'wp-smart-tag-generator'),
                'success' => __('Analysis applied successfully!', 'wp-smart-tag-generator'),
                'analyzing' => __('AI is analyzing your content...', 'wp-smart-tag-generator'),
                'no_selection' => __('Please select at least one analysis option.', 'wp-smart-tag-generator'),
            )
        ));
    }
    
    // AJAX Handlers - delegate to appropriate components
    public function ajax_analyze_content() {
        if ($this->content_analyzer) {
            $this->content_analyzer->handle_ajax_analyze();
        } else {
            wp_send_json_error(__('Content analyzer not available.', 'wp-smart-tag-generator'));
        }
    }
    
    public function ajax_apply_analysis() {
        if ($this->content_analyzer && $this->taxonomy_manager) {
            $this->content_analyzer->handle_ajax_apply($this->taxonomy_manager);
        } else {
            wp_send_json_error(__('Analysis components not available.', 'wp-smart-tag-generator'));
        }
    }
    
    public function ajax_test_api() {
        if ($this->deepseek_api) {
            $result = $this->deepseek_api->test_connection();
            if ($result['success']) {
                wp_send_json_success($result['message']);
            } else {
                wp_send_json_error($result['message']);
            }
        } else {
            wp_send_json_error(__('DeepSeek API not available.', 'wp-smart-tag-generator'));
        }
    }
    
    private function get_dashboard_statistics() {
        return array(
            'total_content' => wp_count_posts('post')->publish + wp_count_posts('page')->publish,
            'posts' => wp_count_posts('post')->publish,
            'pages' => wp_count_posts('page')->publish,
            'entities' => wp_count_terms('wstg_named_entities') ?: 0,
            'topics' => wp_count_terms('wstg_topics') ?: 0,
            'tags' => wp_count_terms('post_tag') ?: 0,
            'orthodox_entities' => 0, // Calculate based on meta
            'topic_depth' => 3, // Calculate based on hierarchy
            'tagged_posts' => 0 // Calculate based on posts with tags
        );
    }
    
    public function activate() {
        // Set default options
        add_option('wstg_version', WSTG_VERSION);
        add_option('wstg_deepseek_api_key', '');
        add_option('wstg_ai_model', 'deepseek-chat');
        add_option('wstg_max_tags', 6);
        add_option('wstg_max_entities', 10);
        add_option('wstg_use_ai', 1);
        add_option('wstg_arabic_support', 1);
        add_option('wstg_orthodox_context', 1);
        add_option('wstg_enable_tags', 1);
        add_option('wstg_enable_entities', 1);
        add_option('wstg_enable_topical_map', 1);
        add_option('wstg_excluded_pages', array());
        
        // Let taxonomy manager handle activation
        if ($this->taxonomy_manager) {
            $this->taxonomy_manager->on_activation();
        }
        
        // Let bulk processor handle activation
        if ($this->bulk_processor) {
            $this->bulk_processor->on_activation();
        }
        
        error_log('WSTG: Plugin activated at ' . current_time('Y-m-d H:i:s') . ' UTC by ' . wp_get_current_user()->user_login);
    }
    
    public function deactivate() {
        error_log('WSTG: Plugin deactivated at ' . current_time('Y-m-d H:i:s') . ' UTC by ' . wp_get_current_user()->user_login);
    }
}

// Initialize the plugin
WP_Smart_Tag_Generator_Enhanced::get_instance();