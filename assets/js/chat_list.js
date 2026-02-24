document.addEventListener("DOMContentLoaded", () => {
  const chatList = document.getElementById("chatList");

  // Sample chat data
  const chats = [
    { id: 1, name: "Alice", lastMessage: "Hey, are you coming tonight?" },
    { id: 2, name: "Bob", lastMessage: "Check out this link!" },
    { id: 3, name: "Charlie", lastMessage: "Thanks for the help yesterday." },
    { id: 4, name: "Diana", lastMessage: "Can we meet tomorrow?" },
  ];

  // Render chat list
  chats.forEach(chat => {
    const chatItem = document.createElement("div");
    chatItem.className = "flex items-center p-3 bg-white rounded-xl shadow hover:bg-gray-100 cursor-pointer transition duration-200";

    chatItem.innerHTML = `
      <img src="https://via.placeholder.com/50" alt="${chat.name}" class="rounded-full mr-3">
      <div class="flex-1">
        <div class="font-semibold text-gray-900">${chat.name}</div>
        <div class="text-gray-500 text-sm truncate">${chat.lastMessage}</div>
      </div>
    `;

    chatItem.addEventListener("click", () => {
      window.location.href = `chat.html?chatId=${chat.id}&name=${encodeURIComponent(chat.name)}`;
    });

    chatList.appendChild(chatItem);
  });
});