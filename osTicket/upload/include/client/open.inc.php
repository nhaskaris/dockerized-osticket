<?php
if(!defined('OSTCLIENTINC')) die('Access Denied!');

// --- 1. PREPARE DATA FOR JAVASCRIPT ---
// We simply capture the state and encode it as JSON.
// We do NOT render the form here.
$serverState = array(
    'topicId' => isset($_POST['topicId']) ? (int)$_POST['topicId'] : 0,
    'formData' => ($_POST) ? $_POST : null,
    'errors' => ($errors) ? $errors : null,
    'user' => ($thisclient) ? array(
        'name' => $thisclient->getName(),
        'email' => $thisclient->getEmail(),
        'phone' => $thisclient->getPhoneNumber()
    ) : null
);

$allTopics = Topic::getHelpTopics(true, false, true, array(), true); 
?>

<div id="open-ticket-app">
    
    <div id="error-banner" class="error-banner" style="display:none;">
        <i class="icon-warning-sign"></i>
        <strong><?php echo __('Please correct the following errors:'); ?></strong>
        <ul id="error-list"></ul>
    </div>

    <form id="ticketForm" method="post" action="open.php" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="a" value="open">
        <input type="hidden" name="topicId" id="topicId" value="">

        <div class="modern-card">
            <div class="card-title"><div class="step-number">1</div><?php echo __('Contact Information'); ?></div>
            <div class="form-section">
                <?php if (!$thisclient) { ?>
                    <div class="user-fields">
                        <div class="form-group">
                            <label class="required"><?php echo __('Email Address'); ?></label>
                            <input type="email" name="email" class="form-control" value="<?php echo $serverState['formData']['email'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="required"><?php echo __('Full Name'); ?></label>
                            <input type="text" name="name" class="form-control" value="<?php echo $serverState['formData']['name'] ?? ''; ?>">
                        </div>
                        <div class="form-group">
                            <label><?php echo __('Phone Number'); ?></label>
                            <input type="text" name="phone" class="form-control" value="<?php echo $serverState['formData']['phone'] ?? ''; ?>">
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="static-val">
                        <strong><?php echo $thisclient->getName(); ?></strong> (<?php echo $thisclient->getEmail(); ?>)
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="modern-card">
            <div class="card-title">
                <div class="step-number">2</div>
                <span id="topic-title"><?php echo __('Help Topic'); ?></span>
            </div>
            
            <div class="form-section">
                <div id="topic-breadcrumb" class="breadcrumb-nav">
                    <span class="breadcrumb-item active" onclick="app.renderTopLevel()"><?php echo __('All Categories'); ?></span>
                </div>

                <div id="topic-cards-container" class="topic-grid"></div>

                <div id="dynamic-form" style="margin-top: 20px;">
                    <div id="loading-spinner" style="display:none; text-align:center; padding:20px; color:#ccc;">
                        <i class="icon-spinner icon-spin icon-3x"></i><br><?php echo __('Loading form...'); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions" style="margin-top:20px;">
            <button type="submit" class="btn-primary"><?php echo __('Create Ticket');?></button>
        </div>
    </form>
</div>

<script type="text/javascript">
const RAW_TOPICS = <?php echo json_encode($allTopics); ?>;
const STATE = <?php echo json_encode($serverState); ?>;

const app = {
    init: function() {
        this.renderTopLevel();
        
        // If we have state from a failed submission (or draft), restore it
        if (STATE.topicId && STATE.topicId > 0) {
            console.log("Restoring State for Topic:", STATE.topicId);
            this.selectTopic(STATE.topicId, true); // true = restoration mode
        }
    },

    renderTopLevel: function() {
        this.updateBreadcrumb([]);
        $('#topic-cards-container').empty();
        Object.keys(RAW_TOPICS).forEach(id => {
            if (RAW_TOPICS[id].topic.indexOf(' / ') === -1) {
                this.createCard(id, RAW_TOPICS[id].topic);
            }
        });
        $('#dynamic-form').children().not('#loading-spinner').remove();
        $('#topicId').val('');
    },

    renderSubTopics: function(parentName) {
        this.updateBreadcrumb(parentName.split(' / '));
        $('#topic-cards-container').empty();
        Object.keys(RAW_TOPICS).forEach(id => {
            const topic = RAW_TOPICS[id];
            if (topic.topic.startsWith(parentName + ' / ')) {
                const parts = topic.topic.split(' / ');
                if (parts.length === (parentName.split(' / ').length + 1)) {
                    this.createCard(id, parts[parts.length - 1]);
                }
            }
        });
    },

    createCard: function(id, name) {
        const topic = RAW_TOPICS[id];
        const hasChildren = Object.values(RAW_TOPICS).some(t => t.topic.startsWith(topic.topic + ' / '));
        const action = hasChildren 
            ? `app.renderSubTopics('${topic.topic.replace(/'/g, "\\'")}')` 
            : `app.selectTopic(${id})`;
        
        const html = `
            <div class="topic-card ${hasChildren ? '' : 'selectable'}" id="card-${id}" onclick="${action}">
                <div class="card-content">
                    <i class="${hasChildren ? 'icon-folder-open' : 'icon-file-text-alt'}"></i>
                    <span>${name}</span>
                </div>
            </div>`;
        $('#topic-cards-container').append(html);
    },

    // THE CORE LOGIC: Fetch form and Restore Data
    selectTopic: function(id, isRestoring = false) {
        // 1. Update UI
        $('.topic-card').removeClass('active');
        $(`#card-${id}`).addClass('active');
        $('#topicId').val(id);
        
        // Handle Breadcrumb restoration if deeply nested
        if(isRestoring) {
            const topic = RAW_TOPICS[id];
            if(topic && topic.topic.includes(' / ')) {
                 const parentPath = topic.topic.substring(0, topic.topic.lastIndexOf(' / '));
                 this.renderSubTopics(parentPath);
                 $(`#card-${id}`).addClass('active'); // re-apply active after render
            }
        }

        // 2. Fetch Form via API
        $('#loading-spinner').show();
        $('#dynamic-form').children().not('#loading-spinner').remove();
        
        // We send current form data so osTicket MIGHT pre-fill some simple fields
        const payload = $('#ticketForm').serialize();
        
        $.ajax({
            url: 'ajax.php/form/help-topic/' + id,
            type: 'GET',
            data: payload,
            dataType: 'json',
            success: function(response) {
                $('#loading-spinner').hide();
                $('#dynamic-form').append(response.html);
                
                // Inject media (scripts/styles) required by the form
                if (response.media) {
                    $(document.head).append(response.media);
                }

                // 3. IF RESTORING: MANUALLY RE-POPULATE FIELDS
                // Because fetching a fresh form wipes user input, we put it back from PHP state
                if (isRestoring && STATE.formData) {
                    app.restoreFormData(STATE.formData);
                    app.showErrors(STATE.errors);
                }
                
                // 4. Force Editor Init (The standard osTicket way)
                setTimeout(() => {
                    $('.richtext').each(function() {
                        $(document).trigger('ost:load-quill', [$(this)]);
                    });
                }, 500);
            }
        });
    },

    restoreFormData: function(data) {
        console.log("Restoring form values...");
        $.each(data, function(key, value) {
            // Find inputs with this name
            let $el = $(`[name="${key}"]`);
            if ($el.length) {
                $el.val(value);
            }
            // Handle array fields (osTicket often uses name="field[]")
            // This is a basic implementation; complex fields might need more logic
        });
        
        // Special Case: Restore the Message/Body for the Editor
        if (data.message) {
             $('textarea[name="message"]').val(data.message);
        }
    },

    showErrors: function(errors) {
        if (!errors) return;
        $('#error-banner').show();
        $('#error-list').empty();
        
        // General Errors
        if (typeof errors === 'string') {
            $('#error-list').append(`<li>${errors}</li>`);
        } else if (errors.err) {
            $('#error-list').append(`<li>${errors.err}</li>`);
        }

        // Field Specific Errors
        $.each(errors, function(key, msg) {
            if (key === 'err') return;
            $('#error-list').append(`<li>${msg}</li>`);
            
            // Highlight the field
            $(`[name="${key}"]`).addClass('error-field');
            $(`[name="${key}"]`).closest('.form-group').addClass('has-error');
        });

        // Scroll to errors
        $('html, body').animate({ scrollTop: $('#error-banner').offset().top - 50 }, 500);
    },

    updateBreadcrumb: function(parts) {
        const nav = $('#topic-breadcrumb');
        nav.empty();
        nav.append(`<span class="breadcrumb-item" onclick="app.renderTopLevel()"><?php echo __('All'); ?></span>`);
        let path = "";
        parts.forEach((p, i) => {
            path += (i === 0) ? p : " / " + p;
            nav.append(` <span class="sep">/</span> <span class="breadcrumb-item" onclick="app.renderSubTopics('${path.replace(/'/g, "\\'")}')">${p}</span>`);
        });
    }
};

$(document).ready(function() {
    app.init();
});
</script>

<style>
    /* Minimal Styles for the App */
    .modern-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    .card-title { font-size: 1.2rem; font-weight: bold; margin-bottom: 15px; display: flex; align-items: center; }
    .step-number { background: #005fb8; color: #fff; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 10px; font-size: 0.8rem; }
    
    .topic-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }
    .topic-card { background: #f9f9f9; border: 1px solid #eee; border-radius: 6px; padding: 15px; text-align: center; cursor: pointer; transition: 0.2s; }
    .topic-card:hover { border-color: #005fb8; transform: translateY(-2px); background: #fff; }
    .topic-card.active { background: #e6f0fa; border-color: #005fb8; }
    .topic-card i { font-size: 2em; color: #005fb8; margin-bottom: 10px; display: block; }
    
    .breadcrumb-nav { background: #eee; padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; font-size: 0.9em; }
    .breadcrumb-item { color: #005fb8; cursor: pointer; text-decoration: underline; }
    .sep { margin: 0 5px; color: #999; }
    
    .error-banner { background: #fff0f0; border-left: 4px solid #d9534f; color: #d9534f; padding: 15px; margin-bottom: 20px; }
    .error-field { border-color: #d9534f !important; background: #fff5f5; }
    
    .form-group { margin-bottom: 15px; }
    .form-control { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    label.required:after { content:" *"; color: red; }
</style>