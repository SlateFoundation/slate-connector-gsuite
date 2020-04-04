Ext.define('Site.widget.GoogleCalendarEventCreator', {
    singleton: true,
    requires: [
        'Site.Common'
    ],

    config: {

        selector: '[href^="#create-google-calendar-event"]',
        formEl: null,
        modal: null,
        modalTpl: [
            '<div class="modal-mask">',
                '<div class="modal-container modal-dialog">',
                    '<header class="modal-header">',
                        '<div class="modal-close-button">&times;</div>',
                        '<h2 class="modal-title">{title}</h2>',
                    '</header>',
                    '<div class="modal-body">',
                        '<div class="modal-info"></div>',
                        '{formHtml}',
                        '<div class="attendees">',
                            '<tpl if="attendees.length &gt; 0">',
                                '<span>Attendees:</span>',
                                '<ul>',
                                    '<tpl for="attendees">',
                                        '<li>{.}</li>',
                                    '</tpl>',
                                '</ul>',
                            '</tpl>',
                        '</div>',
                    '</div>',
                    '<footer class="modal-buttons">',
                        '<button class="modal-cancel-button">Cancel</button>',
                        '<button class="modal-save-button">Save</button>',
                    '</footer>',
                '</div>',
            '</div>'
        ]
    },

    constructor: function() {
        var me = this;

        me.initConfig();
        Ext.onReady(me.onDocReady, me);
    },

    onDocReady: function() {
        var me = this,
            body = Ext.getBody(),
            selector = me.getSelector();

        body.on('click', me.onCreateEventClick, me, { delegate: selector });
    },

    onCreateEventClick: function(ev) {
        ev.preventDefault();

        var me = this,
            body = Ext.getBody(),
            target = Ext.fly(ev.target),
            attendeesValue = target.getAttribute('data-event-attendees'),
            attendees = Ext.isString(attendeesValue) ? attendeesValue.split(',') : [],
            modal,
            formEl,
            customFormFields = {},
            _onFormSubmit;

        Ext.iterate(target.getAttributes(), function(field, value) {
            var matches;
            if ((matches = field.match(/^data-event-(.+)/))) {
                customFormFields[matches[1]] = value;
            }
        });

        formEl = me.createForm(customFormFields);
        modalTpl = Ext.create('Ext.XTemplate', me.getModalTpl());
        modal = modalTpl.append(
            body,
            {
                title: target.getAttribute('data-title') || 'Create Google Calendar Event',
                formHtml: formEl.dom.outerHTML,
                attendees: attendees
            },
            true
        );


        _onFormSubmit = function() {
            modal.down('.modal-info').update('');
            if (me.validateForm(formEl) === false) {
                modal.down('.modal-info').update('Invalid Form. Please check all fields and try again.');
                return;
            }
            me.submitEvent();
        };

        formEl.on('submit', function(ev) {
            ev.preventDefault();
            _onFormSubmit();
        });

        // modal button event handlers
        modal.on(
            'click',
            function(ev) {
                var t = Ext.fly(ev.target);
                if (t.hasCls('modal-close-button' || t.hasCls('modal-cancel-button'))) {
                    me.destroyModal();
                } else if (t.hasCls('modal-save-button')) {
                    _onFormSubmit();
                }
            },
            null,
            { delegate: 'button,[class$="-button"]' }
        );

        me.setModal(modal);
    },

    destroyModal: function() {
        var me = this,
            body = Ext.getBody(),
            modal = me.getModal();

        body.removeCls('blurred');
        modal.destroy();
    },

    createForm: function(customFieldValues) {
        var me = this,
            formEl,
            _createHiddenField = function(name, value) {
                return {
                    tag: 'input',
                    type: 'hidden',
                    name: name,
                    value: value
                }
            },
            hiddenFormFields = [];

        Ext.iterate(customFieldValues, function(name, value) {
            hiddenFormFields.push(_createHiddenField(name, value));
        });

        formEl = Ext.get(Ext.DomHelper.createDom({
            tag: 'form',
            cls: 'google-calendar-event-creator',
            cn: [{
                tag: 'label',
                cls: 'field text-field is-required',
                cn: [{
                    tag: 'span',
                    cls: 'field-label',
                    html: 'Title'
                },{
                    tag: 'input',
                    type: 'text',
                    cls: 'field-control',
                    name: 'title',
                    placeholder: 'Title',
                    required: 'true'
                }]
            },{
                tag: 'div',
                // cls: 'inline-fields',
                cn: [{
                    tag: 'label',
                    cls: 'field date-field is-required',
                    cn: [{
                        tag: 'span',
                        cls: 'field-label',
                        html: 'Start Date'
                    },{
                        tag: 'input',
                        type: 'date',
                        cls: 'field-control',
                        name: 'start_date',
                        placeholder: 'yyyy-mm-dd',
                        placeholder: 'Start Date',
                        required: true
                    }]
                },{
                    tag: 'label',
                    cls: 'field time-field is-required',
                    cn: [{
                        tag: 'span',
                        cls: 'field-label',
                        html: 'Start Time'
                    },{
                        tag: 'input',
                        type: 'time',
                        cls: 'field-control',
                        name: 'start_time',
                        placeholder: 'hh:mm (24hr format)',
                    }]
                }]
            },{
                tag: 'div',
                // cls: 'inline-fields',
                cn: [{
                    tag: 'label',
                    cls: 'field date-field is-required',
                    cn: [{
                        tag: 'span',
                        cls: 'field-label',
                        html: 'End Date'
                    },{
                        tag: 'input',
                        type: 'date',
                        cls: 'field-control',
                        name: 'end_date',
                        placeholder: 'yyyy-mm-dd',
                    }]
                },{
                    tag: 'label',
                    cls: 'field time-field is-required',
                    cn: [{
                        tag: 'span',
                        cls: 'field-label',
                        html: 'End Time'
                    },{
                        tag: 'input',
                        type: 'time',
                        cls: 'field-control',
                        name: 'end_time',
                        placeholder: 'hh:mm (24hr format)',
                    }]
                }]
            }, {
                tag: 'div',
                cn: hiddenFormFields
            }]
        }))

        me.setFormEl(formEl);
        return formEl;
    },

    validateForm: function(formEl) {
        var me = this,
            modal = me.getModal(),
            formFields = modal.query('input[required]'),
            validators = {
                date: function(value) {
                    return value && value.match(/^(\d{4})[\-\/](\d{2})[\-\/](\d{2})$/);
                },
                time: function(value) {
                    return value && value.match(/^([0-2]\d):([0-5]\d)$/)
                }
            },
            fieldValidators = {
                start_date: 'date',
                end_date: 'date',
                start_time: 'time',
                end_time: 'time'
            },
            valid = true;

        Ext.each(formFields, function(field) {
            if (!field.value) {
                return valid = false;
            }

            // validate date+time fields
            if (fieldValidators[field.name]) {
                return valid = !!(validators[fieldValidators[field.name]](field.value));
            }
        });

        return valid;
    },

    submitEvent: function() {

        var me = this,
            date = new Date(),
            modal = me.getModal(),
            titleField = modal.down('input[name=title]'),
            startDateField = modal.down('input[name=start_date]'),
            startTimeField = modal.down('input[name=start_time]'),
            endDateField = modal.down('input[name=end_date]'),
            endTimeField = modal.down('input[name=end_time]'),
            localTimezone = date.toString().match(/([-\+][0-9]+)\s/)[1],
    		eventData = {
                calendarId: 'primary',
                summary: titleField.getValue(),
                startDateTime: startDateField.getValue() + 'T' + startTimeField.getValue() + ':00' + localTimezone,
                endDateTime: endDateField.getValue() + 'T' + endTimeField.getValue() + ':00' + localTimezone,
                // create hangout support
                'conferenceData[createRequest][requestId]': date.getTime().toString(),
                conferenceDataVersion: 1

            },
            hiddenFormFields = modal.query('input[type=hidden]');

        Ext.iterate(hiddenFormFields, function(field) {
            eventData[field.name] = field.value;
        });

        modal.down('.modal-container')
            .addCls('saving');

        Ext.Ajax.request({
            url: '/connectors/gsuite/calendar/create-event',
            method: 'POST',
            params: eventData,
            success: function(response) {
                var r = Ext.decode(response.responseText);
                if (r.success) {
                    me.destroyModal();
                } else {
                    modal.down('.modal-info').update('There was a problem processing your request. Would you like to try again?');
                    modal.down('.modal-buttons :last-child').update('Try Again');
                }
            },
            failure: function() {
                modal.down('.modal-info').update('There was a problem processing your request. Would you like to try again?');
                modal.down('.modal-buttons :last-child').update('Try Again');
            }
        });

    }
});
