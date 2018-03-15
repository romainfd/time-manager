// Classe pour gérer les dates
var dateObj = class dateObj {
    // iddate de la forme 2018-03-09
    constructor(iddate) {
        this.iddate = iddate;
        this.dateClean = this.cleanDisplay();
    }

    // on parse 2018-03-09 sur 
    cleanDisplay() {
        var date = new Date(this.iddate);

        var datejour = date.getDate();
        var tabMois = ["Jan.", "Fev.", "Mars", "Avr.", "Mai", "Juin",
            "Juil.", "Août", "Sept.", "Oct.", "Nov.", "Dec."
        ];
        var mois = tabMois[date.getMonth()];
        var tabJour = ["dim.", "lun.", "mar.", "mer.", "jeu.",
            "ven.", "sam."
        ];
        var jour = tabJour[date.getDay()];

        return jour + " " + datejour + " " + mois;
    }

    createByAddDays(nbJours) {
        var newDate = new Date(this.iddate);
        newDate.setDate(newDate.getDate() + nbJours);
        return new dateObj(this.formatDateToIdDate(newDate));
    }

    formatDateToIdDate(date) {
        var d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();

        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;

        return [year, month, day].join('-');
    }

    /*
    Toutes les dates autour iddate avec -deltaDebut dates avant et deltaFin date après
    ATTENTION : deltaDebut est négatif
     */
    datesSpan(deltaDebut, deltaFin) {
        var dates = [];
        for (var i = deltaDebut; i <= deltaFin; i++) {
            dates.push(this.createByAddDays(i));
        }
        return dates;
    }
}