<?php

it('returns the application welcome page', function (): void {
    $this->get('/')->assertOk();
});
