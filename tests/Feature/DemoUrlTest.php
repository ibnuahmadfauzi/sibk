<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class DemoUrlTest extends TestCase
{
    public function test_demo_links_use_configured_public_origin_instead_of_internal_proxy_host(): void
    {
        config()->set('sibk.demo.enabled', true);
        config()->set('app.url', 'https://demo.example.test');
        URL::setRequest(Request::create('http://10.200.5.86:3000/login'));
        (new AppServiceProvider($this->app))->boot();

        $this->assertSame('https://demo.example.test/build/demo.css', asset('build/demo.css'));
        $this->assertSame('https://demo.example.test/login', route('login'));
    }

    public function test_normal_application_keeps_request_origin(): void
    {
        config()->set('sibk.demo.enabled', false);
        URL::setRequest(Request::create('https://school.example.test/login'));
        (new AppServiceProvider($this->app))->boot();

        $this->assertSame('https://school.example.test/login', route('login'));
    }
}
