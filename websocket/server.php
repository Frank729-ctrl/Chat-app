#!/usr/bin/env php
<?php
/**
 * ChatConnect WebSocket Server Entry Point
 * ==========================================
 * Run with:  php websocket/server.php
 *
 * For production use a process manager like Supervisor:
 *   [program:chatconnect-ws]
 *   command=php /var/www/html/Chat-app/websocket/server.php
 *   autostart=true
 *   autorestart=true
 *   stderr_logfile=/var/log/chatconnect-ws.err.log
 *   stdout_logfile=/var/log/chatconnect-ws.out.log
 *
 * Default port: 8080  (change WS_PORT in config/websocket.php or below)
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';    // provides $pdo
require_once __DIR__ . '/ChatServer.php';

use ChatConnect\ChatServer;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

$port = (int)(getenv('WS_PORT') ?: 8080);

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer($pdo)
        )
    ),
    $port
);

echo "[ChatConnect WS] Listening on 0.0.0.0:{$port}\n";
echo "[ChatConnect WS] Press Ctrl+C to stop\n";

$server->run();
