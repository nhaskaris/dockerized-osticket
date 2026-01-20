/* Quill Rich Text Editor Adapter for osTicket */
(function($) {
    'use strict';

    // Quill toolbar configurations
    const TOOLBAR_FULL = [
        [{ 'header': [1, 2, 3, false] }],
        [{ 'font': [] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'indent': '-1'}, { 'indent': '+1' }],
        ['blockquote', 'code-block'],
        ['link', 'image', 'video'],
        ['clean']
    ];

    const TOOLBAR_SIMPLE = [
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        ['link', 'image'],
        ['clean']
    ];

    // Store Quill instances
    const quillInstances = new WeakMap();

    // Main Quill adapter
    $.fn.quill = function(options) {
        return this.each(function() {
            const $textarea = $(this);
            
            // Skip if already initialized
            if (quillInstances.has(this)) {
                return;
            }

            // Hide the original textarea
            $textarea.hide();

            // Determine toolbar type
            const isSimple = $textarea.hasClass('no-bar');
            const toolbar = isSimple ? TOOLBAR_SIMPLE : TOOLBAR_FULL;

            // Determine size
            let minHeight = '150px';
            if ($textarea.hasClass('small')) minHeight = '75px';
            if ($textarea.hasClass('medium')) minHeight = '150px';
            if ($textarea.hasClass('large')) minHeight = '225px';

            // Create editor container
            const $container = $('<div class="quill-container"></div>');
            $container.insertAfter($textarea);

            // Create Quill editor
            const quill = new Quill($container[0], {
                theme: 'snow',
                modules: {
                    toolbar: toolbar
                },
                placeholder: $textarea.attr('placeholder') || 'Compose your message...',
                bounds: $container[0]
            });

            // Set min-height
            $container.find('.ql-editor').css('min-height', minHeight);

            // Load initial content from textarea
            if ($textarea.val()) {
                quill.clipboard.dangerouslyPasteHTML($textarea.val());
            }

            // Sync Quill content back to textarea on change
            quill.on('text-change', function() {
                $textarea.val(quill.root.innerHTML);
            });

            // Handle form reset
            const $form = $textarea.closest('form');
            $form.find('input[type=reset]').on('click', function() {
                setTimeout(function() {
                    quill.setContents([]);
                    $textarea.val('');
                }, 0);
            });

            // Store instance
            quillInstances.set($textarea[0], quill);

            // Add API methods similar to Redactor
            const api = {
                getCode: function() {
                    return quill.root.innerHTML;
                },
                setCode: function(html) {
                    quill.clipboard.dangerouslyPasteHTML(html || '');
                    $textarea.val(html || '');
                },
                getText: function() {
                    return quill.getText();
                },
                getLength: function() {
                    return quill.getLength();
                },
                focus: function() {
                    quill.focus();
                },
                blur: function() {
                    quill.blur();
                },
                enable: function() {
                    quill.enable();
                },
                disable: function() {
                    quill.disable();
                },
                destroy: function() {
                    $container.remove();
                    $textarea.show();
                    quillInstances.delete($textarea[0]);
                },
                getQuill: function() {
                    return quill;
                }
            };

            // Store API on textarea data
            $textarea.data('quill', api);
            $textarea.data('quillInstance', quill);
        });
    };

    // Initialize all richtext textareas
    function findRichtextBoxes() {
        $('.richtext').each(function(i, el) {
            const $el = $(el);
            if (!quillInstances.has(el) && !$el.data('quill')) {
                $el.quill();
            }
        });
    }

    // Auto-initialize on document ready and after AJAX
    $(function() {
        findRichtextBoxes();
        $(document).ajaxStop(findRichtextBoxes);
    });

    // Handle form submissions - ensure content is synced
    $(document).on('submit', 'form', function() {
        $('.richtext', this).each(function() {
            const quill = quillInstances.get(this);
            if (quill) {
                $(this).val(quill.root.innerHTML);
            }
        });
    });

})(jQuery);
