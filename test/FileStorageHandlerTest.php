<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native\Test;

use Heptacom\HeptaConnect\Storage\Native\FileStorageHandler;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\Native\FileStorageHandler
 */
class FileStorageHandlerTest extends TestCase
{
    const FILE_NAME = 'testfile.txt';

    const WORKING_DIR = __DIR__.'/../.build/test-storage';

    const WORKING_FILE = self::WORKING_DIR.'/'.self::FILE_NAME;

    const CONTENT = 'Super special content';

    const JSON_CONTENT = '{"name": "Super special content"}';

    const OBJECT_CONTENT = ['name' => 'Super special content'];

    protected function tearDown(): void
    {
        parent::tearDown();

        if (@\is_file(self::WORKING_FILE)) {
            @\unlink(self::WORKING_FILE);
        }

        if (@\is_dir(self::WORKING_DIR)) {
            @\unlink(self::WORKING_DIR);
        }
    }

    public function testSet(): void
    {
        $this->getStorage()->put(self::FILE_NAME, self::CONTENT);
        self::assertFileExists(self::WORKING_FILE);
        self::assertEquals(self::CONTENT, \file_get_contents(self::WORKING_FILE));
    }

    public function testHas(): void
    {
        if (!@\is_dir(self::WORKING_DIR)) {
            @\mkdir(self::WORKING_DIR, 0777, true);
        }

        self::assertFileDoesNotExist(self::WORKING_FILE);
        self::assertFalse($this->getStorage()->has(self::FILE_NAME));
        @\touch(self::WORKING_FILE);
        self::assertFileExists(self::WORKING_FILE);
        self::assertTrue($this->getStorage()->has(self::FILE_NAME));
    }

    public function testRemove(): void
    {
        if (!@\is_dir(self::WORKING_DIR)) {
            @\mkdir(self::WORKING_DIR, 0777, true);
        }

        @\touch(self::WORKING_FILE);
        self::assertFileExists(self::WORKING_FILE);
        self::assertTrue($this->getStorage()->has(self::FILE_NAME));
        $this->getStorage()->remove(self::FILE_NAME);
        self::assertFileDoesNotExist(self::WORKING_FILE);
        self::assertFalse($this->getStorage()->has(self::FILE_NAME));
    }

    public function testGet(): void
    {
        \file_put_contents(self::WORKING_FILE, self::CONTENT);
        self::assertFileExists(self::WORKING_FILE);
        self::assertTrue($this->getStorage()->has(self::FILE_NAME));
        self::assertEquals(self::CONTENT, $this->getStorage()->get(self::FILE_NAME));
    }

    public function testSetJson(): void
    {
        $this->getStorage()->putJson(self::FILE_NAME, self::OBJECT_CONTENT);
        self::assertFileExists(self::WORKING_FILE);
        self::assertEquals(self::JSON_CONTENT, \file_get_contents(self::WORKING_FILE));
    }

    public function testRemoveOnEmptyJson(): void
    {
        if (!@\is_dir(self::WORKING_DIR)) {
            @\mkdir(self::WORKING_DIR, 0777, true);
        }

        @\touch(self::WORKING_FILE);
        self::assertFileExists(self::WORKING_FILE);
        self::assertTrue($this->getStorage()->has(self::FILE_NAME));
        $this->getStorage()->putJson(self::FILE_NAME, null);
        self::assertFileDoesNotExist(self::WORKING_FILE);
        self::assertFalse($this->getStorage()->has(self::FILE_NAME));
    }

    public function testGetJson(): void
    {
        \file_put_contents(self::WORKING_FILE, self::JSON_CONTENT);
        self::assertFileExists(self::WORKING_FILE);
        self::assertTrue($this->getStorage()->has(self::FILE_NAME));
        self::assertEquals(self::OBJECT_CONTENT, $this->getStorage()->getJson(self::FILE_NAME));
    }

    private function getStorage(): FileStorageHandler
    {
        return new FileStorageHandler(self::WORKING_DIR);
    }
}
