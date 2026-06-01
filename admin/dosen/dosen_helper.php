<?php
function dosen_db_value($conn, $value)
{
    $value = trim((string) ($value ?? ''));

    if ($value === '') {
        return "NULL";
    }

    return "'" . mysqli_real_escape_string($conn, $value) . "'";
}

function dosen_ref_options($conn, $jenis_ref)
{
    $jenis_ref = mysqli_real_escape_string($conn, $jenis_ref);
    $items = [];
    $q = mysqli_query($conn, "
        SELECT id_feeder, nama_ref
        FROM ref_pddikti
        WHERE jenis_ref = '$jenis_ref'
        AND status = 'aktif'
        ORDER BY nama_ref ASC
    ");

    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $items[] = $row;
        }
    }

    return $items;
}

function dosen_render_ref_select($name, $items, $selected, $placeholder)
{
    ?>
    <select name="<?= htmlspecialchars($name); ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-blue-600 outline-none">
        <option value=""><?= htmlspecialchars($placeholder); ?></option>
        <?php foreach ($items as $item): ?>
            <option value="<?= htmlspecialchars($item['id_feeder']); ?>" <?= (string) $selected === (string) $item['id_feeder'] ? 'selected' : ''; ?>>
                <?= htmlspecialchars($item['nama_ref']); ?> (<?= htmlspecialchars($item['id_feeder']); ?>)
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

function dosen_ref_name($items, $id_feeder)
{
    foreach ($items as $item) {
        if ((string) $item['id_feeder'] === (string) $id_feeder) {
            return $item['nama_ref'];
        }
    }

    return '';
}

function dosen_prodi_feeder($conn, $id_prodi)
{
    $id_prodi = (int) $id_prodi;
    if ($id_prodi <= 0) {
        return '';
    }

    $q = mysqli_query($conn, "
        SELECT COALESCE(NULLIF(id_prodi_feeder, ''), NULLIF(id_feeder, '')) AS id_feeder
        FROM prodi
        WHERE id_prodi = '$id_prodi'
        LIMIT 1
    ");

    if ($q && mysqli_num_rows($q) > 0) {
        $row = mysqli_fetch_assoc($q);
        return $row['id_feeder'] ?? '';
    }

    return '';
}
?>
