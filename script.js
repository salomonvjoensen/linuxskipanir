document.addEventListener('DOMContentLoaded', function() {
    // Initialize post loading and setup. If using static content, this can be omitted or adjusted.
    // fetchPosts(); // Uncomment if dynamically loading posts.
    adjustResponsesVisibility(); // Ensure only the first five responses are visible on load.
    setupImageToggle(); // Setup click-to-toggle size functionality for images.
});

// Function to fetch and display posts dynamically. Adjust as needed for your application.
function fetchPosts() {
    fetch('fetch_posts.php')
        .then(response => response.json())
        .then(posts => {
            const forum = document.getElementById('forum');
            forum.innerHTML = ''; // Clear existing forum content.
            posts.forEach(post => {
                const postElement = document.createElement('div');
                postElement.className = 'post';
                let responsesHtml = post.responses.map((response, index) => `
                    <div class="response">
                        <span class="response-id">#${response.id}</span> ${response.content}
                    </div>
                `).join('');
                postElement.innerHTML = `
                    <h2>${post.title} <span class="post-id">#${post.id}</span></h2>
                    <p>${post.content}</p>
                    <button onclick="toggleResponses(this)">Show all replies</button>
                    <div class="responses">
                        ${responsesHtml}
                    </div>
                `;
                forum.appendChild(postElement);
            });
            adjustResponsesVisibility(); // Call this after posts are loaded to ensure correct initial visibility.
            setupImageToggle(); // Ensure images are set up for toggling after dynamic content is loaded.
        })
        .catch(error => console.error('Error loading posts:', error));
}

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
