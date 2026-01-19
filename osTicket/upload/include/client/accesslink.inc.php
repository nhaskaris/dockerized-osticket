<?php
if(!defined('OSTCLIENTINC')) die('Access Denied');

$email=Format::input($_POST['lemail']?$_POST['lemail']:$_GET['e']);
$ticketid=Format::input($_POST['lticket']?$_POST['lticket']:$_GET['t']);

if ($cfg->isClientEmailVerificationRequired())
    $button = __("Email Access Link");
else
    $button = __("View Ticket");
?>
<div id="access-page-wrapper">
    <div class="form-header-main">
        <h1><?php echo __('Check Ticket Status'); ?></h1>
        <p><?php
        echo __('Please provide your email address and a ticket number.');
        if ($cfg->isClientEmailVerificationRequired())
            echo ' '.__('An access link will be emailed to you.');
        else
            echo ' '.__('This will sign you in to view your ticket.');?></p>
    </div>

    <div class="access-card">
        <form action="login.php" method="post" id="clientLogin">
            <?php csrf_token(); ?>
            <div class="login-error"><strong><?php echo Format::htmlchars($errors['login']); ?></strong></div>
            <div class="input-group">
                <label for="email"><?php echo __('Email Address'); ?></label>
                <input id="email" placeholder="<?php echo __('e.g. john.doe@osticket.com'); ?>" type="email"
                    name="lemail" value="<?php echo $email; ?>" class="nowarn">
            </div>
            <div class="input-group">
                <label for="ticketno"><?php echo __('Ticket Number'); ?></label>
                <input id="ticketno" type="text" name="lticket" placeholder="<?php echo __('e.g. 051243'); ?>"
                    value="<?php echo $ticketid; ?>" class="nowarn">
            </div>
            <button type="submit" class="btn-primary"><?php echo $button; ?></button>
        </form>
    </div>
</div>
