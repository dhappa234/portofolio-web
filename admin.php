<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'portofolio';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// CRUD logic
if (isset($_POST['add_skill'])) {
    $name = trim($_POST['skill_name']);
    $percent = intval($_POST['skill_percent']);
    if ($name && $percent >= 0 && $percent <= 100) {
        $stmt = $conn->prepare("INSERT INTO skills (name, percentage) VALUES (?, ?)");
        $stmt->bind_param('si', $name, $percent);
        $stmt->execute();
        $stmt->close();
    }
}
if (isset($_POST['delete_skill'])) {
    $id = intval($_POST['skill_id']);
    $conn->query("DELETE FROM skills WHERE id=$id");
}

if (isset($_POST['add_service'])) {
    $title = trim($_POST['service_title']);
    $desc = trim($_POST['service_desc']);
    $icon = trim($_POST['service_icon']);
    if ($title && $desc) {
        $stmt = $conn->prepare("INSERT INTO services (title, description, icon) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $title, $desc, $icon);
        $stmt->execute();
        $stmt->close();
    }
}
if (isset($_POST['delete_service'])) {
    $id = intval($_POST['service_id']);
    $conn->query("DELETE FROM services WHERE id=$id");
}

if (isset($_POST['add_project'])) {
    $title = trim($_POST['project_title']);
    $desc = trim($_POST['project_desc']);
    $link = trim($_POST['project_link']);

    $image = '';
    if (isset($_FILES['project_image']) && $_FILES['project_image']['error'] === UPLOAD_ERR_OK) {
        $img_name = $_FILES['project_image']['name'];
        $img_tmp = $_FILES['project_image']['tmp_name'];
        $img_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($img_ext, $allowed)) {
            $new_name = uniqid('img_', true) . '.' . $img_ext;
            $target = __DIR__ . '/assets/img/' . $new_name;
            if (move_uploaded_file($img_tmp, $target)) {
                $image = $new_name;
            }
        }
    }

    if ($title && $desc && $image) {
        $stmt = $conn->prepare("INSERT INTO projects (title, description, link, image) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $title, $desc, $link, $image);
        $stmt->execute();
        $stmt->close();
    }
}

if (isset($_POST['delete_project'])) {
    $id = intval($_POST['project_id']);
    $stmt = $conn->prepare("SELECT image FROM projects WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($image_filename);
    $stmt->fetch();
    $stmt->close();

    if ($image_filename && file_exists(__DIR__ . "/assets/img/" . $image_filename)) {
        unlink(__DIR__ . "/assets/img/" . $image_filename);
    }
    $conn->query("DELETE FROM projects WHERE id=$id");
}

$skills = $conn->query("SELECT * FROM skills ORDER BY id DESC");
$services = $conn->query("SELECT * FROM services ORDER BY id DESC");
$projects = $conn->query("SELECT * FROM projects ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Panel - Portfolio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(to bottom right, #eff6ff, #dbeafe);
            min-height: 100vh;
        }

        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #1e40af;
        }

        .glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            border-radius: 1.25rem;
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-100 via-cyan-100 to-white min-h-screen">
    <!-- Header -->
    <header class="sticky top-0 z-30 w-full bg-gradient-to-r from-blue-700 to-cyan-500 shadow-lg">
        <div class="flex items-center justify-between px-8 py-5">
            <h1 class="text-2xl md:text-3xl font-extrabold text-white tracking-wide flex items-center gap-3 drop-shadow-lg">
                    <i class="bx bx-cog bx-spin text-3xl md:text-4xl"></i> Admin Panel
            </h1>
            <div class="flex items-center gap-6">
                   <a href="index.php" class="text-white hover:underline font-bold text-lg flex items-center gap-2">
                <i class="bx bx-home text-2xl"></i> Lihat Website
            </a>
                <form method="post" action="logout.php" class="inline-block">
                    <button type="submit" class="text-white hover:underline font-semibold flex items-center gap-2 transition-all duration-300 hover:scale-105">
                        <i class="bx bx-log-out text-xl"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="max-w-4xl mx-auto glass p-6 sm:p-10 mt-10 mb-10">
            <!-- Skills Section -->
            <section class="mb-12">
                <h2 class="section-title"><i class='bx bx-bar-chart-alt-2'></i> Skills</h2>
                <form method="post" class="grid sm:grid-cols-3 gap-4 mb-6">
                    <input type="text" name="skill_name" placeholder="Nama Skill" required class="border p-2 rounded-lg">
                    <input type="number" name="skill_percent" placeholder="Persentase" min="0" max="100" required class="border p-2 rounded-lg">
                    <button type="submit" name="add_skill" class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-4 py-2">Tambah Skill</button>
                </form>
                <div class="overflow-x-auto rounded-xl shadow">
                    <table class="min-w-full bg-white border border-gray-200 rounded-xl">
                        <thead>
                            <tr class="bg-gradient-to-r from-blue-100 to-cyan-100">
                                <th class="py-3 px-4 text-left font-bold text-blue-700">Skill</th>
                                <th class="py-3 px-4 text-left font-bold text-blue-700">Persentase</th>
                                <th class="py-3 px-4 text-center font-bold text-blue-700">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 0;
                            while ($row = $skills->fetch_assoc()): $i++; ?>
                                <tr class="<?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-blue-50 transition">
                                    <td class="py-3 px-4 flex items-center gap-2">
                                        <i class="bx bx-check-circle text-blue-500 text-xl"></i>
                                        <span class="font-semibold"><?= htmlspecialchars($row['name']) ?></span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="bg-blue-100 text-blue-700 text-sm px-3 py-1 rounded-full font-medium shadow-sm"><?= $row['percentage'] ?>%</span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <form method="post" class="inline-block">
                                            <input type="hidden" name="skill_id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="delete_skill" class="inline-flex items-center gap-1 text-red-600 border border-red-500 px-3 py-1.5 rounded-lg font-semibold hover:bg-red-50 transition">
                                                <i class="bx bx-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Services Section -->
            <section class="mb-12">
                <h2 class="section-title"><i class='bx bx-briefcase-alt'></i> Services</h2>
                <form method="post" class="grid gap-4 mb-6">
                    <input type="text" name="service_title" placeholder="Judul Layanan" required class="border p-2 rounded-lg">
                    <input type="text" name="service_icon" placeholder="Icon Boxicons" class="border p-2 rounded-lg">
                    <textarea name="service_desc" placeholder="Deskripsi" required class="border p-2 rounded-lg"></textarea>
                    <button type="submit" name="add_service" class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-4 py-2">Tambah Layanan</button>
                </form>
                <div class="overflow-x-auto rounded-xl shadow">
                    <table class="min-w-full bg-white border border-gray-200 rounded-xl">
                        <thead>
                            <tr class="bg-gradient-to-r from-cyan-100 to-blue-100">
                                <th class="py-3 px-4 text-left font-bold text-cyan-700">Layanan</th>
                                <th class="py-3 px-4 text-left font-bold text-cyan-700">Deskripsi</th>
                                <th class="py-3 px-4 text-center font-bold text-cyan-700">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 0;
                            while ($row = $services->fetch_assoc()): $i++; ?>
                                <tr class="<?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-cyan-50 transition">
                                    <td class="py-3 px-4 flex items-center gap-2">
                                        <i class="bx <?= htmlspecialchars($row['icon']) ?> text-cyan-500 text-xl"></i>
                                        <span class="font-semibold"><?= htmlspecialchars($row['title']) ?></span>
                                    </td>
                                    <td class="py-3 px-4"><?= htmlspecialchars($row['description']) ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <form method="post" class="inline-block">
                                            <input type="hidden" name="service_id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="delete_service" class="inline-flex items-center gap-1 text-red-600 border border-red-500 px-3 py-1.5 rounded-lg font-semibold hover:bg-red-50 transition">
                                                <i class="bx bx-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Projects Section -->
            <section>
                <h2 class="section-title"><i class='bx bx-folder'></i> Projects</h2>
                <form method="post" enctype="multipart/form-data" class="grid gap-4 mb-6">
                    <input type="text" name="project_title" placeholder="Judul Proyek" required class="border p-2 rounded-lg">
                    <input type="file" name="project_image" accept="image/*" required class="border p-2 rounded-lg">
                    <input type="text" name="project_link" placeholder="Link Proyek (Opsional)" class="border p-2 rounded-lg">
                    <textarea name="project_desc" placeholder="Deskripsi" required class="border p-2 rounded-lg"></textarea>
                    <button type="submit" name="add_project" class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-4 py-2">Tambah Proyek</button>
                </form>
                <div class="overflow-x-auto rounded-xl shadow">
                    <table class="min-w-full bg-white border border-gray-200 rounded-xl">
                        <thead>
                            <tr class="bg-gradient-to-r from-purple-100 to-blue-100">
                                <th class="py-3 px-4 text-left font-bold text-purple-700">Gambar</th>
                                <th class="py-3 px-4 text-left font-bold text-purple-700">Judul</th>
                                <th class="py-3 px-4 text-left font-bold text-purple-700">Deskripsi</th>
                                <th class="py-3 px-4 text-left font-bold text-purple-700">Link</th>
                                <th class="py-3 px-4 text-center font-bold text-purple-700">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 0;
                            while ($row = $projects->fetch_assoc()): $i++; ?>
                                <tr class="<?= $i % 2 == 0 ? 'bg-gray-50' : 'bg-white' ?> hover:bg-purple-50 transition">
                                    <td class="py-3 px-4">
                                        <img src="assets/img/<?= htmlspecialchars($row['image']) ?>"
                                            onerror="this.onerror=null;this.src='https://placehold.co/60x60/e2e8f0/64748b?text=No+Image';"
                                            alt="<?= htmlspecialchars($row['title']) ?>"
                                            class="w-14 h-14 object-cover rounded shadow border border-gray-200">
                                    </td>
                                    <td class="py-3 px-4 font-semibold"><?= htmlspecialchars($row['title']) ?></td>
                                    <td class="py-3 px-4"><?= htmlspecialchars($row['description']) ?></td>
                                    <td class="py-3 px-4">
                                        <?php if ($row['link']): ?>
                                            <a href="<?= htmlspecialchars($row['link']) ?>" target="_blank" class="text-blue-600 hover:underline flex items-center gap-1">
                                                <i class="bx bx-link-external"></i> Link
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <form method="post" class="inline-block">
                                            <input type="hidden" name="project_id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="delete_project" class="inline-flex items-center gap-1 text-red-600 border border-red-500 px-3 py-1.5 rounded-lg font-semibold hover:bg-red-50 transition">
                                                <i class="bx bx-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</body>

</html>
<?php $conn->close(); ?>