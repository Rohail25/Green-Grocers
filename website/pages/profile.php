<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

requireAuth();
$currentUser = getCurrentUser();
$userProfile = getUserProfile($currentUser['id']);

$pageTitle = 'My Profile';
$message = $_SESSION['profile_message'] ?? '';
$success = $_SESSION['profile_success'] ?? false;
unset($_SESSION['profile_message'], $_SESSION['profile_success']);

$addresses = $userProfile['addresses'] ?? [];
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="min-h-screen bg-gray-100 p-6 md:p-8 mt-24">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-800 mb-6" style="font-family: 'Arial', sans-serif;">My Profile</h1>
        
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $success ? 'bg-green-100 text-green-700 border border-green-400' : 'bg-red-100 text-red-700 border border-red-400'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Change Password Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4" style="font-family: 'Arial', sans-serif;">Change Password</h2>
                <form id="passwordForm" method="POST" action="<?php echo BASE_PATH; ?>/includes/profile-action.php" class="space-y-4">
                    <input type="hidden" name="action" value="update_password">
                    <input type="hidden" name="ajax" value="1">
                    
                    <div>
                        <label class="block text-sm font-medium mb-2 text-gray-700" style="font-family: 'Arial', sans-serif;">Current Password</label>
                        <input type="password" name="currentPassword" required class="w-full border border-gray-300 px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="font-family: 'Arial', sans-serif;" />
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2 text-gray-700" style="font-family: 'Arial', sans-serif;">New Password</label>
                        <input type="password" name="newPassword" required minlength="6" class="w-full border border-gray-300 px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="font-family: 'Arial', sans-serif;" />
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2 text-gray-700" style="font-family: 'Arial', sans-serif;">Confirm New Password</label>
                        <input type="password" name="confirmPassword" required minlength="6" class="w-full border border-gray-300 px-4 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="font-family: 'Arial', sans-serif;" />
                    </div>
                    
                    <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 font-medium" style="font-family: 'Arial', sans-serif;">Update Password</button>
                </form>
            </div>
            
            <!-- Address Management Section -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800" style="font-family: 'Arial', sans-serif;">My Addresses</h2>
                    <button onclick="openAddAddressModal()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm font-medium" style="font-family: 'Arial', sans-serif;">Add Address</button>
                </div>
                
                <div class="space-y-4" id="addresses-list">
                    <?php if (empty($addresses)): ?>
                        <p class="text-gray-500 text-sm" style="font-family: 'Arial', sans-serif;">No addresses saved yet.</p>
                    <?php else: ?>
                        <?php foreach ($addresses as $index => $address): ?>
                            <div class="border border-gray-300 rounded-lg p-4 <?php echo ($address['isDefault'] ?? false) ? 'bg-green-50 border-green-500' : ''; ?>">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <?php if ($address['isDefault'] ?? false): ?>
                                            <span class="inline-block bg-green-600 text-white text-xs px-2 py-1 rounded mb-2">Default</span>
                                        <?php endif; ?>
                                        <p class="text-sm text-gray-600 mb-1" style="font-family: 'Arial', sans-serif;">
                                            <?php echo htmlspecialchars($address['streetAddressLine1'] ?? ''); ?><?php echo !empty($address['streetAddressLine2']) ? ', ' . htmlspecialchars($address['streetAddressLine2']) : ''; ?>
                                        </p>
                                        <p class="text-sm text-gray-600 mb-1" style="font-family: 'Arial', sans-serif;">
                                            <?php echo htmlspecialchars($address['suburb'] ?? ''); ?><?php echo !empty($address['state']) ? ', ' . htmlspecialchars($address['state']) : ''; ?> <?php echo htmlspecialchars($address['postalCode'] ?? ''); ?>
                                        </p>
                                    </div>
                                    <div class="flex gap-2 ml-4">
                                        <?php if (!($address['isDefault'] ?? false)): ?>
                                            <button onclick="setDefaultAddress(<?php echo $index; ?>)" class="text-green-600 hover:text-green-700 text-sm" style="font-family: 'Arial', sans-serif;">Set Default</button>
                                        <?php endif; ?>
                                        <button onclick="editAddress(<?php echo $index; ?>)" class="text-blue-600 hover:text-blue-700 text-sm" style="font-family: 'Arial', sans-serif;">Edit</button>
                                        <button onclick="deleteAddress(<?php echo $index; ?>)" class="text-red-600 hover:text-red-700 text-sm" style="font-family: 'Arial', sans-serif;">Delete</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Address Modal -->
<div id="addressModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-800" id="modalTitle" style="font-family: 'Arial', sans-serif;">Address</h3>
            <button onclick="closeAddressModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="addressForm" method="POST" action="<?php echo BASE_PATH; ?>/includes/profile-action.php" class="space-y-5">
            <input type="hidden" name="action" id="addressAction" value="add_address">
            <input type="hidden" name="addressIndex" id="addressIndex" value="">
            <input type="hidden" name="ajax" value="1">
            
            <div>
                <label class="block text-sm font-medium mb-2 text-gray-800" style="font-family: 'Arial', sans-serif;">Street Address Line 1:</label>
                <input type="text" name="streetAddressLine1" id="streetAddressLine1" required class="w-full border border-gray-300 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="font-family: 'Arial', sans-serif;" placeholder="Enter street address line 1" />
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2 text-gray-800" style="font-family: 'Arial', sans-serif;">Street Address Line 2:</label>
                <input type="text" name="streetAddressLine2" id="streetAddressLine2" class="w-full border border-gray-300 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="font-family: 'Arial', sans-serif;" placeholder="Enter street address line 2 (optional)" />
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2 text-gray-800" style="font-family: 'Arial', sans-serif;">Suburb:</label>
                <input type="text" name="suburb" id="suburb" required class="w-full border border-gray-300 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="font-family: 'Arial', sans-serif;" placeholder="Enter suburb" />
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2 text-gray-800" style="font-family: 'Arial', sans-serif;">State:</label>
                <select name="state" id="state" required class="w-full border border-gray-300 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="font-family: 'Arial', sans-serif;">
                    <option value="">Select State</option>
                    <option value="New South Wales">New South Wales</option>
                    <option value="Victoria">Victoria</option>
                    <option value="Queensland">Queensland</option>
                    <option value="South Australia">South Australia</option>
                    <option value="Western Australia">Western Australia</option>
                    <option value="Tasmania">Tasmania</option>
                    <option value="Australian Capital Territory">Australian Capital Territory</option>
                    <option value="Northern Territory">Northern Territory</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2 text-gray-800" style="font-family: 'Arial', sans-serif;">Postal Code:</label>
                <input type="text" name="postalCode" id="postalCode" required pattern="[0-9]{4}" maxlength="4" class="w-full border border-gray-300 px-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" style="font-family: 'Arial', sans-serif;" placeholder="Enter postal code" />
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeAddressModal()" class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-50 font-medium" style="font-family: 'Arial', sans-serif;">Cancel</button>
                <button type="submit" class="flex-1 bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 font-medium" style="font-family: 'Arial', sans-serif;">Save Address</button>
            </div>
        </form>
    </div>
</div>

<script>
const basePath = '<?php echo BASE_PATH; ?>';
const addresses = <?php echo json_encode($addresses); ?>;

function openAddAddressModal() {
    document.getElementById('modalTitle').textContent = 'Address';
    document.getElementById('addressAction').value = 'add_address';
    document.getElementById('addressIndex').value = '';
    document.getElementById('addressForm').reset();
    document.getElementById('addressModal').classList.remove('hidden');
}

function closeAddressModal() {
    document.getElementById('addressModal').classList.add('hidden');
    document.getElementById('addressForm').reset();
}

function editAddress(index) {
    const address = addresses[index];
    if (!address) return;
    
    document.getElementById('modalTitle').textContent = 'Address';
    document.getElementById('addressAction').value = 'update_address';
    document.getElementById('addressIndex').value = index;
    document.getElementById('streetAddressLine1').value = address.streetAddressLine1 || '';
    document.getElementById('streetAddressLine2').value = address.streetAddressLine2 || '';
    document.getElementById('suburb').value = address.suburb || '';
    document.getElementById('state').value = address.state || '';
    document.getElementById('postalCode').value = address.postalCode || '';
    document.getElementById('addressModal').classList.remove('hidden');
}

function deleteAddress(index) {
    if (!confirm('Are you sure you want to delete this address?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_address');
    formData.append('addressIndex', index);
    formData.append('ajax', '1');
    
    fetch(basePath + '/includes/profile-action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to delete address');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}

function setDefaultAddress(index) {
    const formData = new FormData();
    formData.append('action', 'set_default_address');
    formData.append('addressIndex', index);
    formData.append('ajax', '1');
    
    fetch(basePath + '/includes/profile-action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to set default address');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}

// Handle address form submission
document.getElementById('addressForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(basePath + '/includes/profile-action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeAddressModal();
            location.reload();
        } else {
            alert(data.message || 'Failed to save address');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
});

// Handle password form submission
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(basePath + '/includes/profile-action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Password updated successfully');
            this.reset();
        } else {
            alert(data.message || 'Failed to update password');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>

