/* eslint-disable */
const $ = jQuery;
const localized_data = atbdp_public_data.add_listing_data;

/* Show and hide manual coordinate input field */
$(window).on('load', function () {

    if ($('input#manual_coordinate').length) {

        $('input#manual_coordinate').each((index, element) => {
            if (!$(element).is(':checked')) {
                $('#hide_if_no_manual_cor').hide();
                $('.directorist-map-coordinates').hide();
            }
        });
    }
});

$('body').on("click", "#manual_coordinate", function (e) {
    if ($('input#manual_coordinate').is(':checked')) {
        $('.directorist-map-coordinates').show();
        $('#hide_if_no_manual_cor').show();
    } else {
        $('.directorist-map-coordinates').hide();
        $('#hide_if_no_manual_cor').hide();
    }
});


// enable sorting if only the container has any social or skill field
const $s_wrap = $('#social_info_sortable_container'); // cache it
/* if (window.outerWidth > 1700) {
        if ($s_wrap.length) {
                $s_wrap.sortable({
                        axis: 'y',
                        opacity: '0.7',
                });
        }
} */

// SOCIAL SECTION
// Rearrange the IDS and Add new social field
$('body').on('click', '#addNewSocial', function (e) {
    const social_wrap = $('#social_info_sortable_container'); // cache it
    const currentItems = $('.directorist-form-social-fields').length;
    const ID = `id=${currentItems}`; // eg. 'id=3'
    const iconBindingElement = jQuery('#addNewSocial');

    // arrange names ID in order before adding new elements
    $('.directorist-form-social-fields').each(function (index, element) {
        const e = $(element);
        e.attr('id', `socialID-${index}`);
        e.find('select').attr('name', `social[${index}][id]`);
        e.find('.atbdp_social_input').attr('name', `social[${index}][url]`);
        e.find('.directorist-form-social-fields__remove').attr('data-id', index);
    });

    // now add the new elements. we could do it here without using ajax but it would require more markup here.
    atbdp_do_ajax(iconBindingElement, 'atbdp_social_info_handler', ID, function (data) {
        social_wrap.append(data);
    });
});

// remove the social field and then reset the ids while maintaining position
$('body').on('click', '.directorist-form-social-fields__remove', function (e) {
    const id = $(this).data('id');
    const elementToRemove = $(`div#socialID-${id}`);
    /* Act on the event */
    swal(
        {
            title: localized_data.i18n_text.confirmation_text,
            text: localized_data.i18n_text.ask_conf_sl_lnk_del_txt,
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#DD6B55',
            confirmButtonText: localized_data.i18n_text.confirm_delete,
            closeOnConfirm: false,
        },
        function (isConfirm) {
            if (isConfirm) {
                // user has confirmed, no remove the item and reset the ids
                elementToRemove.slideUp('fast', function () {
                    elementToRemove.remove();
                    // reorder the index
                    $('.directorist-form-social-fields').each(function (index, element) {
                        const e = $(element);
                        e.attr('id', `socialID-${index}`);
                        e.find('select').attr('name', `social[${index}][id]`);
                        e.find('.atbdp_social_input').attr(
                            'name',
                            `social[${index}][url]`
                        );
                        e.find('.directorist-form-social-fields__remove').attr('data-id', index);
                    });
                });

                // show success message
                swal({
                    title: localized_data.i18n_text.deleted,
                    // text: "Item has been deleted.",
                    type: 'success',
                    timer: 200,
                    showConfirmButton: false,
                });
            }
        }
    );
});

/* This function handles all ajax request */
function atbdp_do_ajax(ElementToShowLoadingIconAfter, ActionName, arg, CallBackHandler) {
    let data;
    if (ActionName) data = `action=${ActionName}`;
    if (arg) data = `${arg}&action=${ActionName}`;
    if (arg && !ActionName) data = arg;
    // data = data ;

    const n = data.search(localized_data.nonceName);
    if (n < 0) {
        data = `${data}&${localized_data.nonceName}=${localized_data.nonce}`;
    }

    jQuery.ajax({
        type: 'post',
        url: localized_data.ajaxurl,
        data,
        beforeSend() {
            jQuery("<span class='atbdp_ajax_loading'></span>").insertAfter(
                ElementToShowLoadingIconAfter
            );
        },
        success(data) {
            jQuery('.atbdp_ajax_loading').remove();
            CallBackHandler(data);
        },
    });
}

// Select2 js code
if (!localized_data.is_admin) {
    // Location
    const createLoc = $('#at_biz_dir-location').attr("data-allow_new");
    let maxLocationLength = $('#at_biz_dir-location').attr("data-max");
    if (createLoc) {
        $("#at_biz_dir-location").select2({
            tags: true,
            maximumSelectionLength: maxLocationLength,
            language: {
                maximumSelected: function () {
                    return localized_data.i18n_text.max_location_msg;
                }
            },
            tokenSeparators: [","],
        });
    } else {
        $("#at_biz_dir-location").select2({
            allowClear: true,
            tags: false,
            maximumSelectionLength: maxLocationLength,
            tokenSeparators: [","],
        });
    }

    // Tags
    const createTag = $('#at_biz_dir-tags').attr("data-allow_new");
    // console.log($('#at_biz_dir-tags').attr("data-max"));
    let maxTagLength = $('#at_biz_dir-tags').attr("data-max");
    if (createTag) {
        $('#at_biz_dir-tags').select2({
            tags: true,
            maximumSelectionLength: maxTagLength,
            tokenSeparators: [','],
        });
    } else {
        $('#at_biz_dir-tags').select2({
            allowClear: true,
            maximumSelectionLength: maxTagLength,
            tokenSeparators: [','],
        });
    }
    //Category
    const createCat = $('#at_biz_dir-categories').attr("data-allow_new");
    let maxCatLength = $('#at_biz_dir-categories').attr("data-max");
    if (createCat) {
        $('#at_biz_dir-categories').select2({
            allowClear: true,
            tags: true,
            maximumSelectionLength: maxCatLength,
            tokenSeparators: [','],
        });
    } else {
        $('#at_biz_dir-categories').select2({
            maximumSelectionLength: maxCatLength,
            allowClear: true,
        });
    }

}


// Custom Image uploader for listing image (multiple)

// price range
$('#price_range').hide();
const is_checked = $('#atbd_listing_pricing').val();
if (is_checked === 'range') {
    $('#price').hide();
    $('#price_range').show();
}
$('.directorist-form-pricing-field__options .directorist-checkbox__label').on('click', function () {
    const $this = $(this);
    if ($this.parent('.directorist-checkbox').children('input[type=checkbox]').prop('checked') === true) {
        $(`#${$this.data('option')}`).hide();
    } else {
        $(`#${$this.data('option')}`).show();
    }
    const $sibling = $this.parent().siblings('.directorist-checkbox');
    $sibling.children('input[type=checkbox]').prop('checked', false);
    $(`#${$sibling.children('.directorist-checkbox__label').data('option')}`).hide();
});

const has_tagline = $('#has_tagline').val();
const has_excerpt = $('#has_excerpt').val();
if (has_excerpt && has_tagline) {
    $('.atbd_tagline_moto_field').fadeIn();
} else {
    $('.atbd_tagline_moto_field').fadeOut();
}

$('#atbd_optional_field_check').on('change', function () {
    $(this).is(':checked')
        ? $('.atbd_tagline_moto_field').fadeIn()
        : $('.atbd_tagline_moto_field').fadeOut();
});

// it shows the hidden term and conditions
$('#listing_t_c').on('click', function (e) {
    e.preventDefault();
    $('#tc_container').toggleClass('active');
});

$(function () {
    $('#color_code2')
        .wpColorPicker()
        .empty();
});

// Load custom fields of the selected category in the custom post type "atbdp_listings"
$('#at_biz_dir-categories').on('change', function () {
    var directory_type = qs.directory_type ? qs.directory_type : $('input[name="directory_type"]').val();
    const length = $('#at_biz_dir-categories option:selected');
    const id = [];
    length.each((el, index) => {
        id.push($(index).val());
    });

    const data = {
        action: 'atbdp_custom_fields_listings',
        post_id: $('input[name="listing_id"]').val(),
        term_id: id,
        directory_type: directory_type,
    };

    $.post(localized_data.ajaxurl, data, function (response) {
        if (response) {
            $('.atbdp_category_custom_fields').empty().append(response);
            function atbdp_tooltip() {
                var atbd_tooltip = document.querySelectorAll('.atbd_tooltip');
                atbd_tooltip.forEach(function (el) {
                    if (el.getAttribute('aria-label') !== " ") {
                        document.body.addEventListener('mouseover', function (e) {
                            for (var target = e.target; target && target != this; target = target.parentNode) {
                                if (target.matches('.atbd_tooltip')) {
                                    el.classList.add('atbd_tooltip_active');
                                }
                            }
                        }, false);
                    }
                });
            }
            atbdp_tooltip();
        } else {
            $('.atbdp_category_custom_fields').empty();
        }
    });
});


let test_data = null;

// Load custom fields of the selected category in the custom post type "atbdp_listings"
$(document).ready(function () {
    var directory_type = qs.listing_type ? qs.listing_type : $('input[name="directory_type"]').val();
    const length = $('#at_biz_dir-categories option:selected');
    const id = [];
    length.each((el, index) => {
        id.push($(index).val());
    });
    const data = {
        action: 'atbdp_custom_fields_listings',
        post_id: $('input[name="listing_id"]').val(),
        term_id: id,
        directory_type: directory_type,
    };
    $.post(localized_data.ajaxurl, data, function (response) {
        if (response) {
            $('.atbdp_category_custom_fields').empty().append(response);
            function atbdp_tooltip() {
                var atbd_tooltip = document.querySelectorAll('.atbd_tooltip');
                atbd_tooltip.forEach(function (el) {
                    if (el.getAttribute('aria-label') !== " ") {
                        document.body.addEventListener('mouseover', function (e) {
                            for (var target = e.target; target && target != this; target = target.parentNode) {
                                if (target.matches('.atbd_tooltip')) {
                                    el.classList.add('atbd_tooltip_active');
                                }
                            }
                        }, false);
                    }
                });
            }
            atbdp_tooltip();
        }
    });
});


function atbdp_is_checked(name) {
    const is_checked = $(`input[name="${name}"]`).is(':checked');
    if (is_checked) {
        return '1';
    }
    return '';
}

function setup_form_data(form_data, type, field) {
    //normal input
    if ((type === 'hidden') || (type === 'text') || (type === 'number') || (type === 'tel') || (type === 'email') || (type === 'date') || (type === 'time') || (type === 'url')) {
        form_data.append(field.name, field.value);
    }
    //textarea
    if ('textarea' === type) {
        const value = $('#' + field.name + '_ifr').length ? tinymce.get(field.name).getContent() : atbdp_element_value('textarea[name="' + field.name + '"]');
        form_data.append(field.name, value);
    }
    //radio
    if ('radio' === type) {
        form_data.append(field.name, atbdp_element_value('input[name="' + field.name + '"]:checked'));
    }
    // checkbox
    if ('checkbox' === type) {
        var values = [];
        var new_field = $('input[name^="' + field.name + '"]:checked');
        if (new_field.length > 1) {
            new_field.each(function () {
                var value = $(this).val();
                values.push(value);
            });
            form_data.append(field.name, values);
        } else {
            form_data.append(field.name, atbdp_element_value('input[name="' + field.name + '"]:checked'));
        }
    }
    //select
    if ('select-one' === type) {
        form_data.append(field.name, atbdp_element_value('select[name="' + field.name + '"]'));
    }
}

function atbdp_element_value(element) {
    const field = $(element);
    if (field.length) {
        return field.val();
    }
    return '';
}

const qs = (function (a) {
    if (a == '') return {};
    const b = {};
    for (let i = 0; i < a.length; ++i) {
        const p = a[i].split('=', 2);
        if (p.length == 1) b[p[0]] = '';
        else b[p[0]] = decodeURIComponent(p[1].replace(/\+/g, ' '));
    }
    return b;
})(window.location.search.substr(1).split('&'));

const uploaders = localized_data.media_uploader;
let mediaUploaders = [];
if (uploaders) {
    let i = 0;
    for (var uploader of uploaders) {
        if ($('#' + uploader['element_id']).length) {
            let media_uploader = new EzMediaUploader({
                containerID: uploader['element_id'],
            });
            mediaUploaders.push({
                media_uploader: media_uploader,
                uploaders_data: uploader,
            });
            mediaUploaders[i].media_uploader.init();
            i++;
        }
    }
}

const formID = $('#directorist-add-listing-form');
let on_processing = false;
let has_media = true;
let quick_login_modal__success_callback = null;

$('body').on('submit', formID, function (e) {
    if (localized_data.is_admin) return;
    e.preventDefault();
    let error_count = 0;
    const err_log = {};
    // if ($('#atbdp_front_media_wrap:visible').length == 0) {
    //         has_media = false;
    // }
    if (on_processing) {
        $('.directorist-form-submit__btn').attr('disabled', true);
        return;
    }

    let form_data = new FormData();
    let field_list = [];
    let field_list2 = [];
    $('.directorist-form-submit__btn').addClass('atbd_loading');
    form_data.append('action', 'add_listing_action');
    const fieldValuePairs = $('#directorist-add-listing-form').serializeArray();
    $.each(fieldValuePairs, function (index, fieldValuePair) {
        const field = document.getElementsByName(fieldValuePair.name)[0];
        const type = field.type;
        field_list.push({ name: field.name, });
        //array fields
        if (field.name.indexOf('[') > -1) {
            const field_name = field.name.substr(0, field.name.indexOf("["));
            const ele = $("[name^='" + field_name + "']");
            // process tax input
            if ('tax_input' !== field_name) {
                if (ele.length && (ele.length > 1)) {
                    ele.each(function (index, value) {
                        const field_type = $(this).attr('type');
                        var name = $(this).attr('name');
                        if (field_type === 'radio') {
                            if ($(this).is(':checked')) {
                                form_data.append(name, $(this).val());
                            }
                        } else if (field_type === 'checkbox') {
                            const new_field = $('input[name^="' + name + '"]:checked');
                            if (new_field.length > 1) {
                                new_field.each(function () {
                                    const name = $(this).attr('name');
                                    const value = $(this).val();
                                    form_data.append(name, value);
                                });
                            } else {
                                var name = new_field.attr('name');
                                var value = new_field.val();
                                form_data.append(name, value);
                            }
                        } else {
                            var name = $(this).attr('name');
                            var value = $(this).val();
                            if (!value) {
                                value = $(this).attr('data-time');
                            }
                            form_data.append(name, value);
                        }
                    });
                } else {
                    const name = ele.attr('name');
                    const value = ele.val();

                    form_data.append(name, value);
                }
            }
        } else {
            //  field_list2.push({ nam: name, val: value, field: field, type: type})
            setup_form_data(form_data, type, field);
        }
    });

    // console.log( field_list2 );
    // return;
    // images

    if (mediaUploaders.length) {
        for (var uploader of mediaUploaders) {
            if (uploader.media_uploader && has_media) {
                var hasValidFiles = uploader.media_uploader.hasValidFiles();
                if (hasValidFiles) {
                    // files
                    var files = uploader.media_uploader.getTheFiles();
                    if (files) {
                        for (var i = 0; i < files.length; i++) {
                            form_data.append(uploader.uploaders_data['meta_name'] + '[]', files[i]);
                        }
                    }
                    var files_meta = uploader.media_uploader.getFilesMeta();
                    if (files_meta) {
                        for (var i = 0; i < files_meta.length; i++) {
                            var elm = files_meta[i];
                            for (var key in elm) {
                                form_data.append(`${uploader.uploaders_data['files_meta_name']}[${i}][${key}]`, elm[key]);
                            }
                        }
                    }
                } else {
                    $('.directorist-form-submit__btn').removeClass('atbd_loading');
                    err_log.listing_gallery = { msg: uploader.uploaders_data['error_msg'] };
                    error_count++;
                    UIkit2.Utils.scrollToElement($('#' + uploader.uploaders_data['element_id']));
                }
            }
        }
    }

    // locations
    const locaitons = $('#at_biz_dir-location').val();
    if (Array.isArray(locaitons) && locaitons.length) {
        for (var key in locaitons) {
            var value = locaitons[key];
            form_data.append('tax_input[at_biz_dir-location][]', value);
        }
    }

    if (typeof locaitons === 'string') {
        form_data.append('tax_input[at_biz_dir-location][]', locaitons);
    }

    // tags
    const tags = $('#at_biz_dir-tags').val();
    if (tags) {
        for (var key in tags) {
            var value = tags[key];
            form_data.append('tax_input[at_biz_dir-tags][]', value);
        }
    }

    // categories
    const categories = $('#at_biz_dir-categories').val();
    if (Array.isArray(categories) && categories.length) {
        for (var key in categories) {
            var value = categories[key];
            form_data.append('tax_input[at_biz_dir-category][]', value);
        }
    }

    if (typeof categories === 'string') {
        form_data.append('tax_input[at_biz_dir-category][]', categories);
    }
    var directory_type = qs.directory_type ? qs.directory_type : $('input[name="directory_type"]').val();
    form_data.append('directory_type', directory_type);

    if (qs.plan) {
        form_data.append('plan_id', qs.plan);
    }

    if (error_count) {
        on_processing = false;
        $('.directorist-form-submit__btn').attr('disabled', false);
        console.log('Form has invalid data');
        console.log(error_count, err_log);
        return;
    }

    // on_processing = true;
    // $('.directorist-form-submit__btn').attr('disabled', true);
    $.ajax({
        method: 'POST',
        processData: false,
        contentType: false,
        url: localized_data.ajaxurl,
        data: form_data,
        success(response) {
            console.log(response);
            // return;
            // show the error notice
            $('.directorist-form-submit__btn').attr('disabled', false);

            // var is_pending = response ? '&' : '?';
            var is_pending = (response && response.pending) ? '&' : '?';

            if (response.error === true) {
                $('#listing_notifier').show().html(`<span>${response.error_msg}</span>`);
                $('.directorist-form-submit__btn').removeClass('atbd_loading');
                on_processing = false;

                if (response.quick_login_required) {
                    var modal = $('#directorist-quick-login');
                    var email = response.email;

                    // Prepare fields
                    modal.find('input[name="email"]').val(email);
                    modal.find('input[name="email"]').prop('disabled', true);

                    // Show alert
                    var alert = '<div class="directorist-alert directorist-alert-info directorist-mb-10 atbd-text-center directorist-mb-10">' + response.error_msg + '</div>';
                    modal.find('.directorist-modal-alerts-area').html(alert);

                    // Show the modal
                    modal.addClass('show');

                    quick_login_modal__success_callback = function (args) {
                        $('#guest_user_email').prop('disabled', true);
                        $('#listing_notifier').hide().html('');

                        args.elements.submit_button.remove();

                        var form_actions = args.elements.form.find('.directorist-form-actions');
                        form_actions.find('.directorist-toggle-modal').removeClass('directorist-d-none');
                    }
                }
            } else {
                // preview on and no need to redirect to payment
                if (response.preview_mode === true && response.need_payment !== true) {
                    if (response.edited_listing !== true) {
                        $('#listing_notifier')
                            .show()
                            .html(`<span class="atbdp_success">${response.success_msg}</span>`);
                        window.location.href = `${response.preview_url
                            }?preview=1&redirect=${response.redirect_url}`;
                    } else {
                        $('#listing_notifier')
                            .show()
                            .html(`<span class="atbdp_success">${response.success_msg}</span>`);
                        if (qs.redirect) {
                            var is_pending = '?';
                            window.location.href = `${response.preview_url +
                                is_pending}post_id=${response.id
                                }&preview=1&payment=1&edited=1&redirect=${qs.redirect}`;
                        } else {
                            window.location.href = `${response.preview_url
                                }?preview=1&edited=1&redirect=${response.redirect_url}`;
                        }
                    }
                    // preview mode active and need payment
                } else if (response.preview_mode === true && response.need_payment === true) {
                    window.location.href = `${response.preview_url
                        }?preview=1&payment=1&redirect=${response.redirect_url}`;
                } else {
                    const is_edited = response.edited_listing
                        ? `${is_pending}listing_id=${response.id}&edited=1`
                        : '';
                    if (response.need_payment === true) {
                        $('#listing_notifier')
                            .show()
                            .html(`<span class="atbdp_success">${response.success_msg}</span>`);
                        window.location.href = response.redirect_url;
                    } else {
                        $('#listing_notifier')
                            .show()
                            .html(`<span class="atbdp_success">${response.success_msg}</span>`);
                        window.location.href = response.redirect_url + is_edited;
                    }
                }
            }
        },
        error(error) {
            on_processing = false;
            $('.directorist-form-submit__btn').attr('disabled', false);

            $('.directorist-form-submit__btn').removeClass('atbd_loading');
            console.log(error);
        },
    });
});

// Custom Field Checkbox Button More
$(window).on('load', function () {
    if ($('.directorist-custom-field-btn-more').length) {
        $('.directorist-custom-field-btn-more').each((index, element) => {
            let fieldWrapper = $(element).closest('.directorist-custom-field-checkbox, .directorist-custom-field-radio');
            let customField = $(fieldWrapper).find('.directorist-checkbox, .directorist-radio');
            $(customField).slice(20, customField.length).slideUp();

            if (customField.length < 20) {
                $(element).slideUp();
            }
        });
    }

});

$('body').on('click', '.directorist-custom-field-btn-more', function (event) {
    event.preventDefault();
    let fieldWrapper = $(this).closest('.directorist-custom-field-checkbox, .directorist-custom-field-radio');
    let customField = $(fieldWrapper).find('.directorist-checkbox, .directorist-radio');
    $(customField).slice(20, customField.length).slideUp();

    $(this).toggleClass('active');

    if ($(this).hasClass('active')) {
        $(this).text("See Less");
        $(customField).slice(20, customField.length).slideDown();
    } else {
        $(this).text("See More");
        $(customField).slice(20, customField.length).slideUp();
    }

});

// ------------------------------
// Quick Login
// ------------------------------
$('#directorist-quick-login .directorist-toggle-modal').on("click", function (e) {
    e.preventDefault();
    $("#directorist-quick-login").removeClass("show");
});

$('#quick-login-from-submit-btn').on('click', function (e) {
    e.preventDefault();

    var form_id = $(this).data('form');
    var modal_id = $(this).data('form');

    var modal = $(modal_id);
    var form = $(form_id);
    var form_feedback = form.find('.directorist-form-feedback');

    var email = $(form).find('input[name="email"]');
    var password = $(form).find('input[name="password"]');
    var security = $(form).find('input[name="directorist-quick-login-security"]');

    var form_data = {
        action: 'directorist_ajax_quick_login',
        username: email.val(),
        password: password.val(),
        rememberme: false,
        ['directorist-quick-login-security']: security.val(),
    };

    var submit_button = $(this);
    var submit_button_default_html = submit_button.html();

    $.ajax({
        method: 'POST',
        url: atbdp_public_data.ajaxurl,
        data: form_data,
        beforeSend: function () {
            form_feedback.html('');
            submit_button.prop('disabled', true);
            submit_button.prepend('<i class="fas fa-circle-notch fa-spin"></i> ');
        },
        success: function (response) {
            console.log({ response });
            submit_button.html(submit_button_default_html);

            if (response.loggedin) {
                password.prop('disabled', true);

                var message = 'Successfully logged in, please continue to the listing submission';
                var msg = '<div class="directorist-alert directorist-alert-success directorist-text-center directorist-mb-20">' + message + '</div>';

                form_feedback.html(msg);

                if (quick_login_modal__success_callback) {
                    var args = {
                        elements: {
                            modal_id,
                            form,
                            email,
                            password,
                            submit_button
                        }
                    };

                    quick_login_modal__success_callback(args);
                }
            } else {
                var msg = '<div class="directorist-alert directorist-alert-danger directorist-text-center directorist-mb-20">' + response.message + '</div>';
                form_feedback.html(msg);
                submit_button.prop('disabled', false);
            }

        },
        error: function (error) {
            console.log({ error });

            submit_button.prop('disabled', false);
            submit_button.html(submit_button_default_html);
        },
    });
});