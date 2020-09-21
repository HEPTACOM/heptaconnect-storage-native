<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\StorageKey;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingExceptionKeyInterface;

class MappingExceptionStorageKey extends AbstractStorageKey implements MappingExceptionKeyInterface
{
    public const TYPE_KEY = 'mapping_exception';

    public function __construct(string $id)
    {
        parent::__construct(self::TYPE_KEY, $id);
    }
}
