<?php
if(!defined('OSTCLIENTINC')) die('Access Denied');

$email=Format::input($_POST['lemail']?$_POST['lemail']:$_GET['e']);
$ticketid=Format::input($_POST['lticket']?$_POST['lticket']:$_GET['t']);

if ($cfg->isClientEmailVerificationRequired())
    $button = __("Email Access Link");
else
    $button = __("View Ticket");
?>
<h1><?php echo __('Check Ticket Status'); ?></h1>
<p><?php
echo __('Please provide your email address and a ticket number.');
if ($cfg->isClientEmailVerificationRequired())
    echo ' '.__('An access link will be emailed to you.');
else
    echo ' '.__('This will sign you in to view your ticket.');?></p>

<div class="auth-container">
    <form action="login.php" method="post" id="clientLogin" class="auth-card">
        <?php csrf_token(); ?>
        <h2 class="auth-title"><?php echo __('Access Your Ticket'); ?></h2>
        <div class="login-error"><strong><?php echo Format::htmlchars($errors['login']); ?></strong></div>
        <div class="field">
            <label for="email"><?php echo __('Email Address'); ?></label>
            <input id="email" placeholder="<?php echo __('e.g. john.doe@osticket.com'); ?>" type="email"
                name="lemail" value="<?php echo $email; ?>" class="nowarn">
        </div>
        <div class="field">
            <label for="ticketno"><?php echo __('Ticket Number'); ?></label>
            <input id="ticketno" type="text" name="lticket" placeholder="<?php echo __('e.g. 051243'); ?>"
                value="<?php echo $ticketid; ?>" class="nowarn">
        </div>
        <div class="actions auth-actions">
            <button type="submit" class="button primary"><?php echo $button; ?></button>
            <a href="open.php" class="button outline"><?php echo __('Open a New Ticket'); ?></a>
        </div>
        <div class="auth-help">
            <?php if ($cfg && $cfg->getClientRegistrationMode() !== 'disabled') { ?>
                <div><?php echo __('Have an account with us?'); ?> <a href="login.php"><?php echo __('Sign In'); ?></a></div>
                <?php if ($cfg->isClientRegistrationEnabled()) { ?>
                    <div><?php echo sprintf(__('Or %s create an account %s to manage all your tickets.'), '<a href="account.php?do=create">', '</a>'); ?></div>
                <?php } ?>
            <?php } ?>
        </div>
    </form>
</div>
<br>
<p>
<?php
if ($cfg->getClientRegistrationMode() != 'disabled'
    || !$cfg->isClientLoginRequired()) {
    echo sprintf(
    __("If this is your first time contacting us or you've lost the ticket number, please %s open a new ticket %s"),
        '<a href="open.php">','</a>');
} ?>
</p>
