<?php
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
        // Приклад 1: Додавання action хука
        \GbitStudio\GDT\Engine\Hook::add_action('admin_menu_render', [$this, 'addCustomMenuItem'], 10);
        
        // Приклад 2: Додавання filter хука
        \GbitStudio\GDT\Engine\Hook::add_filter('product_price', [$this, 'modifyProductPrice'], 10, 2);
        
        // Приклад 3: Реєстрація події OpenCart
        \GbitStudio\GDT\Engine\Hook::add_event(
            'catalog/model/checkout/order/addOrder/after',
            [$this, 'onOrderCreated'],
            0
        );
        
        // Приклад 4: Реєстрація глобального методу
        \GbitStudio\GDT\Engine\Hook::register_global('getExampleData', [$this, 'getExampleData']);
    }
    
    /**
     * Приклад action хука - додає пункт до меню адміністратора
     */
    public function addCustomMenuItem() {
        // Ваш код для додавання пункту меню
    }
    
    /**
     * Приклад filter хука - модифікує ціну продукту
     * 
     * @param float $price Оригінальна ціна
     * @param array $product_data Дані продукту
     * @return float Модифікована ціна
     */
    public function modifyProductPrice($price, $product_data) {
        // Приклад: додаємо 10% до ціни
        return $price * 1.1;
    }
    
    /**
     * Приклад обробника події - викликається після створення замовлення
     * 
     * @param string $route Маршрут
     * @param array $args Аргументи події
     */
    public function onOrderCreated($route, &$args) {
        // Отримуємо ID створеного замовлення
        $order_id = $args[0];
        
        // Ваш код обробки події
        // Наприклад, надіслати повідомлення, оновити статистику тощо
    }
    
    /**
     * Приклад глобального методу
     * 
     * @return array Приклад даних
     */
    public function getExampleData() {
        return [
            'module' => 'example_hook',
            'version' => '1.0.0',
            'status' => 'active'
        ];
    }
    
    /**
     * Головний метод модуля (стандартний для OpenCart)
     * Цей метод викликається при відображенні модуля на сторінці
     */
    public function index() {
        $this->load->language('extension/module/example_hook');
        
        $data['heading_title'] = $this->language->get('heading_title');
        
        return $this->load->view('extension/module/example_hook', $data);
    }
    
    /**
     * Метод install викликається при встановленні модуля
     */
    public function install() {
        // Ваш код ініціалізації при встановленні
        // Наприклад, створення таблиць БД
        
        // Очищаємо кеш хуків для негайного застосування
        if (class_exists('\GbitStudio\GDT\Engine\HookCache')) {
            \GbitStudio\GDT\Engine\HookCache::clear();
        }
    }
    
    /**
     * Метод uninstall викликається при видаленні модуля
     */
    public function uninstall() {
        // Ваш код очищення при видаленні
        // Наприклад, видалення таблиць БД
        
        // Очищаємо кеш хуків
        if (class_exists('\GbitStudio\GDT\Engine\HookCache')) {
            \GbitStudio\GDT\Engine\HookCache::clear();
        }
    }
}
