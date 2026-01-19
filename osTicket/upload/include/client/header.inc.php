<?php
$title=($cfg && is_object($cfg) && $cfg->getTitle())
    ? $cfg->getTitle() : 'osTicket :: '.__('Support Ticket System');
$signin_url = ROOT_PATH . "login.php"
    . ($thisclient ? "?e=".urlencode($thisclient->getEmail()) : "");
$signout_url = ROOT_PATH . "logout.php?auth=".$ost->getLinkToken();

header("Content-Type: text/html; charset=UTF-8");
header(
    "Content-Security-Policy: " .
    "frame-ancestors " . $cfg->getAllowIframes() . "; " .
    "img-src 'self' data:; " .
    "script-src 'self' " .
        "https://www.google.com " .
        "https://www.gstatic.com " .
        "https://www.recaptcha.net " .
        "https://challenges.cloudflare.com " .
        "'unsafe-inline'; " .
    "frame-src " .
        "https://www.google.com " .
        "https://www.recaptcha.net " .
        "https://challenges.cloudflare.com; " .
    "object-src 'none'"
);

if (($lang = Internationalization::getCurrentLanguage())) {
    $langs = array_unique(array($lang, $cfg->getPrimaryLanguage()));
    $langs = Internationalization::rfc1766($langs);
    header("Content-Language: ".implode(', ', $langs));
}
?>
<!DOCTYPE html>
<html<?php
if ($lang
        && ($info = Internationalization::getLanguageInfo($lang))
        && (@$info['direction'] == 'rtl'))
    echo ' dir="rtl" class="rtl"';
if ($lang) {
    echo ' lang="' . $lang . '"';
}

// Dropped IE Support Warning
if (osTicket::is_ie())
    $ost->setWarning(__('osTicket no longer supports Internet Explorer.'));
?>>
<head>
    <style>
        :root {
            /* Default osTicket Blue */
            <?php 
            $h_col = '#004976';
            $f_col = '#004976';
            if (isset($ost) && $ost && ($c = $ost->getConfig())) {
                $h_col = $c->get('header_color', '#004976');
                $f_col = $c->get('footer_color', '#004976');
            }
            echo "--header-bg: " . $h_col . ";";
            echo "--footer-bg: " . $f_col . ";";
            ?>
        }

        #header { background: var(--header-bg) !important; }
        #footer { background: var(--footer-bg) !important; }
        #banner { background-color: var(--header-bg) !important; }
    </style>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title><?php echo Format::htmlchars($title); ?></title>
    <meta name="description" content="customer support platform">
    <meta name="keywords" content="osTicket, Customer support system, support ticket system">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/modern/base.css">
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/modern/layout.css">
    <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/modern/forms.css">

    <?php if (strpos($_SERVER['SCRIPT_NAME'], 'login.php') !== false) { ?>
        <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/modern/auth.css">
    <?php } elseif (strpos($_SERVER['SCRIPT_NAME'], 'view.php') !== false) { ?>
        <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/modern/access.css">
    <?php } elseif (strpos($_SERVER['SCRIPT_NAME'], 'account.php') !== false) { ?>
        <link rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/modern/auth.css">
    <?php } ?>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/flags.css?53339df"/>
    <link type="text/css" rel="stylesheet" href="<?php echo ROOT_PATH; ?>css/select2.min.css?53339df"/>


    <!-- Favicons -->
    <link rel="icon" type="image/png" href="<?php echo ROOT_PATH ?>images/oscar-favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="<?php echo ROOT_PATH ?>images/oscar-favicon-16x16.png" sizes="16x16" />
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-3.7.0.min.js?53339df"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-ui-1.13.2.custom.min.js?53339df"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/jquery-ui-timepicker-addon.js?53339df"></script>
    <script src="<?php echo ROOT_PATH; ?>js/osticket.js?53339df"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/filedrop.field.js?53339df"></script>
    <script src="<?php echo ROOT_PATH; ?>js/bootstrap-typeahead.js?53339df"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor.min.js?53339df"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-plugins.js?53339df"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/redactor-osticket.js?53339df"></script>
    <script type="text/javascript" src="<?php echo ROOT_PATH; ?>js/select2.min.js?53339df"></script>
    <?php
    if($ost && ($headers=$ost->getExtraHeaders())) {
        echo "\n\t".implode("\n\t", $headers)."\n";
    }

    // Offer alternate links for search engines
    // @see https://support.google.com/webmasters/answer/189077?hl=en
    if (($all_langs = Internationalization::getConfiguredSystemLanguages())
        && (count($all_langs) > 1)
    ) {
        $langs = Internationalization::rfc1766(array_keys($all_langs));
        $qs = array();
        parse_str($_SERVER['QUERY_STRING'], $qs);
        foreach ($langs as $L) {
            $qs['lang'] = $L; ?>
        <link rel="alternate" href="//<?php echo $_SERVER['HTTP_HOST'] . htmlspecialchars($_SERVER['REQUEST_URI']); ?>?<?php
            echo http_build_query($qs); ?>" hreflang="<?php echo $L; ?>" />
<?php
        } ?>
        <link rel="alternate" href="//<?php echo $_SERVER['HTTP_HOST'] . htmlspecialchars($_SERVER['REQUEST_URI']); ?>"
            hreflang="x-default" />
<?php
    }
    ?>
</head>
<body>
    <div id="container">
        <?php
        if($ost->getError())
            echo sprintf('<div class="error_bar">%s</div>', $ost->getError());
        elseif($ost->getWarning())
            echo sprintf('<div class="warning_bar">%s</div>', $ost->getWarning());
        elseif($ost->getNotice())
            echo sprintf('<div class="notice_bar">%s</div>', $ost->getNotice());
        ?>
        <div id="header">
            <a class="pull-left" id="logo" href="<?php echo ROOT_PATH; ?>index.php"
            title="<?php echo __('Support Center'); ?>">
                <span class="valign-helper"></span>
                <img src="<?php echo ROOT_PATH; ?>logo.php" border=0 alt="<?php
                echo $ost->getConfig()->getTitle(); ?>">
            </a>
            <button id="nav-toggle" aria-expanded="false" aria-controls="main-nav">☰</button>
            <nav id="main-nav" role="navigation">
                <ul class="flush-left">
                    <!-- logo side; intentionally left empty to keep nav on the right -->
                </ul>
                <ul class="flush-right">
                    <?php
                    // Render primary nav items on the right (short labels)
                    if($nav && ($navs=$nav->getNavLinks()) && is_array($navs)){
                        $short_labels = array(
                            'home' => 'Home',
                            'kb'   => 'KB',
                            'new'  => 'New ticket',
                            'tickets' => 'Tickets',
                            'status' => 'Status'
                        );
                        foreach($navs as $name =>$nav) {
                            if (isset($short_labels[$name])) {
                                $candidate = $short_labels[$name];
                                $translated = __($candidate);
                                // If translation for the short label isn't available, fall back to the
                                // already-localized full description provided by the nav.
                                $label = ($translated === $candidate && $nav['desc']) ? $nav['desc'] : $translated;
                            } else {
                                $label = $nav['desc'];
                            }
                            echo sprintf('<li><a class="%s %s" href="%s">%s</a></li>%s',
                                $nav['active']?'active':'',$name,(ROOT_PATH.$nav['href']),Format::htmlchars($label),"\n");
                        }
                    }
                    ?>
                    <?php
                    if ($thisclient && is_object($thisclient) && $thisclient->isValid()
                        && !$thisclient->isGuest()) {
                        echo sprintf('<li><a href="%s">%s</a></li>',
                            ROOT_PATH.'profile.php', __('Profile'));
                        echo sprintf('<li><a href="%s">%s</a></li>',
                            $signout_url, __('Sign Out'));
                    } elseif($nav) {
                        // Show Guest label with a prominent Sign In CTA under it when registration is public
                        if ($cfg->getClientRegistrationMode() == 'public') {
                            echo '<li class="guest-cta">';
                            echo '<span class="guest-label">'.Format::htmlchars(__('Guest User')).'</span>';
                            if ($cfg->getClientRegistrationMode() != 'disabled') {
                                echo '<a class="signin-link" href="'.Format::htmlchars($signin_url).'">'.Format::htmlchars(__('Sign In')).'</a>';
                            }
                            echo '</li>';
                        } else {
                            if ($cfg->getClientRegistrationMode() != 'disabled') {
                                echo sprintf('<li><a href="%s">%s</a></li>', $signin_url, __('Sign In'));
                            }
                        }

                        if ($thisclient && $thisclient->isValid() && $thisclient->isGuest()) {
                            echo sprintf('<li><a href="%s">%s</a></li>', $signout_url, __('Sign Out'));
                        }
                    }
                    ?>
                    <?php
                    // Render language flags in the header nav (if multiple languages configured)
                    if (($all_langs = Internationalization::getConfiguredSystemLanguages())
                        && (count($all_langs) > 1)
                    ) {
                        $qs = array();
                        parse_str($_SERVER['QUERY_STRING'], $qs);
                        foreach ($all_langs as $code=>$info) {
                            list($lang, $locale) = explode('_', $code);
                            $qs['lang'] = $code;
                            $flag = strtolower(($locale ?: $info['flag'] ?: $lang));
                            echo sprintf('<li class="lang"><a class="flag flag-%s" href="?%s" title="%s">&nbsp;</a></li>',
                                $flag, http_build_query($qs), Internationalization::getLanguageDescription($code));
                        }
                    }
                    ?>
                    </ul>
            </nav>
            <script type="text/javascript">
            (function(){
                var btn = document.getElementById('nav-toggle');
                var nav = document.getElementById('main-nav');
                if(!btn || !nav) return;
                btn.addEventListener('click', function(){
                    var open = nav.classList.toggle('open');
                    btn.setAttribute('aria-expanded', open? 'true':'false');
                });
            })();
            </script>
            <script type="text/javascript">
            // Highlight form fields that have server-side validation errors.
            (function(){
                function highlightErrors() {
                    var form = document.getElementById('ticketForm');
                    if (!form) return;
                    // Clear previous markers
                    form.querySelectorAll('.field-error').forEach(function(el){ el.classList.remove('field-error'); });
                    // For each .error message, find the nearest input/select/textarea or editor and mark it
                    form.querySelectorAll('.error').forEach(function(err){
                        var cell = err.closest('td') || err.parentElement;
                        if (!cell) return;
                        var field = cell.querySelector('input, select, textarea, .redactor-editor, .richtext');
                        if (field) field.classList.add('field-error');
                    });
                }
                if (document.readyState === 'loading')
                    document.addEventListener('DOMContentLoaded', highlightErrors);
                else
                    highlightErrors();

                // Re-run after AJAX updates (topic change loads dynamic form html)
                if (window.jQuery) {
                    (function($){
                        $(document).ajaxComplete(function(){ highlightErrors(); });
                    })(jQuery);
                }
            })();
            </script>
            <script type="text/javascript">
            // Toggle focus class for Redactor / richtext editors so outlines appear
            (function(){
                function findBox(el){
                    return el && (el.classList && el.classList.contains('redactor-editor') ? el.closest('.redactor-box') : el.closest && el.closest('.redactor-box'));
                }
                document.addEventListener('focusin', function(e){
                    var box = findBox(e.target) || (e.target && e.target.nodeName==='IFRAME' && e.target.closest && e.target.closest('.redactor-box'));
                    if(box) box.classList.add('focused');
                });
                document.addEventListener('focusout', function(e){
                    var box = findBox(e.target) || (e.target && e.target.nodeName==='IFRAME' && e.target.closest && e.target.closest('.redactor-box'));
                    if(box) box.classList.remove('focused');
                });
            })();
            </script>
        </div>
        <div id="content">
         <?php if($errors['err']) { ?>
            <div id="msg_error"><?php echo $errors['err']; ?></div>
         <?php }elseif($msg) { ?>
            <div id="msg_notice"><?php echo $msg; ?></div>
         <?php }elseif($warn) { ?>
            <div id="msg_warning"><?php echo $warn; ?></div>
         <?php } ?>
