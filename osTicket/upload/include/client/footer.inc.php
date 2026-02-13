</div></div>
<div id="footer" style="width:100%;box-sizing:border-box;">
    <div class="footer-content">
        <div class="footer-left">
            <p><?php echo __('Copyright &copy;'); ?> <?php echo date('Y'); ?> <?php
            echo Format::htmlchars((string) $ost->company ?: 'osTicket.com'); ?> - <?php echo __('All rights reserved.'); ?></p>
        </div>
        
        <div class="footer-right">
            <a id="poweredBy" href="https://osticket.com" target="_blank">
                <?php echo __('Helpdesk software - powered by osTicket'); ?>
            </a>
        </div>
    </div>
</div>

<div id="overlay"></div>

<?php
if (($lang = Internationalization::getCurrentLanguage()) && $lang != 'en_US') { ?>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>ajax.php/i18n/<?php
        echo $lang; ?>/js"></script>
<?php } ?>

<script type="text/javascript">
    getConfig().resolve(<?php
        include INCLUDE_DIR . 'ajax.config.php';
        $api = new ConfigAjaxAPI();
        print $api->client(false);
    ?>);
</script>

<link rel="stylesheet" type="text/css" href="<?php echo ROOT_PATH; ?>css/select2.min.css"/>
<script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/select2.min.js"></script>

<script type="text/javascript">
$(function() {
    // Function to initialize Select2 on our custom fields
    var initSearchableChoices = function() {
        $('select.searchable-choice').each(function() {
            var $this = $(this);
            // Prevent double-initialization
            if (!$this.hasClass('select2-offscreen')) {
                $this.select2({
                    width: '100%', // Matches the width of the original select
                    allowClear: true,
                    minimumResultsForSearch: 0, // Always show the search box
                    dropdownAutoWidth: true
                });
            }
        });
    };

    // 1. Run on initial page load
    initSearchableChoices();

    // 2. Run whenever osTicket finishes loading AJAX content (like dialog popups)
    $(document).ajaxComplete(function() {
        initSearchableChoices();
    });
});
</script>

<style>
/* --- Global Fixes --- */
.select2-container.searchable-choice {
    max-width: 100% !important;
}

/* --- Dropdown Alignment Fix --- */
/* This ensures the dropdown doesn't fly to the left edge of the screen */
.select2-drop-active {
    border-top: 0;
    margin-top: -2px;
    /* Force it to stay relative to where the input is */
    left: auto !important; 
    width: inherit !important;
}

/* --- Mobile Specific Fixes --- */
@media screen and (max-width: 760px) {
    
    /* Force everything to 100% width and centered */
    .select2-container, 
    .select2-container.searchable-choice,
    .select2-choice,
    .select2-drop {
        width: 100% !important;
        min-width: 100% !important;
        box-sizing: border-box !important;
    }

    /* THE KEY FIX: Reset the left/right calculation */
    .select2-drop {
        left: 0 !important;
        right: 0 !important;
        position: absolute !important;
    }

    /* Make it look better for touch */
    .select2-container .select2-choice {
        height: 44px !important;
        line-height: 44px !important;
        font-size: 16px !important;
        display: flex !important;
        align-items: center;
        justify-content: space-between;
    }

    .select2-container .select2-choice .select2-arrow b {
        background-position: 0 10px !important;
    }

    /* Make search results easier to tap */
    .select2-results .select2-result-label {
        padding: 12px 15px !important;
        font-size: 16px !important;
    }
}
</style>

</body>
</html>