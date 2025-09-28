import 'bootstrap';
import TomSelect from 'tom-select';

import './styles/app.scss';

function initTomSelects() {
    document.querySelectorAll('select.js-tomselect').forEach(element => {
        if (element.tomselect) return;

        new TomSelect(element, {
            create: false, // interdit d’ajouter de nouvelles valeurs
            plugins: { remove_button: { title: 'Retirer' } }, // joli "x" sur les tags
            maxOptions: 200,
        });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const wysiwygs = document.querySelectorAll('textarea.wysiwyg');
    if (wysiwygs) {
        wysiwygs.forEach((el) => {
            CKEDITOR.plugins.loaded['version'] = null;
            CKEDITOR.replace(el, {
                fullPage: false,
                versionCheck: false,
                allowedContent: true,
                removePlugins: 'elementspath',
                extraPlugins: 'colorbutton,colordialog,justify',
                language: 'fr',
                height: el.dataset.height || 300,
                toolbar: [
                    {name: 'clipboard', items: ['Undo', 'Redo']},
                    {name: 'basicstyles', items: ['Bold', 'Italic', 'Underline']},
                    {name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent']},
                    {name: 'alignment', items: ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock']},
                    {name: 'links', items: ['Link', 'Unlink']},
                    {name: 'insert', items: ['Image', 'Table']},
                    {name: 'colors', items: ['TextColor', 'BGColor']},
                    {name: 'tools', items: ['Maximize', 'Source']}
                ]
            });

            CKEDITOR.instances[el.id].on('instanceReady', function (evt) {
                if (!evt.editor.getData().trim() && el.classList.contains('page-template')) {
                    evt.editor.setData(`
                    <h2>Description</h2>
                    <p>Décrivez votre association...</p>

                    <h2>Autres informations utiles</h2>
                    <p>Ajoutez ici toute information que vous jugez utile.</p>
                `);
                }
            });
        });
    }

    const backToTop = document.getElementById("btn-back-to-top");
    if (backToTop) {
        // When the user scrolls down 20px from the top of the document, show the button
        window.onscroll = function () {
            scrollFunction();
        };

        function scrollFunction() {
            if (
                document.body.scrollTop > 20 ||
                document.documentElement.scrollTop > 20
            ) {
                backToTop.style.display = "block";
            } else {
                backToTop.style.display = "none";
            }
        }

        // When the user clicks on the button, scroll to the top of the document
        backToTop.addEventListener("click", toTop);

        function toTop() {
            document.body.scrollTop = 0;
            document.documentElement.scrollTop = 0;
        }
    }

    const revisionPreviews = document.querySelectorAll('.revision-preview');
    if (revisionPreviews) {
        revisionPreviews.forEach(revisionPreview => {
            revisionPreview.addEventListener('click', () => {
                const url = revisionPreview.dataset.href;
                const modalElement = document.getElementById('revision-preview-modal');
                const modalBody = modalElement.querySelector('.modal-body');

                fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Erreur HTTP ${response.status}`);
                        }

                        return response.json();
                    })
                    .then(html => {
                        modalBody.innerHTML = html;

                        const modal = new bootstrap.Modal(modalElement);
                        modal.show();
                    })
                    .catch(error => {
                        modalBody.innerHTML = `<div class="alert alert-danger">
                            Impossible de charger la prévisualisation.<br>
                            ${error.message}
                        </div>`;
                        const modal = new bootstrap.Modal(modalElement);
                        modal.show();
                    });
            });
        });
    }

    initTomSelects();

    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            mutation.addedNodes.forEach((node) => {
                if (!(node instanceof HTMLElement)) return;

                // si un <select> est ajouté directement
                if (node.matches?.('select.js-tomselect')) {
                    initTomSelects(node.parentNode || document);
                }

                // si un container est ajouté avec des <select> à l’intérieur
                if (node.querySelectorAll) {
                    initTomSelects(node);
                }
            });
        }
    });
    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });
});
