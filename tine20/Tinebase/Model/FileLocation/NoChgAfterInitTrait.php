<?php declare(strict_types=1);

trait Tinebase_Model_FileLocation_NoChgAfterInitTrait
{
    public function __set($_name, $_value)
    {
        if ($this->_init) {
            throw new Tinebase_Exception('can\'t change ' . static::class . ' after initialization');
        }
        parent::__set($_name, $_value); // TODO: Change the autogenerated stub
    }

    protected function _init(): void
    {
        $this->_init = true;
    }

    protected bool $_init = false;
}
