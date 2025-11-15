# 🛣️ ДОРОЖНАЯ КАРТА РЕАЛИЗАЦИИ (ROADMAP)
## Система GDT Hook для OpenCart v2.0

---

## ФАЗА 1: КРИТИЧЕСКОЕ ОБНОВЛЕНИЕ (v1.5)
### Временные рамки: 2-3 недели
### Приоритет: 🔴 КРИТИЧЕСКИЙ

### 1.1 Обработка исключений в hook_boot() ⚠️ СРОЧНО

**Файл**: `upload/system/library/gbitstudio/gdt/engine/hook.php`

**Текущий код** (ПРОБЛЕМНЫЙ):
```php
$controller->hook_boot();
return true;
```

**Новый код**:
```php
try {
    $controller->hook_boot();
} catch (\Throwable $e) {
    self::$registry->get('log')->write(
        "[GDT Hook Error] {$hook_data['route']}: " . $e->getMessage() 
        . " in " . $e->getFile() . ":" . $e->getLine()
    );
    
    // Событие об ошибке
    self::do_action('gdt_hook_error', [
        'extension' => $hook_data['route'],
        'error' => $e,
        'type' => get_class($e)
    ]);
    
    // Продолжаем выполнение несмотря на ошибку
    return true;
}
return true;
```

**Тестирование**:
```php
// test_error_handling.php
$hook = new Hook();
Hook::add_action('test_error', function() {
    throw new Exception("Test error");
});
Hook::do_action('test_error'); // Должно логировать, но не падать
```

---

### 1.2 Система удаления хуков ✂️ ВАЖНО

**Файл**: `upload/system/library/gbitstudio/gdt/engine/hook.php`

**Методы для добавления**:

```php
/**
 * Удаление action хука
 */
public static function remove_action($hook_name, $callback = null) {
    return self::remove_hook('action', $hook_name, $callback);
}

/**
 * Удаление filter хука
 */
public static function remove_filter($hook_name, $callback = null) {
    return self::remove_hook('filter', $hook_name, $callback);
}

/**
 * Удаление хука
 */
private static function remove_hook($type, $hook_name, $callback = null) {
    if ($type === 'action') {
        $target = &self::$actions;
    } else {
        $target = &self::$filters;
    }
    
    if (!isset($target[$hook_name])) {
        return false;
    }
    
    if ($callback === null) {
        unset($target[$hook_name]);
        return true;
    }
    
    // Удаляем конкретный callback
    $found = false;
    foreach ($target[$hook_name] as $priority => &$callbacks) {
        foreach ($callbacks as $key => $hook) {
            if (self::compare_callbacks($hook['function'], $callback)) {
                unset($callbacks[$key]);
                $found = true;
            }
        }
        
        // Удаляем пустые приоритеты
        if (empty($callbacks)) {
            unset($target[$hook_name][$priority]);
        }
    }
    
    // Удаляем пустые хуки
    if (empty($target[$hook_name])) {
        unset($target[$hook_name]);
    }
    
    return $found;
}

/**
 * Получить все callback-ы для хука
 */
public static function get_callbacks($hook_name, $type = 'action') {
    if ($type === 'action') {
        return self::$actions[$hook_name] ?? [];
    } else {
        return self::$filters[$hook_name] ?? [];
    }
}

/**
 * Сравнение callback-ов
 */
private static function compare_callbacks($callback1, $callback2) {
    if (is_string($callback1) && is_string($callback2)) {
        return $callback1 === $callback2;
    }
    
    if (is_array($callback1) && is_array($callback2)) {
        $class1 = is_object($callback1[0]) ? get_class($callback1[0]) : $callback1[0];
        $class2 = is_object($callback2[0]) ? get_class($callback2[0]) : $callback2[0];
        return $class1 === $class2 && $callback1[1] === $callback2[1];
    }
    
    return spl_object_hash($callback1) === spl_object_hash($callback2);
}
```

**Использование**:
```php
// Удалить конкретный callback
Hook::remove_action('my_action', [$this, 'myHandler']);

// Удалить все callback-ы для хука
Hook::remove_action('my_action');

// Получить все callback-ы
$callbacks = Hook::get_callbacks('my_action');
```

---

### 1.3 Валидация callback-ов 🔍 ВАЖНО

**Файл**: `upload/system/library/gbitstudio/gdt/engine/hook.php`

**Добавить в add_hook**:

```php
private static function add_hook($type, $hook_name, $callback, $priority, $accepted_args) {
    // Валидация параметров
    if (!is_string($hook_name) || empty($hook_name)) {
        throw new \InvalidArgumentException("Hook name must be a non-empty string");
    }
    
    if (!is_int($priority)) {
        throw new \InvalidArgumentException("Priority must be an integer");
    }
    
    if (!is_int($accepted_args) || $accepted_args < 0) {
        throw new \InvalidArgumentException("Accepted args must be 0 or positive integer");
    }
    
    // Валидация callback-а
    if (!self::is_valid_callback($callback)) {
        throw new \InvalidArgumentException(
            "Invalid callback: " . self::callback_to_string($callback)
        );
    }
    
    // ... существующий код ...
}

/**
 * Проверка валидности callback-а
 */
private static function is_valid_callback($callback) {
    // Функции как строки
    if (is_string($callback)) {
        if (!function_exists($callback)) {
            return false;
        }
        return true;
    }
    
    // Методы класса
    if (is_array($callback)) {
        if (count($callback) !== 2) {
            return false;
        }
        
        list($class, $method) = $callback;
        
        // Инстанс объекта
        if (is_object($class)) {
            return method_exists($class, $method);
        }
        
        // Имя класса
        if (is_string($class)) {
            if (!class_exists($class)) {
                return false;
            }
            return method_exists($class, $method);
        }
        
        return false;
    }
    
    // Callable объекты (closure, __invoke)
    if (is_callable($callback)) {
        return true;
    }
    
    return false;
}

/**
 * Конвертировать callback в строку
 */
private static function callback_to_string($callback) {
    if (is_string($callback)) {
        return "function '{$callback}'";
    }
    
    if (is_array($callback)) {
        $class = is_object($callback[0]) ? get_class($callback[0]) : $callback[0];
        return "{$class}::{$callback[1]}()";
    }
    
    if (is_object($callback) && method_exists($callback, '__invoke')) {
        return get_class($callback) . "::__invoke()";
    }
    
    return "Closure";
}
```

---

### 1.4 Улучшенное логирование 📝 ВАЖНО

**Файл**: `upload/system/library/gbitstudio/gdt/engine/hook.php`

**Добавить метод**:

```php
public static function get_debug_info() {
    return [
        'actions_count' => count(self::$actions),
        'filters_count' => count(self::$filters),
        'actions' => self::format_hooks_info(self::$actions),
        'filters' => self::format_hooks_info(self::$filters),
        'loaded_extensions' => self::$loaded_extensions ?? [],
    ];
}

private static function format_hooks_info($hooks) {
    $info = [];
    foreach ($hooks as $name => $priorities) {
        $callbacks = [];
        foreach ($priorities as $priority => $cbs) {
            foreach ($cbs as $cb) {
                $callbacks[] = [
                    'function' => self::callback_to_string($cb['function']),
                    'priority' => $priority,
                    'args' => $cb['accepted_args']
                ];
            }
        }
        $info[$name] = $callbacks;
    }
    return $info;
}
```

---

## ФАЗА 2: СТАБИЛЬНОСТЬ (v1.8)
### Временные рамки: 3-4 недели
### Приоритет: 🟠 ВЫСОКИЙ

### 2.1 Система зависимостей между хуками

**Файл**: `upload/system/library/gbitstudio/gdt/engine/hook.php`

```php
private static $loaded_extensions = [];
private static $pending_dependencies = [];

/**
 * Требовать расширение перед регистрацией хуков
 */
public static function require_extension($extension_code, $callback = null) {
    if (self::extension_loaded($extension_code)) {
        if ($callback !== null) {
            call_user_func($callback);
        }
        return true;
    }
    
    if ($callback !== null) {
        if (!isset(self::$pending_dependencies[$extension_code])) {
            self::$pending_dependencies[$extension_code] = [];
        }
        self::$pending_dependencies[$extension_code][] = $callback;
    }
    
    return false;
}

/**
 * Проверить загружено ли расширение
 */
public static function extension_loaded($extension_code) {
    return isset(self::$loaded_extensions[$extension_code]);
}

/**
 * Зарегистрировать расширение как загруженное
 */
private static function mark_extension_loaded($extension_code) {
    self::$loaded_extensions[$extension_code] = true;
    
    // Вызываем ожидающие callback-ы
    if (isset(self::$pending_dependencies[$extension_code])) {
        foreach (self::$pending_dependencies[$extension_code] as $callback) {
            try {
                call_user_func($callback);
            } catch (\Throwable $e) {
                self::$registry->get('log')->write(
                    "[GDT Hook] Dependency callback error for {$extension_code}: " . $e->getMessage()
                );
            }
        }
        unset(self::$pending_dependencies[$extension_code]);
    }
}
```

### 2.2 События жизненного цикла

**Файл**: `upload/system/library/gbitstudio/gdt/engine/hook.php`

**В методе load()**:

```php
public static function load(Registry $registry) {
    self::$registry = $registry;
    
    // Начало инициализации
    self::do_action('gdt_hook_init');
    
    // ... существующий код ...
    
    // Сканирование
    self::do_action('gdt_hook_scanning');
    
    // После загрузки расширений
    self::do_action('gdt_hook_loaded');
    
    // Полная готовность
    self::do_action('gdt_hook_ready');
}
```

---

## ФАЗА 3: РАСШИРЕННАЯ ФУНКЦИОНАЛЬНОСТЬ (v2.0)
### Временные рамки: 4-5 недель
### Приоритет: 🟡 СРЕДНИЙ

### 3.1 API управления хуками

**Файл**: `admin/controller/extension/gdt/hook_manager.php` (НОВЫЙ)

```php
<?php
class ControllerExtensionGdtHookManager extends Controller {
    
    public function getHooks() {
        $this->load->model('setting/setting');
        
        $debug_info = \GbitStudio\GDT\Engine\Hook::get_debug_info();
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($debug_info));
    }
    
    public function removeHook() {
        if (!$this->user->hasPermission('modify', 'extension/gdt/hook_manager')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        if (!$this->error) {
            $hook_name = $this->request->post['hook_name'] ?? '';
            $callback = $this->request->post['callback'] ?? '';
            
            \GbitStudio\GDT\Engine\Hook::remove_action($hook_name, $callback);
            
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode(['success' => true]));
        }
    }
}
```

---

## 📊 ЧЕКЛИСТ РЕАЛИЗАЦИИ

### Фаза 1 ✅
- [ ] Обработка исключений в hook_boot
- [ ] Система удаления хуков
- [ ] Валидация callback-ов
- [ ] Улучшенное логирование
- [ ] Тестирование фазы 1
- [ ] Релиз v1.5

### Фаза 2 ⏳
- [ ] Система зависимостей
- [ ] События жизненного цикла
- [ ] Условная активация
- [ ] Улучшенное кеширование
- [ ] Тестирование фазы 2
- [ ] Релиз v1.8

### Фаза 3 ⏳
- [ ] API управления
- [ ] Система отладки
- [ ] Документация
- [ ] Примеры
- [ ] Тестирование фазы 3
- [ ] Релиз v2.0

---

## 🧪 ТЕСТИРОВАНИЕ ДЛЯ КАЖДОЙ ФАЗЫ

### Модульные тесты (PHPUnit)

**Файл**: `tests/HookTest.php`

```php
<?php
use GbitStudio\GDT\Engine\Hook;

class HookTest extends \PHPUnit\Framework\TestCase {
    
    public function setUp(): void {
        Hook::remove_all();
    }
    
    // Фаза 1 тесты
    
    public function testAddAndRemoveAction() {
        Hook::add_action('test', [$this, 'testCallback']);
        $this->assertTrue(Hook::has_action('test'));
        
        Hook::remove_action('test', [$this, 'testCallback']);
        $this->assertFalse(Hook::has_action('test'));
    }
    
    public function testInvalidCallbackThrows() {
        $this->expectException(\InvalidArgumentException::class);
        Hook::add_action('test', 'non_existent_function');
    }
    
    public function testHookBootErrorHandling() {
        // Тест что ошибка в hook_boot не ломает систему
        // ...
    }
    
    // Фаза 2 тесты
    
    public function testExtensionDependencies() {
        $called = false;
        
        Hook::require_extension('missing_ext', function() use (&$called) {
            $called = true;
        });
        
        $this->assertFalse($called);
    }
    
    public function testLifecycleEvents() {
        $events = [];
        
        Hook::add_action('gdt_hook_init', function() use (&$events) {
            $events[] = 'init';
        });
        
        // ... проверяем события ...
    }
    
    // Фаза 3 тесты
    
    public function testHookManager() {
        // Тест API управления хуками
        // ...
    }
    
    public function testCallbackToString() {
        $result = Hook::callback_to_string(['SomeClass', 'method']);
        $this->assertStringContainsString('SomeClass::method', $result);
    }
    
    public function testCallback() {
        return 'test_result';
    }
}
```

---

## 📋 ВЕРСИЯ 2.0 ФИНАЛЬНЫЙ ЧЕКЛИСТ

### Код
- [ ] Все 12 улучшений реализовано
- [ ] 100% покрытие тестами
- [ ] PHPStan анализ пройден
- [ ] Нет ошибок с уровнем E_ERROR

### Документация
- [ ] IMPROVEMENT_ANALYSIS.md
- [ ] ROADMAP.md (этот файл)
- [ ] DEVELOPMENT_GUIDE.md
- [ ] BEST_PRACTICES.md
- [ ] API_REFERENCE.md
- [ ] TROUBLESHOOTING.md

### Примеры
- [ ] example_simple.php
- [ ] example_advanced.php
- [ ] example_errors.php
- [ ] example_dependencies.php
- [ ] example_debugging.php

### Релиз
- [ ] Git tags для каждой версии
- [ ] CHANGELOG.md обновлен
- [ ] opencart-module.json обновлен
- [ ] Версия в hook.php обновлена

---

## 📞 КОНТАКТЫ И ПОДДЕРЖКА

При возникновении вопросов по реализации:
1. Проверить документацию в папке `/docs`
2. Посмотреть примеры в `/examples`
3. Запустить тесты: `phpunit tests/`
4. Проверить логи: `system/storage/logs/error.log`

---

**Статус**: 🟢 ГОТОВО К РЕАЛИЗАЦИИ  
**Последнее обновление**: 16 листопада 2025  
**Версия**: 1.0
