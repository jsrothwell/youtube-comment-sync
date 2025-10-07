document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.getElementById('yt-comments-wrapper');
    if (!wrapper) {
        return; // Exit if our container isn't on the page
    }

    const videoId = wrapper.dataset.videoId;
    const apiKey = wrapper.dataset.apiKey;
    const commentsContainer = document.getElementById('youtube-comments-container');

    if (videoId && apiKey && commentsContainer) {
        fetchComments(apiKey, videoId, commentsContainer);
    }
});

async function fetchComments(apiKey, videoId, container) {
    const apiUrl = `https://www.googleapis.com/youtube/v3/commentThreads?part=snippet,replies&videoId=${videoId}&key=${apiKey}&maxResults=50&textFormat=plainText`;

    try {
        const response = await fetch(apiUrl);
        if (!response.ok) {
            const errorData = await response.json();
            const errorMessage = errorData.error?.message || `HTTP error! Status: ${response.status}`;
            throw new Error(errorMessage);
        }
        const data = await response.json();
        renderComments(data.items, container);
    } catch (error) {
        console.error('Error fetching comments:', error);
        showError(`Failed to load comments. Check API key and shortcode. Error: ${error.message}`, container);
    }
}

function renderComments(commentThreads, container) {
    if (!commentThreads || commentThreads.length === 0) {
        container.innerHTML = '<p class="yt-no-comments">No comments found for this video.</p>';
        return;
    }

    let commentsHtml = '<div class="yt-comments-list">';
    commentThreads.forEach(thread => {
        const topLevelComment = thread.snippet.topLevelComment.snippet;
        commentsHtml += '<div class="yt-comment-thread">';
        commentsHtml += createCommentHtml(topLevelComment);

        if (thread.replies) {
            commentsHtml += '<div class="yt-comment-replies">';
            thread.replies.comments.forEach(reply => {
                commentsHtml += createCommentHtml(reply.snippet);
            });
            commentsHtml += '</div>';
        }
        commentsHtml += '</div>';
    });
    commentsHtml += '</div>';
    container.innerHTML = commentsHtml;
}

function createCommentHtml(snippet) {
    const publishedDate = new Date(snippet.publishedAt).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    // Simple sanitization
    const safeText = snippet.textDisplay.replace(/</g, "&lt;").replace(/>/g, "&gt;");

    return `
        <div class="yt-comment">
            <img src="${snippet.authorProfileImageUrl}" alt="" class="yt-author-avatar">
            <div class="yt-comment-content">
                <div class="yt-comment-header">
                    <a href="${snippet.authorChannelUrl}" target="_blank" class="yt-author-name">${snippet.authorDisplayName}</a>
                    <span class="yt-comment-date">${publishedDate}</span>
                </div>
                <p class="yt-comment-text">${safeText}</p>
            </div>
        </div>
    `;
}

function showError(message, container) {
    container.innerHTML = `<div class="yt-error-message"><p>${message}</p></div>`;
}
