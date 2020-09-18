<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\StorageKey;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\RouteKeyInterface;

class RouteStorageKey extends AbstractStorageKey implements RouteKeyInterface
{
    public const TYPE_KEY = 'route';

    public function __construct(string $id)
    {
        parent::__construct(self::TYPE_KEY, $id);
    }
}
