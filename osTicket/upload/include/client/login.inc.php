<?php
if(!defined('OSTCLIENTINC')) die('Access Denied');

$email=Format::input($_POST['luser']?:$_GET['e']);
$passwd=Format::input($_POST['lpasswd']?:$_GET['t']);

$content = Page::lookupByType('banner-client');

if ($content) {
    list($title, $body) = $ost->replaceTemplateVariables(
        array($content->getLocalName(), $content->getLocalBody()));
} else {
    $title = __('Sign In');
    $body = __('To better serve you, we encourage our clients to register for an account.');
}
?>

<div id="login-page-wrapper">
    <div class="form-header-main">
        <h1><?php echo Format::display($title); ?></h1>
        <p><?php echo Format::display($body); ?></p>
    </div>

    <div class="auth-card">
        <form action="login.php" method="post" id="clientLogin">
            <?php csrf_token(); ?>
            
            <?php if ($errors['login']) { ?>
                <div class="error-banner"><?php echo Format::htmlchars($errors['login']); ?></div>
            <?php } ?>

            <div class="input-group">
                <label for="username"><?php echo __('Email or Username'); ?></label>
                <input id="username" placeholder="name@example.com" type="text" name="luser" value="<?php echo $email; ?>" class="nowarn">
            </div>

            <div class="input-group">
                <label for="passwd"><?php echo __('Password'); ?></label>
                <input id="passwd" placeholder="<?php echo __('Enter password'); ?>" type="password" name="lpasswd" maxlength="128" value="<?php echo $passwd; ?>" class="nowarn">
            </div>

            <button type="submit" class="btn-primary"><?php echo __('Sign In'); ?></button>
        </form>
    </div>

    <div class="auth-actions">
        <?php if ($suggest_pwreset) { ?>
            <a class="link-muted" href="pwreset.php"><?php echo __('Forgot Password?'); ?></a>
        <?php } ?>
        
        <?php if ($cfg && $cfg->isClientRegistrationEnabled()) { ?>
            <a class="link-muted" href="account.php?do=create"><?php echo __("Don't have an account? Sign up"); ?></a>
        <?php } ?>
    </div>

    <div class="agent-link">
        <a href="<?php echo ROOT_PATH; ?>scp/"><?php echo __('Are you an Agent?'); ?></a>
    </div>
</div>