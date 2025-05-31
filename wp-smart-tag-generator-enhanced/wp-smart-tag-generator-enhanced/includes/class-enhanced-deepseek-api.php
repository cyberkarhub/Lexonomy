<?php
/**
 * Enhanced DeepSeek API Integration with Orthodox Context
 * 
 * @package WP_Smart_Tag_Generator_Enhanced
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WSTG_Enhanced_DeepSeek_API {
    
    private $api_key;
    private $base_url = 'https://api.deepseek.com/v1';
    private $model;
    private $arabic_support;
    private $orthodox_context;
    
    public function __construct() {
        $this->api_key = get_option('wstg_deepseek_api_key', '');
        $this->model = get_option('wstg_ai_model', 'deepseek-chat');
        $this->arabic_support = get_option('wstg_arabic_support', 1);
        $this->orthodox_context = get_option('wstg_orthodox_context', 1);
    }
    
    /**
     * Comprehensive content analysis
     */
    public function analyze_content($title, $content, $options = array()) {
        if (empty($this->api_key)) {
            return array('success' => false, 'message' => 'API key not configured');
        }
        
        $is_arabic = $this->detect_arabic_content($title . ' ' . $content);
        $language = $is_arabic ? 'Arabic' : 'English';
        
        $analysis_results = array();
        
        // Generate tags
        if (!empty($options['tags'])) {
            $tags_result = $this->generate_tags($title, $content, $language);
            if ($tags_result['success']) {
                $analysis_results['tags'] = $tags_result['data'];
            }
        }
        
        // Extract named entities
        if (!empty($options['entities'])) {
            $entities_result = $this->extract_named_entities($title, $content, $language);
            if ($entities_result['success']) {
                $analysis_results['entities'] = $entities_result['data'];
            }
        }
        
        // Generate topical map
        if (!empty($options['topical_map'])) {
            $topical_result = $this->generate_topical_map($title, $content, $language);
            if ($topical_result['success']) {
                $analysis_results['topical_map'] = $topical_result['data'];
            }
        }
        
        return array(
            'success' => !empty($analysis_results),
            'data' => $analysis_results,
            'language_detected' => $language,
            'orthodox_context' => $this->orthodox_context
        );
    }
    
    /**
     * Generate tags with Orthodox context
     */
    public function generate_tags($title, $content, $language = 'English') {
        $max_tags = get_option('wstg_max_tags', 6);
        $existing_tags = $this->get_existing_tags();
        $existing_tags_text = !empty($existing_tags) ? implode(', ', array_slice($existing_tags, 0, 10)) : 'none';
        
        $prompt = $this->build_tags_prompt($title, $content, $existing_tags_text, $max_tags, $language);
        
        $response = $this->make_api_request($prompt, array('max_tokens' => 100));
        
        if (!$response['success']) {
            return $response;
        }
        
        $tags = $this->parse_tags_from_response($response['data'], $language);
        
        return array(
            'success' => true,
            'data' => $tags,
            'usage' => $response['usage'] ?? null
        );
    }
    
    /**
     * Extract named entities with Orthodox focus
     */
    public function extract_named_entities($title, $content, $language = 'English') {
        $max_entities = get_option('wstg_max_entities', 10);
        
        $prompt = $this->build_entities_prompt($title, $content, $max_entities, $language);
        
        $response = $this->make_api_request($prompt, array('max_tokens' => 200));
        
        if (!$response['success']) {
            return $response;
        }
        
        $entities = $this->parse_entities_from_response($response['data'], $language);
        
        return array(
            'success' => true,
            'data' => $entities,
            'usage' => $response['usage'] ?? null
        );
    }
    
    /**
     * Generate topical map with Orthodox perspective
     */
    public function generate_topical_map($title, $content, $language = 'English') {
        $prompt = $this->build_topical_map_prompt($title, $content, $language);
        
        $response = $this->make_api_request($prompt, array('max_tokens' => 300));
        
        if (!$response['success']) {
            return $response;
        }
        
        $topical_map = $this->parse_topical_map_from_response($response['data'], $language);
        
        return array(
            'success' => true,
            'data' => $topical_map,
            'usage' => $response['usage'] ?? null
        );
    }
    
    /**
     * Build tags prompt with Orthodox context
     */
    private function build_tags_prompt($title, $content, $existing_tags, $max_tags, $language) {
        $orthodox_context = $this->orthodox_context ? $this->get_orthodox_context_text($language) : '';
        
        if ($language === 'Arabic') {
            return "أنت خبير في تحليل المحتوى المسيحي الأرثوذكسي ومتخصص في إنشاء علامات (tags) لمنصة ووردبريس.

{$orthodox_context}

تحليل المقال:
العنوان: {$title}
المحتوى: " . mb_substr($content, 0, 1000) . "
العلامات الموجودة: {$existing_tags}

التعليمات:
1. أنشئ بالضبط {$max_tags} علامات مناسبة لهذا المحتوى
2. ركز على المواضيع اللاهوتية والروحية والطقسية إذا كان المحتوى أرثوذكسي
3. استخدم العلامات الموجودة عند الملاءمة
4. اجعل العلامات محسنة لمحركات البحث

أرجع العلامات فقط مفصولة بفواصل:";
        } else {
            return "You are an expert in content analysis and WordPress tag generation.

{$orthodox_context}

CONTENT ANALYSIS:
Title: {$title}
Content: " . mb_substr($content, 0, 1000) . "
Existing Tags: {$existing_tags}

INSTRUCTIONS:
1. Generate exactly {$max_tags} highly relevant tags for this content
2. Focus on theological, spiritual, and liturgical themes if Orthodox Christian content is detected
3. Reuse existing tags when relevant
4. Ensure tags are SEO-optimized
5. Consider saints, feast days, and Orthodox terminology if applicable

RESPONSE FORMAT:
Return only the tags as a comma-separated list:";
        }
    }
    
    /**
     * Build named entities prompt with Orthodox focus
     */
    private function build_entities_prompt($title, $content, $max_entities, $language) {
        $orthodox_context = $this->orthodox_context ? $this->get_orthodox_context_text($language) : '';
        
        if ($language === 'Arabic') {
            return "أنت خبير في استخراج الكيانات المسماة من النصوص.

{$orthodox_context}

النص للتحليل:
العنوان: {$title}
المحتوى: " . mb_substr($content, 0, 800) . "

استخرج أهم {$max_entities} كيانات مسماة.

تنسيق الاستجابة (JSON):
[{\"name\": \"اسم الكيان\", \"type\": \"نوع\", \"category\": \"فئة\"}]

الأنواع المتاحة: person, place, organization, concept, saint
الفئات: orthodox, biblical, general";
        } else {
            return "You are an expert in extracting named entities from text.

{$orthodox_context}

TEXT TO ANALYZE:
Title: {$title}
Content: " . mb_substr($content, 0, 800) . "

Extract the top {$max_entities} most important named entities.

RESPONSE FORMAT (JSON):
[{\"name\": \"Entity Name\", \"type\": \"entity_type\", \"category\": \"category\"}]

AVAILABLE TYPES: person, place, organization, concept, saint
CATEGORIES: orthodox, biblical, general";
        }
    }
    
    /**
     * Build topical map prompt with Orthodox perspective
     */
    private function build_topical_map_prompt($title, $content, $language) {
        $orthodox_context = $this->orthodox_context ? $this->get_orthodox_context_text($language) : '';
        
        if ($language === 'Arabic') {
            return "أنت خبير في إنشاء خرائط موضوعية للمحتوى.

{$orthodox_context}

النص للتحليل:
العنوان: {$title}
المحتوى: " . mb_substr($content, 0, 800) . "

أنشئ خريطة موضوعية تظهر المواضيع الرئيسية.

تنسيق الاستجابة (JSON):
[{\"topic\": \"الموضوع الرئيسي\", \"subtopics\": [\"موضوع فرعي 1\"], \"relevance\": \"الصلة\"}]";
        } else {
            return "You are an expert in creating topical maps for content.

{$orthodox_context}

TEXT TO ANALYZE:
Title: {$title}
Content: " . mb_substr($content, 0, 800) . "

Create a topical map showing main topics and subtopics.

RESPONSE FORMAT (JSON):
[{\"topic\": \"Main Topic\", \"subtopics\": [\"Subtopic 1\"], \"relevance\": \"How this relates to the content\"}]";
        }
    }
    
    /**
     * Get Orthodox context text for prompts
     */
    private function get_orthodox_context_text($language) {
        if ($language === 'Arabic') {
            return "السياق الأرثوذكسي: ركز على التعاليم والتقاليد الأرثوذكسية، القديسين، الليتورجيا، والروحانية الأرثوذكسية عند وجودها في المحتوى.";
        } else {
            return "ORTHODOX CONTEXT: Focus on Orthodox Christian teachings, traditions, saints, liturgy, and Orthodox spirituality when detected in content.";
        }
    }
    
    /**
     * Make API request to DeepSeek
     */
    private function make_api_request($prompt, $options = array()) {
        $url = $this->base_url . '/chat/completions';
        
        $default_options = array(
            'max_tokens' => 150,
            'temperature' => 0.3,
        );
        
        $options = array_merge($default_options, $options);
        
        $data = array(
            'model' => $this->model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => $options['max_tokens'],
            'temperature' => $options['temperature']
        );
        
        $args = array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'body' => wp_json_encode($data)
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'API request failed: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : 'Unknown API error';
            
            return array(
                'success' => false,
                'message' => "API error ({$response_code}): {$error_message}"
            );
        }
        
        $data = json_decode($body, true);
        
        if (!isset($data['choices'][0]['message']['content'])) {
            return array(
                'success' => false,
                'message' => 'Invalid API response format'
            );
        }
        
        return array(
            'success' => true,
            'data' => $data['choices'][0]['message']['content'],
            'usage' => $data['usage'] ?? null
        );
    }
    
    /**
     * Parse tags from API response
     */
    private function parse_tags_from_response($response, $language) {
        $response = trim($response);
        
        // Clean up the response
        $response = preg_replace('/^[^a-zA-Zأ-ي]*/u', '', $response);
        $response = preg_replace('/[.!?]+$/', '', $response);
        
        $tags = array_map('trim', explode(',', $response));
        
        $cleaned_tags = array();
        foreach ($tags as $tag) {
            $tag = trim($tag);
            $tag = preg_replace('/["\'.;:!?]/', '', $tag);
            
            if (mb_strlen($tag, 'UTF-8') >= 2 && mb_strlen($tag, 'UTF-8') <= 50) {
                if ($language !== 'Arabic') {
                    $tag = ucwords(strtolower($tag));
                }
                $cleaned_tags[] = $tag;
            }
        }
        
        return array_unique($cleaned_tags);
    }
    
    /**
     * Parse named entities from API response
     */
    private function parse_entities_from_response($response, $language) {
        $response = trim($response);
        
        // Try to extract JSON from response
        preg_match('/\[.*?\]/s', $response, $matches);
        
        if (empty($matches)) {
            // Fallback: simple text parsing
            return $this->fallback_entity_extraction($response);
        }
        
        $entities_data = json_decode($matches[0], true);
        
        if (!is_array($entities_data)) {
            return $this->fallback_entity_extraction($response);
        }
        
        $entities = array();
        foreach ($entities_data as $entity) {
            if (isset($entity['name']) && isset($entity['type'])) {
                $entities[] = array(
                    'name' => sanitize_text_field($entity['name']),
                    'type' => sanitize_text_field($entity['type']),
                    'category' => isset($entity['category']) ? sanitize_text_field($entity['category']) : 'general',
                    'description' => isset($entity['description']) ? sanitize_text_field($entity['description']) : ''
                );
            }
        }
        
        return $entities;
    }
    
    /**
     * Fallback entity extraction
     */
    private function fallback_entity_extraction($text) {
        $entities = array();
        
        // Simple pattern matching for common entities
        if (preg_match_all('/\b[A-Z][a-z]+ [A-Z][a-z]+\b/', $text, $matches)) {
            foreach ($matches[0] as $match) {
                $entities[] = array(
                    'name' => $match,
                    'type' => 'person',
                    'category' => 'general',
                    'description' => ''
                );
            }
        }
        
        return array_slice($entities, 0, 5);
    }
    
    /**
     * Parse topical map from API response
     */
    private function parse_topical_map_from_response($response, $language) {
        $response = trim($response);
        
        // Try to extract JSON from response
        preg_match('/\[.*?\]/s', $response, $matches);
        
        if (empty($matches)) {
            // Fallback: create simple topical map
            return array(
                array(
                    'topic' => 'Main Content Theme',
                    'subtopics' => array('Content Analysis'),
                    'relevance' => 'General content topic'
                )
            );
        }
        
        $topical_data = json_decode($matches[0], true);
        
        if (!is_array($topical_data)) {
            return array();
        }
        
        $topical_map = array();
        foreach ($topical_data as $topic) {
            if (isset($topic['topic'])) {
                $topical_map[] = array(
                    'topic' => sanitize_text_field($topic['topic']),
                    'subtopics' => isset($topic['subtopics']) && is_array($topic['subtopics']) 
                        ? array_map('sanitize_text_field', $topic['subtopics']) 
                        : array(),
                    'relevance' => isset($topic['relevance']) 
                        ? sanitize_text_field($topic['relevance']) 
                        : 'Related to content'
                );
            }
        }
        
        return $topical_map;
    }
    
    /**
     * Detect if content is primarily Arabic
     */
    private function detect_arabic_content($text) {
        if (!$this->arabic_support) {
            return false;
        }
        
        $arabic_chars = preg_match_all('/[\x{0600}-\x{06FF}]/u', $text);
        $total_chars = mb_strlen(preg_replace('/\s+/', '', $text), 'UTF-8');
        
        return $total_chars > 0 && ($arabic_chars / $total_chars) > 0.3;
    }
    
    /**
     * Get existing WordPress tags
     */
    private function get_existing_tags() {
        $tags = get_tags(array(
            'hide_empty' => false,
            'fields' => 'names',
            'number' => 50
        ));
        
        return is_array($tags) ? $tags : array();
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'API key is not configured'
            );
        }
        
        $test_prompt = "Generate 3 tags for content about 'Prayer and Liturgy'. Respond with just the tags separated by commas.";
        
        $response = $this->make_api_request($test_prompt, array('max_tokens' => 50));
        
        if ($response['success']) {
            return array(
                'success' => true,
                'message' => 'API connection successful! Model: ' . $this->model . 
                           ' | Orthodox context: ' . ($this->orthodox_context ? 'Enabled' : 'Disabled') .
                           ' | Arabic support: ' . ($this->arabic_support ? 'Enabled' : 'Disabled')
            );
        } else {
            return array(
                'success' => false,
                'message' => $response['message']
            );
        }
    }
}