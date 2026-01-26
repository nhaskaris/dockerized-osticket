<?php
if(!defined('OSTCLIENTINC')) die('Access Denied!');

$info=array();
if($thisclient && $thisclient->isValid()) {
    $info=array('name'=>$thisclient->getName(),
                'email'=>$thisclient->getEmail(),
                'phone'=>$thisclient->getPhoneNumber());
}
$info=($_POST && $errors)?Format::htmlchars($_POST):$info;

$forms = array();
if ($info['topicId'] && ($topic=Topic::lookup($info['topicId']))) {
    foreach ($topic->getForms() as $F) {
        if (!$F->hasAnyVisibleFields()) continue;
        if (strtolower(trim($F->getTitle())) === strtolower(__('Contact Information'))) continue;
        if ($_POST) {
            $F = $F->instanciate();
            $F->isValidForClient();
        }
        $forms[] = $F->getForm();
    }
}

$allTopics = Topic::getHelpTopics(true, false, true, array(), true); 
?>

<div id="open-ticket-wrapper" class="open-ticket-page">
    <form id="ticketForm" method="post" action="open.php" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="a" value="open">

        <div class="modern-card">
            <div class="card-title"><div class="step-number">1</div><?php echo __('Contact Information'); ?></div>
            <div class="form-section">
                <?php if (!$thisclient) {
                    $uform = UserForm::getUserForm()->getForm($_POST);
                    $uform->render(array('staff' => false, 'mode' => 'create'));
                } else { ?>
                    <div class="static-val"><strong><?php echo $thisclient->getName(); ?></strong> (<?php echo $thisclient->getEmail(); ?>)</div>
                <?php } ?>
            </div>
        </div>

        <div class="modern-card">
            <div class="card-title">
                <div class="step-number">2</div>
                <span id="topic-title"><?php echo __('Help Topic'); ?></span>
            </div>
            
            <div class="form-section">
                <div class="search-container">
                    <input type="text" id="topic-search" placeholder="<?php echo __('Search for a topic...'); ?>" onkeyup="filterTopics()">
                </div>
                <div id="topic-breadcrumb" class="breadcrumb-nav">
                    <span class="breadcrumb-item active" onclick="renderTopLevel()"><?php echo __('All Categories'); ?></span>
                </div>

                <select id="topicId" name="topicId" style="display:none;" onchange="javascript:
                        var data = $(':input[name]', '#dynamic-form').serialize();
                        $.ajax('ajax.php/form/help-topic/' + this.value, {
                            data: data, dataType: 'json',
                            success: function(json) {
                                $('#dynamic-form').empty().append(json.html);
                                $(document.head).append(json.media);
                            }
                        });">
                    <option value=""><?php echo __('Select a Help Topic');?></option>
                    <?php foreach($allTopics as $id => $T) {
                        echo sprintf('<option value="%d">%s</option>', $id, Format::htmlchars($T['topic']));
                    } ?>
                </select>

                <div id="topic-cards-container" class="topic-grid"></div>

                <div id="dynamic-form" style="margin-top: 20px;">
                    <?php foreach ($forms as $form) { include(CLIENTINC_DIR . 'templates/dynamic-form.tmpl.php'); } ?>
                </div>
            </div>
        </div>

        <div class="form-actions" style="margin-top:20px;">
            <button type="submit" class="btn-primary"><?php echo __('Create Ticket');?></button>
        </div>
    </form>
</div>



<script type="text/javascript">
const rawTopics = <?php echo json_encode($allTopics); ?>;

function renderTopLevel() {
    $('#back-btn').hide();
    $('#topic-search').val('');
    updateBreadcrumb([]);
    const container = $('#topic-cards-container');
    container.empty();

    Object.keys(rawTopics).forEach(id => {
        const topic = rawTopics[id];
        if (topic.topic.indexOf(' / ') === -1) {
            container.append(createCard(id, topic.topic));
        }
    });
}

function renderSubTopics(parentName) {
    $('#back-btn').show();
    updateBreadcrumb(parentName.split(' / '));
    const container = $('#topic-cards-container');
    container.empty();

    Object.keys(rawTopics).forEach(id => {
        const topic = rawTopics[id];
        if (topic.topic.startsWith(parentName + ' / ')) {
            const parts = topic.topic.split(' / ');
            if (parts.length === (parentName.split(' / ').length + 1)) {
                container.append(createCard(id, parts[parts.length - 1]));
            }
        }
    });
}

function createCard(id, displayName) {
    const topic = rawTopics[id];
    const hasChildren = Object.values(rawTopics).some(t => t.topic.startsWith(topic.topic + ' / '));
    const isSelectable = topic.not_selectable != 1;
    
    // Logic: If it has children, the primary action is to drill down.
    // If it's selectable, we add a "Select This" button or allow direct click.
    let action = hasChildren ? `renderSubTopics('${topic.topic.replace(/'/g, "\\'")}')` : `selectFinalTopic(${id})`;
    let icon = hasChildren ? 'icon-folder-open' : 'icon-file-text-alt';
    
    let html = `<div class="topic-card ${!isSelectable && !hasChildren ? 'disabled' : ''}" id="card-${id}">`;
    
    // Main clickable area
    html += `<div class="card-main" onclick="${action}">
                <i class="${icon} card-icon"></i>
                <div class="card-label">${displayName}</div>
             </div>`;
    
    // If it has children AND is selectable, add a small "Select Only This" button
    if (hasChildren && isSelectable) {
        html += `<div class="card-action-bar">
                    <button type="button" class="btn-select-only" onclick="event.stopPropagation(); selectFinalTopic(${id})">
                        <?php echo __('Select This Topic'); ?>
                    </button>
                 </div>`;
    }
    
    html += `</div>`;
    return html;
}

function updateBreadcrumb(parts) {
    const nav = $('#topic-breadcrumb');
    nav.empty();
    nav.append(`<span class="breadcrumb-item" onclick="renderTopLevel()"><?php echo __('All'); ?></span>`);
    
    let currentPath = "";
    parts.forEach((p, index) => {
        currentPath += (index === 0) ? p : " / " + p;
        const isLast = index === parts.length - 1;
        nav.append(` <span class="sep">/</span> `);
        nav.append(`<span class="breadcrumb-item ${isLast ? 'active' : ''}" onclick="renderSubTopics('${currentPath.replace(/'/g, "\\'")}')">${p}</span>`);
    });
}

function filterTopics() {
    const val = $('#topic-search').val().toLowerCase();
    const container = $('#topic-cards-container');
    if (val.length < 1) { renderTopLevel(); return; }

    container.empty();
    $('#back-btn').show();
    Object.keys(rawTopics).forEach(id => {
        const topic = rawTopics[id];
        if (topic.topic.toLowerCase().includes(val) && topic.not_selectable != 1) {
            container.append(createCard(id, topic.topic));
        }
    });
}

function selectFinalTopic(id) {
    $('.topic-card').removeClass('active');
    $(`#card-${id}`).addClass('active');
    $('#topicId').val(id).trigger('change');
    $('html, body').animate({ scrollTop: $("#dynamic-form").offset().top - 50 }, 500);
}

$(document).ready(function() { renderTopLevel(); });
</script>

<style>
    .breadcrumb-nav { margin-bottom: 15px; font-size: 0.9em; color: #666; background: #f9f9f9; padding: 10px; border-radius: 6px; }
    .breadcrumb-item { cursor: pointer; color: #005fb8; text-decoration: underline; }
    .breadcrumb-item.active { color: #333; text-decoration: none; font-weight: bold; cursor: default; }
    .sep { color: #ccc; margin: 0 5px; }

    .topic-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }
    .topic-card { background: #fff; border: 1px solid #eef2f7; border-radius: 10px; display: flex; flex-direction: column; overflow: hidden; transition: 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    .card-main { padding: 20px; text-align: center; cursor: pointer; flex-grow: 1; }
    .topic-card:hover { border-color: #005fb8; transform: translateY(-2px); }
    .topic-card.active { border: 2px solid #005fb8; background: #f0f7ff; }
    .card-icon { font-size: 2em; color: #005fb8; margin-bottom: 8px; display: block; }
    .card-label { font-weight: bold; font-size: 0.9em; }

    .card-action-bar { border-top: 1px solid #eee; padding: 8px; background: #fafafa; }
    .btn-select-only { width: 100%; border: none; background: #eee; color: #555; font-size: 0.75em; padding: 5px; border-radius: 4px; cursor: pointer; transition: 0.2s; }
    .btn-select-only:hover { background: #005fb8; color: white; }
    .topic-card.disabled { opacity: 0.5; pointer-events: none; }
</style>