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
        // Skip Contact Information form as it's handled separately
        if (strtolower(trim($F->getTitle())) === strtolower(__('Contact Information'))) continue;
        if ($_POST) {
            $F = $F->instanciate();
            $F->isValidForClient();
        }
        $forms[] = $F->getForm();
    }
}
?>

<div id="open-ticket-wrapper" class="open-ticket-page">
    <div class="form-header-main">
        <h1><?php echo __('Open a New Ticket');?></h1>
        <p><?php echo __('Please provide the details below so we can assist you.');?></p>
    </div>

    <form id="ticketForm" method="post" action="open.php" enctype="multipart/form-data">
        <?php csrf_token(); ?>
        <input type="hidden" name="a" value="open">

        <div class="modern-card">
            <div class="card-title">
                <div class="step-number">1</div>
                <?php echo __('Contact Information'); ?>
            </div>
            <div class="form-section">
                <?php if (!$thisclient) {
                    $uform = UserForm::getUserForm()->getForm($_POST);
                    if ($_POST) $uform->isValid();
                    $uform->render(array('staff' => false, 'mode' => 'create'));
                } else { ?>
                    <div class="form-row-flex">
                        <div class="info-block">
                            <label><?php echo __('Email Address'); ?></label>
                            <div class="static-val"><?php echo $thisclient->getEmail(); ?></div>
                        </div>
                        <div class="info-block">
                            <label><?php echo __('Client Name'); ?></label>
                            <div class="static-val"><?php echo Format::htmlchars($thisclient->getName()); ?></div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="modern-card">
            <div class="card-title">
                <div class="step-number">2</div>
                <?php echo __('Ticket Details'); ?>
            </div>
            
            <div class="form-section">
                <div class="input-group">
                    <label for="topicId"><?php echo __('Help Topic'); ?> <span class="required-star">*</span></label>
                    <select id="topicId" name="topicId" onchange="javascript:
                            var data = $(':input[name]', '#dynamic-form').serialize();
                            $.ajax(
                              'ajax.php/form/help-topic/' + this.value,
                              {
                                data: data,
                                dataType: 'json',
                                success: function(json) {
                                  $('#dynamic-form').empty().append(json.html);
                                  $(document.head).append(json.media);
                                }
                              });">
                        <option value="" selected="selected">&mdash; <?php echo __('Select a Help Topic');?> &mdash;</option>
                        <?php
                        if($topics=Topic::getPublicHelpTopics()) {
                            foreach($topics as $id =>$name) {
                                echo sprintf('<option value="%d" %s>%s</option>',
                                        $id, ($info['topicId']==$id)?'selected="selected"':'', $name);
                            }
                        } ?>
                    </select>
                    <div class="error-msg"><?php echo $errors['topicId']; ?></div>
                </div>

                <div id="dynamic-form">
                    <?php
                    foreach ($forms as $form) {
                        include(CLIENTINC_DIR . 'templates/dynamic-form.tmpl.php');
                    } ?>
                </div>
            </div>
        </div>

        <?php if($cfg && $cfg->isCaptchaEnabled() && (!$thisclient || !$thisclient->isValid())) { ?>
        <div class="modern-card">
            <div class="card-title"><?php echo __('Verification');?></div>
            <div class="captcha-flex">
                <img src="captcha.php" border="0" alt="captcha">
                <input id="captcha" type="text" name="captcha" size="6" placeholder="<?php echo __('Enter code'); ?>">
            </div>
            <div class="error-msg"><?php echo $errors['captcha']; ?></div>
        </div>
        <?php } ?>

        <div class="form-actions">
            <button type="submit" class="btn-primary"><?php echo __('Create Ticket');?></button>
            <button type="button" class="btn-secondary" onclick="window.location.href='index.php';"><?php echo __('Cancel'); ?></button>
        </div>
    </form>
</div>