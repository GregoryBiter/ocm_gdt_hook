<?php
use \GbitStudio\GDT\Engine\Hook;
/**
 * Приклад модуля з hook_boot методом
 * 
 * Цей модуль демонструє як використовувати hook_boot для ініціалізації хуків
 * при завантаженні системи без необхідності створювати окремі файли в controller/hook/
 */
class ControllerExtensionModuleExampleHook extends Controller {
    
    /**
     * Метод hook_boot викликається автоматично при завантаженні системи
     * якщо модуль встановлений та активований
     * 
     * Використовуйте цей метод для:
     * - Реєстрації action хуків
     * - Реєстрації filter хуків
     * - Реєстрації подій OpenCart
     * - Додавання глобальних методів
     */
    public function hook_boot() {
        Hook::register_event('model/catalog/product/getProduct/after', 'extension/module/example_hook/event1', 10);
        hook::register_event('model/catalog/product/getProduct/before', 'extension/module/example_hook/event1', 10);
     
    }

    public function event1($args, &$output = null, &$data = null) {
        // Ваш код для обробки події catalog_model_catalog_product_getProduct_after
        
        $data['name'] = 'Modified Product Name';
        var_dump($args);
        var_dump($output);
        var_dump($data);
    }

    
  
}
