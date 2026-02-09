<?php 
require_once INCLUDE_DIR . 'class.plugin.php';

class CloudflareTurnstileConfig extends PluginConfig {
    function getOptions() {       
        return array(
            'cloudflare' => new SectionBreakField(array(
                'label' => 'Cloudflare Turnstile Settings',
            )),
            'cf-site-key' => new TextboxField(array(
                'label' => 'Site Key',
                'required'=> true,
                'configuration' => array('size'=>60, 'length'=>100, 'autocomplete'=>'off'),
            )),
            'cf-secret-key' => new TextboxField(array(
                'widget'=>'PasswordWidget',
                'required'=>true,
                'label' => 'Secret Key',
                'configuration' => array('size'=>60, 'length'=>100),
            ))
        );
    }
    function pre_save(&$config, &$errors) {
        if (!function_exists('curl_init')) {
            Messages::error('CURL extension is required');
            return false;
        }
        return true;
    }
}