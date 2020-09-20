<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\Repository;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\RouteInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\RouteKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\RouteRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\RouteStorageKey;

class RouteRepository extends RouteRepositoryContract
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

    public function read(RouteKeyInterface $key): RouteInterface
    {
        if (!$key instanceof RouteStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $data = $this->fileStorageHandler->getJson($this->getStoragePath());
        $keyData = $this->storageKeyGenerator->serialize($key);

        if (!\array_key_exists($keyData, $data)) {
            throw new NotFoundException();
        }

        $sourceKey = $this->storageKeyGenerator->deserialize($data[$keyData]['sourceKey']);

        if (!$sourceKey instanceof PortalNodeKeyInterface) {
            throw new UnsupportedStorageKeyException(\get_class($sourceKey));
        }

        $targetKey = $this->storageKeyGenerator->deserialize($data[$keyData]['targetKey']);

        if (!$targetKey instanceof PortalNodeKeyInterface) {
            throw new UnsupportedStorageKeyException(\get_class($targetKey));
        }

        return new class (
            $key,
            $targetKey,
            $sourceKey,
            (string) $data[$keyData]['type']
        ) implements RouteInterface {
            private RouteKeyInterface $key;

            private PortalNodeKeyInterface $targetKey;

            private PortalNodeKeyInterface $sourceKey;

            /**
             * @psalm-var class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface>
             */
            private string $entityClassName;

            /**
             * @psalm-param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface> $entityClassName
             */
            public function __construct(
                RouteKeyInterface $key,
                PortalNodeKeyInterface $targetKey,
                PortalNodeKeyInterface $sourceKey,
                string $entityClassName
            ) {
                $this->key = $key;
                $this->targetKey = $targetKey;
                $this->sourceKey = $sourceKey;
                $this->entityClassName = $entityClassName;
            }

            public function getKey(): RouteKeyInterface
            {
                return $this->key;
            }

            public function getTargetKey(): PortalNodeKeyInterface
            {
                return $this->targetKey;
            }

            public function getSourceKey(): PortalNodeKeyInterface
            {
                return $this->sourceKey;
            }

            public function getEntityClassName(): string
            {
                return $this->entityClassName;
            }
        };
    }

    public function listBySourceAndEntityType(PortalNodeKeyInterface $sourceKey, string $entityClassName): iterable
    {
        if (!$sourceKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($sourceKey));
        }

        $sourceKeyData = $this->storageKeyGenerator->serialize($sourceKey);
        $data = $this->fileStorageHandler->getJson($this->getStoragePath());

        foreach ($data as $routeKey => $route) {
            if ($route['type'] === $entityClassName && $route['sourceKey'] === $sourceKeyData) {
                yield new $this->storageKeyGenerator->deserialize($routeKey);
            }
        }
    }

    public function create(
        PortalNodeKeyInterface $sourceKey,
        PortalNodeKeyInterface $targetKey,
        string $entityClassName
    ): RouteKeyInterface {
        if (!$sourceKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($sourceKey));
        }

        if (!$targetKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($targetKey));
        }

        $key = $this->storageKeyGenerator->generateKey(RouteKeyInterface::class);

        if (!$key instanceof RouteStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($targetKey));
        }

        $data = $this->fileStorageHandler->getJson($this->getStoragePath());
        $data[$this->storageKeyGenerator->serialize($key)] = [
            'type' => $entityClassName,
            'sourceKey' => $this->storageKeyGenerator->serialize($sourceKey),
            'targetKey' => $this->storageKeyGenerator->serialize($targetKey),
        ];
        $this->fileStorageHandler->putJson($this->getStoragePath(), $data);

        return $key;
    }

    private function getStoragePath(): string
    {
        return 'routes.json';
    }
}
