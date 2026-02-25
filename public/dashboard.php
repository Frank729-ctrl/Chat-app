<?php
require_once "../includes/dashboard_data.php"; // loads $user and $conversations
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
            theme: {
                extend: {
                    colors: {
                        primary: "#137fec",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101922",
                    },
                    fontFamily: {
                        display: ["Plus Jakarta Sans", "sans-serif"]
                    },
                    borderRadius: {
                        DEFAULT: "0.25rem",
                        lg: "0.5rem",
                        xl: "0.75rem",
                        full: "9999px",
                    },
                },
            },
        }
    </script>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.1);
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(0,0,0,0.2);
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-slate-100 antialiased min-h-screen">

<div class="flex h-screen w-full overflow-hidden">

    <!-- Sidebar -->
    <aside class="flex h-full w-full max-w-[400px] flex-col border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">

        <!-- Header -->
        <header class="flex flex-col gap-4 px-6 py-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-white">
                        <span class="material-symbols-outlined text-2xl">chat_bubble</span>
                    </div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">ChatConnect</h1>
                </div>
                <div class="flex items-center gap-2">
                    <button class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700 transition-colors">
                        <span class="material-symbols-outlined">settings</span>
                    </button>
                    <div class="h-10 w-10 overflow-hidden rounded-full border-2 border-primary/20 bg-slate-200 dark:bg-slate-700">
                        <img class="h-full w-full object-cover" 
                             src="<?= htmlspecialchars($user['profile_picture'] ?? 'https://via.placeholder.com/150') ?>" 
                             alt="Your profile picture"/>
                    </div>
                </div>
            </div>

            <button class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-bold text-white shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all active:scale-[0.98]">
                <span class="material-symbols-outlined text-[20px]">add</span>
                <span>New Chat</span>
            </button>

            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 pointer-events-none">search</span>
                <input class="w-full rounded-xl border-none bg-slate-100 py-3 pl-11 pr-4 text-sm placeholder:text-slate-500 focus:ring-2 focus:ring-primary/30 dark:bg-slate-800 dark:text-slate-100 outline-none" placeholder="Search conversations..." type="text"/>
            </div>
        </header>

        <!-- Conversations List -->
        <div class="flex-1 overflow-y-auto px-2 pb-6 custom-scrollbar">
            <div class="px-4 py-2">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Recent Messages</h3>
            </div>

            <div class="space-y-1 px-2">
                <?php foreach($conversations as $chat): 
                    $isActive = false; // ← you can set this dynamically later (e.g. based on URL param)
                    $hasUnread = !empty($chat['unread_count']) && $chat['unread_count'] > 0;
                ?>
                    <div class="group relative flex cursor-pointer items-center gap-4 rounded-xl p-4 transition-all <?php if($isActive): ?> bg-primary/5 dark:bg-primary/10 <?php else: ?> hover:bg-slate-50 dark:hover:bg-slate-800/50 <?php endif; ?>">
                        
                        <?php if($isActive): ?>
                            <div class="absolute left-0 top-1/2 h-8 w-1 -translate-y-1/2 rounded-r-full bg-primary"></div>
                        <?php endif; ?>

                        <div class="relative h-14 w-14 shrink-0 overflow-hidden rounded-full <?php if($chat['is_group']): ?> bg-slate-100 dark:bg-slate-800 flex items-center justify-center <?php endif; ?>">
                            <?php if(!$chat['is_group']): ?>
                                <img class="h-full w-full object-cover" 
                                     src="<?= htmlspecialchars($chat['other_user_image'] ?? 'https://via.placeholder.com/150') ?>" 
                                     alt="Profile picture of <?= htmlspecialchars($chat['other_user_name'] ?? 'user') ?>"/>
                                <?php if(!empty($chat['other_user_online'])): ?>
                                    <div class="absolute bottom-0 right-0 h-3.5 w-3.5 rounded-full border-2 border-white dark:border-slate-900 bg-green-500"></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="material-symbols-outlined text-slate-400">group</span>
                            <?php endif; ?>
                        </div>

                        <div class="flex flex-1 flex-col overflow-hidden min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="truncate font-semibold text-slate-900 dark:text-white">
                                    <?= htmlspecialchars($chat['name'] ?: $chat['other_user_name'] ?: 'Unknown') ?>
                                </span>
                                <span class="text-xs font-medium <?= $hasUnread ? 'text-primary' : 'text-slate-400 dark:text-slate-500' ?>">
                                    <?php
                                        $time = strtotime($chat['last_message_time'] ?? 'now');
                                        $diff = time() - $time;
                                        if ($diff < 86400) {
                                            echo date('g:i A', $time);
                                        } elseif ($diff < 172800) {
                                            echo 'Yesterday';
                                        } else {
                                            echo date('M d', $time);
                                        }
                                    ?>
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate text-sm <?= $hasUnread ? 'font-medium text-slate-700 dark:text-slate-300' : 'text-slate-500 dark:text-slate-400' ?>">
                                    <?= htmlspecialchars($chat['last_message'] ?: 'No messages yet') ?>
                                </p>
                                <?php if($hasUnread): ?>
                                    <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-white">
                                        <?= min($chat['unread_count'], 99) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if(empty($conversations)): ?>
                    <div class="py-10 text-center text-slate-400 dark:text-slate-500 text-sm">
                        No conversations yet
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bottom bar (like in your static version) -->
        <footer class="border-t border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50">
            <div class="flex items-center justify-between rounded-lg bg-white p-2 shadow-sm dark:bg-slate-800">
                <button class="flex flex-1 flex-col items-center gap-1 text-primary">
                    <span class="material-symbols-outlined text-2xl">chat</span>
                    <span class="text-[10px] font-bold uppercase tracking-wider">Chats</span>
                </button>
                <button class="flex flex-1 flex-col items-center gap-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined text-2xl">call</span>
                    <span class="text-[10px] font-bold uppercase tracking-wider">Calls</span>
                </button>
                <button class="flex flex-1 flex-col items-center gap-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <span class="material-symbols-outlined text-2xl">person</span>
                    <span class="text-[10px] font-bold uppercase tracking-wider">Contacts</span>
                </button>
            </div>
        </footer>

    </aside>

    <!-- Main area (empty state) -->
    <main class="hidden flex-1 items-center justify-center bg-background-light dark:bg-background-dark md:flex">
        <div class="text-center">
            <div class="mb-4 inline-flex h-24 w-24 items-center justify-center rounded-full bg-slate-200 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
                <span class="material-symbols-outlined text-5xl">forum</span>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                Welcome<?php if(!empty($user['full_name'])) echo ', ' . htmlspecialchars($user['full_name']); ?>
            </h2>
            <p class="mt-2 text-slate-500 dark:text-slate-400">Select a conversation to start messaging</p>
        </div>
    </main>

</div>

</body>
</html>