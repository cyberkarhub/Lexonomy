<?php
/**
 * Meta Box Template
 * 
 * @package WP_Smart_Tag_Generator_Enhanced
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="wstg-content-analyzer">
    <div class="wstg-status-indicator">
        <?php if ($use_ai && !empty($api_key)): ?>
            <p>🤖 <?php _e('AI-powered analysis ready', 'wp-smart-tag-generator'); ?>
            <?php if ($orthodox_context): ?>
                <br><small>☦️ <?php _e('Orthodox context enabled', 'wp-smart-tag-generator'); ?></small>
            <?php endif; ?>
            </p>
        <?php else: ?>
            <p>⚙️ <?php _e('Basic analysis mode', 'wp-smart-tag-generator'); ?></p>
            <p><small><a href="<?php echo admin_url('options-general.php?page=wstg-settings'); ?>"><?php _e('Configure AI settings', 'wp-smart-tag-generator'); ?></a></small></p>
        <?php endif; ?>
        
        <?php if (!empty($existing_data['last_analyzed'])): ?>
            <p class="wstg-last-analyzed">
                <small>📅 <?php _e('Last analyzed:', 'wp-smart-tag-generator'); ?> <?php echo date('M j, Y H:i', strtotime($existing_data['last_analyzed'])); ?></small>
                <?php if (!empty($existing_data['analyzed_by'])): ?>
                    <small> by <?php echo esc_html($existing_data['analyzed_by']); ?></small>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
    
    <div id="wstg-current-analysis" style="margin: 15px 0;">
        <h4><?php _e('Current Analysis:', 'wp-smart-tag-generator'); ?></h4>
        
        <div class="wstg-analysis-summary">
            <div class="analysis-item">
                <span class="analysis-label">🏷️ <?php _e('Tags:', 'wp-smart-tag-generator'); ?></span>
                <span class="analysis-count"><?php echo count($existing_data['tags']); ?></span>
                <?php if (!empty($existing_data['tags'])): ?>
                    <small><?php echo implode(', ', array_slice($existing_data['tags'], 0, 3)); ?><?php echo count($existing_data['tags']) > 3 ? '...' : ''; ?></small>
                <?php endif; ?>
            </div>
            
            <div class="analysis-item">
                <span class="analysis-label">👤 <?php _e('Entities:', 'wp-smart-tag-generator'); ?></span>
                <span class="analysis-count"><?php echo count($existing_data['entities']); ?></span>
                <?php if (!empty($existing_data['entities'])): ?>
                    <small><?php echo implode(', ', array_slice($existing_data['entities'], 0, 3)); ?><?php echo count($existing_data['entities']) > 3 ? '...' : ''; ?></small>
                <?php endif; ?>
            </div>
            
            <div class="analysis-item">
                <span class="analysis-label">🗺️ <?php _e('Topics:', 'wp-smart-tag-generator'); ?></span>
                <span class="analysis-count"><?php echo count($existing_data['topics']); ?></span>
                <?php if (!empty($existing_data['topics'])): ?>
                    <small><?php echo implode(', ', array_slice($existing_data['topics'], 0, 3)); ?><?php echo count($existing_data['topics']) > 3 ? '...' : ''; ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div id="wstg-analysis-options">
        <h4><?php _e('New Analysis Options:', 'wp-smart-tag-generator'); ?></h4>
        <div class="wstg-checkboxes">
            <?php if ($enable_tags): ?>
            <label>
                <input type="checkbox" id="wstg-option-tags" checked>
                <?php _e('🏷️ Generate Tags', 'wp-smart-tag-generator'); ?>
                <small>(<?php _e('SEO keywords', 'wp-smart-tag-generator'); ?>)</small>
            </label>
            <?php endif; ?>
            
            <?php if ($enable_entities): ?>
            <label>
                <input type="checkbox" id="wstg-option-entities" checked>
                <?php _e('👤 Extract Named Entities', 'wp-smart-tag-generator'); ?>
                <small>(<?php _e('People, places, concepts', 'wp-smart-tag-generator'); ?>)</small>
            </label>
            <?php endif; ?>
            
            <?php if ($enable_topical_map): ?>
            <label>
                <input type="checkbox" id="wstg-option-topical" checked>
                <?php _e('🗺️ Generate Topical Map', 'wp-smart-tag-generator'); ?>
                <small>(<?php _e('Hierarchical topics', 'wp-smart-tag-generator'); ?>)</small>
            </label>
            <?php endif; ?>
        </div>
        
        <div class="wstg-analysis-mode" style="margin: 10px 0;">
            <label>
                <input type="radio" name="analysis_mode" value="append" checked>
                <?php _e('Add to existing (recommended)', 'wp-smart-tag-generator'); ?>
            </label><br>
            <label>
                <input type="radio" name="analysis_mode" value="replace">
                <?php _e('Replace existing analysis', 'wp-smart-tag-generator'); ?>
            </label>
        </div>
    </div>
    
    <div id="wstg-actions">
        <button type="button" id="wstg-analyze-btn" class="button button-primary" data-post-id="<?php echo $post->ID; ?>">
            <?php _e('🚀 Analyze Content', 'wp-smart-tag-generator'); ?>
        </button>
        
        <?php if (!empty($existing_data['tags']) || !empty($existing_data['entities']) || !empty($existing_data['topics'])): ?>
        <button type="button" id="wstg-view-analysis" class="button button-secondary">
            <?php _e('👁️ View Current Analysis', 'wp-smart-tag-generator'); ?>
        </button>
        <?php endif; ?>
    </div>
    
    <div id="wstg-loading" style="display: none;">
        <p>🧠 <?php _e('AI is analyzing your content...', 'wp-smart-tag-generator'); ?></p>
        <div class="wstg-progress-bar">
            <div class="wstg-progress-fill"></div>
        </div>
        <div class="wstg-progress-text">0%</div>
    </div>
    
    <div id="wstg-results" style="display: none;">
        <div id="wstg-tags-section" style="display: none;">
            <h4>🏷️ <?php _e('Generated Tags:', 'wp-smart-tag-generator'); ?></h4>
            <div id="wstg-tag-list"></div>
        </div>
        
        <div id="wstg-entities-section" style="display: none;">
            <h4>👤 <?php _e('Named Entities:', 'wp-smart-tag-generator'); ?></h4>
            <div id="wstg-entities-list"></div>
        </div>
        
        <div id="wstg-topical-section" style="display: none;">
            <h4>🗺️ <?php _e('Topical Map:', 'wp-smart-tag-generator'); ?></h4>
            <div id="wstg-topical-list"></div>
        </div>
        
        <button type="button" id="wstg-apply-analysis" class="button button-primary">
            ✨ <?php _e('Apply Selected Analysis', 'wp-smart-tag-generator'); ?>
        </button>
    </div>
    
    <div id="wstg-messages"></div>
</div>