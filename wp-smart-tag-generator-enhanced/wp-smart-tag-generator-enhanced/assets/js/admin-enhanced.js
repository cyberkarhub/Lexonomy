/**
 * Enhanced Admin JavaScript
 * 
 * @package WP_Smart_Tag_Generator_Enhanced
 * @since 3.1.0
 */

jQuery(document).ready(function($) {
    console.log('WSTG Enhanced: Admin JavaScript loaded at' . current_time('Y-m-d H:i:s') . 'UTC by '  . wp_get_current_user()->user_login);
    
    var analysisData = {};
    
    // Analyze content button
    $("#wstg-analyze-btn").on("click", function() {
        var postId = $(this).data("post-id");
        var options = {};
        
        // Get selected options
        if ($("#wstg-option-tags").is(":checked")) options.tags = true;
        if ($("#wstg-option-entities").is(":checked")) options.entities = true;
        if ($("#wstg-option-topical").is(":checked")) options.topical_map = true;
        
        if (Object.keys(options).length === 0) {
            showMessage(wstg_ajax.messages.no_selection, "error");
            return;
        }
        
        analyzeContent(postId, options);
    });
    
    // Apply analysis button
    $(document).on("click", "#wstg-apply-analysis", function() {
        var postId = $("#wstg-analyze-btn").data("post-id");
        var mode = $('input[name="analysis_mode"]:checked').val();
        applyAnalysis(postId, mode);
    });
    
    function analyzeContent(postId, options) {
        showLoading();
        clearMessages();
        
        $.ajax({
            url: wstg_ajax.ajax_url,
            type: "POST",
            data: {
                action: "wstg_analyze_content",
                post_id: postId,
                options: options,
                nonce: wstg_ajax.nonce
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    analysisData = response.data;
                    displayResults(response.data);
                    showMessage(wstg_ajax.messages.success, "success");
                } else {
                    showMessage(response.data || wstg_ajax.messages.error, "error");
                }
            },
            error: function() {
                hideLoading();
                showMessage(wstg_ajax.messages.error, "error");
            }
        });
    }
    
    function applyAnalysis(postId, mode) {
        // Collect selected data
        var selectedData = {};
        
        // Get selected tags
        var selectedTags = [];
        $("#wstg-tag-list input:checked").each(function() {
            selectedTags.push($(this).next("label").text().trim());
        });
        if (selectedTags.length > 0) selectedData.tags = selectedTags;
        
        // Get selected entities
        if (analysisData.entities) {
            selectedData.entities = analysisData.entities;
        }
        
        // Get selected topical map
        if (analysisData.topical_map) {
            selectedData.topical_map = analysisData.topical_map;
        }
        
        // Include analysis metadata
        if (analysisData.context_detection) {
            selectedData.context_detection = analysisData.context_detection;
        }
        if (analysisData.method) {
            selectedData.method = analysisData.method;
        }
        
        if (Object.keys(selectedData).length === 0) {
            showMessage("Please select items to apply.", "error");
            return;
        }
        
        $.ajax({
            url: wstg_ajax.ajax_url,
            type: "POST",
            data: {
                action: "wstg_apply_analysis",
                post_id: postId,
                analysis_data: selectedData,
                mode: mode,
                nonce: wstg_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage("Analysis applied successfully!", "success");
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showMessage(response.data || "Failed to apply analysis", "error");
                }
            },
            error: function() {
                showMessage("Request failed. Please try again.", "error");
            }
        });
    }
    
    function displayResults(data) {
        $("#wstg-results").show();
        
        // Display tags
        if (data.tags && data.tags.length > 0) {
            var tagHtml = "";
            data.tags.forEach(function(tag, index) {
                tagHtml += "<span class=\"wstg-tag\">";
                tagHtml += "<input type=\"checkbox\" id=\"tag-" + index + "\" checked>";
                tagHtml += "<label for=\"tag-" + index + "\">" + escapeHtml(tag) + "</label>";
                tagHtml += "</span>";
            });
            $("#wstg-tag-list").html(tagHtml);
            $("#wstg-tags-section").show();
        }
        
        // Display entities
        if (data.entities && data.entities.length > 0) {
            var entityHtml = "";
            data.entities.forEach(function(entity) {
                entityHtml += "<span class=\"wstg-entity\" title=\"" + escapeHtml(entity.type || 'general') + "\">";
                entityHtml += escapeHtml(entity.name || entity);
                if (entity.category === "orthodox") entityHtml += " ☦️";
                if (entity.category === "biblical") entityHtml += " 📖";
                entityHtml += "</span>";
            });
            $("#wstg-entities-list").html(entityHtml);
            $("#wstg-entities-section").show();
        }
        
        // Display topical map
        if (data.topical_map && data.topical_map.length > 0) {
            var topicalHtml = "";
            data.topical_map.forEach(function(topic) {
                topicalHtml += "<div class=\"wstg-topical-item\">";
                topicalHtml += "<div class=\"wstg-topical-title\">" + escapeHtml(topic.topic || topic) + "</div>";
                if (topic.subtopics && topic.subtopics.length > 0) {
                    topicalHtml += "<div class=\"wstg-subtopics\">Subtopics: " + topic.subtopics.map(escapeHtml).join(", ") + "</div>";
                }
                if (topic.relevance) {
                    topicalHtml += "<div class=\"wstg-relevance\"><small>" + escapeHtml(topic.relevance) + "</small></div>";
                }
                topicalHtml += "</div>";
            });
            $("#wstg-topical-list").html(topicalHtml);
            $("#wstg-topical-section").show();
        }
        
        // Show method and language detection info
        if (data.method || data.language_detected) {
            var infoHtml = "<div class=\"wstg-analysis-info\">";
            if (data.method) {
                infoHtml += "<small>Method: " + escapeHtml(data.method) + "</small>";
            }
            if (data.language_detected) {
                infoHtml += "<small> | Language: " + escapeHtml(data.language_detected) + "</small>";
            }
            infoHtml += "</div>";
            $("#wstg-results").prepend(infoHtml);
        }
    }
    
    function showLoading() {
        $("#wstg-analyze-btn").prop("disabled", true).text("🧠 " + wstg_ajax.messages.analyzing);
        $("#wstg-loading").show();
        $("#wstg-results").hide();
        animateProgress();
    }
    
    function hideLoading() {
        $("#wstg-analyze-btn").prop("disabled", false).text("🚀 Analyze Content");
        $("#wstg-loading").hide();
        $(".wstg-progress-fill").css("width", "100%");
        setTimeout(function() {
            $(".wstg-progress-fill").css("width", "0%");
            $(".wstg-progress-text").text("0%");
        }, 500);
    }
    
    function animateProgress() {
        var width = 0;
        var interval = setInterval(function() {
            width += Math.random() * 15;
            if (width > 90) width = 90;
            $(".wstg-progress-fill").css("width", width + "%");
            $(".wstg-progress-text").text(Math.round(width) + "%");
            
            if (!$("#wstg-loading").is(":visible")) {
                clearInterval(interval);
            }
        }, 300);
    }
    
    function showMessage(message, type) {
        var className = type === "error" ? "notice-error" : "notice-success";
        var html = "<div class=\"notice " + className + "\"><p>" + escapeHtml(message) + "</p></div>";
        $("#wstg-messages").html(html);
        
        if (type === "success") {
            setTimeout(function() {
                $("#wstg-messages").fadeOut();
            }, 3000);
        }
    }
    
    function clearMessages() {
        $("#wstg-messages").empty();
    }
    
    function escapeHtml(text) {
        var map = {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            "\"": "&quot;",
            "\'": "&#039;"
        };
        return String(text).replace(/[&<>"\']/g, function(s) {
            return map[s];
        });
    }
});