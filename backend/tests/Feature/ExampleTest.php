<?php

it('renders the panel login page', function () {
    $response = $this->get('/admin/login');

    $response->assertStatus(200);
});
