<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Update User Test</title>
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
            width: 120px;
            height: 120px;
            border-radius: 999px;
            border: 1px solid #d1d5db;
            object-fit: cover;
            background: #f3f4f6;
            display: block;
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
    <h1>Update User + Avatar Test</h1>
    <p>Use this page to manually test user updates and avatar upload with multipart/form-data. This project exposes API routes at root, for example /users/{id}.</p>

    <form id="updateUserForm" class="grid">
        <div class="col-span-2">
            <label for="endpoint">API endpoint</label>
            <input id="endpoint" type="text" value="https://web-pgb0.onrender.com/users/{id}">
        </div>

        <div>
            <label for="method">Method</label>
            <select id="method">
                <option value="POST">POST</option>
                <option value="PUT">PUT</option>
                <option value="PATCH" selected>PATCH</option>
            </select>
        </div>

        <div>
            <label for="token">Bearer token (optional)</label>
            <input id="token" type="text" placeholder="eyJ...">
        </div>

        <div>
            <label for="user_id">User ID</label>
            <input id="user_id" type="number" min="1" placeholder="1">
        </div>

        <div>
            <label for="username">Username</label>
            <input id="username" type="text" placeholder="Your username">
        </div>

        <div>
            <label for="email">Email</label>
            <input id="email" type="email" placeholder="user@example.com">
        </div>

        <div>
            <label for="password">Password (optional)</label>
            <input id="password" type="password" placeholder="********">
        </div>

        <div>
            <label for="password_confirmation">Password confirmation</label>
            <input id="password_confirmation" type="password" placeholder="********">
        </div>

        <div>
            <label for="avatar">Avatar file</label>
            <input id="avatar" type="file" accept="image/*">
        </div>

        <div class="col-span-2">
            <button type="submit">Send Request</button>
        </div>
    </form>

    <div class="preview-wrap">
        <div class="preview-box">
            <h3>Local preview</h3>
            <img id="localPreview" class="preview" alt="Local avatar preview">
        </div>
        <div class="preview-box">
            <h3>Server avatar preview</h3>
            <img id="serverPreview" class="preview" alt="Server avatar preview">
        </div>
    </div>

    <div class="result">
        <h2>Response</h2>
        <pre id="result">Waiting for request...</pre>
    </div>
</div>

<script>
    const form = document.getElementById("updateUserForm");
    const resultEl = document.getElementById("result");
    const avatarInput = document.getElementById("avatar");
    const localPreview = document.getElementById("localPreview");
    const serverPreview = document.getElementById("serverPreview");

    avatarInput.addEventListener("change", function () {
        const file = avatarInput.files && avatarInput.files[0] ? avatarInput.files[0] : null;
        if (!file) {
            localPreview.removeAttribute("src");
            return;
        }
        localPreview.src = URL.createObjectURL(file);
    });

    function pickAvatarValue(payload) {
        const candidates = [
            payload && payload.avatar_url,
            payload && payload.avatar,
            payload && payload.data && payload.data.avatar_url,
            payload && payload.data && payload.data.avatar,
            payload && payload.user && payload.user.avatar_url,
            payload && payload.user && payload.user.avatar,
            payload && payload.data && payload.data.user && payload.data.user.avatar_url,
            payload && payload.data && payload.data.user && payload.data.user.avatar,
        ];

        for (let i = 0; i < candidates.length; i += 1) {
            if (typeof candidates[i] === "string" && candidates[i].trim() !== "") {
                return candidates[i].trim();
            }
        }

        return "";
    }

    function buildAvatarUrl(rawValue) {
        if (!rawValue || typeof rawValue !== "string") {
            return "";
        }

        if (/^https?:\/\//i.test(rawValue)) {
            return rawValue;
        }

        const value = rawValue.replace(/^\/+/, "");

        if (value.startsWith("storage/")) {
            return "https://web-pgb0.onrender.com/" + value;
        }

        if (value.startsWith("avatars/")) {
            return "https://web-pgb0.onrender.com/storage/" + value;
        }

        return "https://web-pgb0.onrender.com/" + value;
    }

    function buildEndpoint(rawEndpoint, userId) {
        let endpoint = rawEndpoint.trim();

        // Keep backward compatibility with the old incorrect endpoint.
        if (endpoint.endsWith("/api/user/update")) {
            if (!userId) {
                throw new Error("Missing user_id. This API needs /users/{id}.");
            }
            return endpoint.replace(/\/api\/user\/update$/, "/users/" + encodeURIComponent(userId));
        }

        if (endpoint.includes("{id}")) {
            if (!userId) {
                throw new Error("Missing user_id to replace {id} in endpoint.");
            }
            endpoint = endpoint.replace("{id}", encodeURIComponent(userId));
        }

        return endpoint;
    }

    form.addEventListener("submit", async function (event) {
        event.preventDefault();

        const rawEndpoint = document.getElementById("endpoint").value.trim();
        const method = document.getElementById("method").value;
        const token = document.getElementById("token").value.trim();
        const userId = document.getElementById("user_id").value.trim();
        const username = document.getElementById("username").value.trim();
        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value;
        const passwordConfirmation = document.getElementById("password_confirmation").value;
        const avatar = avatarInput.files && avatarInput.files[0] ? avatarInput.files[0] : null;

        let endpoint;
        try {
            endpoint = buildEndpoint(rawEndpoint, userId);
        } catch (error) {
            resultEl.textContent = "Endpoint error: " + String(error.message || error);
            return;
        }

        const formData = new FormData();

        if (userId) formData.append("user_id", userId);
        if (username) formData.append("username", username);
        if (email) formData.append("email", email);
        if (password) formData.append("password", password);
        if (passwordConfirmation) formData.append("password_confirmation", passwordConfirmation);
        if (avatar) formData.append("avatar", avatar);

        if (method !== "POST") {
            formData.append("_method", method);
        }

        const headers = {
            "Accept": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
        };

        if (token) {
            headers["Authorization"] = "Bearer " + token;
        }

        try {
            const response = await fetch(endpoint, {
                method: "POST",
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

            const avatarValue = pickAvatarValue(payload);
            const avatarUrl = buildAvatarUrl(avatarValue);

            if (avatarUrl) {
                serverPreview.src = avatarUrl;
            } else {
                serverPreview.removeAttribute("src");
            }
        } catch (error) {
            resultEl.textContent = "Request failed: " + String(error);
            serverPreview.removeAttribute("src");
        }
    });
</script>
</body>
</html>
