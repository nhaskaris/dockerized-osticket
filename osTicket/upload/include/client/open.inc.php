<?php
if(!defined('OSTCLIENTINC')) die('Access Denied!');

// --- 1. PREPARE DATA FOR JAVASCRIPT ---

// Fetch raw topics from osTicket
// Args: publicOnly=false (show private if logged in), distinct=false, disabled=true (allow all)
$rawTopics = Topic::getHelpTopics(false, false, true);

// CRITICAL FIX: Convert simple list [ID => "Name"] to Object [ID => ["topic" => "Name"]]
// This prevents the JavaScript "undefined" error when reading .topic
$allTopics = array();
if (is_array($rawTopics)) {
    foreach ($rawTopics as $id => $name) {
        $allTopics[$id] = array('topic' => (string)$name);
    }
}

// Prepare Server State
// We use 'isset' checks to prevent PHP Notices/Warnings
$serverState = array(
    'topicId' => isset($_POST['topicId']) ? (int)$_POST['topicId'] : 0,
    'formData' => ($_POST) ? $_POST : null,
    'errors' => (isset($errors) && $errors) ? $errors : null,
    'user' => (isset($thisclient) && $thisclient) ? array(
        'name' => $thisclient->getName(),
        'email' => $thisclient->getEmail(),
        'phone' => $thisclient->getPhoneNumber()
    ) : null
);
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
                <?php if (!isset($thisclient) || !$thisclient) { ?>
                    <div class="user-fields">
                        <div class="form-group">
                            <label class="required"><?php echo __('Email Address'); ?></label>
                            <input type="email" name="email" class="form-control" value="<?php echo isset($serverState['formData']['email']) ? Format::htmlchars($serverState['formData']['email']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label class="required"><?php echo __('Full Name'); ?></label>
                            <input type="text" name="name" class="form-control" value="<?php echo isset($serverState['formData']['name']) ? Format::htmlchars($serverState['formData']['name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label><?php echo __('Phone Number'); ?></label>
                            <input type="text" name="phone" class="form-control" value="<?php echo isset($serverState['formData']['phone']) ? Format::htmlchars($serverState['formData']['phone']) : ''; ?>">
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="static-val">
                        <strong><?php echo Format::htmlchars($thisclient->getName()); ?></strong> (<?php echo $thisclient->getEmail(); ?>)
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
// CRITICAL FIX: We add "|| '{}'" so if PHP outputs nothing, JS gets an empty object instead of crashing.
const RAW_TOPICS = <?php echo ($t = json_encode($allTopics)) ? $t : '{}'; ?>;
const STATE = <?php echo ($s = json_encode($serverState)) ? $s : '{}'; ?>;

const app = {
    init: function() {
        // Safety check to ensure topics loaded
        if (Object.keys(RAW_TOPICS).length === 0) {
            console.warn("No topics found or JSON encoding failed.");
        }

        this.renderTopLevel();
        
        // Restore State if it exists
        if (STATE.topicId && STATE.topicId > 0) {
            console.log("Restoring State for Topic:", STATE.topicId);
            this.selectTopic(STATE.topicId, true);
        } else if (STATE.errors) {
            this.showErrors(STATE.errors);
        }
    },

    renderTopLevel: function() {
        this.updateBreadcrumb([]);
        $('#topic-cards-container').empty();
        
        Object.keys(RAW_TOPICS).forEach(id => {
            // Safe access to topic string
            let t = RAW_TOPICS[id];
            let tName = (typeof t === 'string') ? t : t.topic;

            if (tName && tName.indexOf(' / ') === -1) {
                this.createCard(id, tName);
            }
        });
        $('#dynamic-form').children().not('#loading-spinner').remove();
        $('#topicId').val('');
    },

    renderSubTopics: function(parentName) {
        this.updateBreadcrumb(parentName.split(' / '));
        $('#topic-cards-container').empty();
        
        Object.keys(RAW_TOPICS).forEach(id => {
            let t = RAW_TOPICS[id];
            let tName = (typeof t === 'string') ? t : t.topic;

            if (tName && tName.startsWith(parentName + ' / ')) {
                const parts = tName.split(' / ');
                if (parts.length === (parentName.split(' / ').length + 1)) {
                    this.createCard(id, parts[parts.length - 1]);
                }
            }
        });
    },

    createCard: function(id, name) {
        let t = RAW_TOPICS[id];
        let tName = (typeof t === 'string') ? t : t.topic;

        const hasChildren = Object.values(RAW_TOPICS).some(sub => {
            let subName = (typeof sub === 'string') ? sub : sub.topic;
            return subName.startsWith(tName + ' / ');
        });

        // Safe quote escaping
        const safeName = tName.replace(/'/g, "\\'");
        
        const action = hasChildren 
            ? `app.renderSubTopics('${safeName}')` 
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

    selectTopic: function(id, isRestoring = false) {
        $('.topic-card').removeClass('active');
        $(`#card-${id}`).addClass('active');
        $('#topicId').val(id);
        
        if(isRestoring) {
            let t = RAW_TOPICS[id];
            let tName = (typeof t === 'string') ? t : t.topic;
            if(tName && tName.includes(' / ')) {
                 const parentPath = tName.substring(0, tName.lastIndexOf(' / '));
                 this.renderSubTopics(parentPath);
                 $(`#card-${id}`).addClass('active');
            }
        }

        $('#loading-spinner').show();
        $('#dynamic-form').children().not('#loading-spinner').remove();
        
        const payload = $('#ticketForm').serialize();
        
        $.ajax({
            url: 'ajax.php/form/help-topic/' + id,
            type: 'GET',
            data: payload,
            dataType: 'json',
            success: function(response) {
                $('#loading-spinner').hide();
                $('#dynamic-form').append(response.html);
                
                if (response.media) {
                    $(document.head).append(response.media);
                }

                if (isRestoring && STATE.formData) {
                    app.restoreFormData(STATE.formData);
                    app.showErrors(STATE.errors);
                }
                
                setTimeout(() => {
                    $('.richtext').each(function() {
                        $(document).trigger('ost:load-quill', [$(this)]);
                    });
                }, 500);
            }
        });
    },

    restoreFormData: function(data) {
        $.each(data, function(key, value) {
            let $el = $(`[name="${key}"]`);
            if ($el.length) $el.val(value);
        });
        if (data.message) $('textarea[name="message"]').val(data.message);
    },

    showErrors: function(errors) {
        if (!errors) return;
        $('#error-banner').show();
        $('#error-list').empty();
        
        if (typeof errors === 'string') {
            $('#error-list').append(`<li>${errors}</li>`);
        } else if (errors.err) {
            $('#error-list').append(`<li>${errors.err}</li>`);
        }

        $.each(errors, function(key, msg) {
            if (key === 'err') return;
            $('#error-list').append(`<li>${msg}</li>`);
            $(`[name="${key}"]`).addClass('error-field');
            $(`[name="${key}"]`).closest('.form-group').addClass('has-error');
        });

        $('html, body').animate({ scrollTop: $('#error-banner').offset().top - 50 }, 500);
    },

    updateBreadcrumb: function(parts) {
        const nav = $('#topic-breadcrumb');
        nav.empty();
        nav.append(`<span class="breadcrumb-item" onclick="app.renderTopLevel()"><?php echo __('All Categories'); ?></span>`);
        let path = "";
        parts.forEach((p, i) => {
            path += (i === 0) ? p : " / " + p;
            const safePath = path.replace(/'/g, "\\'");
            nav.append(` <span class="sep">/</span> <span class="breadcrumb-item" onclick="app.renderSubTopics('${safePath}')">${p}</span>`);
        });
    }
};

$(document).ready(function() {
    app.init();
});
</script>

<style>
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