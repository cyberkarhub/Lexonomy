<?php
/**
 * Settings Page Template
 * 
 * @package WP_Smart_Tag_Generator_Enhanced
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['wstg_settings_nonce'], 'wstg_save_settings')) {
    update_option('wstg_deepseek_api_key', sanitize_text_field($_POST['deepseek_api_key']));
    update_option('wstg_ai_model', sanitize_text_field($_POST['ai_model']));
    update_option('wstg_max_tags', intval($_POST['max_tags']));
    update_option('wstg_max_entities', intval($_POST['max_entities']));
    update_option('wstg_use_ai', isset($_POST['use_ai']) ? 1 : 0);
    update_option('wstg_arabic_support', isset($_POST['arabic_support']) ? 1 : 0);
    update_option('wstg_orthodox_context', isset($_POST['orthodox_context']) ? 1 : 0);
    update_option('wstg_enable_tags', isset($_POST['enable_tags']) ? 1 : 0);
    update_option('wstg_enable_entities', isset($_POST['enable_entities']) ? 1 : 0);
    update_option('wstg_enable_topical_map', isset($_POST['enable_topical_map']) ? 1 : 0);
    
    // Handle excluded pages
    $excluded_pages = isset($_POST['excluded_pages']) ? array_map('intval', $_POST['excluded_pages']) : array();
    update_option('wstg_excluded_pages', $excluded_pages);
    
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'wp-smart-tag-generator') . '</p></div>';
}

// Get current settings
$api_key = get_option('wstg_deepseek_api_key', '');
$ai_model = get_option('wstg_ai_model', 'deepseek-chat');
$max_tags = get_option('wstg_max_tags', 6);
$max_entities = get_option('wstg_max_entities', 10);
$use_ai = get_option('wstg_use_ai', 1);
$arabic_support = get_option('wstg_arabic_support', 1);
$orthodox_context = get_option('wstg_orthodox_context', 1);
$enable_tags = get_option('wstg_enable_tags', 1);
$enable_entities = get_option('wstg_enable_entities', 1);
$enable_topical_map = get_option('wstg_enable_topical_map', 1);
$excluded_pages = get_option('wstg_excluded_pages', array());

// Get statistics
$total_entities = wp_count_terms('wstg_named_entities');
$total_topics = wp_count_terms('wstg_topics');
$total_tags = wp_count_terms('post_tag');
?>

<div class="wrap">
    <h1><?php _e('Smart Content Analyzer - Enhanced Settings', 'wp-smart-tag-generator'); ?></h1>
    
    <div class="notice notice-info">
        <p><strong>✅ Plugin Status:</strong> Enhanced Edition Active!</p>
        <p><strong>📊 Current Stats:</strong> 
            <?php echo $total_entities; ?> entities, 
            <?php echo $total_topics; ?> topics, 
            <?php echo $total_tags; ?> tags
        </p>
        <p><strong>👤 Current User:</strong> cyberkarhub | <strong>🕐 Time:</strong> 2025-05-30 15:02:00 UTC</p>
    </div>
    
    <div class="nav-tab-wrapper">
        <a href="#general" class="nav-tab nav-tab-active" onclick="switchTab(event, 'general')">⚙️ General</a>
        <a href="#ai" class="nav-tab" onclick="switchTab(event, 'ai')">🤖 AI Settings</a>
        <a href="#orthodox" class="nav-tab" onclick="switchTab(event, 'orthodox')">☦️ Orthodox</a>
        <a href="#exclusions" class="nav-tab" onclick="switchTab(event, 'exclusions')">🚫 Exclusions</a>
        <a href="#advanced" class="nav-tab" onclick="switchTab(event, 'advanced')">🔧 Advanced</a>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('wstg_save_settings', 'wstg_settings_nonce'); ?>
        
        <!-- General Tab -->
        <div id="general" class="tab-content">
            <h2><?php _e('General Settings', 'wp-smart-tag-generator'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Analysis Features', 'wp-smart-tag-generator'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="enable_tags" value="1" <?php checked($enable_tags, 1); ?>>
                                <?php _e('🏷️ Generate SEO Tags', 'wp-smart-tag-generator'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="enable_entities" value="1" <?php checked($enable_entities, 1); ?>>
                                <?php _e('👤 Extract Named Entities', 'wp-smart-tag-generator'); ?>
                            </label><br>
                            <label>
                                <input type="checkbox" name="enable_topical_map" value="1" <?php checked($enable_topical_map, 1); ?>>
                                <?php _e('🗺️ Generate Topical Maps', 'wp-smart-tag-generator'); ?>
                            </label>
                        </fieldset>
                        <p class="description"><?php _e('Choose which types of analysis to perform on your content', 'wp-smart-tag-generator'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Maximum Tags', 'wp-smart-tag-generator'); ?></th>
                    <td>
                        <input type="number" name="max_tags" value="<?php echo esc_attr($max_tags); ?>" min="3" max="15" class="small-text">
                        <p class="description"><?php _e('Maximum number of tags to generate per post', 'wp-smart-tag-generator'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Maximum Named Entities', 'wp-smart-tag-generator'); ?></th>
                    <td>
                        <input type="number" name="max_entities" value="<?php echo esc_attr($max_entities); ?>" min="5" max="20" class="small-text">
                        <p class="description"><?php _e('Maximum number of entities to extract per post', 'wp-smart-tag-generator'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- AI Settings Tab -->
        <div id="ai" class="tab-content" style="display: none;">
            <h2><?php _e('AI Configuration', 'wp-smart-tag-generator'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Use AI Analysis', 'wp-smart-tag-generator'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="use_ai" value="1" <?php checked($use_ai, 1); ?>>
                            <?php _e('Enable AI-powered content analysis', 'wp-smart-tag-generator'); ?>
                        </label>
                        <p class="description"><?php _e('Requires DeepSeek API key. Falls back to basic analysis if disabled.', 'wp-smart-tag-generator'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('DeepSeek API Key', 'wp-smart-tag-generator'); ?></th>
                    <td>
                        <input type="password" name="deepseek_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="sk-...">
                        <button type="button" id="toggle-api-key" class="button button-secondary"><?php _e('Show/Hide', 'wp-smart-tag-generator'); ?></button>
                        <p class="description">
                            <?php _e('Get your API key from', 'wp-smart-tag-generator'); ?> 
                            <a href="https://platform.deepseek.com/api_keys" target="_blank">DeepSeek Platform</a>
                            <?php if (!empty($api_key)): ?>
                                <br><span style="color: green;">✅ API Key configured</span>
                            <?php else: ?>
                                <br><span style="color: red;">❌ API Key not configured</span>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('AI Model', 'wp-smart-tag-generator'); ?></th>
                    <td>
                        <select name="ai_model">
                            <option value="deepseek-chat" <?php selected($ai_model, 'deepseek-chat'); ?>>DeepSeek Chat (Recommended)</option>
                            <option value="deepseek-coder" <?php selected($ai_model, 'deepseek-coder'); ?>>DeepSeek Coder</option>
                        </select>
                        <p class="description"><?php _e('DeepSeek Chat is optimized for content analysis', 'wp-smart-tag-generator'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('API Test', 'wp-smart-tag-generator'); ?></th>
                    <td>
                        <button type="button" id="test-api" class="button button-secondary"><?php _e('Test API Connection', 'wp-smart-tag-generator'); ?></button>
                        <div id="api-test-result"></div>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Orthodox Settings Tab -->
        <div id="orthodox" class="tab-content" style="display: none;">
            <h2><?php _e('Orthodox Christian Context', 'wp-smart-tag-generator'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Orthodox Context', 'wp-smart-tag-generator'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="orthodox_context" value="1" <?php checked($orthodox_context, 1); ?>>
                            <?php _e('Enable Orthodox Christian theological context in AI analysis', 'wp-smart-tag-generator'); ?>
                        </label>
                        <p class="description">
                            <?php _e('AI will consider Orthodox theology, saints, liturgy, church fathers, and traditions when analyzing content.', 'wp-smart-tag-generator'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Arabic Language Support', 'wp-smart-tag-generator'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="arabic_support" value="1" <?php checked($arabic_support, 1); ?>>
                            <?php _e('🇸🇦 Enable enhanced Arabic language detection and processing', 'wp-smart-tag-generator'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Includes automatic Arabic content detection and Arabic-English bilingual content handling.', 'wp-smart-tag-generator'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Exclusions Tab -->
        <div id="exclusions" class="tab-content" style="display: none;">
            <h2><?php _e('Page Exclusions', 'wp-smart-tag-generator'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Excluded Pages', 'wp-smart-tag-generator'); ?></th>
                    <td>
                        <select name="excluded_pages[]" multiple style="width: 100%; height: 200px;" id="excluded-pages-select">
                            <?php
                            $pages = get_pages(array('number' => 0));
                            foreach ($pages as $page) {
                                $selected = in_array($page->ID, $excluded_pages) ? 'selected' : '';
                                echo '<option value="' . $page->ID . '" ' . $selected . '>';
                                echo esc_html($page->post_title) . ' (ID: ' . $page->ID . ')';
                                echo '</option>';
                            }
                            ?>
                        </select>
                        <p class="description">
                            <?php _e('Hold Ctrl/Cmd to select multiple pages to exclude from bulk analysis.', 'wp-smart-tag-generator'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Advanced Tab -->
        <div id="advanced" class="tab-content" style="display: none;">
            <h2><?php _e('Advanced Settings', 'wp-smart-tag-generator'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Plugin Information', 'wp-smart-tag-generator'); ?></th>
                    <td>
                        <p><strong>Version:</strong> <?php echo WSTG_VERSION; ?></p>
                        <p><strong>Plugin Directory:</strong> <?php echo esc_html(WSTG_PLUGIN_DIR); ?></p>
                        <p><strong>Database Tables:</strong> 
                            <?php 
                            global $wpdb;
                            echo $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->terms}'") ? '✅' : '❌';
                            echo ' WordPress Terms System';
                            ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('System Status', 'wp-smart-tag-generator'); ?></th>
                    <td>
                        <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?> <?php echo version_compare(PHP_VERSION, '7.4', '>=') ? '✅' : '❌'; ?></p>
                        <p><strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?> <?php echo version_compare(get_bloginfo('version'), '5.0', '>=') ? '✅' : '❌'; ?></p>
                        <p><strong>cURL Extension:</strong> <?php echo function_exists('curl_init') ? '✅ Available' : '❌ Missing'; ?></p>
                        <p><strong>JSON Extension:</strong> <?php echo function_exists('json_encode') ? '✅ Available' : '❌ Missing'; ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <?php submit_button(__('Save All Settings', 'wp-smart-tag-generator'), 'primary', 'submit', true, array('style' => 'margin-top: 20px;')); ?>
    </form>
</div>

<style>
.nav-tab-wrapper { margin-bottom: 20px; }
.tab-content { background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-top: none; }
.nav-tab.nav-tab-active { background: #fff; border-bottom: 1px solid #fff; }
.form-table th { width: 220px; }
#excluded-pages-select option { padding: 5px; }
</style>

<script>
function switchTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("nav-tab");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" nav-tab-active", "");
    }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " nav-tab-active";
}

jQuery(document).ready(function($) {
    $('#toggle-api-key').on('click', function() {
        var input = $('input[name="deepseek_api_key"]');
        input.attr('type', input.attr('type') === 'password' ? 'text' : 'password');
    });
    
    $('#test-api').on('click', function() {
        var button = $(this);
        var result = $('#api-test-result');
        
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
                    result.html('<div class="notice notice-success inline"><p>✅ ' + response.data + '</p></div>');
                } else {
                    result.html('<div class="notice notice-error inline"><p>❌ ' + response.data + '</p></div>');
                }
            },
            error: function() {
                result.html('<div class="notice notice-error inline"><p>❌ Request failed</p></div>');
            },
            complete: function() {
                button.prop('disabled', false).text('Test API Connection');
            }
        });
    });
});
</script>