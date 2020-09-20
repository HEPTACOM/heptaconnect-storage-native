<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\Repository;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\WebhookKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Webhook\Contract\WebhookInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\WebhookRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Native\FileStorageRepository;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\WebhookStorageKey;

class WebhookRepository extends WebhookRepositoryContract
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

    public function create(
        PortalNodeKeyInterface $portalNodeKey,
        string $url,
        string $handler,
        ?array $payload = null
    ): WebhookKeyInterface {
        $key = $this->storageKeyGenerator->generateKey(WebhookKeyInterface::class);

        if (!$key instanceof WebhookStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $this->repository->put($key, [
            'url' => $url,
            'handler' => $handler,
            'payload' => $payload,
            'portalNodeKey' => $portalNodeKey,
        ]);

        return $key;
    }

    public function read(WebhookKeyInterface $key): WebhookInterface
    {
        $item = $this->repository->get($key);
        $portalNodeKey = $item['portalNodeKey'];

        if (!$portalNodeKey instanceof PortalNodeKeyInterface) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        return new class (
            $portalNodeKey,
            $key,
            (string) $item['key'],
            (string) $item['handler'],
            $item['payload'] ? (array) $item['payload'] : null
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
        foreach ($this->repository->list() as $webhook) {
            if ($webhook['url'] === $url) {
                yield new $webhook['id'];
            }
        }
    }
}
