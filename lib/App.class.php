<?php

class App 
{
	/*Статическая функция Ini.
	Устанавливает подключение к БД
	и запускает статический метод данного класса web.
	*/
    public static function Init() 
    {
        date_default_timezone_set('Europe/Moscow');	//Установим временную зону по умолчанию для всех функций даты/времени в скрипте
		/*
		Вызовем метод getInstance() класса DB. Файл db.class.php 
		Используется метод get('db_user') класса Config с параметром который необходимо получить. Файл Config.class.php
		В указанном примере параметр db_user
		*/
        db::getInstance()->Connect(Config::get('db_user'), Config::get('db_password'), Config::get('db_base'));

		//CLI - интерфейс командной строки
		//php_sapi_name() == 'cli' - означает что скрипт запущен с командной строке
        if (php_sapi_name() !== 'cli' && isset($_SERVER) && isset($_GET)) { //Проверим, установленны ли переменные $_SERVER и $_GET
            self::web(isset($_GET['path']) ? $_GET['path'] : ''); 
        }
    }

    protected static function web($url)
    {
        $url = explode("/", $url); //Разбиваем строку с помощью разделителя

        if (isset($url[0])) { //Проверяем, установленна ли переменная
            $_GET['page'] = $url[0];
            if (isset($url[1])) {
                if (is_numeric($url[1])) {
                    $_GET['id'] = $url[1];
                } else {
                    $_GET['action'] = $url[1];
                }
                if (isset($url[2])) {
                    $_GET['id'] = $url[2];
                }
            }
        }
        else{
            $_GET['page'] = 'Index';	//Иначе устанавливаем стартовую страницу
        }

		

        if (isset($_GET['page'])) {		//Проверим, установленна ли переменная
			//ucfirst — Преобразует первый символ строки в верхний регистр
            $controllerName = ucfirst($_GET['page']) . 'Controller';	//получим имя контроллера. Получается из параметра в $_GET['page'] и 'Controller'
            $methodName = isset($_GET['action']) ? $_GET['action'] : 'index';

            $controller = new $controllerName(); //Создаем объект контроллера. подключается файл с классом $controllerName.class.php
            /*
			Создаем ассоциативный массив с данными и ключами content_data, title, categories
			*/

			$data = [
                'content_data' => $controller->$methodName($_GET),	//Получаем content_data из метода объекта $controller
                'title' => $controller->title,				//Получаем title из метода title объекта $controller
                'categories' => Category::getCategories(0) //Вызываем статический метод getCategories класса Category. Является моделью
            ];

            $view = $controller->view . '/' . $methodName . '.html';
			
			
            if (!isset($_GET['asAjax'])) { //Если не Ajax, то отправляем все в шаблонизатор
                $loader = new Twig_Loader_Filesystem(Config::get('path_templates')); //Указываем место хранения шаблонов
                $twig = new Twig_Environment($loader); //Инициализируем Twing
                $template = $twig->loadTemplate($view); //Передаем название шаблона
				
				// передаём в шаблон переменные и значения
  				// выводим сформированное содержание
                echo $template->render($data); 
            } else {	//Если Ajax, то просто отправляем данные
                echo json_encode($data);
            }
        }
    }


}