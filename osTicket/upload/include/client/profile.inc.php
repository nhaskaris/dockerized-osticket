<div class="profile-page">
    <div class="profile-header">
        <h1><?php echo __('Manage Your Profile Information'); ?></h1>
        <p><?php echo __('Use the forms below to update the information we have on file for your account'); ?></p>
    </div>

    <form action="profile.php" method="post" class="profile-form">
        <?php csrf_token(); ?>

        <div class="profile-card form-card">
            <div class="profile-card-head">
                <div>
                    <div class="profile-card-title"><?php echo __('Account Details'); ?></div>
                    <div class="profile-card-hint"><?php echo __('Update your contact information.'); ?></div>
                </div>
            </div>
            <div class="profile-card-body form-section">
                <?php
                foreach ($user->getForms() as $f) {
                    $f->render(['staff' => false]);
                }
                ?>
            </div>
        </div>

<?php
if ($acct = $thisclient->getAccount()) {
    $info=$acct->getInfo();
    $info=Format::htmlchars(($errors && $_POST)?$_POST:$info);
?>
        <div class="profile-card form-card">
            <div class="profile-card-head">
                <div class="profile-card-title"><?php echo __('Preferences'); ?></div>
                <div class="profile-card-hint"><?php echo __('Time zone and language for notifications and display.'); ?></div>
            </div>
            <div class="profile-card-body form-section">
                <div class="input-group timezone-group">
                    <label for="timezone-dropdown"><?php echo __('Time Zone'); ?></label>
                    <?php
                    $TZ_NAME = 'timezone';
                    $TZ_TIMEZONE = $info['timezone'];
                    include INCLUDE_DIR.'staff/templates/timezone.tmpl.php';
                    ?>
                    <div class="error-text"><?php echo $errors['timezone']; ?></div>
                </div>

<?php if ($cfg->getSecondaryLanguages()) { ?>
                <div class="input-group">
                    <label for="lang-select"><?php echo __('Preferred Language'); ?></label>
            <?php
            $langs = Internationalization::getConfiguredSystemLanguages(); ?>
                    <select id="lang-select" name="lang">
                        <option value="">&mdash; <?php echo __('Use Browser Preference'); ?> &mdash;</option>
            <?php foreach($langs as $l) {
            $selected = ($info['lang'] == $l['code']) ? 'selected="selected"' : ''; ?>
                        <option value="<?php echo $l['code']; ?>" <?php echo $selected; ?>><?php echo Internationalization::getLanguageDescription($l['code']); ?></option>
            <?php } ?>
                    </select>
                    <div class="error-text"><?php echo $errors['lang']; ?></div>
                </div>
<?php } ?>
            </div>
        </div>

<?php if ($acct->isPasswdResetEnabled()) { ?>
        <div class="profile-card form-card">
            <div class="profile-card-head">
                <div class="profile-card-title"><?php echo __('Access Credentials'); ?></div>
                <div class="profile-card-hint"><?php echo __('Change your password to keep your account secure.'); ?></div>
            </div>
            <div class="profile-card-body form-section">
<?php if (!isset($_SESSION['_client']['reset-token'])) { ?>
                <div class="input-group">
                    <label for="cpasswd"><?php echo __('Current Password'); ?></label>
                    <input id="cpasswd" type="password" name="cpasswd" maxlength="128" value="<?php echo $info['cpasswd']; ?>">
                    <div class="error-text"><?php echo $errors['cpasswd']; ?></div>
                </div>
<?php } ?>
                <div class="input-group">
                    <label for="passwd1"><?php echo __('New Password'); ?></label>
                    <input id="passwd1" type="password" name="passwd1" maxlength="128" value="<?php echo $info['passwd1']; ?>">
                    <div class="error-text"><?php echo $errors['passwd1']; ?></div>
                </div>
                <div class="input-group">
                    <label for="passwd2"><?php echo __('Confirm New Password'); ?></label>
                    <input id="passwd2" type="password" name="passwd2" maxlength="128" value="<?php echo $info['passwd2']; ?>">
                    <div class="error-text"><?php echo $errors['passwd2']; ?></div>
                </div>
            </div>
        </div>
<?php } ?>
<?php } ?>

        <div class="profile-actions">
            <button type="submit" class="btn-primary"><?php echo __('Update'); ?></button>
            <button type="reset" class="btn-ghost"><?php echo __('Reset'); ?></button>
            <button type="button" class="btn-ghost" onclick="window.location.href='index.php';"><?php echo __('Cancel'); ?></button>
        </div>
    </form>
</div>
