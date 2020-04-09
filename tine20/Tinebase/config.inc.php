<?php
return array (
    'features' => array(
        Tinebase_Config::FEATURE_SHOW_ADVANCED_SEARCH => false,
        Tinebase_Config::FEATURE_SHOW_ACCOUNT_EMAIL => false,
        Tinebase_Config::FEATURE_REMEMBER_POPUP_SIZE => true,
        Tinebase_Config::FEATURE_SEARCH_PATH => true,
        Tinebase_Config::FEATURE_AUTODISCOVER => true,
        Tinebase_Config::FEATURE_AUTODISCOVER_MAILCONFIG => true,
        Tinebase_Config::FEATURE_CREATE_PREVIEWS => true,
    ),
    // branding / url config
    Tinebase_Config::BRANDING_WEBURL => 'https://www.tine20.com',
    // activate in 2019.11 (when we have all distro (centos, ...) packages)
    Tinebase_Config::VERSION_CHECK => false,
);
