document.addEventListener("DOMContentLoaded", () => {
  const chatMessages = document.getElementById("chatMessages");
  const messageInput = document.getElementById("messageInput");
  const sendBtn = document.getElementById("sendBtn");
  const chatHeader = document.getElementById("chatHeader");

  // Get sender name from URL
  const urlParams = new URLSearchParams(window.location.search);
  const senderName = urlParams.get("name") || "Bot";
  
  // Update header dynamically
  if (chatHeader) chatHeader.textContent = `${senderName}`;

  // Append message function
  function appendMessage(text, sender, showName = false) {
    const msgDiv = document.createElement("div");
    const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    
    if (sender === "user") {
      // Sent messages
      msgDiv.className = "self-end max-w-[70%] p-3 rounded-2xl break-words fade-in shadow bg-gradient-to-r from-blue-400 to-blue-500 text-white flex flex-col";
      msgDiv.innerHTML = `<span>${text}</span><span class="text-xs mt-1 text-gray-200 self-end">${time}</span>`;
    } else {
      // Received messages
      msgDiv.className = "self-start max-w-[70%] p-3 rounded-2xl break-words fade-in shadow bg-gray-200 text-gray-900 flex flex-col";
      msgDiv.innerHTML = `
        ${showName ? `<span class="font-semibold text-sm mb-1">${sender}</span>` : ""}
        <span>${text}</span>
        <span class="text-xs mt-1 text-gray-500 self-end">${time}</span>
      `;
    }

    chatMessages.appendChild(msgDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  // Send message
  function sendMessage() {
    const text = messageInput.value.trim();
    if (!text) return;

    appendMessage(text, "user");
    messageInput.value = "";

    // Simulate a received message after 1 second
    setTimeout(() => {
      appendMessage(`Reply to: ${text}`, senderName, true); // show sender name
    }, 1000);
  }

  sendBtn.addEventListener("click", sendMessage);
  messageInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") sendMessage();
  });
});