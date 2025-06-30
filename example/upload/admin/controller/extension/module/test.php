<?php
use GbitStudio\GDT\Engine\Controller;
use GbitStudio\GDT\Tools;
class ControllerExtensionModuleTest extends Controller
{

    public const ROUTE = 'extension/module/test';
    public const NAME = 'test';
    public function index()
    {
        // Используем Language Manager с точечной нотацией напрямую, без $this->app
        //$data['heading_title'] = $this->lang->get('extension/module/test.heading_title');
        $this->load->language(self::ROUTE, self::NAME);
        $data = [
            'heading_title' => __('test.heading_title'),
            'text_edit' => __('test.text_edit'),
            'entry_status' => __('test.entry_status'),
            'button_save' => __('test.button_save'),
            'button_cancel' => __('test.button_cancel'),
        ];
        
        // Используем формирование хлебных крошек в Laravel-стиле
        $this->document->setTitle($data['heading_title']);
        
        $data['breadcrumbs'] = [
            [
                'text' => __('common/dashboard.heading_title'),
                'href' => route('common/dashboard')
            ],
            [
                'text' => __('marketplace/extension.heading_title'),
                'href' => route('marketplace/extension', ['type'=> 'module'])
            ],
            [
                'text' => $data['heading_title'],
                'href' => route(self::ROUTE)
            ]
        ];
        
        // Формирование действий
        $data['action'] = route('extension/module/test/save');
        $data['cancel'] = route('marketplace/extension', ['type'=> 'module']);
        
        // Получение настроек из конфигурации
        $data['status'] = $this->config->get('test_status') ?? 0;
        
        // Отображение сообщения об успехе, если оно есть
        if ($this->gdt_session->has('success')) {
            $data['success'] = $this->gdt_session->get('success');
            $this->gdt_session->forget('success');
        } else {
            $data['success'] = '';
        }
        
        // Отображение ошибок валидации, если они есть
        if ($this->gdt_session->has('error_warning')) {
            $data['error_warning'] = $this->gdt_session->get('error_warning');
            $this->gdt_session->forget('error_warning');
        } else {
            $data['error_warning'] = '';
        }

        Tools::getLayout($data);
        // Загрузка шаблона с использованием View renderer напрямую
        return response(view(self::ROUTE, $data));
    }
    
    /**
     * Метод для сохранения настроек модуля
     */
    public function save()
    {
        // Получаем все данные из запроса
        
        // Создаем экземпляр валидатора и задаем правила валидации 
        // (Laravel-подобный синтаксис правил)
        $validator = $this->app->make('validator', [
            'data' => $this->request->post(),
            'rules' => [
                'status' => 'required|boolean',
                // Здесь могут быть другие правила для различных полей
            ],
            'messages' => [
                'status.required' => __('extension/module/test.error_status_required'),
                'status.boolean' => __('extension/module/test.error_status_boolean'),
            ]
        ]);
        
        // Проверяем валидность данных
        if ($validator->fails()) {
            // Если валидация не пройдена, получаем ошибки
            $errors = $validator->errors();
            
            // Добавляем ошибки в сессию
            $this->gdt_session->put('error_warning', reset($errors)); // Берем первую ошибку
            
            // Перенаправляем назад на страницу настроек
            return redirect(route(self::ROUTE));
        }
        
        // Получаем статус из проверенных данных
        $status = (int)$this->request->input('status');
        
        // Сохраняем настройки в базе данных
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('test', [
            'test_status' => $status
        ]);
        
        // Добавляем сообщение об успешном сохранении
        $this->gdt_session->put('success', __('extension/module/test.text_success'));
        
        // Перенаправляем на список модулей
        return redirect(
            route(self::ROUTE)
        );
    }
    
    /**
     * Метод для установки модуля
     */
    public function install()
    {
        // Здесь логика для установки модуля
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('test', [
            'test_status' => 1
        ]);
        
        // Регистрируем события, если необходимо
    }
    
    /**
     * Метод для удаления модуля
     */
    public function uninstall()
    {
        // Здесь логика для удаления модуля
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('test');
        
        // Удаляем события, если были зарегистрированы
    }
}