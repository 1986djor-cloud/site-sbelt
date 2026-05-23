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

const renderInstagramFeed = (items, container, profileUrl) => {
  if (!items?.length) return;

  const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;",
  }[char]));

  container.innerHTML = items.slice(0, 3).map((item) => {
    const image = item.image || item.thumbnail_url || item.media_url;
    const link = item.link || item.permalink || profileUrl;
    const caption = escapeHtml(item.caption || "Post recente da Sbelt no Instagram");

    return `
      <a class="instagram-card" href="${link}" target="_blank" rel="noopener" aria-label="Abrir post da Sbelt no Instagram">
        <img src="${image}" alt="${caption}" loading="lazy">
        <span>Ver no Instagram</span>
      </a>
    `;
  }).join("");
};

const loadInstagramFeed = async () => {
  const container = document.querySelector("[data-instagram-feed]");
  if (!container) return;

  const profileUrl = container.dataset.profileUrl || "https://www.instagram.com/sbeltbeauty/";

  try {
    const response = await fetch("api/instagram-feed.php", {
      headers: { "Accept": "application/json" },
    });

    if (!response.ok) return;

    const payload = await response.json();
    renderInstagramFeed(payload.items, container, profileUrl);
  } catch {
    // Mantem o fallback visual quando a API ainda nao estiver conectada.
  }
};

loadInstagramFeed();
