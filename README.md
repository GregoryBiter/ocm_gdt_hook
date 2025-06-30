# GDT Hook Module

## Описание

Модуль **GDT Hook** предоставляет мощную систему хуков (hooks) для OpenCart, аналогичную WordPress. Этот модуль позволяет создавать расширяемые приложения, где различные компоненты могут взаимодействовать друг с другом через систему событий и фильтров, не изменяя основной код.

### Основные возможности:

- 🔗 **Система хуков WordPress-style** - знакомый API для разработчиков
- ⚡ **Action хуки** - выполнение действий в определенных точках приложения
- 🔧 **Filter хуки** - модификация данных через цепочку фильтров
- 📅 **Интеграция с OpenCart Events** - работа со стандартными событиями OpenCart
- 📁 **Автоматическое обнаружение хуков** - сканирование директории `admin/controller/hook/`
- 🎛️ **Управление через админ-панель** - установка/удаление хуков как модулей
- 📝 **Метаданные хуков** - автоматический парсинг описаний из комментариев
- 🎯 **Приоритеты выполнения** - контроль порядка выполнения хуков

## Технические требования

- PHP 7.4 или выше
- OpenCart 3.x/4.x
- Поддержка пространств имен (namespace)
- Система OCMOD включена

## Установка

1. Скопируйте содержимое папки `upload/` в корневую директорию вашего OpenCart
2. Установите OCMOD файл через админ-панель:
   - Перейдите: Extensions → Installer
   - Загрузите файл `gdt_hook.ocmod.xml`
   - Перейдите: Extensions → Modifications
   - Нажмите кнопку "Refresh"

### Структура установки:
```
system/gdt_hook.ocmod.xml                           # OCMOD модификации
system/library/gbitstudio/gdt/engine/hook.php       # Основной класс Hook
system/library/gbitstudio/gdt/engine/hookcontroller.php      # Базовый класс для хуков
system/library/gbitstudio/gdt/engine/hookmetaparser.php      # Парсер метаданных
```

## Создание хуков

### Структура хука

Хуки размещаются в директории `admin/controller/hook/` и должны следовать определенной структуре:

```php
<?php
/*
 * name: Мой пользовательский хук
 * description: Описание функциональности хука
 * version: 1.0.0
 * author: Ваше Имя
 * controller: extension/module/my_hook
 * hidden: false
 */

namespace GbitStudio\GDT\Engine;

class MyHook extends HookController {
    
    public function boot() {
        // Инициализация хука
        $this->registerHooks();
    }
    
    private function registerHooks() {
        // Регистрация action хуков
        Hook::add_action('init', [$this, 'onInit'], 10, 1);
        Hook::add_action('admin_header', [$this, 'addCustomCSS'], 20);
        
        // Регистрация filter хуков
        Hook::add_filter('product_data', [$this, 'modifyProductData'], 10, 2);
        Hook::add_filter('menu_items', [$this, 'addMenuItems'], 15, 1);
        
        // Регистрация OpenCart событий
        Hook::add_event('catalog/model/checkout/order/addOrder/after', [$this, 'afterOrderAdd']);
    }
    
    public function onInit($data) {
        // Действие при инициализации
        error_log('Hook initialized with data: ' . print_r($data, true));
    }
    
    public function addCustomCSS() {
        // Добавление CSS в админ-панель
        echo '<link rel="stylesheet" href="/admin/view/stylesheet/my-hook.css">';
    }
    
    public function modifyProductData($product_data, $product_id) {
        // Модификация данных товара
        $product_data['custom_field'] = 'Added by hook';
        return $product_data;
    }
    
    public function addMenuItems($menu_items) {
        // Добавление пунктов в меню
        $menu_items[] = [
            'name' => 'Custom Menu Item',
            'href' => 'extension/module/my_hook'
        ];
        return $menu_items;
    }
    
    public function afterOrderAdd($route, $args) {
        // Обработчик события после создания заказа
        $order_id = $args[0];
        // Логика обработки нового заказа
    }
}

// Автоматическая регистрация хука
if (class_exists('GbitStudio\GDT\Engine\Hook')) {
    $hook = new MyHook(registry());
    $hook->boot();
}
```

## API системы хуков

### Action хуки

Action хуки выполняют действия в определенных точках приложения:

```php
// Регистрация action хука
Hook::add_action('hook_name', 'callback_function', $priority, $accepted_args);

// Выполнение action хука
Hook::do_action('hook_name', $arg1, $arg2, $arg3);
```

#### Примеры action хуков:

```php
// Простой action хук
Hook::add_action('admin_init', function() {
    // Код выполняется при инициализации админ-панели
});

// Action хук с аргументами
Hook::add_action('user_login', function($user_id, $user_data) {
    // Логирование входа пользователя
    error_log("User $user_id logged in");
}, 10, 2);

// Action хук с высоким приоритетом
Hook::add_action('before_save_product', function($product_data) {
    // Валидация перед сохранением товара
    if (empty($product_data['name'])) {
        throw new Exception('Product name is required');
    }
}, 5, 1);

// Выполнение action хуков
Hook::do_action('admin_init');
Hook::do_action('user_login', $user_id, $user_data);
Hook::do_action('before_save_product', $product_data);
```

### Filter хуки

Filter хуки модифицируют данные, пропуская их через цепочку фильтров:

```php
// Регистрация filter хука
Hook::add_filter('filter_name', 'callback_function', $priority, $accepted_args);

// Применение filter хука
$modified_value = Hook::apply_filters('filter_name', $original_value, $arg1, $arg2);
```

#### Примеры filter хуков:

```php
// Простой filter хук
Hook::add_filter('product_price', function($price) {
    // Применение скидки 10%
    return $price * 0.9;
});

// Filter хук с дополнительными аргументами
Hook::add_filter('product_description', function($description, $product_id, $language_id) {
    // Добавление префикса к описанию
    return '[ID: ' . $product_id . '] ' . $description;
}, 10, 3);

// Цепочка фильтров
Hook::add_filter('email_content', function($content) {
    return $content . "\n\nWith regards,\nYour Team";
}, 10);

Hook::add_filter('email_content', function($content) {
    return strtoupper($content); // Верхний регистр
}, 20);

// Применение фильтров
$price = Hook::apply_filters('product_price', 100); // Результат: 90
$description = Hook::apply_filters('product_description', 'Product desc', 123, 1);
$email = Hook::apply_filters('email_content', 'Hello!'); // Цепочка фильтров
```

### OpenCart Events

Интеграция со стандартными событиями OpenCart:

```php
// Регистрация обработчика события
Hook::add_event('catalog/model/checkout/order/addOrder/after', function($route, $args) {
    $order_id = $args[0];
    // Обработка после создания заказа
}, 0);

// Событие перед выполнением контроллера
Hook::add_event('controller/*/before', function($route, $args) {
    // Глобальная проверка прав доступа
});

// Событие после выполнения модели
Hook::add_event('model/*/after', function($route, $args, $output) {
    // Логирование запросов к моделям
});
```

## Практические примеры

### Создание системы уведомлений

```php
<?php
/*
 * name: Notification System
 * description: Система уведомлений для админ-панели
 * version: 1.0.0
 * author: GbitStudio
 */

namespace GbitStudio\GDT\Engine;

class NotificationHook extends HookController {
    
    public function boot() {
        Hook::add_action('admin_header', [$this, 'showNotifications']);
        Hook::add_action('order_status_change', [$this, 'notifyOrderStatusChange'], 10, 2);
        Hook::add_filter('admin_menu', [$this, 'addNotificationMenu']);
    }
    
    public function showNotifications() {
        $notifications = $this->getUnreadNotifications();
        
        if (!empty($notifications)) {
            echo '<div class="alert alert-info">';
            echo '<strong>Уведомления (' . count($notifications) . '):</strong><br>';
            foreach ($notifications as $notification) {
                echo '• ' . $notification['message'] . '<br>';
            }
            echo '</div>';
        }
    }
    
    public function notifyOrderStatusChange($order_id, $new_status) {
        $this->addNotification([
            'type' => 'order_status',
            'message' => "Статус заказа #$order_id изменен на: $new_status",
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function addNotificationMenu($menu_items) {
        $menu_items[] = [
            'name' => 'Уведомления',
            'href' => 'extension/module/notifications',
            'icon' => 'fa-bell'
        ];
        return $menu_items;
    }
    
    private function getUnreadNotifications() {
        // Логика получения непрочитанных уведомлений
        return [];
    }
    
    private function addNotification($notification) {
        // Логика сохранения уведомления
    }
}

if (class_exists('GbitStudio\GDT\Engine\Hook')) {
    $hook = new NotificationHook(registry());
    $hook->boot();
}
```

### Создание системы аудита

```php
<?php
/*
 * name: Audit System
 * description: Система аудита изменений в админ-панели
 * version: 1.0.0
 * author: GbitStudio
 */

namespace GbitStudio\GDT\Engine;

class AuditHook extends HookController {
    
    public function boot() {
        // Аудит действий с товарами
        Hook::add_event('admin/model/catalog/product/addProduct/after', [$this, 'logProductAdd']);
        Hook::add_event('admin/model/catalog/product/editProduct/after', [$this, 'logProductEdit']);
        Hook::add_event('admin/model/catalog/product/deleteProduct/after', [$this, 'logProductDelete']);
        
        // Аудит действий с заказами
        Hook::add_event('admin/model/sale/order/addOrderHistory/after', [$this, 'logOrderHistory']);
        
        // Фильтр для добавления информации об аудите
        Hook::add_filter('admin_dashboard_data', [$this, 'addAuditInfo']);
    }
    
    public function logProductAdd($route, $args, $output) {
        $product_id = $output;
        $this->logAction('product_add', [
            'product_id' => $product_id,
            'user_id' => $this->getUserId(),
            'ip_address' => $this->getClientIP()
        ]);
    }
    
    public function logProductEdit($route, $args) {
        $product_id = $args[0];
        $this->logAction('product_edit', [
            'product_id' => $product_id,
            'user_id' => $this->getUserId(),
            'changes' => $args[1], // Данные изменений
            'ip_address' => $this->getClientIP()
        ]);
    }
    
    public function logProductDelete($route, $args) {
        $product_id = $args[0];
        $this->logAction('product_delete', [
            'product_id' => $product_id,
            'user_id' => $this->getUserId(),
            'ip_address' => $this->getClientIP()
        ]);
    }
    
    public function logOrderHistory($route, $args) {
        $order_id = $args[0];
        $order_status_id = $args[1];
        
        $this->logAction('order_status_change', [
            'order_id' => $order_id,
            'new_status_id' => $order_status_id,
            'user_id' => $this->getUserId(),
            'ip_address' => $this->getClientIP()
        ]);
    }
    
    public function addAuditInfo($dashboard_data) {
        $recent_actions = $this->getRecentAuditActions(10);
        $dashboard_data['audit_actions'] = $recent_actions;
        return $dashboard_data;
    }
    
    private function logAction($action_type, $data) {
        $db = registry('db');
        
        $db->query("
            INSERT INTO " . DB_PREFIX . "audit_log 
            SET action_type = '" . $db->escape($action_type) . "',
                data = '" . $db->escape(json_encode($data)) . "',
                created_at = NOW()
        ");
    }
    
    private function getUserId() {
        $session = registry('session');
        return isset($session->data['user_id']) ? $session->data['user_id'] : 0;
    }
    
    private function getClientIP() {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    private function getRecentAuditActions($limit) {
        $db = registry('db');
        
        $query = $db->query("
            SELECT * FROM " . DB_PREFIX . "audit_log 
            ORDER BY created_at DESC 
            LIMIT " . (int)$limit
        );
        
        return $query->rows;
    }
}

if (class_exists('GbitStudio\GDT\Engine\Hook')) {
    $hook = new AuditHook(registry());
    $hook->boot();
}
```

### Создание системы кеширования

```php
<?php
/*
 * name: Cache System
 * description: Расширенная система кеширования
 * version: 1.0.0
 * author: GbitStudio
 */

namespace GbitStudio\GDT\Engine;

class CacheHook extends HookController {
    
    public function boot() {
        // Кеширование данных товаров
        Hook::add_filter('product_data', [$this, 'cacheProductData'], 5, 2);
        Hook::add_action('product_update', [$this, 'clearProductCache'], 10, 1);
        
        // Кеширование категорий
        Hook::add_filter('category_tree', [$this, 'cacheCategoryTree'], 5, 1);
        Hook::add_action('category_update', [$this, 'clearCategoryCache']);
        
        // Автоочистка кеша
        Hook::add_action('admin_footer', [$this, 'scheduleCleanup']);
    }
    
    public function cacheProductData($product_data, $product_id) {
        $cache_key = 'product_' . $product_id;
        
        // Проверяем кеш
        if ($cached_data = $this->getCache($cache_key)) {
            return $cached_data;
        }
        
        // Сохраняем в кеш
        $this->setCache($cache_key, $product_data, 3600); // 1 час
        
        return $product_data;
    }
    
    public function clearProductCache($product_id) {
        $this->deleteCache('product_' . $product_id);
        $this->deleteCache('product_related_' . $product_id);
    }
    
    public function cacheCategoryTree($categories) {
        $cache_key = 'category_tree';
        
        if ($cached_categories = $this->getCache($cache_key)) {
            return $cached_categories;
        }
        
        $this->setCache($cache_key, $categories, 7200); // 2 часа
        
        return $categories;
    }
    
    public function clearCategoryCache() {
        $this->deleteCache('category_tree');
        $this->deleteCache('category_*'); // Очистка всех кешей категорий
    }
    
    public function scheduleCleanup() {
        // Планируем очистку устаревшего кеша
        if (rand(1, 100) === 1) { // 1% вероятность
            $this->cleanupExpiredCache();
        }
    }
    
    private function getCache($key) {
        $cache_file = DIR_CACHE . 'gdt_' . md5($key) . '.cache';
        
        if (is_file($cache_file)) {
            $data = unserialize(file_get_contents($cache_file));
            
            if ($data['expires'] > time()) {
                return $data['content'];
            } else {
                unlink($cache_file); // Удаляем устаревший кеш
            }
        }
        
        return false;
    }
    
    private function setCache($key, $content, $ttl = 3600) {
        $cache_file = DIR_CACHE . 'gdt_' . md5($key) . '.cache';
        
        $data = [
            'expires' => time() + $ttl,
            'content' => $content
        ];
        
        file_put_contents($cache_file, serialize($data));
    }
    
    private function deleteCache($pattern) {
        $files = glob(DIR_CACHE . 'gdt_*.cache');
        
        foreach ($files as $file) {
            if (strpos($pattern, '*') !== false) {
                // Поддержка wildcards
                $regex = str_replace('*', '.*', preg_quote($pattern));
                if (preg_match('/' . $regex . '/', basename($file))) {
                    unlink($file);
                }
            } else {
                $cache_file = DIR_CACHE . 'gdt_' . md5($pattern) . '.cache';
                if ($file === $cache_file) {
                    unlink($file);
                }
            }
        }
    }
    
    private function cleanupExpiredCache() {
        $files = glob(DIR_CACHE . 'gdt_*.cache');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $data = unserialize(file_get_contents($file));
                if ($data['expires'] <= time()) {
                    unlink($file);
                }
            }
        }
    }
}

if (class_exists('GbitStudio\GDT\Engine\Hook')) {
    $hook = new CacheHook(registry());
    $hook->boot();
}
```

## Управление хуками через админ-панель

Модуль интегрируется с системой модулей OpenCart, позволяя управлять хуками через стандартный интерфейс:

### Список хуков
- Перейдите: Extensions → Extensions → Modules
- Хуки отображаются с суффиксом "(Hook)"
- Показывается информация из метаданных: название, автор, версия

### Установка хука
1. Создайте файл хука в `admin/controller/hook/`
2. Перейдите в Extensions → Extensions → Modules
3. Найдите ваш хук в списке
4. Нажмите кнопку "Install"

### Удаление хука
1. В списке модулей найдите установленный хук
2. Нажмите кнопку "Uninstall"
3. Настройки хука будут удалены

## Структура модуля

```
ocm_gdt_hook/
├── README.md                                           # Этот файл
└── upload/
    └── system/
        ├── gdt_hook.ocmod.xml                          # OCMOD модификации
        └── library/
            └── gbitstudio/
                └── gdt/
                    └── engine/
                        ├── hook.php                    # Основной класс Hook
                        ├── hookcontroller.php          # Базовый класс для хуков
                        └── hookmetaparser.php          # Парсер метаданных
```

## Модификации OCMOD

Модуль вносит следующие изменения в ядро OpenCart:

### 1. Registry модификации
- Загрузка классов Hook, HookController, HookMetaParser

### 2. Модуль Extensions/Modules
- Обнаружение хуков в `admin/controller/hook/`
- Парсинг метаданных хуков
- Отображение хуков в списке модулей
- Обработка установки/удаления хуков

### 3. Startup инициализация
- Автоматическая загрузка системы хуков
- Обработка ошибок инициализации

### 4. Event система
- Интеграция с OpenCart событиями
- Применение фильтров до и после событий

### 5. Column Left (меню)
- Применение фильтров к меню админ-панели

## Лучшие практики

1. **Используйте правильные приоритеты** для контроля порядка выполнения
2. **Документируйте метаданные** в комментариях хуков
3. **Обрабатывайте ошибки** в обработчиках хуков
4. **Используйте пространства имен** для избежания конфликтов
5. **Тестируйте совместимость** с другими хуками
6. **Логируйте действия** для отладки

## Отладка хуков

### Логирование выполнения хуков

```php
// Добавление отладочной информации
Hook::add_action('debug_log', function($message) {
    error_log('[GDT Hook Debug] ' . $message);
});

// Использование в хуках
Hook::do_action('debug_log', 'Product data modified: ' . $product_id);
```

### Проверка зарегистрированных хуков

```php
// Получение списка зарегистрированных action хуков
$actions = Hook::getRegisteredActions();

// Получение списка зарегистрированных filter хуков
$filters = Hook::getRegisteredFilters();
```

## Безопасность

- Все хуки должны проверять права доступа
- Валидируйте входящие данные в обработчиках
- Используйте подготовленные запросы для БД
- Логируйте подозрительную активность
- Ограничивайте время выполнения хуков

## Производительность

- Избегайте тяжелых операций в хуках с высоким приоритетом
- Используйте кеширование для часто вызываемых операций
- Оптимизируйте запросы к базе данных
- Минимизируйте количество хуков на критических путях

## Поддержка

Этот модуль является частью GDT Framework для OpenCart. Для получения поддержки или сообщения об ошибках обратитесь к документации основного фреймворка.

## Лицензия

Модуль распространяется в соответствии с лицензией основного GDT Framework.
