<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\StorageKey;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingKeyInterface;

class MappingStorageKey extends AbstractStorageKey implements MappingKeyInterface
{
    public const TYPE_KEY = 'mapping';

    public function __construct(string $id)
    {
        parent::__construct(self::TYPE_KEY, $id);
    }
}
