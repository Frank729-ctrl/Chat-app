document.querySelector("form").addEventListener("submit", async (e) => {
  e.preventDefault();

  const inputs = e.target.querySelectorAll("input");

  const payload = {
    name: inputs[0].value,
    email: inputs[1].value,
    password: inputs[2].value,
    confirm: inputs[3].value
  };

  const res = await fetch("/api/auth/register.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload)
  });

  const data = await res.json();

  if (data.success) {
    window.location.href = "index.html";
  } else {
    alert(data.error || "Registration failed");
  }
});