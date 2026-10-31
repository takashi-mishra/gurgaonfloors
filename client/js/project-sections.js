document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("projectEnquiryModal");
    const form = document.getElementById("projectEnquiryForm");
    const selectedName = document.getElementById("selectedProjectName");
    const message = document.getElementById("enquiryMessage");

    if (!modal || !form) return;

    const openModal = (projectName) => {
        selectedName.textContent = projectName || "Selected Property";

        modal.classList.add("active");
        modal.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";

        message.textContent = "";
    };

    const closeModal = () => {
        modal.classList.remove("active");
        modal.setAttribute("aria-hidden", "true");
        document.body.style.overflow = "";
    };

    document.querySelectorAll("[data-enquiry]").forEach((button) => {
        button.addEventListener("click", () => {
            openModal(button.dataset.project);
        });
    });

    document.querySelectorAll("[data-close-enquiry]").forEach((button) => {
        button.addEventListener("click", closeModal);
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && modal.classList.contains("active")) {
            closeModal();
        }
    });

    form.addEventListener("submit", (event) => {
        event.preventDefault();

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        message.textContent =
            "Form UI is working! Lead saving will be connected in the next step.";

        // Actual lead storage will be implemented using PHP + JSON later.
    });
});