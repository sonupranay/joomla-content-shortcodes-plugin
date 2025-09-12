/**
 * Content Shortcodes Plugin JavaScript
 * 
 * @package     Content Shortcodes
 * @subpackage  plg_content_contentshortcodes
 * @author      Pranay
 * @copyright   Copyright (C) 2024. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initializeCountdowns();
        initializeContactForms();
        initializeTabs();
        initializeAccordions();
        initializeGallery();
    });

    /**
     * Initialize countdown timers
     */
    function initializeCountdowns() {
        $('.content-shortcodes-countdown').each(function() {
            var $countdown = $(this);
            var targetDate = $countdown.data('target');
            var format = $countdown.data('format') || 'days,hours,minutes,seconds';
            var message = $countdown.data('message') || 'Countdown finished!';
            
            if (!targetDate) return;
            
            var target = new Date(targetDate).getTime();
            var $display = $countdown.find('.countdown-display');
            var $message = $countdown.find('.countdown-message');
            
            // Update countdown every second
            var timer = setInterval(function() {
                var now = new Date().getTime();
                var distance = target - now;
                
                if (distance < 0) {
                    clearInterval(timer);
                    $display.hide();
                    $message.show();
                    return;
                }
                
                var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                // Update display based on format
                var formatArray = format.split(',');
                
                $countdown.find('.countdown-number[data-type="days"]').text(days);
                $countdown.find('.countdown-number[data-type="hours"]').text(hours);
                $countdown.find('.countdown-number[data-type="minutes"]').text(minutes);
                $countdown.find('.countdown-number[data-type="seconds"]').text(seconds);
                
                // Hide/show elements based on format
                $countdown.find('.countdown-item').each(function() {
                    var type = $(this).find('.countdown-number').data('type');
                    if (formatArray.indexOf(type) === -1) {
                        $(this).hide();
                    } else {
                        $(this).show();
                    }
                });
                
            }, 1000);
        });
    }

    /**
     * Initialize contact forms
     */
    function initializeContactForms() {
        $('.content-shortcodes-contact-form').each(function() {
            var $form = $(this);
            
            $form.on('submit', function(e) {
                e.preventDefault();
                
                var $submitBtn = $form.find('button[type="submit"]');
                var originalText = $submitBtn.text();
                
                // Show loading state
                $submitBtn.prop('disabled', true).text('Sending...');
                
                // Collect form data
                var formData = {
                    task: 'contactform.submit',
                    form_id: $form.attr('id'),
                    name: $form.find('input[name="name"]').val(),
                    email: $form.find('input[name="email"]').val(),
                    message: $form.find('textarea[name="message"]').val(),
                    to_email: $form.find('input[name="to_email"]').val(),
                    subject: $form.find('input[name="subject"]').val(),
                    redirect: $form.find('input[name="redirect"]').val()
                };
                
                // Add CSRF token
                var csrfToken = $form.find('input[name*="token"]').attr('name');
                if (csrfToken) {
                    formData[csrfToken] = '1';
                }
                
                // Send AJAX request
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        // Show success message
                        showFormMessage($form, 'success', 'Message sent successfully!');
                        $form[0].reset();
                    },
                    error: function() {
                        // Show error message
                        showFormMessage($form, 'error', 'Failed to send message. Please try again.');
                    },
                    complete: function() {
                        // Reset button
                        $submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });
        });
    }

    /**
     * Show form message
     */
    function showFormMessage($form, type, message) {
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        var $alert = $('<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                      message +
                      '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                      '</div>');
        
        $form.before($alert);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $alert.alert('close');
        }, 5000);
    }

    /**
     * Initialize tabs
     */
    function initializeTabs() {
        $('.content-shortcodes-tabs .nav-link').on('click', function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var target = $this.data('bs-target');
            
            // Remove active class from all tabs and panes
            $this.closest('.content-shortcodes-tabs').find('.nav-link').removeClass('active');
            $this.closest('.content-shortcodes-tabs').find('.tab-pane').removeClass('active show');
            
            // Add active class to clicked tab and corresponding pane
            $this.addClass('active');
            $(target).addClass('active show');
        });
    }

    /**
     * Initialize accordions
     */
    function initializeAccordions() {
        $('.content-shortcodes-accordion .accordion-button').on('click', function() {
            var $this = $(this);
            var target = $this.data('bs-target');
            var $targetPane = $(target);
            
            // Close all other accordion items
            $this.closest('.content-shortcodes-accordion').find('.accordion-collapse').not(target).removeClass('show');
            $this.closest('.content-shortcodes-accordion').find('.accordion-button').not(this).addClass('collapsed');
            
            // Toggle current item
            if ($targetPane.hasClass('show')) {
                $targetPane.removeClass('show');
                $this.addClass('collapsed');
            } else {
                $targetPane.addClass('show');
                $this.removeClass('collapsed');
            }
        });
    }

    /**
     * Initialize gallery
     */
    function initializeGallery() {
        // Add click handlers for gallery images
        $('.content-shortcodes-gallery .gallery-item img').on('click', function() {
            var $img = $(this);
            var src = $img.attr('src');
            var alt = $img.attr('alt');
            
            // Create modal
            var modal = createImageModal(src, alt);
            $('body').append(modal);
            
            // Show modal
            $(modal).modal('show');
            
            // Remove modal when hidden
            $(modal).on('hidden.bs.modal', function() {
                $(this).remove();
            });
        });
    }

    /**
     * Create image modal
     */
    function createImageModal(src, alt) {
        var modalId = 'gallery-modal-' + Math.random().toString(36).substr(2, 9);
        
        return $('<div class="modal fade" id="' + modalId + '" tabindex="-1" role="dialog">' +
                '<div class="modal-dialog modal-lg modal-dialog-centered" role="document">' +
                '<div class="modal-content">' +
                '<div class="modal-header">' +
                '<h5 class="modal-title">' + alt + '</h5>' +
                '<button type="button" class="btn-close" data-bs-dismiss="modal"></button>' +
                '</div>' +
                '<div class="modal-body text-center">' +
                '<img src="' + src + '" alt="' + alt + '" class="img-fluid">' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>');
    }

    /**
     * Utility function to debounce events
     */
    function debounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }

    /**
     * Handle window resize for responsive elements
     */
    $(window).on('resize', debounce(function() {
        // Recalculate countdown positions if needed
        $('.content-shortcodes-countdown').each(function() {
            var $countdown = $(this);
            var $display = $countdown.find('.countdown-display');
            
            // Adjust layout for mobile
            if ($(window).width() < 768) {
                $display.addClass('mobile-layout');
            } else {
                $display.removeClass('mobile-layout');
            }
        });
    }, 250));

    /**
     * Add smooth scrolling for anchor links
     */
    $('a[href^="#"]').on('click', function(e) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 800);
        }
    });

    /**
     * Add fade-in animation to elements when they come into view
     */
    function animateOnScroll() {
        $('.fade-in').each(function() {
            var $this = $(this);
            var elementTop = $this.offset().top;
            var elementBottom = elementTop + $this.outerHeight();
            var viewportTop = $(window).scrollTop();
            var viewportBottom = viewportTop + $(window).height();
            
            if (elementBottom > viewportTop && elementTop < viewportBottom) {
                $this.addClass('animated');
            }
        });
    }

    // Run animation check on scroll
    $(window).on('scroll', debounce(animateOnScroll, 100));

    // Initial animation check
    animateOnScroll();

})(jQuery);
