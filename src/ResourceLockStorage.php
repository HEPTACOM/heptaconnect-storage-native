<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native;

use Heptacom\HeptaConnect\Portal\Base\Parallelization\Exception\ResourceIsLockedException;
use Heptacom\HeptaConnect\Storage\Base\Contract\ResourceLockStorageContract;

class ResourceLockStorage extends ResourceLockStorageContract
{
    private FileStorageHandler $fileStorageHandler;

    private string $lockfile;

    public function __construct(FileStorageHandler $fileStorageHandler, string $lockfile)
    {
        $this->fileStorageHandler = $fileStorageHandler;
        $this->lockfile = $lockfile;
    }

    public function create(string $key): void
    {
        $file = \md5($key);

        $this->doLocked(function () use ($key, $file) {
            if ($this->fileStorageHandler->has($file)) {
                throw new ResourceIsLockedException($key, null);
            }

            $this->fileStorageHandler->put($file, '');
        });
    }

    public function has(string $key): bool
    {
        $file = \md5($key);

        return $this->doLocked(fn() => $this->fileStorageHandler->has($file));
    }

    public function delete(string $key): void
    {
        $file = \md5($key);

        $this->doLocked(function () use ($file) {
            if ($this->fileStorageHandler->has($file)) {
                $this->fileStorageHandler->remove($file);
            }
        });
    }

    private function doLocked(callable $callable)
    {
        try {
            $fp = \fopen($this->lockfile, 'wb');

            while (!\flock($fp, LOCK_EX)) {
                sleep(1);
            }

            return $callable();
        } finally {
            if (\is_resource($fp)) {
                \flock($fp, LOCK_UN);
                \fclose($fp);
            }
        }
    }
}
