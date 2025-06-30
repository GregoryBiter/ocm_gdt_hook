<?php
namespace GbitStudio\GDT;

/**
 * Трейт ToolsController для обратной совместимости
 * Предоставляет доступ к методам класса Tools
 */
trait ToolsController {
    
    /** @var Tools */
    private $tools;
    
    /**
     * Инициализирует инструменты
     */
    private function initTools() {
        if (!$this->tools) {
            $this->tools = new Tools($this->registry);
            if (isset($this->error)) {
                $this->tools->setErrorsArray($this->error);
            }
            if (property_exists($this, 'route') && $this->route) {
                $this->tools->setModuleRoute($this->route);
            }
        }
    }
    
    /**
     * Вызывает метод из класса Tools
     */
    private function callToolsMethod($method, $args = []) {
        $this->initTools();
        return call_user_func_array([$this->tools, $method], $args);
    }
    
    // Делегируем все методы к классу Tools
    protected function errorTest($key, &$data, $defaultValue = '') {
        return $this->callToolsMethod('errorTest', [$key, &$data, $defaultValue]);
    }
    
    protected function errorTests($keys, &$data) {
        return $this->callToolsMethod('errorTests', [$keys, &$data]);
    }
    
    protected function getUrlSort() {
        return $this->callToolsMethod('getUrlSort');
    }
    
    protected function getActionAndCancel(&$data, $url, $key) {
        return $this->callToolsMethod('getActionAndCancel', [&$data, $url, $key]);
    }
    
    protected function getBreadcrumbs(&$data, $url) {
        return $this->callToolsMethod('getBreadcrumbs', [&$data, $url]);
    }
    
    protected function link($route, $params = '') {
        return $this->callToolsMethod('link', [$route, $params]);
    }
    
    protected function isAdmin() {
        return $this->callToolsMethod('isAdmin');
    }
    
    protected function getLanguage($key) {
        return $this->callToolsMethod('getLanguage', [$key]);
    }
    
    protected function getLanguageArray($keys) {
        return $this->callToolsMethod('getLanguageArray', [$keys]);
    }
    
    protected function isPost() {
        return $this->callToolsMethod('isPost');
    }
    
    protected function setDataValue($key, $value, $defaultValue = null) {
        return $this->callToolsMethod('setDataValue', [$key, $value, $defaultValue]);
    }
    
    protected function getSetting(array $data = []) {
        return $this->callToolsMethod('getSetting', [$data]);
    }
    
    protected function getData($key, $default = false) {
        return $this->callToolsMethod('getData', [$key, $default]);
    }
    
    protected function isAjax() {
        return $this->callToolsMethod('isAjax');
    }
    
    protected function isJson() {
        return $this->callToolsMethod('isJson');
    }
    
    protected function responseJson($data) {
        return $this->callToolsMethod('responseJson', [$data]);
    }
    
    protected function responseJsonError($message) {
        return $this->callToolsMethod('responseJsonError', [$message]);
    }
    
    protected function responseView($view, $data) {
        return $this->callToolsMethod('responseView', [$view, $data]);
    }
    
    protected function getLayout(&$data) {
        return $this->callToolsMethod('getLayout', [&$data]);
    }
    
    protected function getUrlToSortOrderPage($url) {
        return $this->callToolsMethod('getUrlToSortOrderPage', [$url]);
    }
    
    protected function setFlashMessages(&$data) {
        return $this->callToolsMethod('setFlashMessages', [&$data]);
    }
    
    protected function setErrors(&$data) {
        return $this->callToolsMethod('setErrors', [&$data]);
    }
    
    protected function setSuccess(&$data) {
        return $this->callToolsMethod('setSuccess', [&$data]);
    }
    
    protected function setSelectedItems(&$data) {
        return $this->callToolsMethod('setSelectedItems', [&$data]);
    }
    
    protected function getDataRequestOrItem($key, $item, $default = null) {
        return $this->callToolsMethod('getDataRequestOrItem', [$key, $item, $default]);
    }
}