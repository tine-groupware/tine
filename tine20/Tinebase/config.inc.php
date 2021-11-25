<?php
return [
    // this switches some Tinebase features on for Saas
    'accountDeletionEventConfiguration' => [
        Tinebase_Config::ACCOUNT_DELETION_DELETE_PERSONAL_CONTAINER => true,
        Tinebase_Config::ACCOUNT_DELETION_DELETE_PERSONAL_FOLDERS => true,
        Tinebase_Config::ACCOUNT_DELETION_DELETE_EMAIL_ACCOUNTS => true,
        Tinebase_Config::ACCOUNT_DELETION_ADDITIONAL_TEXT => ''
    ]
];
