<?php
namespace GbitStudio\GDT;

/**
 * Класс Events для управления событиями OpenCart
 * 
 * Предоставляет методы для добавления, удаления и обновления
 * событий в системе OpenCart.
 */
class Events{

    /** @var object $registry Реестр OpenCart */
    public $registry;
    
    /**
     * Конструктор класса Events
     *
     * @param object $registry Реестр OpenCart
     */
    public function __construct($registry)
    {
        $this->registry = $registry;
        $this->registry->get('load')->model('setting/event');
    }

    /** @var array $events Массив событий */
    public $events = [];

    /**
     * Добавляет событие в массив событий
     *
     * @param array $events Массив с информацией о событии
     * @return void
     */
    public function setEvent($events){
        $this->events[] = $events;
    }
    
    /**
     * Сбрасывает массив событий
     *
     * @return void
     */
    public function resetEvent(){
        $this->events = [];
    }

    /**
     * Устанавливает события в системе OpenCart
     *
     * @param array $events Массив с информацией о событиях
     * @return void
     */
    public function installEvents($events = []){
        foreach($events as $event){
            $event['status'] = isset($event['status']) ? $event['status'] : 1;
            $event['sort_order'] = isset($event['sort_order']) ? $event['sort_order'] : 0;
            $this->registry->get('model_setting_event')->addEvent($event['code'], $event['trigger'], $event['action'], $event['status'], $event['sort_order']);
        }
    }

    /**
     * Удаляет события из системы OpenCart
     *
     * @param array $events Массив с информацией о событиях
     * @return void
     */
    public function uninstallEvents($events = []){
        foreach($events as $event){
            $this->registry->get('model_setting_event')->deleteEventByCode($event['code']);
        }
    }

    /**
     * Обновляет события в системе OpenCart (сначала удаляет, потом устанавливает)
     *
     * @param array $events Массив с информацией о событиях
     * @return void
     */
    public function refrashEvents($events = []){
        $this->uninstallEvents($events);
        $this->installEvents($events);
    }

    /**
     * Добавляет событие для включения пункта в меню администратора
     *
     * @param string $name_module Название модуля
     * @param string $controller Путь к контроллеру
     * @return void
     */
    public function onMenu(string $name_module, string $controller){
        $this->registry->get('model_setting_event')->addEvent($name_module. '_menuadmin', 'admin/view/common/column_left/before', $controller, 1);
    }
    
    /**
     * Удаляет событие для пункта меню администратора
     *
     * @param string $name_module Название модуля
     * @return void
     */
    public function offMenu(string $name_module){
        $this->registry->get('model_setting_event')->deleteEventByCode($name_module. '_menuadmin');
    }

    /**
     * Устанавливает события для обработки Vue-скриптов
     *
     * @return void
     */
    public function setupVueEvents() {
        $vueEvents = [
            [
                'code' => 'gdt_vue_catalog',
                'trigger' => 'catalog/view/*/after',
                'action' => 'event/gdt_vue/addVueScripts',
                'status' => 1,
                'sort_order' => 0
            ],
            [
                'code' => 'gdt_vue_admin',
                'trigger' => 'admin/view/*/after',
                'action' => 'event/gdt_vue/addVueScripts',
                'status' => 1,
                'sort_order' => 0
            ],
            [
                'code' => 'gdt_vue_startup',
                'trigger' => 'startup/startup/before',
                'action' => 'event/gdt_vue',
                'status' => 1,
                'sort_order' => 0
            ]
        ];
        
        $this->installEvents($vueEvents);
    }
    
    /**
     * Удаляет события для обработки Vue-скриптов
     *
     * @return void
     */
    public function removeVueEvents() {
        $vueEvents = [
            [
                'code' => 'gdt_vue_catalog'
            ],
            [
                'code' => 'gdt_vue_admin'
            ],
            [
                'code' => 'gdt_vue_startup'
            ]
        ];
        
        $this->uninstallEvents($vueEvents);
    }
}