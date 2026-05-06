<?php

namespace SchenkeIo\LaravelAuthRouter\Tests\Feature\Auth;

use Illuminate\Support\Facades\Route;
use SchenkeIo\LaravelAuthRouter\Auth\SessionKey;
use SchenkeIo\LaravelAuthRouter\Data\UserData;
use SchenkeIo\LaravelAuthRouter\Tests\TestCase;

class PayloadRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Route::get('home', fn () => 'home')->name('home');
        Route::get('error', fn () => 'error')->name('error');
    }

    public function test_payload_route_redirects_to_error_when_show_payload_is_false()
    {
        Route::authRouter(['google'])->success('home')->error('error')->showPayload(false);

        $response = $this->get(route('callback.payload'));

        $response->assertRedirect(route('error'));
    }

    public function test_payload_route_redirects_to_error_when_no_session_data()
    {
        Route::authRouter(['google'])->success('home')->error('error')->showPayload(true);

        $response = $this->get(route('callback.payload'));

        $response->assertRedirect(route('error'));
    }

    public function test_payload_route_shows_view_when_data_in_session()
    {
        Route::authRouter(['google'])->success('home')->error('error')->showPayload(true);

        $userData = new UserData('Test User', 'test@example.com', 'https://example.com/avatar.jpg');
        session([SessionKey::PAYLOAD => $userData]);

        $response = $this->get(route('callback.payload'));

        $response->assertStatus(200);
        $response->assertViewIs('auth-router::callback-payload');
        $response->assertViewHas('userData', $userData);
    }

    public function test_finalize_route_redirects_to_error_when_show_payload_is_false()
    {
        Route::authRouter(['google'])->success('home')->error('error')->showPayload(false);

        $response = $this->post(route('callback.finalize'));

        $response->assertRedirect(route('error'));
    }

    public function test_finalize_route_redirects_to_error_when_no_session_data()
    {
        Route::authRouter(['google'])->success('home')->error('error')->showPayload(true);

        $response = $this->post(route('callback.finalize'));

        $response->assertRedirect(route('error'));
    }

    public function test_finalize_route_works_with_fake_code_even_if_show_payload_is_false()
    {
        Route::authRouter(['google'])->success('home')->error('error')->showPayload(false);

        $userData = new UserData('Test User', 'test@example.com', 'https://example.com/avatar.jpg');
        session([SessionKey::PAYLOAD => $userData]);

        $response = $this->post(route('callback.finalize', ['code' => 'fake_code']));

        $response->assertRedirect(route('home'));
        $this->assertNull(session(SessionKey::PAYLOAD));
    }
}
