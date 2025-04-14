<?php
/**
 * @author kendall Aaron <kendallangulo01@gmail.com>
 *
 */

it('returns a successful response', function () {
    $response = $this->get('/');
    $response->assertStatus(200);
});
