<?php

/**
 * One-shot: map hardcoded toast messages → __() keys + update lang JSON.
 * Run: php tools/translate_toasts.php
 */

$root = dirname(__DIR__);

$catalog = [
    'Branch selection is required.' => ['toast.branch_required', 'اختيار الفرع مطلوب.', 'Branch selection is required.'],
    'Forbidden.' => ['toast.forbidden', 'غير مسموح.', 'Forbidden.'],
    'Unauthenticated.' => ['toast.unauthenticated', 'غير مسجّل الدخول.', 'Unauthenticated.'],
    'الطلب لا يحتوي على منتجات.' => ['toast.order_empty', 'الطلب لا يحتوي على منتجات.', 'The order has no products.'],
    'انتهت صلاحية رمز التحقق. أعد إرسال الطلب.' => ['toast.otp_expired', 'انتهت صلاحية رمز التحقق. أعد إرسال الطلب.', 'Verification code expired. Please resubmit your order.'],
    'تعذّر حذف الصفحة.' => ['toast.page_delete_failed', 'تعذّر حذف الصفحة.', 'Could not delete the page.'],
    'تم إرسال طلبك. بانتظار موافقة الكاشير قبل إرساله للمطبخ.' => ['toast.table_order_sent', 'تم إرسال طلبك. بانتظار موافقة الكاشير قبل إرساله للمطبخ.', 'Order sent. Waiting for cashier approval before kitchen.'],
    'تم إضافة الحساب بنجاح.' => ['toast.account_created', 'تم إضافة الحساب بنجاح.', 'Account created successfully.'],
    'تم إضافة الدور بنجاح.' => ['toast.role_created', 'تم إضافة الدور بنجاح.', 'Role created successfully.'],
    'تم إضافة الصندوق بنجاح.' => ['toast.cash_register_created', 'تم إضافة الصندوق بنجاح.', 'Cash register created successfully.'],
    'تم إضافة الطبق بنجاح.' => ['toast.product_created', 'تم إضافة الطبق بنجاح.', 'Product created successfully.'],
    'تم إضافة المادة بنجاح.' => ['toast.material_created', 'تم إضافة المادة بنجاح.', 'Material created successfully.'],
    'تم إضافة المستخدم بنجاح.' => ['toast.user_created', 'تم إضافة المستخدم بنجاح.', 'User created successfully.'],
    'تم إضافة المورد بنجاح.' => ['toast.supplier_created', 'تم إضافة المورد بنجاح.', 'Supplier created successfully.'],
    'تم إغلاق جلسة الصندوق.' => ['toast.session_closed', 'تم إغلاق جلسة الصندوق.', 'Cash register session closed.'],
    'تم إنشاء التحويل. أضف المواد والكميات.' => ['toast.transfer_created', 'تم إنشاء التحويل. أضف المواد والكميات.', 'Transfer created. Add items and quantities.'],
    'تم إنشاء الجلسة بنجاح.' => ['toast.table_session_created', 'تم إنشاء الجلسة بنجاح.', 'Table session created successfully.'],
    'تم إنشاء الطاولة.' => ['toast.table_created', 'تم إنشاء الطاولة.', 'Table created.'],
    'تم إنشاء الطلب بنجاح.' => ['toast.order_created', 'تم إنشاء الطلب بنجاح.', 'Order created successfully.'],
    'تم إنشاء الفاتورة بنجاح.' => ['toast.invoice_created', 'تم إنشاء الفاتورة بنجاح.', 'Invoice created successfully.'],
    'تم إنشاء القسم بنجاح.' => ['toast.category_created', 'تم إنشاء القسم بنجاح.', 'Category created successfully.'],
    'تم إنشاء المستودع بنجاح.' => ['toast.warehouse_created', 'تم إنشاء المستودع بنجاح.', 'Warehouse created successfully.'],
    'تم إنشاء فاتورة الشراء. أضف المواد والكميات.' => ['toast.purchase_invoice_created', 'تم إنشاء فاتورة الشراء. أضف المواد والكميات.', 'Purchase invoice created. Add items and quantities.'],
    'تم استلام الشراء وإضافة المواد للمخزون.' => ['toast.purchase_received', 'تم استلام الشراء وإضافة المواد للمخزون.', 'Purchase received and stock updated.'],
    'تم اعتماد الجرد وتصحيح المخزون.' => ['toast.stock_count_approved', 'تم اعتماد الجرد وتصحيح المخزون.', 'Stock count approved and inventory corrected.'],
    'تم بدء الجرد. أدخل الكميات الفعلية.' => ['toast.stock_count_started', 'تم بدء الجرد. أدخل الكميات الفعلية.', 'Stock count started. Enter actual quantities.'],
    'تم تأكيد طلبك بنجاح!' => ['toast.order_confirmed', 'تم تأكيد طلبك بنجاح!', 'Your order was confirmed successfully!'],
    'تم تحديث الجلسة بنجاح.' => ['toast.table_session_updated', 'تم تحديث الجلسة بنجاح.', 'Session updated successfully.'],
    'تم تحديث الحساب.' => ['toast.account_updated', 'تم تحديث الحساب.', 'Account updated.'],
    'تم تحديث الدور.' => ['toast.role_updated', 'تم تحديث الدور.', 'Role updated.'],
    'تم تحديث الصندوق.' => ['toast.cash_register_updated', 'تم تحديث الصندوق.', 'Cash register updated.'],
    'تم تحديث الطاولة.' => ['toast.table_updated', 'تم تحديث الطاولة.', 'Table updated.'],
    'تم تحديث الطبق بنجاح.' => ['toast.product_updated', 'تم تحديث الطبق بنجاح.', 'Product updated successfully.'],
    'تم تحديث الطلب بنجاح.' => ['toast.order_updated', 'تم تحديث الطلب بنجاح.', 'Order updated successfully.'],
    'تم تحديث العملة.' => ['toast.currency_updated', 'تم تحديث العملة.', 'Currency updated.'],
    'تم تحديث الفاتورة بنجاح.' => ['toast.invoice_updated', 'تم تحديث الفاتورة بنجاح.', 'Invoice updated successfully.'],
    'تم تحديث القسم بنجاح.' => ['toast.category_updated', 'تم تحديث القسم بنجاح.', 'Category updated successfully.'],
    'تم تحديث القيد.' => ['toast.journal_updated', 'تم تحديث القيد.', 'Journal entry updated.'],
    'تم تحديث المادة بنجاح.' => ['toast.material_updated', 'تم تحديث المادة بنجاح.', 'Material updated successfully.'],
    'تم تحديث المستودع.' => ['toast.warehouse_updated', 'تم تحديث المستودع.', 'Warehouse updated.'],
    'تم تحديث المصروف.' => ['toast.expense_updated', 'تم تحديث المصروف.', 'Expense updated.'],
    'تم تحديث الملف الشخصي' => ['toast.profile_updated', 'تم تحديث الملف الشخصي', 'Profile updated'],
    'تم تحديث الموقع' => ['toast.address_updated', 'تم تحديث الموقع', 'Address updated'],
    'تم تحديث بيانات المستخدم.' => ['toast.user_updated', 'تم تحديث بيانات المستخدم.', 'User updated.'],
    'تم تحديث بيانات المورد.' => ['toast.supplier_updated', 'تم تحديث بيانات المورد.', 'Supplier updated.'],
    'تم تحديث حالة الطلب.' => ['toast.order_status_updated', 'تم تحديث حالة الطلب.', 'Order status updated.'],
    'تم تسجيل القيد بنجاح.' => ['toast.journal_created', 'تم تسجيل القيد بنجاح.', 'Journal entry recorded successfully.'],
    'تم تسجيل المصروف بنجاح.' => ['toast.expense_created', 'تم تسجيل المصروف بنجاح.', 'Expense recorded successfully.'],
    'تم تسجيل الهدر وخصم المخزون تلقائياً.' => ['toast.wastage_recorded', 'تم تسجيل الهدر وخصم المخزون تلقائياً.', 'Wastage recorded and stock deducted.'],
    'تم تعليم الرسالة كغير مقروءة.' => ['toast.message_unread', 'تم تعليم الرسالة كغير مقروءة.', 'Message marked as unread.'],
    'تم تعليم الرسالة كمقروءة.' => ['toast.message_read', 'تم تعليم الرسالة كمقروءة.', 'Message marked as read.'],
    'تم تعيين الموقع الافتراضي' => ['toast.address_default', 'تم تعيين الموقع الافتراضي', 'Default address set'],
    'تم تفعيل الطاولة. لديك ساعة للطلب.' => ['toast.table_activated', 'تم تفعيل الطاولة. لديك ساعة للطلب.', 'Table activated. You have one hour to order.'],
    'تم تنفيذ التحويل وتحديث المخزون.' => ['toast.transfer_completed', 'تم تنفيذ التحويل وتحديث المخزون.', 'Transfer completed and stock updated.'],
    'تم حذف التحويل.' => ['toast.transfer_deleted', 'تم حذف التحويل.', 'Transfer deleted.'],
    'تم حذف الجرد.' => ['toast.stock_count_deleted', 'تم حذف الجرد.', 'Stock count deleted.'],
    'تم حذف الجلسة بنجاح.' => ['toast.table_session_deleted', 'تم حذف الجلسة بنجاح.', 'Session deleted successfully.'],
    'تم حذف الجلسة.' => ['toast.session_deleted', 'تم حذف الجلسة.', 'Session deleted.'],
    'تم حذف الحساب.' => ['toast.account_deleted', 'تم حذف الحساب.', 'Account deleted.'],
    'تم حذف الدور.' => ['toast.role_deleted', 'تم حذف الدور.', 'Role deleted.'],
    'تم حذف الرسالة.' => ['toast.message_deleted', 'تم حذف الرسالة.', 'Message deleted.'],
    'تم حذف الصفحة.' => ['toast.page_deleted', 'تم حذف الصفحة.', 'Page deleted.'],
    'تم حذف الصندوق.' => ['toast.cash_register_deleted', 'تم حذف الصندوق.', 'Cash register deleted.'],
    'تم حذف الطاولة.' => ['toast.table_deleted', 'تم حذف الطاولة.', 'Table deleted.'],
    'تم حذف الطلب بنجاح.' => ['toast.order_deleted', 'تم حذف الطلب بنجاح.', 'Order deleted successfully.'],
    'تم حذف العملة.' => ['toast.currency_deleted', 'تم حذف العملة.', 'Currency deleted.'],
    'تم حذف الفاتورة بنجاح.' => ['toast.invoice_deleted', 'تم حذف الفاتورة بنجاح.', 'Invoice deleted successfully.'],
    'تم حذف القسم.' => ['toast.category_deleted', 'تم حذف القسم.', 'Category deleted.'],
    'تم حذف القيد.' => ['toast.journal_deleted', 'تم حذف القيد.', 'Journal entry deleted.'],
    'تم حذف المستخدم.' => ['toast.user_deleted', 'تم حذف المستخدم.', 'User deleted.'],
    'تم حذف المستودع.' => ['toast.warehouse_deleted', 'تم حذف المستودع.', 'Warehouse deleted.'],
    'تم حذف المصروف.' => ['toast.expense_deleted', 'تم حذف المصروف.', 'Expense deleted.'],
    'تم حذف المنتج من السلة' => ['toast.cart_item_removed', 'تم حذف المنتج من السلة', 'Item removed from cart'],
    'تم حذف المورد.' => ['toast.supplier_deleted', 'تم حذف المورد.', 'Supplier deleted.'],
    'تم حذف الموقع' => ['toast.address_deleted', 'تم حذف الموقع', 'Address deleted'],
    'تم حذف سجل الهدر وإرجاع الكمية للمخزون.' => ['toast.wastage_deleted', 'تم حذف سجل الهدر وإرجاع الكمية للمخزون.', 'Wastage deleted and quantity restored.'],
    'تم حذف شعار الباركود المخصص.' => ['toast.qr_logo_deleted', 'تم حذف شعار الباركود المخصص.', 'Custom QR logo deleted.'],
    'تم حذف فاتورة الشراء.' => ['toast.purchase_invoice_deleted', 'تم حذف فاتورة الشراء.', 'Purchase invoice deleted.'],
    'تم حفظ إعدادات الموقع بنجاح.' => ['toast.site_settings_saved', 'تم حفظ إعدادات الموقع بنجاح.', 'Site settings saved successfully.'],
    'تم حفظ إعدادات النشاط بنجاح.' => ['toast.business_settings_saved', 'تم حفظ إعدادات النشاط بنجاح.', 'Business settings saved successfully.'],
    'تم حفظ الموقع' => ['toast.address_saved', 'تم حفظ الموقع', 'Address saved'],
    'تم حفظ تصميم الصفحة.' => ['toast.page_saved', 'تم حفظ تصميم الصفحة.', 'Page design saved.'],
    'تم حفظ تصميم الفاتورة.' => ['toast.receipt_saved', 'تم حفظ تصميم الفاتورة.', 'Receipt design saved.'],
    'تم حفظ شعار الباركود.' => ['toast.qr_logo_saved', 'تم حفظ شعار الباركود.', 'QR logo saved.'],
    'تم حفظ كميات الجرد.' => ['toast.stock_count_saved', 'تم حفظ كميات الجرد.', 'Stock count quantities saved.'],
    'تم حفظ مواد التحويل.' => ['toast.transfer_items_saved', 'تم حفظ مواد التحويل.', 'Transfer items saved.'],
    'تم حفظ مواد فاتورة الشراء.' => ['toast.purchase_items_saved', 'تم حفظ مواد فاتورة الشراء.', 'Purchase invoice items saved.'],
    'تم حفظ موقع التوصيل' => ['toast.delivery_location_saved', 'تم حفظ موقع التوصيل', 'Delivery location saved'],
    'تم رفض الطلب' => ['toast.order_rejected', 'تم رفض الطلب', 'Order rejected'],
    'تم رفض طلب الطاولة' => ['toast.table_order_rejected', 'تم رفض طلب الطاولة', 'Table order rejected'],
    'تم فتح جلسة الصندوق.' => ['toast.session_opened', 'تم فتح جلسة الصندوق.', 'Cash register session opened.'],
    'تم قبول الطلب' => ['toast.order_accepted', 'تم قبول الطلب', 'Order accepted'],
    'تم قبول طلب الطاولة وإرساله للمطبخ' => ['toast.table_order_accepted', 'تم قبول طلب الطاولة وإرساله للمطبخ', 'Table order accepted and sent to kitchen'],
    'تمت إضافة العملة بنجاح.' => ['toast.currency_created', 'تمت إضافة العملة بنجاح.', 'Currency added successfully.'],
    'تمت إعادة تصميم الفاتورة للافتراضي.' => ['toast.receipt_reset', 'تمت إعادة تصميم الفاتورة للافتراضي.', 'Receipt design reset to default.'],
    'تمت الإزالة من المفضلة' => ['toast.favorite_removed', 'تمت الإزالة من المفضلة', 'Removed from favorites'],
    'تمت الإضافة إلى السلة' => ['toast.cart_item_added', 'تمت الإضافة إلى السلة', 'Added to cart'],
    'تمت الإضافة إلى المفضلة' => ['toast.favorite_added', 'تمت الإضافة إلى المفضلة', 'Added to favorites'],
    'جلسة الطاولة غير صالحة أو منتهية. يرجى إعادة التفعيل.' => ['toast.table_session_invalid', 'جلسة الطاولة غير صالحة أو منتهية. يرجى إعادة التفعيل.', 'Table session is invalid or expired. Please reactivate.'],
    'صفحات النظام محمية وتبقى منشورة دائماً.' => ['toast.system_page_protected_publish', 'صفحات النظام محمية وتبقى منشورة دائماً.', 'System pages are protected and stay published.'],
    'صفحات النظام محمية ولا يمكن حذفها.' => ['toast.system_page_protected_delete', 'صفحات النظام محمية ولا يمكن حذفها.', 'System pages are protected and cannot be deleted.'],
    'فعّل كلمة المرور للدخول إلى حسابك' => ['toast.password_setup_required', 'فعّل كلمة المرور للدخول إلى حسابك', 'Set a password to access your account'],
    'لا توجد فاتورة قابلة للتعديل لهذا الطلب.' => ['toast.no_editable_invoice', 'لا توجد فاتورة قابلة للتعديل لهذا الطلب.', 'No editable invoice for this order.'],
    'لا يمكن تعديل طلب مكتمل أو ملغي.' => ['toast.order_locked', 'لا يمكن تعديل طلب مكتمل أو ملغي.', 'Cannot edit a completed or cancelled order.'],
    'لا يمكن تعديل فاتورة ملغاة.' => ['toast.invoice_void_locked', 'لا يمكن تعديل فاتورة ملغاة.', 'Cannot edit a voided invoice.'],
    'لا يمكن تعديل قيد مرتبط بعملية نظام.' => ['toast.journal_system_locked', 'لا يمكن تعديل قيد مرتبط بعملية نظام.', 'Cannot edit a system-linked journal entry.'],
    'لا يمكن تغيير اسم دور النظام.' => ['toast.role_system_rename', 'لا يمكن تغيير اسم دور النظام.', 'Cannot rename a system role.'],
    'لا يمكن حذف أدوار النظام الأساسية.' => ['toast.role_system_delete', 'لا يمكن حذف أدوار النظام الأساسية.', 'Cannot delete core system roles.'],
    'لا يمكن حذف العملة الرئيسية.' => ['toast.currency_primary_delete', 'لا يمكن حذف العملة الرئيسية.', 'Cannot delete the primary currency.'],
    'لا يمكن حذف جلسة فيها مدفوعات أو حركات نقد.' => ['toast.session_has_movements', 'لا يمكن حذف جلسة فيها مدفوعات أو حركات نقد.', 'Cannot delete a session with payments or cash movements.'],
    'لا يمكن حذف حساب له حسابات فرعية.' => ['toast.account_has_children', 'لا يمكن حذف حساب له حسابات فرعية.', 'Cannot delete an account that has child accounts.'],
    'لا يمكن حذف حساب مرتبط بقيود أو مصروفات.' => ['toast.account_has_entries', 'لا يمكن حذف حساب مرتبط بقيود أو مصروفات.', 'Cannot delete an account linked to entries or expenses.'],
    'لا يمكن حذف حساب نظامي.' => ['toast.account_system_delete', 'لا يمكن حذف حساب نظامي.', 'Cannot delete a system account.'],
    'لا يمكن حذف دور مرتبط بمستخدمين.' => ['toast.role_has_users', 'لا يمكن حذف دور مرتبط بمستخدمين.', 'Cannot delete a role assigned to users.'],
    'لا يمكن حذف صندوق له جلسات سابقة — أوقفه بدلاً من الحذف.' => ['toast.cash_register_has_sessions', 'لا يمكن حذف صندوق له جلسات سابقة — أوقفه بدلاً من الحذف.', 'Cannot delete a register with past sessions — deactivate it instead.'],
    'لا يمكن حذف صندوق له جلسة مفتوحة — أغلق الجلسة أولاً.' => ['toast.cash_register_open_session', 'لا يمكن حذف صندوق له جلسة مفتوحة — أغلق الجلسة أولاً.', 'Cannot delete a register with an open session — close it first.'],
    'لا يمكن حذف قيد مرتبط بعملية نظام.' => ['toast.journal_system_delete', 'لا يمكن حذف قيد مرتبط بعملية نظام.', 'Cannot delete a system-linked journal entry.'],
    'لا يمكن حذف مستخدم مرتبط بطلبات أو فواتير.' => ['toast.user_has_orders', 'لا يمكن حذف مستخدم مرتبط بطلبات أو فواتير.', 'Cannot delete a user linked to orders or invoices.'],
    'لا يمكن حذف مستودع فيه مواد — انقل أو صفِّ الرصيد أولاً.' => ['toast.warehouse_has_stock', 'لا يمكن حذف مستودع فيه مواد — انقل أو صفِّ الرصيد أولاً.', 'Cannot delete a warehouse with stock — transfer or clear balances first.'],
    'لا يمكن حذف مورد مرتبط بأوامر شراء أو فواتير.' => ['toast.supplier_has_docs', 'لا يمكن حذف مورد مرتبط بأوامر شراء أو فواتير.', 'Cannot delete a supplier linked to purchase orders or invoices.'],
    'لا يمكن رفض هذا الطلب.' => ['toast.cannot_reject_order', 'لا يمكن رفض هذا الطلب.', 'Cannot reject this order.'],
    'لا يمكن قبول هذا الطلب.' => ['toast.cannot_accept_order', 'لا يمكن قبول هذا الطلب.', 'Cannot accept this order.'],
    'لا يمكنك حذف حسابك الحالي.' => ['toast.cannot_delete_self', 'لا يمكنك حذف حسابك الحالي.', 'You cannot delete your own account.'],
    'نموذج التواصل غير متاح حالياً.' => ['toast.contact_form_disabled', 'نموذج التواصل غير متاح حالياً.', 'Contact form is currently unavailable.'],
    'يرجى إرسال طلبك أولاً للحصول على رمز التحقق' => ['toast.otp_need_order', 'يرجى إرسال طلبك أولاً للحصول على رمز التحقق', 'Please submit your order first to get a verification code'],
    'يرجى تحديد موقع التوصيل أولاً' => ['toast.delivery_location_required', 'يرجى تحديد موقع التوصيل أولاً', 'Please set a delivery location first'],
    'يرجى تحديد موقع التوصيل على الخريطة' => ['toast.delivery_map_required', 'يرجى تحديد موقع التوصيل على الخريطة', 'Please set the delivery location on the map'],
    'Created successfully.' => ['toast.created', 'تم الإنشاء بنجاح.', 'Created successfully.'],
    'Updated successfully.' => ['toast.updated', 'تم التحديث بنجاح.', 'Updated successfully.'],
    'Deleted successfully.' => ['toast.deleted', 'تم الحذف بنجاح.', 'Deleted successfully.'],
    'Stock adjusted successfully.' => ['toast.stock_adjusted', 'تم تعديل المخزون بنجاح.', 'Stock adjusted successfully.'],
];

$arPath = $root.'/lang/ar.json';
$enPath = $root.'/lang/en.json';
$ar = json_decode(file_get_contents($arPath), true) ?: [];
$en = json_decode(file_get_contents($enPath), true) ?: [];

foreach ($catalog as [$key, $arText, $enText]) {
    $ar[$key] = $arText;
    $en[$key] = $enText;
}

// Frontend toast keys
$fe = [
    'toast.login_for_favorites' => ['سجّل دخولك أو أكمل طلباً للوصول إلى المفضلة', 'Sign in or complete an order to use favorites'],
    'toast.barcode_read' => ['تم قراءة الباركود بنجاح', 'Barcode read successfully'],
    'toast.select_table_first' => ['اختر الطاولة أولاً', 'Select a table first'],
    'toast.table_already_open' => ['هذه الطاولة مفتوحة حالياً ولا يمكن اختيارها', 'This table is already open and cannot be selected'],
    'toast.copied' => ['تم النسخ', 'Copied'],
    'toast.copy_failed' => ['تعذر النسخ', 'Could not copy'],
    'toast.unified_qr_copied' => ['تم نسخ رابط الباركود الموحد', 'Unified QR link copied'],
    'toast.access_code_copied' => ['تم نسخ رمز التفعيل العام', 'Access code copied'],
    'toast.operation_failed' => ['تعذر إتمام العملية', 'Operation failed'],
    'toast.add_product_failed' => ['تعذر إضافة المنتج', 'Could not add product'],
    'toast.qty_update_failed' => ['تعذر تحديث الكمية', 'Could not update quantity'],
    'toast.remove_product_failed' => ['تعذر حذف المنتج', 'Could not remove product'],
    'toast.open_table_failed' => ['تعذر فتح الطاولة', 'Could not open table'],
    'toast.warehouse_missing' => ['لم يتم العثور على مستودع', 'No warehouse found'],
    'toast.order_sent_kitchen' => ['تم إرسال الطلب للمطبخ', 'Order sent to kitchen'],
    'toast.send_order_failed' => ['تعذر إرسال الطلب', 'Could not send order'],
    'toast.payment_done' => ['تم تسديد الحساب', 'Payment completed'],
    'toast.operation_success' => ['تمت العملية بنجاح', 'Operation completed successfully'],
    'toast.payment_failed' => ['تعذر إتمام الدفع', 'Payment failed'],
    'toast.accept_failed' => ['تعذر قبول الطلب', 'Could not accept order'],
    'toast.reject_failed' => ['تعذر رفض الطلب', 'Could not reject order'],
    'toast.session_expired' => ['انتهت مدة الجلسة', 'Session expired'],
    'toast.product_added_named' => ['تمت إضافة :name', 'Added :name'],
    'toast.cart_empty' => ['السلة فارغة', 'Cart is empty'],
    'toast.page_saved_fe' => ['تم حفظ الصفحة', 'Page saved'],
    'toast.page_save_failed' => ['تعذّر الحفظ — راجع الحقول', 'Save failed — check the fields'],
    'toast.block_added' => ['تمت إضافة «:name» — عبّئ النصوص من اللوحة اليمنى', 'Added “:name” — fill texts from the right panel'],
    'toast.section_added' => ['تمت إضافة «:name»', 'Added “:name”'],
    'toast.receipt_save_failed' => ['تعذر حفظ التصميم', 'Could not save design'],
    'toast.qr_generate_failed' => ['تعذر إنشاء الباركود', 'Could not generate QR'],
    'toast.invalid_image' => ['اختر صورة صالحة', 'Choose a valid image'],
    'toast.logo_saved_colors' => ['تم حفظ الشعار واستخراج الألوان', 'Logo saved and colors extracted'],
    'toast.logo_save_failed' => ['تعذر حفظ الشعار', 'Could not save logo'],
    'toast.logo_deleted' => ['تم حذف الشعار المخصص', 'Custom logo deleted'],
    'toast.logo_delete_failed' => ['تعذر حذف الشعار', 'Could not delete logo'],
    'toast.qr_downloaded' => ['تم تحميل صورة الباركود', 'QR image downloaded'],
    'toast.invoice_needs_item' => ['يجب أن تحتوي الفاتورة على صنف واحد على الأقل', 'Invoice must contain at least one item'],
    'toast.debug_otp' => ['رمز التطوير: :code', 'Dev code: :code'],
    'toast.accept_table_order' => ['تم قبول الطلب وإرساله للمطبخ', 'Order accepted and sent to kitchen'],
];

foreach ($fe as $key => [$arText, $enText]) {
    $ar[$key] = $arText;
    $en[$key] = $enText;
}

ksort($ar);
ksort($en);

file_put_contents($arPath, json_encode($ar, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
file_put_contents($enPath, json_encode($en, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

$replaced = 0;
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app'));
foreach ($iterator as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $content = file_get_contents($path);
    $original = $content;

    foreach ($catalog as $literal => [$key]) {
        $escaped = str_replace(['\\', "'"], ['\\\\', "\\'"], $literal);
        $content = str_replace("'message' => '{$escaped}'", "'message' => __('{$key}')", $content);
        $content = str_replace('__("'.$literal.'")', "__('{$key}')", $content);
        $content = str_replace("__('{$literal}')", "__('{$key}')", $content);
    }

    // Locale controller special case already handled if literal matches
    if ($content !== $original) {
        file_put_contents($path, $content);
        $replaced++;
        echo 'updated: '.str_replace($root.'\\', '', str_replace($root.'/', '', $path)).PHP_EOL;
    }
}

echo "Done. Files updated: {$replaced}, keys: ".count($catalog).PHP_EOL;
