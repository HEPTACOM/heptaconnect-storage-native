<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\StorageKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\PortalNodeStorageKey;

class StorageKeyGenerator extends StorageKeyGeneratorContract
{
    private const FILE_KEYS = 'storage_key_generator/keys.json';

    private FileStorageHandler $storage;

    public function __construct(FileStorageHandler $storage)
    {
        $this->storage = $storage;
    }

    public function generateKey(string $keyClassName): StorageKeyInterface
    {
        if ($keyClassName === PortalNodeKeyInterface::class) {
            return new PortalNodeStorageKey((string) $this->nextPrimaryKey('portal_node'));
        }

        throw new UnsupportedStorageKeyException($keyClassName);
    }

    private function nextPrimaryKey(string $type): int
    {
        $keys = (array) \json_decode($this->storage->get(self::FILE_KEYS) ?? '{}', true);
        $result = $keys[$type] = ($keys[$type] ?? 0) + 1;
        $this->storage->put(self::FILE_KEYS, \json_encode($keys, JSON_PRETTY_PRINT));

        return $result;
    }
}
