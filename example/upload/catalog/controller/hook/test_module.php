<?php
/*
name: Catalog Test Hook
description: This hook adds functionality to the catalog side.
version: 1.0.0
controller: extension/module/test
*/

use GbitStudio\GDT\Engine\HookController;
use GbitStudio\GDT\Engine\Hook;

class ControllerHookTestModule extends HookController {
    /**
     * Catalog test module controller
     */
    public function boot() {
        // Добавляем filter для модификации продуктов в каталоге
        Hook::add_filter('catalog_product_info', function($product_info) {
            // Можем добавить дополнительную информацию к продукту
            $product_info['custom_field'] = 'Modified by catalog hook';
            return $product_info;
        });

        // Добавляем action для логирования просмотров продуктов
        Hook::add_action('catalog_product_view', function($product_id) {
            // Логируем просмотр продукта
            \GbitStudio\GDT\Engine\GDT::logWrite("Product viewed: " . $product_id);
        });
    }
}
