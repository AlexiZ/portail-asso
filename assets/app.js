import * as bootstrap from 'bootstrap';
import TomSelect from 'tom-select';
import Routing from 'fos-router';

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
                const modalElement = document.getElementById('revisionPreviewModal');
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

    const associationAutocompleteField = document.querySelector('body[data-route="association_pre_new"] input[type="text"][name="association[name]"]');
    if (associationAutocompleteField) {
        associationAutocompleteField.addEventListener('input', (event) => {
            const value = event.target.value.trim();
            const resultsDiv = document.querySelector('#results');

            if (value.length > 2) {
                const resultsDivConfirm = resultsDiv.querySelector('#confirm');
                resultsDivConfirm.dataset.name = '?name='+value;

                const url = Routing.generate('association_pre_new', {q: value});
                fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Erreur HTTP : ' + response.status);
                        }

                        return response.json();
                    })
                    .then(data => {
                        const query = associationAutocompleteField.value.trim();
                        const resultsDivContent = resultsDiv.querySelector('#content');

                        resultsDivContent.innerHTML = '';
                        resultsDiv.classList.remove('d-none');

                        if (data.length === 0) {
                            const p = document.createElement('p');
                            p.innerHTML = '<em>Aucune page association ne porte ce nom.</em>';
                            resultsDivContent.appendChild(p);

                            return;
                        }

                        const div = document.createElement('div');
                        div.classList.add('list-group');

                        data.forEach(association => {
                            const a = document.createElement('a');
                            a.classList.add('list-group-item', 'list-group-item-action');
                            a.href = Routing.generate('association_show', {'slug': association.slug});

                            const regex = new RegExp(`(${query})`, 'gi');
                            a.innerHTML = association.name.replace(regex, '<mark>$1</mark>');

                            div.appendChild(a);
                        });

                        resultsDivContent.appendChild(div);
                    })
                    .catch(error => {
                        console.error('Erreur dans l’appel Ajax : ', error);
                    })
                ;
            } else {
                resultsDiv.classList.add('d-none');
            }
        });
    }

    const eventsViewSelector = document.getElementById('eventsViewSelector');
    if (eventsViewSelector) {
        eventsViewSelector.addEventListener('change', () => {
            const eventsViews = document.querySelectorAll('[data-events]');
            const view = eventsViewSelector.value;
            localStorage.setItem('events-view', view);
            if (view && eventsViews) {
                eventsViews.forEach(eventsView => {
                    eventsView.classList.add('d-none');
                    if (view === eventsView.dataset.events) {
                        eventsView.classList.remove('d-none');
                    }
                });
            }
        });

        const eventsView = localStorage.getItem('events-view');
        if (eventsView) {
            eventsViewSelector.value = eventsView;
            eventsViewSelector.dispatchEvent(new Event('change'));
        }
    }

    const calendarEventsLink = document.querySelectorAll('.calendar .event-link.has-element');
    if (calendarEventsLink) {
        calendarEventsLink.forEach(calendarEventLink => {
            const eventDate = calendarEventLink.dataset.date;
            calendarEventLink.addEventListener('click', () => {
                calendarEventsLink.forEach(otherCalendarEventLink => {
                    otherCalendarEventLink.classList.remove('selected');
                });
                calendarEventLink.classList.add('selected');

                const allEventsDetails = document.querySelectorAll('.calendar .event-list');
                if (allEventsDetails) {
                    allEventsDetails.forEach(allEventDetails => {
                        allEventDetails.classList.add('d-none');
                        if (eventDate === allEventDetails.dataset.date) {
                            allEventDetails.classList.remove('d-none');
                        }
                    });
                }
            });
        });
    }
});
