<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\StorageKey;

use Heptacom\HeptaConnect\Storage\Base\Contract\JobKeyInterface;

class JobStorageKey extends AbstractStorageKey implements JobKeyInterface
{
    public const TYPE_KEY = 'job';

    public function __construct(string $id)
    {
        parent::__construct(self::TYPE_KEY, $id);
    }
}
