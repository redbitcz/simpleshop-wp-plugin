(function(){
        tinymce.create('tinymce.plugins.SSCTinyMCePlugin', {
            init: function(ed, url){
                ed.addButton('sscaddformbutton', {
                    title: 'SimpleShop',
                    cmd: 'sscaddformbuttonCmd',
                    image: url + '/img/ssc-logo.png'
                });
                ed.addCommand('sscaddformbuttonCmd', function(){

                    jQuery.post(ajaxurl,{action: 'load_simple_shop_products'},function (response)  {
                       console.log(response);
                        var sscProducts = [];
                        jQuery.each( response, function( index, value ){
                            sscProducts.push({
                                text: value,
                                value: index
                            });
                        });

                        var win = ed.windowManager.open({
                            width : 600,
                            height : 300,
                            title: 'SimpleShop Formulář',
                            body: [
                                {
                                    type   : 'listbox',
                                    name   : 'sscId',
                                    label  : 'Formulář',
                                    values : sscProducts,
                                    value : 'test2' // Sets the default
                                }
                            ],
                            buttons: [
                                {
                                    text: "Ok",
                                    subtype: "primary",
                                    onclick: function() {
                                        win.submit();
                                    }
                                },
                                {
                                    text: "Cancel",
                                    onclick: function() {
                                        win.close();
                                    }
                                }
                            ],
                            onsubmit: function(e){
                                var params = [];
                                console.log(e);
                                if( e.data.sscId.length > 0 ) {
                                    params.push('ID="' + e.data.sscId + '"');
                                }
                                if( params.length > 0 ) {
                                    paramsString = ' ' + params.join(' ');
                                }

                                var returnText = '[SimpleShop-form ' + paramsString + ']';
                                ed.execCommand('mceInsertContent', 0, returnText);
                            }
                        });
                    },'json');


                });
            },
            getInfo: function() {
                return {
                    longname : 'SimpleShop TinyMCE Plugin',
                    author : 'Václav Greif',
                    authorurl : 'https://wp-programator.cz',
                    version : "1.0"
                };
            }
        });
        tinymce.PluginManager.add( 'ssctinymceplugin', tinymce.plugins.SSCTinyMCePlugin );
})();