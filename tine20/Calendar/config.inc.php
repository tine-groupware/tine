<?php
return [
    // configure modules/features in business edition
    'features' => array(
        Calendar_Config::FEATURE_COLOR_BY                        => true,
        Calendar_Config::FEATURE_EVENT_NOTIFICATION_CONFIRMATION => false,
        Calendar_Config::FEATURE_EXTENDED_EVENT_CONTEXT_ACTIONS  => true,
        Calendar_Config::FEATURE_POLLS                           => true,
        Calendar_Config::FEATURE_RECUR_EXCEPT                    => false,
        Calendar_Config::FEATURE_SPLIT_VIEW                      => true,
        Calendar_Config::FEATURE_YEAR_VIEW                       => false,
    )
];
