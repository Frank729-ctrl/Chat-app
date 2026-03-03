<?php
require_once "../includes/dashboard_data.php";
$conversations = $conversations ?? [];
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Dashboard | ChatConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: { extend: { colors: { primary: "#137fec", "background-light": "#f6f7f8", "background-dark": "#101922" }, fontFamily: { display: ["Plus Jakarta Sans", "sans-serif"] } } }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 10px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100 antialiased min-h-screen">

<!-- New Chat Modal -->
<div id="new-chat-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm hidden">
    <div class="w-full max-w-sm mx-4 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-lg font-bold">New Conversation</h3>
            <button id="close-modal-btn" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="p-5">
            <div class="relative mb-4">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
                <input id="user-search-input" type="text" placeholder="Search by name or email…"
                    class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary/40"/>
            </div>
            <div id="user-search-results" class="space-y-1 max-h-64 overflow-y-auto custom-scrollbar">
                <p class="text-sm text-slate-400 text-center py-6">Start typing to find people</p>
            </div>
        </div>
    </div>
</div>

<!-- Profile Modal -->
<div id="profile-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden">
    <div class="w-full max-w-md mx-4 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden max-h-[90vh] flex flex-col">

        <!-- Modal header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 shrink-0">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">My Profile</h3>
            <button id="close-profile-btn" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Scrollable body -->
        <div class="overflow-y-auto flex-1 custom-scrollbar">

            <!-- Cover banner -->
            <div class="h-24 bg-gradient-to-r from-primary to-blue-400 relative">
                <div class="absolute inset-0 opacity-20" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,.15) 10px, rgba(255,255,255,.15) 20px);"></div>
            </div>

            <!-- Avatar -->
            <div class="px-6 pb-4">
                <div class="flex items-end justify-between -mt-10 mb-4">
                    <div class="relative group cursor-pointer" id="avatar-upload-area">
                        <img id="avatar-preview"
                             src="<?= htmlspecialchars($user['profile_image'] ?? 'https://ui-avatars.com/api/?name='.urlencode($user['full_name'] ?? 'U').'&background=137fec&color=fff&size=200') ?>"
                             class="w-20 h-20 rounded-2xl object-cover ring-4 ring-white dark:ring-slate-900 shadow-lg" alt="Your avatar"/>
                        <div class="absolute inset-0 rounded-2xl bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="material-symbols-outlined text-white text-2xl">photo_camera</span>
                        </div>
                        <input id="avatar-file-input" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden"/>
                    </div>
                    <!-- Online badge -->
                    <span class="flex items-center gap-1.5 px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-xs font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Online
                    </span>
                </div>

                <!-- Avatar upload feedback -->
                <div id="avatar-status" class="hidden mb-3 px-3 py-2 rounded-lg text-sm font-medium text-center"></div>
                <div id="avatar-save-row" class="hidden mb-4 flex gap-2">
                    <button id="avatar-upload-btn" class="flex-1 py-2 bg-primary text-white text-sm font-bold rounded-xl hover:bg-primary/90 transition-all flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-base">save</span> Save Photo
                    </button>
                    <button id="avatar-cancel-btn" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-sm font-semibold rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-all">
                        Cancel
                    </button>
                </div>

                <!-- Name & email -->
                <div class="mb-1">
                    <p id="profile-display-name" class="text-xl font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($user['full_name']) ?></p>
                    <p class="text-sm text-slate-500 dark:text-slate-400"><?= htmlspecialchars($user['email']) ?></p>
                </div>
                <p class="text-xs text-slate-400 mb-5">
                    Member since <?= date('F Y', strtotime($user['created_at'])) ?>
                </p>

                <!-- Divider -->
                <div class="border-t border-slate-100 dark:border-slate-800 mb-5"></div>

                <!-- Edit display name -->
                <div class="mb-5">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Display Name</label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none">person</span>
                            <input id="name-input" type="text"
                                   value="<?= htmlspecialchars($user['full_name']) ?>"
                                   class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-900 dark:text-slate-100 outline-none focus:ring-2 focus:ring-primary/40 transition-all"/>
                        </div>
                        <button id="save-name-btn" class="px-4 py-2.5 bg-primary text-white text-sm font-bold rounded-xl hover:bg-primary/90 transition-all flex items-center gap-1 shrink-0">
                            <span class="material-symbols-outlined text-base">check</span> Save
                        </button>
                    </div>
                    <p id="name-status" class="hidden mt-1.5 text-xs font-medium px-1"></p>
                </div>

                <!-- Divider -->
                <div class="border-t border-slate-100 dark:border-slate-800 mb-5"></div>

                <!-- Change password -->
                <div>
                    <button id="toggle-password-section" class="w-full flex items-center justify-between text-left group mb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Change Password</span>
                        <span class="material-symbols-outlined text-slate-400 text-lg transition-transform" id="pw-chevron">expand_more</span>
                    </button>

                    <div id="password-section" class="hidden space-y-3">
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none">lock</span>
                            <input id="current-pw" type="password" placeholder="Current password"
                                   class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary/40"/>
                        </div>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none">lock_open</span>
                            <input id="new-pw" type="password" placeholder="New password (min 6 chars)"
                                   class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary/40"/>
                        </div>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none">lock_reset</span>
                            <input id="confirm-pw" type="password" placeholder="Confirm new password"
                                   class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary/40"/>
                        </div>
                        <p id="pw-status" class="hidden text-xs font-medium px-1"></p>
                        <button id="save-pw-btn" class="w-full py-2.5 bg-slate-800 dark:bg-slate-700 text-white text-sm font-bold rounded-xl hover:bg-slate-700 dark:hover:bg-slate-600 transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-base">key</span> Update Password
                        </button>
                    </div>
                </div>

                <!-- Bottom padding -->
                <div class="pb-2"></div>
            </div>
        </div>
    </div>
</div>

<div class="flex h-screen w-full overflow-hidden">

    <!-- Sidebar -->
    <aside class="flex h-full w-full max-w-[400px] flex-col border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
        <header class="flex flex-col gap-4 px-5 py-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-white">
                        <span class="material-symbols-outlined text-2xl">chat_bubble</span>
                    </div>
                    <h1 class="text-xl font-bold tracking-tight">ChatConnect</h1>
                </div>
                <div class="flex items-center gap-2">
                    <button id="dark-toggle" title="Toggle dark mode" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700 transition-colors">
                        <span class="material-symbols-outlined text-xl" id="dark-icon">dark_mode</span>
                    </button>
                    <a href="/api/auth/logout.php" title="Logout" class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-red-100 hover:text-red-500 dark:bg-slate-800 dark:text-slate-400 transition-colors">
                        <span class="material-symbols-outlined text-xl">logout</span>
                    </a>
                    <div id="my-avatar-btn" class="h-9 w-9 overflow-hidden rounded-full border-2 border-primary/20 bg-slate-200 dark:bg-slate-700 shrink-0 cursor-pointer hover:ring-2 hover:ring-primary/40 transition-all" title="Profile settings">
                        <img class="h-full w-full object-cover"
                             src="<?= htmlspecialchars($user['profile_image'] ?? 'https://ui-avatars.com/api/?name='.urlencode($user['full_name'] ?? 'U').'&background=137fec&color=fff') ?>"
                             alt="Your avatar"/>
                    </div>
                </div>
            </div>

            <button id="new-chat-btn" class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-bold text-white shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all active:scale-[0.98]">
                <span class="material-symbols-outlined text-[20px]">add</span>
                <span>New Conversation</span>
            </button>

            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 pointer-events-none">search</span>
                <input id="conv-search" class="w-full rounded-xl border-none bg-slate-100 py-3 pl-11 pr-4 text-sm placeholder:text-slate-500 focus:ring-2 focus:ring-primary/30 dark:bg-slate-800 dark:text-slate-100 outline-none" placeholder="Search conversations…" type="text"/>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto px-2 pb-6 custom-scrollbar">
            <div class="px-4 py-2">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Recent Messages</h3>
            </div>
            <div id="conv-list" class="space-y-0.5 px-2">
                <?php foreach($conversations as $chat):
                    $hasUnread = !empty($chat['unread_count']) && $chat['unread_count'] > 0;
                    $convId = (int)$chat['conversation_id'];
                    $name = htmlspecialchars($chat['name'] ?: $chat['other_user_name'] ?: 'Unknown');
                    $avatar = htmlspecialchars($chat['other_user_image'] ?? 'https://ui-avatars.com/api/?name='.urlencode($chat['other_user_name'] ?? 'U').'&background=137fec&color=fff');
                ?>
                <div class="group relative flex cursor-pointer items-center gap-4 rounded-xl p-3.5 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors conv-item"
                     data-conv-id="<?= $convId ?>"
                     onclick="openChat(<?= $convId ?>)">
                    <div class="relative shrink-0">
                        <?php if(!$chat['is_group']): ?>
                            <img class="h-12 w-12 rounded-full object-cover ring-1 ring-slate-200 dark:ring-slate-700" src="<?= $avatar ?>" alt="<?= $name ?>"/>
                            <?php if(!empty($chat['other_user_online'])): ?>
                                <div class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white dark:border-slate-900 bg-green-500"></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="h-12 w-12 rounded-full bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                <span class="material-symbols-outlined text-primary text-2xl">group</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-1 flex-col overflow-hidden min-w-0">
                        <div class="flex items-center justify-between">
                            <span class="truncate font-semibold text-slate-900 dark:text-white text-sm"><?= $name ?></span>
                            <span class="text-xs ml-2 shrink-0 <?= $hasUnread ? 'text-primary font-semibold' : 'text-slate-400' ?>">
                                <?php
                                    $time = strtotime($chat['last_message_time'] ?? 'now');
                                    $diff = time() - $time;
                                    if ($diff < 86400) echo date('g:i A', $time);
                                    elseif ($diff < 172800) echo 'Yesterday';
                                    else echo date('M j', $time);
                                ?>
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-sm <?= $hasUnread ? 'font-medium text-slate-700 dark:text-slate-300' : 'text-slate-500 dark:text-slate-400' ?>">
                                <?= htmlspecialchars($chat['last_message'] ?: 'No messages yet') ?>
                            </p>
                            <?php if($hasUnread): ?>
                                <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-white">
                                    <?= min((int)$chat['unread_count'], 99) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($conversations)): ?>
                <div class="py-16 text-center">
                    <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 mb-3">
                        <span class="material-symbols-outlined text-3xl text-slate-400">chat_bubble_outline</span>
                    </div>
                    <p class="text-sm text-slate-400">No conversations yet.<br>Start one above!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <footer class="border-t border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900/50">
            <div class="flex items-center justify-around rounded-lg bg-white p-2 shadow-sm dark:bg-slate-800">
                <button class="flex flex-1 flex-col items-center gap-0.5 text-primary">
                    <span class="material-symbols-outlined text-xl">chat</span>
                    <span class="text-[9px] font-bold uppercase tracking-wider">Chats</span>
                </button>
                <button class="flex flex-1 flex-col items-center gap-0.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined text-xl">call</span>
                    <span class="text-[9px] font-bold uppercase tracking-wider">Calls</span>
                </button>
                <button onclick="document.getElementById('profile-modal').classList.remove('hidden')" class="flex flex-1 flex-col items-center gap-0.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined text-xl">person</span>
                    <span class="text-[9px] font-bold uppercase tracking-wider">Profile</span>
                </button>
            </div>
        </footer>
    </aside>

    <!-- Desktop main panel -->
    <main id="main-panel" class="hidden flex-1 items-center justify-center bg-background-light dark:bg-background-dark md:flex">
        <div class="text-center select-none">
            <div class="mb-4 inline-flex h-24 w-24 items-center justify-center rounded-full bg-primary/10 text-primary">
                <span class="material-symbols-outlined text-5xl">forum</span>
            </div>
            <h2 class="text-2xl font-bold">Welcome<?php if(!empty($user['full_name'])) echo ', ' . htmlspecialchars(explode(' ', $user['full_name'])[0]); ?>!</h2>
            <p class="mt-2 text-slate-500 dark:text-slate-400">Select a conversation or start a new one</p>
        </div>
    </main>
</div>

<script>
// Dark mode
const html = document.documentElement;
const savedTheme = localStorage.getItem('cc-theme') || 'light';
if (savedTheme === 'dark') { html.classList.add('dark'); document.getElementById('dark-icon').textContent = 'light_mode'; }

document.getElementById('dark-toggle').addEventListener('click', () => {
    const isDark = html.classList.toggle('dark');
    localStorage.setItem('cc-theme', isDark ? 'dark' : 'light');
    document.getElementById('dark-icon').textContent = isDark ? 'light_mode' : 'dark_mode';
});

// Open chat
function openChat(convId) {
    if (window.innerWidth < 768) {
        window.location.href = `/public/chat.php?conv=${convId}`;
        return;
    }
    document.querySelectorAll('.conv-item').forEach(el => el.classList.remove('bg-primary/5', 'dark:bg-primary/10'));
    const el = document.querySelector(`[data-conv-id="${convId}"]`);
    if (el) el.classList.add('bg-primary/5', 'dark:bg-primary/10');
    const main = document.getElementById('main-panel');
    main.innerHTML = `<iframe src="/public/chat.php?conv=${convId}&embed=1" class="w-full h-full border-0 rounded-none"></iframe>`;
}

// Conversation search filter
document.getElementById('conv-search').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.conv-item').forEach(item => {
        item.style.display = item.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

// New chat modal
const modal  = document.getElementById('new-chat-modal');
document.getElementById('new-chat-btn').addEventListener('click', () => modal.classList.remove('hidden'));
document.getElementById('close-modal-btn').addEventListener('click', () => modal.classList.add('hidden'));
modal.addEventListener('click', e => { if (e.target === modal) modal.classList.add('hidden'); });

let searchTimer;
document.getElementById('user-search-input').addEventListener('input', function() {
    clearTimeout(searchTimer);
    const q = this.value.trim();
    const results = document.getElementById('user-search-results');
    if (q.length < 2) { results.innerHTML = '<p class="text-sm text-slate-400 text-center py-6">Start typing…</p>'; return; }
    results.innerHTML = '<p class="text-sm text-slate-400 text-center py-4">Searching…</p>';
    searchTimer = setTimeout(async () => {
        const res  = await fetch(`/api/users/search.php?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        if (!data.users?.length) { results.innerHTML = '<p class="text-sm text-slate-400 text-center py-6">No users found</p>'; return; }
        results.innerHTML = data.users.map(u => `
            <button onclick="startConversation(${u.id})"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-left">
                <img class="h-10 w-10 rounded-full object-cover"
                     src="${u.profile_image || `https://ui-avatars.com/api/?name=${encodeURIComponent(u.full_name)}&background=137fec&color=fff`}"
                     alt="${u.full_name}"/>
                <div class="min-w-0">
                    <p class="font-semibold text-sm text-slate-900 dark:text-white truncate">${u.full_name}</p>
                    <p class="text-xs text-slate-500 truncate">${u.email}</p>
                </div>
                ${u.is_online ? '<span class="ml-auto w-2 h-2 rounded-full bg-green-500 shrink-0"></span>' : ''}
            </button>`).join('');
    }, 350);
});

async function startConversation(userId) {
    const res  = await fetch('/api/conversations/create.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId })
    });
    const data = await res.json();
    if (data.success) { modal.classList.add('hidden'); openChat(data.conversation_id); if (data.new) location.reload(); }
}

// ── Profile Modal ──────────────────────────────────────────
const profileModal    = document.getElementById('profile-modal');
const avatarInput     = document.getElementById('avatar-file-input');
const avatarPreview   = document.getElementById('avatar-preview');
const avatarUploadBtn = document.getElementById('avatar-upload-btn');
const avatarCancelBtn = document.getElementById('avatar-cancel-btn');
const avatarSaveRow   = document.getElementById('avatar-save-row');
const avatarStatus    = document.getElementById('avatar-status');

// Open / close
document.getElementById('my-avatar-btn')?.addEventListener('click', () => profileModal.classList.remove('hidden'));
document.getElementById('close-profile-btn').addEventListener('click', () => profileModal.classList.add('hidden'));
profileModal.addEventListener('click', e => { if (e.target === profileModal) profileModal.classList.add('hidden'); });

// Click avatar image or upload area to pick file
document.getElementById('avatar-upload-area')?.addEventListener('click', () => avatarInput.click());

let selectedAvatarFile = null;
let originalAvatarSrc  = avatarPreview?.src;

// File selected — show preview & save row
avatarInput.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    selectedAvatarFile = file;
    const reader = new FileReader();
    reader.onload = e => { avatarPreview.src = e.target.result; };
    reader.readAsDataURL(file);
    avatarSaveRow.classList.remove('hidden');
    avatarStatus.classList.add('hidden');
});

// Cancel — revert preview
avatarCancelBtn.addEventListener('click', () => {
    avatarPreview.src = originalAvatarSrc;
    avatarSaveRow.classList.add('hidden');
    avatarStatus.classList.add('hidden');
    avatarInput.value = '';
    selectedAvatarFile = null;
});

// Save photo
avatarUploadBtn.addEventListener('click', async () => {
    if (!selectedAvatarFile) return;
    const formData = new FormData();
    formData.append('avatar', selectedAvatarFile);
    avatarUploadBtn.disabled = true;
    avatarUploadBtn.innerHTML = '<span class="material-symbols-outlined text-base animate-spin">progress_activity</span> Uploading…';

    try {
        const res  = await fetch('/api/users/upload_avatar.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            const newSrc = data.url + '?t=' + Date.now();
            originalAvatarSrc = newSrc;
            avatarPreview.src = newSrc;
            // Update sidebar avatar too
            document.querySelectorAll('img[alt="Your avatar"]').forEach(img => img.src = newSrc);
            avatarSaveRow.classList.add('hidden');
            selectedAvatarFile = null;
            avatarInput.value = '';
            showProfileStatus(avatarStatus, '✓ Photo updated!', 'green');
        } else {
            throw new Error(data.error || 'Upload failed');
        }
    } catch(e) {
        showProfileStatus(avatarStatus, '✗ ' + e.message, 'red');
    }
    avatarUploadBtn.disabled = false;
    avatarUploadBtn.innerHTML = '<span class="material-symbols-outlined text-base">save</span> Save Photo';
});

// ── Edit display name ──────────────────────────────────────
document.getElementById('save-name-btn').addEventListener('click', async () => {
    const nameInput  = document.getElementById('name-input');
    const nameStatus = document.getElementById('name-status');
    const newName    = nameInput.value.trim();
    if (!newName) return;

    const btn = document.getElementById('save-name-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined text-base animate-spin">progress_activity</span>';

    try {
        const res  = await fetch('/api/users/update_profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update_name', full_name: newName })
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('profile-display-name').textContent = data.full_name;
            showProfileStatus(nameStatus, '✓ Name updated!', 'green');
        } else {
            throw new Error(data.error || 'Failed to update name');
        }
    } catch(e) {
        showProfileStatus(nameStatus, '✗ ' + e.message, 'red');
    }
    btn.disabled = false;
    btn.innerHTML = '<span class="material-symbols-outlined text-base">check</span> Save';
});

// ── Change password toggle ─────────────────────────────────
document.getElementById('toggle-password-section').addEventListener('click', () => {
    const section = document.getElementById('password-section');
    const chevron = document.getElementById('pw-chevron');
    const isHidden = section.classList.contains('hidden');
    section.classList.toggle('hidden', !isHidden);
    chevron.textContent = isHidden ? 'expand_less' : 'expand_more';
});

// ── Save new password ──────────────────────────────────────
document.getElementById('save-pw-btn').addEventListener('click', async () => {
    const current  = document.getElementById('current-pw').value;
    const newPw    = document.getElementById('new-pw').value;
    const confirm  = document.getElementById('confirm-pw').value;
    const pwStatus = document.getElementById('pw-status');
    const btn      = document.getElementById('save-pw-btn');

    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined text-base animate-spin">progress_activity</span> Updating…';

    try {
        const res  = await fetch('/api/users/update_profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'change_password', current_password: current, new_password: newPw, confirm_password: confirm })
        });
        const data = await res.json();
        if (data.success) {
            document.getElementById('current-pw').value = '';
            document.getElementById('new-pw').value     = '';
            document.getElementById('confirm-pw').value = '';
            showProfileStatus(pwStatus, '✓ Password updated successfully!', 'green');
        } else {
            throw new Error(data.error || 'Failed to update password');
        }
    } catch(e) {
        showProfileStatus(pwStatus, '✗ ' + e.message, 'red');
    }
    btn.disabled = false;
    btn.innerHTML = '<span class="material-symbols-outlined text-base">key</span> Update Password';
});

// Helper — show a status message then fade it out
function showProfileStatus(el, msg, color) {
    el.textContent = msg;
    el.className = `text-xs font-medium px-1 ${color === 'green' ? 'text-green-600' : 'text-red-500'}`;
    el.classList.remove('hidden');
    clearTimeout(el._timer);
    el._timer = setTimeout(() => el.classList.add('hidden'), 4000);
}

// ── Real-time sidebar preview (called by chat iframe) ──────
function updateConvPreview(convId, lastMsg) {
    const item = document.querySelector(`[data-conv-id="${convId}"]`);
    if (!item) return;
    const preview = item.querySelector('p.truncate');
    if (preview) preview.textContent = lastMsg;
    const timeEl = item.querySelector('.text-xs.ml-2');
    if (timeEl) timeEl.textContent = new Date().toLocaleTimeString([], { hour:'2-digit', minute:'2-digit' });
    // Move to top of list
    const list = document.getElementById('conv-list');
    if (list && item.parentNode === list) list.prepend(item);
}
</script>
</body>
</html>