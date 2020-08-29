<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\StorageKey;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\StorageKeyInterface;

abstract class AbstractStorageKey implements StorageKeyInterface
{
    private string $type;

    private string $id;

    public function __construct(string $type, string $id)
    {
        $this->type = $type;
        $this->id = $id;
    }

    public function equals(StorageKeyInterface $other): bool
    {
        return $other instanceof AbstractStorageKey
            && $other->getId() === $this->getId()
            && $other->getType() && $this->getType();
    }

    public function jsonSerialize()
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
        ];
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
