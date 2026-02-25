document.getElementById("login-form").addEventListener("submit", async (e) => {
  e.preventDefault();

  const payload = {
    email: email.value,
    password: password.value
  };

  const res = await fetch("/api/auth/login.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload)
  });

  const data = await res.json();

  if (data.success) {
    window.location.href = "dashboard.php";
  } else {
    alert(data.error || "Login failed");
  }
});