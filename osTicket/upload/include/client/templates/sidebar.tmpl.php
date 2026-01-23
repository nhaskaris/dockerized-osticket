<?php
$BUTTONS = isset($BUTTONS) ? $BUTTONS : true;

$show_sidebar = false;
if ($cfg->isKnowledgebaseEnabled()
    && ($faqs = FAQ::getFeatured()->select_related('category')->limit(5))
    && $faqs->all()) {
    $show_sidebar = true;
}

$resources = Page::getActivePages()->filter(array('type'=>'other'));
if ($resources->all()) {
    $show_sidebar = true;
}

if ($show_sidebar) {
?>
<div class="sidebar pull-right" style="width: 100%; margin-top: 20px;">
    <div class="content" style="display: flex; justify-content: space-around; flex-wrap: wrap;">
        <?php
        if ($cfg->isKnowledgebaseEnabled() && $faqs->all()) { ?>
            <section style="flex: 1; min-width: 250px; margin: 10px;">
                <div class="header"><?php echo __('Featured Questions'); ?></div>
                <?php foreach ($faqs as $F) { ?>
                    <div><a href="<?php echo ROOT_PATH; ?>kb/faq.php?id=<?php echo urlencode($F->getId()); ?>"><?php echo $F->getLocalQuestion(); ?></a></div>
                <?php } ?>
            </section>
        <?php }

        if ($resources->all()) { ?>
            <section style="flex: 1; min-width: 250px; margin: 10px;">
                <div class="header"><?php echo __('Other Resources'); ?></div>
                <?php foreach ($resources as $page) { ?>
                    <div><a href="<?php echo ROOT_PATH; ?>pages/<?php echo $page->getNameAsSlug(); ?>"><?php echo $page->getLocalName(); ?></a></div>
                <?php } ?>
            </section>
        <?php }
        ?>
    </div>
</div>
<?php
}
?>

