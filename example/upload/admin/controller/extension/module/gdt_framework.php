<?php
use GbitStudio\GDT\Engine\GDT;
use GbitStudio\GDT\Engine\HookMetaParser;

class ControllerExtensionModuleGDTFramework extends Controller {
    private $error = [];

    public function index() {
        $this->load->language('extension/module/hook_manager');

        $this->document->setTitle($this->language->get('heading_title'));

        // Загружаем метаданные всех хуков
        $hooks_meta = HookMetaParser::getAllHooksMeta();

        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/hook_manager', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['hooks'] = [];
        foreach ($hooks_meta as $hook_name => $meta) {
            $data['hooks'][] = [
                'name' => $meta['name'],
                'description' => $meta['description'],
                'version' => $meta['version'],
                'author' => $meta['author'],
                'controller' => $meta['controller'],
                'context' => isset($meta['context']) ? $meta['context'] : 'unknown',
                'status' => $this->config->get('module_' . basename($meta['file_path'], '.php') . '_status') ? 'Enabled' : 'Disabled',
                'file_path' => $meta['file_path']
            ];
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/hook_manager', $data));
    }

    public function refresh() {
        // Перезагружаем хуки
        \GbitStudio\GDT\Engine\Hook::remove_all();
        \GbitStudio\GDT\Engine\Hook::load();

        $this->session->data['success'] = 'Hooks refreshed successfully!';
        $this->response->redirect($this->url->link('extension/module/hook_manager', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function toggle() {
        $hook_name = $this->request->get['hook'] ?? '';
        
        if ($hook_name) {
            $current_status = $this->config->get('module_' . $hook_name . '_status');
            $new_status = !$current_status;
            
            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('module_' . $hook_name, [
                'module_' . $hook_name . '_status' => $new_status
            ]);

            $status_text = $new_status ? 'enabled' : 'disabled';
            $this->session->data['success'] = "Hook '{$hook_name}' has been {$status_text}!";
        }

        $this->response->redirect($this->url->link('extension/module/hook_manager', 'user_token=' . $this->session->data['user_token'], true));
    }
}
