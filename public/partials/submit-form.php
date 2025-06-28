<?php
/**
 * Poll Submit Form Template
 *
 * @package WPCS_Poll
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$max_options = intval($atts['max_options']);
$show_description = $atts['show_description'] === 'true';
$show_tags = $atts['show_tags'] === 'true';
$show_category = $atts['show_category'] === 'true';

// Get available categories
$categories = get_option('wpcs_poll_categories', array('General'));
?>

<div class="wpcs-poll-submit-form">
    
    <div class="form-header">
        <h3><?php _e('Create a New Poll', 'wpcs-poll'); ?></h3>
        <p><?php _e('Share your question with the community and get instant feedback!', 'wpcs-poll'); ?></p>
    </div>

    <form id="wpcs-submit-poll-form" method="post">
        <?php wp_nonce_field('wpcs_poll_submit_nonce', 'nonce'); ?>
        
        <div class="form-group">
            <label for="poll-title"><?php _e('Poll Question', 'wpcs-poll'); ?> <span class="required">*</span></label>
            <input type="text" id="poll-title" name="title" required maxlength="255" 
                   placeholder="<?php _e('What would you like to ask?', 'wpcs-poll'); ?>">
            <div class="char-counter">
                <span class="current">0</span>/<span class="max">255</span>
            </div>
        </div>

        <?php if ($show_description): ?>
            <div class="form-group">
                <label for="poll-description"><?php _e('Description (Optional)', 'wpcs-poll'); ?></label>
                <textarea id="poll-description" name="description" rows="3" maxlength="500"
                          placeholder="<?php _e('Add more context to your poll...', 'wpcs-poll'); ?>"></textarea>
                <div class="char-counter">
                    <span class="current">0</span>/<span class="max">500</span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($show_category): ?>
            <div class="form-group">
                <label for="poll-category"><?php _e('Category', 'wpcs-poll'); ?></label>
                <select id="poll-category" name="category">
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo esc_attr($category); ?>"><?php echo esc_html($category); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label><?php _e('Poll Options', 'wpcs-poll'); ?> <span class="required">*</span></label>
            <div id="poll-options-container">
                <div class="option-input">
                    <input type="text" name="options[]" required maxlength="100" 
                           placeholder="<?php _e('Option 1', 'wpcs-poll'); ?>">
                    <button type="button" class="remove-option" disabled>×</button>
                </div>
                <div class="option-input">
                    <input type="text" name="options[]" required maxlength="100" 
                           placeholder="<?php _e('Option 2', 'wpcs-poll'); ?>">
                    <button type="button" class="remove-option" disabled>×</button>
                </div>
            </div>
            <button type="button" id="add-option" class="btn-add-option">
                + <?php _e('Add Option', 'wpcs-poll'); ?>
            </button>
            <small class="help-text">
                <?php printf(__('Minimum 2 options, maximum %d options', 'wpcs-poll'), $max_options); ?>
            </small>
        </div>

        <?php if ($show_tags): ?>
            <div class="form-group">
                <label for="poll-tags"><?php _e('Tags (Optional)', 'wpcs-poll'); ?></label>
                <input type="text" id="poll-tags" name="tags" maxlength="200"
                       placeholder="<?php _e('Enter tags separated by commas', 'wpcs-poll'); ?>">
                <small class="help-text"><?php _e('Tags help people find your poll', 'wpcs-poll'); ?></small>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <span class="btn-text"><?php _e('Submit Poll', 'wpcs-poll'); ?></span>
                <span class="btn-loading" style="display: none;">
                    <span class="spinner"></span> <?php _e('Submitting...', 'wpcs-poll'); ?>
                </span>
            </button>
            <button type="button" class="btn btn-secondary" onclick="wpcsResetForm()">
                <?php _e('Reset', 'wpcs-poll'); ?>
            </button>
        </div>
    </form>

    <div id="submit-result" class="submit-result" style="display: none;"></div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('wpcs-submit-poll-form');
    const optionsContainer = document.getElementById('poll-options-container');
    const addOptionBtn = document.getElementById('add-option');
    const maxOptions = <?php echo $max_options; ?>;
    
    // Character counters
    document.querySelectorAll('input[maxlength], textarea[maxlength]').forEach(function(element) {
        const counter = element.parentNode.querySelector('.char-counter .current');
        if (counter) {
            element.addEventListener('input', function() {
                counter.textContent = this.value.length;
            });
        }
    });
    
    // Add option functionality
    addOptionBtn.addEventListener('click', function() {
        const optionCount = optionsContainer.children.length;
        if (optionCount < maxOptions) {
            const newOption = document.createElement('div');
            newOption.className = 'option-input';
            newOption.innerHTML = `
                <input type="text" name="options[]" maxlength="100" 
                       placeholder="<?php _e('Option', 'wpcs-poll'); ?> ${optionCount + 1}">
                <button type="button" class="remove-option">×</button>
            `;
            optionsContainer.appendChild(newOption);
            updateRemoveButtons();
        }
        
        if (optionCount + 1 >= maxOptions) {
            addOptionBtn.style.display = 'none';
        }
    });
    
    // Remove option functionality
    optionsContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-option')) {
            e.target.parentNode.remove();
            updateRemoveButtons();
            addOptionBtn.style.display = 'inline-block';
        }
    });
    
    function updateRemoveButtons() {
        const options = optionsContainer.children;
        const removeButtons = optionsContainer.querySelectorAll('.remove-option');
        
        removeButtons.forEach(function(btn, index) {
            btn.disabled = options.length <= 2;
        });
    }
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoading = submitBtn.querySelector('.btn-loading');
        
        // Show loading state
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline-block';
        submitBtn.disabled = true;
        
        // Prepare form data
        const formData = new FormData(form);
        formData.append('action', 'wpcs_poll_submit');
        
        // Submit via AJAX
        fetch(wpcs_poll_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const resultDiv = document.getElementById('submit-result');
            
            if (data.success) {
                resultDiv.innerHTML = `
                    <div class="success-message">
                        <h4><?php _e('Success!', 'wpcs-poll'); ?></h4>
                        <p>${data.data.message}</p>
                    </div>
                `;
                form.reset();
                updateCharCounters();
                resetOptions();
            } else {
                resultDiv.innerHTML = `
                    <div class="error-message">
                        <h4><?php _e('Error', 'wpcs-poll'); ?></h4>
                        <p>${data.data ? data.data.message : '<?php _e('An error occurred', 'wpcs-poll'); ?>'}</p>
                    </div>
                `;
            }
            
            resultDiv.style.display = 'block';
            resultDiv.scrollIntoView({ behavior: 'smooth' });
        })
        .catch(error => {
            console.error('Error:', error);
            const resultDiv = document.getElementById('submit-result');
            resultDiv.innerHTML = `
                <div class="error-message">
                    <h4><?php _e('Network Error', 'wpcs-poll'); ?></h4>
                    <p><?php _e('Please check your connection and try again.', 'wpcs-poll'); ?></p>
                </div>
            `;
            resultDiv.style.display = 'block';
        })
        .finally(() => {
            // Reset button state
            btnText.style.display = 'inline-block';
            btnLoading.style.display = 'none';
            submitBtn.disabled = false;
        });
    });
    
    function updateCharCounters() {
        document.querySelectorAll('.char-counter .current').forEach(function(counter) {
            counter.textContent = '0';
        });
    }
    
    function resetOptions() {
        // Reset to 2 options
        optionsContainer.innerHTML = `
            <div class="option-input">
                <input type="text" name="options[]" required maxlength="100" 
                       placeholder="<?php _e('Option 1', 'wpcs-poll'); ?>">
                <button type="button" class="remove-option" disabled>×</button>
            </div>
            <div class="option-input">
                <input type="text" name="options[]" required maxlength="100" 
                       placeholder="<?php _e('Option 2', 'wpcs-poll'); ?>">
                <button type="button" class="remove-option" disabled>×</button>
            </div>
        `;
        addOptionBtn.style.display = 'inline-block';
    }
});

// Global reset function
function wpcsResetForm() {
    if (confirm('<?php _e('Are you sure you want to reset the form?', 'wpcs-poll'); ?>')) {
        document.getElementById('wpcs-submit-poll-form').reset();
        document.getElementById('submit-result').style.display = 'none';
        document.querySelectorAll('.char-counter .current').forEach(function(counter) {
            counter.textContent = '0';
        });
    }
}
</script>

<style>
.wpcs-poll-submit-form {
    max-width: 600px;
    margin: 0 auto;
    padding: 30px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.form-header {
    text-align: center;
    margin-bottom: 30px;
}

.form-header h3 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 24px;
}

.form-header p {
    margin: 0;
    color: #666;
    font-size: 16px;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.required {
    color: #d63638;
}

.form-group input[type="text"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.2s ease;
    box-sizing: border-box;
}

.form-group input[type="text"]:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #0073aa;
}

.char-counter {
    text-align: right;
    margin-top: 5px;
    font-size: 12px;
    color: #666;
}

.option-input {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
    align-items: center;
}

.option-input input {
    flex: 1;
}

.remove-option {
    background: #d63638;
    color: white;
    border: none;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 18px;
    line-height: 1;
    transition: background-color 0.2s ease;
}

.remove-option:hover:not(:disabled) {
    background: #b32d2e;
}

.remove-option:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.btn-add-option {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    color: #0073aa;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
    margin-bottom: 10px;
}

.btn-add-option:hover {
    background: #e9ecef;
    border-color: #0073aa;
}

.help-text {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: #666;
    font-style: italic;
}

.form-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.btn {
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.btn-primary {
    background: #0073aa;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #005a87;
}

.btn-primary:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.btn-secondary {
    background: #f8f9fa;
    color: #666;
    border: 2px solid #dee2e6;
}

.btn-secondary:hover {
    background: #e9ecef;
    color: #333;
}

.spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.submit-result {
    margin-top: 20px;
    padding: 20px;
    border-radius: 8px;
}

.success-message {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    padding: 15px;
    border-radius: 8px;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    padding: 15px;
    border-radius: 8px;
}

.success-message h4,
.error-message h4 {
    margin: 0 0 10px 0;
    font-size: 16px;
}

.success-message p,
.error-message p {
    margin: 0;
    font-size: 14px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .wpcs-poll-submit-form {
        margin: 20px;
        padding: 20px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
    
    .option-input {
        flex-direction: column;
        align-items: stretch;
    }
    
    .remove-option {
        align-self: flex-end;
        margin-top: 5px;
    }
}
</style>