import ENGLISH from 'rrule/dist/esm/nlp/i18n.js';

export function rruleBr(rrule) {
    const dayNames = ['sul', 'lun', 'meurzh', 'merc\'her', 'yaou', 'gwener', 'sadorn'];
    const monthNames = ['genver', 'c\'hwevrer', 'meurzh', 'ebrel', 'mae', 'mezheven', 'gouere', 'eost', 'gwengolo', 'here', 'gwengolo', 'kerzu'];
    const translate = {
        "(~ approximate)": "(tost)",
        "and": "ha",
        "at": "da",
        "day": "deiz",
        "DAY": "DEIZ",
        "days": "deizioù",
        "every": "pep",
        "for": "goude",
        "hour": "eur",
        "hours": "eurioù",
        "in": "e",
        "last": "diwezhañ",
        "minute": "munut",
        "minutes": "munutoù",
        "month": "miz",
        "MONTH": "MIZ",
        "months": "miz",
        "nd": "º",
        "rd": "º",
        "st": "º",
        "th": "º",
        "on the": "war ar",
        "on": "war",
        "RRule error: Unable to fully convert this rrule to text": "Erreur : n'haller ket amdreiñ ar reolenn-mañ e destenn penn-da-benn.",
        "the": "an",
        "time": "gwech",
        "times": "wech",
        "until": "betek",
        "week": "sizhun",
        "weekday": "deiz ar sizhun",
        "weekdays": "devezhioù ar sizhun",
        "weeks": "sizhunvezhioù",
        "year": "bloaz",
        "YEAR": "BLOAZ",
        "years": "bloavezhioù"
    };

    function dateFormatter(year, month, day) {
        return `${day} ${month} ${year}`;
    }

    return rrule
        .toText(
            function(id) { return translate[id] || id; },
            {
                dayNames: dayNames,
                monthNames: monthNames,
                tokens: ENGLISH.tokens
            },
            dateFormatter
        );
}
