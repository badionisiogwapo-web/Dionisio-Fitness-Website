document.addEventListener('DOMContentLoaded', function () {

    const menuButton =
        document.getElementById('mobileMenuBtn');

    const sidebar =
        document.querySelector('.admin-sidebar');

    if (menuButton && sidebar) {

        menuButton.addEventListener('click', function () {

            sidebar.classList.toggle('open');

        });

    }

    const modal =
        document.getElementById('confirmModal');

    const message =
        document.getElementById('confirmMessage');

    const cancelButton =
        document.getElementById('confirmCancel');

    const proceedButton =
        document.getElementById('confirmProceed');

    let selectedLink = null;

    document.querySelectorAll('[data-confirm]').forEach(function (link) {

        link.addEventListener('click', function (event) {

            event.preventDefault();

            selectedLink = link;

            if (message) {
                message.textContent =
                    link.getAttribute('data-confirm');
            }

            if (modal) {
                modal.classList.add('show');
            }

        });

    });

    if (cancelButton) {

        cancelButton.addEventListener('click', function () {

            modal.classList.remove('show');

            selectedLink = null;

        });

    }

    if (proceedButton) {

        proceedButton.addEventListener('click', function () {

            if (selectedLink) {

                window.location.href =
                    selectedLink.href;

            }

        });

    }

    if (modal) {

        modal.addEventListener('click', function (event) {

            if (event.target === modal) {

                modal.classList.remove('show');

                selectedLink = null;

            }

        });

    }

});