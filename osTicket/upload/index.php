<?php
/*********************************************************************
    index.php

    Helpdesk landing page.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com
**********************************************************************/
require('client.inc.php');
require_once INCLUDE_DIR . 'class.page.php';

$section = 'home';
require(CLIENTINC_DIR.'header.inc.php');
?>

<div id="landing_page" style="display: flex; flex-direction: column; align-items: center; text-align: center; width: 100%;">
    
    <?php 
    // 1. Fetch the Landing Image URL from your new custom config setting
    $landingImg = ($cfg) ? $cfg->get('landing_image_url') : null;
    if ($landingImg) { ?>
        <div class="hp-banner" style="width: 100%; overflow: hidden; margin-bottom: 20px;">
            <img src="<?php echo $landingImg; ?>" style="max-width: 100%; height: auto; display: block; margin: 0 auto;" />
        </div>
    <?php } ?>

    <div class="main-content" style="width: 90%; max-width: 960px; margin: 0 auto;">
        <div class="thread-body" style="margin-bottom: 30px;">
            <?php
            // 2. Fetch and display the actual "Landing Page" content from Admin Panel
            if($cfg && ($page = $cfg->getLandingPage())) {
                echo $page->getBodyWithImages();
            } else {
                echo '<h1>'.__('Welcome to the Support Center').'</h1>';
            }
            ?>
        </div>

        <?php 
        // 3. Include the sidebar templates (Search, Sign In, etc.)
        // This is centered because of the parent flex-column
        include CLIENTINC_DIR.'templates/sidebar.tmpl.php'; 
        ?>

        <div class="kb-section" style="margin-top: 40px; text-align: left;">
            <?php
            if($cfg && $cfg->isKnowledgebaseEnabled()){
                $cats = Category::getFeatured();
                if ($cats && count($cats) > 0) { ?>
                    <h1 style="text-align: center; border-bottom: 1px solid #ddd; padding-bottom: 10px;">
                        <?php echo __('Featured Knowledge Base Articles'); ?>
                    </h1>
                    
                    <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; margin-top: 20px;">
                    <?php
                    foreach ($cats as $C) { ?>
                        <div class="featured-category front-page" style="flex: 1; min-width: 300px; max-width: 450px;">
                            <i class="icon-folder-open icon-2x"></i>
                            <div class="category-name" style="font-weight: bold; font-size: 1.2em; margin-bottom: 10px;">
                                <?php echo $C->getName(); ?>
                            </div>
                            <?php foreach ($C->getTopArticles() as $F) { ?>
                                <div class="article-headline" style="margin-bottom: 15px;">
                                    <div class="article-title">
                                        <a href="<?php echo ROOT_PATH; ?>kb/faq.php?id=<?php echo $F->getId(); ?>">
                                            <?php echo $F->getQuestion(); ?>
                                        </a>
                                    </div>
                                    <div class="article-teaser" style="color: #666; font-size: 0.9em;">
                                        <?php echo $F->getTeaser(); ?>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                    </div>
                <?php }
            } ?>
        </div>
    </div>
</div>

<?php require(CLIENTINC_DIR.'footer.inc.php'); ?>