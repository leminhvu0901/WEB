<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Users List</title>
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
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
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

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        thead {
            background: #f9fafb;
            border-bottom: 2px solid var(--border);
        }

        th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: var(--text);
        }

        td {
            padding: 12px;
            border-bottom: 1px solid var(--border);
        }

        tr:hover {
            background: #f9fafb;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            background: #e5e7eb;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-admin {
            background: #fecaca;
            color: #991b1b;
        }

        .badge-user {
            background: #a7f3d0;
            color: #065f46;
        }

        .actions {
            white-space: nowrap;
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info-text h4 {
            margin: 0;
            font-size: 14px;
        }

        .user-info-text p {
            margin: 2px 0 0;
            font-size: 12px;
            color: var(--muted);
        }

        .pagination {
            display: flex;
            gap: 6px;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination button,
        .pagination a {
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--card);
            color: var(--text);
            cursor: pointer;
            font-size: 12px;
        }

        .pagination button:hover,
        .pagination a:hover {
            background: #f9fafb;
        }

        .pagination .active {
            background: var(--action);
            color: #fff;
            border-color: var(--action);
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--muted);
        }

        .search-box {
            margin-bottom: 16px;
        }

        .search-box input {
            width: 100%;
            max-width: 300px;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 9px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Users List</h1>
            <a href="/update-user" class="btn btn-primary">Back to Test</a>
        </div>

        <div class="card">
            <div id="message"></div>

            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search by username or email...">
            </div>

            <div class="table-wrapper">
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th style="width: 40%;">User Info</th>
                            <th style="width: 20%;">Role</th>
                            <th style="width: 15%;">Posts</th>
                            <th style="width: 25%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersList">
                        <tr>
                            <td colspan="4" class="loading">
                                <div class="spinner"></div>
                                <p>Loading users...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="noneData" class="no-data" style="display: none;">
                <p>No users found</p>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = "https://web-pgb0.onrender.com";
        let allUsers = [];

        async function loadUsers() {
            try {
                const response = await fetch(`${API_BASE}/users`);
                const data = await response.json();

                if (data.status === 'success') {
                    allUsers = data.data || [];
                    renderUsers(allUsers);
                    showMessage('Users loaded successfully', 'success');
                } else {
                    showMessage('Failed to load users: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                showMessage('Error loading users: ' + error.message, 'error');
                console.error(error);
            }
        }

        function renderUsers(users) {
            const usersList = document.getElementById('usersList');
            const noneData = document.getElementById('noneData');

            if (users.length === 0) {
                usersList.innerHTML = '';
                noneData.style.display = 'block';
                return;
            }

            noneData.style.display = 'none';
            usersList.innerHTML = users.map(user => `
                <tr>
                    <td>
                        <div class="user-info">
                            ${user.avatar_url ? `<img src="${user.avatar_url}" alt="${user.username}" class="avatar">` : '<div class="avatar" style="background: #d1d5db; display: flex; align-items: center; justify-content: center;"><span style="color: #6b7280; font-size: 12px;">No avatar</span></div>'}
                            <div class="user-info-text">
                                <h4>${escapeHtml(user.username)}</h4>
                                <p>${escapeHtml(user.email)}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge ${user.role === 'admin' ? 'badge-admin' : 'badge-user'}">
                            ${user.role || 'user'}
                        </span>
                    </td>
                    <td>
                        <strong>${user.posts_count || 0}</strong> posts
                    </td>
                    <td>
                        <div class="actions">
                            <button class="btn btn-sm btn-edit" onclick="editUser(${user.id})">Edit</button>
                            <button class="btn btn-sm btn-delete" onclick="deleteUser(${user.id}, '${escapeHtml(user.username)}')">Delete</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function filterUsers() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const filtered = allUsers.filter(user =>
                user.username.toLowerCase().includes(searchTerm) ||
                user.email.toLowerCase().includes(searchTerm)
            );
            renderUsers(filtered);
        }

        async function deleteUser(userId, username) {
            if (!confirm(`Delete user "${username}"? This action cannot be undone.`)) {
                return;
            }

            try {
                const response = await fetch(`${API_BASE}/users/${userId}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' }
                });

                const data = await response.json();

                if (data.status === 'success') {
                    showMessage(`User "${username}" deleted successfully`, 'success');
                    allUsers = allUsers.filter(u => u.id !== userId);
                    renderUsers(allUsers);
                } else {
                    showMessage('Delete failed: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                showMessage('Error deleting user: ' + error.message, 'error');
                console.error(error);
            }
        }

        function editUser(userId) {
            const user = allUsers.find(u => u.id === userId);
            if (!user) {
                showMessage('User not found', 'error');
                return;
            }
            // Store user ID in session storage so update-user page can prefill
            sessionStorage.setItem('editUserId', userId);
            window.location.href = '/update-user';
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

        // Event listeners
        document.getElementById('searchInput').addEventListener('input', filterUsers);

        // Load users on page load
        document.addEventListener('DOMContentLoaded', loadUsers);
    </script>
</body>
</html>
