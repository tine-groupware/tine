<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Twig Template Model
 *
 * @package     Tinebase
 * @subpackage  Model
 */

class Tinebase_Model_TwigTemplate extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'TwigTemplate';
    public const TABLE_NAME = 'twig_tmpl';

    public const FLD_PATH = 'path';
    public const FLD_NAME = 'name';
    public const FLD_APPLICATION_ID = 'application_id';
    public const FLD_DESCRIPTION = 'description';
    public const FLD_LOCALE = 'locale';
    public const FLD_TWIG_TEMPLATE = 'twig_template';

    public const FLD_IS_ORIGINAL = 'is_original';
    public const FLD_HAS_ORIGINAL = 'has_original';
    public const FLD_DIFF_TO_ORIGINAL = 'diff_to_original';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 1,
        self::APP_NAME                  => Tinebase_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        self::MODLOG_ACTIVE             => true,
        self::HAS_DELETED_TIME_UNIQUE   => true,
        self::EXPOSE_JSON_API           => true,

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::UNIQUE_CONSTRAINTS        => [
                self::FLD_PATH                  => [
                    self::COLUMNS                   => [self::FLD_PATH, self::FLD_DELETED_TIME],
                ],
            ],
        ],

        self::ASSOCIATIONS              => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                self::FLD_APPLICATION_ID        => [
                    self::TARGET_ENTITY             => Tinebase_Model_Application::class,
                    self::FIELD_NAME                => self::FLD_APPLICATION_ID,
                    self::JOIN_COLUMNS              => [[
                        self::NAME                      => self::FLD_APPLICATION_ID,
                        self::REFERENCED_COLUMN_NAME    => self::ID,
                    ]],
                ],
            ],
        ],

        self::FIELDS                    => [
            self::FLD_PATH                  => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_NAME                  => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
            ],
            self::FLD_APPLICATION_ID        => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 40,
            ],
            self::FLD_DESCRIPTION           => [
                self::TYPE                      => self::TYPE_TEXT,
                self::NULLABLE                  => true,
            ],
            self::FLD_TWIG_TEMPLATE         => [
                self::TYPE                      => self::TYPE_TEXT,
            ],
            self::FLD_LOCALE                 => [
                self::TYPE                      => self::TYPE_STRING,
                self::DOCTRINE_IGNORE           => true,
            ],
            self::FLD_IS_ORIGINAL           => [
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::DOCTRINE_IGNORE           => true,
            ],
            self::FLD_HAS_ORIGINAL          => [
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::DOCTRINE_IGNORE           => true,
            ],
            self::FLD_DIFF_TO_ORIGINAL      => [
                self::TYPE                      => self::TYPE_TEXT,
                self::DOCTRINE_IGNORE           => true,
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    public function setFromArray(array &$_data)
    {
        if (is_string($_data[self::FLD_PATH] ?? null)) {
            $_data[self::FLD_PATH] = trim(trim($_data[self::FLD_PATH]), '/');
            $pathParts = explode('/', $_data[self::FLD_PATH]);
            $count = count($pathParts);
            $_data[self::FLD_NAME] = $pathParts[$count - 1];
            if ($count > 3) {
                $locale = $pathParts[$count - 2];
                if (Zend_Locale::isLocale($locale)) {
                    $_data[self::FLD_LOCALE] = $locale;
                }
            }
        }

        if (($_data[self::FLD_IS_ORIGINAL] ?? false) && ($_data[self::FLD_TWIG_TEMPLATE] ?? false)) {
            if (preg_match('/\{#([^#]+)#\}/', $_data[self::FLD_TWIG_TEMPLATE], $m)) {
                $_data[self::FLD_DESCRIPTION] = $m[1];
            }
        }

        parent::setFromArray($_data);
    }
}
