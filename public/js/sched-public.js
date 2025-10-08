jQuery(document).ready(function($) {
    // Modern session card animations
    function initializeCardAnimations() {
        const cards = document.querySelectorAll('.sched-session-card');
        
        // Add intersection observer for fade-in animation
        if ('IntersectionObserver' in window) {
            const cardObserver = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                        cardObserver.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            });
            
            cards.forEach(function(card) {
                cardObserver.observe(card);
            });
        }
    }
    
    // Advanced toggle filter functionality
    function initializeAdvancedFilter() {
        const toggleBtn = document.getElementById('sched-filter-toggle');
        const filterPanel = document.getElementById('sched-filter-panel');
        const eventTypeFilter = document.getElementById('event-type-filter');
        const sessionTypeFilter = document.getElementById('session-type-filter');
        const dateFilter = document.getElementById('date-filter');
        const clearFiltersBtn = document.getElementById('clear-all-filters');
        const filterBadge = document.getElementById('filter-active-badge');
        
        if (!toggleBtn || !filterPanel) return;
        
        // Toggle panel open/close
        toggleBtn.addEventListener('click', function() {
            const isOpen = filterPanel.classList.contains('open');
            
            if (isOpen) {
                filterPanel.classList.remove('open');
                toggleBtn.classList.remove('active');
            } else {
                filterPanel.classList.add('open');
                toggleBtn.classList.add('active');
            }
        });
        
        // Handle event type filter changes
        if (eventTypeFilter) {
            eventTypeFilter.addEventListener('change', function() {
                const selectedType = this.value;
                updateFilterBadge();
                
                // Update URL and reload page
                updateURLAndReload('event_type', selectedType);
            });
        }
        
        // Handle session type filter changes
        if (sessionTypeFilter) {
            sessionTypeFilter.addEventListener('change', function() {
                const selectedType = this.value;
                updateFilterBadge();
                
                // Update URL and reload page
                updateURLAndReload('session_type', selectedType);
            });
        }
        
        // Handle date filter changes
        if (dateFilter) {
            dateFilter.addEventListener('change', function() {
                const selectedDate = this.value;
                updateFilterBadge();
                
                // Update URL and reload page
                updateURLAndReload('date', selectedDate);
            });
        }
        
        // Clear all filters
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function() {
                if (eventTypeFilter) {
                    eventTypeFilter.value = '';
                }
                if (sessionTypeFilter) {
                    sessionTypeFilter.value = '';
                }
                if (dateFilter) {
                    dateFilter.value = '';
                }
                
                // Clear URL parameters and reload with nonce
                const url = new URL(window.location);
                url.searchParams.delete('event_type');
                url.searchParams.delete('session_type');
                url.searchParams.delete('date');
                
                // Add nonce for security
                if (typeof sched_public !== 'undefined' && sched_public.filter_nonce) {
                    url.searchParams.set('_wpnonce', sched_public.filter_nonce);
                } else {
                    url.searchParams.delete('_wpnonce');
                }
                
                window.location.href = url.toString();
            });
        }
        
        // Update filter badge count
        function updateFilterBadge() {
            if (!filterBadge) return;
            
            let activeFilters = 0;
            
            if (eventTypeFilter && eventTypeFilter.value) {
                activeFilters++;
            }
            if (sessionTypeFilter && sessionTypeFilter.value) {
                activeFilters++;
            }
            if (dateFilter && dateFilter.value) {
                activeFilters++;
            }
            
            if (activeFilters > 0) {
                filterBadge.textContent = activeFilters;
                filterBadge.style.display = 'inline-block';
            } else {
                filterBadge.style.display = 'none';
            }
        }
        
        // Helper function to update URL and reload with nonce
        function updateURLAndReload(param, value) {
            const url = new URL(window.location);
            
            if (value) {
                url.searchParams.set(param, value);
            } else {
                url.searchParams.delete(param);
            }
            
            // Add nonce for security
            if (typeof sched_public !== 'undefined' && sched_public.filter_nonce) {
                url.searchParams.set('_wpnonce', sched_public.filter_nonce);
            }
            
            window.location.href = url.toString();
        }
        
        // Initialize badge on load
        updateFilterBadge();
        
        // Close panel when clicking outside
        document.addEventListener('click', function(event) {
            const filterContainer = document.querySelector('.sched-filter-toggle-container');
            
            if (filterContainer && !filterContainer.contains(event.target)) {
                if (filterPanel.classList.contains('open')) {
                    filterPanel.classList.remove('open');
                    toggleBtn.classList.remove('active');
                }
            }
        });
    }

    // Event type filtering with smooth animations
    function filterByEventType(selectedType) {
        const cards = document.querySelectorAll('.sched-session-card');
        
        cards.forEach(function(card) {
            const cardType = card.dataset.eventType;
            
            if (!selectedType || cardType === selectedType) {
                card.style.display = 'block';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 10);
            } else {
                card.style.opacity = '0';
                card.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    card.style.display = 'none';
                }, 300);
            }
        });
        
        // Update URL without page reload
        if (history.replaceState) {
            const url = new URL(window.location);
            if (selectedType) {
                url.searchParams.set('event_type', selectedType);
            } else {
                url.searchParams.delete('event_type');
            }
            
            // Add nonce for security when updating filter state
            if (typeof sched_public !== 'undefined' && sched_public.filter_nonce) {
                url.searchParams.set('_wpnonce', sched_public.filter_nonce);
            }
            
            history.replaceState(null, null, url);
        }
    }
    
    // Speaker chip hover effects
    function initializeSpeakerChips() {
        const speakerChips = document.querySelectorAll('.speaker-chip');
        
        speakerChips.forEach(function(chip) {
            chip.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.02)';
            });
            
            chip.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    }
    
    // Load state management
    function showLoadingState() {
        const container = document.querySelector('.sched-sessions-grid');
        if (container) {
            container.classList.add('loading');
        }
    }
    
    function hideLoadingState() {
        const container = document.querySelector('.sched-sessions-grid');
        if (container) {
            container.classList.remove('loading');
        }
    }
    
    // Handle session card clicks for potential modal/detail view
    function initializeSessionCardClicks() {
        const sessionCards = document.querySelectorAll('.sched-session-card');
        
        sessionCards.forEach(function(card) {
            card.addEventListener('click', function(e) {
                // Don't trigger if clicking on speaker links
                if (e.target.closest('.speaker-chip a')) return;
                
                // Add ripple effect
                const ripple = document.createElement('div');
                ripple.className = 'card-ripple';
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                
                card.appendChild(ripple);
                setTimeout(() => ripple.remove(), 600);
            });
        });
    }
    
    // Responsive behavior
    function handleResponsiveFeatures() {
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
            // Collapse long descriptions on mobile
            document.querySelectorAll('.session-description').forEach(function(desc) {
                if (desc.textContent.length > 150) {
                    desc.classList.add('mobile-truncated');
                }
            });
        }
    }
    
    // Initialize URL parameters
    function initializeFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const eventType = urlParams.get('event_type');
        const sessionType = urlParams.get('session_type');
        const selectedDate = urlParams.get('date');
        
        if (eventType || sessionType || selectedDate) {
            // Wait for filter interface to be created
            setTimeout(() => {
                if (eventType) {
                    const eventFilterSelect = document.getElementById('event-type-filter');
                    if (eventFilterSelect) {
                        eventFilterSelect.value = eventType;
                    }
                }
                
                if (sessionType) {
                    const sessionFilterSelect = document.getElementById('session-type-filter');
                    if (sessionFilterSelect) {
                        sessionFilterSelect.value = sessionType;
                    }
                }
                
                if (selectedDate) {
                    const dateFilterSelect = document.getElementById('date-filter');
                    if (dateFilterSelect) {
                        dateFilterSelect.value = selectedDate;
                    }
                }
                
                // Update badge count
                const filterBadge = document.getElementById('filter-active-badge');
                if (filterBadge) {
                    let activeFilters = 0;
                    if (eventType) activeFilters++;
                    if (sessionType) activeFilters++;
                    if (selectedDate) activeFilters++;
                    
                    if (activeFilters > 0) {
                        filterBadge.textContent = activeFilters;
                        filterBadge.style.display = 'inline-block';
                    }
                }
            }, 100);
        }
    }
    
    // Legacy support for old template filtering
    window.filterSessions = function(type) {
        filterByEventType(type);
    };
    
    // Initialize all features
    function initializeModernSched() {
        initializeCardAnimations();
        initializeAdvancedFilter();
        initializeSpeakerChips();
        initializeSessionCardClicks();
        handleResponsiveFeatures();
        initializeFromURL();
    }
    
    // Run initialization
    if (document.querySelector('.sched-modern-container')) {
        initializeModernSched();
    }
    
    // Handle window resize
    let resizeTimeout;
    $(window).on('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(handleResponsiveFeatures, 250);
    });
    
    // Smooth scrolling for modern pagination
    $(document).on('click', '.sched-pagination-modern a', function(e) {
        // Allow default behavior but add smooth scroll
        setTimeout(function() {
            $('html, body').animate({
                scrollTop: $('.sched-modern-container').offset().top - 20
            }, 400);
        }, 100);
    });
});