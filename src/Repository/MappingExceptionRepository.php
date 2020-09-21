<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\Repository;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingExceptionKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\StorageKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingExceptionRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Native\FileStorageRepository;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\MappingExceptionStorageKey;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\MappingStorageKey;

class MappingExceptionRepository extends MappingExceptionRepositoryContract
{
    private FileStorageRepository $repository;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    public function __construct(
        FileStorageRepository $repository,
        StorageKeyGeneratorContract $storageKeyGenerator
    ) {
        $this->repository = $repository;
        $this->storageKeyGenerator = $storageKeyGenerator;
    }

    public function create(MappingKeyInterface $mappingKey, \Throwable $throwable): MappingExceptionKeyInterface
    {
        if (!$mappingKey instanceof MappingStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($mappingKey));
        }

        $resultKey = null;
        $previousKey = null;

        foreach (self::unwrapException($throwable) as $exceptionItem) {
            $key = $this->storageKeyGenerator->generateKey(MappingExceptionKeyInterface::class);

            if (!$key instanceof MappingExceptionStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($key));
            }

            $resultKey ??= $key;

            $this->repository->put($key, [
                'previousId' => $previousKey,
                'groupPreviousId' => $resultKey && !$key->equals($resultKey) ? $resultKey : null,
                'mappingKey' => $mappingKey,
                'type' => \get_class($exceptionItem),
                'message' => $exceptionItem->getMessage(),
                'stackTrace' => \json_encode($exceptionItem->getTrace()),
            ]);
            $previousKey = $key;
        }

        return $resultKey;
    }

    public function listByMapping(MappingKeyInterface $mappingKey): iterable
    {
        if (!$mappingKey instanceof MappingStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($mappingKey));
        }

        foreach ($this->repository->list() as $item) {
            $itemMappingKey = $item['mappingKey'];

            if ($itemMappingKey instanceof StorageKeyInterface && $itemMappingKey->equals($mappingKey)) {
                yield $item['id'];
            }
        }
    }

    public function listByMappingAndType(MappingKeyInterface $mappingKey, string $type): iterable
    {
        if (!$mappingKey instanceof MappingStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($mappingKey));
        }

        foreach ($this->repository->list() as $item) {
            $itemMappingKey = $item['mappingKey'];

            if ($itemMappingKey instanceof StorageKeyInterface &&
                $itemMappingKey->equals($mappingKey) &&
                $item['type'] === $type) {
                yield $item['id'];
            }
        }
    }

    public function delete(MappingExceptionKeyInterface $key): void
    {
        $this->repository->remove($key);
    }

    /**
     * @psalm-return array<array-key, \Throwable>
     */
    private static function unwrapException(\Throwable $exception): array
    {
        $exceptions = [$exception];

        while (($exception = $exception->getPrevious()) instanceof \Throwable) {
            $exceptions[] = $exception;
        }

        return $exceptions;
    }
}
