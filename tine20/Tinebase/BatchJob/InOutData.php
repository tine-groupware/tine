<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  BatchJob
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Tinebase_BatchJob_InOutData
{
    public function __construct(
        protected string $id,
        protected array $data
    ) {
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $this->id)) {
            throw new Tinebase_Exception("id needs to alphanumeric with -_ allowed too");
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function toArray(): array
    {
        return ['id' => $this->id, 'data' => $this->data];
    }
}