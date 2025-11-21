<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2025 Metaways Infosystems GmbH (http://www.metaways.de)
 */


class Calendar_Backend_CalDav_SyncState
{
    public const SYNC_TOKEN = 'syncToken';
    public const SYNC_TOKEN_SUPPORT = 'syncTokenSupport';

    protected ?string $syncToken;
    protected ?bool $syncTokenSupport;

    public function __construct(array $data, protected string $containerKey)
    {
        $this->syncToken = $data[self::SYNC_TOKEN] ?? null;
        $this->syncTokenSupport = $data[self::SYNC_TOKEN_SUPPORT] ?? null;
    }

    public function toArray(): array
    {
        return [
            self::SYNC_TOKEN => $this->syncToken,
            self::SYNC_TOKEN_SUPPORT => $this->syncTokenSupport,
        ];
    }

    public function supportSyncToken(): bool
    {
        return false !== $this->syncTokenSupport;
    }

    public function setSyncTokenSupport(bool $val): void
    {
        $this->syncTokenSupport = $val;
    }

    public function getSyncToken(): ?string
    {
        return $this->syncToken;
    }

    public function setSyncToken(?string $syncToken): void
    {
        $this->syncToken = $syncToken;
    }

    public static function getSyncStateFromContainer(Tinebase_Model_Container $container, string $key): self
    {
        return new Calendar_Backend_CalDav_SyncState($container->xprops()[Calendar_Backend_CalDav_SyncState::class][$key] ?? [], $key);
    }

    public function storeInContainer(Tinebase_Model_Container $container): void
    {
        $container->xprops()[Calendar_Backend_CalDav_SyncState::class][$this->containerKey] = $this->toArray();
        Tinebase_Container::getInstance()->update($container);
    }
}