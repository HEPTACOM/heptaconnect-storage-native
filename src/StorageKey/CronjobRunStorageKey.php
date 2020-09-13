<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\StorageKey;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobRunKeyInterface;

class CronjobRunStorageKey extends AbstractStorageKey implements CronjobRunKeyInterface
{
    public const TYPE_KEY = 'cronjob_run';

    public function __construct(string $id)
    {
        parent::__construct(self::TYPE_KEY, $id);
    }
}
