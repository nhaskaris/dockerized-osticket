<?php
// Return if no visible fields
global $thisclient;
if (!$form->hasAnyVisibleFields($thisclient))
    return;

$isCreate = (isset($options['mode']) && $options['mode'] == 'create');
?>
<div class="form-section-group">
    <div class="form-section-header">
        <h3><?php echo Format::htmlchars($form->getTitle()); ?></h3>
        <p><?php echo Format::display($form->getInstructions()); ?></p>
    </div>
    
    <div class="form-fields-container">
    <?php
    // Form fields, each with corresponding errors follows. Fields marked
    // 'private' are not included in the output for clients
    foreach ($form->getFields() as $field) {
        try {
            if (!$field->isEnabled())
                continue;
        }
        catch (Exception $e) {
            // Not connected to a DynamicFormField
        }

        if ($isCreate) {
            if (!$field->isVisibleToUsers() && !$field->isRequiredForUsers())
                continue;
        } elseif (!$field->isVisibleToUsers()) {
            continue;
        }
        ?>
        <div class="form-field-wrapper">
            <?php if (!$field->isBlockLevel()) { ?>
                <label for="<?php echo $field->getFormName(); ?>">
                    <span class="<?php if ($field->isRequiredForUsers()) echo 'required-label'; ?>">
                        <?php echo Format::htmlchars($field->getLocal('label')); ?>
                        <?php if ($field->isRequiredForUsers() &&
                                ($field->isEditableToUsers() || $isCreate) && $_POST) { ?>
                            <span class="error">*</span>
                        <?php } ?>
                    </span>
                    <?php if ($field->get('hint')) { ?>
                        <div class="field-hint-text">
                            <?php echo Format::viewableImages($field->getLocal('hint')); ?>
                        </div>
                    <?php } ?>
                </label>
            <?php } ?>
            
            <?php
            if ($field->isEditableToUsers() || $isCreate) {
                $field->render(array('client'=>true));
                foreach ($field->errors() as $e) { ?>
                    <div class="error"><?php echo $e; ?></div>
                <?php }
                $field->renderExtras(array('client'=>true));
            } else {
                $val = '';
                if ($field->value)
                    $val = $field->display($field->value);
                elseif (($a=$field->getAnswer()))
                    $val = $a->display();

                echo sprintf('<div class="static-value">%s</div>', $val);
            }
            ?>
        </div>
        <?php
    }
    ?>
    </div>
</div>

