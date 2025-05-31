<?php
/**
 * Taxonomy Manager - FIXED VERSION
 * Handles custom taxonomies, entity management, and data organization
 * 
 * @package WP_Smart_Tag_Generator_Enhanced
 * @since 3.1.0
 * @fixed 2025-05-30 16:58:28 UTC by cyberkarhub
 */

if (!defined('ABSPATH')) {
    exit;
}

class WSTG_Taxonomy_Manager {
    
    public function __construct() {
        // Constructor - setup will be done through init_hooks()
    }
    
    public function init_hooks() {
        // Taxonomy hooks - FIX: Add missing method checks
        add_action('wstg_named_entities_add_form_fields', array($this, 'add_entity_meta_fields'));
        add_action('wstg_named_entities_edit_form_fields', array($this, 'edit_entity_meta_fields'));
        add_action('wstg_topics_add_form_fields', array($this, 'add_topic_meta_fields'));
        add_action('wstg_topics_edit_form_fields', array($this, 'edit_topic_meta_fields'));
        add_action('created_wstg_named_entities', array($this, 'save_entity_meta'));
        add_action('edited_wstg_named_entities', array($this, 'save_entity_meta'));
        add_action('created_wstg_topics', array($this, 'save_topic_meta'));
        add_action('edited_wstg_topics', array($this, 'save_topic_meta'));
        
        // AJAX hooks for entity management
        add_action('wp_ajax_wstg_delete_entity', array($this, 'ajax_delete_entity'));
        add_action('wp_ajax_wstg_merge_entities', array($this, 'ajax_merge_entities'));
        add_action('wp_ajax_wstg_get_similar_entities', array($this, 'ajax_get_similar_entities'));
        add_action('wp_ajax_wstg_export_entities', array($this, 'ajax_export_entities'));
        
        // Admin columns
        add_filter('manage_posts_columns', array($this, 'add_analysis_columns'));
        add_filter('manage_pages_columns', array($this, 'add_analysis_columns'));
        add_action('manage_posts_custom_column', array($this, 'display_analysis_columns'), 10, 2);
        add_action('manage_pages_custom_column', array($this, 'display_analysis_columns'), 10, 2);
        
        error_log('WSTG Taxonomy Manager: Hooks initialized at' .current_time('Y-m-d H:i:s') . 'UTC by '  . wp_get_current_user()->user_login);
    }
    
    // REMOVED: add_admin_pages() - Will be handled by main plugin class for Lexonmy menu
    
    /**
     * Register custom taxonomies - FIXED: Removed icons from labels
     */
    public function register_taxonomies() {
        // Named Entities Taxonomy - CLEAN LABELS WITHOUT ICONS
        register_taxonomy('wstg_named_entities', array('post', 'page'), array(
            'labels' => array(
                'name' => __('Named Entities', 'wp-smart-tag-generator'),
                'singular_name' => __('Named Entity', 'wp-smart-tag-generator'),
                'menu_name' => __('Named Entities', 'wp-smart-tag-generator'), // REMOVED ICON
                'all_items' => __('All Entities', 'wp-smart-tag-generator'),
                'edit_item' => __('Edit Entity', 'wp-smart-tag-generator'),
                'view_item' => __('View Entity', 'wp-smart-tag-generator'),
                'update_item' => __('Update Entity', 'wp-smart-tag-generator'),
                'add_new_item' => __('Add New Entity', 'wp-smart-tag-generator'),
                'new_item_name' => __('New Entity Name', 'wp-smart-tag-generator'),
                'search_items' => __('Search Entities', 'wp-smart-tag-generator'),
                'popular_items' => __('Popular Entities', 'wp-smart-tag-generator'),
                'not_found' => __('No entities found', 'wp-smart-tag-generator'),
                'separate_items_with_commas' => __('Separate entities with commas', 'wp-smart-tag-generator'),
                'add_or_remove_items' => __('Add or remove entities', 'wp-smart-tag-generator'),
                'choose_from_most_used' => __('Choose from most used entities', 'wp-smart-tag-generator'),
            ),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'show_in_rest' => true,
            'description' => __('People, places, organizations, saints, and concepts extracted from content', 'wp-smart-tag-generator'),
            'capabilities' => array(
                'manage_terms' => 'manage_categories',
                'edit_terms' => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_posts',
            ),
        ));
        
        // Topics Taxonomy - CLEAN LABELS WITHOUT ICONS
        register_taxonomy('wstg_topics', array('post', 'page'), array(
            'labels' => array(
                'name' => __('Content Topics', 'wp-smart-tag-generator'),
                'singular_name' => __('Topic', 'wp-smart-tag-generator'),
                'menu_name' => __('Content Topics', 'wp-smart-tag-generator'), // REMOVED ICON
                'all_items' => __('All Topics', 'wp-smart-tag-generator'),
                'parent_item' => __('Parent Topic', 'wp-smart-tag-generator'),
                'parent_item_colon' => __('Parent Topic:', 'wp-smart-tag-generator'),
                'edit_item' => __('Edit Topic', 'wp-smart-tag-generator'),
                'view_item' => __('View Topic', 'wp-smart-tag-generator'),
                'update_item' => __('Update Topic', 'wp-smart-tag-generator'),
                'add_new_item' => __('Add New Topic', 'wp-smart-tag-generator'),
                'new_item_name' => __('New Topic Name', 'wp-smart-tag-generator'),
                'search_items' => __('Search Topics', 'wp-smart-tag-generator'),
                'not_found' => __('No topics found', 'wp-smart-tag-generator'),
            ),
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => false,
            'show_in_rest' => true,
            'description' => __('Hierarchical topic structure extracted from content analysis', 'wp-smart-tag-generator'),
            'capabilities' => array(
                'manage_terms' => 'manage_categories',
                'edit_terms' => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_posts',
            ),
        ));
        
        error_log('WSTG Taxonomy Manager: Taxonomies registered at' .current_time('Y-m-d H:i:s') . 'UTC by '  . wp_get_current_user()->user_login);
    }
    
    /**
     * Apply analysis results to post taxonomies
     */
    public function apply_analysis_to_post($post_id, $analysis_data, $mode = 'append') {
        $results = array('success' => true, 'applied' => array(), 'errors' => array());
        
        try {
            // Apply tags to WordPress tags taxonomy
            if (!empty($analysis_data['tags'])) {
                $tag_result = $this->apply_tags_to_post($post_id, $analysis_data['tags'], $mode);
                if ($tag_result['success']) {
                    $results['applied']['tags'] = $tag_result['applied'];
                } else {
                    $results['errors']['tags'] = $tag_result['message'];
                }
            }
            
            // Apply named entities to custom taxonomy
            if (!empty($analysis_data['entities'])) {
                $entity_result = $this->apply_entities_to_post($post_id, $analysis_data['entities'], $mode);
                if ($entity_result['success']) {
                    $results['applied']['entities'] = $entity_result['applied'];
                } else {
                    $results['errors']['entities'] = $entity_result['message'];
                }
            }
            
            // Apply topical map to hierarchical taxonomy
            if (!empty($analysis_data['topical_map'])) {
                $topic_result = $this->apply_topics_to_post($post_id, $analysis_data['topical_map'], $mode);
                if ($topic_result['success']) {
                    $results['applied']['topics'] = $topic_result['applied'];
                } else {
                    $results['errors']['topics'] = $topic_result['message'];
                }
            }
            
            error_log('WSTG Taxonomy Manager: Applied analysis to post ' . $post_id . ' at' .current_time('Y-m-d H:i:s') . 'UTC by '  . wp_get_current_user()->user_login);
            return $results;
            
        } catch (Exception $e) {
            error_log('WSTG Taxonomy Manager: Error applying analysis to post ' . $post_id . ': ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => __('Failed to apply analysis: ', 'wp-smart-tag-generator') . $e->getMessage()
            );
        }
    }
    
    private function apply_tags_to_post($post_id, $tags, $mode) {
        try {
            if ($mode === 'replace') {
                $result = wp_set_post_tags($post_id, $tags, false);
            } else {
                $existing_tags = wp_get_post_tags($post_id, array('fields' => 'names'));
                $all_tags = array_unique(array_merge($existing_tags, $tags));
                $result = wp_set_post_tags($post_id, $all_tags, false);
            }
            
            if (is_wp_error($result)) {
                return array('success' => false, 'message' => $result->get_error_message());
            }
            
            return array('success' => true, 'applied' => $tags);
            
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
    
    private function apply_entities_to_post($post_id, $entities, $mode) {
        try {
            $entity_terms = array();
            
            foreach ($entities as $entity) {
                $entity_name = is_array($entity) ? $entity['name'] : $entity;
                $entity_type = is_array($entity) ? ($entity['type'] ?? 'general') : 'general';
                $entity_category = is_array($entity) ? ($entity['category'] ?? 'general') : 'general';
                $entity_description = is_array($entity) ? ($entity['description'] ?? '') : '';
                
                // Check if entity already exists
                $existing_term = get_term_by('name', $entity_name, 'wstg_named_entities');
                
                if ($existing_term) {
                    $term_id = $existing_term->term_id;
                } else {
                    // Create new entity
                    $term_result = wp_insert_term($entity_name, 'wstg_named_entities');
                    if (is_wp_error($term_result)) {
                        continue; // Skip this entity
                    }
                    $term_id = $term_result['term_id'];
                    
                    // Add metadata
                    update_term_meta($term_id, 'entity_type', $entity_type);
                    update_term_meta($term_id, 'entity_category', $entity_category);
                    update_term_meta($term_id, 'entity_description', $entity_description);
                    update_term_meta($term_id, 'created_by', wp_get_current_user()->user_login);
                    update_term_meta($term_id, 'created_at', current_time('mysql'));
                }
                
                $entity_terms[] = $term_id;
            }
            
            if (!empty($entity_terms)) {
                $append = ($mode !== 'replace');
                $result = wp_set_post_terms($post_id, $entity_terms, 'wstg_named_entities', $append);
                
                if (is_wp_error($result)) {
                    return array('success' => false, 'message' => $result->get_error_message());
                }
            }
            
            return array('success' => true, 'applied' => $entities);
            
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
    
    private function apply_topics_to_post($post_id, $topical_map, $mode) {
        try {
            $topic_terms = array();
            
            foreach ($topical_map as $topic_data) {
                $topic_name = is_array($topic_data) ? $topic_data['topic'] : $topic_data;
                $subtopics = is_array($topic_data) ? ($topic_data['subtopics'] ?? array()) : array();
                $relevance = is_array($topic_data) ? ($topic_data['relevance'] ?? '') : '';
                
                // Create main topic
                $main_topic_id = $this->create_or_get_topic($topic_name, 0, $relevance);
                if ($main_topic_id) {
                    $topic_terms[] = $main_topic_id;
                    
                    // Create subtopics
                    foreach ($subtopics as $subtopic) {
                        $subtopic_id = $this->create_or_get_topic($subtopic, $main_topic_id, '');
                        if ($subtopic_id) {
                            $topic_terms[] = $subtopic_id;
                        }
                    }
                }
            }
            
            if (!empty($topic_terms)) {
                $append = ($mode !== 'replace');
                $result = wp_set_post_terms($post_id, $topic_terms, 'wstg_topics', $append);
                
                if (is_wp_error($result)) {
                    return array('success' => false, 'message' => $result->get_error_message());
                }
            }
            
            return array('success' => true, 'applied' => $topical_map);
            
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
    
    private function create_or_get_topic($topic_name, $parent_id = 0, $relevance = '') {
        // Check if topic already exists
        $existing_term = get_term_by('name', $topic_name, 'wstg_topics');
        
        if ($existing_term) {
            return $existing_term->term_id;
        }
        
        // Create new topic
        $term_result = wp_insert_term($topic_name, 'wstg_topics', array(
            'parent' => $parent_id
        ));
        
        if (is_wp_error($term_result)) {
            return false;
        }
        
        $term_id = $term_result['term_id'];
        
        // Add metadata
        if ($relevance) {
            update_term_meta($term_id, 'topic_relevance', $relevance);
        }
        update_term_meta($term_id, 'created_by', wp_get_current_user()->user_login);
        update_term_meta($term_id, 'created_at', current_time('mysql'));
        
        return $term_id;
    }
    
    /**
     * Get analysis summary for a post
     */
    public function get_post_analysis_summary($post_id) {
        return array(
            'tags' => wp_get_post_tags($post_id, array('fields' => 'names')),
            'entities' => wp_get_post_terms($post_id, 'wstg_named_entities', array('fields' => 'names')),
            'topics' => wp_get_post_terms($post_id, 'wstg_topics', array('fields' => 'names')),
            'last_analyzed' => get_post_meta($post_id, '_wstg_last_analyzed', true),
            'analyzed_by' => get_post_meta($post_id, '_wstg_analyzed_by', true)
        );
    }
    
    // FIX: Add all missing methods that were causing fatal errors
    
    /**
     * MISSING METHOD FIX: Add entity meta fields
     */
    public function add_entity_meta_fields($taxonomy) {
        ?>
        <div class="form-field">
            <label for="entity_type"><?php _e('Entity Type', 'wp-smart-tag-generator'); ?></label>
            <select name="entity_type" id="entity_type">
                <option value="person"><?php _e('Person', 'wp-smart-tag-generator'); ?></option>
                <option value="saint"><?php _e('Saint', 'wp-smart-tag-generator'); ?></option>
                <option value="place"><?php _e('Place', 'wp-smart-tag-generator'); ?></option>
                <option value="organization"><?php _e('Organization', 'wp-smart-tag-generator'); ?></option>
                <option value="concept"><?php _e('Concept', 'wp-smart-tag-generator'); ?></option>
            </select>
            <p><?php _e('Select the type of entity this represents.', 'wp-smart-tag-generator'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="entity_category"><?php _e('Category', 'wp-smart-tag-generator'); ?></label>
            <select name="entity_category" id="entity_category">
                <option value="general"><?php _e('General', 'wp-smart-tag-generator'); ?></option>
                <option value="orthodox"><?php _e('Orthodox', 'wp-smart-tag-generator'); ?></option>
                <option value="biblical"><?php _e('Biblical', 'wp-smart-tag-generator'); ?></option>
            </select>
            <p><?php _e('Categorize this entity by context.', 'wp-smart-tag-generator'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="entity_description"><?php _e('Description', 'wp-smart-tag-generator'); ?></label>
            <textarea name="entity_description" id="entity_description" rows="3" cols="50"></textarea>
            <p><?php _e('Brief description of this entity.', 'wp-smart-tag-generator'); ?></p>
        </div>
        <?php
    }
    
    /**
     * MISSING METHOD FIX: Edit entity meta fields
     */
    public function edit_entity_meta_fields($term, $taxonomy) {
        $entity_type = get_term_meta($term->term_id, 'entity_type', true);
        $entity_category = get_term_meta($term->term_id, 'entity_category', true);
        $entity_description = get_term_meta($term->term_id, 'entity_description', true);
        ?>
        <tr class="form-field">
            <th scope="row"><label for="entity_type"><?php _e('Entity Type', 'wp-smart-tag-generator'); ?></label></th>
            <td>
                <select name="entity_type" id="entity_type">
                    <option value="person" <?php selected($entity_type, 'person'); ?>><?php _e('Person', 'wp-smart-tag-generator'); ?></option>
                    <option value="saint" <?php selected($entity_type, 'saint'); ?>><?php _e('Saint', 'wp-smart-tag-generator'); ?></option>
                    <option value="place" <?php selected($entity_type, 'place'); ?>><?php _e('Place', 'wp-smart-tag-generator'); ?></option>
                    <option value="organization" <?php selected($entity_type, 'organization'); ?>><?php _e('Organization', 'wp-smart-tag-generator'); ?></option>
                    <option value="concept" <?php selected($entity_type, 'concept'); ?>><?php _e('Concept', 'wp-smart-tag-generator'); ?></option>
                </select>
                <p class="description"><?php _e('Select the type of entity this represents.', 'wp-smart-tag-generator'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row"><label for="entity_category"><?php _e('Category', 'wp-smart-tag-generator'); ?></label></th>
            <td>
                <select name="entity_category" id="entity_category">
                    <option value="general" <?php selected($entity_category, 'general'); ?>><?php _e('General', 'wp-smart-tag-generator'); ?></option>
                    <option value="orthodox" <?php selected($entity_category, 'orthodox'); ?>><?php _e('Orthodox', 'wp-smart-tag-generator'); ?></option>
                    <option value="biblical" <?php selected($entity_category, 'biblical'); ?>><?php _e('Biblical', 'wp-smart-tag-generator'); ?></option>
                </select>
                <p class="description"><?php _e('Categorize this entity by context.', 'wp-smart-tag-generator'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row"><label for="entity_description"><?php _e('Description', 'wp-smart-tag-generator'); ?></label></th>
            <td>
                <textarea name="entity_description" id="entity_description" rows="3" cols="50"><?php echo esc_textarea($entity_description); ?></textarea>
                <p class="description"><?php _e('Brief description of this entity.', 'wp-smart-tag-generator'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * MISSING METHOD FIX: Add topic meta fields
     */
    public function add_topic_meta_fields($taxonomy) {
        ?>
        <div class="form-field">
            <label for="topic_relevance"><?php _e('Relevance', 'wp-smart-tag-generator'); ?></label>
            <textarea name="topic_relevance" id="topic_relevance" rows="3" cols="50"></textarea>
            <p><?php _e('Describe why this topic is relevant to the content.', 'wp-smart-tag-generator'); ?></p>
        </div>
        <?php
    }
    
    /**
     * MISSING METHOD FIX: Edit topic meta fields
     */
    public function edit_topic_meta_fields($term, $taxonomy) {
        $topic_relevance = get_term_meta($term->term_id, 'topic_relevance', true);
        ?>
        <tr class="form-field">
            <th scope="row"><label for="topic_relevance"><?php _e('Relevance', 'wp-smart-tag-generator'); ?></label></th>
            <td>
                <textarea name="topic_relevance" id="topic_relevance" rows="3" cols="50"><?php echo esc_textarea($topic_relevance); ?></textarea>
                <p class="description"><?php _e('Describe why this topic is relevant to the content.', 'wp-smart-tag-generator'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * MISSING METHOD FIX: Save entity meta
     */
    public function save_entity_meta($term_id) {
        if (isset($_POST['entity_type'])) {
            update_term_meta($term_id, 'entity_type', sanitize_text_field($_POST['entity_type']));
        }
        if (isset($_POST['entity_category'])) {
            update_term_meta($term_id, 'entity_category', sanitize_text_field($_POST['entity_category']));
        }
        if (isset($_POST['entity_description'])) {
            update_term_meta($term_id, 'entity_description', sanitize_textarea_field($_POST['entity_description']));
        }
    }
    
    /**
     * MISSING METHOD FIX: Save topic meta
     */
    public function save_topic_meta($term_id) {
        if (isset($_POST['topic_relevance'])) {
            update_term_meta($term_id, 'topic_relevance', sanitize_textarea_field($_POST['topic_relevance']));
        }
    }
    
    /**
     * MISSING METHOD FIX: Add analysis columns
     */
    public function add_analysis_columns($columns) {
        $columns['wstg_analysis'] = __('AI Analysis', 'wp-smart-tag-generator');
        return $columns;
    }
    
    /**
     * MISSING METHOD FIX: Display analysis columns
     */
    public function display_analysis_columns($column, $post_id) {
        if ($column === 'wstg_analysis') {
            $summary = $this->get_post_analysis_summary($post_id);
            
            $status_items = array();
            if (!empty($summary['tags'])) {
                $status_items[] = count($summary['tags']) . ' tags';
            }
            if (!empty($summary['entities'])) {
                $status_items[] = count($summary['entities']) . ' entities';
            }
            if (!empty($summary['topics'])) {
                $status_items[] = count($summary['topics']) . ' topics';
            }
            
            if (!empty($status_items)) {
                echo '<span style="color: green;">✅ ' . implode(', ', $status_items) . '</span>';
                if ($summary['last_analyzed']) {
                    echo '<br><small style="color: #666;">Last: ' . date('M j, Y', strtotime($summary['last_analyzed'])) . '</small>';
                }
            } else {
                echo '<span style="color: #999;">— Not analyzed</span>';
            }
        }
    }
    
    // MISSING METHOD FIX: Add AJAX handlers
    public function ajax_delete_entity() {
        check_ajax_referer('wstg_delete_entity', 'nonce');
        
        if (!current_user_can('manage_categories')) {
            wp_send_json_error(__('Insufficient permissions.', 'wp-smart-tag-generator'));
        }
        
        $entity_id = intval($_POST['entity_id']);
        $result = wp_delete_term($entity_id, 'wstg_named_entities');
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(__('Entity deleted successfully.', 'wp-smart-tag-generator'));
    }
    
    public function ajax_merge_entities() {
        // Placeholder for merge functionality
        wp_send_json_error(__('Merge functionality not yet implemented.', 'wp-smart-tag-generator'));
    }
    
    public function ajax_get_similar_entities() {
        // Placeholder for similar entities functionality
        wp_send_json_error(__('Similar entities functionality not yet implemented.', 'wp-smart-tag-generator'));
    }
    
    public function ajax_export_entities() {
        // Placeholder for export functionality
        wp_send_json_error(__('Export functionality not yet implemented.', 'wp-smart-tag-generator'));
    }
    
    public function on_activation() {
        // Any activation tasks for taxonomy manager
        error_log('WSTG Taxonomy Manager: Activated at' .current_time('Y-m-d H:i:s') . 'UTC by '  . wp_get_current_user()->user_login);
    }
}