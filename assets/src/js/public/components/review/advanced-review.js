;(function($) {
    'use strict';

    class ReplyFormObserver {
        constructor() {
            this.init();

            $( document ).on( 'directorist_review_updated', () => this.init() );
        }

        init() {
            const node = document.querySelector('.commentlist');

            if (node) {
                this.observe(node);
            }
        }

        observe(node) {
            const config = {
                childList: true,
                subtree: true
            };
            const observer = new MutationObserver(this.callback);
            observer.observe(node, config);
        }

        callback(mutationsList, observer) {
            for (const mutation of mutationsList) {
                const target = mutation.target;

                if (mutation.removedNodes) {
                    target.classList.remove('directorist-form-added');

                    for (const node of mutation.removedNodes) {
                        if (!node.id || node.id !== 'respond') {
                            continue;
                        }

                        const criteria = node.querySelector('.directorist-review-criteria');
                        if (criteria) {
                            criteria.style.display = '';
                        }

                        const ratings = node.querySelectorAll('.directorist-review-criteria-select');
                        for (const rating of ratings) {
                            rating.removeAttribute('disabled');
                        }

                        node.querySelector('#submit').innerHTML = 'Submit Review';
                        node.querySelector('#comment').setAttribute('placeholder', 'Leave a review' );
                    }
                }

                const form = target.querySelector('#commentform');
                if (form) {
                    target.classList.add('directorist-form-added');
                    const isReview = target.classList.contains('review');
                    const isEditing = target.classList.contains('directorist-form-editing');

                    if (!isReview || (isReview && !isEditing)) {
                        const criteria = form.querySelector('.directorist-review-criteria');
                        if (criteria) {
                            criteria.style.display = 'none';
                        }

                        const ratings = form.querySelectorAll('.directorist-review-criteria-select');
                        for (const rating of ratings) {
                            rating.setAttribute('disabled', 'disabled');
                        }
                    }

                    const alert = form.querySelector('.directorist-alert');
                    if (alert) {
                        alert.style.display = 'none';
                    }

                    form.querySelector('#submit').innerHTML = 'Submit Comment';
                    form.querySelector('#comment').setAttribute('placeholder', 'Leave your comment' );
                }
            }
        };
    }

    class Ajax_Comment {

        constructor() {
            this.init();
        }

        init() {
            $( document ).on('submit', '#commentform', this.onSubmit );
        }

        static getErrorMsg($dom) {
            if ($dom.find('p').length) {
                return $dom.find('p').text();
            }
            return $dom.text();
        }

        static showError(form, $dom) {
            if (form.find('.directorist-alert').length) {
                form.find('.directorist-alert').remove();
            }
            const $error = $('<div />', {class: 'directorist-alert directorist-alert-danger'}).html(Ajax_Comment.getErrorMsg($dom));
            form.prepend($error)
        }

        onSubmit( event ) {
            event.preventDefault();

            const form                = $( '#commentform' );
            const originalButtonLabel = form.find( '[type="submit"]' ).val();

            $( document ).trigger( 'directorist_review_before_submit', form );

            const do_comment = $.ajax({
                url        : form.attr('action'),
                type       : 'POST',
                contentType: false,
                cache      : false,
                processData: false,
                data       : new FormData(form[0])
            });

            $( '#comment' ).prop( 'disabled', true );

            form.find( '[type="submit"]' ).prop( 'disabled', true ).val( 'loading' );

            do_comment.success(
                function ( data, status, request ) {
                    var body = $( '<div></div>' );
                    body.append( data );
                    var comment_section = '.directorist-review-container';
                    var comments = body.find( comment_section );

                    const errorMsg = body.find( '.wp-die-message' );
                    if (errorMsg.length > 0) {
                        Ajax_Comment.showError(form, errorMsg);

                        $( document ).trigger( 'directorist_review_update_failed' );

                        return;
                    }

                    let commentsLists = comments.find( '.commentlist li' );
                    let newCommentId  = false;

                    // catch the new comment id by comparing to old dom.
                    commentsLists.each(
                        function ( index ) {
                            var _this = $( commentsLists[ index ] );
                            if ( $( '#' + _this.attr( 'id' ) ).length == 0 ) {
                                newCommentId = _this.attr( 'id' );
                            }
                        }
                    );

                    $( comment_section ).replaceWith( comments );

                    $( document ).trigger( 'directorist_review_updated', data );

                    var commentTop = $( "#" + newCommentId ).offset().top;

                    if ( $( 'body' ).hasClass( 'admin-bar' ) ) {
                        commentTop = commentTop - $( '#wpadminbar' ).height();
                    }

                    // scroll to comment
                    if ( newCommentId ) {
                        $( "body, html" ).animate(
                            {
                                scrollTop: commentTop
                            },
                            600
                        );
                    }
                }
            );

            do_comment.fail(
                function ( data ) {
                    var body = $( '<div></div>' );
                    body.append( data.responseText );

                    Ajax_Comment.showError(form, body.find( '.wp-die-message' ));

                    $( document ).trigger( 'directorist_review_update_failed' );
                }
            );

            do_comment.always(
                function () {
                    $( '#comment' ).prop( 'disabled', false );
                    $( '#commentform' ).find( '[type="submit"]' ).prop( 'disabled', false ).val( originalButtonLabel );
                }
            );

            $( document ).trigger( 'directorist_review_after_submit', form );
        }
    }

    class CommentsManager {
        constructor() {
            this.$doc = $(document);

            this.setupComponents();
            this.addEventListeners();
            this.setFormEncodingAttribute();
        }

        initStarRating() {
            $('.directorist-stars, .directorist-review-criteria-select').barrating({
                theme: 'fontawesome-stars'
            });
        }

        cancelOthersEditMode(currentCommentId) {
            $('.directorist-comment-editing').each(function(index, comment) {
                const $cancelButton = $(comment).find('.directorist-js-cancel-comment-edit');

                if ($cancelButton.data('commentid') != currentCommentId) {
                    $cancelButton.click();
                }
            });
        }

        cancelReplyMode() {
            const replyLink = document.querySelector('#cancel-comment-reply-link');
            replyLink && replyLink.click();
        }

        addEventListeners() {
            const self = this;

            this.$doc.on( 'directorist_review_updated', (event) => {
                this.initStarRating();
                this.setFormEncodingAttribute();
            } );

            this.$doc.on('directorist_comment_edit_form_loaded', (event) => {
                this.initStarRating();
            });

            this.$doc.on('click', 'a[href="#respond"]', this.onWriteReivewClick);

            this.$doc.on('click', '.directorist-js-edit-comment', function(event) {
                event.preventDefault();

                const $target = $(event.target);
                const $wrap = $target.parents('#div-comment-'+$target.data('commentid'));

                $wrap.addClass('directorist-comment-edit-request');

                $.ajax({
                    url: $target.attr('href'),
                    data: {
                        post_id: $target.data('postid'),
                        comment_id: $target.data('commentid')
                    },
                    setContent: false,
                    method: 'GET',
                    reload: 'strict',
                    success: function(response) {
                        console.log(response);
                        $target
                            .parents('#div-comment-'+$target.data('commentid'))
                            .find('.directorist-review-single__contents-wrap').append(response.data.html);

                        $wrap
                            .removeClass('directorist-comment-edit-request')
                            .addClass('directorist-comment-editing');

                        self.cancelOthersEditMode($target.data('commentid'));
                        self.cancelReplyMode();

                        $('.directorist-form-comment-edit').find('textarea').focus();

                        self.$doc.trigger('directorist_comment_edit_form_loaded', $target.data('commentid'));
                    },
                });
            });

            this.$doc.on('click', '.directorist-js-cancel-comment-edit', (event) => {
                event.preventDefault();

                const $target = $(event.target);
                const $wrap = $target.parents('#div-comment-'+$target.data('commentid'));

                $wrap
                    .removeClass(['directorist-comment-edit-request', 'directorist-comment-editing'])
                    .find('form')
                    .remove();
            });
        }

        onWriteReivewClick(event) {
            event.preventDefault();

            let scrollTop = $('#respond').offset().top;

            if ($('body').hasClass('admin-bar') ) {
                scrollTop = scrollTop - $('#wpadminbar').height();
            }

            $('body, html').animate({scrollTop}, 600);
        }

        setupComponents() {
            new ReplyFormObserver();
            new Ajax_Comment();
        }

        setFormEncodingAttribute() {
            const form = document.querySelector('#commentform');
            if (form) {
                form.encoding = 'multipart/form-data';
            }
        }
    }

    const commentsManager = new CommentsManager();
}(jQuery));