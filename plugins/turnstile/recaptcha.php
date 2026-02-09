<?php
require_once(INCLUDE_DIR . 'class.plugin.php');
require_once(INCLUDE_DIR . 'class.forms.php');
require_once(INCLUDE_DIR . 'class.i18n.php');
require_once('config.php');

class TurnstileField extends FormField {
    static $cf_site_key; 
    static $cf_secret_key;
    static $widget = 'TurnstileWidget';

    function validateEntry($value) {
        parent::validateEntry($value);

        static $was_validated = false;
        if ($was_validated) return;

        if (!$value && isset($_POST['cf-turnstile-response'])) {
            $value = $_POST['cf-turnstile-response'];
        }

        if (!$value) {
            $this->addError(__('Please complete the security check.'));
            return;
        }

        if (count(parent::errors()) === 0) {
            $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
            
            $post_data = array(
                'secret' => trim(self::$cf_secret_key),
                'response' => $value,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            );

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response);

            if ($http_code !== 200 || !$result) {
                error_log("Turnstile Error: Could not connect to Cloudflare. HTTP $http_code");
                $this->addError('Verification server unreachable.');
            } elseif ($result->success) {
                $was_validated = true; 
            } else {
                $errors = isset($result->{'error-codes'}) ? implode(', ', $result->{'error-codes'}) : 'unknown';
                error_log("Turnstile Validation Failed for help.elke.hua.gr: $errors");
                $this->addError('Security check failed ('.$errors.'). Please try again.');
            }
        }
    }

    function getConfigurationOptions() {
        return array(
            'theme' => new ChoiceField(array(
                'label' => 'Theme',
                'choices' => array('light' => 'Light', 'dark' => 'Dark', 'auto' => 'Auto'),
                'default' => 'auto',
            )),
            'size' => new ChoiceField(array(
                'label' => 'Widget Size',
                'choices' => array('normal' => 'Normal', 'flexible' => 'Flexible', 'compact' => 'Compact'),
                'default' => 'normal',
            )),
            'appearance' => new ChoiceField(array(
                'label' => 'Appearance Mode',
                'choices' => array(
                    'always' => 'Always',
                    'execute' => 'Execute',
                    'interaction-only' => 'Interaction Only'
                ),
                'default' => 'always',
            )),
            'execution' => new ChoiceField(array(
                'label' => 'Execution Mode',
                'choices' => array(
                    'render' => 'Render',
                    'execute' => 'Execute'
                ),
                'default' => 'render',
            )),
            'action' => new TextboxField(array(
                'label' => 'Action (optional)',
                'required' => false,
                'configuration' => array('size'=>30, 'length'=>120, 'autocomplete'=>'off'),
            )),
            'cdata' => new TextboxField(array(
                'label' => 'Customer Data (optional)',
                'required' => false,
                'configuration' => array('size'=>30, 'length'=>120, 'autocomplete'=>'off'),
            )),
        );
    }
}

class TurnstileWidget extends Widget {
    function render() {
        $fconfig = $this->field->getConfiguration();
        ?>
        <div 
            id="<?php echo $this->id; ?>" 
            class="cf-turnstile" 
            data-sitekey="<?php echo TurnstileField::$cf_site_key; ?>" 
            data-theme="<?php echo $fconfig['theme'] ?: 'auto'; ?>" 
            data-size="<?php echo $fconfig['size'] ?: 'normal'; ?>"
            data-appearance="<?php echo $fconfig['appearance'] ?: 'always'; ?>"
            data-execution="<?php echo $fconfig['execution'] ?: 'render'; ?>"
            <?php if (!empty($fconfig['action'])) { ?>data-action="<?php echo $fconfig['action']; ?>"<?php } ?>
            <?php if (!empty($fconfig['cdata'])) { ?>data-cdata="<?php echo $fconfig['cdata']; ?>"<?php } ?>
        >
        </div>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <?php
    }

    function getValue() {
        if ($data = $this->field->getSource()) {
            if (isset($data['cf-turnstile-response'])) return $data['cf-turnstile-response'];
        }
        return $_POST['cf-turnstile-response'] ?? null;
    }
}

class CloudflareTurnstile extends Plugin {
    var $config_class = "CloudflareTurnstileConfig";
    
    function bootstrap() {
        $config = $this->getConfig();
        TurnstileField::$cf_site_key = $config->get('cf-site-key'); 
        TurnstileField::$cf_secret_key = $config->get('cf-secret-key'); 
        
        FormField::addFieldTypes(__('Verification'), function () {
            return array(
                'turnstile' => array('Cloudflare Turnstile', 'TurnstileField')
            );
        });
    }
}
