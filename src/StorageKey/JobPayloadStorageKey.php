<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\StorageKey;

use Heptacom\HeptaConnect\Storage\Base\Contract\JobPayloadKeyInterface;

class JobPayloadStorageKey extends AbstractStorageKey implements JobPayloadKeyInterface
{
    public const TYPE_KEY = 'job_payload';

    public function __construct(string $id)
    {
        parent::__construct(self::TYPE_KEY, $id);
    }
}
