<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * EvaluationDimension Model
 *
 * @package     Tinebase
 * @subpackage  Model
 */

class Tinebase_Model_EvaluationDimension extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'EvaluationDimension';
    public const TABLE_NAME = 'evaluation_dimension';

    public const FLD_DEPENDS_ON = 'depends_on';
    public const FLD_ITEMS = 'items';
    public const FLD_MODELS = 'models';
    public const FLD_NAME = self::NAME;
    public const FLD_DESCRIPTION = 'description';
    public const FLD_SORTING = 'sorting';

    public const COST_CENTER = 'Cost Center'; // gettext('Cost Center')
    public const COST_BEARER = 'Cost Bearer'; // gettext('Cost Bearer')

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 2,
        self::APP_NAME                  => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::MODLOG_ACTIVE             => true,
        self::HAS_DELETED_TIME_UNIQUE   => true,
        self::HAS_SYSTEM_CUSTOM_FIELDS  => true,
        self::EXPOSE_JSON_API           => true,
        self::RECORD_NAME               => 'Evaluation Dimension', // gettext('GENDER_Evaluation Dimension')
        self::RECORDS_NAME              => 'Evaluation Dimensions', // ngettext('Evaluation Dimension', 'Evaluation Dimensions', n)
        self::TITLE_PROPERTY            => self::FLD_NAME,

        self::JSON_EXPANDER             => [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                self::FLD_ITEMS     => [],
            ],
        ],

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS        => [
                self::NAME            => [
                    self::COLUMNS                   => [self::FLD_NAME, self::FLD_DELETED_TIME],
                ],
            ],
            self::INDEXES                   => [
                self::FLD_DESCRIPTION           => [
                    self::COLUMNS                   => [self::FLD_DESCRIPTION],
                    self::FLAGS                     => [self::TYPE_FULLTEXT],
                ]
            ],
        ],

        self::FIELDS                    => [
            self::FLD_NAME                  => [
                self::LABEL                     => 'Name', // _('Name')
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::QUERY_FILTER              => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                    Zend_Validate_Regex::class      => '/^[a-zA-Z\-_ 0-9]+$/',
                ],
            ],
            self::FLD_DESCRIPTION           => [
                self::LABEL                     => 'Description', // _('Description')
                self::TYPE                      => self::TYPE_FULLTEXT,
                self::NULLABLE                  => true,
                self::QUERY_FILTER              => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => TRUE],
            ],
            self::FLD_ITEMS                 => [
                self::TYPE                      => self::TYPE_RECORDS,
                self::LABEL                     => 'Dimension Items', // _('Dimension Items')
                self::CONFIG                    => [
                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME                => Tinebase_Model_EvaluationDimensionItem::MODEL_NAME_PART,
                    self::REF_ID_FIELD              => Tinebase_Model_EvaluationDimensionItem::FLD_EVALUATION_DIMENSION_ID,
                    self::DEPENDENT_RECORDS         => true,
                ],
            ],
            self::FLD_MODELS                => [
                self::LABEL                     => 'Configured Models', // _('Configured Models')
                self::TYPE                      => self::TYPE_JSON,
                self::NULLABLE                  => true,
                self::UI_CONFIG                 => [
                    'xtype'                         => 'tw-modelspickers',
                ]
            ],
//            self::FLD_DEPENDS_ON            => [
//                self::TYPE                      => self::TYPE_RECORD,
//                self::LABEL                     => 'Depends on', // _('Depends on')
//                self::NULLABLE                  => true,
//                self::CONFIG                    => [
//                    self::APP_NAME                  => Tinebase_Config::APP_NAME,
//                    self::MODEL_NAME                => Tinebase_Model_EvaluationDimension::MODEL_NAME_PART,
//                ],
//            ],
            self::FLD_SORTING               => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::LABEL                     => 'Sorting', // _('Sorting')
                self::DEFAULT_VAL               => 0,
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public function getSystemCF(string $model): Tinebase_Model_CustomField_Config
    {
        [$appId] = explode('_', $model, 2);
        $appId = Tinebase_Application::getInstance()->getApplicationByName($appId)->getId();

        $fldName = 'eval_dim_' . str_replace(' ', '_', strtolower((string) $this->{self::FLD_NAME}));

        $definition = [
            Tinebase_Model_CustomField_Config::DEF_FIELD => [
                self::LABEL             => $this->{self::FLD_NAME},
                self::OWNING_APP        => Tinebase_Config::APP_NAME,
                self::TYPE              => self::TYPE_RECORD,
                self::CONFIG            => [
                    self::APP_NAME          => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME        => Tinebase_Model_EvaluationDimensionItem::MODEL_NAME_PART,
                    self::FLD_DEPENDS_ON    => $this->{self::FLD_DEPENDS_ON},
                ],
                self::SHY              => true,
                self::UI_CONFIG         => [
                    'sorting'              => $this->{self::FLD_SORTING},
                    'grouping'             => self::RECORDS_NAME,
                    'additionalFilters'    => [[
                        'field'     => Tinebase_Model_EvaluationDimensionItem::FLD_EVALUATION_DIMENSION_ID,
                        'operator'  => 'equals',
                        'value'     => $this->id,
                    ]]
                ],
                self::NULLABLE          => true,
            ],
            Tinebase_Model_CustomField_Config::DEF_HOOK => [
                [Tinebase_Controller_EvaluationDimension::class, 'modelConfigHook'],
            ],
        ];
        if (in_array(Tinebase_Model_EvaluationDimensionCFHook::class, class_implements($model))) {
            /** @var Tinebase_Model_EvaluationDimensionCFHook $model */
            $model::evalDimCFHook($fldName, $definition);
        }

        return new Tinebase_Model_CustomField_Config([
            'name' => $fldName,
            'application_id' => $appId,
            'model' => $model,
            'is_system' => true,
            'definition' => $definition,
        ], true);
    }

    public function isReplicable()
    {
        return true;
    }
}
