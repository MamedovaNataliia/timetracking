function TimeTracking() {
    this.ajax_handler = null;
    this.time_interval = null;
    this.satisfactory_count_hours = 7;
    this.popup = null;
}

TimeTracking.prototype.init = function (ajax_handler) {
    this.ajax_handler = ajax_handler;
}
TimeTracking.prototype.show_notify = function (title, content, close) {
    this.popup = new BX.PopupWindow("popup", null, {
        content: content,
        closeIcon: {right: "20px", top: "10px"},//Иконка закрытия
        titleBar: title,
        zIndex: 10000,
        position: top,
        offsetLeft: 0,
        offsetTop: 0,
        draggable: {restrict: true},
        overlay: {backgroundColor: 'black', opacity: '80'},
        buttons: [
            new BX.PopupWindowButton({
                text: close,
                className: "popup-window-button-accept",
                events: {
                    click: function () {
                        this.popupWindow.close();// закрытие окна
                    }
                }
            })
        ]
    });
    this.popup.show();
}
TimeTracking.prototype.getPopupHandler = function () {
    return this.popup;
}
TimeTracking.prototype.send_ajax = function () {
    if (this.ajax_handler !== null) {
        var _this = this;
        BX.ajax({
            url: this.ajax_handler,
            data: {'tracking_data': 'Y'},
            method: 'POST',
            dataType: 'json',
            async: true,
            start: true,
            cache: false,
            onsuccess: function (data) {
                if (data.hours < this.satisfactory_count_hours)
                    var content = 'За вчерашний рабочий день вы отметили ' + data.hours + ' отработанных часов<br>';
                content += 'Нужно отметить не менее 7 рабочих часов!!!';
                _this.show_notify();
            },

        });
    }
}

