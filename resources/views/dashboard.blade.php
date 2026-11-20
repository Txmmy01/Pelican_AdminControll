<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Server insgesamt</div>
                        <div class="mt-1 text-2xl font-semibold">{{ $this->getServers()->count() }}</div>
                    </div>
                    <x-filament::icon icon="heroicon-o-server-stack" class="h-8 w-8 text-primary-500" />
                </div>
            </x-filament::section>
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Ausgewählt</div>
                        <div class="mt-1 text-2xl font-semibold">{{ count($selectedServers) }}</div>
                    </div>
                    <x-filament::icon icon="heroicon-o-check-circle" class="h-8 w-8 text-primary-500" />
                </div>
            </x-filament::section>
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Live-Konsolen</div>
                        <div id="admin-controll-connection-count" class="mt-1 text-2xl font-semibold">0</div>
                    </div>
                    <x-filament::icon icon="heroicon-o-signal" class="h-8 w-8 text-success-500" />
                </div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">Server auswählen</x-slot>
            <x-slot name="description">Aktionen und Konsolenbefehle werden nur an ausgewählte Server gesendet. Die Live-Ausgabe aller Server wird zusammengeführt.</x-slot>
            <div class="mb-4 flex flex-wrap gap-2">
                <x-filament::button color="gray" size="sm" wire:click="selectAll">Alle auswählen</x-filament::button>
                <x-filament::button color="gray" size="sm" outlined wire:click="clearSelection">Auswahl aufheben</x-filament::button>
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach($this->getServers() as $server)
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 p-3 transition hover:border-primary-400 dark:border-gray-700">
                        <input type="checkbox" value="{{ $server->id }}" wire:model.live="selectedServers" data-admin-controll-server="{{ $server->id }}" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold">{{ $server->name }}</span>
                            <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ $server->node?->name ?? 'Node unbekannt' }} · {{ $server->uuid_short }}</span>
                        </span>
                        <span class="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $server->status?->value ?? 'unknown' }}</span>
                    </label>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Server-Steuerung</x-slot>
            <x-slot name="description">Die Aktionen werden auf allen ausgewählten Servern ausgeführt.</x-slot>
            <div class="flex flex-wrap gap-2">
                <x-filament::button color="success" icon="heroicon-o-play" wire:click="sendPowerAction('start')" wire:confirm="Alle ausgewählten Server starten?">Starten</x-filament::button>
                <x-filament::button color="danger" icon="heroicon-o-stop" wire:click="sendPowerAction('stop')" wire:confirm="Alle ausgewählten Server stoppen?">Stoppen</x-filament::button>
                <x-filament::button color="warning" icon="heroicon-o-arrow-path" wire:click="sendPowerAction('restart')" wire:confirm="Alle ausgewählten Server neustarten?">Restart</x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Zentrale Live-Konsole</x-slot>
            <x-slot name="description">Konsolenausgaben aller Server werden mit Servernamen zusammengeführt.</x-slot>
            <div class="overflow-hidden rounded-xl border border-gray-800 bg-[#0b1220]">
                <div class="flex items-center justify-between border-b border-gray-800 px-4 py-3">
                    <div class="flex items-center gap-2 text-sm font-medium text-gray-200">
                        <span class="h-2 w-2 rounded-full bg-green-400" id="admin-controll-live-indicator"></span>
                        LIVE OUTPUT
                    </div>
                    <div class="flex gap-2">
                        <button type="button" id="admin-controll-clear" class="text-xs text-gray-400 hover:text-white">Leeren</button>
                        <button type="button" id="admin-controll-copy" class="text-xs text-gray-400 hover:text-white">Kopieren</button>
                    </div>
                </div>
                <pre id="admin-controll-output" class="h-[420px] overflow-auto whitespace-pre-wrap break-words p-4 font-mono text-xs leading-5 text-gray-200">Verbinde mit den Server-Konsolen …</pre>
                <div class="flex gap-2 border-t border-gray-800 p-3">
                    <input id="admin-controll-command" type="text" autocomplete="off" placeholder="Befehl an ausgewählte Server senden …" class="min-w-0 flex-1 rounded-lg border border-gray-700 bg-[#111b2d] px-3 py-2 font-mono text-sm text-white placeholder:text-gray-500 focus:border-primary-500 focus:outline-none">
                    <button type="button" id="admin-controll-send" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500">Senden</button>
                </div>
            </div>
            <div class="mt-2 text-xs text-gray-500">Die Konsole verbindet sich im Lesemodus mit allen Servern. Befehle werden ausschließlich an die aktuelle Auswahl gesendet.</div>
        </x-filament::section>
    </div>

    @script
    <script>
        const connections = @js($this->getConnections());
        const sockets = new Map();
        const output = document.getElementById('admin-controll-output');
        const commandInput = document.getElementById('admin-controll-command');
        const sendButton = document.getElementById('admin-controll-send');
        const connectionCount = document.getElementById('admin-controll-connection-count');
        const indicator = document.getElementById('admin-controll-live-indicator');
        const connected = new Set();
        let destroyed = false;

        const appendLine = (name, line) => {
            if (output.textContent === 'Verbinde mit den Server-Konsolen …') {
                output.textContent = '';
            }
            output.textContent += '[' + name + '] ' + String(line ?? '').replace(/[\r\n]+$/, '') + '\n';
            output.scrollTop = output.scrollHeight;
        };

        const updateConnections = () => {
            connectionCount.textContent = String(connected.size);
            indicator.classList.toggle('bg-green-400', connected.size > 0);
            indicator.classList.toggle('bg-red-500', connected.size === 0);
        };

        const sendSocket = (connection, payload) => {
            const entry = sockets.get(connection.id);
            if (entry?.authenticated && entry.socket.readyState === WebSocket.OPEN) {
                entry.socket.send(JSON.stringify(payload));
                return true;
            }
            return false;
        };

        const connect = (connection) => {
            if (destroyed || sockets.has(connection.id)) {
                return;
            }

            const socket = new WebSocket(connection.socket);
            const entry = { socket, authenticated: false, connection };
            sockets.set(connection.id, entry);

            socket.onopen = () => {
                socket.send(JSON.stringify({ event: 'auth', args: [connection.token] }));
            };

            socket.onmessage = async (message) => {
                let payload;
                try {
                    payload = JSON.parse(message.data);
                } catch {
                    return;
                }

                const args = payload.args ?? [];
                if (payload.event === 'auth success') {
                    entry.authenticated = true;
                    connected.add(connection.id);
                    updateConnections();
                    socket.send(JSON.stringify({ event: 'send logs', args: [null] }));
                } else if (payload.event === 'console output' || payload.event === 'install output') {
                    appendLine(connection.name, args[0]);
                } else if (payload.event === 'status') {
                    appendLine(connection.name, 'Status: ' + args[0]);
                } else if (payload.event === 'daemon error') {
                    appendLine(connection.name, 'ERROR: ' + args[0]);
                } else if (payload.event === 'token expiring' || payload.event === 'token expired') {
                    try {
                        const renewed = await $wire.getConnectionFor(connection.id);
                        connection.token = renewed.token;
                        if (socket.readyState === WebSocket.OPEN) {
                            socket.send(JSON.stringify({ event: 'auth', args: [renewed.token] }));
                        }
                    } catch {
                        appendLine(connection.name, 'Authentifizierung konnte nicht erneuert werden.');
                    }
                }
            };

            socket.onclose = () => {
                connected.delete(connection.id);
                sockets.delete(connection.id);
                updateConnections();
                if (!destroyed) {
                    setTimeout(() => connect(connection), 4000);
                }
            };

            socket.onerror = () => {
                appendLine(connection.name, 'Verbindung fehlgeschlagen.');
            };
        };

        connections.forEach(connect);

        const sendCommand = () => {
            const command = commandInput.value.trim();
            if (!command) {
                return;
            }

            const selected = Array.from(document.querySelectorAll('[data-admin-controll-server]:checked')).map((element) => String(element.dataset.adminControllServer));
            const ids = new Set(selected);
            let sent = 0;

            connections.forEach((connection) => {
                if (ids.has(String(connection.id)) && sendSocket(connection, { event: 'send command', args: [command] })) {
                    sent++;
                }
            });

            appendLine('ADMIN Controll', 'Befehl an ' + sent + ' von ' + ids.size + ' ausgewählten Servern gesendet: ' + command);
            commandInput.value = '';
        };

        sendButton.addEventListener('click', sendCommand);
        commandInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                sendCommand();
            }
        });

        document.getElementById('admin-controll-clear').addEventListener('click', () => {
            output.textContent = '';
        });

        document.getElementById('admin-controll-copy').addEventListener('click', () => {
            navigator.clipboard.writeText(output.textContent);
        });

        document.addEventListener('livewire:navigating', () => {
            destroyed = true;
            sockets.forEach((entry) => entry.socket.close());
            sockets.clear();
            connected.clear();
        }, { once: true });
    </script>
    @endscript
</x-filament-panels::page>