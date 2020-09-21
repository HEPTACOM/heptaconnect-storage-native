<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\Native;

class FileStorageHandler
{
    private string $baseDir;

    private string $separator;

    public function __construct(string $baseDir, string $separator = \DIRECTORY_SEPARATOR)
    {
        $this->separator = $separator;
        $this->baseDir = \rtrim($baseDir, $this->separator);
    }

    public function put(string $filePath, string $content): void
    {
        $fullPath = $this->mergePath($filePath);
        $directory = \dirname($fullPath);

        if (!@\is_dir($directory)) {
            @\mkdir($directory, 0777, true);
        }

        \file_put_contents($fullPath, $content);
    }

    public function has(string $filePath): bool
    {
        return \is_file($this->mergePath($filePath));
    }

    public function get(string $filePath): ?string
    {
        if (!\is_file($this->mergePath($filePath))) {
            return null;
        }

        return \file_get_contents($this->mergePath($filePath)) ?: null;
    }

    public function remove(string $filePath): void
    {
        $fullPath = $this->mergePath($filePath);

        if (@\is_file($fullPath)) {
            @\unlink($fullPath);
        }
    }

    public function putJson(string $filePath, ?array $content): void
    {
        if (\is_null($content) || empty($content)) {
            $this->remove($filePath);
        } else {
            $this->put($filePath, \json_encode($content, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));
        }
    }

    public function getJson(string $filePath, string $defaultJson = '[]'): ?array
    {
        $content = $this->get($filePath);

        return (array) \json_decode($content ?? $defaultJson, true);
    }

    private function mergePath($filePath): string
    {
        return $this->baseDir.$this->separator.\ltrim($filePath, $this->separator);
    }
}
