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