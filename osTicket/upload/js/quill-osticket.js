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

    const TOOLBAR_FULL_WITH_DRAFT = [
        [{ 'header': [1, 2, 3, false] }],
        [{ 'font': [] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'indent': '-1'}, { 'indent': '+1' }],
        ['blockquote', 'code-block'],
        ['link', 'image', 'video'],
        ['clean'],
        ['html-editor', 'save-draft', 'clear-draft']
    ];

    const TOOLBAR_SIMPLE = [
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        ['link', 'image'],
        ['clean']
    ];

    const TOOLBAR_SIMPLE_WITH_DRAFT = [
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        ['link', 'image'],
        ['clean'],
        ['html-editor', 'save-draft', 'clear-draft']
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
            const hasDraftDelete = $textarea.hasClass('draft-delete');
            
            // Choose toolbar based on type and draft capabilities
            let toolbar;
            if (isSimple) {
                toolbar = hasDraftDelete ? TOOLBAR_SIMPLE_WITH_DRAFT : TOOLBAR_SIMPLE;
            } else {
                toolbar = hasDraftDelete ? TOOLBAR_FULL_WITH_DRAFT : TOOLBAR_FULL;
            }
            
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

            // Add HTML editor functionality
            const toolbar_el = $container.prev('.ql-toolbar');
            const htmlButton = toolbar_el.find('.ql-html-editor');
            const saveDraftButton = toolbar_el.find('.ql-save-draft');
            const clearDraftButton = toolbar_el.find('.ql-clear-draft');
            
            // Generate unique storage key based on textarea name/id
            const storageKey = 'quill_draft_' + ($textarea.attr('name') || $textarea.attr('id') || 'unnamed_' + Date.now());
            
            // Load saved draft on initialization
            const savedDraft = localStorage.getItem(storageKey);
            if (savedDraft) {
                quill.clipboard.dangerouslyPasteHTML(savedDraft);
                $textarea.val(savedDraft);
                // Visual indicator that draft was loaded
                saveDraftButton.addClass('ql-draft-loaded');
                setTimeout(function() {
                    saveDraftButton.removeClass('ql-draft-loaded');
                }, 2000);
            }
            
            // Save Draft Button
            if (saveDraftButton.length) {
                saveDraftButton.html('<svg viewBox="0 0 18 18"><path class="ql-stroke" d="M3 3 L3 15 L15 15 L15 6 L12 3 Z M6 3 L6 6 L12 6 L12 3"></path><rect class="ql-fill" x="6" y="10" width="6" height="3"></rect><circle class="ql-fill" cx="9" cy="11.5" r="0.8"></circle></svg>');
                saveDraftButton.attr('title', 'Save draft to browser');
                
                saveDraftButton.on('click', function(e) {
                    e.preventDefault();
                    const currentHTML = quill.root.innerHTML;
                    localStorage.setItem(storageKey, currentHTML);
                    
                    // Show checkmark feedback
                    const originalHTML = saveDraftButton.html();
                    saveDraftButton.html('<svg viewBox="0 0 18 18"><polyline class="ql-stroke" points="3,9 7,13 15,5" stroke-width="2" fill="none"></polyline></svg>');
                    saveDraftButton.css('color', '#22c55e');
                    
                    setTimeout(function() {
                        saveDraftButton.html(originalHTML);
                        saveDraftButton.css('color', '');
                    }, 800);
                });
            }
            
            // Clear Draft Button
            if (clearDraftButton.length) {
                clearDraftButton.html('<svg viewBox="0 0 18 18"><polyline class="ql-stroke" points="3 6 4 6 15 6"></polyline><path class="ql-stroke" d="M6,6 L6,4 L12,4 L12,6 M5,6 L5,15 C5,15.5 5.5,16 6,16 L12,16 C12.5,16 13,15.5 13,15 L13,6"></path><line class="ql-stroke" x1="8" y1="9" x2="8" y2="13"></line><line class="ql-stroke" x1="10" y1="9" x2="10" y2="13"></line></svg>');
                clearDraftButton.attr('title', 'Clear saved draft');
                
                clearDraftButton.on('click', function(e) {
                    e.preventDefault();
                    localStorage.removeItem(storageKey);
                    
                    // Show X feedback
                    const originalHTML = clearDraftButton.html();
                    clearDraftButton.html('<svg viewBox="0 0 18 18"><line class="ql-stroke" x1="5" y1="5" x2="13" y2="13" stroke-width="2"></line><line class="ql-stroke" x1="13" y1="5" x2="5" y2="13" stroke-width="2"></line></svg>');
                    clearDraftButton.css('color', '#ef4444');
                    
                    setTimeout(function() {
                        clearDraftButton.html(originalHTML);
                        clearDraftButton.css('color', '');
                    }, 800);
                });
            }
            
            // HTML Editor Button
            if (htmlButton.length) {
                htmlButton.html('<span style="font-size: 11px; font-weight: 600; font-family: monospace;">HTML</span>');
                htmlButton.attr('title', 'Toggle HTML editor');
                
                let htmlMode = false;
                let htmlTextarea = null;
                
                htmlButton.on('click', function(e) {
                    e.preventDefault();
                    
                    if (!htmlMode) {
                        // Switch to HTML mode
                        const currentHTML = quill.root.innerHTML;
                        
                        // Create HTML textarea
                        htmlTextarea = $('<textarea class="html-editor-textarea"></textarea>');
                        htmlTextarea.css({
                            'width': '100%',
                            'min-height': minHeight,
                            'padding': '12px',
                            'font-family': 'Consolas, Monaco, monospace',
                            'font-size': '13px',
                            'border': '1px solid #ccc',
                            'border-radius': '0 0 8px 8px',
                            'background': '#f9f9f9',
                            'color': '#333',
                            'resize': 'vertical'
                        });
                        htmlTextarea.val(currentHTML);
                        
                        // Hide editor, show textarea
                        $container.find('.ql-editor').hide();
                        $container.find('.ql-clipboard').hide();
                        $container.append(htmlTextarea);
                        
                        htmlButton.addClass('ql-active');
                        htmlMode = true;
                    } else {
                        // Switch back to visual mode
                        const updatedHTML = htmlTextarea.val();
                        quill.clipboard.dangerouslyPasteHTML(updatedHTML);
                        $textarea.val(updatedHTML);
                        
                        // Remove textarea, show editor
                        htmlTextarea.remove();
                        $container.find('.ql-editor').show();
                        $container.find('.ql-clipboard').show();
                        
                        htmlButton.removeClass('ql-active');
                        htmlMode = false;
                    }
                });
            }

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
                
                // If this form has canned responses, unbind the original scp.js handler
                const $form = $el.closest('form');
                const $cannedSelect = $form.find('select#cannedResp');
                if ($cannedSelect.length) {
                    // Unbind the original change handler from scp.js
                    $cannedSelect.off('change');
                }
            }
        });
    }

    // --- MAIN INITIALIZATION & EVENT HANDLERS ---
    $(function() {
        // 1. Initial Load
        findRichtextBoxes();
        
        // Unbind scp.js handlers after it has loaded (use setTimeout to ensure it runs after scp.js)
        setTimeout(function() {
            $('form select#cannedResp').each(function() {
                var $form = $(this).closest('form');
                var $box = $('.richtext', $form);
                if ($box.data('quillInstance')) {
                    // Unbind the scp.js handler if Quill is active
                    $(this).off('change');
                }
            });
        }, 100);
        
        // 2. Re-init after standard AJAX (e.g. Help Topic change)
        $(document).ajaxStop(function() {
            setTimeout(findRichtextBoxes, 100);
        });

        // 3. Re-init after osTicket PJAX (Backend Navigation) - NEW!
        $(document).on('pjax:end pjax:complete', function() {
            setTimeout(findRichtextBoxes, 100);
        });

        // 4. Handle Canned Response Selection (Save Cursor)
        $(document).on('select2:opening.quill', 'form select#cannedResp', function (e) {
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
        // Use namespace to prevent multiple bindings
        $(document).off('change.quill', 'form select#cannedResp');
        $(document).on('change.quill', 'form select#cannedResp', function(e) {
            var $this = $(this);
            var $form = $this.closest('form');
            var $box = $('.richtext', $form);
            var quill = $box.data('quillInstance');
            var selectedId = $this.val();

            // Only handle if Quill is active on this form
            if (!quill) {
                return;
            }
            
            // Stop all other handlers from firing
            e.stopImmediatePropagation();
            e.preventDefault();

            if (selectedId > 0) {
                // Determine correct URL - use ticket-specific endpoint if ticket ID exists
                var tid = $(':input[name=id]', $form).val();
                var url = 'ajax.php/kb/canned-response/' + selectedId + '.json';
                if (tid) {
                    url = 'ajax.php/tickets/' + tid + '/canned-resp/' + selectedId + '.json';
                }
                
                // Reset dropdown to first option
                $this.find('option:first').attr('selected', 'selected');
                
                $.ajax({
                    type: 'GET',
                    url: url,
                    dataType: 'json',
                    cache: false,
                    success: function(data) {
                        if (data && data.response) {
                            var index = $box.data('savedSelection') || 0;
                            // Insert text at saved cursor position
                            quill.clipboard.dangerouslyPasteHTML(index, data.response);
                            $box.val(quill.root.innerHTML);
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