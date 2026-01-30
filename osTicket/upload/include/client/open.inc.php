<?php
if(!defined('OSTCLIENTINC')) die('Access Denied!');

// --- 1. SETUP DATA ---
$topicId = $_POST['topicId'] ?? 0;

// Get the User Form (Contact Info) from osTicket System
$userForm = UserForm::getUserForm()->getForm($_POST);

// Fetch Help Topics
$rawTopics = Topic::getHelpTopics(false, false, true);
$allTopics = array();
if (is_array($rawTopics)) {
    foreach ($rawTopics as $id => $name) {
        $allTopics[$id] = array('topic' => (string)$name);
    }
}

// Javascript State
$serverState = array(
    'topicId' => (int)$topicId,
    'errors' => (isset($errors) && $errors) ? $errors : null,
);
?>

<div id="open-ticket-app">
    
    <div id="error-banner" class="error-banner" style="<?php echo ($errors) ? '' : 'display:none;'; ?>">
        <i class="icon-warning-sign"></i>
        <strong><?php echo __('Please correct the following errors:'); ?></strong>
        <ul id="error-list">
            <?php 
            if ($errors) {
                // Print the main generic error first if it exists
                if (isset($errors['err'])) {
                    echo "<li>" . Format::htmlchars($errors['err']) . "</li>";
                }

                // Loop through specific field errors
                foreach($errors as $key => $msg) {
                    // Skip 'err' as we already printed it
                    if ($key == 'err') continue;

                    // CHECK IF ERROR IS AN ARRAY (Fixes "Array" output)
                    if (is_array($msg)) {
                        foreach ($msg as $subMsg) {
                            if (is_string($subMsg)) {
                                echo "<li>" . Format::htmlchars($subMsg) . "</li>";
                            }
                        }
                    } elseif (is_string($msg)) {
                        echo "<li>" . Format::htmlchars($msg) . "</li>";
                    }
                }
            }
            ?>
        </ul>
    </div>

    <form id="ticketForm" method="post" action="open.php" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="a" value="open">
        <input type="hidden" name="topicId" id="topicId" value="<?php echo Format::htmlchars($topicId); ?>">

        <div class="modern-card">
            <div class="card-title"><div class="step-number">1</div><?php echo __('Contact Information'); ?></div>
            <div class="form-section">
                <?php if (!isset($thisclient) || !$thisclient) { ?>
                    
                    <div class="user-fields">
                        <?php 
                        // DYNAMICALLY RENDER USER FIELDS
                        foreach ($userForm->getFields() as $f) {
                            if (!$f->isVisibleToUsers()) continue;
                            ?>
                            <div class="form-group <?php if (isset($errors[$f->get('name')])) echo 'has-error'; ?>">
                                <label class="<?php if ($f->isRequired()) echo 'required'; ?>">
                                    <?php echo $f->get('label'); ?>
                                </label>
                                
                                <?php 
                                    $f->render(array('class' => 'form-control')); 
                                ?>

                                <?php if (isset($errors[$f->get('name')])) { ?>
                                    <div class="error-text">
                                        <?php 
                                        $fieldError = $errors[$f->get('name')];
                                        echo is_array($fieldError) ? implode(', ', $fieldError) : $fieldError; 
                                        ?>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>

                <?php } else { ?>
                    <div class="static-val">
                        <i class="icon-user"></i>
                        <strong><?php echo Format::htmlchars($thisclient->getName()); ?></strong> 
                        &lt;<?php echo $thisclient->getEmail(); ?>&gt;
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
                <div class="search-box-container">
                    <i class="icon-search search-icon"></i>
                    <input type="text" id="topic-search" class="form-control search-input" 
                           placeholder="<?php echo __('Search for a topic (e.g. Printer, Login)...'); ?>" 
                           onkeyup="app.handleSearch(this.value)">
                </div>

                <div id="topic-breadcrumb" class="breadcrumb-nav">
                    <span class="breadcrumb-item active" onclick="app.renderTopLevel()"><?php echo __('All Categories'); ?></span>
                </div>

                <div id="topic-cards-container" class="topic-grid"></div>

                <div id="dynamic-form" style="margin-top: 20px;">
                    <?php if ($topicId) {
                        $topic = Topic::lookup($topicId);
                    } ?>
                    <div id="loading-spinner" style="display:none; text-align:center; padding:20px; color:#ccc;">
                        <i class="icon-spinner icon-spin icon-3x"></i><br><?php echo __('Loading form...'); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions" style="margin-top:20px;">
            <button type="submit" class="btn-primary"><?php echo __('Create Ticket');?></button>
            <button type="reset" class="btn-secondary"><?php echo __('Reset');?></button>
        </div>
    </form>
</div>

<script type="text/javascript">
const RAW_TOPICS = <?php echo ($t = json_encode($allTopics)) ? $t : '{}'; ?>;
const STATE = <?php echo ($s = json_encode($serverState)) ? $s : '{}'; ?>;

const app = {
    init: function() {
        if (Object.keys(RAW_TOPICS).length === 0) console.warn("No topics found.");

        this.renderTopLevel();
        
        // RESTORE STATE (If page reloaded due to error)
        if (STATE.topicId && STATE.topicId > 0) {
            console.log("Restoring Topic:", STATE.topicId);
            this.selectTopic(STATE.topicId, true);
        }
    },

    handleSearch: function(query) {
        query = query.toLowerCase().trim();
        if (query === '') { this.renderTopLevel(); return; }

        $('#topic-breadcrumb').hide();
        $('#topic-cards-container').empty();

        let matchCount = 0;
        Object.keys(RAW_TOPICS).forEach(id => {
            let t = RAW_TOPICS[id];
            let tName = (typeof t === 'string') ? t : t.topic;
            if (tName.toLowerCase().indexOf(query) !== -1) {
                this.createCard(id, tName); 
                matchCount++;
            }
        });

        if (matchCount === 0) {
            $('#topic-cards-container').html('<div class="no-results">No topics found matching "' + query + '"</div>');
        }
    },

    renderTopLevel: function() {
        $('#topic-search').val('');
        $('#topic-breadcrumb').show();
        this.updateBreadcrumb([]);
        $('#topic-cards-container').empty();
        
        Object.keys(RAW_TOPICS).forEach(id => {
            let t = RAW_TOPICS[id];
            let tName = (typeof t === 'string') ? t : t.topic;
            if (tName && tName.indexOf(' / ') === -1) {
                this.createCard(id, tName);
            }
        });
        
        if ($('#topicId').val() == '') {
            $('#dynamic-form').children().not('#loading-spinner').remove();
        }
    },

    renderSubTopics: function(parentName) {
        $('#topic-search').val('');
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
        const safeName = tName.replace(/'/g, "\\'");
        const action = hasChildren ? `app.renderSubTopics('${safeName}')` : `app.selectTopic(${id})`;
        
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
                if (response.media) $(document.head).append(response.media);

                setTimeout(() => {
                    $('.richtext').each(function() {
                        $(document).trigger('ost:load-quill', [$(this)]);
                    });
                }, 100);
            }
        });
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
    
    .search-box-container { position: relative; margin-bottom: 15px; }
    .search-input { padding-left: 35px !important; width: 100%; box-sizing: border-box; }
    .search-icon { position: absolute; left: 12px; top: 11px; color: #999; font-size: 1.1em; }
    .no-results { grid-column: 1 / -1; text-align: center; color: #999; padding: 20px; }

    .topic-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }
    .topic-card { background: #f9f9f9; border: 1px solid #eee; border-radius: 6px; padding: 15px; text-align: center; cursor: pointer; transition: 0.2s; word-wrap: break-word; }
    .topic-card:hover { border-color: #005fb8; transform: translateY(-2px); background: #fff; }
    .topic-card.active { background: #e6f0fa; border-color: #005fb8; }
    .topic-card i { font-size: 2em; color: #005fb8; margin-bottom: 10px; display: block; }

    .form-group { margin-bottom: 15px; }
    .form-control { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    label.required:after { content:" *"; color: red; }
    .has-error .form-control { border-color: #d9534f; background: #fff5f5; }
    .error-text { color: #d9534f; font-size: 0.9em; margin-top: 5px; }
    
    .btn-primary { background: #005fb8; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 1em; }
    .btn-primary:hover { background: #004a91; }
    .btn-secondary { background: #eee; color: #333; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-left: 10px; }
    
    .breadcrumb-nav { background: #eee; padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; font-size: 0.9em; }
    .breadcrumb-item { color: #005fb8; cursor: pointer; text-decoration: underline; }
    .sep { margin: 0 5px; color: #999; }
    
    .error-banner { background: #fff0f0; border-left: 4px solid #d9534f; color: #d9534f; padding: 15px; margin-bottom: 20px; }
</style>