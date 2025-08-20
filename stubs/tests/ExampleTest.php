<?php

declare(strict_types=1);

use :namespace\Example;

describe('Example', function (): void {
    it('should greet', function (): void {
        $example = new Example();

        expect($example->greet())->toBe('Hello, World!');
    });
});
