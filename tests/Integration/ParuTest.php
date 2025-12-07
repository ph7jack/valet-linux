<?php

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use Valet\CommandLine;
use Valet\PackageManagers\Paru;

class ParuTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER['SUDO_USER'] = user();

        Container::setInstance(new Container);
    }


    protected function tearDown(): void
    {
        Mockery::close();
    }


    public function test_paru_can_be_resolved_from_container()
    {
        $this->assertInstanceOf(Paru::class, resolve(Paru::class));
    }


    public function test_installed_returns_true_when_given_formula_is_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()
            ->with("paru -Qq php84-fpm 2>/dev/null || true")
            ->andReturn('php84-fpm');
        swap(CommandLine::class, $cli);
        $this->assertTrue(resolve(Paru::class)->installed('php84-fpm'));
    }


    public function test_installed_returns_false_when_given_formula_is_not_installed()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()
            ->with("paru -Qq php84-fpm 2>/dev/null || true")
            ->andReturn('');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(Paru::class)->installed('php84-fpm'));

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()
            ->with("paru -Qq php84-fpm 2>/dev/null || true")
            ->andReturn('php83-fpm');
        swap(CommandLine::class, $cli);
        $this->assertFalse(resolve(Paru::class)->installed('php84-fpm'));
    }


    public function test_install_or_fail_will_install_packages()
    {
        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->once()->with('paru --noconfirm --needed -S dnsmasq', Mockery::type('Closure'));
        swap(CommandLine::class, $cli);
        resolve(Paru::class)->installOrFail('dnsmasq');
    }


    public function test_install_or_fail_throws_exception_on_failure()
    {
        $this->expectException(DomainException::class);

        $cli = Mockery::mock(CommandLine::class);
        $cli->shouldReceive('run')->andReturnUsing(function ($command, $onError) {
            $onError(1, 'test error output');
        });
        swap(CommandLine::class, $cli);
        resolve(Paru::class)->installOrFail('dnsmasq');
    }

    public function test_supported_php_versions_returns_collection()
    {
        $versions = resolve(Paru::class)->supportedPhpVersions();
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $versions);
        $this->assertTrue($versions->contains('php'));
        $this->assertTrue($versions->contains('php84'));
        $this->assertTrue($versions->contains('php83'));
    }
}
