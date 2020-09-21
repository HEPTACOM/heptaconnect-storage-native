<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\Repository;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\MappingNodeStructInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingNodeRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Native\FileStorageRepository;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;

class MappingNodeRepository extends MappingNodeRepositoryContract
{
    private FileStorageRepository $repository;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private MappingRepositoryContract $mappingRepository;

    public function __construct(
        FileStorageRepository $repository,
        StorageKeyGeneratorContract $storageKeyGenerator,
        MappingRepositoryContract $mappingRepository
    ) {
        $this->repository = $repository;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->mappingRepository = $mappingRepository;
    }

    public function read(MappingNodeKeyInterface $key): MappingNodeStructInterface
    {
        $item = $this->repository->get($key);

        return new class($key, $item['type']) implements MappingNodeStructInterface {
            private MappingNodeKeyInterface $key;

            private string $datasetEntityClassName;

            public function __construct(MappingNodeKeyInterface $key, string $datasetEntityClassName)
            {
                $this->key = $key;
                $this->datasetEntityClassName = $datasetEntityClassName;
            }

            public function getKey(): MappingNodeKeyInterface
            {
                return $this->key;
            }

            public function getDatasetEntityClassName(): string
            {
                return $this->datasetEntityClassName;
            }
        };
    }

    public function listByTypeAndPortalNodeAndExternalId(
        string $datasetEntityClassName,
        PortalNodeKeyInterface $portalNodeKey,
        string $externalId
    ): iterable {
        foreach ($this->repository->list() as $node) {
            if ($node['type'] === $datasetEntityClassName) {
                foreach ($this->mappingRepository->listByNodes($node['id'], $portalNodeKey) as $mapping) {
                    if ($mapping['externalId'] === $externalId) {
                        yield $node['id'];
                    }
                }
            }
        }
    }

    public function create(
        string $datasetEntityClassName,
        PortalNodeKeyInterface $portalNodeKey
    ): MappingNodeKeyInterface {
        $key = $this->storageKeyGenerator->generateKey(MappingNodeKeyInterface::class);

        if (!$key instanceof MappingNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $this->repository->put($key, [
            'type' => $datasetEntityClassName,
            'origin' => $portalNodeKey,
        ]);

        return $key;
    }
}
