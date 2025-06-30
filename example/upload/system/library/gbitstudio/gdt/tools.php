<?php
// opencart library
namespace GbitStudio\GDT;

/**
 * Класс Tools - универсальный набор инструментов для работы с OpenCart
 * Включает в себя методы для работы с URL, запросами, контроллерами и другими утилитами
 */
class Tools {
    private $registry;
    protected $error = [];
    protected $m_route = '';

    public function getLayout(array &$data, $application = 'admin') {
        if ($application === 'admin') {
            $data['header'] = $this->registry->get('load')->controller('common/header');
            $data['column_left'] = $this->registry->get('load')->controller('common/column_left');
            $data['footer'] = $this->registry->get('load')->controller('common/footer');
        } else {
            // Для каталога или других приложений можно добавить свои контроллеры
            $data['header'] = $this->registry->get('load')->controller('common/header');
            $data['column_left'] = $this->registry->get('load')->controller('common/column_left');
            $data['column_right'] = $this->registry->get('load')->controller('common/column_right');
            $data['content_top'] = $this->registry->get('load')->controller('common/content_top');
            $data['content_bottom'] = $this->registry->get('load')->controller('common/content_bottom');
            $data['footer'] = $this->registry->get('load')->controller('common/footer');
        }
    }

    public function __construct($registry) {
        $this->registry = $registry;
    }

    /**
     * Устанавливает маршрут модуля
     */
    public function setModuleRoute($route) {
        $this->m_route = $route;
        return $this;
    }

    /**
     * Устанавливает массив ошибок
     */
    public function setErrorsArray($errors) {
        $this->error = $errors;
        return $this;
    }
    



    // === Методы для контроллеров (из ToolsController) ===

    /**
     * Тестирует наличие ошибки и устанавливает соответствующее значение в данные
     */
    protected function errorTest($key, &$data, $defaultValue = '') {
        if (isset($this->error[$key])) {
            $data['error_' . $key] = $this->error[$key];
        } else {
            $data['error_' . $key] = $defaultValue;
        }
    }

    /**
     * Тестирует множественные ошибки
     */
    protected function errorTests($keys, &$data) {
        foreach ($keys as $key) {
            $this->errorTest($key, $data);
        }
    }

    /**
     * Получает URL параметры для сортировки
     */
    protected function getUrlSort() {
        $url = '';

        if (isset($this->registry->get('request')->get['sort'])) {
            $url .= '&sort=' . $this->registry->get('request')->get['sort'];
        }

        if (isset($this->registry->get('request')->get['order'])) {
            $url .= '&order=' . $this->registry->get('request')->get['order'];
        }

        if (isset($this->registry->get('request')->get['page'])) {
            $url .= '&page=' . $this->registry->get('request')->get['page'];
        }

        return $url;
    }

    /**
     * Устанавливает действие и кнопку отмены для форм
     */
    protected function getActionAndCancel(&$data, $url, $key) {
        $request = $this->registry->get('request');
        $session = $this->registry->get('session');
        $urlObj = $this->registry->get('url');

        if (!isset($request->get[$key])) {
            $data['action'] = $urlObj->link($this->m_route . '/add', 'user_token=' . $session->data['user_token'] . $url, true);
        } else {
            $data['action'] = $urlObj->link($this->m_route . '/edit', 'user_token=' . $session->data['user_token'] . '&' . $key . '=' . $request->get[$key] . $url, true);
        }

        $data['cancel'] = $urlObj->link($this->m_route, 'user_token=' . $session->data['user_token'] . $url, true);
    }



    /**
     * Получает массив языковых переменных
     */
    protected function getLanguageArray($keys) {
        $language = $this->registry->get('language');
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $language->get($key);
        }
        return $data;
    }

    /**
     * Устанавливает значение данных с проверкой POST запроса
     */
    protected function setDataValue($key, $value, $defaultValue = null) {
        $request = $this->registry->get('request');
        
        if (isset($request->post[$key]) && !empty($request->post[$key])) {
            return $request->post[$key];
        } elseif (!empty($value) && !is_null($value)) {
            return $value;
        } else {
            return $defaultValue;
        }
    }

    /**
     * Получает настройки по массиву ключей
     */
    protected function getSetting(array $data = []) {
        $result = [];
        foreach ($data as $key) {
            $result[$key] = $this->getData($key, null);
        }
        return $result;
    }

    /**
     * Получает данные из POST или конфигурации
     */
    protected function getData($key, $default = false) {
        $request = $this->registry->get('request');
        $config = $this->registry->get('config');

        if (isset($request->post[$key])) {
            return $request->post[$key];
        } 
        
        $data = $config->get($key);
        if($data) {
            return $config->get($key);
        } 
        return $default;
    }
    /**
     * Отправляет ответ с видом
     */
    protected function responseView($view, $data) {
        $response = $this->registry->get('response');
        $load = $this->registry->get('load');
        $response->setOutput($load->view($view, $data));
    }

    /**
     * Получает URL с параметрами сортировки, порядка и страницы
     */
    protected function getUrlToSortOrderPage($url) {
        $request = $this->registry->get('request');

        if (isset($request->get['sort'])) {
            $url .= '&sort=' . $request->get['sort'];
        }
        if (isset($request->get['order'])) {
            $url .= '&order=' . $request->get['order'];
        }
        if (isset($request->get['page'])) {
            $url .= '&page=' . $request->get['page'];
        }
        return $url;
    }

    /**
     * Устанавливает flash сообщения
     */
    protected function setFlashMessages(&$data) {
        $this->setErrors($data);
        $this->setSuccess($data);
    }

    /**
     * Устанавливает ошибки в данные
     */
    protected function setErrors(&$data) {
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
    }

    /**
     * Устанавливает сообщение об успехе
     */
    protected function setSuccess(&$data) {
        $session = $this->registry->get('session');
        if (isset($session->data['success'])) {
            $data['success'] = $session->data['success'];
            unset($session->data['success']);
        } else {
            $data['success'] = '';
        }
    }

    /**
     * Устанавливает выбранные элементы
     */
    protected function setSelectedItems(&$data) {
        $request = $this->registry->get('request');
        if (isset($request->post['selected'])) {
            $data['selected'] = (array)$request->post['selected'];
        } else {
            $data['selected'] = [];
        }
    }

    /**
     * Получает данные из запроса или элемента
     */
    protected function getDataRequestOrItem($key, $item, $default = null) {
        $request = $this->registry->get('request');
        if (isset($request->post[$key])) {
            return $request->post[$key];
        } elseif (isset($item[$key])) {
            return $item[$key];
        } else {
            return $default;
        }
    }
}