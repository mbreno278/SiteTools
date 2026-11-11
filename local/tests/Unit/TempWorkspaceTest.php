<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Process\TempWorkspace;
use Tests\Support\SimpleTestCase;

final class TempWorkspaceTest extends SimpleTestCase
{
    public function run(): void
    {
        $this->testCreatesAndCleansWorkspace();
        $this->testJoinDiscardsPathTraversal();
    }

    private function testCreatesAndCleansWorkspace(): void
    {
        $workspace = new TempWorkspace();
        $directory = $workspace->path();
        $file = $workspace->join('sample.txt');

        file_put_contents($file, 'test');

        $this->assertTrue(is_dir($directory));
        $this->assertTrue(is_file($file));

        $workspace->cleanup();
        $this->assertFalse(is_dir($directory));
    }

    private function testJoinDiscardsPathTraversal(): void
    {
        $workspace = new TempWorkspace();
        try {
            $path = $workspace->join('../outside.txt');
            $this->assertSame('outside.txt', basename($path));
            $this->assertSame($workspace->path(), dirname($path));
        } finally {
            $workspace->cleanup();
        }
    }
}
