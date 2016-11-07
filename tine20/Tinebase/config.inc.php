<?php
return array (
    // this switches modules off in business edition
    'features' => array(
        Tinebase_Config::FEATURE_SHOW_ADVANCED_SEARCH  => false,
        // TODO reactivate when this is working consistently
        Tinebase_Config::FEATURE_CONTAINER_CUSTOM_SORT => false,
        Tinebase_Config::FEATURE_SHOW_ACCOUNT_EMAIL    => false,
        // TODO reactivate when minimal size change is finished
        Tinebase_Config::FEATURE_REMEMBER_POPUP_SIZE   => false,
    ),
);
