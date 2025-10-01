// Rating functionality
document.addEventListener('DOMContentLoaded', function() {
    initRatingSystems();
});

function initRatingSystems() {
    // Initialize rating sliders
    const ratingSliders = document.querySelectorAll('input[type="range"].rating-slider');
    
    ratingSliders.forEach(slider => {
        const output = slider.nextElementSibling;
        const starsContainer = output.nextElementSibling;
        
        if (output && starsContainer) {
            // Set initial value
            output.textContent = slider.value;
            updateRatingStars(starsContainer, slider.value);
            
            // Update on change
            slider.addEventListener('input', function() {
                output.textContent = this.value;
                updateRatingStars(starsContainer, this.value);
            });
        }
    });
    
    // Initialize star rating systems
    const starRatings = document.querySelectorAll('.star-rating');
    
    starRatings.forEach(rating => {
        const stars = rating.querySelectorAll('.star');
        const input = rating.querySelector('input[type="hidden"]');
        
        if (stars.length && input) {
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    input.value = value;
                    setActiveStars(stars, value);
                });
                
                star.addEventListener('mouseenter', function() {
                    const value = this.getAttribute('data-value');
                    highlightStars(stars, value);
                });
                
                star.addEventListener('mouseleave', function() {
                    const currentValue = input.value || 0;
                    setActiveStars(stars, currentValue);
                });
            });
        }
    });
    
    // Load average ratings
    loadAverageRatings();
}

function updateRatingStars(container, value) {
    const stars = container.querySelectorAll('i');
    const numericValue = parseFloat(value);
    
    stars.forEach((star, index) => {
        star.classList.remove('fas', 'far', 'fa-star-half-alt');
        
        if (index < Math.floor(numericValue)) {
            star.classList.add('fas');
        } else if (index < numericValue) {
            star.classList.add('fas', 'fa-star-half-alt');
        } else {
            star.classList.add('far');
        }
    });
}

function setActiveStars(stars, value) {
    const numericValue = parseFloat(value);
    
    stars.forEach((star, index) => {
        star.classList.remove('active');
        if (index < numericValue) {
            star.classList.add('active');
        }
    });
}

function highlightStars(stars, value) {
    const numericValue = parseFloat(value);
    
    stars.forEach((star, index) => {
        star.classList.remove('highlight');
        if (index < numericValue) {
            star.classList.add('highlight');
        }
    });
}

function loadAverageRatings() {
    const ratingElements = document.querySelectorAll('[data-title-id]');
    
    ratingElements.forEach(element => {
        const titleId = element.getAttribute('data-title-id');
        
        ajaxRequest(`api/ratings.php?title_id=${titleId}`, {
            onSuccess: function(data) {
                if (data.success && data.average) {
                    element.innerHTML = `
                        <div class="rating-stars">${getRatingStarsHTML(data.average)}</div>
                        <span class="rating-value">${data.average.toFixed(1)}</span>
                        <small>(${data.count} ratings)</small>
                    `;
                }
            }
        });
    });
}

function getRatingStarsHTML(rating) {
    let starsHTML = '';
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    
    for (let i = 0; i < fullStars; i++) {
        starsHTML += '<i class="fas fa-star"></i>';
    }
    
    if (hasHalfStar) {
        starsHTML += '<i class="fas fa-star-half-alt"></i>';
    }
    
    const emptyStars = 10 - fullStars - (hasHalfStar ? 1 : 0);
    for (let i = 0; i < emptyStars; i++) {
        starsHTML += '<i class="far fa-star"></i>';
    }
    
    return starsHTML;
}

// Submit rating via AJAX
function submitRating(form, onSuccess, onError) {
    const formData = new FormData(form);
    const data = {
        title_id: formData.get('title_id'),
        user_id: formData.get('user_id'),
        rating: formData.get('rating')
    };
    
    ajaxRequest('api/ratings.php', {
        method: 'POST',
        data: data,
        onSuccess: function(response) {
            if (response.success) {
                if (onSuccess) onSuccess(response);
                showNotification('Rating submitted successfully!', 'success');
            } else {
                if (onError) onError(response);
                showNotification(response.message || 'Failed to submit rating.', 'error');
            }
        },
        onError: function(error) {
            if (onError) onError(error);
            showNotification('An error occurred while submitting your rating.', 'error');
        }
    });
}