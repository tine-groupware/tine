<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Model for CommunityKey
 *
 * @package     Tinebase
 * @subpackage  Model
 */
class Tinebase_Model_MunicipalityKey extends Tinebase_Record_NewAbstract
{
    public const FLD_SATZ_ART = 'satzArt';
    public const FLD_TEXTKENNZEICHEN = 'textkenzeichen';
    public const FLD_ARS_LAND = 'arsLand';
    public const FLD_ARS_RB = 'arsRB';
    public const FLD_ARS_KREIS = 'arsKreis';
    public const FLD_ARS_VB = 'arsVB';
    public const FLD_ARS_GEM = 'arsGem';
    public const FLD_ARS_COMBINED = 'arsCombined';
    public const FLD_GEMEINDENAMEN = 'gemeindenamen';
    public const FLD_FLAECHE = 'flaeche';
    public const FLD_BEVOELKERUNG_GESAMT = 'bevoelkerungGesamt';
    public const FLD_BEVOELKERUNG_MAENNLICH = 'bevoelkerungMaennlich';
    public const FLD_BEVOELKERUNG_WEIBLICH = 'bevoelkerungWeiblich';
    public const FLD_BEVOELKERUNG_JE_KM = 'bevoelkerungJeKm';
    public const FLD_PLZ = 'plz';
    public const FLD_LAENGENGRAD = 'laengengrad';
    public const FLD_BREITENGRAD = 'breitengrad';
    public const FLD_REISEGEBIET = 'reisegebiet';
    public const FLD_GRAD_DER_VERSTAEDTERUNG = 'gradDerVerstaedterung';
    public const FLD_GEBIETSSTAND = 'gebietsstand';
    public const FLD_BEVOELKERUNGSSTAND = 'bevoelkerungsstand';

    public const MODEL_NAME_PART = 'MunicipalityKey';
    public const TABLE_NAME = 'municipalitykeys';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 4,
        self::MODLOG_ACTIVE => true,
        self::IS_DEPENDENT => true,

        self::APP_NAME => Tinebase_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::RECORD_NAME => 'Municipality Key', // _('GENDER_Municipality Key')
        self::RECORDS_NAME => 'Municipality Keys', // ngettext('Municipality Key', 'Municipality Keys', n)
        self::TITLE_PROPERTY => self::FLD_ARS_COMBINED,
        
        self::HAS_RELATIONS => true,
        self::HAS_ATTACHMENTS => true,
        self::HAS_NOTES => false,
        self::HAS_TAGS => true,
        
        self::EXPOSE_HTTP_API => true,
        self::EXPOSE_JSON_API => true,
        self::CREATE_MODULE => false,

        self::HAS_DELETED_TIME_UNIQUE => true,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS => [
                self::FLD_ARS_COMBINED => [
                    self::COLUMNS => [self::FLD_ARS_COMBINED, self::FLD_DELETED_TIME]
                ],
            ],
        ],

        self::FIELDS => [
            self::FLD_SATZ_ART => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 2,
                self::SHY => true,
                self::DISABLED => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::ALLOW_CAMEL_CASE          => true,
            ],
            self::FLD_TEXTKENNZEICHEN => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 2,
                self::DISABLED => true,
                self::SHY => true,
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null]
            ],
            self::FLD_ARS_LAND => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 2,
                self::DISABLED => true,
                self::NULLABLE => true,
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::ALLOW_CAMEL_CASE          => true,
            ],
            self::FLD_ARS_RB => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 1,
                self::DISABLED => true,
                self::NULLABLE => true,
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::ALLOW_CAMEL_CASE          => true,
            ],
            self::FLD_ARS_KREIS => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 2,
                self::DISABLED => true,
                self::NULLABLE => true,
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::ALLOW_CAMEL_CASE          => true,
            ],
            self::FLD_ARS_VB => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 4,
                self::DISABLED => true,
                self::NULLABLE => true,
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::ALLOW_CAMEL_CASE          => true,
            ],
            self::FLD_ARS_GEM => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 3,
                self::DISABLED => true,
                self::NULLABLE => true,
                self::SHY => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::ALLOW_CAMEL_CASE          => true,
            ],
            self::FLD_ARS_COMBINED => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 12,
                self::LABEL => 'Official Regional Key', // _('Official Regional Key')
                self::NULLABLE => false,
                self::QUERY_FILTER => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                ],
                self::ALLOW_CAMEL_CASE          => true,
            ],
            self::FLD_GEMEINDENAMEN => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::LABEL => 'Municipality Name', // _('Municipality Name')
                self::QUERY_FILTER => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_FLAECHE => [
                self::TYPE => self::TYPE_FLOAT,
                self::LABEL => 'Area', // _('Area')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null]
            ],
            self::FLD_BEVOELKERUNG_GESAMT => [
                self::TYPE => self::TYPE_INTEGER,
                self::LABEL => 'Total Population', // _('Total Population')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::ALLOW_CAMEL_CASE          => true,
            ],
            self::FLD_BEVOELKERUNG_MAENNLICH => [
                self::TYPE => self::TYPE_INTEGER,
                self::LABEL => 'Male Population', // _('Male Population')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::ALLOW_CAMEL_CASE          => true,
            ],
            self::FLD_BEVOELKERUNG_WEIBLICH => [
                self::TYPE => self::TYPE_INTEGER,
                self::LABEL => 'Female Population', // _('Female Population')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::ALLOW_CAMEL_CASE          => true,
            ],
            self::FLD_BEVOELKERUNG_JE_KM => [
                self::TYPE => self::TYPE_INTEGER,
                self::LABEL => 'Population per Square Kilometer', // _('Population per Square Kilometer')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                self::ALLOW_CAMEL_CASE          => true,
            ],
            self::FLD_PLZ => [
                self::TYPE => self::TYPE_STRING,
                self::LABEL => 'Postal Code', // _('Postal Code')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null]
            ],
            self::FLD_LAENGENGRAD => [
                self::TYPE => self::TYPE_FLOAT,
                self::LABEL => 'Longitude', // _('Longitude')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null]
            ],
            self::FLD_BREITENGRAD => [
                self::TYPE => self::TYPE_FLOAT,
                self::LABEL => 'Latitude', // _('Latitude')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null]
            ],
            self::FLD_REISEGEBIET => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 10,
                self::LABEL  => 'Travel Region', // _('Travel Region')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
            ],
            self::FLD_GRAD_DER_VERSTAEDTERUNG => [
                self::NULLABLE => true,
                self::TYPE => self::TYPE_KEY_FIELD,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL                     => 'Degree of Urbanization', // _('Degree of Urbanization')
                self::NAME                      => 'gradVerstaedterung',
                self::ALLOW_CAMEL_CASE          => true,
            ],
            self::FLD_GEBIETSSTAND => [
                self::TYPE => self::TYPE_DATE,
                self::LABEL => 'Territorial Status', // _('Territorial Status')
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
            ],
            self::FLD_BEVOELKERUNGSSTAND => [
                self::TYPE => self::TYPE_DATE,
                self::LABEL => 'Population Status', // _('Population Status')
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
