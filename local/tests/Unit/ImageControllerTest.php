<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\ImageController;
use InvalidArgumentException;
use Tests\Support\SimpleTestCase;

final class ImageControllerTest extends SimpleTestCase
{
    public function run(): void
    {
        $this->testRejectsUnsupportedAction();
    }

    private function testRejectsUnsupportedAction(): void
    {
        $controller = new ImageController();

        try {
            $controller->process('delete', []);
            $this->assertTrue(false, 'Ação não suportada deveria lançar InvalidArgumentException.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('não suportada', $exception->getMessage());
        }
    }
}
