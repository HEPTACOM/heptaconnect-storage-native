<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\Repository;

use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\StorageKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingNodeRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Native\FileStorageRepository;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\MappingStorageKey;

class MappingRepository extends MappingRepositoryContract
{
    private FileStorageRepository $repository;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private MappingNodeRepositoryContract $mappingNodeRepository;

    public function __construct(
        FileStorageRepository $repository,
        StorageKeyGeneratorContract $storageKeyGenerator,
        MappingNodeRepositoryContract $mappingNodeRepository
    ) {
        $this->repository = $repository;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->mappingNodeRepository = $mappingNodeRepository;
    }

    public function read(MappingKeyInterface $key): MappingInterface
    {
        $item = $this->repository->get($key);
        $portalNodeKey = $item['portalNodeKey'];
        $mappingNodeKey = $item['mappingNodeKey'];

        if (!$portalNodeKey instanceof PortalNodeKeyInterface) {
            throw new NotFoundException();
        }

        if (!$mappingNodeKey instanceof MappingNodeKeyInterface) {
            throw new NotFoundException();
        }

        return new class($item['externalId'], $portalNodeKey, $mappingNodeKey, $item['mappingNodeType']) implements MappingInterface {
            private ?string $externalId;

            private PortalNodeKeyInterface $portalNodeKey;

            private MappingNodeKeyInterface $mappingNodeKey;

            private string $datasetEntityClass;

            /**
             * @psalm-param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface> $datasetEntityClass
             */
            public function __construct(
                ?string $externalId,
                PortalNodeKeyInterface $portalNodeKey,
                MappingNodeKeyInterface $mappingNodeKey,
                string $datasetEntityClass
            ) {
                $this->externalId = $externalId;
                $this->portalNodeKey = $portalNodeKey;
                $this->mappingNodeKey = $mappingNodeKey;
                $this->datasetEntityClass = $datasetEntityClass;
            }

            public function getExternalId(): ?string
            {
                return $this->externalId;
            }

            public function setExternalId(string $externalId): MappingInterface
            {
                $this->externalId = $externalId;

                return $this;
            }

            public function getPortalNodeKey(): PortalNodeKeyInterface
            {
                return $this->portalNodeKey;
            }

            public function getMappingNodeKey(): MappingNodeKeyInterface
            {
                return $this->mappingNodeKey;
            }

            public function getDatasetEntityClassName(): string
            {
                return $this->datasetEntityClass;
            }
        };
    }

    public function listByNodes(
        MappingNodeKeyInterface $mappingNodeKey,
        PortalNodeKeyInterface $portalNodeKey
    ): iterable {
        foreach ($this->repository->list() as $item) {
            $itemMappingNodeKey = $item['mappingNodeKey'] ?? null;
            $itemPortalNodeKey = $item['portalNodeKey'] ?? null;

            if ($itemMappingNodeKey instanceof StorageKeyInterface &&
                $itemPortalNodeKey instanceof StorageKeyInterface &&
                $itemMappingNodeKey->equals($mappingNodeKey) &&
                $itemPortalNodeKey->equals($portalNodeKey)
            ) {
                yield $item['id'];
            }
        }
    }

    public function listByPortalNodeAndType(PortalNodeKeyInterface $portalNodeKey, string $datasetEntityType): iterable
    {
        foreach ($this->repository->list() as $item) {
            $itemPortalNodeKey = $item['portalNodeKey'] ?? null;

            if (
                $item['mappingNodeType'] === $datasetEntityType &&
                $itemPortalNodeKey instanceof StorageKeyInterface &&
                $itemPortalNodeKey->equals($portalNodeKey)
            ) {
                yield $item['id'];
            }
        }
    }

    public function create(
        PortalNodeKeyInterface $portalNodeKey,
        MappingNodeKeyInterface $mappingNodeKey,
        ?string $externalId
    ): MappingKeyInterface {
        $id = $this->storageKeyGenerator->generateKey(MappingKeyInterface::class);

        if (!$id instanceof MappingStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($id));
        }

        $this->repository->put($id, [
            'portalNodeKey' => $portalNodeKey,
            'mappingNodeKey' => $mappingNodeKey,
            'mappingNodeType' => $this->mappingNodeRepository->read($mappingNodeKey)->getDatasetEntityClassName(),
            'externalId' => $externalId,
        ]);

        return $id;
    }

    public function updateExternalId(MappingKeyInterface $key, ?string $externalId): void
    {
        $item = $this->repository->get($key);
        $item['externalId'] = $externalId;
        $this->repository->put($key, $item);
    }

    public function delete(MappingKeyInterface $key): void
    {
        $this->repository->remove($key);
    }
}
