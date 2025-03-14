<?php

class Admin_Model_EmailAccount extends Felamimail_Model_Account
{
    public const MODEL_NAME_PART = 'EmailAccount';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        $_definition[self::APP_NAME] = Admin_Config::APP_NAME;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;

        parent::inheritModelConfigHook($_definition);
    }
}
