<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\Test;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Native\ConfigurationStorage;
use Heptacom\HeptaConnect\Storage\Native\FileStorageHandler;
use Heptacom\HeptaConnect\Storage\Native\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\Native\StorageKeyGenerator;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigurationStorageTest extends TestCase
{
    const STORAGE_DIR = __DIR__.'/../.build/test-storage';

    public function testSetConfiguration(): void
    {
        /** @var SystemConfigService&MockObject $systemConfigService */
        $systemConfigService = $this->createMock(SystemConfigService::class);
        $systemConfigService->expects(static::once())
            ->method('set')
            ->with(
                static::logicalAnd(
                    static::stringContains('2281f7b9f4e847d5b0bc084288b871b1'),
                    static::logicalNot(static::equalTo('2281f7b9f4e847d5b0bc084288b871b1'))
                ),
                static::logicalAnd(
                    static::isType(IsType::TYPE_ARRAY),
                    static::arrayHasKey('foo')
                )
            );

        $storage = new ConfigurationStorage(new FileStorageHandler(self::STORAGE_DIR));
        $storage->setConfiguration(new PortalNodeStorageKey('2281f7b9f4e847d5b0bc084288b871b1'), ['foo' => 'bar']);
    }

    public function testSetConfigurationNonArrayStored(): void
    {
        /** @var SystemConfigService&MockObject $systemConfigService */
        $systemConfigService = $this->createMock(SystemConfigService::class);
        $systemConfigService->expects(static::once())
            ->method('get')
            ->with(static::logicalAnd(
                static::stringContains('2281f7b9f4e847d5b0bc084288b871b1'),
                static::logicalNot(static::equalTo('2281f7b9f4e847d5b0bc084288b871b1'))
            ))
            ->willReturn('party');
        $systemConfigService->expects(static::once())
            ->method('set')
            ->with(
                static::logicalAnd(
                    static::stringContains('2281f7b9f4e847d5b0bc084288b871b1'),
                    static::logicalNot(static::equalTo('2281f7b9f4e847d5b0bc084288b871b1'))
                ),
                static::logicalAnd(
                    static::isType(IsType::TYPE_ARRAY),
                    static::arrayHasKey('foo'),
                    static::arrayHasKey('value')
                )
            );

        $storage = new ConfigurationStorage(new FileStorageHandler(self::STORAGE_DIR));
        $storage->setConfiguration(new PortalNodeStorageKey('2281f7b9f4e847d5b0bc084288b871b1'), ['foo' => 'bar']);
    }

    public function testGetConfiguration(): void
    {
        /** @var SystemConfigService&MockObject $systemConfigService */
        $systemConfigService = $this->createMock(SystemConfigService::class);
        $systemConfigService->expects(static::once())
            ->method('get')
            ->with(
                static::logicalAnd(
                    static::stringContains('2281f7b9f4e847d5b0bc084288b871b1'),
                    static::logicalNot(static::equalTo('2281f7b9f4e847d5b0bc084288b871b1'))
                )
            )
            ->willReturn(['foo' => 'bar']);

        $storage = new ConfigurationStorage(new FileStorageHandler(self::STORAGE_DIR));
        $result = $storage->getConfiguration(new PortalNodeStorageKey('2281f7b9f4e847d5b0bc084288b871b1'));
        static::assertEquals(['foo' => 'bar'], $result);
    }

    public function testGetConfigurationNonArray(): void
    {
        /** @var SystemConfigService&MockObject $systemConfigService */
        $systemConfigService = $this->createMock(SystemConfigService::class);
        $systemConfigService->expects(static::once())
            ->method('get')
            ->with(
                static::logicalAnd(
                    static::stringContains('2281f7b9f4e847d5b0bc084288b871b1'),
                    static::logicalNot(static::equalTo('2281f7b9f4e847d5b0bc084288b871b1'))
                )
            )
            ->willReturn('foobar');

        $storage = new ConfigurationStorage(new FileStorageHandler(self::STORAGE_DIR));
        $result = $storage->getConfiguration(new PortalNodeStorageKey('2281f7b9f4e847d5b0bc084288b871b1'));
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
