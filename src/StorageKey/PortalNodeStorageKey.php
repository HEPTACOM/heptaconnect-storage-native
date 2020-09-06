<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\StorageKey;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;

class PortalNodeStorageKey extends AbstractStorageKey implements PortalNodeKeyInterface
{
    public function __construct(string $id)
    {
        parent::__construct('portal_node', $id);
    }
}
