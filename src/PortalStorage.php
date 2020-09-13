<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\PortalStorageContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\PortalNodeStorageKey;

class PortalStorage extends PortalStorageContract
{
    private const KEY_VALUE = 'value';

    private const KEY_TYPE = 'type';

    private FileStorageHandler $fileStorageHandler;

    public function __construct(FileStorageHandler $fileStorageHandler)
    {
        $this->fileStorageHandler = $fileStorageHandler;
    }

    public function set(PortalNodeKeyInterface $portalNodeKey, string $key, string $value, string $type): void
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $storageFile = $this->getStoragePath($portalNodeKey);
        $data = (array) \json_decode($this->fileStorageHandler->get($storageFile) ?? '[]', true);
        $data[$key] = [
            self::KEY_VALUE => $value,
            self::KEY_TYPE => $type,
        ];
        $this->fileStorageHandler->put($storageFile, \json_encode($data, \JSON_PRETTY_PRINT));
    }

    public function unset(PortalNodeKeyInterface $portalNodeKey, string $key): void
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $storageFile = $this->getStoragePath($portalNodeKey);
        $data = (array) \json_decode($this->fileStorageHandler->get($storageFile) ?? '[]', true);

        if (!\array_key_exists($key, $data)) {
            throw new NotFoundException();
        }

        unset($data[$key]);
        $this->fileStorageHandler->put($storageFile, \json_encode($data, \JSON_PRETTY_PRINT));
    }

    public function getValue(PortalNodeKeyInterface $portalNodeKey, string $key): string
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $result = $this->innerGet($portalNodeKey, $key);

        if (!\is_array($result) || !\array_key_exists(self::KEY_VALUE, $result)) {
            throw new NotFoundException();
        }

        return (string) $result[self::KEY_VALUE];
    }

    public function getType(PortalNodeKeyInterface $portalNodeKey, string $key): string
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $result = $this->innerGet($portalNodeKey, $key);

        if (!\is_array($result) || !\array_key_exists(self::KEY_TYPE, $result)) {
            throw new NotFoundException();
        }

        return (string) $result[self::KEY_TYPE];
    }

    public function has(PortalNodeKeyInterface $portalNodeKey, string $key): bool
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $result = $this->innerGet($portalNodeKey, $key);

        return \is_array($result) && \array_key_exists(self::KEY_VALUE, $result) && \array_key_exists(self::KEY_TYPE, $result);
    }

    private function innerGet(PortalNodeStorageKey $portalNodeKey, string $key): ?array
    {
        $storageFile = $this->getStoragePath($portalNodeKey);
        $data = (array) \json_decode($this->fileStorageHandler->get($storageFile) ?? '[]', true);

        return $data[$key] ?? null;
    }

    private function getStoragePath(PortalNodeStorageKey $portalNodeKey): string
    {
        return \sprintf('portalStorage/%s.json', $portalNodeKey->getId());
    }
}
