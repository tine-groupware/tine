<?php declare(strict_types=1);
/**
 * class to hold EDocument dispatch data
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025-2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Sales_Model_EDocument_Dispatch_Custom extends Tinebase_Record_NewAbstract implements Sales_Model_EDocument_Dispatch_Interface
{
    public const MODEL_NAME_PART = 'EDocument_Dispatch_Custom';

    public const FLD_DISPATCH_CONFIGS = 'dispatch_configs';

    protected static $_modelConfiguration = [
        self::APP_NAME                  => Sales_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::RECORD_NAME               => 'Custom Config', // gettext('GENDER_Custom Config')
        self::RECORDS_NAME              => 'Custom Configs', // ngettext('Custom Config', 'Custom Configs', n)
//        self::TITLE_PROPERTY            => '{% for config in dispatch_configs %}{{ renderModel(config.dispatch_type) }}{% endfor %}',
        self::TITLE_PROPERTY            => '{{ dispatch_configs|map(c => renderModel(c.dispatch_type))|join(", ") }}',

        self::FIELDS                    => [
            self::FLD_DISPATCH_CONFIGS        => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::LABEL                     => 'Custom Configs', // _('Custom Configs')
                self::CONFIG                    => [
                    self::APP_NAME                  => Sales_Config::APP_NAME,
                    self::MODEL_NAME                => Sales_Model_EDocument_Dispatch_DynamicConfig::MODEL_NAME_PART,
                    self::STORAGE                   => self::TYPE_JSON,
                ],
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
                self::UI_CONFIG                 => [
                    'allowDuplicatePicks'           => true,
                    'columns'                       => [
                        Sales_Model_EDocument_Dispatch_DynamicConfig::FLD_DISPATCH_TYPE,
                        Sales_Model_EDocument_Dispatch_DynamicConfig::FLD_DISPATCH_CONFIG,
                    ]
                ]
            ],
        ],
    ];
    protected static $_configurationObject = null;

    public function dispatch(Sales_Model_Document_Abstract $document): void
    {
        /** @var Sales_Model_EDocument_Dispatch_DynamicConfig $dispatchConfig */
        foreach ($this->{self::FLD_DISPATCH_CONFIGS} as $dispatchConfig) {
            $dispatchConfig->{Sales_Model_EDocument_Dispatch_DynamicConfig::FLD_DISPATCH_CONFIG}->dispatch($document);
        }
    }
}