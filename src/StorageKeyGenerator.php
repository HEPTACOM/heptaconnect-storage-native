<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\StorageKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;

class StorageKeyGenerator extends StorageKeyGeneratorContract
{
    public function generateKey(string $keyClassName): StorageKeyInterface
    {
        throw new UnsupportedStorageKeyException($keyClassName);
    }
}
