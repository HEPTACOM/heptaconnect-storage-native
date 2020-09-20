<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\Repository;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\PortalNodeRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Native\FileStorageRepository;

class PortalNodeRepository extends PortalNodeRepositoryContract
{
    private StorageKeyGeneratorContract $storageKeyGenerator;

    private FileStorageRepository $repository;

    public function __construct(
        FileStorageRepository $repository,
        StorageKeyGeneratorContract $storageKeyGenerator
    ) {
        $this->repository = $repository;
        $this->storageKeyGenerator = $storageKeyGenerator;
    }

    public function read(PortalNodeKeyInterface $portalNodeKey): string
    {
        return $this->repository->get($portalNodeKey)['className'];
    }

    public function listAll(): iterable
    {
        foreach ($this->repository->list() as $portalNode) {
            yield $portalNode['id'];
        }
    }

    public function listByClass(string $className): iterable
    {
        foreach ($this->repository->list() as $portalNode) {
            if ($portalNode['className'] === $className) {
                yield $portalNode['id'];
            }
        }
    }

    public function create(string $className): PortalNodeKeyInterface
    {
        $id = $this->storageKeyGenerator->generateKey(PortalNodeKeyInterface::class);

        if (!$id instanceof PortalNodeKeyInterface) {
            throw new UnsupportedStorageKeyException(\get_class($id));
        }

        $this->repository->put($id, [
            'className' => $className,
        ]);

        return $id;
    }

    public function delete(PortalNodeKeyInterface $portalNodeKey): void
    {
        $this->repository->remove($portalNodeKey);
    }

    private function getStoragePath(): string
    {
        return 'portalNodes.json';
    }
}
