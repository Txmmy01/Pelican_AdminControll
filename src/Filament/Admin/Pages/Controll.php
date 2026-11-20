<?php

namespace Txmmy01\AdminControll\Filament\Admin\Pages;

use App\Enums\NodeJwtScope;
use App\Jobs\SendServerPowerAction;
use App\Models\Server;
use App\Services\Nodes\NodeJWTService;
use App\Services\Servers\GetUserPermissionsService;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Controll extends Page
{
    protected static ?string $slug = 'controll';

    protected string $view = 'admin-controll::dashboard';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-command-line';

    protected static ?int $navigationSort = 90;

    public array $selectedServers = [];

    public function mount(): void
    {
        abort_unless(user()?->isRootAdmin(), 403);
    }

    public static function canAccess(): bool
    {
        return (bool) user()?->isRootAdmin();
    }

    public static function getNavigationLabel(): string
    {
        return 'ADMIN Controll';
    }

    public function getTitle(): string
    {
        return 'ADMIN Controll';
    }

    public function getServers(): Collection
    {
        return Server::query()
            ->with('node')
            ->orderBy('name')
            ->get();
    }

    public function selectAll(): void
    {
        $this->selectedServers = $this->getServers()->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    public function clearSelection(): void
    {
        $this->selectedServers = [];
    }

    public function sendPowerAction(string $action): void
    {
        $this->authorizeAdmin();

        abort_unless(in_array($action, ['start', 'stop', 'restart'], true), 422);

        $servers = Server::query()
            ->whereIn('id', array_map('intval', $this->selectedServers))
            ->get();

        abort_if($servers->isEmpty(), 422, 'Bitte wähle mindestens einen Server aus.');

        foreach ($servers as $server) {
            SendServerPowerAction::dispatch($server, $action);
        }
    }

    public function getConnections(): array
    {
        $this->authorizeAdmin();

        $permissions = app(GetUserPermissionsService::class);
        $jwt = app(NodeJWTService::class);

        return $this->getServers()->map(function (Server $server) use ($permissions, $jwt) {
            return $this->makeConnection($server, $permissions, $jwt);
        })->all();
    }

    public function getConnectionFor(int $serverId): array
    {
        $this->authorizeAdmin();

        $server = Server::query()->with('node')->findOrFail($serverId);

        return $this->makeConnection(
            $server,
            app(GetUserPermissionsService::class),
            app(NodeJWTService::class)
        );
    }

    private function makeConnection(Server $server, GetUserPermissionsService $permissionsService, NodeJWTService $jwtService): array
    {
        $admin = user();
        $permissions = $permissionsService->handle($server, $admin);

        $token = $jwtService
            ->setExpiresAt(now()->addMinutes(10)->toImmutable())
            ->setScopes(NodeJwtScope::Websocket)
            ->setUser($admin)
            ->setClaims([
                'server_uuid' => $server->uuid,
                'permissions' => $permissions,
            ])
            ->handle($server->node, $admin->id . $server->uuid)
            ->toString();

        $socket = str_replace(['https://', 'http://'], ['wss://', 'ws://'], $server->node->getConnectionAddress());
        $socket .= sprintf('/api/servers/%s/ws', $server->uuid);

        return [
            'id' => $server->id,
            'uuid' => $server->uuid,
            'name' => $server->name,
            'socket' => $socket,
            'token' => $token,
        ];
    }

    private function authorizeAdmin(): void
    {
        if (!user()?->isRootAdmin()) {
            throw new AccessDeniedHttpException();
        }
    }
}