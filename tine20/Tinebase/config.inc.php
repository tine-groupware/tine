<?php
return [
    Tinebase_Config::VERSION_CHECK => true,
    'features' => array(
        Tinebase_Config::FEATURE_SHOW_ADVANCED_SEARCH => false,
        Tinebase_Config::FEATURE_REMEMBER_POPUP_SIZE => true,
        Tinebase_Config::FEATURE_SEARCH_PATH => true,
        Tinebase_Config::FEATURE_AUTODISCOVER => true,
        Tinebase_Config::FEATURE_AUTODISCOVER_MAILCONFIG => true,
        Tinebase_Config::FEATURE_CREATE_PREVIEWS => true,
        Tinebase_Config::FEATURE_COMMUNITY_IDENT_NR => false,
    ),
    Tinebase_Config::BRANDING_WEBURL => 'https://www.tine-groupware.de/',
    Tinebase_Config::USE_NOMINATIM_SERVICE => false,
    Tinebase_Config::USE_MAP_SERVICE => false,
];
