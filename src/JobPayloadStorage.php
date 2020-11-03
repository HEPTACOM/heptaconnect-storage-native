<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native;

use Heptacom\HeptaConnect\Storage\Base\Contract\JobPayloadKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobPayloadStorageContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\JobPayloadStorageKey;

class JobPayloadStorage extends JobPayloadStorageContract
{
    private const KEY_PAYLOAD = 'payload';

    private const KEY_CHECKSUM = 'checksum';

    private const KEY_FORMAT = 'format';

    /**
     * @deprecated TODO remove serialized format
     */
    private const FORMAT_SERIALIZED = 'serialized';

    private FileStorageHandler $fileStorageHandler;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    public function __construct(
        FileStorageHandler $fileStorageHandler,
        StorageKeyGeneratorContract $storageKeyGenerator
    ) {
        $this->fileStorageHandler = $fileStorageHandler;
        $this->storageKeyGenerator = $storageKeyGenerator;
    }

    public function add(object $payload): JobPayloadKeyInterface
    {
        $key = $this->storageKeyGenerator->generateKey(JobPayloadKeyInterface::class);

        if (!$key instanceof JobPayloadStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $storageFile = $this->getStoragePath();
        $data = $this->fileStorageHandler->getJson($storageFile);
        $keyData = $this->storageKeyGenerator->serialize($key);

        $serialized = \serialize($payload);
        $data[$keyData] = [
            self::KEY_PAYLOAD => $serialized,
            self::KEY_CHECKSUM => \crc32($serialized),
            self::KEY_FORMAT => self::FORMAT_SERIALIZED,
        ];

        $this->fileStorageHandler->putJson($storageFile, $data);

        return $key;
    }

    public function remove(JobPayloadKeyInterface $processPayloadKey): void
    {
        if (!$processPayloadKey instanceof JobPayloadStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($processPayloadKey));
        }

        $storageFile = $this->getStoragePath();
        $data = $this->fileStorageHandler->getJson($storageFile);
        $keyData = $this->storageKeyGenerator->serialize($processPayloadKey);

        if (!\array_key_exists($keyData, $data)) {
            throw new NotFoundException();
        }

        unset($data[$keyData]);

        $this->fileStorageHandler->putJson($storageFile, $data);
    }

    public function list(): iterable
    {
        $storageFile = $this->getStoragePath();
        $data = $this->fileStorageHandler->getJson($storageFile);

        foreach ($data as $keyData => $ignored) {
            $key = $this->storageKeyGenerator->deserialize($keyData);

            if (!$key instanceof JobPayloadStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($key));
            }

            yield $key;
        }
    }

    public function has(JobPayloadKeyInterface $processPayloadKey): bool
    {
        if (!$processPayloadKey instanceof JobPayloadStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($processPayloadKey));
        }

        $storageFile = $this->getStoragePath();
        $data = $this->fileStorageHandler->getJson($storageFile);
        $keyData = $this->storageKeyGenerator->serialize($processPayloadKey);

        return \array_key_exists($keyData, $data);
    }

    public function get(JobPayloadKeyInterface $processPayloadKey): object
    {
        if (!$processPayloadKey instanceof JobPayloadStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($processPayloadKey));
        }

        $storageFile = $this->getStoragePath();
        $data = $this->fileStorageHandler->getJson($storageFile);
        $keyData = $this->storageKeyGenerator->serialize($processPayloadKey);

        if (!\array_key_exists($keyData, $data)) {
            throw new NotFoundException();
        }

        [
            self::KEY_PAYLOAD => $payload,
            self::KEY_FORMAT => $format,
        ] = $data[$keyData];

        if ($format === self::FORMAT_SERIALIZED) {
            $payload = \unserialize($format);
        }

        return (object) $payload;
    }

    private function getStoragePath(): string
    {
        return 'job_payloads.json';
    }
}
