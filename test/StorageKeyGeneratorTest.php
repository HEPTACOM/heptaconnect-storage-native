<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\Test;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobRunKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingExceptionKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\RouteKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\WebhookKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobPayloadKeyInterface;
use Heptacom\HeptaConnect\Storage\Native\FileStorageHandler;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\Native\StorageKeyGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\Native\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\Native\StorageKey\CronjobStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\Native\StorageKey\CronjobRunStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\Native\StorageKey\JobPayloadStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\Native\StorageKey\MappingExceptionStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\Native\StorageKey\MappingNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\Native\StorageKey\MappingStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\Native\StorageKey\PortalNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\Native\StorageKey\RouteStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\Native\StorageKey\WebhookStorageKey
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

    /**
     * @dataProvider provideKeyInterfaces
     */
    public function testKeyGenerator(string $interface): void
    {
        $generator = new StorageKeyGenerator(new FileStorageHandler(self::WORKING_DIR));
        /** @var \Heptacom\HeptaConnect\Storage\Native\StorageKey\AbstractStorageKey $key */
        $key = $generator->generateKey($interface);
        self::assertInstanceOf($interface, $key);
    }

    /**
     * @dataProvider provideKeyInterfaces
     */
    public function testKeyIncrement(string $interface): void
    {
        $generator = new StorageKeyGenerator(new FileStorageHandler(self::WORKING_DIR));
        /** @var \Heptacom\HeptaConnect\Storage\Native\StorageKey\AbstractStorageKey $key1 */
        $key1 = $generator->generateKey($interface);
        /** @var \Heptacom\HeptaConnect\Storage\Native\StorageKey\AbstractStorageKey $key2 */
        $key2 = $generator->generateKey($interface);
        self::assertEquals(((int) $key1->getId()) + 1, (int) $key2->getId());
    }

    /**
     * @dataProvider provideKeyInterfaces
     */
    public function testKeySerialization(string $interface): void
    {
        $generator = new StorageKeyGenerator(new FileStorageHandler(self::WORKING_DIR));
        /** @var \Heptacom\HeptaConnect\Storage\Native\StorageKey\AbstractStorageKey $key */
        $key = $generator->generateKey($interface);
        $serialized = $generator->serialize($key);
        self::assertStringContainsString($key->getId(), $serialized);
        self::assertStringContainsString($key->getType(), $serialized);
    }

    /**
     * @dataProvider provideKeyInterfaces
     */
    public function testKeyDeserialization(string $interface): void
    {
        $generator = new StorageKeyGenerator(new FileStorageHandler(self::WORKING_DIR));
        /** @var \Heptacom\HeptaConnect\Storage\Native\StorageKey\AbstractStorageKey $key */
        $key = $generator->generateKey($interface);
        $serialized = $generator->serialize($key);
        $deserialized = $generator->deserialize($serialized);
        self::assertTrue($key->equals($deserialized), 'Keys are not equal');
    }

    /**
     * @dataProvider provideKeyInterfaces
     */
    public function testKeyJsonSerialization(string $interface): void
    {
        $generator = new StorageKeyGenerator(new FileStorageHandler(self::WORKING_DIR));
        /** @var \Heptacom\HeptaConnect\Storage\Native\StorageKey\AbstractStorageKey $key */
        $key = $generator->generateKey($interface);
        self::assertStringContainsString($key->getId(), \json_encode($key));
        self::assertStringContainsString($key->getType(), \json_encode($key));
    }

    public function provideKeyInterfaces(): iterable
    {
        yield [PortalNodeKeyInterface::class];
        yield [CronjobKeyInterface::class];
        yield [CronjobRunKeyInterface::class];
        yield [WebhookKeyInterface::class];
        yield [RouteKeyInterface::class];
        yield [MappingKeyInterface::class];
        yield [MappingNodeKeyInterface::class];
        yield [MappingExceptionKeyInterface::class];
        yield [JobPayloadKeyInterface::class];
    }
}
