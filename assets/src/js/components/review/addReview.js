;(function ($) {
    // 	prepear_form_data
    function prepear_form_data ( form, field_map, data ) {
        if ( ! data || typeof data !== 'object' ) {
        var data = {};
        }

        for ( var key in field_map) {
        var field_item = field_map[ key ];
        var field_key = field_item.field_key;
        var field_type = field_item.type;

        if ( 'name' === field_type ) {
            var field = form.find( '[name="'+ field_key +'"]' );
        } else {
            var field = form.find( field_key );
        }

        if ( field.length ) {
            var data_key = ( 'name' === field_type ) ? field_key : field.attr('name') ;
            var data_value = ( field.val() ) ? field.val() : '';

            data[data_key] = data_value;
        }
        }

        return data;
    }

     /*HELPERS*/
     function print_static_rating($star_number) {
        var v;
        if ($star_number) {
            v = '<ul>';
            for (var i = 1; i <= 5; i++) {
                v += (i <= $star_number)
                    ? "<li><span class='directorist-rate-active'></span></li>"
                    : "<li><span class='directoristrate-disable'></span></li>";
            }
            v += '</ul>';
        }

        return v;
    }

    /* Add review to the database using ajax*/
    var submit_count = 1;

    $("#directorist-review-form").on("submit", function (e) {
        e.preventDefault();
        if (submit_count > 1) {
            // show error message
            swal({
                title: atbdp_public_data.warning,
                text: atbdp_public_data.not_add_more_than_one,
                type: "warning",
                timer: 2000,
                showConfirmButton: false
            });
            return false; // if user try to submit the form more than once on a page load then return false and get out
        }
        var $form = $(this);
        var $data = $form.serialize();

        var field_field_map = [
            { type: 'name', field_key: 'post_id' },
            { type: 'id', field_key: '#atbdp_review_nonce_form' },
            { type: 'id', field_key: '#guest_user_email' },
            { type: 'id', field_key: '#reviewer_name' },
            { type: 'id', field_key: '#review_content' },
            { type: 'id', field_key: '#directorist-review-rating' },
            { type: 'id', field_key: '#review_duplicate' },
        ];

        var _data = { action: 'save_listing_review' };
        _data = prepear_form_data( $form, field_field_map, _data );

        // atbdp_do_ajax($form, 'save_listing_review', _data, function (response) {

        jQuery.post(atbdp_public_data.ajaxurl, _data, function(response) {
            var output = '';
            var deleteBtn = '';
            var d;
            var name = $form.find("#reviewer_name").val();
            var content = $form.find("#review_content").val();
            var rating = $form.find("#directorist-review-rating").val();
            var ava_img = $form.find("#reviewer_img").val();
            var approve_immediately = $form.find("#approve_immediately").val();
            var review_duplicate = $form.find("#review_duplicate").val();
            if (approve_immediately === 'no') {
                if(content === '') {
                    // show error message
                    swal({
                        title: "ERROR!!",
                        text: atbdp_public_data.review_error,
                        type: "error",
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    if (submit_count === 1) {
                        $('#directorist-client-review-list').prepend(output); // add the review if it's the first review of the user
                        $('.atbdp_static').remove();
                    }
                    submit_count++;
                    if (review_duplicate === 'yes') {
                        swal({
                            title: atbdp_public_data.warning,
                            text: atbdp_public_data.duplicate_review_error,
                            type: "warning",
                            timer: 3000,
                            showConfirmButton: false
                        });
                    } else {
                        swal({
                            title: atbdp_public_data.success,
                            text: atbdp_public_data.review_approval_text,
                            type: "success",
                            timer: 4000,
                            showConfirmButton: false
                        });
                    }
                }


            } else if (response.success) {
                output +=
                    '<div class="directorist-signle-review" id="directorist-single-review-' + response.data.id + '">' +
                    '<input type="hidden" value="1" id="has_ajax">' +
                    '<div class="directorist-signle-review__top"> ' +
                    '<div class="directorist-signle-review-avatar-wrap"> ' +
                    '<div class="directorist-signle-review-avatar">' + ava_img + '</div> ' +
                    '<div class="directorist-signle-review-avatar__info"> ' +
                    '<p>' + name + '</p>' +
                    '<span class="directorist-signle-review-time">' + response.data.date + '</span> ' + '</div> ' + '</div> ' +
                    '<div class="directorist-rated-stars">' + print_static_rating(rating) + '</div> ' +
                    '</div> ';
                if( atbdp_public_data.enable_reviewer_content ) {
                output +=
                    '<div class="directorist-signle-review__content"> ' +
                    '<p>' + content + '</p> ' +
                    //'<a href="#"><span class="fa fa-mail-reply-all"></span>Reply</a> ' +
                    '</div> ';
                }
                output +=
                    '</div>';

                // as we have saved a review lets add a delete button so that user cann delete the review he has just added.
                deleteBtn += '<button class="directory_btn btn btn-danger" type="button" id="atbdp_review_remove" data-review_id="' + response.data.id + '">Remove</button>';
                $form.append(deleteBtn);
                if (submit_count === 1) {
                    $('#directorist-client-review-list').prepend(output); // add the review if it's the first review of the user
                    $('.atbdp_static').remove();
                }
                var sectionToShow = $("#has_ajax").val();
                var sectionToHide = $(".atbdp_static");
                var sectionToHide2 = $(".directory_btn");
                if (sectionToShow) {
                    // $(sectionToHide).hide();
                    $(sectionToHide2).hide();
                }
                submit_count++;
                // show success message
                swal({
                    title: atbdp_public_data.review_success,
                    type: "success",
                    timer: 800,
                    showConfirmButton: false
                });

                //reset the form
                $form[0].reset();
                // remove the notice if there was any
                var $r_notice = $('#review_notice');
                if ($r_notice) {
                    $r_notice.remove();
                }
            } else {
                // show error message
                swal({
                    title: "ERROR!!",
                    text: atbdp_public_data.review_error,
                    type: "error",
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });

        return false;
    });


    class AttachmentPreview {
        constructor(form) {
            this.$form = $(form);
            this.$input = this.$form.find('.directorist-review-images');
            this.$preview = this.$form.find('.directorist-review-img-gallery');

            this.bindEvents();
        }

        bindEvents() {
            const self = this;

            this.$input.on('change', function() {
                self.showPreview(this);
            });

            this.$form.on('click', '.directorist-btn-delete', function(e) {
                e.preventDefault();
                $(this).parent().remove();
            });
        }

        // deleteFromFileList(fileField, index) {
        //     let fileBuffer = Array.from(fileField.files);
        //     fileBuffer.splice(index, 1);

        //     /** Code from: https://stackoverflow.com/a/47172409/8145428 */
        //     // Firefox < 62 workaround exploiting https://bugzilla.mozilla.org/show_bug.cgi?id=1422655
        //     // specs compliant (as of March 2018 only Chrome)
        //     const dataTransfer = new ClipboardEvent('').clipboardData || new DataTransfer();

        //     for (let file of fileBuffer) {
        //         dataTransfer.items.add(file);
        //     }
        //     fileField.files = dataTransfer.files;
        // }

        // addToFileList(fileField, index) {
        //     let fileBuffer = Array.from(fileField.files);
        //     fileBuffer.splice(index, 1);

        //     /** Code from: https://stackoverflow.com/a/47172409/8145428 */
        //     // Firefox < 62 workaround exploiting https://bugzilla.mozilla.org/show_bug.cgi?id=1422655
        //     // specs compliant (as of March 2018 only Chrome)
        //     const dataTransfer = new ClipboardEvent('').clipboardData || new DataTransfer();

        //     for (let file of fileBuffer) {
        //         dataTransfer.items.add(file);
        //     }
        //     fileField.files = dataTransfer.files;
        // }

        showPreview(input) {
            this.$preview.html('');

            for (let i = 0, len = input.files.length; i < len; i++) {
                const fileReader = new FileReader();
                let file = input.files[i];

                if (!file.type.startsWith('image/')) {
                    continue;
                }

                fileReader.onload = event => {
                    const html = `
                    <div class="directorist-review-gallery-preview preview-image">
                        <img src="${event.target.result}" alt="Directorist Review Preview">
                        <a href="#" class="directorist-btn-delete"><i class="la la-trash"></i></a>
                    </div>
                    `;

                    this.$preview.append(html);
                }

                fileReader.readAsDataURL(file);
            }
        }
    }

    class CommentInteraction {
        constructor() {
            this.selector = '[data-comment-interaction]';
            this.$wrap    = $('.directorist-review-content__reviews');

            this.events();
        }

        events() {
            this.$wrap.on(
                'click.directoristInteractionClick',
                this.selector,
                this.callback.bind(this)
            );
        }

        callback(event) {
            event.preventDefault();

            const $target = $(event.currentTarget);
            const config = $target.data('comment-interaction');

            if (!config) {
                return;
            }

            const [commentId, interaction] = config.split(':');

            if (!commentId || !interaction) {
                return;
            }

            if ($target.hasClass('processing')) {
                return;
            } else {
                $target.addClass('processing').attr('disabled', true);

                if (interaction === 'helpful' || interaction === 'unhelpful') {
                    $target.find('span').html($target.data('count') + 1);
                    $target.data('count', $target.data('count') + 1);
                }
            }

            this.timeout && clearTimeout(this.timeout);

            this.send(commentId, interaction)
                .done(response => {
                    const $comment = $('#div-comment-'+commentId);
                    let type = 'warning';

                    if (response.success) {
                        $target.removeClass('processing').removeAttr('disabled', true);
                        type = 'success';
                    }

                    $comment.find('.directorist-alert').remove();
                    $comment.prepend(this.getAlert(type).html(response.data));

                    this.timeout = setTimeout(() => {
                        $comment.find('.directorist-alert').slideUp('medium');
                        clearTimeout(this.timeout);
                    }, 3000);
                });
        }

        getAlert(type) {
            return $('<div />', {
                class: 'directorist-alert directorist-alert-' + type
            });
        }

        send(commentId, interaction) {
            return $.post(
                directorist.ajaxUrl,
                {
                    action: directorist.action,
                    nonce: directorist.nonce,
                    comment_id: commentId,
                    interaction: interaction
                }
            );
        }
    }

    class AdvancedReview {
        constructor() {
            this.form = document.querySelector('#commentform');
            this.setFormEncoding();

            new AttachmentPreview(this.form);
            new CommentInteraction();
        }

        setFormEncoding() {
            this.form.encoding = 'multipart/form-data';
        }
    }

    const advancedReview = new AdvancedReview();
})(jQuery);
