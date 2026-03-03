<?php
/**
 * WebSocket server configuration
 * --------------------------------
 * Consumed by chat.php to inject the correct WS URL into the page.
 *
 * In development:  ws://localhost:8080
 * In production:   wss://yourdomain.com/ws  (after nginx proxy_pass)
 *
 * Nginx proxy example (add inside your server {} block):
 *
 *   location /ws {
 *       proxy_pass         http://127.0.0.1:8080;
 *       proxy_http_version 1.1;
 *       proxy_set_header   Upgrade    $http_upgrade;
 *       proxy_set_header   Connection "Upgrade";
 *       proxy_set_header   Host       $host;
 *   }
 */

$wsProtocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'wss' : 'ws';
// $wsHost     = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('WS_URL', "{$wsProtocol}://localhost:8080");

// If running behind Nginx with /ws proxy, use path-based URL instead:
// define('WS_URL', "{$wsProtocol}://{$wsHost}/ws");
define('WS_URL', "{$wsProtocol}://{$wsHost}:{$wsPort}");
