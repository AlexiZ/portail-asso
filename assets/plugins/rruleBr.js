import ENGLISH from 'rrule/dist/esm/nlp/i18n.js';

export function rruleBr(rrule, hiddenYear = null, from = null) {
    const dayNames = ['sul', 'lun', 'meurzh', 'merc\'her', 'yaou', 'gwener', 'sadorn'];
    const monthNames = ['genver', 'c\'hwevrer', 'meurzh', 'ebrel', 'mae', 'mezheven', 'gouere', 'eost', 'gwengolo', 'here', 'du', 'kerzu'];
    const translate = {
        "(~ approximate)": "(tost)",
        "and": "ha",
        "at": "da",
        "day": "deiz",
        "DAY": "DEIZ",
        "days": "deizioù",
        "every": "pep",
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
        return year === hiddenYear ? `${day} ${month}` : `${day} ${month} ${year}`;
    }

    if (from) {
        const start = dateFormatter(from[0], monthNames[from[1]], from[2]);
        translate.until = `eus ${start} betek`;
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
