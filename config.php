<?php
// приложение
$urlApp = "https://tech-api-ecwid.ru";

// DB connection
    $host = 'u72059.mysql.masterhost.ru';
    $dbname = '';
    $username = '';
    $password = '';

// Ecwid     
    $storeId = "";
    $bearer_public = '';
    $bearer_secret = '';
//    $client_secret = '2RaD1fZHmvfHOaqXKH0PfjwE7IUgswHP'; // your client_secret value; NOT your 'secret_*' access token.
//    'clientId'     => 'custom-app-16208263-1',
//    'clientSecret' => '2RaD1fZHmvfHOaqXKH0PfjwE7IUgswHP',

// MoySklad
    $isLoginAuth = true; // авторизация через логин.пароль
    $authMS = ['', '']; // замените на ваши учетные данные
    $bearer = 'Bearer ';

// Logs
    $isLoggingYandex = false; // логгирование в Яндекс
        $group = "ms-ecwid-reh"; // группа в логах Яндекса
        $Ya_ip = ""; // адрес сервере Яндекса
        $Ya_login = ''; // логин
        $Ya_pass = ''; // пароль
        
    $isLogFile = true; // делать лог-файл
    $isLogFileShort = true; // сокращенное логгирование в файл
    
    $logLifeTime = 10; // срок хранения логов - кол-во дней

// productstoecwid
    $limit = 100; // размер пакета товаров для обновления
    $addAbsent = true; // добавлять товары с нулевым остатком
    $enableAbsent = true; // активировать продажу товаров с нулевым остатком

// ordertoms  -- организация МС, отгружающая заказы    
    $organizationMSId = "";
    $fieldMSOrder = "Номер заказа в Эквидe"; // поле МС для хранения номера заказа в Эквид

    
// поле в МС по которому идентифицируются товары для синхронизации   
// поле в Эквид - артикул (scu)
//    $fieldSync = "id";
    $fieldSync = "externalCode";
//    $fieldSync = "article";


 
    