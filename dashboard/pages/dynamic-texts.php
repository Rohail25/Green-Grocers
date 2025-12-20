<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Admin only access
requireAuth();
$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'admin') {
    header('Location: ' . BASE_PATH . '/dashboard/pages/dashboard.php');
    exit;
}

$pageTitle = 'Manage Dynamic Texts';
$texts = getAllDynamicTexts();
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
        <h2 class="text-3xl font-bold text-gray-800">Manage Dynamic Texts</h2>
        <button onclick="openAddTextModal()" class="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add Text
        </button>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-green-600 text-white">
                    <tr>
                        <th class="px-4 py-3">Title</th>
                        <th class="px-4 py-3">Content Preview</th>
                        <th class="px-4 py-3">Position</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Created</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($texts as $text): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-semibold"><?php echo htmlspecialchars($text['title']); ?></td>
                            <td class="px-4 py-3">
                                <p class="text-sm text-gray-600 line-clamp-2"><?php echo htmlspecialchars(substr($text['content'], 0, 100)); ?><?php echo strlen($text['content']) > 100 ? '...' : ''; ?></p>
                            </td>
                            <td class="px-4 py-3"><?php echo $text['position']; ?></td>
                            <td class="px-4 py-3">
                                <?php if ($text['is_active']): ?>
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-800">Active</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-800">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3"><?php echo date('Y-m-d', strtotime($text['created_at'])); ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <button onclick="openEditTextModal(<?php echo $text['id']; ?>)" class="text-blue-600 hover:text-blue-800">Edit</button>
                                    <button onclick="deleteText(<?php echo $text['id']; ?>, '<?php echo htmlspecialchars($text['title']); ?>')" class="text-red-600 hover:text-red-800">Delete</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($texts)): ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">No dynamic texts found. Click "Add Text" to create one.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Text Modal -->
<div id="textModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full mx-4 flex flex-col max-h-[90vh]">
        <div class="flex justify-between items-center p-6 border-b flex-shrink-0">
            <h3 class="text-xl font-bold" id="textModalTitle">Add Text</h3>
            <button onclick="closeTextModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form id="textForm" onsubmit="saveText(event)" class="flex flex-col flex-1 min-h-0">
            <input type="hidden" id="textId" name="id">
            <div class="space-y-4 p-6 overflow-y-auto flex-1">
                <div>
                    <label class="block text-sm font-medium mb-1">Title *</label>
                    <input type="text" id="textTitle" name="title" required class="w-full px-3 py-2 border rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Content *</label>
                    <textarea id="textContent" name="content" required rows="6" class="w-full px-3 py-2 border rounded-md"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Position</label>
                    <input type="number" id="textPosition" name="position" value="0" min="0" class="w-full px-3 py-2 border rounded-md">
                    <p class="text-xs text-gray-500 mt-1">Lower numbers appear first</p>
                </div>
                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" id="textIsActive" name="is_active" checked class="rounded">
                        <span class="text-sm font-medium">Active</span>
                    </label>
                </div>
            </div>
            <div class="flex gap-2 pt-4 border-t p-6 flex-shrink-0">
                <button type="submit" class="flex-1 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">Save</button>
                <button type="button" onclick="closeTextModal()" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
const basePath = '<?php echo BASE_PATH; ?>';

function openAddTextModal() {
    document.getElementById('textModalTitle').textContent = 'Add Text';
    document.getElementById('textForm').reset();
    document.getElementById('textId').value = '';
    document.getElementById('textIsActive').checked = true;
    document.getElementById('textModal').classList.remove('hidden');
}

function openEditTextModal(id) {
    fetch(`${basePath}/dashboard/pages/get-dynamic-text.php?id=${id}`)
        .then(response => response.json())
        .then(text => {
            document.getElementById('textModalTitle').textContent = 'Edit Text';
            document.getElementById('textId').value = text.id;
            document.getElementById('textTitle').value = text.title;
            document.getElementById('textContent').value = text.content;
            document.getElementById('textPosition').value = text.position || 0;
            document.getElementById('textIsActive').checked = text.is_active == 1;
            document.getElementById('textModal').classList.remove('hidden');
        })
        .catch(error => {
            alert('Error loading text data');
            console.error(error);
        });
}

function closeTextModal() {
    document.getElementById('textModal').classList.add('hidden');
}

function saveText(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', document.getElementById('textId').value ? 'update' : 'create');
    formData.append('is_active', document.getElementById('textIsActive').checked ? '1' : '0');
    
    fetch(`${basePath}/includes/dynamic-text-action.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Error saving text');
        }
    })
    .catch(error => {
        alert('Error saving text');
        console.error(error);
    });
}

function deleteText(id, title) {
    if (!confirm(`Are you sure you want to delete: "${title}"?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    
    fetch(`${basePath}/includes/dynamic-text-action.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Error deleting text');
        }
    })
    .catch(error => {
        alert('Error deleting text');
        console.error(error);
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

