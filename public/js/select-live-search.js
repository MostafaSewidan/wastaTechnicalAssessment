$(document).ready(function(){
    $('.live-search').each(function() {
        let parentModelId = $(this).closest('.modal').attr("id");
        
        $(this).select2({
            dropdownParent: $(`#${parentModelId}`),
            width: "off",
            ajax: {
                url: function(params) {
                    return $(this).data("url");
                },
                data: function (params) {
                    var query = {
                        search: params.term,
                        page: params.page || 1
                    };

                    // Query parameters will be ?search=[term]&type=public
                    return query;
                },
                processResults: function (data, page) {
                    let meta = data.meta;
                    return {
                        results: data.data,
                        pagination: {
                            more: meta.last_page > meta.current_page
                        }
                    };
                },
                cache: true
            },
            escapeMarkup: function (markup) {
                return markup;
            }, // let our custom formatter work
            minimumInputLength: 1,
            placeholder: 'Search for an option',
            allowClear: true
        });
    });
});

