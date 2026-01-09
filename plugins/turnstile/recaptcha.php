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

        if (count(parent::errors()) === 0) {
            // Cloudflare Turnstile Verification API
            $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
            $data = array(
                'secret' => self::$cf_secret_key,
                'response' => $value,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            );

            $options = array(
                'http' => array(
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data)
                )
            );
            
            $context  = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            $response = json_decode($result);

            if ($response == FALSE) {
                $this->addError('Unable to communicate with Cloudflare servers');
            } elseif (!$response->success) {
                $this->addError('Security check failed. Please try again.');
            }
        }
    }

    function getConfigurationOptions() {
        return array(
            'theme' => new ChoiceField(array(
                'label' => 'Appearance',
                'choices' => array('light' => 'Light', 'dark' => 'Dark', 'auto' => 'Auto'),
                'default' => 'auto',
            )),
            'size' => new ChoiceField(array(
                'label' => 'Widget Size',
                'choices' => array('normal' => 'Normal', 'compact' => 'Compact'),
                'default' => 'normal',
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
            data-size="<?php echo $fconfig['size'] ?: 'normal'; ?>">
        </div>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <?php
    }

    function getValue() {
        if (!($data = $this->field->getSource()))
            return null;
        // Turnstile uses the same POST key name as reCAPTCHA by default for compatibility
        if (!isset($data['cf-turnstile-response']) && !isset($data['g-recaptcha-response']))
            return null;
        
        return $data['cf-turnstile-response'] ?: $data['g-recaptcha-response'];
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