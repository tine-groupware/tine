<?php declare(strict_types=1);

/**
 * holds information about the requested data
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2023-2024 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @property Tinebase_Record_RecordSet $ids;
 */
class Tinebase_Record_Expander_DataRequest_AccountGrants extends Tinebase_Record_Expander_DataRequest
{
    protected Tinebase_ModelConfiguration $parentMC;

    public function __construct($prio, $controller, $ids, $mc, $callback, $getDeleted = false)
    {
        $this->parentMC = $mc;
        parent::__construct($prio, $controller, $ids, $callback, $getDeleted);
    }


    public function merge(Tinebase_Record_Expander_DataRequest $_dataRequest)
    {
        $this->ids->mergeById($_dataRequest->ids);
        $this->_merged = true;
    }

    public function getKey(): string
    {
        return 'AccountGrants#' . parent::getKey();
    }

    public function getData()
    {
        $containerCache = [];
        $funcCache = [];
        $func = function (Tinebase_Record_Interface $record, callable $func) use(&$funcCache) {
            $mc = $record::getConfiguration();
            if ($mc->delegateAclField) {
                if (empty($delegateRecord = $record->{$mc->delegateAclField})) {
                    throw new Tinebase_Exception_Record_Validation($mc->delegateAclField . ' must not be empty');
                }
                if ($delegateRecord instanceof Tinebase_Record_RecordSet) {
                    $grants = null;
                    foreach ($delegateRecord as $delegate) {
                        $newGrants = $func($delegate, $func);
                        if (null === $grants) {
                            $grants = $newGrants;
                        } else {
                            foreach ($newGrants::getAllGrants() as $grant) {
                                // decide and / or ... ?! for now we or them
                                $grants->{$grant} = $grants->{$grant} || $newGrants->{$grant};
                            }
                        }
                    }
                    if ($grants) {
                        $record->setAccountGrants($grants);
                    }
                    return $record->{Tinebase_ModelConfiguration::FLD_ACCOUNT_GRANTS};
                } elseif (!$delegateRecord instanceof Tinebase_Record_Interface) {
                    if (!isset($funcCache[$delegateRecord])) {
                        $funcCache[$delegateRecord] = $mc->fields[$mc->delegateAclField][Tinebase_Record_Abstract::CONFIG][Tinebase_Record_Abstract::CONTROLLER_CLASS_NAME]::getInstance()->get($delegateRecord, null, true, true);
                    }
                    $delegateRecord = $funcCache[$delegateRecord];
                } elseif (!isset($funcCache[$delegateRecord->getId()])) {
                    $funcCache[$delegateRecord->getId()] = $delegateRecord;
                }
                // see comment below
                $record->setAccountGrants($func($delegateRecord, $func));
            } else {
                // this is important, we allow the record to process the account grants here and then inherit that processed grants down the line
                // the model down the line do not need to know how to process the grants. HR: Division -> Employee (process grants) -> FreeTime (does not need to know!)
                $record->setAccountGrants(Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $record->{$mc->getContainerProperty()}));
            }
            return $record->{Tinebase_ModelConfiguration::FLD_ACCOUNT_GRANTS};
        };
        /** @var Tinebase_Record_Interface $record */
        foreach ($this->ids as $record) {
            if (!$record->{Tinebase_Record_Abstract::FLD_ACCOUNT_GRANTS}) {
                if (($delAclFld = $this->parentMC->delegateAclField) && $record->{$delAclFld} instanceof Tinebase_Record_RecordSet) {
                    $containerId = $record->getId();
                } else {
                    $containerId = $record->getIdFromProperty($delAclFld ?: $this->parentMC->getContainerProperty());
                }
                if (!isset($containerCache[$containerId])) {
                    $containerCache[$containerId] = $func($record, $func);
                }
                if ($containerCache[$containerId]) {
                    $record->setAccountGrants($containerCache[$containerId]);
                }
            }
        }

        // TODO add sub expanding!
    }
}
