<?php

test('guests are redirected to login from the event list', function () {
    $this->get('/events')->assertRedirect(route('login'));
});
