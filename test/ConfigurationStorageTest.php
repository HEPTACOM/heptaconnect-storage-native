<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\Test;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Native\ConfigurationStorage;
use Heptacom\HeptaConnect\Storage\Native\FileStorageHandler;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\Native\StorageKeyGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\Native\ConfigurationStorage
 * @covers \Heptacom\HeptaConnect\Storage\Native\FileStorageHandler
 * @covers \Heptacom\HeptaConnect\Storage\Native\StorageKey\PortalNodeStorageKey
 */
class ConfigurationStorageTest extends TestCase
{
    const STORAGE_DIR = __DIR__.'/../.build/test-storage';

    public function testSetConfiguration(): void
    {
        /** @var FileStorageHandler&MockObject $fileStorageHandler */
        $fileStorageHandler = $this->createMock(FileStorageHandler::class);
        $fileStorageHandler->expects(static::once())
            ->method('put')
            ->with(
                static::logicalAnd(
                    static::stringContains('13'),
                    static::logicalNot(static::equalTo('13'))
                ),
                static::logicalAnd(
                    static::stringContains('foo'),
                    static::stringContains('bar')
                )
            );

        $storage = new ConfigurationStorage($fileStorageHandler);
        $storage->setConfiguration(new PortalNodeStorageKey('13'), ['foo' => 'bar']);
    }

    public function testSetConfigurationNonArrayStored(): void
    {
        /** @var FileStorageHandler&MockObject $fileStorageHandler */
        $fileStorageHandler = $this->createMock(FileStorageHandler::class);
        $fileStorageHandler->expects(static::once())
            ->method('put')
            ->with(
                static::logicalAnd(
                    static::stringContains('13'),
                    static::logicalNot(static::equalTo('13'))
                ),
                static::stringContains('foo')
            );

        $storage = new ConfigurationStorage($fileStorageHandler);
        $storage->setConfiguration(new PortalNodeStorageKey('13'), ['foo' => 'bar']);
    }

    public function testGetConfiguration(): void
    {
        /** @var FileStorageHandler&MockObject $fileStorageHandler */
        $fileStorageHandler = $this->createMock(FileStorageHandler::class);
        $fileStorageHandler->expects(static::once())
            ->method('has')
            ->with(
                static::logicalAnd(
                    static::stringContains('13'),
                    static::logicalNot(static::equalTo('13'))
                )
            )
            ->willReturn(true);
        $fileStorageHandler->expects(static::once())
            ->method('get')
            ->with(
                static::logicalAnd(
                    static::stringContains('13'),
                    static::logicalNot(static::equalTo('13'))
                )
            )
            ->willReturn('{"foo": "bar"}');

        $storage = new ConfigurationStorage($fileStorageHandler);
        $result = $storage->getConfiguration(new PortalNodeStorageKey('13'));
        static::assertEquals(['foo' => 'bar'], $result);
    }

    public function testGetConfigurationNonArray(): void
    {
        /** @var FileStorageHandler&MockObject $fileStorageHandler */
        $fileStorageHandler = $this->createMock(FileStorageHandler::class);
        $fileStorageHandler->expects(static::once())
            ->method('get')
            ->with(
                static::logicalAnd(
                    static::stringContains('13'),
                    static::logicalNot(static::equalTo('13'))
                )
            )
            ->willReturn('{"value": "foobar"}');
        $fileStorageHandler->expects(static::once())
            ->method('has')
            ->with(
                static::logicalAnd(
                    static::stringContains('13'),
                    static::logicalNot(static::equalTo('13'))
                )
            )
            ->willReturn(true);

        $storage = new ConfigurationStorage($fileStorageHandler);
        $result = $storage->getConfiguration(new PortalNodeStorageKey('13'));
        static::assertEquals(['value' => 'foobar'], $result);
    }

    public function testResetStorage(): void
    {
        $storage = new ConfigurationStorage(new FileStorageHandler(self::STORAGE_DIR));
        $keyGenerator = new StorageKeyGenerator(new FileStorageHandler(self::STORAGE_DIR));

        /** @var PortalNodeKeyInterface $portalNodeKey */
        $portalNodeKey = $keyGenerator->generateKey(PortalNodeKeyInterface::class);
        $storage->setConfiguration($portalNodeKey, ['test' => true]);
        $value = $storage->getConfiguration($portalNodeKey);
        static::assertArrayHasKey('test', $value);
        static::assertEquals(true, $value['test']);
        $storage->setConfiguration($portalNodeKey, null);
        static::assertCount(0, $storage->getConfiguration($portalNodeKey));
    }
}
