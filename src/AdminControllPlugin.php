<?php

namespace Txmmy01\AdminControll;

use Filament\Panel;
use Filament\Contracts\Plugin;

class AdminControllPlugin implements Plugin
{
    public function getId(): string
    {
        return 'admin-controll';
    }

    public function register(Panel $panel): void
    {
        if ($panel->getId() !== 'admin') {
            return;
        }

        $panel->discoverPages(
            plugin_path($this->getId(), 'src/Filament/Admin/Pages'),
            'Txmmy01\\AdminControll\\Filament\\Admin\\Pages'
        );
    }

    public function boot(Panel $panel): void
    {
    }
}