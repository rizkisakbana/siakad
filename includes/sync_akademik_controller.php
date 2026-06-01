<?php

function sync_limit_from_post()
{
    $limit = intval($_POST['limit'] ?? 25);
    if ($limit < 1) return 25;
    if ($limit > 200) return 200;
    return $limit;
}

function sync_set_result_alert($result)
{
    $msg = "Diproses: {$result['total']}, berhasil: {$result['berhasil']}, gagal: {$result['gagal']}.";
    if (!empty($result['errors'])) {
        $msg .= ' Error awal: ' . implode(' | ', array_slice($result['errors'], 0, 3));
    }
    set_alert($result['gagal'] > 0 ? 'warning' : 'success', $msg);
}

function render_sync_form_hint()
{
    ?>
    <div class="mb-4">
        <label class="block text-sm font-semibold text-slate-700 mb-2">Batas data per proses</label>
        <input type="number" name="limit" value="25" min="1" max="200" class="w-32 rounded-xl border border-slate-300 px-4 py-3">
    </div>
    <?php
}
