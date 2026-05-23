const body = document.body;
const menuButton = document.querySelector("[data-menu-button]");
const nav = document.querySelector("[data-nav]");
const header = document.querySelector("[data-header]");

menuButton?.addEventListener("click", () => {
  const isOpen = body.classList.toggle("nav-open");
  menuButton.setAttribute("aria-expanded", String(isOpen));
});

nav?.addEventListener("click", (event) => {
  if (event.target instanceof HTMLAnchorElement) {
    body.classList.remove("nav-open");
    menuButton?.setAttribute("aria-expanded", "false");
  }
});

const setHeaderState = () => {
  header?.toggleAttribute("data-scrolled", window.scrollY > 8);
  body.classList.toggle("show-floating", window.scrollY > 420);
};

setHeaderState();
window.addEventListener("scroll", setHeaderState, { passive: true });
