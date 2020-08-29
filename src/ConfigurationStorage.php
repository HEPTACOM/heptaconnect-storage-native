<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\ConfigurationStorageContract;

class ConfigurationStorage extends ConfigurationStorageContract
{
    public function getConfiguration(PortalNodeKeyInterface $portalNodeKey): array
    {
        // TODO: Implement getConfiguration() method.
    }

    public function setConfiguration(PortalNodeKeyInterface $portalNodeKey, ?array $data): void
    {
        // TODO: Implement setConfiguration() method.
    }
}
