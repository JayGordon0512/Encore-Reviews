import './bootstrap';

// Encore Reviews: mobile menu toggle
document.addEventListener("DOMContentLoaded", () => {
  const btn = document.querySelector("[data-er-menu-button]");
  const menu = document.querySelector("[data-er-mobile-menu]");

  if (!btn || !menu) return;

 const closeMenu = () => {
  menu.classList.remove("is-open");
  btn.setAttribute("aria-expanded", "false");
};

const openMenu = () => {
  menu.classList.add("is-open");
  btn.setAttribute("aria-expanded", "true");
};
  btn.addEventListener("click", () => {
    const expanded = btn.getAttribute("aria-expanded") === "true";
    expanded ? closeMenu() : openMenu();
  });

  // Close on link click
  menu.addEventListener("click", (e) => {
    const target = e.target;
    if (target && target.matches("a")) closeMenu();
  });

  // Close on Escape
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeMenu();
  });

  // Close if resizing up to desktop
  window.addEventListener("resize", () => {
    if (window.innerWidth > 520) closeMenu();
  });
});

document.addEventListener("DOMContentLoaded", () => {
  const editor = document.querySelector("[data-performance-editor]");
  if (!editor) return;

  const list = editor.querySelector("[data-performance-list]");
  const template = editor.querySelector("[data-performance-template]");
  const addButton = editor.querySelector("[data-add-performance]");
  let nextIndex = list.querySelectorAll("[data-performance-row]").length;

  const updateRemoveButtons = () => {
    const rows = list.querySelectorAll("[data-performance-row]");
    rows.forEach((row) => {
      const removeButton = row.querySelector("[data-remove-performance]");
      if (removeButton) removeButton.disabled = rows.length === 1;
    });
  };

  addButton?.addEventListener("click", () => {
    list.insertAdjacentHTML("beforeend", template.innerHTML.replaceAll("__INDEX__", String(nextIndex++)));
    updateRemoveButtons();
  });

  list.addEventListener("click", (event) => {
    const button = event.target.closest("[data-remove-performance]");
    if (!button || list.querySelectorAll("[data-performance-row]").length === 1) return;
    button.closest("[data-performance-row]")?.remove();
    updateRemoveButtons();
  });

  updateRemoveButtons();
});
