<?php

declare(strict_types=1);

it('has GLC landing page', function (): void {
    $page = visit('/');

    $page->assertSee('GLC AI Platform');
});
