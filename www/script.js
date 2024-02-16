document.addEventListener('DOMContentLoaded', function() {
    // Initialize post loading and setup. If using static content, this can be omitted or adjusted.
    adjustResponsesVisibility(); // Ensure only the first five responses are visible on load.
    setupImageToggle(); // Setup click-to-toggle size functionality for images.
});

// Adjusts visibility of responses beyond the fifth for each post.
function adjustResponsesVisibility() {
    document.querySelectorAll('.post').forEach(post => {
        const responses = post.querySelectorAll('.response');
        responses.forEach((response, index) => {
            if (index >= 5) {
                response.style.display = 'none'; // Hide responses beyond the fifth.
            }
        });
    });
}

// Toggles the visibility of responses for a post and updates button text.
function toggleResponses(button) {
    const responses = button.nextElementSibling.querySelectorAll('.response');
    let isAnyHidden = Array.from(responses).slice(5).some(response => response.style.display === 'none');
    
    responses.forEach((response, index) => {
        if (index >= 5) {
            response.style.display = isAnyHidden ? 'block' : 'none'; // Toggle display for responses beyond the fifth.
        }
    });
    
    button.textContent = isAnyHidden ? "Hide replies" : "Show all replies"; // Update button text.
}

// Sets up click event listeners on images to toggle their size.
function setupImageToggle() {
    document.querySelectorAll('.post-image, .response-image').forEach(image => {
        image.addEventListener('click', function() {
            // Toggle image size between 150x150 and 250x250.
            const isExpanded = this.style.width === '250px' || this.style.width === '';
            this.style.width = isExpanded ? '150px' : '250px';
            this.style.height = isExpanded ? '150px' : '250px';
        });
    });
}
