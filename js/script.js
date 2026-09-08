document.addEventListener("DOMContentLoaded", function () {

    const header = document.querySelector(".site-header");
    const menuButton = document.querySelector(".menu-toggle");
    const menu = document.querySelector(".main-nav");
    const topBtn = document.getElementById("topBtn");

    /* Small effect for the header when scrolling */
    function updateScroll() {
        if (header) {
            header.classList.toggle("scrolled", window.scrollY > 50);
        }

        if (topBtn) {
            topBtn.style.display = window.scrollY > 450 ? "grid" : "none";
        }
    }

    window.addEventListener("scroll", updateScroll);
    updateScroll();


    /* Mobile navigation */
    if (menuButton && menu) {
        menuButton.addEventListener("click", function () {
            const isOpen = menu.classList.toggle("open");
            menuButton.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });

        menu.querySelectorAll("a").forEach(function (link) {
            link.addEventListener("click", function () {
                menu.classList.remove("open");
                menuButton.setAttribute("aria-expanded", "false");
            });
        });
    }


    /* Reveal sections while scrolling */
    const reveals = document.querySelectorAll(".reveal");

    if ("IntersectionObserver" in window) {
        const revealObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add("visible");
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });

        reveals.forEach(function (item) {
            revealObserver.observe(item);
        });
    } else {
        reveals.forEach(function (item) {
            item.classList.add("visible");
        });
    }


    /* Back to top */
    if (topBtn) {
        topBtn.addEventListener("click", function () {
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });
    }


    /* Merch popup */
    const modal = document.getElementById("productModal");
    const modalProduct = document.getElementById("modalProduct");
    const closeModal = document.querySelector(".modal-close");

    document.querySelectorAll(".product-btn").forEach(function (button) {
        button.addEventListener("click", function () {
            if (!modal) return;

            if (modalProduct) {
                modalProduct.textContent = button.dataset.product || "PRODUCT";
            }

            modal.classList.add("open");
            modal.setAttribute("aria-hidden", "false");
        });
    });

    function hideModal() {
        if (!modal) return;
        modal.classList.remove("open");
        modal.setAttribute("aria-hidden", "true");
    }

    if (closeModal) {
        closeModal.addEventListener("click", hideModal);
    }

    if (modal) {
        modal.addEventListener("click", function (event) {
            if (event.target === modal) {
                hideModal();
            }
        });
    }

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            hideModal();
        }
    });


    /* Testimonial slider */
    const testimonials = Array.from(document.querySelectorAll(".testimonial"));
    const nextButton = document.querySelector(".next");
    const prevButton = document.querySelector(".prev");
    const counter = document.querySelector(".slider-count");

    let current = 0;

    function showTestimonial(index) {
        if (!testimonials.length) return;

        current = (index + testimonials.length) % testimonials.length;

        testimonials.forEach(function (item, i) {
            item.classList.toggle("active", i === current);
        });

        if (counter) {
            counter.textContent =
                String(current + 1).padStart(2, "0") +
                " / " +
                String(testimonials.length).padStart(2, "0");
        }
    }

    if (testimonials.length) {
        showTestimonial(0);

        if (nextButton) {
            nextButton.addEventListener("click", function () {
                showTestimonial(current + 1);
            });
        }

        if (prevButton) {
            prevButton.addEventListener("click", function () {
                showTestimonial(current - 1);
            });
        }

        setInterval(function () {
            showTestimonial(current + 1);
        }, 6000);
    }

});
