(function () {
    tinymce.create('tinymce.plugins.SSCTinyMCePlugin', {
        init: function (ed, url) {
            ed.addButton('sscaddformbutton', {
                title: 'SimpleShop',
                cmd: 'sscaddformbuttonCmd',
                image: url + '/img/ssc-logo.png'
            });
            ed.addButton('ssccontentbutton', {
                title: 'SimpleShop',
                cmd: 'ssccontentbuttonCMD',
                image: url + '/img/ssc-logo.png'
            });
            ed.addCommand('sscaddformbuttonCmd', function () {

                jQuery.post(ajaxurl, {action: 'load_simple_shop_products'}, function (response) {

                    var sscProducts = [];
                    jQuery.each(response, function (index, value) {
                        sscProducts.push({
                            text: value,
                            value: index
                        });
                    });

                    var win = ed.windowManager.open({
                        width: 600,
                        height: 300,
                        title: 'SimpleShop Formulář',
                        body: [
                            {
                                type: 'listbox',
                                name: 'sscId',
                                label: 'Formulář',
                                values: sscProducts,
                                value: 'test2' // Sets the default
                            }
                        ],
                        buttons: [
                            {
                                text: "Ok",
                                subtype: "primary",
                                onclick: function () {
                                    win.submit();
                                }
                            },
                            {
                                text: "Cancel",
                                onclick: function () {
                                    win.close();
                                }
                            }
                        ],
                        onsubmit: function (e) {
                            var params = [];
                            console.log(e);
                            if (e.data.sscId.length > 0) {
                                params.push('ID="' + e.data.sscId + '"');
                            }
                            if (params.length > 0) {
                                paramsString = ' ' + params.join(' ');
                            }

                            var returnText = '[SimpleShop-form ' + paramsString + ']';
                            ed.execCommand('mceInsertContent', 0, returnText);
                        }
                    });
                }, 'json');


            });
            ed.addCommand('ssccontentbuttonCMD', function () {
                var selectedText = ed.selection.getContent({format: 'html'});

                var win = ed.windowManager.open({
                    width: 600,
                    height: 300,
                    title: 'SimpleShop zabezpečený obsah',
                    body: [
                        {
                            type: 'listbox',
                            name: 'sscContentGroupID',
                            label: 'Skupina',
                            values: sscContentGroups,
                            value: 'test2' // Sets the default
                        },
                        {
                            type: 'listbox',
                            name: 'sscContentIsMember',
                            label: 'Je členem skupiny?',
                            values: [
                                {
                                    text: 'Ano',
                                    value: 'yes'
                                },
                                {
                                    text: 'Ne',
                                    value: 'no'
                                }
                            ],
                            value: 'yes'
                        },
                        {
                            type: 'textbox',
                            name: 'sscContentDaysToView',
                            label: 'Povolit přístup po X dnech od přiřazení do skupiny',
                            minWidth: 200,
                            value: ''
                        },
                        {
                            type: 'textbox',
                            name: 'sscContentSpecifiDate',
                            label: 'Zobrazit od data',
                            minWidth: 200,
                            value: ''
                        }
                    ],
                    buttons: [
                        {
                            text: "Ok",
                            subtype: "primary",
                            onclick: function () {
                                win.submit();
                            }
                        },
                        {
                            text: "Cancel",
                            onclick: function () {
                                win.close();
                            }
                        }
                    ],
                    onsubmit: function (e) {
                        var params = [];
                        if (e.data.sscContentGroupID.length > 0) {
                            params.push('group_id="' + e.data.sscContentGroupID + '"');
                        }
                        if (e.data.sscContentIsMember.length > 0) {
                            params.push('is_member="' + e.data.sscContentIsMember + '"');
                        }
                        if (e.data.sscContentDaysToView.length > 0) {
                            params.push('is_member="' + e.data.sscContentDaysToView + '"');
                        }
                        if (e.data.sscContentSpecifiDate.length > 0) {
                            params.push('specific_date="' + e.data.sscContentSpecifiDate + '"');
                        }
                        if (params.length > 0) {
                            paramsString = ' ' + params.join(' ');
                        }

                        var returnText = '[SimpleShop-content ' + paramsString + ']<br/><span id="_cursor" /><br/>'+ selectedText +'[/SimpleShop-content]';
                        ed.execCommand('mceInsertContent', 0, returnText);
                        ed.selection.select(ed.dom.select('#_cursor')[0]);
                        ed.selection.collapse(0);
                        ed.dom.remove('_cursor');

                    }
                });
                win.$el.addClass('sscContentPopup');
                jQuery('.sscContentPopup .mce-formitem:last-child input').datepicker({
                        dateFormat: 'yy-mm-dd'
                });
                setTimeout(function() {
                    console.log(jQuery('.sscContentPopup .mce-formitem:last-child input'));

                },500);



            });
        },
        getInfo: function () {
            return {
                longname: 'SimpleShop TinyMCE Plugin',
                author: 'Václav Greif',
                authorurl: 'https://wp-programator.cz',
                version: "1.0"
            };
        }
    });
    tinymce.PluginManager.add('ssctinymceplugin', tinymce.plugins.SSCTinyMCePlugin);
})();