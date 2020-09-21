<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\StorageKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\AbstractStorageKey;

class FileStorageRepository
{
    private StorageKeyGeneratorContract $storageKeyGenerator;

    private FileStorageHandler $fileStorageHandler;

    private string $storagePath;

    public function __construct(
        StorageKeyGeneratorContract $storageKeyGenerator,
        FileStorageHandler $fileStorageHandler,
        string $storagePath
    ) {
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->fileStorageHandler = $fileStorageHandler;
        $this->storagePath = $storagePath;
    }

    /**
     * @throws UnsupportedStorageKeyException
     */
    public function put(StorageKeyInterface $key, array $content): void
    {
        if (!$key instanceof AbstractStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $keyData = $this->storageKeyGenerator->serialize($key);
        $items = $this->fileStorageHandler->getJson($this->storagePath);
        $items[$keyData] = $this->pack($content);
        $this->fileStorageHandler->putJson($this->storagePath, $items);
    }

    /**
     * @throws UnsupportedStorageKeyException
     */
    public function has(StorageKeyInterface $key): bool
    {
        if (!$key instanceof AbstractStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $keyData = $this->storageKeyGenerator->serialize($key);
        $items = $this->fileStorageHandler->getJson($this->storagePath);

        return \array_key_exists($keyData, $items);
    }

    /**
     * @throws NotFoundException
     * @throws UnsupportedStorageKeyException
     */
    public function get(StorageKeyInterface $key): array
    {
        if (!$key instanceof AbstractStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $keyData = $this->storageKeyGenerator->serialize($key);
        $items = $this->fileStorageHandler->getJson($this->storagePath);

        if (!\array_key_exists($keyData, $items)) {
            throw new NotFoundException();
        }

        return $this->unpack($items[$keyData]);
    }

    /**
     * @throws NotFoundException
     * @throws UnsupportedStorageKeyException
     */
    public function remove(StorageKeyInterface $key): void
    {
        if (!$key instanceof AbstractStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $keyData = $this->storageKeyGenerator->serialize($key);
        $items = $this->fileStorageHandler->getJson($this->storagePath);

        if (!\array_key_exists($keyData, $items)) {
            throw new NotFoundException();
        }

        unset($items[$keyData]);

        $this->fileStorageHandler->putJson($this->storagePath, $items);
    }

    public function list(): iterable
    {
        $items = $this->fileStorageHandler->getJson($this->storagePath);

        foreach ($items as $key => $item) {
            $yield = $this->unpack($item);
            $yield['id'] = $this->storageKeyGenerator->deserialize($key);
            yield $yield;
        }
    }

    private function unpack(array $data): array
    {
        $storageKeys = $data['_keys'] ?? [];
        $datetimeKeys = $data['_datetimes'] ?? [];
        unset($data['_keys'], $data['_datetimes']);

        foreach ($storageKeys as $key) {
            $data[$key] = $this->storageKeyGenerator->deserialize($data[$key]);
        }

        foreach ($datetimeKeys as $key) {
            $data[$key] = \date_create()->setTimestamp((int) $data[$key]);
        }

        return $data;
    }

    private function pack(array $data): array
    {
        $storageKeys = [];
        $datetimeKeys = [];

        foreach ($data as $key => &$value) {
            if ($value instanceof AbstractStorageKey) {
                $storageKeys[] = $key;
                $value = $this->storageKeyGenerator->serialize($value);
            }

            if ($value instanceof \DateTimeInterface) {
                $datetimeKeys[] = $key;
                $value = $value->getTimestamp();
            }
        }

        unset($value);

        $data['_keys'] = $storageKeys;
        $data['_datetimes'] = $datetimeKeys;

        return $data;
    }
}
