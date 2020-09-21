<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\Repository;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\RouteInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\RouteKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\StorageKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\RouteRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Native\FileStorageRepository;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\RouteStorageKey;

class RouteRepository extends RouteRepositoryContract
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

    public function read(RouteKeyInterface $key): RouteInterface
    {
        $item = $this->repository->get($key);
        $sourceKey = $item['sourceKey'];
        $targetKey = $item['targetKey'];

        if (!$sourceKey instanceof PortalNodeKeyInterface) {
            throw new UnsupportedStorageKeyException(\get_class($sourceKey));
        }

        if (!$targetKey instanceof PortalNodeKeyInterface) {
            throw new UnsupportedStorageKeyException(\get_class($targetKey));
        }

        return new class($key, $targetKey, $sourceKey, (string) $item['type']) implements RouteInterface {
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
        foreach ($this->repository->list() as $item) {
            $itemSourceKey = $item['sourceKey'] ?? null;

            if ($item['type'] === $entityClassName &&
                $itemSourceKey instanceof StorageKeyInterface &&
                $itemSourceKey->equals($sourceKey)
            ) {
                yield $item['id'];
            }
        }
    }

    public function create(
        PortalNodeKeyInterface $sourceKey,
        PortalNodeKeyInterface $targetKey,
        string $entityClassName
    ): RouteKeyInterface {
        $key = $this->storageKeyGenerator->generateKey(RouteKeyInterface::class);

        if (!$key instanceof RouteStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($targetKey));
        }

        $this->repository->put($key, [
            'type' => $entityClassName,
            'sourceKey' => $sourceKey,
            'targetKey' => $targetKey,
        ]);

        return $key;
    }
}
