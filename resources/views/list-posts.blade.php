<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Posts List</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f3f5f8;
            --card: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #d1d5db;
            --action: #111827;
            --action-hover: #0b1220;
            --success: #10b981;
            --danger: #ef4444;
            --info: #3b82f6;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
            color: var(--text);
        }

        .container {
            width: min(1200px, 100%);
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
        }

        .header-actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: var(--action);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--action-hover);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-edit {
            background: var(--info);
            color: #fff;
            margin-right: 6px;
        }

        .btn-edit:hover {
            background: #2563eb;
        }

        .btn-delete {
            background: var(--danger);
            color: #fff;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .card {
            background: var(--card);
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 18px 45px rgba(17, 24, 39, 0.09);
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: var(--muted);
        }

        .spinner {
            display: inline-block;
            width: 24px;
            height: 24px;
            border: 3px solid #e5e7eb;
            border-top-color: var(--action);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 9px;
            margin-bottom: 16px;
            border: 1px solid #fecaca;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 9px;
            margin-bottom: 16px;
            border: 1px solid #bbf7d0;
        }

        .search-box {
            margin-bottom: 16px;
        }

        .search-box input {
            width: 100%;
            max-width: 400px;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 9px;
            font-size: 14px;
        }

        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .post-card {
            background: var(--card);
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .post-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .post-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f3f4f6;
            display: block;
        }

        .post-content {
            padding: 16px;
        }

        .post-author {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .post-author-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            background: #e5e7eb;
        }

        .post-author-info {
            flex: 1;
        }

        .post-author-info h4 {
            margin: 0;
            font-size: 13px;
            font-weight: 600;
        }

        .post-author-info p {
            margin: 2px 0 0;
            font-size: 11px;
            color: var(--muted);
        }

        .post-caption {
            font-size: 14px;
            line-height: 1.5;
            margin: 12px 0;
            color: var(--text);
            word-wrap: break-word;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .post-actions {
            display: flex;
            gap: 6px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
        }

        .btn-action {
            flex: 1;
            padding: 6px 10px;
            font-size: 12px;
            text-align: center;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--muted);
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }

            .posts-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Posts List</h1>
            <div class="header-actions">
                <a href="/create-post" class="btn btn-primary">Create Post</a>
                <a href="/list-users" class="btn btn-primary btn-sm" style="padding: 10px 12px;">View Users</a>
            </div>
        </div>

        <div class="card">
            <div id="message"></div>

            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search by caption or author...">
            </div>

            <div id="postsContainer">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Loading posts...</p>
                </div>
            </div>

            <div id="noneData" class="no-data" style="display: none;">
                <p>No posts found</p>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = "http://127.0.0.1:8000";
        let allPosts = [];

        async function loadPosts() {
            try {
                const response = await fetch(`${API_BASE}/posts`);
                const data = await response.json();

                if (data.status === 'success') {
                    allPosts = data.data || [];
                    renderPosts(allPosts);
                    showMessage('Posts loaded successfully', 'success');
                } else {
                    showMessage('Failed to load posts: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                showMessage('Error loading posts: ' + error.message, 'error');
                console.error(error);
            }
        }

        function renderPosts(posts) {
            const container = document.getElementById('postsContainer');
            const noneData = document.getElementById('noneData');

            if (posts.length === 0) {
                container.innerHTML = '';
                noneData.style.display = 'block';
                return;
            }

            noneData.style.display = 'none';
            container.innerHTML = `<div class="posts-grid">${posts.map(post => `
                <div class="post-card">
                    ${post.image_url ? `<img src="${post.image_url}" alt="Post" class="post-image">` : '<div class="post-image" style="background: #f3f4f6; display: flex; align-items: center; justify-content: center;"><span style="color: #9ca3af;">No image</span></div>'}
                    <div class="post-content">
                        <div class="post-author">
                            ${post.user && post.user.avatar_url ? `<img src="${post.user.avatar_url}" alt="${post.user.username}" class="post-author-avatar">` : '<div class="post-author-avatar" style="background: #d1d5db; display: flex; align-items: center; justify-content: center;"><span style="color: #6b7280; font-size: 10px;">No</span></div>'}
                            <div class="post-author-info">
                                <h4>${escapeHtml(post.user?.username || 'Unknown')}</h4>
                                <p>${formatDate(post.created_at)}</p>
                            </div>
                        </div>
                        <div class="post-caption">${escapeHtml(post.caption || 'No caption')}</div>
                        <div class="post-actions">
                            <button class="btn btn-sm btn-edit btn-action" onclick="editPost(${post.id})">Edit</button>
                            <button class="btn btn-sm btn-delete btn-action" onclick="deletePost(${post.id})">Delete</button>
                        </div>
                    </div>
                </div>
            `).join('')}</div>`;
        }

        function filterPosts() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const filtered = allPosts.filter(post => {
                const caption = (post.caption || '').toLowerCase();
                const username = (post.user?.username || '').toLowerCase();
                return caption.includes(searchTerm) || username.includes(searchTerm);
            });
            renderPosts(filtered);
        }

        async function deletePost(postId) {
            if (!confirm('Delete this post? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/posts/${postId}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' }
                });

                const data = await response.json();

                if (data.status === 'success') {
                    showMessage('Post deleted successfully', 'success');
                    allPosts = allPosts.filter(p => p.id !== postId);
                    renderPosts(allPosts);
                } else {
                    showMessage('Delete failed: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                showMessage('Error deleting post: ' + error.message, 'error');
                console.error(error);
            }
        }

        function editPost(postId) {
            sessionStorage.setItem('editPostId', postId);
            window.location.href = '/create-post';
        }

        function showMessage(msg, type) {
            const messageEl = document.getElementById('message');
            messageEl.className = type;
            messageEl.textContent = msg;
            messageEl.style.display = 'block';

            if (type === 'success') {
                setTimeout(() => {
                    messageEl.style.display = 'none';
                }, 3000);
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'just now';
            if (diffMins < 60) return diffMins + 'm ago';
            if (diffHours < 24) return diffHours + 'h ago';
            if (diffDays < 7) return diffDays + 'd ago';

            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined });
        }

        // Event listeners
        document.getElementById('searchInput').addEventListener('input', filterPosts);

        // Load posts on page load
        document.addEventListener('DOMContentLoaded', loadPosts);
    </script>
</body>
</html>
