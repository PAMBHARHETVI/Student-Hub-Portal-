
<?php
require_once __DIR__ . '/auth.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Async Event Management</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .async-events { max-width: 1100px; margin: 30px auto; padding: 20px; }
        .async-events input, .async-events textarea {
            display: block;
            width: 100%;
            max-width: 550px;
            padding: 10px;
            margin: 6px 0 14px;
            box-sizing: border-box;
        }
        .async-events button { padding: 8px 12px; margin: 4px; cursor: pointer; }
        .async-events .table-wrap { overflow-x: auto; }
        .async-events table { width: 100%; border-collapse: collapse; }
        .async-events th, .async-events td {
            border: 1px solid #ccc;
            padding: 9px;
            text-align: left;
        }
        .async-events th { background: #eee; color: #222; }
        #status { padding: 10px; margin: 12px 0; }
        #status:empty { display: none; }
        #status.error { background: #fde8e8; color: #991b1b; }
        #status.success { background: #e1f5e5; color: #14532d; }
    </style>
</head>
<body>
<main class="async-events">
    <h1>Event Management — Fetch API</h1>
    <p><a href="admin_dashboard.php">Back to Admin Dashboard</a></p>

    <p id="status" role="status" aria-live="polite"></p>

    <h2 id="form-title">Add Event</h2>

    <form id="event-form">
        <input type="hidden" id="event_id">

        <label for="title">Event title</label>
        <input id="title" maxlength="150" required>

        <label for="event_date">Event date</label>
        <input id="event_date" type="date" required>

        <label for="event_time">Event time</label>
        <input id="event_time" type="time">

        <label for="venue">Venue</label>
        <input id="venue" maxlength="200" required>

        <label for="category">Category</label>
        <input id="category" maxlength="80" required>

        <label for="description">Description</label>
        <textarea id="description" maxlength="5000"></textarea>

        <button type="submit" id="save-button">Add Event</button>
        <button type="button" id="cancel-button" hidden>Cancel Edit</button>
    </form>

    <hr>

    <h2>Event List</h2>
    <button type="button" id="refresh-button">Refresh Events</button>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Venue</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="event-list">
                <tr><td colspan="8">Loading events...</td></tr>
            </tbody>
        </table>
    </div>
</main>

<script>
const apiUrl = 'api/events.php';
let csrfToken = '';
let events = [];

const form = document.getElementById('event-form');
const list = document.getElementById('event-list');
const statusBox = document.getElementById('status');

function showMessage(message, type = 'success') {
    statusBox.textContent = message;
    statusBox.className = type;
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[char]);
}

async function apiRequest(method, data = null) {
    const options = {
        method,
        headers: { 'Accept': 'application/json' }
    };

    if (data !== null) {
        options.headers['Content-Type'] = 'application/json';
        options.headers['X-CSRF-Token'] = csrfToken;
        options.body = JSON.stringify(data);
    }

    const response = await fetch(apiUrl, options);
    const result = await response.json();

    if (!response.ok || !result.success) {
        throw new Error(result.message || 'Request failed.');
    }

    return result;
}

async function loadEvents(showStatus = false) {
    try {
        const result = await apiRequest('GET');
        csrfToken = result.csrf_token;
        events = result.events;
        renderEvents();

        if (showStatus) {
            showMessage('Event list refreshed successfully.');
        }
    } catch (error) {
        showMessage(error.message, 'error');
        list.innerHTML =
            '<tr><td colspan="8">Could not load events.</td></tr>';
    }
}

function renderEvents() {
    if (events.length === 0) {
        list.innerHTML =
            '<tr><td colspan="8">No events found.</td></tr>';
        return;
    }

    list.innerHTML = events.map(event => `
        <tr>
            <td>${Number(event.event_id)}</td>
            <td>${escapeHtml(event.title)}</td>
            <td>${escapeHtml(event.event_date)}</td>
            <td>${escapeHtml(event.event_time || 'Not specified')}</td>
            <td>${escapeHtml(event.venue)}</td>
            <td>${escapeHtml(event.category)}</td>
            <td>${escapeHtml(event.description)}</td>
            <td>
                <button type="button"
                    data-action="edit"
                    data-id="${Number(event.event_id)}">Edit</button>
                <button type="button"
                    data-action="delete"
                    data-id="${Number(event.event_id)}">Delete</button>
            </td>
        </tr>
    `).join('');
}

function resetForm() {
    form.reset();
    document.getElementById('event_id').value = '';
    document.getElementById('form-title').textContent = 'Add Event';
    document.getElementById('save-button').textContent = 'Add Event';
    document.getElementById('cancel-button').hidden = true;
}

form.addEventListener('submit', async event => {
    event.preventDefault();

    const id = document.getElementById('event_id').value;

    const data = {
        title: document.getElementById('title').value.trim(),
        event_date: document.getElementById('event_date').value,
        event_time: document.getElementById('event_time').value,
        venue: document.getElementById('venue').value.trim(),
        category: document.getElementById('category').value.trim(),
        description: document.getElementById('description').value.trim()
    };

    if (id) {
        data.event_id = Number(id);
    }

    try {
        const result = await apiRequest(id ? 'PUT' : 'POST', data);

        showMessage(result.message);
        resetForm();
        await loadEvents();
    } catch (error) {
        showMessage(error.message, 'error');
    }
});

list.addEventListener('click', async event => {
    const button = event.target.closest('button[data-action]');
    if (!button) return;

    const id = Number(button.dataset.id);
    const selected = events.find(item => Number(item.event_id) === id);
    if (!selected) return;

    if (button.dataset.action === 'edit') {
        document.getElementById('event_id').value = selected.event_id;
        document.getElementById('title').value = selected.title;
        document.getElementById('event_date').value = selected.event_date;
        document.getElementById('event_time').value =
            (selected.event_time || '').slice(0, 5);
        document.getElementById('venue').value = selected.venue;
        document.getElementById('category').value = selected.category;
        document.getElementById('description').value =
            selected.description || '';

        document.getElementById('form-title').textContent = 'Edit Event';
        document.getElementById('save-button').textContent = 'Update Event';
        document.getElementById('cancel-button').hidden = false;

        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        showMessage('Edit the event details and click Update Event.');
        return;
    }

    if (button.dataset.action === 'delete') {
        if (!confirm(`Delete "${selected.title}"?`)) return;

        try {
            const result = await apiRequest('DELETE', { event_id: id });
            showMessage(result.message);
            await loadEvents();

            if (Number(document.getElementById('event_id').value) === id) {
                resetForm();
            }
        } catch (error) {
            showMessage(error.message, 'error');
        }
    }
});

document.getElementById('cancel-button').addEventListener('click', resetForm);
document.getElementById('refresh-button').addEventListener('click', () => loadEvents(true));

loadEvents();
</script>
</body>
</html>
