# Примеры использования register_event

## Что это даёт?

Метод `register_event()` позволяет удобно вешать свои callback-функции на **встроенные события OpenCart**, которые обычно поддерживают только вызов контроллеров через стандартную систему событий.

**Главная особенность**: Ваши callback получают аргументы **по ссылке**, что позволяет напрямую модифицировать данные, как это делается в стандартных событиях OpenCart.

## Как это работает?

1. В `system/engine/event.php` через OCMOD добавлен вызов `Hook::trigger_oc_event($event, $args)` перед выполнением встроенных событий
2. Этот метод запускает action-хуки для события с передачей аргументов по ссылке
3. Вы регистрируете свои callback через `Hook::register_event()`, которые будут вызваны автоматически
4. Ваши callback получают аргументы по ссылке и могут их изменять

## Примеры использования

### Пример 1: Модификация данных перед отправкой в view (передача по ссылке)

```php
// В методе hook_boot() вашего расширения
public function hook_boot() {
    // Регистрируем обработчик на событие просмотра категории
    \GbitStudio\GDT\Engine\Hook::register_event(
        'catalog/view/product/category/before',
        function(&$route, &$data, &$template) {
            // ВАЖНО: параметры по ссылке (&) позволяют изменять данные
            $data['custom_message'] = 'Привет из hook!';
            $data['extra_data'] = [
                'foo' => 'bar'
            ];
        },
        10 // приоритет
    );
}
```

### Пример 2: Логирование и модификация данных заказа

```php
public function hook_boot() {
    \GbitStudio\GDT\Engine\Hook::register_event(
        'catalog/model/checkout/order/addOrder/before',
        function(&$route, &$data) {
            // Логируем создание заказа
            error_log("Создаётся заказ: " . print_r($data, true));
            
            // Можем модифицировать данные заказа перед сохранением
            if (!isset($data['custom_field'])) {
                $data['custom_field'] = 'Added by hook';
            }
        }
    );
    
    // После создания заказа
    \GbitStudio\GDT\Engine\Hook::register_event(
        'catalog/model/checkout/order/addOrder/after',
        function(&$route, &$args, &$order_id) {
            // $order_id содержит ID созданного заказа
            error_log("Создан новый заказ #$order_id");
        }
    );
}
```

### Пример 3: Модификация меню админки

```php
public function hook_boot() {
    \GbitStudio\GDT\Engine\Hook::register_event(
        'admin/view/common/column_left/before',
        function(&$route, &$data) {
            // Добавляем свой пункт меню напрямую в $data
            if (!isset($data['menus'])) {
                $data['menus'] = [];
            }
            
            $data['menus'][] = [
                'id' => 'menu-custom',
                'icon' => 'fa-star',
                'name' => 'Мой пункт меню',
                'href' => '',
                'children' => [
                    [
                        'name' => 'Подпункт 1',
                        'href' => 'extension/module/my_module',
                        'children' => []
                    ]
                ]
            ];
        },
        5 // более высокий приоритет (выполнится раньше)
    );
}
```

### Пример 4: Множественные обработчики с разными приоритетами

```php
public function hook_boot() {
    // Первый обработчик с приоритетом 5 (выполнится раньше)
    \GbitStudio\GDT\Engine\Hook::register_event(
        'catalog/model/catalog/product/getProduct/after',
        function(&$route, &$args, &$product) {
            error_log('Обработчик 1: приоритет 5');
            // Модифицируем данные продукта
            if (is_array($product)) {
                $product['processed_by_hook_1'] = true;
            }
        },
        5
    );
    
    // Второй обработчик с приоритетом 15 (выполнится позже)
    \GbitStudio\GDT\Engine\Hook::register_event(
        'catalog/model/catalog/product/getProduct/after',
        function(&$route, &$args, &$product) {
            error_log('Обработчик 2: приоритет 15');
            // Видим изменения от первого обработчика
            if (isset($product['processed_by_hook_1'])) {
                $product['processed_by_hook_2'] = true;
            }
        },
        15
    );
}
```

### Пример 5: Использование в классе контроллера

```php
namespace GbitStudio\MyExtension;

class ControllerExtensionModuleMyModule extends \Controller {
    
    public function hook_boot() {
        // Привязываем метод класса
        \GbitStudio\GDT\Engine\Hook::register_event(
            'catalog/view/product/product/before',
            [$this, 'onProductView'],
            10
        );
    }
    
    public function onProductView(&$route, &$data, &$template) {
        // ВАЖНО: параметры по ссылке (&)
        // Можем использовать $this->registry и другие методы класса
        $product_id = $this->request->get['product_id'] ?? 0;
        
        if ($product_id) {
            // Загружаем дополнительные данные
            $this->load->model('catalog/product');
            $custom_data = $this->model_catalog_product->getProduct($product_id);
            
            // Модифицируем $data напрямую
            $data['custom_field'] = $custom_data;
            $data['viewed_at'] = date('Y-m-d H:i:s');
        }
    }
}
```

### Пример 6: Модификация данных продукта с валидацией

```php
public function hook_boot() {
    \GbitStudio\GDT\Engine\Hook::register_event(
        'admin/model/catalog/product/editProduct/before',
        function(&$route, &$product_id, &$data) {
            // Валидация перед сохранением
            if (isset($data['price']) && $data['price'] < 0) {
                throw new \Exception('Цена не может быть отрицательной');
            }
            
            // Автоматическое форматирование
            if (isset($data['name'])) {
                $data['name'] = trim($data['name']);
            }
            
            // Добавление timestamp
            $data['modified'] = date('Y-m-d H:i:s');
        }
    );
}
```

## Доступные события OpenCart

Вы можете вешать обработчики на любые встроенные события OpenCart:

### События моделей
- `catalog/model/*/before` - перед выполнением метода модели
- `catalog/model/*/after` - после выполнения метода модели
- `admin/model/*/before`
- `admin/model/*/after`

### События view (шаблонов)
- `catalog/view/*/before` - перед рендерингом шаблона
- `catalog/view/*/after` - после рендерингом шаблона
- `admin/view/*/before`
- `admin/view/*/after`

### События контроллеров
- `catalog/controller/*/before` - перед выполнением контроллера
- `catalog/controller/*/after` - после выполнения контроллера
- `admin/controller/*/before`
- `admin/controller/*/after`

## Преимущества перед стандартной системой событий

1. **Callback-функции вместо контроллеров** - не нужно создавать отдельные файлы контроллеров
2. **Приоритеты** - контроль порядка выполнения обработчиков
3. **Модификация данных** - можно изменять и возвращать аргументы
4. **Чистый код** - всё в одном месте (в методе `hook_boot()`)
5. **Совместимость** - работает со всеми встроенными событиями OpenCart

## Отладка

Для отладки можете использовать встроенное логирование:

```php
\GbitStudio\GDT\Engine\Hook::register_event(
    'catalog/view/product/product/before',
    function($args) {
        // Записываем в лог OpenCart
        error_log('Event triggered: ' . print_r($args, true));
        return $args;
    }
);
```

## Полный пример расширения

```php
<?php
class ControllerExtensionModuleExampleHook extends \Controller {
    
    public function hook_boot() {
        // Модификация данных продукта
        \GbitStudio\GDT\Engine\Hook::register_event(
            'catalog/model/catalog/product/getProduct/after',
            [$this, 'modifyProduct'],
            10
        );
        
        // Добавление пункта меню
        \GbitStudio\GDT\Engine\Hook::register_event(
            'admin/view/common/column_left/before',
            [$this, 'addMenuItem'],
            5
        );
        
        // Логирование заказов
        \GbitStudio\GDT\Engine\Hook::register_event(
            'catalog/model/checkout/order/addOrder/after',
            [$this, 'logOrder']
        );
    }
    
    public function modifyProduct($args) {
        // Добавляем свои данные к продукту
        return $args;
    }
    
    public function addMenuItem($args) {
        // Добавляем пункт меню
        return $args;
    }
    
    public function logOrder($args) {
        // Логируем заказ
        return $args;
    }
}
```
