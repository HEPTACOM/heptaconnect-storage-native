<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\Test;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Native\FileStorageHandler;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\Native\StorageKeyGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\Native\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\Native\StorageKeyGenerator
 */
class StorageKeyGeneratorTest extends TestCase
{
    const WORKING_DIR = __DIR__.'/../.build/test-storage';

    public function testUnsupportedClassException(): void
    {
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Unsupported storage key class: '.AbstractStorageKey::class);

        $generator = new StorageKeyGenerator($this->createMock(FileStorageHandler::class));
        $generator->generateKey(AbstractStorageKey::class);
    }

    public function testPortalNodeKey(): void
    {
        $generator = new StorageKeyGenerator(new FileStorageHandler(self::WORKING_DIR));
        /** @var \Heptacom\HeptaConnect\Storage\Native\StorageKey\AbstractStorageKey $key */
        $key = $generator->generateKey(PortalNodeKeyInterface::class);
        self::assertInstanceOf(PortalNodeStorageKey::class, $key);
        self::assertStringContainsString($key->getId(), \json_encode($key));
        self::assertStringContainsString($key->getType(), \json_encode($key));
    }

    public function testKeyIncrement(): void
    {
        $generator = new StorageKeyGenerator(new FileStorageHandler(self::WORKING_DIR));
        /** @var \Heptacom\HeptaConnect\Storage\Native\StorageKey\AbstractStorageKey $key1 */
        /** @var \Heptacom\HeptaConnect\Storage\Native\StorageKey\AbstractStorageKey $key2 */
        $key1 = $generator->generateKey(PortalNodeKeyInterface::class);
        $key2 = $generator->generateKey(PortalNodeKeyInterface::class);
        self::assertEquals(((int) $key1->getId()) + 1, (int) $key2->getId());
    }
}
