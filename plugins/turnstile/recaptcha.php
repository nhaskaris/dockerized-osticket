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
            'mode' => new ChoiceField(array(
                'label' => 'Turnstile Mode Preset',
                'hint' => 'Select the same mode configured for your widget in Cloudflare.',
                'choices' => array(
                    'managed' => 'Managed (recommended)',
                    'non-interactive' => 'Non-Interactive',
                    'invisible' => 'Invisible'
                ),
                'default' => 'managed',
            )),
        );
    }
}

class TurnstileWidget extends Widget {
    static $script_loaded = false;

    function render() {
        $fconfig = $this->field->getConfiguration();

        $presets = array(
            'managed' => array(
                'theme' => 'auto',
                'size' => 'normal',
                'appearance' => 'always',
                'execution' => 'render',
            ),
            'non-interactive' => array(
                'theme' => 'auto',
                'size' => 'normal',
                'appearance' => 'always',
                'execution' => 'render',
            ),
            'invisible' => array(
                'theme' => 'auto',
                'size' => 'normal',
                'appearance' => 'execute',
                'execution' => 'render',
            ),
        );

        $mode = $fconfig['mode'] ?? 'managed';
        if (!isset($presets[$mode])) {
            $mode = 'managed';
        }

        $theme = $fconfig['theme'] ?? $presets[$mode]['theme'];
        $size = $fconfig['size'] ?? $presets[$mode]['size'];
        $appearance = $fconfig['appearance'] ?? $presets[$mode]['appearance'];
        $execution = $fconfig['execution'] ?? $presets[$mode]['execution'];
        $widget_id = $this->id;
        ?>
        <div 
            id="<?php echo $widget_id; ?>" 
            class="cf-turnstile" 
            data-sitekey="<?php echo TurnstileField::$cf_site_key; ?>" 
            data-theme="<?php echo $theme ?: 'auto'; ?>" 
            data-size="<?php echo $size ?: 'normal'; ?>"
            data-appearance="<?php echo $appearance ?: 'always'; ?>"
            data-execution="<?php echo $execution ?: 'render'; ?>"
            <?php if (!empty($fconfig['action'])) { ?>data-action="<?php echo $fconfig['action']; ?>"<?php } ?>
            <?php if (!empty($fconfig['cdata'])) { ?>data-cdata="<?php echo $fconfig['cdata']; ?>"<?php } ?>
        >
        </div>
        <script>
        // Render Turnstile widget - works on initial load and after form errors
        (function() {
            function renderTurnstile() {
                var element = document.getElementById('<?php echo $widget_id; ?>');
                if (element && typeof window.turnstile !== 'undefined') {
                    // Clear any existing widget and render fresh
                    window.turnstile.remove('#<?php echo $widget_id; ?>');
                    window.turnstile.render('#<?php echo $widget_id; ?>', {
                        sitekey: '<?php echo TurnstileField::$cf_site_key; ?>',
                        theme: '<?php echo $theme ?: 'auto'; ?>',
                        size: '<?php echo $size ?: 'normal'; ?>',
                        appearance: '<?php echo $appearance ?: 'always'; ?>',
                        execution: '<?php echo $execution ?: 'render'; ?>'
                        <?php if (!empty($fconfig['action'])) { ?>,action: '<?php echo $fconfig['action']; ?>'<?php } ?>
                        <?php if (!empty($fconfig['cdata'])) { ?>,cData: '<?php echo $fconfig['cdata']; ?>'<?php } ?>
                    });
                }
            }
            
            if (typeof window.turnstile !== 'undefined') {
                renderTurnstile();
            } else {
                // Wait for Turnstile to load if not available yet
                var maxAttempts = 50;
                var attempts = 0;
                var interval = setInterval(function() {
                    attempts++;
                    if (typeof window.turnstile !== 'undefined') {
                        renderTurnstile();
                        clearInterval(interval);
                    } else if (attempts >= maxAttempts) {
                        clearInterval(interval);
                    }
                }, 100);
            }
        })();
        </script>
        <?php
        // Only load the script once per page
        if (!self::$script_loaded) {
            self::$script_loaded = true;
            ?>
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            <?php
        }
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
