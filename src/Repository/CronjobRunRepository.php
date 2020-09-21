<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\Repository;

use Heptacom\HeptaConnect\Portal\Base\Cronjob\Contract\CronjobRunInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobRunKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\CronjobRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\CronjobRunRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Native\FileStorageRepository;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\CronjobRunStorageKey;

class CronjobRunRepository extends CronjobRunRepositoryContract
{
    private FileStorageRepository $repository;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private CronjobRepositoryContract $cronjobRepository;

    public function __construct(
        FileStorageRepository $repository,
        StorageKeyGeneratorContract $storageKeyGenerator,
        CronjobRepositoryContract $cronjobRepository
    ) {
        $this->repository = $repository;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->cronjobRepository = $cronjobRepository;
    }

    public function create(CronjobKeyInterface $cronjobKey, \DateTimeInterface $queuedFor): CronjobRunKeyInterface
    {
        $id = $this->storageKeyGenerator->generateKey(CronjobRunKeyInterface::class);

        if (!$id instanceof CronjobRunStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($id));
        }

        $cronjob = $this->cronjobRepository->read($cronjobKey);

        $this->repository->put($id, [
            'cronjobKey' => $cronjobKey,
            'handler' => $cronjob->getHandler(),
            'payload' => $cronjob->getPayload(),
            'queuedFor' => $queuedFor,
            'portalNodeKey' => $cronjob->getPortalNodeKey(),
        ]);

        return $id;
    }

    public function listExecutables(\DateTimeInterface $now): iterable
    {
        foreach ($this->repository->list() as $item) {
            $queuedUntil = $item['queuedUntil'] ?? null;

            if (!$queuedUntil instanceof \DateTimeInterface || $queuedUntil->getTimestamp() <= $now->getTimestamp()) {
                yield $item['id'];
            }
        }
    }

    public function read(CronjobRunKeyInterface $cronjobRunKey): CronjobRunInterface
    {
        $item = $this->repository->get($cronjobRunKey);
        $portalNodeKey = $item['portalNodeKey'];
        $cronjobKey = $item['cronjobKey'];

        if (!$portalNodeKey instanceof PortalNodeKeyInterface) {
            throw new NotFoundException();
        }

        if (!$cronjobKey instanceof CronjobKeyInterface) {
            throw new NotFoundException();
        }

        return new class($portalNodeKey, $cronjobKey, $cronjobRunKey, $item['handler'], $item['payload'], $item['queuedUntil']) implements CronjobRunInterface {
            private PortalNodeKeyInterface $portalNodeKey;

            private CronjobKeyInterface $cronjobKey;

            private CronjobRunKeyInterface $cronjobRunKey;

            private string $handler;

            private ?array $payload;

            private \DateTimeInterface $queuedFor;

            public function __construct(
                PortalNodeKeyInterface $portalNodeKey,
                CronjobKeyInterface $cronjobKey,
                CronjobRunKeyInterface $cronjobRunKey,
                string $handler,
                ?array $payload,
                \DateTimeInterface $queuedFor
            ) {
                $this->portalNodeKey = $portalNodeKey;
                $this->cronjobKey = $cronjobKey;
                $this->cronjobRunKey = $cronjobRunKey;
                $this->handler = $handler;
                $this->payload = $payload;
                $this->queuedFor = $queuedFor;
            }

            public function getPortalNodeKey(): PortalNodeKeyInterface
            {
                return $this->portalNodeKey;
            }

            public function getCronjobKey(): CronjobKeyInterface
            {
                return $this->cronjobKey;
            }

            public function getRunKey(): CronjobRunKeyInterface
            {
                return $this->cronjobRunKey;
            }

            public function getHandler(): string
            {
                return $this->handler;
            }

            public function getPayload(): ?array
            {
                return $this->payload;
            }

            public function getQueuedFor(): \DateTimeInterface
            {
                return $this->queuedFor;
            }
        };
    }

    public function updateStartedAt(CronjobRunKeyInterface $cronjobRunKey, \DateTimeInterface $now): void
    {
        $item = $this->repository->get($cronjobRunKey);
        $item['startedAt'] = $now;
        $this->repository->put($cronjobRunKey, $item);
    }

    public function updateFinishedAt(CronjobRunKeyInterface $cronjobRunKey, \DateTimeInterface $now): void
    {
        $item = $this->repository->get($cronjobRunKey);
        $item['finishedAt'] = $now;
        $this->repository->put($cronjobRunKey, $item);
    }

    public function updateFailReason(CronjobRunKeyInterface $cronjobRunKey, \Throwable $throwable): void
    {
        $item = $this->repository->get($cronjobRunKey);
        $item['throwableClass'] = \get_class($throwable);
        $item['throwableMessage'] = $throwable->getMessage();
        $item['throwableFile'] = $throwable->getFile();
        $item['throwableLine'] = $throwable->getLine();
        $this->repository->put($cronjobRunKey, $item);
    }
}
