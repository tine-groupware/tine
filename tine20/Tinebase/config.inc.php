<?php
return array (
    // this switches modules off in business edition
    'features' => array(
        Tinebase_Config::FEATURE_SHOW_ADVANCED_SEARCH  => false,
        // TODO reactivate when this is working consistently
        Tinebase_Config::FEATURE_CONTAINER_CUSTOM_SORT => false,
        Tinebase_Config::FEATURE_SHOW_ACCOUNT_EMAIL    => false,
        Tinebase_Config::FEATURE_REMEMBER_POPUP_SIZE   => true,
        Tinebase_Config::FEATURE_SEARCH_PATH           => true,
    ),
    // branding / url config
    Tinebase_Config::BRANDING_WEBURL => 'https://www.tine20.com',
    // deactivate version check
    // TODO activate when 2018.11.1 is released!
    Tinebase_Config::VERSION_CHECK => false,
);
