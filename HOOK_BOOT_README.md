# GDT Hook Framework для OpenCart

Система хуків для OpenCart, що дозволяє розширенням реєструвати свої хуки через метод `hook_boot()` без створення окремих файлів в `controller/hook/`.

## Особливості

- ✅ **Автоматичне сканування розширень**: Система автоматично шукає метод `hook_boot()` у всіх встановлених розширеннях через рефлексію PHP
- ✅ **Файлове кешування**: Кешування знайдених хуків для швидкої ініціалізації (без використання системи кешування OpenCart)
- ✅ **Підтримка всіх типів розширень**: module, payment, shipping, dashboard, currency, тощо
- ✅ **Автоматичне очищення кешу**: При встановленні/видаленні розширень
- ✅ **Action і Filter хуки**: WordPress-подібна система хуків
- ✅ **Інтеграція з подіями OpenCart**: Підтримка стандартних подій OpenCart
- ✅ **Глобальні методи**: Можливість реєструвати глобально доступні методи
- ✅ **Перевірка через Reflection API**: Використання PHP Reflection для надійної перевірки наявності методів

## Як це працює

### Процес ініціалізації:

```
1. OpenCart завантажується
   ↓
2. Startup викликає Hook::load()
   ↓
3. Hook перевіряє кеш хуків
   ↓
4. Якщо кеш валідний → завантажує з кешу
   ↓
5. Якщо кеш застарів або відсутній:
   ├─ Сканує БД для отримання всіх встановлених розширень
   ├─ Для кожного активного розширення:
   │  ├─ Завантажує контролер через loader
   │  ├─ Перевіряє наявність методу hook_boot() через Reflection API
   │  ├─ Перевіряє що метод публічний (public)
   │  └─ Викликає hook_boot() якщо все ОК
   └─ Зберігає результати в кеш
   ↓
6. hook_boot() методи реєструють свої хуки через Hook API
```

### Перевірка методу hook_boot():

Система використовує PHP Reflection API для надійної перевірки:

```php
// Завантажуємо контролер
$controller = $loader->controller('extension/module/my_module');

// Перевіряємо наявність методу
if (!method_exists($controller, 'hook_boot')) {
    return false;
}

// Перевіряємо що метод публічний
$reflection = new \ReflectionMethod($controller, 'hook_boot');
if (!$reflection->isPublic()) {
    // Помилка: метод має бути публічним
    return false;
}

// Викликаємо метод
$controller->hook_boot();
```

## Встановлення

1. Скопіюйте файли з директорії `upload/` в корінь вашого OpenCart
2. Перейдіть в адмін панель: Extensions → Modifications
3. Натисніть "Refresh" для застосування OCMOD модифікацій
4. Очистіть кеш OpenCart

## Створення розширення з hook_boot

### Мінімальний приклад:

```php
<?php
class ControllerExtensionModuleMyModule extends Controller {
    
    /**
     * Метод hook_boot ОБОВ'ЯЗКОВО має бути публічним (public)
     */
    public function hook_boot() {
        // Реєстрація action хука
        \GbitStudio\GDT\Engine\Hook::add_action('my_action', [$this, 'myHandler']);
        
        // Реєстрація filter хука
        \GbitStudio\GDT\Engine\Hook::add_filter('product_price', [$this, 'modifyPrice'], 10, 2);
        
        // Реєстрація події OpenCart
        \GbitStudio\GDT\Engine\Hook::add_event(
            'catalog/model/checkout/order/addOrder/after',
            [$this, 'onOrderCreated']
        );
    }
    
    public function myHandler() {
        // Ваш код
    }
    
    public function modifyPrice($price, $product_data) {
        return $price * 1.1; // +10%
    }
    
    public function onOrderCreated($route, &$args) {
        $order_id = $args[0];
        // Обробка події
    }
    
    // Стандартні методи OpenCart
    public function index() {
        // Відображення модуля
    }
    
    public function install() {
        // Очищаємо кеш при встановленні
        if (class_exists('\GbitStudio\GDT\Engine\HookCache')) {
            \GbitStudio\GDT\Engine\HookCache::clear();
        }
    }
    
    public function uninstall() {
        // Очищаємо кеш при видаленні
        if (class_exists('\GbitStudio\GDT\Engine\HookCache')) {
            \GbitStudio\GDT\Engine\HookCache::clear();
        }
    }
}
```

### ⚠️ Важливі правила:

1. **Метод `hook_boot()` ОБОВ'ЯЗКОВО має бути публічним** (`public function hook_boot()`)
2. Метод викликається автоматично тільки для **встановлених та активних** розширень
3. Метод викликається **один раз** при завантаженні системи
4. Всі реєстрації хуків мають бути всередині `hook_boot()`

### Повний приклад:

```php
<?php
/**
 * Приклад модуля з hook_boot методом
 */
class ControllerExtensionModuleExampleHook extends Controller {
    
    /**
     * Метод hook_boot викликається автоматично при завантаженні системи
     * ОБОВ'ЯЗКОВО має бути public!
     */
    public function hook_boot() {
        // Приклад 1: Action хук для додавання CSS
        \GbitStudio\GDT\Engine\Hook::add_action('admin_header', [$this, 'addCustomCSS'], 10);
        
        // Приклад 2: Filter хук для модифікації даних
        \GbitStudio\GDT\Engine\Hook::add_filter('product_data', [$this, 'addProductField'], 10, 2);
        
        // Приклад 3: Множинні хуки з різними пріоритетами
        \GbitStudio\GDT\Engine\Hook::add_filter('menu_items', [$this, 'addMenuItem'], 15);
        \GbitStudio\GDT\Engine\Hook::add_filter('menu_items', [$this, 'sortMenuItems'], 20);
        
        // Приклад 4: Події OpenCart
        \GbitStudio\GDT\Engine\Hook::add_event(
            'catalog/model/checkout/order/addOrder/after',
            [$this, 'afterOrderAdd'],
            0
        );
        
        // Приклад 5: Глобальний метод
        \GbitStudio\GDT\Engine\Hook::register_global('getModuleData', [$this, 'getModuleData']);
        
        // Логування для відлагодження
        \GbitStudio\GDT\Engine\GDT::logWrite('Example Hook: hook_boot initialized');
    }
    
    public function addCustomCSS() {
        echo '<link rel="stylesheet" href="/admin/view/stylesheet/example_hook.css">';
    }
    
    public function addProductField($product_data, $product_id) {
        $product_data['custom_field'] = 'Added by hook';
        return $product_data;
    }
    
    public function addMenuItem($menu_items) {
        $menu_items[] = [
            'name' => 'Custom Menu',
            'href' => 'extension/module/example_hook'
        ];
        return $menu_items;
    }
    
    public function sortMenuItems($menu_items) {
        usort($menu_items, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        return $menu_items;
    }
    
    public function afterOrderAdd($route, &$args) {
        $order_id = $args[0];
        // Ваша логіка
    }
    
    public function getModuleData() {
        return [
            'name' => 'Example Hook',
            'version' => '1.0.0',
            'active' => true
        ];
    }
    
    // Стандартні методи OpenCart
    public function index() {
        $this->load->language('extension/module/example_hook');
        $data['heading_title'] = $this->language->get('heading_title');
        return $this->load->view('extension/module/example_hook', $data);
    }
    
    public function install() {
        // Очищення кешу при встановленні
        if (class_exists('\GbitStudio\GDT\Engine\HookCache')) {
            \GbitStudio\GDT\Engine\HookCache::clear();
        }
    }
    
    public function uninstall() {
        // Очищення кешу при видаленні
        if (class_exists('\GbitStudio\GDT\Engine\HookCache')) {
            \GbitStudio\GDT\Engine\HookCache::clear();
        }
    }
}
```

## API Документація

### Hook класс

#### Action хуки
```php
// Реєстрація action хука
Hook::add_action(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1)

// Виклик action хука
Hook::do_action(string $hook_name, ...$args)

// Перевірка наявності action хука
Hook::has_action(string $hook_name): bool
```

#### Filter хуки
```php
// Реєстрація filter хука
Hook::add_filter(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1)

// Застосування filter хука
Hook::apply_filters(string $hook_name, mixed $value, ...$args): mixed

// Перевірка наявності filter хука
Hook::has_filter(string $hook_name): bool
```

#### Події OpenCart
```php
// Реєстрація обробника події
Hook::add_event(string $trigger, callable $callback, int $priority = 0): string|false

// Видалення обробника події
Hook::remove_event(string $trigger, callable $callback): bool

// Виклик події
Hook::trigger_event(string $event, array $args = []): mixed

// Отримання списку подій
Hook::get_events(): array
```

#### Глобальні методи
```php
// Реєстрація глобального методу
Hook::register_global(string $name, callable $callback)

// Виклик глобального методу
Hook::call_global(string $name, ...$args): mixed

// Перевірка наявності глобального методу
Hook::has_global(string $name): bool

// Список всіх глобальних методів
Hook::get_globals(): array
```

### HookCache клас

```php
// Отримати дані з кешу
HookCache::get(): array|null

// Зберегти дані в кеш
HookCache::set(array $hooks): bool

// Очистити кеш
HookCache::clear(): bool

// Отримати інформацію про кеш
HookCache::getInfo(): array
```

### GDT клас

```php
// Встановити реєстр
GDT::setRegistry(object $registry)

// Отримати реєстр
GDT::registry(): object

// Швидкий доступ до об'єктів
GDT::config(): object
GDT::db(): object
GDT::request(): object
GDT::session(): object
GDT::url(): object
GDT::load(): object

// Запис у лог
GDT::logWrite(string $message)

// Перевірка ініціалізації
GDT::isInitialized(): bool
```

## Кешування

### Як працює кеш:

1. **Автоматична валідація**: Кеш перевіряється кожну годину та при зміні файлів розширень
2. **MD5 хеш файлів**: Система відслідковує зміни через хеш часу модифікації файлів
3. **Файловий формат**: Кеш зберігається як PHP файл для швидкого `include()`

### Структура кешу:

```php
[
    'timestamp' => 1699724400,           // Час створення
    'files_hash' => 'abc123...',         // MD5 хеш всіх файлів розширень
    'hooks' => [
        [
            'type' => 'extension',
            'extension_type' => 'module',
            'extension_code' => 'my_module',
            'path' => '/path/to/controller.php',
            'route' => 'extension/module/my_module'
        ],
        // ...
    ]
]
```

### Очищення кешу:

```php
// Вручну
\GbitStudio\GDT\Engine\HookCache::clear();

// Автоматично при:
// - Встановленні розширення
// - Видаленні розширення
// - Активації/деактивації розширення
```

## Налагодження

### Перевірка логів:

Всі дії системи записуються у лог OpenCart (`system/storage/logs/error.log`):

```
[GDT Hook] Loaded hook_boot from extension: module/my_module
[GDT Hook] hook_boot method must be public in extension/module/test
[GDT Hook] Error loading hook_boot from extension/payment/custom: ...
```

### Перевірка кешу:

```php
$info = \GbitStudio\GDT\Engine\HookCache::getInfo();
print_r($info);
// Виведе:
// [
//     'exists' => true,
//     'path' => '/path/to/cache/hook_boot_cache.php',
//     'size' => 1234,
//     'created' => '2025-11-11 15:30:00',
//     'hooks_count' => 5,
//     'valid' => true
// ]
```

### Типові помилки:

1. **"hook_boot method must be public"**
   - ❌ Неправильно: `private function hook_boot()`
   - ✅ Правильно: `public function hook_boot()`

2. **"Error loading hook_boot from extension/..."**
   - Перевірте чи існує контролер
   - Перевірте правильність імені класу
   - Перевірте синтаксис PHP

3. **Хуки не спрацьовують**
   - Перевірте чи встановлено і активовано розширення
   - Очистіть кеш: `HookCache::clear()`
   - Перевірте логи на наявність помилок

## Переваги нової системи

### Раніше (старий підхід):
```
admin/controller/hook/my_module.php          ← окремий файл хука
admin/controller/extension/module/my_module.php  ← основний контролер
```
❌ 2 файли для підтримки  
❌ Ручне створення хуків  
❌ Потрібно відслідковувати зміни  

### Тепер (новий підхід):
```
admin/controller/extension/module/my_module.php  ← все в одному файлі
```
✅ 1 файл замість 2  
✅ Автоматичне виявлення  
✅ Швидке кешування  
✅ Перевірка через Reflection API  
✅ Підтримка всіх типів розширень  

## Сумісність

- ✅ OpenCart 3.x
- ✅ OpenCart 4.x (потребує тестування)
- ✅ PHP 7.0+
- ✅ PHP 8.0+
- ✅ Працює в admin і catalog частинах
- ✅ Сумісно зі старою системою хуків (controller/hook/)

## Технічні деталі

### Використані технології:

- **PHP Reflection API**: Для перевірки наявності методів
- **MD5 хешування**: Для відслідковування змін файлів
- **OpenCart Loader**: Для завантаження контролерів
- **Файлове кешування**: Для швидкої ініціалізації

### Продуктивність:

- 🚀 Перша ініціалізація: ~100-200ms (сканування всіх розширень)
- ⚡ З кешем: ~5-10ms (швидке завантаження)
- 💾 Розмір кешу: ~1-5KB залежно від кількості розширень

## Приклад використання

Дивіться повний приклад у файлі:  
`example/upload/admin/controller/extension/module/example_hook.php`

## Ліцензія

GNU General Public License version 3

## Автор

GbitStudio / GregoryBiter
