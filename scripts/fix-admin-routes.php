<?php

$basePath = dirname(__DIR__) . '/app/Http/Controllers/Admin';

$replacements = [
    "admin.product.index" => "admin.products.index",
    "admin.product-category.index" => "admin.product-categories.index",
    "admin.modifier-group.index" => "admin.modifier-groups.index",
    "admin.recipe.index" => "admin.recipes.index",
    "admin.warehouse.index" => "admin.warehouses.index",
    "admin.stock-transfer.index" => "admin.stock-transfers.index",
    "admin.stock-count.index" => "admin.stock-counts.index",
    "admin.supplier.index" => "admin.suppliers.index",
    "admin.purchase-order.index" => "admin.purchase-orders.index",
    "admin.purchase-invoice.index" => "admin.purchase-invoices.index",
    "admin.order.index" => "admin.orders.index",
    "admin.invoice.index" => "admin.invoices.index",
    "admin.refund.index" => "admin.refunds.index",
    "admin.restaurant-table.index" => "admin.restaurant-tables.index",
    "admin.table-session.index" => "admin.table-sessions.index",
    "admin.cash-register.index" => "admin.cash-registers.index",
    "admin.cash-register-session.index" => "admin.cash-register-sessions.index",
    "admin.expense.index" => "admin.expenses.index",
    "admin.production-order.index" => "admin.production-orders.index",
    "admin.chart-of-account.index" => "admin.chart-of-accounts.index",
    "admin.journal-entry.index" => "admin.journal-entries.index",
    "admin.user.index" => "admin.users.index",
];

foreach (glob("{$basePath}/*.php") as $file) {
    $content = file_get_contents($file);
    $updated = str_replace(array_keys($replacements), array_values($replacements), $content);
    if ($updated !== $content) {
        file_put_contents($file, $updated);
    }
}

echo "Route names fixed.\n";
