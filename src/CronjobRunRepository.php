<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native;

use Heptacom\HeptaConnect\Portal\Base\Cronjob\Contract\CronjobRunInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobRunKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\CronjobRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\CronjobRunRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\CronjobRunStorageKey;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\CronjobStorageKey;

class CronjobRunRepository extends CronjobRunRepositoryContract
{
    private FileStorageHandler $fileStorageHandler;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private CronjobRepositoryContract $cronjobRepository;

    public function __construct(
        FileStorageHandler $fileStorageHandler,
        StorageKeyGeneratorContract $storageKeyGenerator,
        CronjobRepositoryContract $cronjobRepository
    ) {
        $this->fileStorageHandler = $fileStorageHandler;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->cronjobRepository = $cronjobRepository;
    }

    public function create(CronjobKeyInterface $cronjobKey, \DateTimeInterface $queuedFor): CronjobRunKeyInterface
    {
        if (!$cronjobKey instanceof CronjobStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($cronjobKey));
        }

        $data = $this->fileStorageHandler->getJson($this->getStoragePath());
        $id = $this->storageKeyGenerator->generateKey(CronjobRunKeyInterface::class);

        if (!$id instanceof CronjobRunStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($id));
        }

        $cronjob = $this->cronjobRepository->read($cronjobKey);

        $data[$this->storageKeyGenerator->serialize($id)] = [
            'cronjobKey' => $this->storageKeyGenerator->serialize($cronjobKey),
            'handler' => $cronjob->getHandler(),
            'payload' => $cronjob->getPayload(),
            'queuedFor' => $queuedFor->getTimestamp(),
            'portalNodeKey' => $this->storageKeyGenerator->serialize($cronjob->getPortalNodeKey()),
        ];
        $this->fileStorageHandler->putJson($this->getStoragePath(), $data);

        return $id;
    }

    public function listExecutables(\DateTimeInterface $now): iterable
    {
        $storageFile = $this->getStoragePath();
        $data = $this->fileStorageHandler->getJson($storageFile);

        foreach ($data as $cronjobId => $cronjob) {
            if ($cronjob['queuedFor'] <= $now->getTimestamp()) {
                yield new CronjobRunStorageKey($cronjobId);
            }
        }
    }

    /**
     * @throws UnsupportedStorageKeyException
     */
    public function read(CronjobRunKeyInterface $cronjobRunKey): CronjobRunInterface
    {
        if (!$cronjobRunKey instanceof CronjobRunStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($cronjobRunKey));
        }

        $data = $this->fileStorageHandler->getJson($this->getStoragePath());
        $item = $data[$this->storageKeyGenerator->serialize($cronjobRunKey)] ?? null;

        if (!\is_array($item)) {
            throw new NotFoundException();
        }

        $portalNodeKey = $this->storageKeyGenerator->deserialize($item['portalNodeKey']);

        if (!$portalNodeKey instanceof PortalNodeKeyInterface) {
            throw new NotFoundException();
        }

        $cronjobKey = $this->storageKeyGenerator->deserialize($item['cronjobKey']);

        if (!$cronjobKey instanceof CronjobKeyInterface) {
            throw new NotFoundException();
        }

        return new class (
            $portalNodeKey,
            $cronjobKey,
            $cronjobRunKey,
            $item['handler'],
            $item['payload'],
            \date_create()
                ->setTimestamp((int) $item['queuedUntil'])
                ->setTimezone(new \DateTimeZone(\DateTimeZone::UTC))
        ) implements CronjobRunInterface {
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

    /**
     * @throws NotFoundException
     * @throws UnsupportedStorageKeyException
     */
    public function updateStartedAt(CronjobRunKeyInterface $cronjobRunKey, \DateTimeInterface $now): void
    {
        if (!$cronjobRunKey instanceof CronjobRunStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($cronjobRunKey));
        }

        $storageFile = $this->getStoragePath();
        $data = $this->fileStorageHandler->getJson($storageFile);
        $key = $this->storageKeyGenerator->serialize($cronjobRunKey);

        if (!\array_key_exists($key, $data)) {
            throw new NotFoundException();
        }

        $data[$key]['startedAt'] = $now->getTimestamp();

        $this->fileStorageHandler->putJson($this->getStoragePath(), $data);
    }

    /**
     * @throws NotFoundException
     * @throws UnsupportedStorageKeyException
     */
    public function updateFinishedAt(CronjobRunKeyInterface $cronjobRunKey, \DateTimeInterface $now): void
    {
        if (!$cronjobRunKey instanceof CronjobRunStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($cronjobRunKey));
        }

        $storageFile = $this->getStoragePath();
        $data = $this->fileStorageHandler->getJson($storageFile);
        $key = $this->storageKeyGenerator->serialize($cronjobRunKey);

        if (!\array_key_exists($key, $data)) {
            throw new NotFoundException();
        }

        $data[$key]['finishedAt'] = $now->getTimestamp();

        $this->fileStorageHandler->putJson($this->getStoragePath(), $data);
    }

    /**
     * @throws NotFoundException
     * @throws UnsupportedStorageKeyException
     */
    public function updateFailReason(CronjobRunKeyInterface $cronjobRunKey, \Throwable $throwable): void
    {
        if (!$cronjobRunKey instanceof CronjobRunStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($cronjobRunKey));
        }

        $storageFile = $this->getStoragePath();
        $data = $this->fileStorageHandler->getJson($storageFile);
        $key = $this->storageKeyGenerator->serialize($cronjobRunKey);

        if (!\array_key_exists($key, $data)) {
            throw new NotFoundException();
        }

        $data[$key]['throwableClass'] = \get_class($throwable);
        $data[$key]['throwableMessage'] = $throwable->getMessage();
        $data[$key]['throwableFile'] = $throwable->getFile();
        $data[$key]['throwableLine'] = $throwable->getLine();

        $this->fileStorageHandler->putJson($this->getStoragePath(), $data);
    }

    private function getStoragePath(): string
    {
        return 'cronjobRuns.json';
    }
}
