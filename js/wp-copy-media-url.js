(function ($) {

    var wp_cmu = jQuery.noConflict();

    wp_cmu(document).ready(function () {
        if (undefined !== wp.media) {
            try {
                wp.media.view.Attachment.Library = wp.media.view.Attachment.Library.extend({
                    className: function () {
                        return 'attachment ' + this.model.get('customClass');
                    },
                    tagName: function () {
                        return 'li';
                    },
                    template: wp.template("attachment-copy-media-url"),
                });
            } catch (err) {
            }

            try {
                wp.media.view.Attachment.Details.TwoColumn = wp.media.view.Attachment.Details.TwoColumn.extend({
                    tagName: function () {
                        return 'div';
                    },
                    template: wp.template("attachment-details-two-column-copy-media-url"),
                });
            } catch (err) {
            }
        }
    });

    wp_cmu(document).on("click", ".wp-cmu-copy-btn,.wp-cmu-copy-btn-list", function () {
        $current = wp_cmu(this);
        var copy_text = $current.text();
        var copied_text = $current.data("copied-text");
        copy_to_clipboard($current.attr("url"));
        $current.text(copied_text);
        replace_text($current, copy_text);
    });

    function replace_text($current, copy_text) {
        setTimeout(function () {
            $current.text(copy_text);
        }, 3000);
    }

    function copy_to_clipboard(element) {
        var $temp = wp_cmu("<input>");
        wp_cmu("body").append($temp);
        $temp.val(element).select();
        document.execCommand("copy");
        $temp.remove();
    }
})(jQuery);