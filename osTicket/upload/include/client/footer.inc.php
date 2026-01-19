</div> </div> <div id="footer">
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
</body>
</html>