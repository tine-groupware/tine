<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2024 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * DispatchHistory Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_DispatchHistory extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Document_DispatchHistory';
    public const TABLE_NAME = 'document_dispatch_history';


    public const FLD_ATTACHED_DOCUMENT_ID = 'attached_document_id';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                       => 1,
        self::MODLOG_ACTIVE                 => true,
        self::IS_DEPENDENT                  => true,

        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::TABLE                         => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_ATTACHED_DOCUMENT_ID  => [
                    self::COLUMNS                   => [self::FLD_ATTACHED_DOCUMENT_ID],
                ],
            ],
        ],

        self::FIELDS                        => [
            self::FLD_ATTACHED_DOCUMENT_ID      => [
                self::TYPE                          => self::TYPE_RECORD,
                self::NULLABLE                      => true,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_Document_AttachedDocument::MODEL_NAME_PART,
                    self::IS_PARENT                     => true,
                ],
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
