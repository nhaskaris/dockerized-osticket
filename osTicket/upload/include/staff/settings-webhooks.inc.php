<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin() || !$config) die('Access Denied');
?>

<h2><?php echo __('Webhook Settings'); ?></h2>
<form action="settings.php?t=webhooks" method="post" class="save">
 <?php csrf_token(); ?>
 <input type="hidden" name="t" value="webhooks" />
 <table class="form_table settings_table" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Webhook Configuration'); ?></strong></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="220">
                <?php echo __('Enable Webhooks'); ?>:
            </td>
            <td>
                <input type="checkbox" name="webhook_enabled" value="1"
                    <?php echo $config['webhook_enabled'] ? 'checked="checked"' : ''; ?> />
                <?php echo __('Enable webhook notifications'); ?>
                <i class="help-tip icon-question-sign" href="#webhook_enabled"></i>
                <div class="error"><?php echo $errors['webhook_enabled']; ?></div>
            </td>
        </tr>
        <tr>
            <td width="220" class="required">
                <?php echo __('Webhook URL'); ?>:
            </td>
            <td>
                <input type="text" size="70" name="webhook_url" 
                    value="<?php echo $config['webhook_url']; ?>"
                    placeholder="https://example.com/webhook" />
                <br/>
                <span class="faded"><?php echo __('The URL where webhook events will be sent'); ?></span>
                <div class="error">&nbsp;<?php echo $errors['webhook_url']; ?></div>
            </td>
        </tr>
        <tr>
            <td width="220">
                <?php echo __('Webhook Secret'); ?>:
            </td>
            <td>
                <input type="text" size="40" name="webhook_secret" 
                    value="<?php echo $config['webhook_secret']; ?>"
                    placeholder="Optional secret for webhook verification" />
                <br/>
                <span class="faded"><?php echo __('Optional: Secret key to sign webhook payloads'); ?></span>
                <div class="error"><?php echo $errors['webhook_secret']; ?></div>
            </td>
        </tr>
    </tbody>
    <thead>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Event Triggers'); ?></strong></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="220">
                <?php echo __('Client Reply Event'); ?>:
            </td>
            <td>
                <input type="checkbox" name="webhook_event_client_reply" value="1"
                    <?php echo $config['webhook_event_client_reply'] ? 'checked="checked"' : ''; ?> />
                <?php echo __('Trigger webhook when client replies to ticket'); ?>
            </td>
        </tr>
        <tr>
            <td width="220">
                <?php echo __('New Ticket Event'); ?>:
            </td>
            <td>
                <input type="checkbox" name="webhook_event_new_ticket" value="1"
                    <?php echo $config['webhook_event_new_ticket'] ? 'checked="checked"' : ''; ?> 
                    disabled="disabled" style="opacity: 0.5;" />
                <span style="color: #999;"><?php echo __('Trigger webhook when new ticket is created'); ?> <em>(<?php echo __('Not yet implemented'); ?>)</em></span>
            </td>
        </tr>
        <tr>
            <td width="220">
                <?php echo __('Ticket Closed Event'); ?>:
            </td>
            <td>
                <input type="checkbox" name="webhook_event_ticket_closed" value="1"
                    <?php echo $config['webhook_event_ticket_closed'] ? 'checked="checked"' : ''; ?> 
                    disabled="disabled" style="opacity: 0.5;" />
                <span style="color: #999;"><?php echo __('Trigger webhook when ticket is closed'); ?> <em>(<?php echo __('Not yet implemented'); ?>)</em></span>
            </td>
        </tr>
        <tr>
            <td width="220">
                <?php echo __('Staff Reply Event'); ?>:
            </td>
            <td>
                <input type="checkbox" name="webhook_event_staff_reply" value="1"
                    <?php echo $config['webhook_event_staff_reply'] ? 'checked="checked"' : ''; ?> 
                    disabled="disabled" style="opacity: 0.5;" />
                <span style="color: #999;"><?php echo __('Trigger webhook when staff replies to ticket'); ?> <em>(<?php echo __('Not yet implemented'); ?>)</em></span>
            </td>
        </tr>
    </tbody>
    <thead>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Webhook Headers'); ?></strong></em>
            </th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td width="220">
                <?php echo __('Custom Headers'); ?>:
            </td>
            <td>
                <textarea name="webhook_headers" rows="5" cols="70" 
                    placeholder="Authorization: Bearer your-token&#10;X-Custom-Header: value"><?php 
                    echo $config['webhook_headers']; ?></textarea>
                <br/>
                <span class="faded"><?php echo __('One header per line in format: Header-Name: value'); ?></span>
                <div class="error"><?php echo $errors['webhook_headers']; ?></div>
            </td>
        </tr>
        <tr>
            <td width="220">
                <?php echo __('Timeout (seconds)'); ?>:
            </td>
            <td>
                <input type="number" name="webhook_timeout" min="1" max="60"
                    value="<?php echo $config['webhook_timeout'] ?: 10; ?>" />
                <span class="faded"><?php echo __('Maximum time to wait for webhook response (1-60 seconds)'); ?></span>
                <div class="error"><?php echo $errors['webhook_timeout']; ?></div>
            </td>
        </tr>
    </tbody>
 </table>
<div style="clear: both; height: 60px;"></div>
<p style="text-align:center; position: relative; z-index: 1000; margin-top: 20px;">
    <input type="submit" name="submit" value="<?php echo __('Save Changes'); ?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset'); ?>">
</p>
</form>
