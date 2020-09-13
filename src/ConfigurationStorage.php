<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\ConfigurationStorageContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\PortalNodeStorageKey;

class ConfigurationStorage extends ConfigurationStorageContract
{
    private FileStorageHandler $storage;

    public function __construct(FileStorageHandler $storage)
    {
        $this->storage = $storage;
    }

    public function getConfiguration(PortalNodeKeyInterface $portalNodeKey): array
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        return $this->storage->getJson($this->getStoragePath($portalNodeKey));
    }

    public function setConfiguration(PortalNodeKeyInterface $portalNodeKey, ?array $data): void
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $this->storage->putJson($this->getStoragePath($portalNodeKey), $data);
    }

    private function getStoragePath(PortalNodeStorageKey $portalNodeKey): string
    {
        return \sprintf('configuration/%s.json', $portalNodeKey->getId());
    }
}
