var formPanel = new Ext.FormPanel({
    labelWidth: 100,
    title: 'Заполните форму',
    frame:true,
    bodyStyle:'padding:5px 5px 0',
    width: 500,
    defaults: {anchor: '100%'},
    defaultType: 'textfield',
    buttonAlign: 'center',
    items: [
        {
            fieldLabel: 'ФИО',
            name: 'name',
            allowBlank:false
        },{
            fieldLabel: 'Пароль',
            name: 'pass',
            allowBlank:false
        },{
            fieldLabel: 'Email',
            name: 'email',
            vtype:'email',
            allowBlank:false
        }, {
            xtype: 'textarea',
            fieldLabel: 'Описание',
            name: 'text',
            allowBlank:false
        }
    ],

    buttons: [{
        text: 'Готово',
        scope: this,
        handler: function () {
            var form = formPanel.getForm();
            if(form.isValid()) {
                var values = form.getValues();
                var text = 'Имя - ' + values.name
                    + '.</br>Пароль - ' + values.pass
                    + '.</br>Почта - ' + values.email
                    + '.</br>Дополнительная информация: ' + values.text;
                result.removeAll();
                result.add({
                    xtype: 'box',
                    html: text
                });
                result.doLayout();
            }
        }
    }]
});

var result = new Ext.Panel({
    xtype:'panel',
    width: 500,
    style: {
        // textAlign: 'center',
    },
    padding: 20,
    margin: 50,
    items: []
});

panel8 = new Ext.Panel({
    title: 'Задание 8',
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
                '<p>Работа с формами</p>',
                'Добавить форму, с полями для ввода, фио, пароля, имейла и описания. Все поля обязательны для ввода',
                'Ниже кнопка субмит, при нажатии на нее с формы берутся все данны и отображаются ниже в виде текста'
            ].join('<br/>')
        }, {
            xtype: 'panel',
            flex: 1,
            padding: 10,
            items: [
                formPanel,
                result
            ]
        }
    ]
});