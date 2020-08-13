
var result = new Ext.Panel({
    xtype:'panel',
    title: 'Результат',
    style: {
        textAlign: 'center',
    },
    padding: 20,
    margin: 50,
    items: []
});

var form = new Ext.FormPanel({
    frame:true,
    layout: 'column',
    border: false,
    defaults: {
        columnWidth: '.5',
        border: false
    },
    items: [{
        xtype:'fieldset',
        title: 'Чекбоксы',
        labelWidth: 20,
        autoHeight:true,
        defaultType: 'checkbox',
        items :[{
            boxLabel: 'Checkbox-1',
            name: 'checkbox-1'
        }, {
            boxLabel: 'Checkbox-2',
            name: 'checkbox-2'
        }, {
            boxLabel: 'Checkbox-3',
            name: 'checkbox-3'
        }, {
            boxLabel: 'Checkbox-4',
            name: 'checkbox-4'
        }]
    },{
        xtype:'fieldset',
        title: 'Радио',
        autoHeight: true,
        labelWidth: 20,
        defaultType: 'radio',
        items :[{
            checked: true,
            boxLabel: 'Номер 1',
            name: 'radio',
            inputValue: '1'
        }, {
            boxLabel: 'Номер 2',
            name: 'radio',
            inputValue: '2'
        }, {
            boxLabel: 'Номер 3',
            name: 'radio',
            inputValue: '3'
        }]
    }],
    buttonAlign: 'center',
    buttons: [{
        text: 'Выбрать',
        handler: function(){
            var data = form.getForm().getFieldValues(),
                text = [],
                row = '';
            for (var key in data) {
                if (key !== 'radio') {
                    if(data[key]) {
                        row = key + ' - нажат;';
                    } else {
                        row = key + ' - не нажат;';
                    }
                    text.push({
                        xtype:'box',
                        html: row,
                    })
                } else {
                    text.push({
                        xtype:'box',
                        html: 'Выбран переключатель №'+ form.getForm().getValues().radio,
                    })
                }
            }
            result.removeAll();
            result.add(text);
            result.doLayout();
        }
    }]
});

panel3 = new Ext.Panel({
    title: 'Задание 3',
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
                '<p>Работа с чекбоксами и радиобатонами</p>',
                'Создать 3 радиобатона и 4 чекбокса. Все это как-то обернуть в fieldset. Внизу сделать кнопку',
                'При нажатии на кнопку, снизу от нее отображать текстом какие радио и чекбоксы сейчас нажаты,',
                'а какие не нажаты. Текст должне быть в виде: чекбокс №1 - нажат или чекбокс№2 - не нажат',
                'Придумать красивое решение, чтоб работало на неопределенное кол-во чекбоксов и радиобатонов'
            ].join('<br/>')
        }, {
            xtype: 'panel',
            flex: 1,
            padding: 10,
            items: [
                form,
                result
            ]
        }
    ]
});