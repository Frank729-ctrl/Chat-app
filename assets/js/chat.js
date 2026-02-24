
    const chatMessages = document.getElementById("chatMessages");
    const messageInput = document.getElementById("messageInput");
    const sendBtn = document.getElementById("sendBtn");

    function appendMessage(text, sender) {
      const msgDiv = document.createElement("div");
      const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
      msgDiv.className = `max-w-[70%] p-3 rounded-2xl break-words fade-in shadow ${
        sender === "user"
          ? "self-end bg-gradient-to-r from-blue-400 to-blue-500 text-white"
          : "self-start bg-gray-200 text-gray-900"
      } flex flex-col`;

      msgDiv.innerHTML = `<span>${text}</span><span class="text-xs mt-1 text-gray-600 self-end">${time}</span>`;
      chatMessages.appendChild(msgDiv);
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function sendMessage() {
      const text = messageInput.value.trim();
      if (!text) return;

      appendMessage(text, "user");
      messageInput.value = "";

      
    }

    sendBtn.addEventListener("click", sendMessage);
    messageInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") sendMessage();
    });
  