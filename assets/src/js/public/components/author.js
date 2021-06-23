// author sorting
(function ($) {
    $('body').on( 'click', '.directorist-alphabet', function() {
        $('#directorist-all-authors').addClass('atbdp-form-fade');
        $.ajax({
            method: 'POST',
            url: atbdp_public_data.ajaxurl,
            data: {
                action   : 'directorist_author_alpha_sorting',
                _nonce   : $(this).attr("data-nonce"),
                alphabet : $(this).attr("data-alphabet")
            },
            success( response ) {
               $('#directorist-all-authors').empty().append( response );
               $('#directorist-all-authors').removeClass('atbdp-form-fade');
            },
            error(error) {
                console.log(error);
            },
        });
    });
})(jQuery)