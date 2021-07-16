const $ = jQuery;

function get_dom_data ( key ) {
    var dom_content = document.body.innerHTML;

    if ( ! dom_content.length ) { return ''; }

    var pattern = new RegExp("(<!-- directorist-dom-data::" + key + "\\s)(.+)(\\s-->)");
    var terget_content = pattern.exec( dom_content );

    if ( ! terget_content ) { return ''; }
    if ( typeof terget_content[2] === 'undefined' ) { return ''; }
    
    var dom_data = JSON.parse( terget_content[2] );

    if ( ! dom_data ) { return ''; }

    return dom_data;
}

function convertToSelect2( field ) {
    if ( ! field ) { return; }
    if ( ! field.elm ) { return; }
    if ( ! field.elm.length ) { return; }

    const default_args = {
        allowClear: true,
        width: '100%',
        templateResult: function (data) {
            // We only really care if there is an element to pull classes from
            if ( ! data.element ) {
                return data.text;
            }
            var $element = $(data.element);
            var $wrapper = $('<span></span>');

            $wrapper.addClass($element[0].className);
            $wrapper.text(data.text);

            return $wrapper;
        }
    };

    var args = ( field.args && typeof field.args === 'object' ) ? Object.assign( default_args, field.args ) : default_args;

    var options = field.elm.find( 'option' );
    var placeholder = ( options.length ) ? options[0].innerHTML: '';

    if ( placeholder.length ) {
        args.placeholder = placeholder;
    }

    field.elm.select2( args );
}

export { get_dom_data, convertToSelect2 };