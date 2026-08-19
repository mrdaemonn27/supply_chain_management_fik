<?php
$multi_filter_id = $multi_filter_id ?? 'adminMultiFilter';
$multi_filter_mode = $multi_filter_mode ?? 'client';
$multi_filter_fields = $multi_filter_fields ?? [];
$multi_filter_rows = !empty($multi_filter_rows) ? array_slice($multi_filter_rows, 0, 4) : [['field' => array_key_first($multi_filter_fields), 'value' => '']];
$multi_filter_action = $multi_filter_action ?? '';
$multi_filter_hidden = $multi_filter_hidden ?? [];
$multi_filter_json = [];
foreach ($multi_filter_fields as $field_key => $field_config) {
    $field_config = is_array($field_config) ? $field_config : ['label' => $field_config];
    $multi_filter_json[$field_key] = [
        'label' => $field_config['label'] ?? $field_key,
        'placeholder' => $field_config['placeholder'] ?? '',
        'type' => $field_config['type'] ?? 'search',
    ];
}
$render_filter_row = static function ($row) use ($multi_filter_fields) {
    $selected_field = (string) ($row['field'] ?? array_key_first($multi_filter_fields));
    $selected_value = (string) ($row['value'] ?? '');
?>
    <div class="admin-multi-filter__row">
        <select class="form-select admin-multi-filter__field" name="filter_field[]" aria-label="Pilih kriteria pencarian">
            <?php foreach ($multi_filter_fields as $field_key => $field_config): $label = is_array($field_config) ? ($field_config['label'] ?? $field_key) : $field_config; ?>
                <option value="<?= html_escape($field_key) ?>" <?= $selected_field === (string) $field_key ? 'selected' : '' ?>><?= html_escape($label) ?></option>
            <?php endforeach; ?>
        </select>
        <input class="form-control admin-multi-filter__value" name="filter_value[]" value="<?= html_escape($selected_value) ?>" autocomplete="off">
        <div class="admin-multi-filter__actions">
            <button type="button" class="admin-multi-filter__action" data-filter-remove aria-label="Hapus kriteria"><i class="bi bi-dash"></i></button>
            <button type="button" class="admin-multi-filter__action admin-multi-filter__action--add" data-filter-add aria-label="Tambah kriteria"><i class="bi bi-plus"></i></button>
        </div>
    </div>
<?php }; ?>
<link rel="stylesheet" href="<?= base_url('assets/css/admin-multi-filter.css') ?>">
<?php if ($multi_filter_mode === 'server'): ?><form method="get" action="<?= html_escape($multi_filter_action) ?>"><?php endif; ?>
<section id="<?= html_escape($multi_filter_id) ?>" class="admin-multi-filter mb-3" data-admin-multi-filter data-mode="<?= html_escape($multi_filter_mode) ?>" data-fields='<?= html_escape(json_encode($multi_filter_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'>
    <div class="admin-multi-filter__header">
        <h2 class="admin-multi-filter__title"><i class="bi bi-funnel"></i> Filter pencarian</h2>
    </div>
    <div class="admin-multi-filter__rows">
        <?php foreach ($multi_filter_rows as $filter_row) $render_filter_row($filter_row); ?>
    </div>
    <template data-filter-template><?php $render_filter_row(['field' => array_key_first($multi_filter_fields), 'value' => '']); ?></template>
    <?php foreach ($multi_filter_hidden as $hidden_name => $hidden_value): ?><input type="hidden" name="<?= html_escape($hidden_name) ?>" value="<?= html_escape($hidden_value) ?>"><?php endforeach; ?>
</section>
<?php if ($multi_filter_mode === 'server'): ?></form><?php endif; ?>
<script src="<?= base_url('assets/js/admin-multi-filter.js') ?>"></script>
