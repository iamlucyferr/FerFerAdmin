<?php
session_start();
if (!isset($_SESSION['admin_id'])) { 
    header("Location: login.php"); 
    exit; 
}

require 'db.php';

// --- CONFIGURATION ---
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// --- ACTION: TOGGLE STATUS ---
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn->query("UPDATE plugin_licenses SET is_active = NOT is_active WHERE id = $id");
    header("Location: index.php?page=$page&search=" . urlencode($search)); 
    exit;
}

// --- ACTION: DELETE ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM plugin_licenses WHERE id = $id");
    header("Location: index.php?page=$page&search=" . urlencode($search)); 
    exit;
}

// --- ACTION: GENERATE NEW KEY ---
if (isset($_POST['add'])) {
    $owner = trim($_POST['owner']);
    $expiry = $_POST['expiry'];
    // Format: FER-XXXX-XXXX (Higher entropy)
    $key = "FER-" . strtoupper(bin2hex(random_bytes(4))) . "-" . strtoupper(bin2hex(random_bytes(4)));
    
    $stmt = $conn->prepare("INSERT INTO plugin_licenses (license_key, owner_name, expiry_date, is_active) VALUES (?, ?, ?, 1)");
    $stmt->bind_param("sss", $key, $owner, $expiry);
    $stmt->execute();
    header("Location: index.php"); 
    exit;
}

// --- DATA FETCHING (Search & Stats) ---
$search_param = "%$search%";
$count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM plugin_licenses WHERE owner_name LIKE ? OR license_key LIKE ?");
$count_stmt->bind_param("ss", $search_param, $search_param);
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['count'];
$total_pages = ceil($total_rows / $limit);

$data_stmt = $conn->prepare("SELECT * FROM plugin_licenses WHERE owner_name LIKE ? OR license_key LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?");
$data_stmt->bind_param("ssii", $search_param, $search_param, $limit, $offset);
$data_stmt->execute();
$licenses = $data_stmt->get_result();

// Stats for Header
$total_all = $conn->query("SELECT COUNT(*) as count FROM plugin_licenses")->fetch_assoc()['count'];
$active_all = $conn->query("SELECT COUNT(*) as count FROM plugin_licenses WHERE is_active = 1 AND expiry_date > NOW()")->fetch_assoc()['count'];
$expired_all = $conn->query("SELECT COUNT(*) as count FROM plugin_licenses WHERE expiry_date <= NOW()")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FerFer | License Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap');
        body { background: #0f172a; color: white; font-family: 'Plus Jakarta Sans', sans-serif; scroll-behavior: smooth; }
        .glass-card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px; }
        .input-style { background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(51, 65, 85, 1); padding: 0.75rem; border-radius: 0.75rem; outline: none; transition: all 0.2s; }
        .input-style:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2); }
    </style>
</head>
<body class="p-6 md:p-12">
    <div class="max-w-7xl mx-auto">
        
        <div class="flex flex-col md:flex-row justify-between items-center mb-10 gap-6">
            <h1 class="text-2xl font-bold text-blue-400">FerFer <span class="text-slate-500 font-medium text-xl">Manager 3.0</span></h1>
            
            <form method="GET" class="relative w-full md:w-96">
                <i data-lucide="search" class="absolute left-4 top-3 w-4 h-4 text-slate-500"></i>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search Client or Key..." class="w-full input-style pl-11 pr-4 py-2.5 text-sm">
            </form>

            <a href="logout.php" class="text-slate-500 hover:text-red-400 transition font-bold text-xs uppercase tracking-widest flex items-center gap-2">
                <i data-lucide="log-out" class="w-4 h-4"></i> Logout
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="glass-card p-6 border-l-4 border-blue-500">
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Total Database</p>
                <h3 class="text-3xl font-bold mt-1"><?php echo $total_all; ?></h3>
            </div>
            <div class="glass-card p-6 border-l-4 border-green-500">
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Active Licenses</p>
                <h3 class="text-3xl font-bold mt-1 text-green-400"><?php echo $active_all; ?></h3>
            </div>
            <div class="glass-card p-6 border-l-4 border-red-500">
                <p class="text-slate-500 text-[10px] font-bold uppercase tracking-wider">Expired / Revoked</p>
                <h3 class="text-3xl font-bold mt-1 text-red-400"><?php echo $expired_all; ?></h3>
            </div>
        </div>

        <div class="glass-card p-8 mb-10">
            <h2 class="text-sm font-bold text-slate-400 mb-4 uppercase tracking-widest">Generate New License</h2>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input name="owner" placeholder="Customer Name" class="input-style" required>
                <input type="datetime-local" name="expiry" class="input-style text-slate-400" required>
                <button name="add" class="md:col-span-2 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl transition py-3 shadow-lg shadow-blue-900/20 uppercase text-xs tracking-widest">
                    Create Production Key
                </button>
            </form>
        </div>

        <div class="glass-card overflow-x-auto">
            <table class="w-full text-left min-w-[800px]">
                <thead class="bg-white/5 text-slate-400 text-[10px] uppercase tracking-wider font-bold">
                    <tr>
                        <th class="p-6">License Key</th>
                        <th class="p-6">Owner</th>
                        <th class="p-6">Expiry</th>
                        <th class="p-6">Status</th>
                        <th class="p-6 text-right">Control</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php if ($licenses->num_rows > 0): ?>
                        <?php while($row = $licenses->fetch_assoc()): 
                            $is_expired = strtotime($row['expiry_date']) < time();
                        ?>
                        <tr class="hover:bg-white/5 transition group">
                            <td class="p-6">
                                <div class="flex items-center gap-3">
                                    <code class="font-mono text-blue-400 font-bold text-sm"><?php echo $row['license_key']; ?></code>
                                    <button onclick="copyKey('<?php echo $row['license_key']; ?>')" class="opacity-0 group-hover:opacity-100 transition text-slate-500 hover:text-white">
                                        <i data-lucide="copy" class="w-3 h-3"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="p-6 text-slate-300 font-medium"><?php echo htmlspecialchars($row['owner_name']); ?></td>
                            <td class="p-6 text-sm <?php echo $is_expired ? 'text-red-400/60' : 'text-slate-400'; ?>">
                                <?php echo date('M d, Y', strtotime($row['expiry_date'])); ?>
                                <span class="block text-[10px] text-slate-600"><?php echo date('H:i', strtotime($row['expiry_date'])); ?></span>
                            </td>
                            <td class="p-6">
                                <?php if ($row['is_active'] && !$is_expired): ?>
                                    <span class="px-3 py-1 text-[9px] font-black rounded-full bg-green-500/10 text-green-400 ring-1 ring-green-500/20">ACTIVE</span>
                                <?php elseif ($is_expired): ?>
                                    <span class="px-3 py-1 text-[9px] font-black rounded-full bg-orange-500/10 text-orange-400 ring-1 ring-orange-500/20">EXPIRED</span>
                                <?php else: ?>
                                    <span class="px-3 py-1 text-[9px] font-black rounded-full bg-red-500/10 text-red-400 ring-1 ring-red-500/20">REVOKED</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-6 text-right space-x-2">
                                <a href="?toggle=<?php echo $row['id']; ?>&page=<?php echo $page; ?>&search=<?php echo urlencode($search); ?>" class="text-slate-500 hover:text-blue-400 transition inline-block p-1">
                                    <i data-lucide="power" class="w-4 h-4"></i>
                                </a>
                                <a href="?delete=<?php echo $row['id']; ?>&page=<?php echo $page; ?>&search=<?php echo urlencode($search); ?>" onclick="return confirm('Permanent delete client?')" class="text-slate-700 hover:text-red-500 transition inline-block p-1">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="p-12 text-center text-slate-500 italic">No licenses found matching your criteria.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="flex justify-center mt-12 gap-2">
            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                   class="px-5 py-2.5 rounded-xl font-bold text-xs transition <?php echo $page == $i ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/40' : 'bg-slate-800/50 text-slate-500 hover:bg-slate-700 hover:text-white'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        lucide.createIcons();
        function copyKey(text) {
            navigator.clipboard.writeText(text);
            alert("Key copied to clipboard!");
        }
    </script>
</body>
</html>