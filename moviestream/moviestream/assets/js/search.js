// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    initSearch();
});

function initSearch() {
    // Live search
    const searchInputs = document.querySelectorAll('.live-search');
    
    searchInputs.forEach(input => {
        input.addEventListener('input', debounce(function() {
            performSearch(this.value);
        }, 300));
    });
    
    // Search form submission
    const searchForms = document.querySelectorAll('form.search-form');
    
    searchForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const input = this.querySelector('input[type="search"]');
            if (input && input.value.trim()) {
                performSearch(input.value.trim(), true);
            }
        });
    });
    
    // Filter changes
    const filters = document.querySelectorAll('.search-filter select, .search-filter input');
    
    filters.forEach(filter => {
        filter.addEventListener('change', function() {
            applyFilters();
        });
    });
}

function performSearch(query, updateURL = false) {
    const resultsContainer = document.getElementById('search-results');
    const loadingIndicator = document.getElementById('search-loading');
    
    if (!resultsContainer) return;
    
    if (loadingIndicator) {
        loadingIndicator.style.display = 'block';
    }
    
    resultsContainer.innerHTML = '';
    
    const url = new URL('api/search.php', window.location.origin);
    url.searchParams.set('q', query);
    
    // Add filters
    const filters = document.querySelectorAll('.search-filter select, .search-filter input');
    filters.forEach(filter => {
        if (filter.value && filter.name) {
            url.searchParams.set(filter.name, filter.value);
        }
    });
    
    ajaxRequest(url.toString(), {
        onSuccess: function(data) {
            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
            }
            
            if (data.success && data.results && data.results.length > 0) {
                displaySearchResults(data.results, resultsContainer);
                
                if (updateURL) {
                    updateBrowserURL(url.searchParams);
                }
            } else {
                resultsContainer.innerHTML = `
                    <div class="card">
                        <p>No results found for "${query}"</p>
                    </div>
                `;
            }
        },
        onError: function(error) {
            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
            }
            
            resultsContainer.innerHTML = `
                <div class="card">
                    <p>An error occurred while searching. Please try again.</p>
                </div>
            `;
        }
    });
}

function applyFilters() {
    const searchInput = document.querySelector('.live-search');
    if (searchInput && searchInput.value) {
        performSearch(searchInput.value);
    } else {
        // Load all titles with filters
        loadFilteredTitles();
    }
}

function loadFilteredTitles() {
    const resultsContainer = document.getElementById('search-results');
    const loadingIndicator = document.getElementById('search-loading');
    
    if (!resultsContainer) return;
    
    if (loadingIndicator) {
        loadingIndicator.style.display = 'block';
    }
    
    resultsContainer.innerHTML = '';
    
    const url = new URL('api/titles.php', window.location.origin);
    
    // Add filters
    const filters = document.querySelectorAll('.search-filter select, .search-filter input');
    filters.forEach(filter => {
        if (filter.value && filter.name) {
            url.searchParams.set(filter.name, filter.value);
        }
    });
    
    ajaxRequest(url.toString(), {
        onSuccess: function(data) {
            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
            }
            
            if (data.success && data.titles && data.titles.length > 0) {
                displaySearchResults(data.titles, resultsContainer);
                updateBrowserURL(url.searchParams);
            } else {
                resultsContainer.innerHTML = `
                    <div class="card">
                        <p>No titles found with the selected filters.</p>
                    </div>
                `;
            }
        },
        onError: function(error) {
            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
            }
            
            resultsContainer.innerHTML = `
                <div class="card">
                    <p>An error occurred while loading titles. Please try again.</p>
                </div>
            `;
        }
    });
}

function displaySearchResults(results, container) {
    let html = '<div class="grid">';
    
    results.forEach(result => {
        html += `
            <div class="title-card">
                <img src="assets/images/placeholder-poster.jpg" alt="${result.name}" class="title-poster">
                <div class="title-info">
                    <h3 class="title-name">${result.name}</h3>
                    <div class="title-meta">
                        <span class="badge badge-primary">${result.type}</span>
                        <span>${result.release_date}</span>
                    </div>
                    <p class="title-description">${result.description || 'No description available.'}</p>
                    <div class="title-actions">
                        <a href="title.php?id=${result.title_id}" class="btn btn-sm">View Details</a>
                        <a href="add_rating.php?title_id=${result.title_id}" class="btn btn-sm btn-outline">Rate</a>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    
    // Add pagination if available
    if (results.pagination) {
        html += generatePagination(results.pagination);
    }
    
    container.innerHTML = html;
}

function generatePagination(pagination) {
    let html = '<div class="pagination">';
    
    if (pagination.current_page > 1) {
        html += `<a href="?page=${pagination.current_page - 1}">&laquo;</a>`;
    }
    
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === pagination.current_page) {
            html += `<span class="current">${i}</span>`;
        } else {
            html += `<a href="?page=${i}">${i}</a>`;
        }
    }
    
    if (pagination.current_page < pagination.total_pages) {
        html += `<a href="?page=${pagination.current_page + 1}">&raquo;</a>`;
    }
    
    html += '</div>';
    return html;
}

function updateBrowserURL(params) {
    const newURL = new URL(window.location);
    
    // Clear existing search params
    for (const key of newURL.searchParams.keys()) {
        newURL.searchParams.delete(key);
    }
    
    // Add new params
    for (const [key, value] of params.entries()) {
        newURL.searchParams.set(key, value);
    }
    
    window.history.replaceState({}, '', newURL);
}

// Initialize from URL parameters
function initFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    const query = urlParams.get('q');
    
    if (query) {
        const searchInput = document.querySelector('.live-search');
        if (searchInput) {
            searchInput.value = query;
            performSearch(query);
        }
    }
    
    // Set filter values from URL
    const filters = document.querySelectorAll('.search-filter select, .search-filter input');
    filters.forEach(filter => {
        const value = urlParams.get(filter.name);
        if (value) {
            filter.value = value;
        }
    });
}