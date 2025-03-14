<?php

class Admin_Model_EmailAccountAdmin extends Felamimail_Model_Account
{
    public const MODEL_NAME_PART = 'EmailAccountAdmin';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
