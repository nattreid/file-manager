(function ($, window) {
    if (window.jQuery === undefined) {
        console.error('Plugin "jQuery" required by "menu.js" is missing!');
        return;
    }

    window.FileManager = {};

    window.FileManager.viewer;
    window.FileManager.container;
    window.FileManager.loaded;


    window.FileManager.redrawViewer = function () {
        this.loaded = false;
        this.viewer = $('.fileManagerContainer .viewer');
        this.container = this.viewer.find('.viewer-container');

        this.viewer.css({visibility: 'hidden', display: 'block'});
        this.resize();
        this.viewer.css({visibility: '', display: 'none'});
        this.viewer.fadeIn();

    };

    window.FileManager.resize = function () {
        if (this.container) {
            if (this.container.hasClass('image')) {
                var img = this.container.find('img');

                function resizeImage() {
                    var limit = 30;

                    img.removeAttr('style');
                    var width = img.width();
                    var height = img.height();

                    var windowWidth = window.innerWidth - limit;
                    var windowHeight = window.innerHeight - limit;

                    if (width > windowWidth) {
                        height = height / (width / windowWidth);
                        width = windowWidth;
                    }
                    if (height > windowHeight) {
                        width = width / (height / windowHeight);
                        height = windowHeight;
                    }

                    img.width(width);
                    img.height(height);

                    window.FileManager.container.centerFixed();
                }

                if (this.loaded) {
                    resizeImage();
                } else {
                    img.load(function () {
                        window.FileManager.loaded = true;
                        resizeImage();
                    });
                }
            } else {
                this.container.centerFixed();
            }
        }
    };

    $(document).ready(function () {

        // *************************************************************************
        // properties

        var ajax = null;
        var timer = null;
        var position = {
            left: 0,
            top: 0
        };

        function disableCallSizeInfo() {
            if (ajax !== null) {
                ajax.abort();
            }
            if (timer !== null) {
                clearTimeout(timer);
            }
        }

        $(document).on('mousemove', '.no-touchevents .fileManagerContainer .fileManagerContent .itemContainer a.item', function (event) {
            position = $(this).closest('.itemContainer').find('.properties').onPosition(event);
        });

        $(document).ajaxStart(function () {
            disableCallSizeInfo();
        });

        $(document).on('mouseenter', '.no-touchevents .fileManagerContainer .fileManagerContent .itemContainer a.item', function () {
            var item = $(this).closest('.itemContainer');

            timer = setTimeout(function () {

                if (item.data('request') === 0) {
                    item.data('request', 1);
                    ajax = $.nette.ajax(item.data('file-size-handler'))
                            .success(function () {
                                item.find('.properties')
                                        .show()
                                        .css({
                                            left: position.left,
                                            top: position.top
                                        });
                                item.data('request', 2);
                            })
                            .complete(function () {
                                if (item.data('request') !== 2) {
                                    item.data('request', 0);
                                }
                                ajax = null;
                            });
                    timer = null;
                }
            }, 2000);
            item.find('.properties').show();
        });

        $(document).on('mouseleave', '.no-touchevents .fileManagerContainer .fileManagerContent .itemContainer a.item', function () {
            disableCallSizeInfo();
            $(this).closest('.itemContainer')
                    .find('.properties')
                    .hide();
        });

        // *************************************************************************
        // context menu
        $(document).on('click', '.fileManagerContainer .fileManagerContent .itemContainer a.item', function (event) {
            disableCallSizeInfo();
            return false;
        });

        $(document).on('contextmenu', '.no-touchevents .fileManagerContainer .fileManagerContent .itemContainer', function (event) {
            disableCallSizeInfo();
            $(this).find('.properties').hide();

            $(this).find('.fileContextMenu').hide();
            var menu = $(this).find('.fileContextMenu');
            menu.onPosition(event, -30, -30);
            menu.show();
            return false;
        });

        $(document).on('contextmenu', '.touchevents .fileManagerContainer .fileManagerContent .itemContainer', function (event) {
            $(this).closest('.fileManagerContent').find('.fileContextMenu').hide();
            var menu = $(this).find('.fileContextMenu');
            menu.onPosition(event, -30, -30);
            menu.show();
            menu.clickOut(function (o) {
                o.hide();
                return true;
            });
            return false;
        });

        $(document).on('mouseleave', '.no-touchevents .fileManagerContainer .fileManagerContent .itemContainer .fileContextMenu', function () {
            $(this).hide();
        });

        // *************************************************************************
        // viewer
        $(document).on('click', '.fileManagerContainer .viewer .background, .fileManagerContainer .viewer .viewerClose', function () {
            $(this).closest('.viewer').fadeOut();
        });

        $(document).on('change', '.fileManagerContainer .viewer .data input[type="checkbox"]', function (ev) {
            var obj = $(this).closest('.data').find('pre, textarea');
            if ($(this).is(':checked')) {
                obj.removeClass('notAlign');
            } else {
                obj.addClass('notAlign');
            }
        });

        $(window).on('resize.fileManager', function () {
            window.FileManager.resize();
        });

    });

})(jQuery, window);