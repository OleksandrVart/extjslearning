var date = new Ext.form.DateField({
    fieldLabel: 'Выберите дату',
    format: 'd.m.Y',
    altFormats: 'F d, Y|Y-m-d|Y-n-d|d.m.Y',
    value: new Date(),
    name: 'choice_date'
});

var form = new Ext.FormPanel({
    border: false,
    width: 400,
    style: {
        margin: '0 auto',
        padding: '10px'
    },
    items: [
        date,
        {
            xtype: 'displayfield',
            fieldLabel: "Выбраная дата",
            name: 'cur_choice_date',
            value: '',
        }
    ],
    buttonAlign: 'center',
    buttons: [{
        text: 'Выбрать',
        handler: function(){
            var choice = form.getForm().findField('choice_date').getValue();
            var res = new Date(choice);
            form.getForm().findField('cur_choice_date').setValue(res.format("Y-m-d"));
        }
    }]
});

panel4 = new Ext.Panel({
    title: 'Задание 4',
    listeners: {
        scope: this,
        activate: function (a) {
            console.log('activate');
            a.doLayout();
        }
    },
    layout: {
        type: 'vbox',
        pack: 'start',
        align: 'stretch'
    },
    items: [
        {
            xtype: 'panel',
            autoHeight: true,
            padding: 10,
            style: {
                fontWeight: 'bold',
                fontSize: '14px',
                color: 'green'
            },
            html: [
                '<p>Работа с датапикером</p>',
                'Сделать выбор даты. После выбора даты отображать ниже результат',
                'в датапикере отрбражать дату в формате 27.04.2020 а результат',
                'отображать в формате Y-m-d'
            ].join('<br/>')
        }, {
            xtype: 'panel',
            flex: 1,
            padding: 10,
            items: [
                form
            ]
        }
    ]
});