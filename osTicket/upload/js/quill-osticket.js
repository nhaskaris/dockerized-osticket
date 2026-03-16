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
                        let currentHTML = quill.root.innerHTML;
                        
                        // Clean up empty paragraph tags
                        if (currentHTML === '<p><br></p>') {
                            currentHTML = '';
                        }
                        
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
                        
                        // Clean up empty paragraph tags before inserting
                        const cleanedHTML = updatedHTML === '<p><br></p>' ? '' : updatedHTML;
                        
                        quill.clipboard.dangerouslyPasteHTML(cleanedHTML);
                        $textarea.val(cleanedHTML === '<p><br></p>' ? '' : cleanedHTML);
                        
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
            } else {
                // Clear default Quill content if textarea is empty
                // Set contents to empty array to avoid <p><br></p>
                quill.setContents([]);
                $textarea.val('');
            }

            // Sync Quill content back to textarea on change
            quill.on('text-change', function() {
                const html = quill.root.innerHTML;
                // Don't sync if it's just the empty paragraph or empty content
                if (html === '<p><br></p>' || html === '') {
                    $textarea.val('');
                } else {
                    $textarea.val(html);
                }
            });

            // Handle form reset
            const $form = $textarea.closest('form');
            $form.find('input[type=reset]').on('click', function() {
                setTimeout(function() {
                    quill.setContents([]);
                    $textarea.val('');
                    $textarea.removeData('quillSignatureRange');
                    $textarea.removeData('quillCannedRange');
                }, 0);
            });

            // Signature support: preview + append into editor content
            const signatureField = $textarea.data('signature-field');
            if (signatureField) {
                const $signatureBox = $('<div class="selected-signature"><div class="inner"></div></div>');
                const $signatureInner = $signatureBox.find('.inner');
                $container.after($signatureBox);

                const stripEditorSignature = function(html) {
                    if (!html) return '';
                    return html
                        .replace(/<p><br><\/p>\s*<div\s+class=["']ost-quill-signature["'][^>]*>[\s\S]*?<\/div>\s*$/i, '')
                        .replace(/<div\s+class=["']ost-quill-signature["'][^>]*>[\s\S]*?<\/div>\s*$/i, '');
                };

                const removeStoredRange = function(dataKey) {
                    const stored = $textarea.data(dataKey);
                    if (!stored || typeof stored.index !== 'number' || typeof stored.length !== 'number') {
                        return;
                    }

                    const maxIndex = Math.max(0, quill.getLength() - 1);
                    const index = Math.max(0, Math.min(stored.index, maxIndex));
                    const length = Math.max(0, Math.min(stored.length, (quill.getLength() - 1) - index));

                    if (length > 0) {
                        quill.deleteText(index, length, 'silent');
                    }

                    $textarea.removeData(dataKey);
                };

                const applySignatureToEditor = function(signatureHtml) {
                    removeStoredRange('quillSignatureRange');

                    if (signatureHtml && signatureHtml.trim()) {
                        const insertAt = Math.max(0, quill.getLength() - 1);
                        const before = quill.getLength();
                        const hasBodyText = (quill.getText() || '').trim().length > 0;
                        const spacer = hasBodyText ? '<p><br><br></p>' : '';
                        const htmlToInsert = spacer + signatureHtml;
                        quill.clipboard.dangerouslyPasteHTML(insertAt, htmlToInsert);
                        const added = quill.getLength() - before;

                        if (added > 0) {
                            $textarea.data('quillSignatureRange', {
                                index: insertAt,
                                length: added
                            });
                        }
                    }

                    const current = quill.root.innerHTML;
                    $textarea.val(current === '<p><br><br></p>' ? '' : current);
                };

                const initialSignature = $textarea.data('signature');
                if (initialSignature) {
                    $signatureInner.html(initialSignature);
                    $signatureBox.show();
                    applySignatureToEditor(initialSignature);
                } else {
                    $signatureBox.hide();
                }

                const updateSignature = function() {
                    const selected = $form.find(':input:checked[name=' + signatureField + ']').val();
                    const deptId = $textarea.data('dept-id');
                    const deptField = $textarea.data('dept-field');
                    const posterId = $textarea.data('poster-id');
                    let url = 'ajax.php/content/signature/';

                    if (!selected || selected === 'none') {
                        $signatureInner.empty();
                        $signatureBox.hide();
                        applySignatureToEditor('');
                        return;
                    }

                    if (selected === 'dept' && deptId) {
                        url += 'dept/' + deptId;
                    } else if (selected === 'dept' && deptField) {
                        const deptValue = $form.find(':input[name=' + deptField + ']').val();
                        if (deptValue) {
                            url += 'dept/' + deptValue;
                        } else {
                            $signatureInner.empty();
                            $signatureBox.hide();
                            applySignatureToEditor('');
                            return;
                        }
                    } else if (selected === 'theirs' && posterId) {
                        url += 'agent/' + posterId;
                    } else {
                        url += selected;
                    }

                    $.get(url, function(html) {
                        $signatureInner.html(html);
                        $signatureBox.show();
                        applySignatureToEditor(html);
                    }).fail(function() {
                        $signatureInner.empty();
                        $signatureBox.hide();
                        applySignatureToEditor('');
                    });
                };

                $form.on('change', ':input[name=' + signatureField + ']', updateSignature);
                if ($textarea.data('dept-field')) {
                    $form.on('change', ':input[name=' + $textarea.data('dept-field') + ']', updateSignature);
                }
            }

            // Empty-body guard — capture phase fires before scp.js's bubble-phase
            // .submit() handler, so we can stop it before the overlay appears.
            // Only guard full editors (not no-bar optional boxes like signature editors).
            if (!$textarea.hasClass('no-bar')) {
                $form[0].addEventListener('submit', function(e) {
                    var text = quill.getText() || '';
                    var signatureRange = $textarea.data('quillSignatureRange');

                    // Ignore managed signature block when deciding if reply body is empty
                    if (signatureRange && typeof signatureRange.index === 'number' && typeof signatureRange.length === 'number') {
                        var start = Math.max(0, signatureRange.index);
                        var end = Math.max(start, start + signatureRange.length);
                        text = text.slice(0, start) + text.slice(end);
                    }

                    text = text.trim();
                    if (!text) {
                        e.preventDefault();
                        e.stopImmediatePropagation();

                        var $c = $textarea.next('.quill-container');
                        $c.css({'outline': '2px solid #c00', 'border-radius': '4px'});
                        setTimeout(function() { $c.css({'outline': '', 'border-radius': ''}); }, 2500);
                        $c.find('.ql-editor').focus();

                        if (!$c.next('.quill-empty-error').length) {
                            var $err = $('<p class="quill-empty-error" style="color:#c00;margin:4px 0 0;font-size:0.9em;">A response is required before submitting.</p>');
                            $c.after($err);
                            setTimeout(function() { $err.remove(); }, 2500);
                        }
                    }
                }, true /* capture — runs before jQuery bubble handlers */);
            }

            // Store instance
            quillInstances.set($textarea[0], quill);

            // Add API methods (for compatibility)
            const api = {
                getCode: function() { 
                    const html = quill.root.innerHTML;
                    return (html === '<p><br></p>') ? '' : html;
                },
                setCode: function(html) {
                    if (!html || html === '<p><br></p>') {
                        quill.setText('');
                        $textarea.val('');
                    } else {
                        quill.clipboard.dangerouslyPasteHTML(html);
                        $textarea.val(html);
                    }
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
                            // Replace previously inserted canned response block
                            // instead of stacking multiple canned responses.
                            var previous = $box.data('quillCannedRange');
                            var replaceIndex = null;
                            if (previous && typeof previous.index === 'number' && typeof previous.length === 'number') {
                                var maxIndex = Math.max(0, quill.getLength() - 1);
                                var prevIndex = Math.max(0, Math.min(previous.index, maxIndex));
                                var prevLen = Math.max(0, Math.min(previous.length, (quill.getLength() - 1) - prevIndex));
                                if (prevLen > 0) {
                                    quill.deleteText(prevIndex, prevLen, 'silent');
                                }
                                replaceIndex = prevIndex;
                                $box.removeData('quillCannedRange');
                            }

                            var index = replaceIndex;
                            if (typeof index !== 'number') {
                                index = $box.data('savedSelection');
                                if (typeof index !== 'number') {
                                    var selection = quill.getSelection(true);
                                    index = selection ? selection.index : Math.max(0, quill.getLength() - 1);
                                }
                            }
                            index = Math.max(0, Math.min(index, Math.max(0, quill.getLength() - 1)));

                            var before = quill.getLength();
                            quill.clipboard.dangerouslyPasteHTML(index, data.response);
                            var inserted = quill.getLength() - before;
                            if (inserted > 0) {
                                $box.data('quillCannedRange', {
                                    index: index,
                                    length: inserted
                                });
                            }

                            $box.val(quill.root.innerHTML);
                        }
                    }
                });
            }
        });
    });

    // Handle form submissions - Final sync: write Quill HTML back to textarea
    $(document).on('submit', 'form', function() {
        $('.richtext', this).each(function() {
            const quill = quillInstances.get(this);
            if (!quill) return;
            const html = quill.root.innerHTML;
            $(this).val(html === '<p><br></p>' ? '' : html);
        });
    });

})(jQuery);