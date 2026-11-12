document.addEventListener("DOMContentLoaded", () => {
    const modal = document.getElementById("projectEnquiryModal");
    const modalForm = document.getElementById("projectEnquiryForm");
    const forms = Array.from(
        document.querySelectorAll("#projectEnquiryForm, #projectDetailReachoutForm")
    );
    const selectedName = document.getElementById("selectedProjectName");
    const toast = document.getElementById("leadToast");

    if (!modal || forms.length === 0 || !toast) return;

    const openModal = (projectName) => {
        modalForm.reset();
        modalForm.hidden = false;
        selectedName.textContent = projectName || "Selected Property";
        modalForm.elements.project.value = projectName || "General Property Enquiry";

        toast.hidden = true;
        modal.hidden = false;
        modal.classList.add("active");
        modal.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";

        const message = modalForm.querySelector(".enquiry-message");
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

    forms.forEach((form) => {
        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const submitButton = form.querySelector('[type="submit"]');
            const message = form.querySelector(".enquiry-message");
            submitButton.disabled = true;
            message.textContent = "Sending your enquiry…";
            message.dataset.status = "";

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

                if (form === modalForm) {
                    closeModal();
                    toast.textContent = result.message || "Thank you! Your enquiry has been sent.";
                    toast.hidden = false;
                    window.setTimeout(() => {
                        toast.hidden = true;
                    }, 5000);
                } else {
                    form.reset();
                    message.textContent = result.message || "Thank you! Your enquiry has been sent.";
                    message.dataset.status = "success";
                }
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
});