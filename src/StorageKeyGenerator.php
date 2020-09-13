<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobRunKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\StorageKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\CronjobRunStorageKey;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\CronjobStorageKey;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\PortalNodeStorageKey;

class StorageKeyGenerator extends StorageKeyGeneratorContract
{
    private const FILE_KEYS = 'storage_key_generator/keys.json';

    private const TYPE_KEY_MAP = [
        CronjobStorageKey::TYPE_KEY => CronjobStorageKey::class,
        CronjobRunStorageKey::TYPE_KEY => CronjobRunStorageKey::class,
        PortalNodeStorageKey::TYPE_KEY => PortalNodeStorageKey::class,
    ];

    private const IMPLEMENTATION_MAP = [
        CronjobKeyInterface::class => CronjobStorageKey::class,
        CronjobRunKeyInterface::class => CronjobRunStorageKey::class,
        PortalNodeKeyInterface::class => PortalNodeStorageKey::class,
    ];

    private FileStorageHandler $storage;

    public function __construct(FileStorageHandler $storage)
    {
        $this->storage = $storage;
    }

    public function generateKey(string $keyClassName): StorageKeyInterface
    {
        return $this->createKey($keyClassName, null);
    }

    private function nextPrimaryKey(string $type): int
    {
        $keys = (array) \json_decode($this->storage->get(self::FILE_KEYS) ?? '{}', true);
        $result = $keys[$type] = ($keys[$type] ?? 0) + 1;
        $this->storage->put(self::FILE_KEYS, \json_encode($keys, \JSON_PRETTY_PRINT));

        return $result;
    }

    public function serialize(StorageKeyInterface $key): string
    {
        if (!$key instanceof AbstractStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        return \sprintf('%s:%s', $key->getType(), $key->getId());
    }

    public function deserialize(string $keyData): StorageKeyInterface
    {
        [$type, $key] = \explode(':', $keyData, 2);

        if (!\is_numeric($key)) {
            throw new UnsupportedStorageKeyException(StorageKeyInterface::class);
        }

        if (!\array_key_exists($type, self::TYPE_KEY_MAP)) {
            throw new UnsupportedStorageKeyException(StorageKeyInterface::class);
        }

        return $this->createKey(self::TYPE_KEY_MAP[$type], (int) $key);
    }

    private function createKey(string $keyClassName, ?int $id = null): StorageKeyInterface
    {
        if (!\array_key_exists($keyClassName, self::IMPLEMENTATION_MAP)) {
            throw new UnsupportedStorageKeyException($keyClassName);
        }

        $class = self::IMPLEMENTATION_MAP[$keyClassName];

        if (\is_null($id)) {
            if (($type = \array_search($class, self::TYPE_KEY_MAP, true)) === false) {
                throw new UnsupportedStorageKeyException($keyClassName);
            }

            $id = $this->nextPrimaryKey($type);
        }

        return $class((string)$id);
    }
}
