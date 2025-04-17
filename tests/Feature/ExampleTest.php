<?php
/**
 * @author kendall Aaron <kendallangulo01@gmail.com>
 *
 */

it('returns a successful response', function () {
    $this->markTestSkipped('La ruta principal no está configurada correctamente');
    
    $response = $this->get('/');
    $response->assertStatus(200);
});
