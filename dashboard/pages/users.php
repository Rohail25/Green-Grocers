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

$pageTitle = 'Manage Users';
$users = getAllUsers();
$message = $_SESSION['user_message'] ?? '';
$error = $_SESSION['user_error'] ?? '';
unset($_SESSION['user_message'], $_SESSION['user_error']);
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="space-y-6">
    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
        <h2 class="text-3xl font-bold text-gray-800">Manage Users</h2>
        <button onclick="openAddUserModal()" class="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add User
        </button>
    </div>

    <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-green-600 text-white">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <!-- <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Platform</th>
                        <th class="px-4 py-3">Phone</th> -->
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Created</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <?php echo htmlspecialchars(trim(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? ''))); ?>
                            </td>
                            <!-- <td class="px-4 py-3"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded text-xs font-semibold <?php 
                                    echo $user['role'] === 'admin' ? 'bg-red-100 text-red-800' : 
                                        ($user['role'] === 'vendor' ? 'bg-blue-100 text-blue-800' : 
                                        ($user['role'] === 'customer' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800')); 
                                ?>">
                                    <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($user['platform'] ?? 'N/A'); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td> -->
                            <td class="px-4 py-3">
                                <?php if ($user['isEmailConfirmed'] ?? false): ?>
                                    <span class="text-green-600">✓ Verified</span>
                                <?php else: ?>
                                    <span class="text-yellow-600">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <button onclick="openEditUserModal('<?php echo $user['id']; ?>')" class="text-blue-600 hover:text-blue-800">Edit</button>
                                    <!-- <button onclick="changeUserPassword('<?php echo $user['id']; ?>', '<?php echo htmlspecialchars($user['email']); ?>')" class="text-purple-600 hover:text-purple-800">Change Password</button> -->
                                    <?php if ($user['id'] !== $currentUser['id']): ?>
                                        <button onclick="deleteUser('<?php echo $user['id']; ?>', '<?php echo htmlspecialchars($user['email']); ?>')" class="text-red-600 hover:text-red-800">Delete</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div id="userModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full mx-4 flex flex-col max-h-[90vh]">
        <div class="flex justify-between items-center p-6 border-b flex-shrink-0">
            <h3 class="text-xl font-bold" id="modalTitle">Add User</h3>
            <button onclick="closeUserModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form id="userForm" onsubmit="saveUser(event)" class="flex flex-col flex-1 min-h-0">
            <input type="hidden" id="userId" name="id">
            <div class="space-y-4 p-6 overflow-y-auto flex-1">
                <div>
                    <label class="block text-sm font-medium mb-1">Email *</label>
                    <input type="email" id="userEmail" name="email" required class="w-full px-3 py-2 border rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">First Name *</label>
                    <input type="text" id="userFirstName" name="firstName" required class="w-full px-3 py-2 border rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Last Name</label>
                    <input type="text" id="userLastName" name="lastName" class="w-full px-3 py-2 border rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Phone</label>
                    <input type="text" id="userPhone" name="phone" class="w-full px-3 py-2 border rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Role *</label>
                    <select id="userRole" name="role" required class="w-full px-3 py-2 border rounded-md">
                        <option value="customer">Customer</option>
                        <option value="admin">Admin</option>
                        <option value="vendor">Vendor</option>
                        <!-- <option value="logistic">Logistic</option>
                        <option value="agent">Agent</option> -->
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Platform *</label>
                    <select id="userPlatform" name="platform" required class="w-full px-3 py-2 border rounded-md">
                        <option value="trivemart">TriveMart</option>
                        <option value="trivestore">TriveStore</option>
                    </select>
                </div>
                <div id="passwordField">
                    <label class="block text-sm font-medium mb-1">Password *</label>
                    <input type="password" id="userPassword" name="password" class="w-full px-3 py-2 border rounded-md">
                    <p class="text-xs text-gray-500 mt-1">Leave blank when editing (password won't change)</p>
                </div>
            </div>
            <div class="px-6 py-4 border-t bg-white sticky bottom-0">
    <div class="flex gap-2">
        <button
            type="submit"
            class="flex-1 bg-green-600 text-white py-2 rounded-md hover:bg-green-700"
        >
            Save
        </button>

        <button
            type="button"
            onclick="closeUserModal()"
            class="flex-1 bg-gray-300 text-gray-700 py-2 rounded-md hover:bg-gray-400"
        >
            Cancel
        </button>
    </div>
</div>

        </form>
    </div>
</div>

<!-- Change Password Modal -->
<div id="passwordModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Change Password</h3>
            <button onclick="closePasswordModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form id="passwordForm" onsubmit="savePassword(event)">
            <input type="hidden" id="passwordUserId" name="userId">
            <div class="mb-4">
                <p class="text-sm text-gray-600">Changing password for: <strong id="passwordUserEmail"></strong></p>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Current Password *</label>
                    <input type="password" id="currentPassword" name="currentPassword" required class="w-full px-3 py-2 border rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">New Password *</label>
                    <input type="password" id="newPassword" name="newPassword" required minlength="6" class="w-full px-3 py-2 border rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Confirm Password *</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required minlength="6" class="w-full px-3 py-2 border rounded-md">
                </div>
            </div>
            <div class="flex gap-2 mt-6">
                <button type="submit" class="flex-1 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">Change Password</button>
                <button type="button" onclick="closePasswordModal()" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
const basePath = '<?php echo BASE_PATH; ?>';

function openAddUserModal() {
    document.getElementById('modalTitle').textContent = 'Add User';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('passwordField').style.display = 'block';
    document.getElementById('userPassword').required = true;
    document.getElementById('userModal').classList.remove('hidden');
}

function openEditUserModal(userId) {
    fetch(`${basePath}/dashboard/pages/get-user.php?id=${userId}`)
        .then(response => response.json())
        .then(user => {
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('userId').value = user.id;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userFirstName').value = user.firstName || '';
            document.getElementById('userLastName').value = user.lastName || '';
            document.getElementById('userPhone').value = user.phone || '';
            document.getElementById('userRole').value = user.role;
            document.getElementById('userPlatform').value = user.platform || 'trivemart';
            document.getElementById('passwordField').style.display = 'none';
            document.getElementById('userPassword').required = false;
            document.getElementById('userModal').classList.remove('hidden');
        })
        .catch(error => {
            alert('Error loading user data');
            console.error(error);
        });
}

function closeUserModal() {
    document.getElementById('userModal').classList.add('hidden');
}

function saveUser(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', document.getElementById('userId').value ? 'update' : 'create');
    
    fetch(`${basePath}/includes/user-action.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Error saving user');
        }
    })
    .catch(error => {
        alert('Error saving user');
        console.error(error);
    });
}

function changeUserPassword(userId, email) {
    document.getElementById('passwordUserId').value = userId;
    document.getElementById('passwordUserEmail').textContent = email;
    document.getElementById('passwordForm').reset();
    document.getElementById('currentPassword').value = '';
    document.getElementById('passwordModal').classList.remove('hidden');
}

function closePasswordModal() {
    document.getElementById('passwordModal').classList.add('hidden');
}

function savePassword(e) {
    e.preventDefault();
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (!currentPassword) {
        alert('Current password is required');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        alert('New passwords do not match');
        return;
    }
    
    const formData = new FormData(e.target);
    formData.append('action', 'change_password');
    formData.append('currentPassword', currentPassword);
    
    fetch(`${basePath}/includes/user-action.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Password changed successfully');
            closePasswordModal();
        } else {
            alert(data.message || 'Error changing password');
        }
    })
    .catch(error => {
        alert('Error changing password');
        console.error(error);
    });
}

function deleteUser(userId, email) {
    if (!confirm(`Are you sure you want to delete user: ${email}?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', userId);
    
    fetch(`${basePath}/includes/user-action.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert(data.message || 'Error deleting user');
        }
    })
    .catch(error => {
        alert('Error deleting user');
        console.error(error);
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

