document.addEventListener("DOMContentLoaded", () => {
  console.log("Kasi2Kasi app.js loaded");

  const toggle = document.querySelector(".nav-toggle");
  const links = document.querySelector(".nav-links");

  console.log("Toggle found:", toggle);
  console.log("Links found:", links);

  if (toggle && links) {
    toggle.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();

      links.classList.toggle("open");
      toggle.classList.toggle("active");

      const isOpen = links.classList.contains("open");
      toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });

    links.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", () => {
        links.classList.remove("open");
        toggle.classList.remove("active");
      });
    });
  }

  document.querySelectorAll(".auto-hide").forEach((alert) => {
    setTimeout(() => {
      alert.style.opacity = "0";
      alert.style.transition = "opacity 0.4s ease";
      setTimeout(() => alert.remove(), 500);
    }, 3000);
  });
});