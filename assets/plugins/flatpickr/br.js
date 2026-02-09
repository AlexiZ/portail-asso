(function (global, factory) {
    typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports) :
        typeof define === 'function' && define.amd ? define(['exports'], factory) :
            (global = typeof globalThis !== 'undefined' ? globalThis : global || self, factory(global.br = {}));
}(this, (function (exports) {
    'use strict';

    let fp = typeof window !== "undefined" && window.flatpickr !== undefined
        ? window.flatpickr
        : {
            l10ns: {},
        };
    const Breton = {
        firstDayOfWeek: 1,
        weekdays: {
            shorthand: ["sul", "lun", "meur", "merc", "yao", "gwe", "sad"],
            longhand: [
                "sul",
                "lun",
                "meurzh",
                "merc'her",
                "yaou",
                "gwener",
                "sadorn",
            ],
        },
        months: {
            shorthand: [
                "gen",
                "c'hw",
                "meur",
                "ebr",
                "mae",
                "eve",
                "gou",
                "eos",
                "gwe",
                "her",
                "du",
                "ker",
            ],
            longhand: [
                "genver",
                "c'hwevrer",
                "meurzh",
                "ebrel",
                "mae",
                "even",
                "gouere",
                "eost",
                "gwengolo",
                "here",
                "du",
                "kerzu",
            ],
        },
        ordinal: function (nth) {
            if (nth > 1)
                return "";
            return "añ";
        },
        rangeSeparator: " d'an ",
        weekAbbreviation: "Siz", // Semaine = Sizhun
        scrollTitle: "Riklañ evit kreskiñ ar werzh",
        toggleTitle: "Klikañ evit cheñch",
        time_24hr: true,
    };
    fp.l10ns.br = Breton;
    var br = fp.l10ns;

    exports.Breton = Breton;
    exports.default = br;

    Object.defineProperty(exports, '__esModule', { value: true });
})));
