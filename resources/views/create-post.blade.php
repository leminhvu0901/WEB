<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Post</title>
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

        .card {
            width: min(840px, 100%);
            margin: 0 auto;
            background: var(--card);
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 18px 45px rgba(17, 24, 39, 0.09);
        }

        h1 {
            margin: 0 0 10px;
            font-size: 24px;
        }

        p {
            margin: 0 0 18px;
            color: var(--muted);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .col-span-2 {
            grid-column: span 2;
        }

        label {
            font-size: 12px;
            font-weight: 600;
            display: block;
            margin-bottom: 6px;
        }

        input,
        select,
        button,
        textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 10px;
            font-size: 14px;
            background: #fff;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }

        button {
            background: var(--action);
            color: #fff;
            border: none;
            font-weight: 600;
            cursor: pointer;
            margin-top: 4px;
        }

        button:hover {
            background: var(--action-hover);
        }

        .result {
            margin-top: 18px;
            border: 1px solid #111827;
            border-radius: 10px;
            overflow: hidden;
        }

        .result h2 {
            margin: 0;
            padding: 10px 12px;
            font-size: 14px;
            color: #fff;
            background: #111827;
        }

        pre {
            margin: 0;
            padding: 12px;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 160px;
            overflow: auto;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .preview-wrap {
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .preview-box {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px;
        }

        .preview-box h3 {
            margin: 0 0 10px;
            font-size: 13px;
            color: #374151;
        }

        .preview {
            width: 100%;
            height: 200px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            object-fit: cover;
            background: #f3f4f6;
            display: block;
        }

        .file-info {
            font-size: 12px;
            color: var(--muted);
            margin-top: 6px;
        }

        .form-group {
            margin-bottom: 12px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 16px;
            color: #3b82f6;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .message {
            padding: 12px;
            border-radius: 9px;
            margin-bottom: 16px;
            display: none;
        }

        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .message.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        @media (max-width: 700px) {
            .grid,
            .preview-wrap {
                grid-template-columns: 1fr;
            }

            .col-span-2 {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
<div class="card">
    <a href="/list-posts" class="back-link">← Back to Posts List</a>

    <h1 id="pageTitle">Create New Post</h1>
    <p id="pageDesc">Upload a new post with image and caption.</p>

    <div id="message" class="message"></div>

    <form id="createPostForm" class="grid">
        <div>
            <label for="token">Bearer Token (required)</label>
            <input id="token" type="text" placeholder="Get token from /login API">
        </div>

        <div>
            <label for="user_id">Select User</label>
            <select id="user_id">
                <option value="">Loading users...</option>
            </select>
        </div>

        <div class="col-span-2">
            <label for="caption">Caption</label>
            <textarea id="caption" placeholder="Write your caption here..."></textarea>
        </div>

        <div class="col-span-2">
            <label for="image">Image</label>
            <input id="image" type="file" accept="image/*">
            <div class="file-info" id="fileInfo"></div>
        </div>

        <div class="col-span-2">
            <button type="submit">Post</button>
        </div>
    </form>

    <div class="preview-wrap">
        <div class="preview-box">
            <h3>Image preview</h3>
            <img id="imagePreview" class="preview" alt="Image preview">
        </div>
    </div>

    <div class="result">
        <h2>Response</h2>
        <pre id="result">Waiting for request...</pre>
    </div>
</div>

<script>
    const API_BASE = "https://web-pgb0.onrender.com";
    const form = document.getElementById("createPostForm");
    const resultEl = document.getElementById("result");
    const imageInput = document.getElementById("image");
    const imagePreview = document.getElementById("imagePreview");
    const pageTitle = document.getElementById("pageTitle");
    const pageDesc = document.getElementById("pageDesc");
    const messageEl = document.getElementById("message");
    const captionInput = document.getElementById("caption");
    const fileInfo = document.getElementById("fileInfo");
    const tokenInput = document.getElementById("token");
    const userIdSelect = document.getElementById("user_id");

    let editPostId = null;
    let allUsers = [];

    // Load users list
    async function loadUsers() {
        try {
            const response = await fetch(`${API_BASE}/users`);
            const data = await response.json();

            if (data.status === 'success') {
                allUsers = data.data || [];
                const options = allUsers.map(user =>
                    `<option value="${user.id}">${user.username} (ID: ${user.id})</option>`
                ).join('');
                userIdSelect.innerHTML = `<option value="">Select a user</option>${options}`;
            } else {
                userIdSelect.innerHTML = '<option value="">Failed to load users</option>';
            }
        } catch (error) {
            userIdSelect.innerHTML = '<option value="">Error loading users</option>';
            console.error('Error loading users:', error);
        }
    }

    imageInput.addEventListener("change", function () {
        const file = imageInput.files && imageInput.files[0] ? imageInput.files[0] : null;

        if (!file) {
            imagePreview.removeAttribute("src");
            fileInfo.textContent = "";
            return;
        }

        const sizeMB = (file.size / 1024 / 1024).toFixed(2);
        fileInfo.textContent = `${file.name} - ${sizeMB} MB`;
        imagePreview.src = URL.createObjectURL(file);
    });

    async function loadPostForEdit(postId) {
        try {
            const response = await fetch(`${API_BASE}/posts/${postId}`);
            const data = await response.json();

            if (data.status === 'success') {
                const post = data.data;
                captionInput.value = post.caption || '';
                userIdSelect.value = post.user_id || '';

                if (post.image_url) {
                    imagePreview.src = post.image_url;
                }

                pageTitle.textContent = "Edit Post";
                pageDesc.textContent = "Update your post caption and image.";
                document.querySelector('button[type="submit"]').textContent = "Update";
            }
        } catch (error) {
            console.error('Error loading post:', error);
        }
    }

    form.addEventListener("submit", async function (event) {
        event.preventDefault();

        const token = tokenInput.value.trim();
        const userId = userIdSelect.value.trim();
        const caption = captionInput.value.trim();
        const image = imageInput.files && imageInput.files[0] ? imageInput.files[0] : null;

        if (!token) {
            showMessage('Please enter bearer token', 'error');
            return;
        }

        if (!userId) {
            showMessage('Please select a user', 'error');
            return;
        }

        if (!caption && !image) {
            showMessage('Please write a caption or upload an image', 'error');
            return;
        }

        const formData = new FormData();
        if (caption) formData.append("caption", caption);
        if (image) formData.append("image", image);

        const method = editPostId ? "POST" : "POST";
        const endpoint = editPostId ? `${API_BASE}/posts/${editPostId}` : `${API_BASE}/posts`;

        if (editPostId) {
            formData.append("_method", "PUT");
        }

        const headers = {
            "Accept": "application/json",
            "Authorization": "Bearer " + token,
        };

        try {
            const response = await fetch(endpoint, {
                method: method,
                headers,
                body: formData,
            });

            const raw = await response.text();
            let payload = raw;
            try {
                payload = JSON.parse(raw);
            } catch (_e) {
                // Keep raw string when response is not JSON.
            }

            resultEl.textContent = JSON.stringify({
                requested_url: endpoint,
                status: response.status,
                ok: response.ok,
                payload,
            }, null, 2);

            if (response.ok) {
                showMessage(editPostId ? 'Post updated successfully!' : 'Post created successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '/list-posts';
                }, 1500);
            } else {
                showMessage('Failed: ' + (payload.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            resultEl.textContent = "Request failed: " + String(error);
            showMessage('Error: ' + error.message, 'error');
        }
    });

    function showMessage(msg, type) {
        messageEl.className = "message " + type;
        messageEl.textContent = msg;
        messageEl.style.display = 'block';
    }

    // Check if editing an existing post
    document.addEventListener('DOMContentLoaded', function () {
        loadUsers();
        editPostId = sessionStorage.getItem('editPostId');
        if (editPostId) {
            loadPostForEdit(editPostId);
            sessionStorage.removeItem('editPostId');
        }
    });
</script>
</body>
</html>
