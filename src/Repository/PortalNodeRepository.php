<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\Repository;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\PortalNodeRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Native\FileStorageHandler;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\PortalNodeStorageKey;

class PortalNodeRepository extends PortalNodeRepositoryContract
{
    private FileStorageHandler $fileStorageHandler;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    public function __construct(
        FileStorageHandler $fileStorageHandler,
        StorageKeyGeneratorContract $storageKeyGenerator
    ) {
        $this->fileStorageHandler = $fileStorageHandler;
        $this->storageKeyGenerator = $storageKeyGenerator;
    }

    public function read(PortalNodeKeyInterface $portalNodeKey): string
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $data = $this->fileStorageHandler->getJson($this->getStoragePath());
        $key = $this->storageKeyGenerator->serialize($portalNodeKey);

        if (!\array_key_exists($key, $data)) {
            throw new NotFoundException();
        }

        return $data[$key]['className'];
    }

    public function listAll(): iterable
    {
        $data = $this->fileStorageHandler->getJson($this->getStoragePath());

        foreach ($data as $portalNodeId => $portalNode) {
            yield $this->storageKeyGenerator->deserialize($portalNodeId);
        }
    }

    public function listByClass(string $className): iterable
    {
        $data = $this->fileStorageHandler->getJson($this->getStoragePath());

        foreach ($data as $portalNodeId => $portalNode) {
            if ($portalNode['className'] === $className) {
                yield $this->storageKeyGenerator->deserialize($portalNodeId);
            }
        }
    }

    public function create(string $className): PortalNodeKeyInterface
    {
        $data = $this->fileStorageHandler->getJson($this->getStoragePath());
        $id = $this->storageKeyGenerator->generateKey(PortalNodeKeyInterface::class);
        $data[$this->storageKeyGenerator->serialize($id)] = [
            'className' => $className,
        ];
        $this->fileStorageHandler->putJson($this->getStoragePath(), $data);

        return $id;
    }

    public function delete(PortalNodeKeyInterface $portalNodeKey): void
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $data = $this->fileStorageHandler->getJson($this->getStoragePath());
        $key = $this->storageKeyGenerator->serialize($portalNodeKey);

        if (!\array_key_exists($key, $data)) {
            throw new NotFoundException();
        }

        unset($data[$key]);
        $this->fileStorageHandler->putJson($this->getStoragePath(), $data);
    }

    private function getStoragePath(): string
    {
        return 'portalNodes.json';
    }
}
