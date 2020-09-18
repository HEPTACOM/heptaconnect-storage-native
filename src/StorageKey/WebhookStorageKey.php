<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\StorageKey;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\WebhookKeyInterface;

class WebhookStorageKey extends AbstractStorageKey implements WebhookKeyInterface
{
    public const TYPE_KEY = 'webhook';

    public function __construct(string $id)
    {
        parent::__construct(self::TYPE_KEY, $id);
    }
}
