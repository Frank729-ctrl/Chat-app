# ChatConnect 🗨️

A real-time PHP chat application with WebSocket messaging (Ratchet), user authentication, profile pictures, and a polished Tailwind CSS UI.

---

## Features

- **Real-time messaging** via WebSockets (Ratchet)
- **Typing indicators** — live "X is typing…" animation
- **Read receipts** — double-tick turns blue when messages are read
- **Online presence** — green dot, instant offline/online updates
- **Profile picture upload** — JPG/PNG/GIF/WebP, max 5 MB
- **Dark mode** — persisted in localStorage
- **Conversation search** — filter sidebar in real time
- **New conversation** — search any registered user and open a DM
- **Secure auth** — bcrypt passwords, PHP sessions
- **Responsive** — works on mobile and desktop

---

## Quick Start

### 1. Install PHP dependencies

```bash
cd Chat-app
composer install
```

### 2. Set up the database

```bash
mysql -u root -p < database.sql
```

Edit `config/database.php` with your credentials.

### 3. Start the WebSocket server

```bash
php websocket/server.php
# Listens on ws://localhost:8080
```

### 4. Open the app

Visit `http://localhost/Chat-app/public/index.html`

Demo login: `demo@chatconnect.app` / `password123`

---

## Production (Nginx + Supervisor)

**Keep WebSocket alive:**
```bash
sudo cp deploy/chatconnect-ws.conf /etc/supervisor/conf.d/
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl start chatconnect-ws
```

**Nginx WebSocket proxy (wss://):**
See `deploy/nginx.conf`. Then update `config/websocket.php`:
```php
define('WS_URL', "{$wsProtocol}://{$wsHost}/ws");
```

---

## File Structure

```
Chat-app/
├── api/
│   ├── auth/           login.php, register.php, logout.php
│   ├── conversations/  create.php
│   ├── messages/       fetch.php, send.php
│   └── users/          search.php, upload_avatar.php
├── config/             database.php, websocket.php
├── deploy/             chatconnect-ws.conf (Supervisor), nginx.conf
├── includes/           session.php, auth_guard.php, dashboard_data.php
├── public/             index.html, register.html, dashboard.php, chat.php
├── storage/avatars/    uploaded profile pictures (auto-created)
├── websocket/          ChatServer.php, server.php
├── composer.json
└── database.sql
```

---

## WebSocket Protocol

| Direction        | type       | Key fields                                              |
|-----------------|------------|---------------------------------------------------------|
| Client → Server | auth       | user_id                                                 |
| Client → Server | message    | conversation_id, content                                |
| Client → Server | typing     | conversation_id, is_typing                              |
| Client → Server | read       | conversation_id                                         |
| Server → Client | auth_ok    | user_id                                                 |
| Server → Client | message    | id, conversation_id, sender_id, content, created_at, is_mine |
| Server → Client | typing     | conversation_id, user_id, is_typing                     |
| Server → Client | read       | conversation_id, reader_id                              |
| Server → Client | presence   | user_id, is_online                                      |

---

## Security Notes

- Passwords hashed with bcrypt (`password_hash`)
- File uploads validated with `finfo` (not client MIME)
- All DB queries use PDO prepared statements
- Consider using a signed JWT for WebSocket auth in production instead of raw `user_id`
