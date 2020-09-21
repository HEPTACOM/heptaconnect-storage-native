<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\StorageKey;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;

class MappingNodeStorageKey extends AbstractStorageKey implements MappingNodeKeyInterface
{
    public const TYPE_KEY = 'mapping_node';

    public function __construct(string $id)
    {
        parent::__construct(self::TYPE_KEY, $id);
    }
}
