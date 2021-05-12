;(function($) {
    if (!window["ARF"]) var ARF = {};
    ARF._Options = ARF._Options || {};

    ARF._BodyRegex = new RegExp("<body[^>]*>((.|\n|\r)*)</body>", "i");
    ARF._ExtractBody = function(html) {
        try {
            return $("<div>"+ARF._BodyRegex.exec(html)[1]+"</div>");
        } catch (e) {
            return false;
        }
    }

    ARF._TitleRegex = new RegExp("<title[^>]*>(.*?)<\\/title>", "im");
    ARF._ExtractTitle = function(html) {
        try {
            return ARF._TitleRegex.exec(html)[1];
        } catch (e) {
            return false;
        }
    }

    ARF._ShowMessage = function (message, type) {

        var top = ARF._Options.popupMarginTop + ($("#wpadminbar").outerHeight() || 0);

        var backgroundColor = ARF._Options.popupBackgroundColorLoading;
        var textColor = ARF._Options.popupTextColorLoading;
        if (type == "error") {
            backgroundColor = ARF._Options.popupBackgroundColorError;
            textColor = ARF._Options.popupTextColorError;
        } else if (type == "success") {
            backgroundColor = ARF._Options.popupBackgroundColorSuccess;
            textColor = ARF._Options.popupTextColorSuccess;
        }

        $.blockUI({
            message: message,
            fadeIn: ARF._Options.popupFadeIn,
            fadeOut: ARF._Options.popupFadeOut,
            timeout:(type == "loading") ? 0 : ARF._Options.popupTimeout,
            centerY: false,
            centerX: true,
            showOverlay: (type == "loading"),
            css: {
                width: ARF._Options.popupWidth + "%",
                left: ((100-ARF._Options.popupWidth)/2) + "%",
                top: top + "px",
                border: "none",
                padding: ARF._Options.popupPadding + "px",
                backgroundColor: backgroundColor,
                "-webkit-border-radius": ARF._Options.popupCornerRadius + "px",
                "-moz-border-radius": ARF._Options.popupCornerRadius + "px",
                "border-radius": ARF._Options.popupCornerRadius + "px",
                opacity: ARF._Options.popupOpacity/100,
                color: textColor,
                textAlign: ARF._Options.popupTextAlign,
                cursor: (type == "loading") ? "wait" : "default",
                "font-size": ARF._Options.popupTextFontSize
            },
            overlayCSS:  {
                backgroundColor: "#000",
                opacity: 0
            },
            baseZ: ARF._Options.popupZindex
        });

    }

    ARF._DebugErrorShown = false;
    ARF._Debug = function(level, message) {

        if (!ARF._Options.debug) return;

        // Fix console.log.apply for IE9
        // see http://stackoverflow.com/a/5539378/516472
        if (Function.prototype.call && Function.prototype.call.bind && typeof window["console"] != "undefined" && console && typeof console.log == "object" && typeof window["console"][level].apply === "undefined") {
            console[level] = Function.prototype.call.bind(console[level], console);
        }

        if (typeof window["console"] === "undefined" || typeof window["console"][level] === "undefined" || typeof window["console"][level].apply === "undefined") {
            if (!ARF._DebugErrorShown) alert("Unfortunately the console object is undefined or is not supported in your browser, debugging WP Ajaxify Comments is disabled! Please use Firebug, Google Chrome or Internet Explorer 9 or above with enabled Developer Tools (F12) for debugging WP Ajaxify Comments.");
            ARF._DebugErrorShown = true;
            return;
        }

        var args = $.merge(["[WP Ajaxify Comments] " + message], $.makeArray(arguments).slice(2));
        console[level].apply(console, args);
    }

    ARF._DebugSelector = function(elementType, selector, optional) {
        if (!ARF._Options.debug) return;

        var element = $(selector);
        if (!element.length) {
            ARF._Debug(optional ? "info" : "error", "Search %s (selector: '%s')... Not found", elementType, selector);
        } else {
            ARF._Debug("info", "Search %s (selector: '%s')... Found: %o", elementType, selector, element);
        }
    }

    ARF._AddQueryParamStringToUrl = function(url, param, value) {
        return new Uri(url).replaceQueryParam(param, value).toString();
    }

    ARF._LoadFallbackUrl = function(fallbackUrl) {

        ARF._ShowMessage(ARF._Options.textReloadPage, "loading");

        var url = ARF._AddQueryParamStringToUrl(fallbackUrl, "WPACRandom", (new Date()).getTime());
        ARF._Debug("info", "Something went wrong. Reloading page (URL: '%s')...", url);

        var reload = function() { location.href = url; };
        if (!ARF._Options.debug) {
            reload();
        } else {
            ARF._Debug("info", "Sleep for 5s to enable analyzing debug messages...");
            window.setTimeout(reload, 5000);
        }
    }

    ARF._ScrollToAnchor = function(anchor, updateHash, scrollComplete) {
        scrollComplete = scrollComplete || function() {};
        var anchorElement = $(anchor)
        if (anchorElement.length) {
            ARF._Debug("info", "Scroll to anchor element %o (scroll speed: %s ms)...", anchorElement, ARF._Options.scrollSpeed);
            var animateComplete = function() {
                if (updateHash) window.location.hash = anchor;
                scrollComplete();
            }
            var scrollTargetTopOffset = anchorElement.offset().top
            if ($(window).scrollTop() == scrollTargetTopOffset) {
                animateComplete();
            } else {
                $("html,body").animate({scrollTop: scrollTargetTopOffset}, {
                    duration: ARF._Options.scrollSpeed,
                    complete: animateComplete
                });
            }
            return true;
        } else {
            ARF._Debug("error", "Anchor element not found (selector: '%s')", anchor);
            return false;
        }
    }

    ARF._UpdateUrl= function(url) {
        if (url.split("#")[0] == window.location.href.split("#")[0]) {
            return;
        }
        if (window.history.replaceState) {
            window.history.replaceState({}, window.document.title, url);
        } else {
            ARF._Debug("info", "Browser does not support window.history.replaceState() to update the URL without reloading the page", anchor);
        }
    }

    ARF._ReplaceComments = function(data, commentUrl, useFallbackUrl, formData, formFocus, selectorCommentsContainer, selectorCommentForm, selectorRespondContainer, beforeSelectElements, beforeUpdateComments, afterUpdateComments) {

        var fallbackUrl = useFallbackUrl ? ARF._AddQueryParamStringToUrl(commentUrl, "WPACFallback", "1") : commentUrl;

        var oldCommentsContainer = $(selectorCommentsContainer);
        if (!oldCommentsContainer.length) {
            ARF._Debug("error", "Comment container on current page not found (selector: '%s')", selectorCommentsContainer);
            ARF._LoadFallbackUrl(fallbackUrl);
            return false;
        }

        var extractedBody = ARF._ExtractBody(data);
        if (extractedBody === false) {
            ARF._Debug("error", "Unsupported server response, unable to extract body (data: '%s')", data);
            ARF._LoadFallbackUrl(fallbackUrl);
            return false;
        }

        beforeSelectElements(extractedBody);

        var newCommentsContainer = extractedBody.find(selectorCommentsContainer);
        if (!newCommentsContainer.length) {
            ARF._Debug("error", "Comment container on requested page not found (selector: '%s')", selectorCommentsContainer);
            ARF._LoadFallbackUrl(fallbackUrl);
            return false;
        }

        beforeUpdateComments(extractedBody, commentUrl);

        // Update title
        var extractedTitle = ARF._ExtractTitle(data);
        if (extractedBody !== false)
            // Decode HTML entities (see http://stackoverflow.com/a/5796744)
            document.title = $('<textarea />').html(extractedTitle).text();

        // Update comments container
        oldCommentsContainer.replaceWith(newCommentsContainer);

        if (ARF._Options.commentsEnabled) {

            var form = $(selectorCommentForm);
            if (form.length) {

                // Replace comment form (for spam protection plugin compatibility) if comment form is not nested in comments container
                // If comment form is nested in comments container comment form has already been replaced
                if (!form.parents(selectorCommentsContainer).length) {

                    ARF._Debug("info", "Replace comment form...");
                    var newCommentForm = extractedBody.find(selectorCommentForm);
                    if (newCommentForm.length == 0) {
                        ARF._Debug("error", "Comment form on requested page not found (selector: '%s')", selectorCommentForm);
                        ARF._LoadFallbackUrl(fallbackUrl);
                        return false;
                    }
                    form.replaceWith(newCommentForm);
                }

            } else {

                ARF._Debug("info", "Try to re-inject comment form...");

                // "Re-inject" comment form, if comment form was removed by updating the comments container; could happen
                // if theme support threaded/nested comments and form tag is not nested in comments container
                // -> Replace WordPress placeholder <div> (#wp-temp-form-div) with respond <div>
                var wpTempFormDiv = $("#wp-temp-form-div");
                if (!wpTempFormDiv.length) {
                    ARF._Debug("error", "WordPress' #wp-temp-form-div container not found", selectorRespondContainer);
                    ARF._LoadFallbackUrl(fallbackUrl);
                    return false;
                }
                var newRespondContainer = extractedBody.find(selectorRespondContainer);
                if (!newRespondContainer.length) {
                    ARF._Debug("error", "Respond container on requested page not found (selector: '%s')", selectorRespondContainer);
                    ARF._LoadFallbackUrl(fallbackUrl);
                    return false;
                }
                wpTempFormDiv.replaceWith(newRespondContainer);

            }

            if (formData) {
                // Re-inject saved form data
                $.each(formData, function(key, value) {
                    var formElement = $("[name='"+value.name+"']", selectorCommentForm);
                    if (formElement.length != 1 || formElement.val()) return;
                    formElement.val(value.value);
                });
            }
            if (formFocus) {
                // Reset focus
                var formElement = $("[name='"+formFocus+"']", selectorCommentForm);
                if (formElement) formElement.focus();
            }

        }

        afterUpdateComments(extractedBody, commentUrl);

        return true;
    }

    ARF._TestCrossDomainScripting = function(url) {
        if (url.indexOf("http") != 0) return false;
        var domain = window.location.protocol + "//" + window.location.host;
        return (url.indexOf(domain) != 0);
    }

    ARF._TestFallbackUrl = function(url) {
        var url = new Uri(location.href);
        return (url.getQueryParamValue("WPACFallback") && url.getQueryParamValue("WPACRandom"));
    }

    ARF.AttachForm = function(options) {

        // Set default options
        options = $.extend({
            selectorCommentForm: ARF._Options.selectorCommentForm,
            selectorCommentPagingLinks: ARF._Options.selectorCommentPagingLinks,
            beforeSelectElements: ARF._Callbacks.beforeSelectElements,
            beforeSubmitComment: ARF._Callbacks.beforeSubmitComment,
            afterPostComment: ARF._Callbacks.afterPostComment,
            selectorCommentsContainer: ARF._Options.selectorCommentsContainer,
            selectorRespondContainer: ARF._Options.selectorRespondContainer,
            beforeUpdateComments: ARF._Callbacks.beforeUpdateComments,
            afterUpdateComments: ARF._Callbacks.afterUpdateComments,
            scrollToAnchor: !ARF._Options.disableScrollToAnchor,
            updateUrl: !ARF._Options.disableUrlUpdate,
            selectorCommentLinks: ARF._Options.selectorCommentLinks
        }, options || {});

        if (ARF._Options.debug && ARF._Options.commentsEnabled) {
            ARF._Debug("info", "Attach form...")
            ARF._DebugSelector("comment form", options.selectorCommentForm);
            ARF._DebugSelector("comments container",options.selectorCommentsContainer);
            ARF._DebugSelector("respond container", options.selectorRespondContainer)
            ARF._DebugSelector("comment paging links", options.selectorCommentPagingLinks, true);
            ARF._DebugSelector("comment links", options.selectorCommentLinks, true);
        }

        options.beforeSelectElements($(document));

        // Get addHandler method
        if ($(document).on) {
            // jQuery 1.7+
            var addHandler = function(event, selector, handler) {
                $(document).on(event, selector, handler)
            }
        } else if ($(document).delegate) {
            // jQuery 1.4.3+
            var addHandler = function(event, selector, handler) {
                $(document).delegate(selector, event, handler)
            }
        } else {
            // jQuery 1.3+
            var addHandler = function(event, selector, handler) {
                $(selector).live(event, handler)
            }
        }

        // Handle paging link clicks
        var pagingClickHandler = function(event) {
            var href = $(this).attr("href");
            if (href) {
                event.preventDefault();
                ARF.LoadComments(href, {
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
        var linkClickHandler = function(event) {
            var element = $(this);
            if (element.is(options.selectorCommentPagingLinks)) return; // skip if paging link was clicked
            var href = element.attr("href");
            var anchor = "#" + (new Uri(href)).anchor();
            if ($(anchor).length > 0) {
                if (options.updateUrl) ARF._UpdateUrl(href);
                ARF._ScrollToAnchor(anchor, options.updateUrl);
                event.preventDefault();
            }
        };
        addHandler("click", options.selectorCommentLinks, linkClickHandler);

        if (!ARF._Options.commentsEnabled) return;

        // Handle form submit
        var formSubmitHandler = function (event) {
            var form = $(this);

            options.beforeSubmitComment();

            var submitUrl = form.attr("action");

            // Cancel AJAX request if cross-domain scripting is detected
            if (ARF._TestCrossDomainScripting(submitUrl)) {
                if (ARF._Options.debug && !form.data("submitCrossDomain")) {
                    ARF._Debug("error", "Cross-domain scripting detected (submit url: '%s'), cancel AJAX request", submitUrl);
                    ARF._Debug("info", "Sleep for 5s to enable analyzing debug messages...");
                    event.preventDefault();
                    form.data("submitCrossDomain", true)
                    window.setTimeout(function() { $('#submit', form).remove(); form.submit(); }, 5000);
                }
                return;
            }

            // Stop default event handling
            event.preventDefault();

            // Test if form is already submitting
            if (form.data("WPAC_SUBMITTING")) {
                ARF._Debug("info", "Cancel submit, form is already submitting (Form: %o)", form);
                return;
            }
            form.data("WPAC_SUBMITTING", true);

            // Show loading info
            ARF._ShowMessage(ARF._Options.textPostComment, "loading");

            var handleErrorResponse = function(data) {

                ARF._Debug("info", "Comment has not been posted");
                ARF._Debug("info", "Try to extract error message (selector: '%s')...", ARF._Options.selectorErrorContainer);

                // Extract error message
                var extractedBody = ARF._ExtractBody(data);
                console.log('lolol', extractedBody);
                if (extractedBody !== false) {
                    // we need this to select the wrapper
                    var errorMessage = extractedBody.find('.wp-die-message');
                    if (errorMessage.length) {
                        errorMessage = errorMessage.html();
                        ARF._Debug("info", "Error message '%s' successfully extracted", errorMessage);
                        ARF._ShowMessage(errorMessage, "error");
                        return;
                    }
                }

                ARF._Debug("error", "Error message could not be extracted, use error message '%s'.", ARF._Options.textUnknownError);
                ARF._ShowMessage(ARF._Options.textUnknownError, "error");
            }

            var request = $.ajax({
                url: submitUrl,
                type: 'POST',
                contentType: false,
                cache: false,
                processData: false,
                data: new FormData(form[0]),
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WPAC-REQUEST', '1'); },
                complete: function(xhr, textStatus) { form.removeData("WPAC_SUBMITTING", true); },
                success: function (data) {

                    // Test error state (WordPress >=4.1 does not return 500 status code if posting comment failed)
                    if (request.getResponseHeader("X-WPAC-ERROR")) {
                        ARF._Debug("info", "Found error state X-WPAC-ERROR header.", commentUrl);
                        handleErrorResponse(data);
                        return;
                    }

                    ARF._Debug("info", "Comment has been posted");

                    // Get info from response header
                    var commentUrl = request.getResponseHeader("X-WPAC-URL");
                    ARF._Debug("info", "Found comment URL '%s' in X-WPAC-URL header.", commentUrl);
                    var unapproved = request.getResponseHeader("X-WPAC-UNAPPROVED");
                    ARF._Debug("info", "Found unapproved state '%s' in X-WPAC-UNAPPROVED", unapproved);

                    options.afterPostComment(commentUrl, unapproved == '1');

                    // Show success message
                    ARF._ShowMessage(unapproved == '1' ? ARF._Options.textPostedUnapproved : ARF._Options.textPosted, "success");

                    // Replace comments (and return if replacing failed)
                    if (!ARF._ReplaceComments(data, commentUrl, false, {}, "", options.selectorCommentsContainer, options.selectorCommentForm, options.selectorRespondContainer,
                        options.beforeSelectElements, options.beforeUpdateComments, options.afterUpdateComments)) return;

                    // Smooth scroll to comment url and update browser url
                    if (commentUrl) {

                        if (options.updateUrl)
                            ARF._UpdateUrl(commentUrl);

                        if (options.scrollToAnchor) {
                            var anchor = commentUrl.indexOf("#") >= 0 ? commentUrl.substr(commentUrl.indexOf("#")) : null;
                            if (anchor) {
                                ARF._Debug("info", "Anchor '%s' extracted from comment URL '%s'", anchor, commentUrl);
                                ARF._ScrollToAnchor(anchor, options.updateUrl);
                            }
                        }
                    }

                },
                error: function (jqXhr, textStatus, errorThrown) {

                    console.log(jqXhr);

                    // Test if loading comment url failed (due to cross site scripting error)
                    if (jqXhr.status === 0 && jqXhr.responseText === "") {
                        ARF._Debug("error", "Comment seems to be posted, but loading comment update failed.");
                        ARF._LoadFallbackUrl(ARF._AddQueryParamStringToUrl(window.location.href, "WPACFallback", "1"));
                        return;
                    }

                    handleErrorResponse(jqXhr.responseText);
                }
            });
        };
        addHandler("submit", options.selectorCommentForm, formSubmitHandler)
    }

    ARF._Initialized = false;
    ARF.Init = function() {

        // Test if plugin already has been initialized
        if (ARF._Initialized) {
            ARF._Debug("info", "Abort initialization (plugin already initialized)");
            return false;
        }
        ARF._Initialized = true;

        // Assert that environment is set up correctly
        if (!ARF._Options || !ARF._Callbacks) {
            ARF._Debug("error", "Something unexpected happened, initialization failed. Please try to reinstall the plugin.");
            return false;
        }

        // Debug infos
        ARF._Debug("info", "Initializing version %s", ARF._Options.version);

        // Debug infos
        if (ARF._Options.debug) {
            if (!jQuery || !$.fn || !$.fn.jquery) {
                ARF._Debug("error", "jQuery not found, abort initialization. Please try to reinstall the plugin.");
                return false;
            }
            ARF._Debug("info", "Found jQuery %s", $.fn.jquery);
            if (!$.blockUI || !$.blockUI.version) {
                ARF._Debug("error", "jQuery blockUI not found, abort initialization. Please try to reinstall the plugin.");
                return false;
            }
            ARF._Debug("info", "Found jQuery blockUI %s", $.blockUI.version);
            if (!$.idleTimer) {
                ARF._Debug("error", "jQuery Idle Timer plugin not found, abort initialization. Please try to reinstall the plugin.");
                return false;
            }
            ARF._Debug("info", "Found jQuery Idle Timer plugin");
        }

        if (ARF._Options.selectorPostContainer) {
            ARF._Debug("info", "Multiple comment form support enabled (selector: '%s')", ARF._Options.selectorPostContainer);
            $(ARF._Options.selectorPostContainer).each(function(i,e) {
                var id = $(e).attr("id");
                if (!id) {
                    ARF._Debug("info", "Skip post container element %o (ID not defined)", e);
                    return
                }
                ARF.AttachForm({
                    selectorCommentForm: "#" + id + " " + ARF._Options.selectorCommentForm,
                    selectorCommentPagingLinks: "#" + id + " " + ARF._Options.selectorCommentPagingLinks,
                    selectorCommentsContainer: "#" + id + " " + ARF._Options.selectorCommentsContainer,
                    selectorRespondContainer: "#" + id + " " + ARF._Options.selectorRespondContainer
                });
            });
        } else {
            ARF.AttachForm();
        }

        // Set up idle timer
        if (ARF._Options.commentsEnabled && ARF._Options.autoUpdateIdleTime > 0) {
            ARF._Debug("info", "Auto updating comments enabled (idle time: %s)", ARF._Options.autoUpdateIdleTime);
            ARF._InitIdleTimer();
        }

        ARF._Debug("info", "Initialization completed");

        return true;
    }

    ARF._OnIdle = function() {
        ARF.RefreshComments({ success: ARF._InitIdleTimer, scrollToAnchor: false});
    };

    ARF._InitIdleTimer = function() {
        if (ARF._TestFallbackUrl(location.href)) {
            ARF._Debug("error", "Fallback URL was detected (url: '%s'), cancel init idle timer", location.href);
            return;
        }

        $(document).idleTimer("destroy");
        $(document).idleTimer(ARF._Options.autoUpdateIdleTime);
        $(document).on("idle.idleTimer", ARF._OnIdle);
    }

    ARF.RefreshComments = function(options) {
        var url = location.href;

        if (ARF._TestFallbackUrl(location.href)) {
            ARF._Debug("error", "Fallback URL was detected (url: '%s'), cancel AJAX request", url);
            return false;
        }

        return ARF.LoadComments(url, options)
    }

    ARF.LoadComments = function(url, options) {

        // Cancel AJAX request if cross-domain scripting is detected
        if (ARF._TestCrossDomainScripting(url)) {
            ARF._Debug("error", "Cross-domain scripting detected (url: '%s'), cancel AJAX request", url);
            return false;
        }

        // Convert boolean parameter (used in version <0.14.0)
        if (typeof(options) == "boolean")
            options = {scrollToAnchor: options}

        // Set default options
        options = $.extend({
            scrollToAnchor: !ARF._Options.disableScrollToAnchor,
            showLoadingInfo: true,
            updateUrl: !ARF._Options.disableUrlUpdate,
            success: function() {},
            selectorCommentForm: ARF._Options.selectorCommentForm,
            selectorCommentsContainer: ARF._Options.selectorCommentsContainer,
            selectorRespondContainer: ARF._Options.selectorRespondContainer,
            disableCache: ARF._Options.disableCache,
            beforeSelectElements: ARF._Callbacks.beforeSelectElements,
            beforeUpdateComments: ARF._Callbacks.beforeUpdateComments,
            afterUpdateComments: ARF._Callbacks.afterUpdateComments,
        }, options || {});

        // Save form data and focus
        var formData = $(options.selectorCommentForm).serializeArray();
        var formFocus = (document.activeElement) ? $("[name='"+document.activeElement.name+"']", options.selectorCommentForm).attr("name") : "";

        // Show loading info
        if (options.showLoadingInfo)
            ARF._ShowMessage(ARF._Options.textRefreshComments, "loading");

        if (options.disableCache)
            url = ARF._AddQueryParamStringToUrl(url, "WPACRandom", (new Date()).getTime());

        var request = $.ajax({
            url: url,
            type: "GET",
            beforeSend: function(xhr){ xhr.setRequestHeader("X-WPAC-REQUEST", "1"); },
            success: function (data) {

                // Replace comments (and return if replacing failed)
                if (!ARF._ReplaceComments(data, url, true, formData, formFocus, options.selectorCommentsContainer, options.selectorCommentForm,
                    options.selectorRespondContainer, options.beforeSelectElements, options.beforeUpdateComments, options.afterUpdateComments)) return;

                if (options.updateUrl) ARF._UpdateUrl(url);

                // Scroll to anchor
                var waitForScrollToAnchor = false;
                if (options.scrollToAnchor) {
                    var anchor = url.indexOf("#") >= 0 ? url.substr(url.indexOf("#")) : null;
                    if (anchor) {
                        ARF._Debug("info", "Anchor '%s' extracted from url", anchor);
                        if (ARF._ScrollToAnchor(anchor, options.updateUrl, function() { options.success(); } )) {
                            waitForScrollToAnchor = true;
                        }
                    }
                }

                // Unblock UI
                $.unblockUI();

                if (!waitForScrollToAnchor) options.success();
            },
            error: function() {
                ARF._LoadFallbackUrl(ARF._AddQueryParamStringToUrl(window.location.href, "WPACFallback", "1"))
            }

        });

        return true;
    }

    $(function() {
        var initSuccesful = ARF.Init();
        if (ARF._Options.loadCommentsAsync) {
            if (!initSuccesful) {
                ARF._LoadFallbackUrl(ARF._AddQueryParamStringToUrl(window.location.href, "WPACFallback", "1"))
                return;
            }

            var asyncLoadTrigger = ARF._Options.asyncLoadTrigger;
            ARF._Debug("info", "Loading comments asynchronously with secondary AJAX request (trigger: '%s')", asyncLoadTrigger);

            if (window.location.hash) {
                var regex = /^#comment-[0-9]+$/;
                if (regex.test(window.location.hash)) {
                    ARF._Debug("info", "Comment anchor in URL detected, force loading comments on DomReady (hash: '%s')", window.location.hash);
                    asyncLoadTrigger = "DomReady";
                }
            }

            if (asyncLoadTrigger == "Viewport") {
                $(ARF._Options.selectorCommentsContainer).waypoint(function(direction) {
                    this.destroy();
                    ARF.RefreshComments();
                }, { offset: "100%" });
            } else if (asyncLoadTrigger == "DomReady") {
                ARF.RefreshComments({scrollToAnchor: true}); // force scroll to anchor
            }
        }
    });

    function wpac_init() {
        ARF._Debug("info", "wpac_init() is deprecated, please use ARF.Init()");
        ARF.Init();
    }
})(jQuery);