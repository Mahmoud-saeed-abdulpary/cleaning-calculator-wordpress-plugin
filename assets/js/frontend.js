(function($) {
    'use strict';

    let rooms = [];
    let roomCounter = 0;
    
    // Get price from both sources with fallback
    let pricePerSqm = parseFloat(cpcFrontend.pricePerSqm) || 
                      parseFloat($('#cpc-calculator').data('price-per-sqm')) || 
                      5.00;
    
    // Debug log to check the price
    console.log('Price per sqm:', pricePerSqm);

    $(document).ready(function() {
        initCalculator();
    });

    function initCalculator() {
        bindEvents();
    }

    function bindEvents() {
        // Add room button
        $(document).on('click', '#cpc-add-room', function() {
            addRoom();
        });

        // Remove room button
        $(document).on('click', '.cpc-remove-room', function() {
            const roomItem = $(this).closest('.cpc-accordion-item');
            const roomIndex = parseInt(roomItem.data('room-index'));
            removeRoom(roomIndex);
        });

        // Toggle accordion
        $(document).on('click', '.cpc-accordion-toggle', function() {
            const accordionItem = $(this).closest('.cpc-accordion-item');
            const accordionContent = accordionItem.find('.cpc-accordion-content');
            
            accordionItem.toggleClass('active');
            accordionContent.slideToggle(300);
        });

        // Room name change
        $(document).on('input', '.cpc-room-name', function() {
            const roomItem = $(this).closest('.cpc-accordion-item');
            const roomIndex = parseInt(roomItem.data('room-index'));
            updateRoomCalculation(roomIndex);
        });

        // Room count change
        $(document).on('input', '.cpc-room-count', function() {
            const roomItem = $(this).closest('.cpc-accordion-item');
            const roomIndex = parseInt(roomItem.data('room-index'));
            updateRoomCalculation(roomIndex);
        });

        // Area change
        $(document).on('input', '.cpc-room-area', function() {
            const roomItem = $(this).closest('.cpc-accordion-item');
            const roomIndex = parseInt(roomItem.data('room-index'));
            updateRoomCalculation(roomIndex);
        });

        // Request quote button
        $(document).on('click', '#cpc-request-quote', function() {
            if (rooms.length === 0) {
                alert(cpcFrontend.strings.addRoom || 'Please add at least one room');
                return;
            }
            showQuoteForm();
        });

        // Submit quote form
        $(document).on('submit', '#cpc-quote-form-element', function(e) {
            e.preventDefault();
            submitQuote();
        });

        // Modal close
        $(document).on('click', '#cpc-modal-close, #cpc-modal-overlay', function() {
            hideQuoteForm();
        });
        
        // Prevent modal content clicks from closing modal
        $(document).on('click', '.cpc-modal-content', function(e) {
            e.stopPropagation();
        });
        
        // Close modal on ESC key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#cpc-quote-modal').is(':visible')) {
                hideQuoteForm();
            }
        });
    }

    function addRoom() {
        roomCounter++;
        const template = $('#cpc-room-template').html();
        const roomHtml = template.replace(/\{\{index\}\}/g, roomCounter);
        
        $('#cpc-rooms-accordion').append(roomHtml);
        $('#cpc-no-rooms').hide();

        // Open the newly added room
        const roomItem = $(`.cpc-accordion-item[data-room-index="${roomCounter}"]`);
        roomItem.addClass('active');
        roomItem.find('.cpc-accordion-content').show();

        // Add to rooms array
        rooms.push({
            index: roomCounter,
            room_name: '',
            room_count: 1,
            area: 0,
            price_per_sqm: pricePerSqm,
            subtotal: 0
        });
    }

    function removeRoom(roomIndex) {
        $(`.cpc-accordion-item[data-room-index="${roomIndex}"]`).remove();
        rooms = rooms.filter(room => room.index !== roomIndex);
        
        if (rooms.length === 0) {
            $('#cpc-no-rooms').show();
        }
        
        updateGrandTotal();
    }

    function updateRoomCalculation(roomIndex) {
        const roomItem = $(`.cpc-accordion-item[data-room-index="${roomIndex}"]`);
        const roomName = roomItem.find('.cpc-room-name').val().trim();
        const roomCount = parseInt(roomItem.find('.cpc-room-count').val()) || 1;
        const area = parseFloat(roomItem.find('.cpc-room-area').val()) || 0;
        const subtotal = roomCount * area * pricePerSqm;

        // Update room data
        const roomData = rooms.find(room => room.index === roomIndex);
        if (roomData) {
            roomData.room_name = roomName;
            roomData.room_count = roomCount;
            roomData.area = area;
            roomData.price_per_sqm = pricePerSqm;
            roomData.subtotal = subtotal;
        }

        // Update calculation display
        const calcFormula = `${roomCount} × ${area.toFixed(2)} m² × ${pricePerSqm.toFixed(2)} ${cpcFrontend.currency}/m²`;
        roomItem.find('.cpc-calc-formula').text(calcFormula);

        // Update UI
        const displayName = roomName || 'Room';
        const roomTitle = roomCount > 1 ? `${displayName} (${roomCount}x)` : displayName;
        roomItem.find('.cpc-room-title').text(`${roomTitle} #${roomIndex}`);
        roomItem.find('.cpc-subtotal-amount').text(subtotal.toFixed(2) + ' ' + cpcFrontend.currency);

        updateGrandTotal();
    }

    function updateGrandTotal() {
        let grandTotal = 0;
        const totalsHtml = [];

        rooms.forEach(function(room) {
            if (room.subtotal > 0) {
                grandTotal += room.subtotal;
                const displayName = room.room_name || 'Room #' + room.index;
                const roomInfo = room.room_count > 1 
                    ? `${displayName} (${room.room_count} × ${room.area.toFixed(2)} m²)`
                    : `${displayName} (${room.area.toFixed(2)} m²)`;
                
                totalsHtml.push(`
                    <div class="cpc-total-item">
                        <span>${roomInfo}</span>
                        <span>${room.subtotal.toFixed(2)} ${cpcFrontend.currency}</span>
                    </div>
                `);
            }
        });

        $('#cpc-totals-list').html(totalsHtml.join(''));
        $('#cpc-grand-total').text(grandTotal.toFixed(2));
    }

    function showQuoteForm() {
        const displayMode = cpcFrontend.formDisplay || 'modal';

        if (displayMode === 'modal') {
            const modal = $('#cpc-quote-modal');
            modal.fadeIn(300, function() {
                modal.addClass('cpc-modal-open');
            });
            $('body').addClass('cpc-modal-active').css('overflow', 'hidden');
            
            // Scroll modal content to top
            $('.cpc-modal-content').scrollTop(0);
        } else if (displayMode === 'inline') {
            $('#cpc-quote-form-container').slideDown(300);
            
            // Scroll to form
            $('html, body').animate({
                scrollTop: $('#cpc-quote-form-container').offset().top - 50
            }, 500);
        } else if (displayMode === 'replace') {
            $('.cpc-calculator-body').hide();
            $('#cpc-quote-form-container').show();
            
            // Scroll to top
            $('html, body').animate({
                scrollTop: $('#cpc-calculator').offset().top - 50
            }, 500);
        }
    }

    function hideQuoteForm() {
        const modal = $('#cpc-quote-modal');
        modal.removeClass('cpc-modal-open');
        
        setTimeout(function() {
            modal.fadeOut(300);
        }, 100);
        
        $('body').removeClass('cpc-modal-active').css('overflow', '');
        $('#cpc-quote-form-container').slideUp(300);
        $('.cpc-calculator-body').show();
    }

    function submitQuote() {
        const submitBtn = $('#cpc-submit-quote');
        const btnText = submitBtn.find('.cpc-btn-text');
        const btnLoader = submitBtn.find('.cpc-btn-loader');
        
        // Validate
        if (rooms.length === 0) {
            showFormNotice('error', cpcFrontend.strings.addRoom || 'Please add at least one room');
            return;
        }

        // Find the visible form (either in modal or inline)
        const $form = $('#cpc-quote-form-element:visible');
        
        // Get form data using the form context
        const formData = {
            name: ($form.find('input[name="name"]').val() || '').trim(),
            email: ($form.find('input[name="email"]').val() || '').trim(),
            phone: ($form.find('input[name="phone"]').val() || '').trim(),
            address: ($form.find('input[name="address"]').val() || '').trim(),
            message: ($form.find('textarea[name="message"]').val() || '').trim()
        };

        // Client-side validation
        if (!formData.name || !formData.email || !formData.phone) {
            showFormNotice('error', cpcFrontend.strings.requiredField || 'Please fill in all required fields');
            return;
        }

        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(formData.email)) {
            showFormNotice('error', cpcFrontend.strings.invalidEmail || 'Please enter a valid email address');
            return;
        }

        // Disable submit button and show loader
        submitBtn.prop('disabled', true);
        btnText.hide();
        btnLoader.show();

        $.ajax({
            url: cpcFrontend.ajaxurl,
            type: 'POST',
            data: {
                action: 'cpc_submit_quote',
                nonce: cpcFrontend.nonce,
                name: formData.name,
                email: formData.email,
                phone: formData.phone,
                address: formData.address,
                message: formData.message,
                rooms: JSON.stringify(rooms)
            },
            success: function(response) {
                if (response.success) {
                    showFormNotice('success', response.data.message || cpcFrontend.strings.success);
                    $('#cpc-quote-form-element')[0].reset();
                    
                    // Reset calculator
                    setTimeout(function() {
                        hideQuoteForm();
                        resetCalculator();
                    }, 2000);
                } else {
                    showFormNotice('error', response.data.message || cpcFrontend.strings.error);
                }
            },
            error: function() {
                showFormNotice('error', cpcFrontend.strings.error || 'An error occurred');
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                btnText.show();
                btnLoader.hide();
            }
        });
    }

    function showFormNotice(type, message) {
        const notice = $('#cpc-form-notice');
        notice.removeClass('cpc-notice-success cpc-notice-error')
              .addClass('cpc-notice-' + type)
              .html(message)
              .slideDown(300);

        setTimeout(function() {
            notice.slideUp(300);
        }, 5000);
    }

    function resetCalculator() {
        rooms = [];
        roomCounter = 0;
        $('#cpc-rooms-accordion').empty();
        $('#cpc-no-rooms').show();
        updateGrandTotal();
    }

})(jQuery);