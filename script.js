document.addEventListener('DOMContentLoaded', function() {
    fetchPosts();
});

function fetchPosts() {
    fetch('fetch_posts.php') // Adjust this URL to your PHP script endpoint
        .then(response => response.json())
        .then(posts => {
            const forum = document.getElementById('forum');
            forum.innerHTML = ''; // Clear existing content
            posts.forEach(post => {
                const postElement = document.createElement('div');
                postElement.className = 'post';
                postElement.innerHTML = `
                    <h2>${post.title} <span class="post-id">#${post.id}</span></h2>
                    <p>${post.content}</p>
                    <button onclick="toggleResponse(this)">Show all responses</button>
                    <div class="responses">` +
                    post.responses.map(response => `
                        <div class="response">
                            <span class="respone-id">#${response.id}</span> ${response.content}
                        </div>
                    `).join('') + `
                    </div>
                `;
                forum.appendChild(postElement);
            });
        })
        .catch(error => console.error('Error loading posts:', error));
}

function toggleResponse(button) {
    const responses = button.nextElementSibling;
    responses.style.display = responses.style.display === 'none' ? 'block' : 'none';
    button.textContent = responses.style.display === 'none' ? 'Show all responses' : 'Hide responses';
}
