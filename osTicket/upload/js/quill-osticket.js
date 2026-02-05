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
            if (quillInstances.has(this) || $textarea.data('quill') || $textarea.next('.quill-container').length) {
                return;
            }

            // Hide the original textarea
            $textarea.hide();

            // Determine toolbar type and size
            const isSimple = $textarea.hasClass('no-bar');
            const toolbar = isSimple ? TOOLBAR_SIMPLE : TOOLBAR_FULL;
            
            let minHeight = '150px';
            if ($textarea.hasClass('small')) minHeight = '75px';
            if ($textarea.hasClass('medium')) minHeight = '150px';
            if ($textarea.hasClass('large')) minHeight = '225px';

            // Create editor container
            const $container = $('<div class="quill-container"></div>');
            $container.css({
                'display': 'block',
                'clear': 'both',
                'margin-bottom': '40px', /* Pushes buttons down */
                'position': 'relative',
                'background': '#fff',
                'z-index': '1'
            });
            $container.insertAfter($textarea);

            // Create Quill editor
            const quill = new Quill($container[0], {
                theme: 'snow',
                modules: { toolbar: toolbar },
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

            // Add API methods (for compatibility)
            const api = {
                getCode: function() { return quill.root.innerHTML; },
                setCode: function(html) {
                    quill.clipboard.dangerouslyPasteHTML(html || '');
                    $textarea.val(html || '');
                },
                getText: function() { return quill.getText(); },
                focus: function() { quill.focus(); },
                getQuill: function() { return quill; }
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
            // Strict check to prevent double-init
            if (!quillInstances.has(el) && !$el.data('quill')) {
                $el.quill();
            }
        });
    }

    // --- MAIN INITIALIZATION & EVENT HANDLERS ---
    $(function() {
        // 1. Initial Load
        findRichtextBoxes();
        
        // 2. Re-init after standard AJAX (e.g. Help Topic change)
        $(document).ajaxStop(function() {
            setTimeout(findRichtextBoxes, 100);
        });

        // 3. Re-init after osTicket PJAX (Backend Navigation) - NEW!
        $(document).on('pjax:end pjax:complete', function() {
            setTimeout(findRichtextBoxes, 100);
        });

        // 4. Handle Canned Response Selection (Save Cursor)
        $(document).on('select2:opening', 'form select#cannedResp', function (e) {
            var $box = $('.richtext', $(this).closest('form'));
            var quill = $box.data('quillInstance');
            
            if (quill) {
                var range = quill.getSelection(true); 
                if (range) {
                    $box.data('savedSelection', range.index);
                } else {
                    $box.data('savedSelection', quill.getLength());
                }
            }
        });

        // 5. Handle Canned Response Insertion (Paste Text)
        $(document).on('change', 'form select#cannedResp', function() {
            var $this = $(this);
            var $form = $this.closest('form');
            var $box = $('.richtext', $form);
            var quill = $box.data('quillInstance');
            var selectedId = $this.val();

            if (quill && selectedId > 0) {
                $.ajax({
                    url: 'ajax.php/kb/canned-response/' + selectedId + '.json',
                    dataType: 'json',
                    success: function(data) {
                        if (data && data.response) {
                            var index = $box.data('savedSelection') || 0;
                            // Insert text at saved cursor position
                            quill.clipboard.dangerouslyPasteHTML(index, data.response);
                            // Move cursor to end of inserted text
                            // Note: This is an approximation; perfect cursor placement requires more logic, 
                            // but this is standard for osTicket adapters.
                        }
                    }
                });
            }
        });
    });

    // Handle form submissions - Final sync check
    $(document).on('submit', 'form', function() {
        $('.richtext', this).each(function() {
            const quill = quillInstances.get(this);
            if (quill) {
                $(this).val(quill.root.innerHTML);
            }
        });
    });

})(jQuery);