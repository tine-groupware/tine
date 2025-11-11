<?php declare(strict_types=1);

class Tinebase_Model_Filter_DelegatedAcl extends Tinebase_Model_Filter_Abstract implements Tinebase_Model_Filter_AclFilter
{
    protected $_operators = [
        'equals',
    ];

    protected $_requiredGrants = null;
    protected $_subFilter = null;
    protected $_joinTable = null;
    protected $_joinAlias = null;
    protected $_refIdField = null;
    protected $_idColumn = null;
    protected $_refModel = null;
    protected $_isOptional  = false;

    public function appendFilterSql($_select, $_backend)
    {
        if (empty($this->_requiredGrants)) {
            return;
        }
        $this->init();

        $db = $_backend->getAdapter();
        if ($this->_isOptional) {
            $_select->joinLeft([$this->_joinAlias => $this->_joinTable],
                $this->_getQuotedFieldName($_backend) . ' = ' . $db->quoteIdentifier(
                    $this->_joinAlias . '.' . $this->_refIdField
                ) . ' AND ' .  $db->quoteIdentifier($this->_joinAlias . '.is_deleted') . ' = 0', []);
        } else {
            $_select->join([$this->_joinAlias => $this->_joinTable],
                $this->_getQuotedFieldName($_backend) . ' = ' . $db->quoteIdentifier(
                    $this->_joinAlias . '.' . $this->_refIdField
                ) . ' AND ' . $db->quoteIdentifier($this->_joinAlias . '.is_deleted') . ' = 0', []);
        }
        if (empty($_select->getPart(Zend_Db_Select::GROUP))) {
            $_select->group($this->_options['tablename'] . '.' . $this->_idColumn);
        }

        $this->_subFilter->appendFilterSql($_select, $_backend);
    }

    public function setRequiredGrants(array $_grants)
    {
        $this->_requiredGrants = $_grants;
    }

    protected function init()
    {
        if (null !== $this->_subFilter) {
            return;
        }
        $this->_joinAlias = Tinebase_Record_Abstract::generateUID(20);

        /** @var Tinebase_Record_Interface $model */
        $model = $this->_options['modelName'];
        $mc = $model::getConfiguration();
        if (!isset($this->_options['tablename'])) {
            $this->_options['tablename'] = $mc->getTableName();
        }
        $this->_idColumn = $mc->getIdProperty();
        /** @var Tinebase_Record_Interface $refModel */
        $refModel = $this->_refModel ?? $mc->fields[$this->getField()][Tinebase_ModelConfiguration::CONFIG]
            [Tinebase_ModelConfiguration::RECORD_CLASS_NAME];
        $refMC = $refModel::getConfiguration();
        $this->_joinTable = SQL_TABLE_PREFIX . $refMC->getTableName();
        if ($this->_refIdField =
                $mc->fields[$this->getField()][Tinebase_ModelConfiguration::CONFIG][Tinebase_ModelConfiguration::REF_ID_FIELD] ?? null) {
            $this->_field = $mc->getIdProperty();
        } else {
            $this->_refIdField = 'id';
        }

        $options = array_merge($this->_options, [
            'tablename' => $this->_joinAlias,
            'modelName' => $refModel,
        ]);
        if ($refMC->{Tinebase_ModelConfiguration::DELEGATED_ACL_FIELD}) {
            $this->_subFilter = new Tinebase_Model_Filter_DelegatedAcl(
                $refMC->{Tinebase_ModelConfiguration::DELEGATED_ACL_FIELD}, null, null, $options);

        } elseif ($refMC->getContainerProperty()) {

            $this->_subFilter = new Tinebase_Model_Filter_Container(
                $refMC->getContainerProperty(), null, '/', $options);

        } else {
            throw new Tinebase_Exception_Record_DefinitionFailure($refModel . ' doesn\'t have neither ' .
                Tinebase_ModelConfiguration::DELEGATED_ACL_FIELD . ' nor ' .
                Tinebase_ModelConfiguration::CONTAINER_PROPERTY);
        }

        $this->_subFilter->setRequiredGrants($this->_requiredGrants);
    }

    protected function _setOptions(array $_options)
    {
        parent::_setOptions($_options);
        if (!isset($this->_options['modelName'])) {
            throw new Tinebase_Exception_Backend('modelName options is required');
        }
        $this->_refModel = $this->_options['refModel'] ?? null;
        $this->_isOptional = $this->_options['isOptional'] ?? false;
    }
}
