<?php
/**
 * قالب تذييل الصفحة
 * يستخدم في جميع صفحات العرض
 */

// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    exit('لا يمكن الوصول المباشر لهذا الملف');
}

// متغير للملفات JavaScript الإضافية
$extraScripts = isset($extraScripts) ? $extraScripts : [];

// متغير للتحكم في عرض نموذج تأكيد الحذف
$showDeleteConfirmModal = isset($showDeleteConfirmModal) ? $showDeleteConfirmModal : false;

// متغير لنص السؤال في نموذج تأكيد الحذف
$deleteConfirmText = isset($deleteConfirmText) ? $deleteConfirmText : 'هل أنت متأكد من أنك تريد حذف هذا العنصر؟';
?>

                </main>
            </div>
        </div>
        
        <!-- تذييل الصفحة -->
        <footer class="footer mt-auto py-3 bg-light">
            <div class="container text-center">
                <span class="text-muted">
                    © <?php echo date('Y'); ?> <?php echo APP_NAME; ?> | جميع الحقوق محفوظة
                </span>
            </div>
        </footer>
        
        <!-- الأسكرول لأعلى -->
        <button type="button" class="btn btn-primary btn-sm rounded-circle back-to-top" id="back-to-top">
            <i class="fas fa-arrow-up"></i>
        </button>
        
        <?php if ($showDeleteConfirmModal): ?>
        <!-- نموذج تأكيد الحذف -->
        <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteConfirmModalLabel">تأكيد الحذف</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0" id="deleteConfirmMessage"><?php echo htmlspecialchars($deleteConfirmText); ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <a href="#" class="btn btn-danger" id="confirmDeleteButton">تأكيد الحذف</a>
                    </div>
                </div>
            </div>
