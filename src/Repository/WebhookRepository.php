<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\Repository;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\WebhookKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Webhook\Contract\WebhookInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\WebhookRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Native\FileStorageHandler;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\WebhookStorageKey;

class WebhookRepository extends WebhookRepositoryContract
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
        string $url,
        string $handler,
        ?array $payload = null
    ): WebhookKeyInterface {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $key = $this->storageKeyGenerator->generateKey(WebhookKeyInterface::class);

        if (!$key instanceof WebhookStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $data = $this->fileStorageHandler->getJson($this->getStoragePath());
        $data[$this->storageKeyGenerator->serialize($key)] = [
            'url' => $url,
            'handler' => $handler,
            'payload' => $payload,
            'portalNodeKey' => $this->storageKeyGenerator->serialize($portalNodeKey),
        ];
        $this->fileStorageHandler->putJson($this->getStoragePath(), $data);

        return $key;
    }

    public function read(WebhookKeyInterface $key): WebhookInterface
    {
        if (!$key instanceof WebhookStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $data = $this->fileStorageHandler->getJson($this->getStoragePath());
        $keyData = $this->storageKeyGenerator->serialize($key);

        if (!\array_key_exists($keyData, $data)) {
            throw new NotFoundException();
        }

        $portalNodeKey = $this->storageKeyGenerator->deserialize($data[$keyData]['portalNodeKey']);

        if (!$portalNodeKey instanceof PortalNodeKeyInterface) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        return new class (
            $portalNodeKey,
            $key,
            (string) $data[$keyData]['key'],
            (string) $data[$keyData]['handler'],
            $data[$keyData]['payload'] ? (array) $data[$keyData]['payload'] : null
        ) implements WebhookInterface {
            private PortalNodeKeyInterface $portalNodeKey;

            private WebhookKeyInterface $key;

            private string $url;

            private string $handler;

            private ?array $payload;

            public function __construct(
                PortalNodeKeyInterface $portalNodeKey,
                WebhookKeyInterface $key,
                string $url,
                string $handler,
                ?array $payload
            ) {
                $this->portalNodeKey = $portalNodeKey;
                $this->key = $key;
                $this->url = $url;
                $this->handler = $handler;
                $this->payload = $payload;
            }

            public function getPortalNodeKey(): PortalNodeKeyInterface
            {
                return $this->portalNodeKey;
            }

            public function getKey(): WebhookKeyInterface
            {
                return $this->key;
            }

            public function getUrl(): string
            {
                return $this->url;
            }

            public function getHandler(): string
            {
                return $this->handler;
            }

            public function getPayload(): ?array
            {
                return $this->payload;
            }
        };
    }

    public function listByUrl(string $url): iterable
    {
        $data = $this->fileStorageHandler->getJson($this->getStoragePath());

        foreach ($data as $webhookKey => $webhook) {
            if ($webhook['url'] === $url) {
                yield new $this->storageKeyGenerator->deserialize($webhookKey);
            }
        }
    }

    private function getStoragePath(): string
    {
        return 'webhooks.json';
    }
}
