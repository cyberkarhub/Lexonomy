<?php
/**
 * Enhanced Content Analyzer
 * Handles content analysis logic and coordination
 * 
 * @package WP_Smart_Tag_Generator_Enhanced
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WSTG_Enhanced_Content_Analyzer {
    
    private $deepseek_api;
    
    public function __construct($deepseek_api = null) {
        $this->deepseek_api = $deepseek_api;
    }
    
    /**
     * Handle AJAX content analysis request
     */
    public function handle_ajax_analyze() {
        check_ajax_referer('wstg_ajax_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Insufficient permissions.', 'wp-smart-tag-generator'));
        }
        
        $post_id = intval($_POST['post_id']);
        $options = isset($_POST['options']) ? $_POST['options'] : array();
        
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(__('Post not found.', 'wp-smart-tag-generator'));
        }
        
        $analysis_result = $this->analyze_post_content($post, $options);
        
        if ($analysis_result['success']) {
            wp_send_json_success($analysis_result['data']);
        } else {
            wp_send_json_error($analysis_result['message']);
        }
    }
    
    /**
     * Handle AJAX apply analysis request
     */
    public function handle_ajax_apply($taxonomy_manager) {
        check_ajax_referer('wstg_ajax_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Insufficient permissions.', 'wp-smart-tag-generator'));
        }
        
        $post_id = intval($_POST['post_id']);
        $analysis_data = isset($_POST['analysis_data']) ? $_POST['analysis_data'] : array();
        $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'append';
        
        if (!$post_id || empty($analysis_data)) {
            wp_send_json_error(__('Invalid data provided.', 'wp-smart-tag-generator'));
        }
        
        $result = $taxonomy_manager->apply_analysis_to_post($post_id, $analysis_data, $mode);
        
        if ($result['success']) {
            // Update analysis metadata
            update_post_meta($post_id, '_wstg_last_analyzed', current_time('mysql'));
            update_post_meta($post_id, '_wstg_analyzed_by', wp_get_current_user()->user_login);
            
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Analyze post content using AI or fallback methods
     */
    public function analyze_post_content($post, $options = array()) {
        $title = $post->post_title;
        $content = wp_strip_all_tags($post->post_content);
        
        if (empty($title) && empty($content)) {
            return array(
                'success' => false,
                'message' => __('No content found to analyze.', 'wp-smart-tag-generator')
            );
        }
        
        // Detect content characteristics
        $is_arabic = $this->detect_arabic_content($title . ' ' . $content);
        $has_orthodox_content = $this->detect_orthodox_content($title . ' ' . $content);
        
        // Try AI analysis first
        $api_key = get_option('wstg_deepseek_api_key', '');
        $use_ai = get_option('wstg_use_ai', 1);
        
        $analysis_result = array();
        
        if ($use_ai && !empty($api_key) && $this->deepseek_api) {
            $ai_analysis = $this->deepseek_api->analyze_content($title, $content, $options);
            
            if ($ai_analysis['success']) {
                $analysis_result = $ai_analysis['data'];
                $analysis_result['method'] = 'ai';
                $analysis_result['language_detected'] = $ai_analysis['language_detected'] ?? ($is_arabic ? 'Arabic' : 'English');
                
                error_log('WSTG Content Analyzer: AI analysis successful for post ' . $post->ID);
            } else {
                error_log('WSTG Content Analyzer: AI analysis failed, using fallback: ' . $ai_analysis['message']);
                $analysis_result = $this->basic_content_analysis($title, $content, $options);
                $analysis_result['method'] = 'basic_fallback';
                $analysis_result['fallback_reason'] = $ai_analysis['message'];
            }
        } else {
            $analysis_result = $this->basic_content_analysis($title, $content, $options);
            $analysis_result['method'] = 'basic';
        }
        
        // Add context detection
        $analysis_result['context_detection'] = array(
            'is_arabic' => $is_arabic,
            'has_orthodox_content' => $has_orthodox_content,
            'timestamp' => current_time('Y-m-d H:i:s'),
            'analyzed_by' => wp_get_current_user()->user_login
        );
        
        return array(
            'success' => true,
            'data' => $analysis_result
        );
    }
    
    /**
     * Basic content analysis (fallback when AI is not available)
     */
    private function basic_content_analysis($title, $content, $options) {
        $analysis = array();
        
        if (!empty($options['tags'])) {
            $analysis['tags'] = $this->basic_generate_tags($title, $content);
        }
        
        if (!empty($options['entities'])) {
            $analysis['entities'] = $this->basic_extract_entities($title, $content);
        }
        
        if (!empty($options['topical_map'])) {
            $analysis['topical_map'] = $this->basic_generate_topical_map($title, $content);
        }
        
        return $analysis;
    }
    
    private function basic_generate_tags($title, $content) {
        $text = strtolower($title . ' ' . $content);
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $words = array_filter(explode(' ', $text));
        
        // Remove stop words
        $stop_words = array('the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should');
        $words = array_diff($words, $stop_words);
        
        // Count word frequency
        $word_counts = array();
        foreach ($words as $word) {
            if (strlen($word) >= 4) {
                $word_counts[$word] = isset($word_counts[$word]) ? $word_counts[$word] + 1 : 1;
            }
        }
        
        arsort($word_counts);
        $max_tags = get_option('wstg_max_tags', 6);
        
        return array_map('ucwords', array_slice(array_keys($word_counts), 0, $max_tags));
    }
    
    private function basic_extract_entities($title, $content) {
        $entities = array();
        $text = $title . ' ' . $content;
        
        // Simple pattern matching for proper nouns
        if (preg_match_all('/\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*\b/', $text, $matches)) {
            $seen = array();
            foreach ($matches[0] as $match) {
                if (strlen($match) > 3 && !isset($seen[$match])) {
                    $seen[$match] = true;
                    
                    // Determine entity type based on length and patterns
                    $type = 'person';
                    if (strlen($match) > 15) $type = 'organization';
                    if (preg_match('/\b(Church|Cathedral|Monastery|Abbey)\b/i', $match)) $type = 'organization';
                    if (preg_match('/\b(Saint|St\.)\b/i', $match)) $type = 'saint';
                    
                    $entities[] = array(
                        'name' => $match,
                        'type' => $type,
                        'category' => $this->detect_orthodox_content($match) ? 'orthodox' : 'general',
                        'description' => 'Automatically extracted entity'
                    );
                }
            }
        }
        
        $max_entities = get_option('wstg_max_entities', 10);
        return array_slice($entities, 0, $max_entities);
    }
    
    private function basic_generate_topical_map($title, $content) {
        $tags = $this->basic_generate_tags($title, $content);
        $main_topic = !empty($tags) ? $tags[0] : 'Content';
        
        return array(
            array(
                'topic' => $main_topic . ' Discussion',
                'subtopics' => array_slice($tags, 1, 3),
                'relevance' => 'Main content theme identified through keyword analysis',
                'orthodox_relevance' => $this->detect_orthodox_content($title . ' ' . $content) ? 'Contains Orthodox Christian references' : null
            )
        );
    }
    
    /**
     * Detect if content contains Arabic text
     */
    private function detect_arabic_content($text) {
        if (!get_option('wstg_arabic_support', 1)) {
            return false;
        }
        
        $arabic_chars = preg_match_all('/[\x{0600}-\x{06FF}]/u', $text);
        $total_chars = mb_strlen(preg_replace('/\s+/', '', $text), 'UTF-8');
        
        return $total_chars > 0 && ($arabic_chars / $total_chars) > 0.3;
    }
    
    /**
     * Detect Orthodox Christian content
     */
    private function detect_orthodox_content($text) {
        if (!get_option('wstg_orthodox_context', 1)) {
            return false;
        }
        
        $orthodox_keywords = array(
            'orthodox', 'liturgy', 'saint', 'icon', 'monastery', 'patriarch', 
            'theotokos', 'byzantine', 'patristic', 'hesychasm', 'theosis',
            'church father', 'divine liturgy', 'holy tradition', 'iconography',
            'أرثوذكسي', 'ليتورجيا', 'قديس', 'أيقونة', 'دير', 'بطريرك'
        );
        
        $text_lower = mb_strtolower($text, 'UTF-8');
        $matches = 0;
        
        foreach ($orthodox_keywords as $keyword) {
            if (mb_strpos($text_lower, mb_strtolower($keyword, 'UTF-8')) !== false) {
                $matches++;
            }
        }
        
        return $matches >= 2;
    }
}