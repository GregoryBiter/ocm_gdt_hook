function autoloadInitData(setting, callback) {
    console.log(setting);
    $(setting.inputLabel).autocomplete({
        'source': function(request, response) {
            console.log(request, response);
            $.ajax({
                url: 'index.php?route=' + setting.route + '&user_token='+ setting.user_token +'&' + setting.filter + '=' + encodeURIComponent(request),
                dataType: 'json',
                success: function(json) {
                    response($.map(json, function(item) {
                        let result = {};
                        for (let key in setting.objectKey) {
                            result[key] = item[setting.objectKey[key]];
                        }
                        return result;
                    }));
                }
            });
        },
        'select': function(item) {
            $(setting.inputLabel).val(item['label']);
            $(setting.inputId).val(item['value']);
        }
    });
}
window.gdt = window.gdt || {};
window.gdt.autoloadInitData = autoloadInitData;