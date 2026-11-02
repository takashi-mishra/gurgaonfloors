document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("projectEnquiryModal");
    const form = document.getElementById("projectEnquiryForm");
    const selectedName = document.getElementById("selectedProjectName");
    const message = document.getElementById("enquiryMessage");
    const toast = document.getElementById("leadToast");

    if (!modal || !form || !toast) return;

    const openModal = (projectName) => {
        form.reset();
        form.hidden = false;
        selectedName.textContent = projectName || "Selected Property";
        form.elements.project.value = projectName || "General Property Enquiry";

        toast.hidden = true;
        modal.hidden = false;
        modal.classList.add("active");
        modal.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";

        message.textContent = "";
        message.dataset.status = "";
    };

    const closeModal = () => {
        modal.classList.remove("active");
        modal.setAttribute("aria-hidden", "true");
        modal.hidden = true;
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

    form.addEventListener("submit", async (event) => {
        event.preventDefault();

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const submitButton = form.querySelector('[type="submit"]');
        submitButton.disabled = true;
        message.textContent = "Sending your enquiry…";

        try {
            const response = await fetch(form.action, {
                method: "POST",
                body: new FormData(form),
                headers: { Accept: "application/json" },
            });
            const responseText = await response.text();
            let result;

            try {
                result = JSON.parse(responseText);
            } catch {
                throw new Error(
                    "The server returned an invalid response. Please try again or call us."
                );
            }

            if (!response.ok || !result.success) {
                throw new Error(result.message || "We could not send your enquiry. Please try again.");
            }

            closeModal();
            toast.textContent = result.message || "Thank you! Your enquiry has been sent.";
            toast.hidden = false;
            window.setTimeout(() => {
                toast.hidden = true;
            }, 5000);
        } catch (error) {
            message.textContent = error instanceof Error
                ? error.message
                : "We could not send your enquiry. Please try again.";
            message.dataset.status = "error";
        } finally {
            submitButton.disabled = false;
        }
    });
});