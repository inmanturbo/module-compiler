<?php

use function Pest\Laravel\artisan;

test('it will build the files', function (): void {
    artisan('app:build');

    // TODO: assert files were built mocking paths somehow or using a temp directory
});
