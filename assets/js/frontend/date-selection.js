(function ($) {
    $.fn.isInViewport = function () {
        var elementTop = $(this).offset().top;
        var elementBottom = elementTop + $(this).outerHeight();
        var viewportTop = $(window).scrollTop();
        var viewportBottom = viewportTop + $(window).height();
        return elementBottom > viewportTop && elementTop < viewportBottom;
    };

    $.fn.dateSelection = function (options) {
        var e = $(this);
        var calendarContainer = e.find('.datepicker');
        var data = false,
            lastRequest = false,
            done = false,
            slots = false,
            resourceQuantity = false,
            arrival = false,
            arrivalTime = false,
            departure = false,
            departureTime = false,
            default_args = er_datepicker_get_args();

        var settings = $.extend({
            resource: 0,
            arrivalHour: false,
            arrivalMinute: false,
            departureHour: false,
            departureMinute: false,
            minDate: default_args.minDate,
            init: true,
            departure: true,
            numberOfMonths: 1,
            time: false
        }, options);

        if (settings.resource === 0) {
            settings.resource = $('*[name=resource]').val();
        }

        e.find('div.arrival').bind('click', function () {
            init();
        });

        e.find('div.departure').bind('click', function () {
            if(arrival && (arrivalTime || !settings.time)){
                resetDeparture();

                if (!calendarContainer.hasClass('hasDatepicker')) {
                    e.find('.departure .text .date').addClass('important').html(er_date_picker_params.wait);
                    loadData(arrival);
                    generateDatepicker();
                } else {
                    e.find('.time-picker > td > div').slideUp(50, function () {
                        calendarContainer.find('.ui-state-active').removeClass('ui-state-highlight').removeClass('ui-state-active');
                        $(this).closest('.time-picker').remove();
                    });
                }
            }
        });

        $('*[name=resource]').bind('change', function () {
            settings.resource = $(this).val();

            init();
        });

        if (settings.init && e.find('input[name=arrival]').val() === '') {
            init();
        }

        function init() {
            e.find('.calendar').css('display', 'block');

            if (calendarContainer.hasClass('hasDatepicker')) {
                destroyDatePicker(init);
            } else {
                e.find('.text .time').html('');

                data = false;
                slots = false;
                resetArrival();
                resetDeparture();
                e.find('.arrival .text .date').addClass('important').html(er_date_picker_params.wait);

                e.find('input[name=slot]').val(-1);

                loadData(arrival ? arrival : 0);
                generateDatepicker();
            }
        }

        function nextAction() {
            if (!done) {
                if (departure) {
                    if (departureTime || !settings.time) {
                        destroyDatePicker(finish);
                        done = true;
                    } else {
                        generateTimepicker();
                    }
                } else if (arrival) {
                    if (arrivalTime !== false || !settings.time) {
                        if (settings.departure) {
                            e.find('.departure .text .date').addClass('important').html(er_date_picker_params.wait);
                            generateDatepicker();
                        } else {
                            if (calendarContainer.hasClass('hasDatepicker')) {
                                destroyDatePicker(nextAction);
                            } else {
                                if (slots) {
                                    var date_string = data[arrival][arrivalTime][0].departure.split(' ');
                                    var time_string = date_string[1].split(':');

                                    setDeparture(departure_string[0]);
                                    setDepartureTime(time_string[0], time_string[1]);
                                }
                                finish();
                            }
                            done = true;
                        }
                    } else {
                        generateTimepicker();
                    }
                }
            }
        }

        function generateTimepicker() {
            var date = $.datepicker.formatDate("DD, d M yy", calendarContainer.datepicker("getDate"));
            e.find('a.ui-state-active').parent().parent().after('<tr class="time-picker"><td colspan="7"><div>' + date + '<div class="insert"></div></div></td></tr>');

            if (slots) {
                var time_options = '';

                if (arrivalTime !== false) {
                    $.each(data[arrival][arrivalTime], function (t, v) {
                        var date_string = v.departure.split(' '),
                            time_string = date_string[1].split(':'),
                            label = easyFormatTime(time_string[0], time_string[1], er_both_params.time_format),
                            c = v.availability < 1 ? 'unavailable' : ( v.availability < resourceQuantity ? 'partially' : 'available' );

                        if(date_string[0] !== departure){
                            return;
                        }
                        time_options += '<li class="easy-button" data-hour="' + time_string[0] + '" data-minute="' + time_string[1] + '" data-id="' + v.key + '" class="' + c + '">' + label + '</li>';
                    });
                } else {
                    $.each(data[arrival], function (t, _slots) {
                        $.each(_slots, function (k, v) {
                            var time = t.split(":"),
                                label = easyFormatTime(time[0], time[1], er_both_params.time_format),
                                attributes = '',
                                c = v.availability < 1 ? 'unavailable' : (v.availability < resourceQuantity ? 'partially' : 'available');

                            if (!settings.departure) {
                                var departure_date_string = v.departure.split(' ');
                                var departure_time_string = departure_date_string[1].split(':');

                                label += ' -';
                                if (arrival !== departure_date_string[0]) {
                                    label += ' ' + departure_date_string[0];
                                }
                                label += ' ' + easyFormatTime(departure_time_string[0], departure_time_string[1], er_both_params.time_format);

                                attributes += ' data-departure=" ' + departure_date_string[0] + '"';
                                attributes += ' data-departure-hour=" ' + departure_time_string[0] + '"';
                                attributes += ' data-departure-minute=" ' + departure_time_string[1] + '"';
                            }

                            time_options += '<li class="easy-button" data-hour="' + time[0] + '" data-minute="' + time[1] + '" data-id="' + v.key + '" class="' + c + '" ' + attributes + '>' + label + '</li>';

                            //Only display one slot with the same arrival time if we allow picking departure
                            if (settings.departure) {
                                return false;
                            }
                        });
                    });
                }

                if (time_options !== '') {
                    e.find('.time-picker .insert').html('<ul class="option-buttons">' + time_options + '</ul>');
                    e.find('.time-picker > td > div').slideDown(350);

                    e.find('ul.option-buttons li').bind('click', function () {
                        if (arrivalTime !== false) {
                            e.find('input[name=slot]').val($(this).attr('data-id'));
                            setDepartureTime($(this).attr('data-hour'), $(this).attr('data-minute'));
                        } else {
                            if (!settings.departure) {
                                e.find('input[name=slot]').val($(this).attr('data-id'));
                                setDeparture($(this).attr('data-departure'));
                                setDepartureTime($(this).attr('data-departure-hour'), $(this).attr('data-departure-minute'));
                            }
                            setArrivalTime($(this).attr('data-hour'), $(this).attr('data-minute'));
                        }
                        destroyDatePicker(nextAction);
                    });
                }
            } else {
                if (data[departure ? departure : arrival].availability && data[departure ? departure : arrival].availability === parseInt(data[departure ? departure : arrival].availability, 10)) {
                    //Arrival and departure hour and minute selects
                    e.find('div.time-prototype').contents().clone(true).appendTo(e.find('.time-picker .insert')).attr( 'disabled' );

                    var minMax;

                    if (departure) {
                        minMax = data[departure].time;
                    } else {
                        minMax = data[arrival].time;
                    }

                    var first_possible_date = data.first_possible.split(' ');
                    if (first_possible_date[0] === (departure ? departure : arrival)) {
                        var first_possible_time = first_possible_date[1].split(':');
                        minMax[0] = parseInt( minMax[0], 10 ) < first_possible_time[0] ? parseInt(first_possible_time[0], 10 ) : minMax[0];
                    }

                    e.find('.time-picker select[name=time_hour] option').each(function () {
                        var value = parseInt($(this).val());
                        if (value < minMax[0] || value > minMax[1]) {
                            $(this).attr('disabled', true).prop("selected", false).css('display', 'none');
                        } else {
                            $(this).attr('disabled', false).css('display', 'block');
                        }
                    });

                    e.find('.time-picker .apply-time').bind('click', function () {
                        var time = e.find('.time-picker select[name=time_hour]');

                        if (time.length > 0) {
                            var minute = parseInt(e.find('.time-picker select[name=time_minute]').val());

                            if (arrivalTime !== false) {
                                setDepartureTime(time.val(), minute);
                            } else {
                                setArrivalTime(time.val(), minute);

                                if (settings.departure) {
                                    loadData(arrival);
                                }
                            }

                            destroyDatePicker(nextAction);
                        }

                    });
                } else {
                    //Arrival and departure buttons
                    var time_options = '';
                    $.each(data[departure ? departure : arrival].availability, function (k, v) {
                        var string = k.split(' ');
                        var time = string[0].split(':');
                        var c = v < 1 ? 'unavailable' : ( v < resourceQuantity ? 'partially' : 'available' );
                        time_options += '<div class="time-option ' + c + '" data-hour="' + time[0] + '" data-minute="' + time[1] + '">' + easyFormatTime(time[0], time[1]) + '</div>';
                    });

                    e.find('.time-picker .insert').html('<div class="option-buttons">' + time_options + '</div>');

                    e.find('.time-picker .time-option.available, .time-picker .time-option.partially').bind('click', function () {
                        if (arrivalTime !== false) {
                            setDepartureTime($(this).attr('data-hour'), $(this).attr('data-minute'));
                        } else {
                            setArrivalTime($(this).attr('data-hour'), $(this).attr('data-minute'));

                            if (settings.departure) {
                                loadData(arrival);
                            }
                        }
                        destroyDatePicker(nextAction);
                    });
                }

                e.find('.time-picker > td > div').slideDown(350);
            }
        }

        function generateDatepicker(maxDate) {
            var date_format = 'dd.mm.yy';
            if (er_both_params.date_format == 'Y/m/d') date_format = 'yy/mm/dd';
            else if (er_both_params.date_format == 'm/d/Y') date_format = 'mm/dd/yy';
            else if (er_both_params.date_format == 'Y-m-d') date_format = 'yy-mm-dd';
            else if (er_both_params.date_format == 'd-m-Y') date_format = 'dd-mm-yy';

            calendarContainer.datepicker(
                $.extend({
                    minDate: arrival ? arrival : settings.minDate,
                    maxDate: maxDate ? maxDate : null,
                    dateFormat: date_format,
                    numberOfMonths: settings.numberOfMonths,
                    beforeShowDay: checkData,
                    onChangeMonthYear: function (year, month, inst) {
                        if (!slots || (!arrivalTime && settings.time) || (arrival && !settings.time)) {
                            loadData(date_format.replace('dd', '01').replace('mm', month).replace('yy', year));
                        }

                        e.find('div.time').slideUp(300);

                        if (arrival && (arrivalTime || !settings.time)) {
                            resetDeparture();
                            e.find('.departure .text .date').addClass('important').html(er_date_picker_params.wait);
                        } else {
                            resetArrival();
                            e.find('.arrival .text .date').addClass('important').html(er_date_picker_params.wait);
                        }
                    },
                    onSelect: select
                }, default_args)
            ).datepicker("setDate", null).slideDown('300');

            var element = calendarContainer.parent().parent();
            if (resourceQuantity && !element.isInViewport()) {
                $([document.documentElement, document.body]).animate({
                    scrollTop: element.offset().top - 30
                }, 500);
            }

            calendarContainer.find('.ui-datepicker').removeClass('ui-datepicker').addClass('easy-datepicker');
            calendarContainer.find('.ui-state-active').removeClass('ui-state-highlight').removeClass('ui-state-hover').removeClass('ui-state-active');
            $.each(er_date_picker_params.datepicker, function (k, v) {
                calendarContainer.datepicker('option', k, $.parseJSON(v))
            });
        }

        function resetArrival(){
            arrival = false;
            arrivalTime = false;

            e.find('.arrival .text .date').addClass('important').html(er_date_picker_params.select);

            e.find('.arrival .text .time').html('');
            e.find('input[name=arrival]').val('');
            e.find('input[name=departure_hour]').val('');
            e.find('input[name=departure_minute]').val('');
        }

        function resetDeparture(){
            departure = false;
            departureTime = false;
            done = false;

            if(arrival){
                e.find('.departure .text .date').addClass('important').html(er_date_picker_params.select);
            } else {
                e.find('.departure .text .date').removeClass('important').html('&#8212;');
            }

            e.find('.departure .text .time').html('');
            e.find('input[name=departure]').val('');
            e.find('input[name=departure_hour]').val('');
            e.find('input[name=departure_minute]').val('');
            e.find('.departure').removeClass('active');
        }

        function setArrival(dateString){
            arrival = dateString;
            e.find('.arrival .text .date').removeClass('important').html(dateString);
            e.find('input[name=arrival]').val(dateString);
            e.find('input[name=arrival_hour]').val('');
            e.find('input[name=arrival_minute]').val('');
        }

        function setDeparture(dateString){
            departure = dateString;
            e.find('input[name=departure]').val(dateString);
            e.find('.departure').addClass('active');
            e.find('.departure .text .date').removeClass('important').html(dateString);
        }

        function setArrivalTime(hour, minute, label){
            hour = easyAddZero(hour);
            minute = easyAddZero(minute);

            if(!label){
                label = easyFormatTime(hour, minute);
            }

            arrivalTime = hour + ':' + minute;
            e.find('input[name=arrival_hour]').val(hour);
            e.find('input[name=arrival_minute]').val(minute);
            e.find('.arrival .text .time').html(label);
        }

        function setDepartureTime(hour, minute, label){
            hour = easyAddZero(hour);
            minute = easyAddZero(minute);

            if(!label){
                label = easyFormatTime(hour, minute);
            }

            departureTime = hour + ':' + minute;
            e.find('input[name=departure_hour]').val(hour);
            e.find('input[name=departure_minute]').val(minute);
            e.find('.departure .text .time').html(label);
        }

        function select(dateString, instance) {
            if (arrival && (arrivalTime !== false || !settings.time)) {
                if(departure === dateString){
                    resetDeparture();
                    e.find('.time-picker > td > div').slideUp(50, function () {
                        calendarContainer.find('.ui-state-active').removeClass('ui-state-highlight').removeClass('ui-state-active');
                    });
                    return false;
                }

                setDeparture(dateString);

                if (settings.time) {
                    setTimeout(generateTimepicker, 1);
                } else {
                    if (slots) {
                        var date = false;
                        $.each(data[arrival][arrivalTime], function (_, v) {
                            var departure_string = v.departure.split(' ');
                            if (departure === departure_string[0]) {
                                var time_string = departure_string.split(':');
                                setDepartureTime(time_string[0], time_string[1]);
                                return false;
                            }
                        });
                    } else {
                        setDepartureTime(settings.departureHour ? settings.departureHour : data[departure].time[0], settings.departureMinute ? settings.departureMinute : 0);
                    }

                    destroyDatePicker(nextAction);
                }
            } else {
                if (arrival === dateString) {
                    resetArrival();
                    e.find('.time-picker > td > div').slideUp(50, function () {
                        calendarContainer.find('.ui-state-active').removeClass('ui-state-highlight').removeClass('ui-state-active');
                    });
                    return false;
                }

                setArrival(dateString);

                if (settings.time) {
                    setTimeout(generateTimepicker, 1);
                } else {
                    let hour = 12;
                    let minute = settings.arrivalHour ? settings.arrivalMinute : 0;
                    if (slots) {
                        let total = Object.keys(data[arrival])[0].split(':');
                        hour = total[0];
                        minute = total[1];
                    } else {
                        hour = settings.arrivalHour ? settings.arrivalHour : data[arrival].time[1]
                    }

                    setArrivalTime(hour, minute);

                    if (settings.departure) {
                        e.find('.departure .text .date').addClass('important').html(er_date_picker_params.wait);
                        loadData(arrival);
                    }

                    destroyDatePicker(nextAction);
                }
            }
        }

        function destroyDatePicker(callback) {
            calendarContainer.slideUp(350, function () {
                $(this).datepicker("destroy").removeClass("hasDatepicker").removeAttr('id');
                if (callback) callback();
            });
        }

        function checkData(d) {
            if (data) {
                var key = easyFormatDate(d, false);

                if (slots && arrival && arrivalTime !== false) {
                    var iterate;

                    if (arrivalTime !== false && settings.time) {
                        iterate = data[arrival][arrivalTime];
                    } else {
                        iterate = data[arrival][Object.keys(data[arrival])[0]];
                    }

                    var toReturn = [false, 'unavailable', ''];

                    $.each(iterate, function (k, v) {
                        var departure_string = v.departure.split(' ');
                        if (departure_string[0] === key) {
                            toReturn = [true, 'available', ''];
                            return true;
                        }
                    });

                    return toReturn;
                }

                if (data.hasOwnProperty(key)) {
                    if (data[key].availability && data[key].availability === parseInt(data[key].availability, 10)) {
                        if (data[key].availability < 0) {
                            return [false, 'unavailable rule', '']
                        }

                        if (data[key].availability < 1) {
                            return [false, 'unavailable', '']
                        }

                        if (data[key].availability < resourceQuantity) {
                            return [true, 'partially', '']
                        }
                    } else {
                        var amount_available = 0;
                        var total;

                        if (slots) {
                            var hasAvailableSlot = false;
                            total = data[key][Object.keys(data[key])[0]];

                            $.each(total, function (k, v) {
                                if (v.availability > 0) {
                                    hasAvailableSlot = true;
                                    amount_available++;
                                }
                            });

                        } else {
                            total = data[key].availability;

                            $.each(total, function (k, v) {
                                if (v > 0) {
                                    hasAvailableSlot = true;
                                    amount_available++;
                                }
                            });
                        }

                        if (!hasAvailableSlot) {
                            return [false, 'unavailable', '']
                        }

                        if (Object.keys(total).length > amount_available) {
                            return [true, 'partially', '']
                        }
                    }

                    return [true, 'available', '']
                }
            }

            return [false, 'past', ''];
        }

        function loadData(date) {
            var now = Date.now();
            lastRequest = now;
            data = false;

            var post = {
                action: 'easyreservations_calendar',
                date: date === 0 ? 0 : date,
                arrival: arrival && (arrivalTime !== false || !settings.time) ? arrival : 0,
                arrivalTime: arrivalTime,
                months: settings.numberOfMonths,
                adults: $('*[name=adults]').val(),
                children: $('*[name=children]').val(),
                resource: settings.resource,
                minDate: settings.minDate,
                security: e.find('input[name="easy-date-selection-nonce"]').val()
            };

            if (!post['resource']) {
                alert('no resource field in form, please fix');
                return;
            }

            $.post(er_both_params.ajaxurl, post, function (response) {
                if (lastRequest === now) {
                    if (arrival && (arrivalTime || !settings.time)) {
                        e.find('.departure .text .date').addClass('important').html(er_date_picker_params.select);
                    } else {
                        e.find('.arrival .text .date').html(er_date_picker_params.select);
                    }

                    data = response;
                    slots = data.hasOwnProperty('slots') && data['slots'];
                    resourceQuantity = er_both_params.resources[settings.resource]['quantity'];

                    if (data.hasOwnProperty('max') && data['max']) {
                        //TODO: reintroduce feature WEIRD BUG still present as of 02 2020
                        //calendarContainer.datepicker('option', 'maxDate', data['max']);
                        calendarContainer.datepicker("refresh");
                    } else {
                        calendarContainer.datepicker("refresh");
                    }
                    calendarContainer.find('.ui-datepicker-today a, .ui-datepicker-current-day a').removeClass('ui-state-highlight').removeClass('ui-state-hover').removeClass('ui-state-active');
                }
            });
        }

        function finish() {
            e.find('input[name=arrival]').trigger('change');
        }
    };

})(jQuery);
