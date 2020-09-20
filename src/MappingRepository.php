<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native;

use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\MappingRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\MappingStorageKey;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;

class MappingRepository extends MappingRepositoryContract
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

    public function read(MappingKeyInterface $key): MappingInterface
    {
        if (!$key instanceof MappingStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $storageFile = $this->getStoragePath();
        $data = $this->fileStorageHandler->getJson($storageFile);
        $keyData = $this->storageKeyGenerator->serialize($key);

        if (!\array_key_exists($keyData, $data)) {
            throw new NotFoundException();
        }

        $item = (array) $data[$keyData];

        $portalNodeKey = $this->storageKeyGenerator->deserialize($item['portalNodeKey']);

        if (!$portalNodeKey instanceof PortalNodeKeyInterface) {
            throw new NotFoundException();
        }

        $mappingNodeKey = $this->storageKeyGenerator->deserialize($item['mappingNodeKey']);

        if (!$mappingNodeKey instanceof MappingNodeKeyInterface) {
            throw new NotFoundException();
        }

        return new class (
            $item['externalId'],
            $portalNodeKey,
            $mappingNodeKey,
            $item['mappingNodeType']
        ) implements MappingInterface {
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
        $storageFile = $this->getStoragePath();
        $data = $this->fileStorageHandler->getJson($storageFile);

        $mappingNodeKeyData = $this->storageKeyGenerator->serialize($mappingNodeKey);
        $portalNodeKeyData = $this->storageKeyGenerator->serialize($portalNodeKey);

        foreach ($data as $mappingId => $mapping) {
            if ($mapping['mappingNodeKey'] === $mappingNodeKeyData &&
                $mapping['portalNodeKey'] === $portalNodeKeyData) {
                yield $this->storageKeyGenerator->deserialize($mappingId);
            }
        }
    }

    public function create(
        PortalNodeKeyInterface $portalNodeKey,
        MappingNodeKeyInterface $mappingNodeKey,
        ?string $externalId
    ): MappingKeyInterface {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        if (!$mappingNodeKey instanceof MappingNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($mappingNodeKey));
        }

        $id = $this->storageKeyGenerator->generateKey(MappingKeyInterface::class);

        if (!$id instanceof MappingStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($id));
        }

        $data = $this->fileStorageHandler->getJson($this->getStoragePath());
        $data[$this->storageKeyGenerator->serialize($id)] = [
            'portalNodeKey' => $this->storageKeyGenerator->serialize($portalNodeKey),
            'mappingNodeKey' => $this->storageKeyGenerator->serialize($mappingNodeKey),
            'mappingNodeType' => null, // TODO fill when MappingNodeRepository is present
            'externalId' => $externalId,
        ];
        $this->fileStorageHandler->putJson($this->getStoragePath(), $data);

        return $id;
    }

    public function updateExternalId(MappingKeyInterface $key, ?string $externalId): void
    {
        if (!$key instanceof MappingStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $storageFile = $this->getStoragePath();
        $data = $this->fileStorageHandler->getJson($storageFile);
        $keyData = $this->storageKeyGenerator->serialize($key);

        if (!\array_key_exists($keyData, $data)) {
            throw new NotFoundException();
        }

        $data[$keyData]['externalId'] = $externalId;

        $this->fileStorageHandler->putJson($this->getStoragePath(), $data);
    }

    public function delete(MappingKeyInterface $key): void
    {
        if (!$key instanceof MappingStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $storageFile = $this->getStoragePath();
        $data = $this->fileStorageHandler->getJson($storageFile);
        $keyData = $this->storageKeyGenerator->serialize($key);

        if (!\array_key_exists($keyData, $data)) {
            throw new NotFoundException();
        }

        unset($data[$keyData]);
        $this->fileStorageHandler->putJson($this->getStoragePath(), $data);
    }

    private function getStoragePath(): string
    {
        return 'mappings.json';
    }
}
