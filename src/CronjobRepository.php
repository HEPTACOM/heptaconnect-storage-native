<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native;

use Heptacom\HeptaConnect\Portal\Base\Cronjob\Contract\CronjobInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\StorageKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\CronjobRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\CronjobStorageKey;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\PortalNodeStorageKey;

class CronjobRepository extends CronjobRepositoryContract
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

    public function create(
        PortalNodeKeyInterface $portalNodeKey,
        string $cronExpression,
        string $handler,
        \DateTimeInterface $nextExecution,
        ?array $payload = null
    ): CronjobInterface {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $data = $this->fileStorageHandler->getJson($this->getStoragePath());
        $id = $this->storageKeyGenerator->generateKey(CronjobKeyInterface::class);
        $data[$this->storageKeyGenerator->serialize($id)] = [
            'cronExpression' => $cronExpression,
            'handler' => $handler,
            'payload' => $payload,
            'queuedUntil' => $nextExecution->getTimestamp(),
            'portalNodeKey' => $this->storageKeyGenerator->serialize($portalNodeKey),
        ];
        $this->fileStorageHandler->putJson($this->getStoragePath(), $data);

        return $this->createCronjob($portalNodeKey, $handler, $payload, $id, $cronExpression, $nextExecution);
    }

    public function read(CronjobKeyInterface $cronjobKey): CronjobInterface
    {
        if (!$cronjobKey instanceof CronjobStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($cronjobKey));
        }

        $storageFile = $this->getStoragePath();
        $data = $this->fileStorageHandler->getJson($storageFile);
        $item = $data[$this->storageKeyGenerator->serialize($cronjobKey)] ?? null;

        if (!\is_array($item)) {
            throw new NotFoundException();
        }

        $portalNodeKey = $this->storageKeyGenerator->deserialize($item['portalNodeKey']);

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
        if (!$cronjobKey instanceof CronjobStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($cronjobKey));
        }

        $storageFile = $this->getStoragePath();
        $data = $this->fileStorageHandler->getJson($storageFile);
        $key = $this->storageKeyGenerator->serialize($cronjobKey);

        if (!\array_key_exists($key, $data)) {
            throw new NotFoundException();
        }

        $data[$key]['queuedUntil'] = $nextExecution->getTimestamp();

        $this->fileStorageHandler->putJson($this->getStoragePath(), $data);
    }

    public function delete(CronjobKeyInterface $cronjobKey): void
    {
        if (!$cronjobKey instanceof CronjobStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($cronjobKey));
        }

        $storageFile = $this->getStoragePath();
        $data = $this->fileStorageHandler->getJson($storageFile);
        $key = $this->storageKeyGenerator->serialize($cronjobKey);

        if (!\array_key_exists($key, $data)) {
            throw new NotFoundException();
        }

        unset($data[$key]);
        $this->fileStorageHandler->putJson($this->getStoragePath(), $data);
    }

    public function listExecutables(?\DateTimeInterface $until = null): iterable
    {
        $storageFile = $this->getStoragePath();
        $data = $this->fileStorageHandler->getJson($storageFile);

        foreach ($data as $cronjobId => $cronjob) {
            if (!$until instanceof \DateTimeInterface || $cronjob['queuedUntil'] <= $until->getTimestamp()) {
                yield new CronjobStorageKey($cronjobId);
            }
        }
    }

    private function getStoragePath(): string
    {
        return 'cronjobs.json';
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
