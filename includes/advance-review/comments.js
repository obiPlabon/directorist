;(function($) {
    const BODY_REGEX = new RegExp("<body[^>]*>((.|\n|\r)*)</body>", "i");
    const TITLE_REGEX = new RegExp("<title[^>]*>(.*?)<\\/title>", "im");

    const extractBody = function(html) {
        try {
            return $('<div>' + BODY_REGEX.exec(html)[1] + '</div>');
        } catch(e) {
            return false;
        }
    }

    const extractTitle = function(html) {
        try {
            return TITLE_REGEX.exec(html)[1];
        } catch (e) {
            return false;
        }
    }

    const _ShowMessage = function (message, type) {
        $.blockUI({
            message: message,
            fadeIn: 2,
            fadeOut: 3,
            timeout: (type == "loading") ? 0 : 2,
            centerY: false,
            centerX: true,
            showOverlay: (type == "loading"),
            css: {
                width: "50%",
                left: ((100-50)/2) + "%",
                top: "100px",
                border: "none",
                padding: "20px",
                backgroundColor: "#eee",
                "border-radius": "5px",
                opacity: 3/100,
                color: "#000",
                textAlign: "center",
                cursor: (type == "loading") ? "wait" : "default",
                fontSize: "14px"
            },
            overlayCSS:  {
                backgroundColor: "#000",
                opacity: 0
            },
            baseZ: 9999
        });
    }

    const DEBUG_ENABLED = true;
    const DEBUG_ERROR_SHOWN = false;
    const _Debug = function(level, message) {
        if (!DEBUG_ENABLED) {
            return;
        }

        // Fix console.log.apply for IE9
        // see http://stackoverflow.com/a/5539378/516472
        if (Function.prototype.call && Function.prototype.call.bind && typeof window["console"] != "undefined" && console && typeof console.log == "object" && typeof window["console"][level].apply === "undefined") {
            console[level] = Function.prototype.call.bind(console[level], console);
        }

        if (typeof window["console"] === "undefined" || typeof window["console"][level] === "undefined" || typeof window["console"][level].apply === "undefined") {
            if (!DEBUG_ERROR_SHOWN) alert("Unfortunately the console object is undefined or is not supported in your browser, debugging Ajax Comment is disabled! Please use Firebug, Google Chrome or Internet Explorer 9 or above with enabled Developer Tools (F12) for debugging Ajax Comment.");
            DEBUG_ERROR_SHOWN = true;
            return;
        }

        var args = $.merge(["[Ajax Comment] " + message], $.makeArray(arguments).slice(2));
        console[level].apply(console, args);
    }

    const _DebugSelector = function(elementType, selector, optional) {
        if (!DEBUG_ENABLED) {
            return;
        }

        var element = $(selector);
        if (!element.length) {
            _Debug(optional ? "info" : "error", "Search %s (selector: '%s')... Not found", elementType, selector);
        } else {
            _Debug("info", "Search %s (selector: '%s')... Found: %o", elementType, selector, element);
        }
    }

    const _AddQueryParamStringToUrl = function(url, param, value) {
        return new Uri(url).replaceQueryParam(param, value).toString();
    }

    const _LoadFallbackUrl = function(fallbackUrl) {
        _ShowMessage(WPAC._Options.textReloadPage, "loading");

        const url = _AddQueryParamStringToUrl(fallbackUrl, "WPACRandom", (new Date()).getTime());

        _Debug("info", "Something went wrong. Reloading page (URL: '%s')...", url);

        const reload = function() {
            location.href = url;
        };

        if (!DEBUG_ENABLED) {
            reload();
        } else {
            _Debug("info", "Sleep for 5s to enable analyzing debug messages...");
            window.setTimeout(reload, 5000);
        }
    }

    const _ScrollToAnchor = function(anchor, updateHash, scrollComplete) {
        scrollComplete = scrollComplete || function() {};
        var anchorElement = $(anchor)

        if (anchorElement.length) {
            _Debug("info", "Scroll to anchor element %o (scroll speed: %s ms)...", anchorElement, WPAC._Options.scrollSpeed);

            const animateComplete = function() {
                if (updateHash) window.location.hash = anchor;
                scrollComplete();
            }

            var scrollTargetTopOffset = anchorElement.offset().top
            if ($(window).scrollTop() == scrollTargetTopOffset) {
                animateComplete();
            } else {
                $("html,body").animate({scrollTop: scrollTargetTopOffset}, {
                    duration: 400,
                    complete: animateComplete
                });
            }
            return true;
        } else {
            _Debug("error", "Anchor element not found (selector: '%s')", anchor);
            return false;
        }
    }

    const _UpdateUrl = function(url) {
        if (url.split("#")[0] === window.location.href.split("#")[0]) {
            return;
        }

        if (window.history.replaceState) {
            window.history.replaceState({}, window.document.title, url);
        } else {
            _Debug("info", "Browser does not support window.history.replaceState() to update the URL without reloading the page", anchor);
        }
    }

    const COMMENTS_ENABLED = true;
    const _ReplaceComments = function(
            data,
            commentUrl,
            useFallbackUrl,
            formData,
            formFocus,
            selectorCommentsContainer,
            selectorCommentForm,
            selectorRespondContainer,
            beforeSelectElements,
            beforeUpdateComments,
            afterUpdateComments
            ) {

        const fallbackUrl = useFallbackUrl ? _AddQueryParamStringToUrl(commentUrl, "WPACFallback", "1") : commentUrl;

        const oldCommentsContainer = $(selectorCommentsContainer);
        if (!oldCommentsContainer.length) {
            _Debug("error", "Comment container on current page not found (selector: '%s')", selectorCommentsContainer);
            _LoadFallbackUrl(fallbackUrl);
            return false;
        }

        const extractedBody = _ExtractBody(data);
        if (extractedBody === false) {
            _Debug("error", "Unsupported server response, unable to extract body (data: '%s')", data);
            _LoadFallbackUrl(fallbackUrl);
            return false;
        }

        beforeSelectElements(extractedBody);

        var newCommentsContainer = extractedBody.find(selectorCommentsContainer);
        if (!newCommentsContainer.length) {
            _Debug("error", "Comment container on requested page not found (selector: '%s')", selectorCommentsContainer);
            _LoadFallbackUrl(fallbackUrl);
            return false;
        }

        beforeUpdateComments(extractedBody, commentUrl);

        // Update title
        var extractedTitle = _ExtractTitle(data);
        if (extractedBody !== false)
            // Decode HTML entities (see http://stackoverflow.com/a/5796744)
            document.title = $('<textarea />').html(extractedTitle).text();

        // Update comments container
        oldCommentsContainer.replaceWith(newCommentsContainer);

        if (COMMENTS_ENABLED) {
            const form = $(selectorCommentForm);

            if (form.length) {
                // Replace comment form (for spam protection plugin compatibility) if comment form is not nested in comments container
                // If comment form is nested in comments container comment form has already been replaced
                if (!form.parents(selectorCommentsContainer).length) {
                    _Debug("info", "Replace comment form...");

                    const newCommentForm = extractedBody.find(selectorCommentForm);
                    if (newCommentForm.length == 0) {
                        _Debug("error", "Comment form on requested page not found (selector: '%s')", selectorCommentForm);
                        _LoadFallbackUrl(fallbackUrl);
                        return false;
                    }
                    form.replaceWith(newCommentForm);
                }

            } else {

                _Debug("info", "Try to re-inject comment form...");

                // "Re-inject" comment form, if comment form was removed by updating the comments container; could happen
                // if theme support threaded/nested comments and form tag is not nested in comments container
                // -> Replace WordPress placeholder <div> (#wp-temp-form-div) with respond <div>
                const wpTempFormDiv = $("#wp-temp-form-div");
                if (!wpTempFormDiv.length) {
                    _Debug("error", "WordPress' #wp-temp-form-div container not found", selectorRespondContainer);
                    _LoadFallbackUrl(fallbackUrl);
                    return false;
                }

                const newRespondContainer = extractedBody.find(selectorRespondContainer);
                if (!newRespondContainer.length) {
                    _Debug("error", "Respond container on requested page not found (selector: '%s')", selectorRespondContainer);
                    _LoadFallbackUrl(fallbackUrl);
                    return false;
                }

                wpTempFormDiv.replaceWith(newRespondContainer);
            }

            if (formData) {
                // Re-inject saved form data
                $.each(formData, function(key, value) {
                    var formElement = $("[name='"+value.name+"']", selectorCommentForm);
                    if (formElement.length != 1 || formElement.val()) {
                        return;
                    }
                    formElement.val(value.value);
                });
            }
            if (formFocus) {
                // Reset focus
                const formElement = $("[name='"+formFocus+"']", selectorCommentForm);
                if (formElement) {
                    formElement.focus();
                }
            }
        }

        afterUpdateComments(extractedBody, commentUrl);

        return true;
    }

    const _TestCrossDomainScripting = function(url) {
        if (url.indexOf("http") !== 0) {
            return false;
        }

        const domain = window.location.protocol + "//" + window.location.host;

        return (url.indexOf(domain) !== 0);
    }

    const _TestFallbackUrl = function(url) {
        const url = new Uri(location.href);

        return (url.getQueryParamValue("WPACFallback") && url.getQueryParamValue("WPACRandom"));
    }

    const AttachForm = function(options) {
        // Set default options
        options = $.extend({
            selectorCommentForm: WPAC._Options.selectorCommentForm,
            selectorCommentPagingLinks: WPAC._Options.selectorCommentPagingLinks,
            beforeSelectElements: WPAC._Callbacks.beforeSelectElements,
            beforeSubmitComment: WPAC._Callbacks.beforeSubmitComment,
            afterPostComment: WPAC._Callbacks.afterPostComment,
            selectorCommentsContainer: WPAC._Options.selectorCommentsContainer,
            selectorRespondContainer: WPAC._Options.selectorRespondContainer,
            beforeUpdateComments: WPAC._Callbacks.beforeUpdateComments,
            afterUpdateComments: WPAC._Callbacks.afterUpdateComments,
            scrollToAnchor: !WPAC._Options.disableScrollToAnchor,
            updateUrl: !WPAC._Options.disableUrlUpdate,
            selectorCommentLinks: WPAC._Options.selectorCommentLinks
        }, options || {});

        if (DEBUG_ENABLED && COMMENTS_ENABLED) {
            _Debug("info", "Attach form...")
            _DebugSelector("comment form", options.selectorCommentForm);
            _DebugSelector("comments container",options.selectorCommentsContainer);
            _DebugSelector("respond container", options.selectorRespondContainer)
            _DebugSelector("comment paging links", options.selectorCommentPagingLinks, true);
            _DebugSelector("comment links", options.selectorCommentLinks, true);
        }

        options.beforeSelectElements($(document));

        const addHandler = function(event, selector, handler) {
            $(document).on(event, selector, handler)
        }

        // Handle paging link clicks
        const pagingClickHandler = function(event) {
            const href = $(this).attr("href");
            if (href) {
                event.preventDefault();
                WPAC.LoadComments(href, {
                    selectorCommentForm: options.selectorCommentForm,
                    selectorCommentsContainer: options.selectorCommentsContainer,
                    selectorRespondContainer: options.selectorRespondContainer,
                    beforeSelectElements: options.beforeSelectElements,
                    beforeUpdateComments: options.beforeUpdateComments,
                    afterUpdateComments: options.afterUpdateComments
                });
            }
        };
        addHandler("click", options.selectorCommentPagingLinks, pagingClickHandler);

        // Handle comment link clicks
        const linkClickHandler = function(event) {
            var element = $(this);
            if (element.is(options.selectorCommentPagingLinks)) {
                return; // skip if paging link was clicked
            }

            const href = element.attr("href");
            const anchor = "#" + (new Uri(href)).anchor();

            if ($(anchor).length > 0) {
                if (options.updateUrl) {
                    _UpdateUrl(href);
                }
                _ScrollToAnchor(anchor, options.updateUrl);
                event.preventDefault();
            }
        };
        addHandler("click", options.selectorCommentLinks, linkClickHandler);

        if (!COMMENTS_ENABLED) {
            return;
        }

        // Handle form submit
        const formSubmitHandler = function (event) {
            const form = $(this);

            options.beforeSubmitComment();

            const submitUrl = form.attr("action");

            // Cancel AJAX request if cross-domain scripting is detected
            if (_TestCrossDomainScripting(submitUrl)) {
                if (DEBUG_ENABLED && !form.data("submitCrossDomain")) {
                    _Debug("error", "Cross-domain scripting detected (submit url: '%s'), cancel AJAX request", submitUrl);
                    _Debug("info", "Sleep for 5s to enable analyzing debug messages...");
                    event.preventDefault();
                    form.data("submitCrossDomain", true)
                    window.setTimeout(function() {
                        $('#submit', form).remove(); form.submit();
                    }, 5000);
                }
                return;
            }

            // Stop default event handling
            event.preventDefault();

            // Test if form is already submitting
            if (form.data("WPAC_SUBMITTING")) {
                _Debug("info", "Cancel submit, form is already submitting (Form: %o)", form);
                return;
            }
            form.data("WPAC_SUBMITTING", true);

            // Show loading info
            _ShowMessage(WPAC._Options.textPostComment, "loading");

            var handleErrorResponse = function(data) {
                _Debug("info", "Comment has not been posted");
                _Debug("info", "Try to extract error message (selector: '%s')...", WPAC._Options.selectorErrorContainer);

                // Extract error message
                var extractedBody = _ExtractBody(data);
                if (extractedBody !== false) {
                    var errorMessage = extractedBody.find(WPAC._Options.selectorErrorContainer);
                    if (errorMessage.length) {
                        errorMessage = errorMessage.html();
                        _Debug("info", "Error message '%s' successfully extracted", errorMessage);
                        _ShowMessage(errorMessage, "error");
                        return;
                    }
                }

                _Debug("error", "Error message could not be extracted, use error message '%s'.", WPAC._Options.textUnknownError);
                _ShowMessage(WPAC._Options.textUnknownError, "error");
            }

            const request = jQuery.ajax({
                url: submitUrl,
                type: "POST",
                data: form.serialize(),
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WPAC-REQUEST', '1'); },
                complete: function(xhr, textStatus) { form.removeData("WPAC_SUBMITTING", true); },
                success: function (data) {

                    // Test error state (WordPress >=4.1 does not return 500 status code if posting comment failed)
                    if (request.getResponseHeader("X-WPAC-ERROR")) {
                        _Debug("info", "Found error state X-WPAC-ERROR header.", commentUrl);
                        handleErrorResponse(data);
                        return;
                    }

                    _Debug("info", "Comment has been posted");

                    // Get info from response header
                    const commentUrl = request.getResponseHeader("X-WPAC-URL");
                    _Debug("info", "Found comment URL '%s' in X-WPAC-URL header.", commentUrl);

                    const unapproved = request.getResponseHeader("X-WPAC-UNAPPROVED");
                    _Debug("info", "Found unapproved state '%s' in X-WPAC-UNAPPROVED", unapproved);

                    options.afterPostComment(commentUrl, unapproved == '1');

                    // Show success message
                    _ShowMessage(unapproved == '1' ? WPAC._Options.textPostedUnapproved : WPAC._Options.textPosted, "success");

                    // Replace comments (and return if replacing failed)
                    if (!_ReplaceComments(data, commentUrl, false, {}, "", options.selectorCommentsContainer, options.selectorCommentForm, options.selectorRespondContainer, options.beforeSelectElements, options.beforeUpdateComments, options.afterUpdateComments)) {
                        return;
                    }

                    // Smooth scroll to comment url and update browser url
                    if (commentUrl) {
                        if (options.updateUrl)
                            _UpdateUrl(commentUrl);

                        if (options.scrollToAnchor) {
                            var anchor = commentUrl.indexOf("#") >= 0 ? commentUrl.substr(commentUrl.indexOf("#")) : null;
                            if (anchor) {
                                _Debug("info", "Anchor '%s' extracted from comment URL '%s'", anchor, commentUrl);
                                _ScrollToAnchor(anchor, options.updateUrl);
                            }
                        }
                    }
                },
                error: function (jqXhr, textStatus, errorThrown) {
                    // Test if loading comment url failed (due to cross site scripting error)
                    if (jqXhr.status === 0 && jqXhr.responseText === "") {
                        _Debug("error", "Comment seems to be posted, but loading comment update failed.");
                        _LoadFallbackUrl(_AddQueryParamStringToUrl(window.location.href, "WPACFallback", "1"));
                        return;
                    }

                    handleErrorResponse(jqXhr.responseText);
                }
            });
        };
        addHandler("submit", options.selectorCommentForm, formSubmitHandler)
    }

    let IS_INITIALIZED = false;
    const Init = function() {

        // Test if plugin already has been initialized
        if (IS_INITIALIZED) {
            _Debug("info", "Abort initialization (plugin already initialized)");
            return false;
        }
        IS_INITIALIZED = true;

        // Assert that environment is set up correctly
        if (!WPAC._Options || !WPAC._Callbacks) {
            _Debug("error", "Something unexpected happened, initialization failed. Please try to reinstall the plugin.");
            return false;
        }

        // Debug infos
        _Debug("info", "Initializing version %s", WPAC._Options.version);

        // Debug infos
        if (DEBUG_ENABLED) {
            if (!$.blockUI || !$.blockUI.version) {
                _Debug("error", "jQuery blockUI not found, abort initialization. Please try to reinstall the plugin.");
                return false;
            }
            _Debug("info", "Found jQuery blockUI %s", $.blockUI.version);
            if (!$.idleTimer) {
                _Debug("error", "jQuery Idle Timer plugin not found, abort initialization. Please try to reinstall the plugin.");
                return false;
            }
            _Debug("info", "Found jQuery Idle Timer plugin");
        }

        if (WPAC._Options.selectorPostContainer) {
            _Debug("info", "Multiple comment form support enabled (selector: '%s')", WPAC._Options.selectorPostContainer);
            jQuery(WPAC._Options.selectorPostContainer).each(function(i,e) {
                var id = $(e).attr("id");
                if (!id) {
                    _Debug("info", "Skip post container element %o (ID not defined)", e);
                    return
                }

                AttachForm({
                    selectorCommentForm: "#" + id + " " + WPAC._Options.selectorCommentForm,
                    selectorCommentPagingLinks: "#" + id + " " + WPAC._Options.selectorCommentPagingLinks,
                    selectorCommentsContainer: "#" + id + " " + WPAC._Options.selectorCommentsContainer,
                    selectorRespondContainer: "#" + id + " " + WPAC._Options.selectorRespondContainer
                });
            });
        } else {
            AttachForm();
        }

        // Set up idle timer
        if (COMMENTS_ENABLED && WPAC._Options.autoUpdateIdleTime > 0) {
            _Debug("info", "Auto updating comments enabled (idle time: %s)", WPAC._Options.autoUpdateIdleTime);
            _InitIdleTimer();
        }

        _Debug("info", "Initialization completed");

        return true;
    }

    _OnIdle = function() {
        RefreshComments({ success: _InitIdleTimer, scrollToAnchor: false});
    };

    _InitIdleTimer = function() {
        if (_TestFallbackUrl(location.href)) {
            _Debug("error", "Fallback URL was detected (url: '%s'), cancel init idle timer", location.href);
            return;
        }

        $(document).idleTimer("destroy");
        $(document).idleTimer(WPAC._Options.autoUpdateIdleTime);
        $(document).on("idle.idleTimer", _OnIdle);
    }

    RefreshComments = function(options) {
        const url = location.href;

        if (_TestFallbackUrl(url)) {
            _Debug("error", "Fallback URL was detected (url: '%s'), cancel AJAX request", url);
            return false;
        }

        return LoadComments(url, options)
    }

    WPAC.LoadComments = function(url, options) {

        // Cancel AJAX request if cross-domain scripting is detected
        if (WPAC._TestCrossDomainScripting(url)) {
            WPAC._Debug("error", "Cross-domain scripting detected (url: '%s'), cancel AJAX request", url);
            return false;
        }

        // Convert boolean parameter (used in version <0.14.0)
        if (typeof(options) == "boolean")
            options = {scrollToAnchor: options}

        // Set default options
        options = jQuery.extend({
            scrollToAnchor: !WPAC._Options.disableScrollToAnchor,
            showLoadingInfo: true,
            updateUrl: !WPAC._Options.disableUrlUpdate,
            success: function() {},
            selectorCommentForm: WPAC._Options.selectorCommentForm,
            selectorCommentsContainer: WPAC._Options.selectorCommentsContainer,
            selectorRespondContainer: WPAC._Options.selectorRespondContainer,
            disableCache: WPAC._Options.disableCache,
            beforeSelectElements: WPAC._Callbacks.beforeSelectElements,
            beforeUpdateComments: WPAC._Callbacks.beforeUpdateComments,
            afterUpdateComments: WPAC._Callbacks.afterUpdateComments,
        }, options || {});

        // Save form data and focus
        var formData = jQuery(options.selectorCommentForm).serializeArray();
        var formFocus = (document.activeElement) ? jQuery("[name='"+document.activeElement.name+"']", options.selectorCommentForm).attr("name") : "";

        // Show loading info
        if (options.showLoadingInfo)
            WPAC._ShowMessage(WPAC._Options.textRefreshComments, "loading");

        if (options.disableCache)
            url = WPAC._AddQueryParamStringToUrl(url, "WPACRandom", (new Date()).getTime());

        var request = jQuery.ajax({
            url: url,
            type: "GET",
            beforeSend: function(xhr){ xhr.setRequestHeader("X-WPAC-REQUEST", "1"); },
            success: function (data) {

                // Replace comments (and return if replacing failed)
                if (!WPAC._ReplaceComments(data, url, true, formData, formFocus, options.selectorCommentsContainer, options.selectorCommentForm,
                    options.selectorRespondContainer, options.beforeSelectElements, options.beforeUpdateComments, options.afterUpdateComments)) return;

                if (options.updateUrl) WPAC._UpdateUrl(url);

                // Scroll to anchor
                var waitForScrollToAnchor = false;
                if (options.scrollToAnchor) {
                    var anchor = url.indexOf("#") >= 0 ? url.substr(url.indexOf("#")) : null;
                    if (anchor) {
                        WPAC._Debug("info", "Anchor '%s' extracted from url", anchor);
                        if (WPAC._ScrollToAnchor(anchor, options.updateUrl, function() { options.success(); } )) {
                            waitForScrollToAnchor = true;
                        }
                    }
                }

                // Unblock UI
                jQuery.unblockUI();

                if (!waitForScrollToAnchor) options.success();
            },
            error: function() {
                WPAC._LoadFallbackUrl(WPAC._AddQueryParamStringToUrl(window.location.href, "WPACFallback", "1"))
            }

        });

        return true;
    }


}(jQuery));
