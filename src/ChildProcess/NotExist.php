<?php

namespace React\Filesystem\ChildProcess;

use React\EventLoop\Loop;
use React\Filesystem\AdapterInterface;
use React\Filesystem\Node;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;
use function WyriHaximus\React\childProcessPromiseClosure;

/**
 * @internal
 */
final class NotExist implements Node\NotExistInterface
{
    use StatTrait;

    private AdapterInterface $filesystem;
    private string $path;
    private string $name;

    public function __construct(AdapterInterface $filesystem, string $path, string $name)
    {
        $this->filesystem = $filesystem;
        $this->path = $path;
        $this->name = $name;
    }

    public function stat(): PromiseInterface
    {
        return $this->internalStat($this->path . $this->name);
    }

    public function createDirectory(): PromiseInterface
    {
        $path = $this->path . $this->name;
        return childProcessPromiseClosure(Loop::get(), function () use ($path): array {
            return ['mkdir' => mkdir($path,0777, true)];
        })->then(fn (array $data): Node\DirectoryInterface => new Directory($this->filesystem, $this->path, $this->name));
    }

    public function createFile(): PromiseInterface
    {
        $file = new File($this->path, $this->name);

        return $this->filesystem->detect($this->path)->then(function (Node\NodeInterface $node): PromiseInterface {
            if ($node instanceof Node\NotExistInterface) {
                return $node->createDirectory();
            }

            return resolve($node);
        })->then(function () use ($file): PromiseInterface {
            return $file->putContents('');
        })->then(function () use ($file): Node\FileInterface {
            return $file;
        });
    }

    public function unlink(): PromiseInterface
    {
        // Essentially a No-OP since it doesn't exist anyway
        return resolve(true);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function name(): string
    {
        return $this->name;
    }
}
