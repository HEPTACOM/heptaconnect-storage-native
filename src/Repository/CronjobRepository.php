<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\Repository;

use Heptacom\HeptaConnect\Portal\Base\Cronjob\Contract\CronjobInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\StorageKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\CronjobRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Native\FileStorageRepository;

class CronjobRepository extends CronjobRepositoryContract
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

    public function create(
        PortalNodeKeyInterface $portalNodeKey,
        string $cronExpression,
        string $handler,
        \DateTimeInterface $nextExecution,
        ?array $payload = null
    ): CronjobInterface {
        $key = $this->storageKeyGenerator->generateKey(CronjobKeyInterface::class);
        $this->repository->put($key, [
            'cronExpression' => $cronExpression,
            'handler' => $handler,
            'payload' => $payload,
            'queuedUntil' => $nextExecution->getTimestamp(),
            'portalNodeKey' => $portalNodeKey,
        ]);

        return $this->createCronjob($portalNodeKey, $handler, $payload, $key, $cronExpression, $nextExecution);
    }

    public function read(CronjobKeyInterface $cronjobKey): CronjobInterface
    {
        $item = $this->repository->get($cronjobKey);
        $portalNodeKey = $item['portalNodeKey'];

        if (!$portalNodeKey instanceof PortalNodeKeyInterface) {
            throw new NotFoundException();
        }

        return $this->createCronjob(
            $portalNodeKey,
            $item['handler'],
            $item['payload'] ?? null,
            $cronjobKey,
            $item['cronExpression'],
            \date_create()
                ->setTimestamp((int) $item['queuedUntil'])
                ->setTimezone(new \DateTimeZone(\DateTimeZone::UTC))
        );
    }

    public function updateNextExecutionTime(CronjobKeyInterface $cronjobKey, \DateTimeInterface $nextExecution): void
    {
        $item = $this->repository->get($cronjobKey);
        $item['queuedUntil'] = $nextExecution;
        $this->repository->put($cronjobKey, $item);
    }

    public function delete(CronjobKeyInterface $cronjobKey): void
    {
        $this->repository->remove($cronjobKey);
    }

    public function listExecutables(?\DateTimeInterface $until = null): iterable
    {
        foreach ($this->repository->list() as $item) {
            $queuedUntil = $item['queuedUntil'] ?? null;

            if (!$until instanceof \DateTimeInterface ||
                !$queuedUntil instanceof \DateTimeInterface ||
                $queuedUntil->getTimestamp() <= $until->getTimestamp()) {
                yield $item['id'];
            }
        }
    }

    private function createCronjob(
        PortalNodeKeyInterface $portalNodeKey,
        string $handler,
        ?array $payload,
        StorageKeyInterface $id,
        string $cronExpression,
        \DateTimeInterface $queuedUntil
    ): CronjobInterface {
        return new class ($portalNodeKey, $handler, $payload, $id, $cronExpression, $queuedUntil) implements CronjobInterface {
            private PortalNodeKeyInterface $portalNodeKey;

            private string $handler;

            private ?array $payload;

            private CronjobKeyInterface $id;

            private string $cronExpression;

            private \DateTimeInterface $queuedUntil;

            public function __construct(
                PortalNodeKeyInterface $portalNodeKey,
                string $handler,
                ?array $payload,
                CronjobKeyInterface $id,
                string $cronExpression,
                \DateTimeInterface $queuedUntil
            ) {
                $this->portalNodeKey = $portalNodeKey;
                $this->handler = $handler;
                $this->payload = $payload;
                $this->id = $id;
                $this->cronExpression = $cronExpression;
                $this->queuedUntil = $queuedUntil;
            }

            public function getPortalNodeKey(): PortalNodeKeyInterface
            {
                return $this->portalNodeKey;
            }

            public function getCronjobKey(): CronjobKeyInterface
            {
                return $this->id;
            }

            public function getHandler(): string
            {
                return $this->handler;
            }

            public function getPayload(): ?array
            {
                return $this->payload;
            }

            public function getCronExpression(): string
            {
                return $this->cronExpression;
            }

            public function getQueuedUntil(): \DateTimeInterface
            {
                return $this->queuedUntil;
            }
        };
    }
}
