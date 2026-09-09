<?php

it('redirects the homepage to the login page', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});
