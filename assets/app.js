import * as bootstrap from 'bootstrap';
import TomSelect from 'tom-select';
import EmblaCarousel from 'embla-carousel';
import { addPrevNextBtnsClickHandlers } from './plugins/EmblaCarouselArrowButtons';
import Autoplay from 'embla-carousel-autoplay';
import Shepherd from 'shepherd.js';
import Routing from 'fos-router';
import { RRule } from 'rrule';
import { rruleFrFr } from "./plugins/rruleFrFr";
import { rruleBr } from "./plugins/rruleBr";

import routes from '../public/js/fos_js_routes.json';
Routing.setRoutingData(routes);

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

function rRuleToText(rrule, locale) {
    let text;
    switch (locale) {
        case 'fr': text = rruleFrFr(rrule); break;
        case 'br': text = rruleBr(rrule); break;
        default: text = rrule.toText(); break;
    }

    return String(text).charAt(0).toUpperCase() + String(text).slice(1);
}

document.addEventListener('DOMContentLoaded', function () {
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

    const ownerModals = document.querySelectorAll('.owner-modal');
    if (ownerModals) {
        ownerModals.forEach(ownerModal => {
            const formSearchMember = ownerModal.querySelector('form[data-search]');
            const searchInput = formSearchMember.querySelector('.search-member');
            const hiddenInput = formSearchMember.querySelector('input[name="user"]');
            const resultsDiv = ownerModal.querySelector('#resultsMember');
            const searchUrl = formSearchMember.getAttribute('data-search');
            let timeout = null;

            searchInput.addEventListener('input', () => {
            const query = searchInput.value.trim();
            clearTimeout(timeout);
            resultsDiv.innerHTML = '';

            if (query.length < 2) return;

            timeout = setTimeout(() => {
                fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
                    .then(response => {
                        if (!response.ok) throw new Error('HTTP error ' + response.status);

                        return response.json();
                    })
                    .then(users => {
                        resultsDiv.innerHTML = '';

                        if (!Array.isArray(users) || users.length === 0) {
                            resultsDiv.innerHTML = '<p>Aucun utilisateur trouvé.</p>';

                            return;
                        }

                        users.forEach(user => {
                            const button = document.createElement('button');
                            button.type = 'submit';
                            button.className = 'btn btn-link';
                            button.textContent = `${user.firstname || ''} ${user.lastname || ''} (${user.email})`;
                            button.addEventListener('click', e => {
                                e.preventDefault();

                                hiddenInput.value = user.id;
                                formSearchMember.submit();
                            });

                            resultsDiv.appendChild(button);
                        });
                    })
                    .catch(() => {
                        resultsDiv.innerHTML = '<p>Erreur lors de la recherche.</p>';
                    });
            }, 300);
        });
        });
    }

    const emblaCarousels = document.querySelectorAll('.embla');
    if (emblaCarousels) {
        const OPTIONS = { loop: true, slidesToScroll: 'auto' }

        emblaCarousels.forEach(emblaNode => {
            const viewportNode = emblaNode.querySelector('.embla__viewport');
            const prevBtnNode = emblaNode.querySelector('.embla__button--prev');
            const nextBtnNode = emblaNode.querySelector('.embla__button--next');

            const emblaApi = EmblaCarousel(viewportNode, OPTIONS, [Autoplay({
                delay: 4000,
                stopOnInteraction: true,
                stopOnMouseEnter: true,
            })]);

            const onNavButtonClick = (emblaApi) => {
                const autoplay = emblaApi?.plugins()?.autoplay;
                if (!autoplay) return;

                const resetOrStop =
                    autoplay.options.stopOnInteraction === false
                        ? autoplay.reset
                        : autoplay.stop;

                resetOrStop();
            };

            const removePrevNextBtnsClickHandlers = addPrevNextBtnsClickHandlers(
                emblaApi,
                prevBtnNode,
                nextBtnNode,
                onNavButtonClick
            );

            emblaApi.on('destroy', removePrevNextBtnsClickHandlers);
        });
    }

    const shepherdTrigger = document.getElementById('shepherdTrigger');
    if (shepherdTrigger) {
        const tourHomepage = new Shepherd.Tour({
            defaultStepOptions: {
                classes: 'shepherd-container shadow-md bg-purple-dark',
                scrollTo: { behavior: 'smooth', block: 'center' },
            },
            exitOnEsc: true,
            keyboardNavigation: true,
            modalOverlayOpeningPadding: 50,
            modalOverlayOpeningRadius: 5,
            useModalOverlay: true,
        });
        // Intro
        tourHomepage.addStep({
            buttons: [
                {
                    text: 'Démarrer la visite <i class="fa fa-arrow-right"></i>',
                    action: tourHomepage.next,
                    classes: 'btn btn-info text-white',
                },
            ],
            cancelIcon: {
                enabled: true,
            },
            id: 'step-intro',
            title: 'Visite guidée',
            text: 'Découvrez les principales fonctionnalités de ce portail pas-à-pas grâce à cette visite guidée.<br><br><small><em>Vous pouvez utiliser les flèches ← → de votre clavier pour naviguer et la touche "Echap" pour sortir de la visite.</em></small>',
        });
        // Connexion / Inscription
        tourHomepage.addStep({
            attachTo: {
                element: '.shepherd-user-login',
                on: 'bottom',
            },
            buttons: [
                {
                    action: tourHomepage.back,
                    classes: 'btn btn-info text-white',
                    text: '<i class="fa fa-arrow-left"></i> Introduction',
                },
                {
                    action: tourHomepage.next,
                    classes: 'btn btn-info text-white',
                    text: 'Suite <i class="fa fa-arrow-right"></i>',
                },
            ],
            cancelIcon: {
                enabled: true,
            },
            extraHighlights: ['.shepherd-user-register'],
            id: 'step-user-login',
            scrollTo: { behavior: 'smooth', block: 'nearest' },
            title: 'Inscription / Connexion',
            text: 'En vous inscrivant sur ce portail, vous pourrez être informé des nouveautés sur les associations de votre choix.',
        });
        // Agenda des 30 prochains jours
        tourHomepage.addStep({
            attachTo: {
                element: '#agendaContainer',
                on: 'top',
            },
            buttons: [
                {
                    action: tourHomepage.back,
                    classes: 'btn btn-info text-white',
                    text: '<i class="fa fa-arrow-left"></i> Revenir en arrière',
                },
                {
                    action: tourHomepage.next,
                    classes: 'btn btn-info text-white',
                    text: 'Suite <i class="fa fa-arrow-right"></i>',
                },
            ],
            cancelIcon: {
                enabled: true,
            },
            id: 'step-agenda',
            title: 'Événements des 30 prochains jours',
            text: 'Les événements des 30 prochains jours ont été créés par les associations et sont visibles ici dans l\'ordre chronologique.',
        });
        // Sélecteur du mode agenda
        tourHomepage.addStep({
            attachTo: {
                element: '#eventsViewSelector',
                on: 'left',
            },
            buttons: [
                {
                    action: tourHomepage.back,
                    classes: 'btn btn-info text-white',
                    text: '<i class="fa fa-arrow-left"></i> Revenir en arrière',
                },
                {
                    action: tourHomepage.next,
                    classes: 'btn btn-info text-white',
                    text: 'Suite <i class="fa fa-arrow-right"></i>',
                },
            ],
            cancelIcon: {
                enabled: true,
            },
            id: 'step-agenda-selecteur',
            title: 'Sélecteur de vue pour l\'agenda',
            text: 'Choisissez parmi les vues "mosaïque", "liste" ou "calendrier" pour votre confort. Ce choix sera conservé pour vos prochaines visites.',
        });
        // Liste des associations
        tourHomepage.addStep({
            attachTo: {
                element: '#listAssociations',
                on: 'top',
            },
            buttons: [
                {
                    action: tourHomepage.back,
                    classes: 'btn btn-info text-white',
                    text: '<i class="fa fa-arrow-left"></i> Revenir en arrière',
                },
                {
                    action: tourHomepage.next,
                    classes: 'btn btn-info text-white',
                    text: 'Suite <i class="fa fa-arrow-right"></i>',
                },
            ],
            cancelIcon: {
                enabled: true,
            },
            id: 'step-list-asso',
            scrollTo: { behavior: 'smooth', block: 'nearest' },
            title: 'Liste des associations',
            text: 'Cette liste des associations, par ordre alphabétique, se veut exhaustive et permet de retrouver rapidement une association.',
        });
        // Détail association active
        tourHomepage.addStep({
            attachTo: {
                element: '#listAssociations .list-group-item.list-group-item-action:not(.wip)',
                on: 'top',
            },
            buttons: [
                {
                    action: tourHomepage.back,
                    classes: 'btn btn-info text-white',
                    text: '<i class="fa fa-arrow-left"></i> Revenir en arrière',
                },
                {
                    action: tourHomepage.next,
                    classes: 'btn btn-info text-white',
                    text: 'Suite <i class="fa fa-arrow-right"></i>',
                },
            ],
            cancelIcon: {
                enabled: true,
            },
            id: 'step-detail-asso',
            title: 'Détail d\'une association',
            text: 'Chaque association est liée à une ou plusieurs catégories. Cliquez sur une ligne pour en consulter les détails.',
        });
        // Détail association en chantier
        tourHomepage.addStep({
            attachTo: {
                element: '#listAssociations .list-group-item.list-group-item-action.wip',
                on: 'top',
            },
            buttons: [
                {
                    action: tourHomepage.back,
                    classes: 'btn btn-info text-white',
                    text: '<i class="fa fa-arrow-left"></i> Revenir en arrière',
                },
                {
                    action: tourHomepage.next,
                    classes: 'btn btn-info text-white',
                    text: 'Suite <i class="fa fa-arrow-right"></i>',
                },
            ],
            cancelIcon: {
                enabled: true,
            },
            id: 'step-detail-asso-wip',
            title: 'Détail d\'une association en chantier',
            text: 'Certaines associations n\'ont pas encore été prises en main par leurs membres et sont donc indiquées "en cours de construction". Si vous êtes membre de l\'une d\'elles, n\'hésitez pas à la modifier !',
        });
        // Rechercher
        tourHomepage.addStep({
            attachTo: {
                element: '#searchDropdown',
                on: 'left',
            },
            buttons: [
                {
                    action: tourHomepage.back,
                    classes: 'btn btn-info text-white',
                    text: '<i class="fa fa-arrow-left"></i> Revenir en arrière',
                },
                {
                    action: tourHomepage.next,
                    classes: 'btn btn-info text-white',
                    text: 'Suite <i class="fa fa-arrow-right"></i>',
                },
            ],
            cancelIcon: {
                enabled: true,
            },
            canClickTarget: false,
            id: 'step-search-asso',
            title: 'Chercher une association',
            text: 'Pour retrouver rapidement une association, utilisez la recherche par mots-clés.',
        });
        // Ajouter une association
        tourHomepage.addStep({
            attachTo: {
                element: '#addAssociation',
                on: 'right',
            },
            buttons: [
                {
                    action: tourHomepage.back,
                    classes: 'btn btn-info text-white',
                    text: '<i class="fa fa-arrow-left"></i> Revenir en arrière',
                },
                {
                    action: tourHomepage.next,
                    classes: 'btn btn-info text-white',
                    text: 'Suite et fin <i class="fa fa-arrow-right"></i>',
                },
            ],
            cancelIcon: {
                enabled: true,
            },
            id: 'step-add-asso',
            title: 'Créer une association',
            text: 'Si votre association n\'est pas encore créée sur ce portail, vous pouvez facilement l\'ajouter.',
        });
        // Terminer le tour
        tourHomepage.addStep({
            buttons: [
                {
                    action: tourHomepage.back,
                    classes: 'btn btn-info text-white',
                    text: '<i class="fa fa-arrow-left"></i> Revenir en arrière',
                },
                {
                    action: tourHomepage.complete,
                    classes: 'btn btn-info text-white',
                    text: 'Terminer <i class="fa fa-check"></i>',
                },
            ],
            cancelIcon: {
                enabled: true,
            },
            id: 'step-add-asso',
            title: 'Fin de la visite',
            text: 'La visite de cette page est terminée, à bientôt !',
        });
        shepherdTrigger.addEventListener('click', () => {
            if ('homepage' === shepherdTrigger.dataset.page) {
                tourHomepage.start();
            }
        });
    }

    const confirmActions = document.querySelectorAll('.confirm-action');
    confirmActions.forEach(confirmAction => {
        confirmAction.addEventListener('click', e => {
            if (!confirm(confirmAction.dataset.confirm)) e.preventDefault();
        });
    });

    const removeContactButtons = document.querySelectorAll('.remove-contact');
    if (removeContactButtons) {
        removeContactButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                this.closest('.contact-entry').remove();
            });
        });
    }

    const collectionHolder = document.getElementById('contacts-collection');
    if (collectionHolder) {
        const addButton = document.getElementById('add-contact');
        let index = collectionHolder.children.length;
        addButton.addEventListener('click', function () {
            const prototype = collectionHolder.dataset.prototype;
            const newForm = prototype.replace(/__name__/g, index);
            const div = document.createElement('div');
            div.classList.add('contact-entry');
            div.innerHTML = newForm;
            collectionHolder.appendChild(div);
            index++;

            div.querySelector('.remove-contact').addEventListener('click', function () {
                div.remove();
            });
        });
    }

    const categoryFilters = document.querySelectorAll('.category-filter-list');
    const categories = document.querySelectorAll('[data-categories]');
    if (categoryFilters && categories) {
        categoryFilters.forEach(categoryFilter => {
            categoryFilter.addEventListener('click', () => {
                const triggerButton = categoryFilter.closest('.dropdown').querySelector('button');
                triggerButton.innerHTML = categoryFilter.innerHTML;

                const url = Routing.generate('association_filter', {q: categoryFilter.dataset.category});
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

                        return response.text();
                    })
                    .then(data => {
                        if (data.length !== 0) {
                            document.getElementById('associationsList').innerHTML = data;
                        }
                    })
                    .catch(error => {
                        console.error('Erreur dans l’appel Ajax : ', error);
                    })
                ;
            });
        });
    }

    const hiddenRecurrenceRule = document.querySelector('[name="event[recurrenceRule]"]');
    if (hiddenRecurrenceRule) {
        const presetSelect = document.getElementById('recurrence-preset');
        const customBlock = document.getElementById('recurrence-custom');
        const intervalInput = document.getElementById('recurrence-interval');
        const freqSelect = document.getElementById('recurrence-freq');
        const byDayCheckboxes = document.querySelectorAll('.recurrence-byday');
        const endTypeSelect = document.getElementById('recurrence-end-type');
        const countInput = document.getElementById('recurrence-count');
        const untilInput = document.getElementById('recurrence-until');
        const applyBtn = document.getElementById('recurrence-apply');
        const summaryBtn = document.getElementById('recurrence-summary');

        // --- helpers -------------------------------------------------------

        const WEEKDAYS = {
            MO: RRule.MO,
            TU: RRule.TU,
            WE: RRule.WE,
            TH: RRule.TH,
            FR: RRule.FR,
            SA: RRule.SA,
            SU: RRule.SU,
        };
        const FREQ_VALUES = {
            3: 'DAILY',
            2: 'WEEKLY',
            1: 'MONTHLY',
            0: 'YEARLY',
        };

        function toggleCustom(show) {
            customBlock.classList.toggle('d-none', !show);
        }

        function toggleEndInputs(type) {
            countInput.classList.toggle('d-none', type !== 'COUNT');
            untilInput.classList.toggle('d-none', type !== 'UNTIL');
        }

        // --- preset logic --------------------------------------------------

        presetSelect.addEventListener('change', () => {
            toggleCustom(presetSelect.value === 'CUSTOM');
        });

        // --- end type logic ------------------------------------------------

        endTypeSelect.addEventListener('change', () => {
            toggleEndInputs(endTypeSelect.value);
        });

        // --- apply recurrence ----------------------------------------------

        applyBtn.addEventListener('click', () => {
            let options = {};

            if (!presetSelect.value) {
                hiddenRecurrenceRule.value = '';
                summaryBtn.textContent = summaryBtn.dataset.default;

                return;
            }

            if (presetSelect.value && presetSelect.value !== 'CUSTOM') {
                options.freq = RRule[presetSelect.value];
                options.interval = 1;
            } else {
                options.freq = RRule[freqSelect.value];
                options.interval = parseInt(intervalInput.value, 10) || 1;

                const byweekday = [];
                byDayCheckboxes.forEach(cb => {
                    if (cb.checked) {
                        byweekday.push(WEEKDAYS[cb.value]);
                    }
                });

                if (byweekday.length > 0) {
                    options.byweekday = byweekday;
                }

                if (endTypeSelect.value === 'COUNT') {
                    options.count = parseInt(countInput.value, 10);
                }

                if (endTypeSelect.value === 'UNTIL' && untilInput.value) {
                    options.until = new Date(untilInput.value);
                }
            }

            const rule = new RRule(options);
            hiddenRecurrenceRule.value = rule.toString();
            summaryBtn.textContent = rRuleToText(rule, summaryBtn.dataset.locale);
        });

        // --- edition: hydrate UI from existing RRULE -----------------------

        if (hiddenRecurrenceRule.value) {
            const rule = RRule.fromString(hiddenRecurrenceRule.value);
            const o = rule.origOptions;

            presetSelect.value = 'CUSTOM';
            toggleCustom(true);

            freqSelect.value = FREQ_VALUES[o.freq];
            intervalInput.value = o.interval || 1;

            if (o.byweekday) {
                const days = Array.isArray(o.byweekday) ? o.byweekday : [o.byweekday];
                days.forEach(d => {
                    const cb = document.querySelector(
                        `.recurrence-byday[value="${d.toString()}"]`
                    );
                    if (cb) cb.checked = true;
                });
            }

            if (o.count) {
                endTypeSelect.value = 'COUNT';
                toggleEndInputs('COUNT');
                countInput.value = o.count;
            }

            if (o.until) {
                endTypeSelect.value = 'UNTIL';
                toggleEndInputs('UNTIL');
                untilInput.value = o.until.toISOString().substring(0, 10);
            }

            summaryBtn.textContent = rRuleToText(rule, summaryBtn.dataset.locale);
        }
    }

    const eventRecurrence = document.getElementById('event-recurrence');
    if (eventRecurrence) {
        const rruleString = eventRecurrence.dataset.rrule;

        try {
            const rule = RRule.fromString(rruleString);
            eventRecurrence.textContent = rRuleToText(rule, eventRecurrence.dataset.locale);
        } catch (e) {
            eventRecurrence.textContent = '';
        }
    }

    const settingsPage = document.querySelector('body[data-route="admin_settings"]');
    if (settingsPage) {
        const tbody = document.getElementById('settings-collection');
        const addButton = document.getElementById('add-setting');
        let index = tbody.children.length;

        function bindRemoveButtons(scope = document) {
            scope.querySelectorAll('.remove-setting').forEach(btn => {
                btn.onclick = () => btn.closest('tr').remove();
            });
        }

        bindRemoveButtons();

        addButton.addEventListener('click', () => {
            const template = addButton.dataset.template;
            const html = template.replace(/__name__/g, index++);
            const temp = document.createElement('tbody');
            temp.innerHTML = html;
            const row = temp.firstElementChild;
            tbody.appendChild(row);
            bindRemoveButtons(row);
        });
    }
});
