const form = document.getElementById("lead-form");
const status = document.getElementById("lead-status");
const submit = form.querySelector('button[type="submit"]');

// Проверка в браузере — только подсказка; сервер проверяет поля заново.
form.addEventListener("submit", async (event) => {
  event.preventDefault();

  status.className = "form__status";
  status.textContent = "Отправляем…";
  submit.disabled = true;

  try {
    const response = await fetch(form.action, {
      method: "POST",
      body: new FormData(form),
      headers: { Accept: "application/json" },
    });
    const data = await response.json().catch(() => ({}));

    if (response.ok && data.ok) {
      form.reset();
      status.textContent = "Заявка отправлена. Мы свяжемся с вами.";
    } else {
      status.className = "form__status form__status--error";
      status.textContent = (
        data.errors || [data.error || "Не удалось отправить заявку."]
      ).join(" ");
    }
  } catch {
    status.className = "form__status form__status--error";
    status.textContent = "Сеть недоступна. Попробуйте ещё раз.";
  } finally {
    submit.disabled = false;
  }
});
