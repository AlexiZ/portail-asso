import ENGLISH from 'rrule/dist/esm/nlp/i18n.js';

export function rruleFrFr(rrule) {
    const monthNames = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin',
        'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    const dayNames = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    const translate = {
        "(~ approximate)": "(approximatif)",
        "and": "et",
        "at": "à",
        "day": "jour",
        "DAY": "JOUR",
        "days": "jours",
        "every": "chaque",
        "for": "pendant",
        "hour": "heure",
        "hours": "heures",
        "in": "dans",
        "last": "dernier",
        "minute": "minute",
        "minutes": "minutes",
        "month": "mois",
        "MONTH": "MOIS",
        "months": "MOIS",
        "nd": "º",
        "rd": "º",
        "st": "º",
        "th": "º",
        "on the": "le",
        "on": "le",
        "RRule error: Unable to fully convert this rrule to text": "Erreur : impossible de textualiser cette règle.",
        "the": "le",
        "time": "occurrence",
        "times": "occurrences",
        "until": "jusqu'au",
        "week": "semaine",
        "weekday": "jour de la semaine",
        "weekdays": "jours de la semaine",
        "weeks": "semaines",
        "year": "an",
        "YEAR": "AN",
        "years": "ans"
    };

    function dateFormatter(year, month, day) {
        return `${day} ${month} ${year}`;
    }

    return rrule
        .toText(
            function(id) { return translate[id] || id },
            {
                dayNames: dayNames,
                monthNames: monthNames,
                tokens: ENGLISH.tokens
            },
            dateFormatter
        );
}
