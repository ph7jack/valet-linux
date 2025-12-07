<?php

namespace Valet\PackageManagers;

use DomainException;
use Valet\CommandLine;
use Valet\Contracts\PackageManager;

class Paru implements PackageManager
{
    public $cli;

    const SUPPORTED_PHP_VERSIONS = [
        'php',
        'php84',
        'php83',
        'php82',
        'php81',
        'php80',
        'php74',
    ];


    /**
     * Create a new Paru instance.
     *
     * @param CommandLine $cli
     * @return void
     */
    public function __construct(CommandLine $cli)
    {
        $this->cli = $cli;
    }

    /**
     * Get array of installed packages
     *
     * @param string $package
     * @return array
     */
    public function packages($package)
    {
        $query = "paru -Qq {$package} 2>/dev/null || true";

        return explode(PHP_EOL, $this->cli->run($query));
    }

    /**
     * Determine if the given package is installed.
     *
     * @param string $package
     * @return bool
     */
    public function installed($package)
    {
        return in_array($package, $this->packages($package));
    }

    /**
     * Ensure that the given package is installed.
     *
     * @param string $package
     * @return void
     */
    public function ensureInstalled($package)
    {
        if (!$this->installed($package)) {
            $this->installOrFail($package);
        }
    }

    /**
     * Install the given package and throw an exception on failure.
     *
     * @param string $package
     * @return void
     */
    public function installOrFail($package)
    {
        output('<info>[' . $package . '] is not installed, installing it now via Paru...</info> 🍻');

        $this->cli->run(trim('paru --noconfirm --needed -S ' . $package), function ($exitCode, $errorOutput) use ($package) {
            output($errorOutput);

            throw new DomainException('Paru was unable to install [' . $package . '].');
        });
    }

    /**
     * Configure package manager on valet install.
     *
     * @return void
     */
    public function setup()
    {
        // Nothing to do
    }

    /**
     * Restart dnsmasq in Arch.
     *
     * @param \Valet\Contracts\ServiceManager $sm
     * @return void
     */
    public function nmRestart($sm)
    {
        $sm->restart('NetworkManager');
    }

    /**
     * Determine if package manager is available on the system.
     *
     * @return bool
     */
    public function isAvailable()
    {
        try {
            $output = $this->cli->run('which paru', function ($exitCode, $output) {
                throw new DomainException('Paru not available');
            });

            return $output != '';
        } catch (DomainException $e) {
            return false;
        }
    }

    public function supportedPhpVersions()
    {
        return collect(static::SUPPORTED_PHP_VERSIONS);
    }
}
